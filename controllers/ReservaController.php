<?php
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Aviso.php';

/**
 * Controlador de Reservas
 * Orquesta la transacción SQL para crear reservas sin cruces de horario,
 * y prepara los datos para el calendario.
 */
class ReservaController
{
    private Reserva $reservaModel;
    private Aviso $avisoModel;

    public function __construct()
    {
        $this->reservaModel = new Reserva();
        $this->avisoModel = new Aviso();
    }

    /**
     * Crea una nueva reserva de forma segura ante concurrencia.
     */
    public function crear(int $idUsuario, array $datos): array
    {
        // Validación de campos obligatorios
        $requeridos = ['id_laboratorio', 'fecha', 'hora_inicio', 'hora_fin', 'motivo'];
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return ["success" => false, "http_code" => 400, "message" => "Falta el campo: {$campo}"];
            }
        }

        $idLaboratorio = (int) $datos['id_laboratorio'];
        $fecha         = $datos['fecha'];
        $horaInicio    = $datos['hora_inicio'];
        $horaFin       = $datos['hora_fin'];
        $motivo        = trim($datos['motivo']);

        // Validación lógica de rango horario
        if ($horaFin <= $horaInicio) {
            return ["success" => false, "http_code" => 400, "message" => "La hora de fin debe ser posterior a la hora de inicio."];
        }

        $pdo = $this->reservaModel->getConexion();

        try {
            // -----------------------------------------------------------
            // INICIO DE TRANSACCIÓN
            // -----------------------------------------------------------
            $pdo->beginTransaction();

            // 1. Verificar que el laboratorio este operativo
            if (!$this->reservaModel->laboratorioEstaOperativo($idLaboratorio)) {

                $pdo->rollBack();

                return [
                    "success" => false,
                    "http_code" => 409,
                    "message" => "El laboratorio seleccionado se encuentra en mantenimiento."
                ];
            }

            // 2. Verificar cruce de horarios (con bloqueo FOR UPDATE)
            if ($this->reservaModel->existeCruce($idLaboratorio, $fecha, $horaInicio, $horaFin)) {
                $pdo->rollBack();
                return [
                    "success"   => false,
                    "http_code" => 409, // Conflict
                    "message"   => "El laboratorio ya está reservado en ese horario. Elige otro rango."
                ];
            }

            // 3. Insertar la reserva (estado 'Pendiente' por defecto)
            $idReserva = $this->reservaModel->crear($idUsuario, $idLaboratorio, $fecha, $horaInicio, $horaFin, $motivo);

            // 4. Confirmar cambios
            $pdo->commit();
            // -----------------------------------------------------------
            // FIN DE TRANSACCIÓN
            // -----------------------------------------------------------

            return [
                "success"   => true,
                "http_code" => 201, // Created
                "message"   => "Reserva registrada correctamente. Queda pendiente de aprobación.",
                "id_reserva" => $idReserva
            ];

        } catch (PDOException $e) {
            // Si algo falla a nivel de BD (ej. restricción UNIQUE), revertimos todo
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error PDO al crear reserva: ' . $e->getMessage());

            return [
                "success"   => false,
                "http_code" => 500,
                "message"   => "ERROR SQL: " . $e->getMessage() 
            ];
        }
    }

    /**
     * Obtiene las reservas y las transforma al formato JSON
     * que consume FullCalendar directamente en su propiedad `events`.
     */
    public function listarParaCalendario(): array
    {
        $reservas = $this->reservaModel->listarParaCalendario();
        $eventos = [];

        foreach ($reservas as $r) {
            $eventos[] = [
                'id'    => $r['id_reserva'],
                'title' => "{$r['nombre_laboratorio']} · {$r['estado']}",
                'start' => "{$r['fecha']}T{$r['hora_inicio']}",
                'end'   => "{$r['fecha']}T{$r['hora_fin']}",
                'color' => $this->obtenerColorPorEstado($r['estado']),
                'extendedProps' => [
                    'motivo' => $r['motivo'],
                    'estado' => $r['estado']
                ]
            ];
        }

        return $eventos;
    }

    /**
     * Mapea el estado de la reserva a un color hexadecimal
     * para que FullCalendar pinte el bloque correspondiente.
     */
    private function obtenerColorPorEstado(string $estado): string
    {
        return match ($estado) {
            'Aprobada'  => '#198754', // Verde (Bootstrap success)
            'Pendiente' => '#fd7e14', // Naranja (Bootstrap warning/orange)
            'Rechazada' => '#dc3545', // Rojo (Bootstrap danger)
            'Cancelada' => '#6c757d', // Gris (por si acaso)
            default     => '#0d6efd',
        };
    }

        /**
     * Obtiene las reservas pendientes, listas para pintar en la tabla
     * del dashboard del Responsable.
     */
    public function listarPendientes(): array
    {
        return $this->reservaModel->listarPendientes();
    }

    /**
     * Cambia el estado de una reserva a 'Aprobada' o 'Rechazada'.
     * Aplica una transacción simple para mantener consistencia,
     * aunque la operación sea de una sola escritura.
     */
    public function cambiarEstado(int $idReserva, string $nuevoEstado): array
    {
        // Solo permitimos transicionar a estos dos estados desde este endpoint
        $estadosValidos = ['Aprobada', 'Rechazada'];
        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            return ["success" => false, "http_code" => 400, "message" => "Estado no válido."];
        }

        $pdo = $this->reservaModel->getConexion();

        try {
            $pdo->beginTransaction();

            // Verificamos que la reserva exista y siga Pendiente
            // (evita, por ejemplo, aprobar dos veces la misma solicitud
            // si el Responsable tiene dos pestañas abiertas).
            $reserva = $this->reservaModel->buscarPorId($idReserva);

            if (!$reserva) {
                $pdo->rollBack();
                return ["success" => false, "http_code" => 404, "message" => "La reserva no existe."];
            }

            if ($reserva['estado'] !== 'Pendiente') {
                $pdo->rollBack();
                return ["success" => false, "http_code" => 409, "message" => "Esta reserva ya fue procesada anteriormente."];
            }

        $this->reservaModel->actualizarEstado($idReserva, $nuevoEstado);

        // Crear aviso para el docente
        if ($nuevoEstado === 'Aprobada') {

            $titulo = 'Reserva aprobada';

            $mensaje = "Tu reserva del laboratorio {$reserva['nombre_laboratorio']} " .
                    "para el {$reserva['fecha']} de {$reserva['hora_inicio']} " .
                    "a {$reserva['hora_fin']} fue aprobada.";

        } else {

            $titulo = 'Reserva rechazada';

            $mensaje = "Tu reserva del laboratorio {$reserva['nombre_laboratorio']} " .
                    "para el {$reserva['fecha']} de {$reserva['hora_inicio']} " .
                    "a {$reserva['hora_fin']} fue rechazada.";
        }

        $this->avisoModel->crear(
            (int) $reserva['id_usuario'],
            $idReserva,
            $titulo,
            $mensaje
        );

        $pdo->commit();

            return [
                "success"   => true,
                "http_code" => 200,
                "message"   => "Reserva {$nuevoEstado} correctamente."
            ];

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error PDO al cambiar estado de reserva: ' . $e->getMessage());

            return ["success" => false, "http_code" => 500, "message" => "Error al procesar la solicitud."];
        }
    }
}