<?php

require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Aviso.php';
require_once __DIR__ . '/../models/Auditoria.php';
require_once __DIR__ . '/../services/CorreoInstitucional.php';

/**
 * Controlador de Reservas
 * Orquesta las operaciones relacionadas con las reservas.
 */
class ReservaController
{
    private Reserva $reservaModel;
    private Aviso $avisoModel;
    private Auditoria $auditoriaModel;
    private CorreoInstitucional $correoInstitucional;

    public function __construct()
    {
        $this->reservaModel = new Reserva();
        $this->avisoModel = new Aviso();
        $this->auditoriaModel = new Auditoria();
        $this->correoInstitucional = new CorreoInstitucional();
    }

    /**
     * Crea una nueva reserva de forma segura ante concurrencia.
     */
    public function crear(int $idUsuario, array $datos): array
    {
        // Validación de campos obligatorios
        $requeridos = [
            'id_laboratorio',
            'fecha',
            'hora_inicio',
            'hora_fin',
            'motivo'
        ];

        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return [
                    "success" => false,
                    "http_code" => 400,
                    "message" => "Falta el campo: {$campo}"
                ];
            }
        }

        $idLaboratorio = (int) $datos['id_laboratorio'];
        $fecha = $datos['fecha'];
        $horaInicio = $datos['hora_inicio'];
        $horaFin = $datos['hora_fin'];
        $motivo = trim($datos['motivo']);

        // Validación lógica de rango horario
        if ($horaFin <= $horaInicio) {
            return [
                "success" => false,
                "http_code" => 400,
                "message" =>
                    "La hora de fin debe ser posterior a la hora de inicio."
            ];
        }

        $pdo = $this->reservaModel->getConexion();

        try {

            // -------------------------------------------------------
            // INICIO DE TRANSACCIÓN
            // -------------------------------------------------------

            $pdo->beginTransaction();

            // 1. Verificar que el laboratorio este operativo
            if (!$this->reservaModel->laboratorioEstaOperativo($idLaboratorio)) {

                $pdo->rollBack();

                return [
                    "success" => false,
                    "http_code" => 409,
                    "message" =>
                        "El laboratorio seleccionado se encuentra en mantenimiento."
                ];
            }

            // 2. Verificar cruce de horarios
            if (
                $this->reservaModel->existeCruce(
                    $idLaboratorio,
                    $fecha,
                    $horaInicio,
                    $horaFin
                )
            ) {

                $pdo->rollBack();

                return [
                    "success" => false,
                    "http_code" => 409,
                    "message" =>
                        "El horario seleccionado ya está reservado. Selecciona otro horario."
                ];
            }

            // 3. Insertar reserva
            $idReserva = $this->reservaModel->crear(
                $idUsuario,
                $idLaboratorio,
                $fecha,
                $horaInicio,
                $horaFin,
                $motivo
            );

            // 4. Confirmar cambios
            $pdo->commit();

            return [
                "success" => true,
                "http_code" => 201,
                "message" =>
                    "Reserva registrada correctamente. Queda pendiente de aprobación.",
                "id_reserva" => $idReserva
            ];

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Error PDO al crear reserva: ' .
                $e->getMessage()
            );

            return [
                "success" => false,
                "http_code" => 500,
                "message" => "Error al crear la reserva."
            ];
        }
    }

    /**
     * Obtiene las reservas para el calendario.
     *
     * El id del usuario se recibe desde la sesión y se utiliza
     * para evitar exponer el motivo de otros docentes.
     */
    public function listarParaCalendario(int $idUsuario): array
    {
        $reservas =
            $this->reservaModel->listarParaCalendario(
                $idUsuario
            );

        $eventos = [];

        foreach ($reservas as $r) {

            $evento = [
                'id' => $r['id_reserva'],

                'title' =>
                    "{$r['nombre_laboratorio']} · {$r['estado']}",

                'start' =>
                    "{$r['fecha']}T{$r['hora_inicio']}",

                'end' =>
                    "{$r['fecha']}T{$r['hora_fin']}",

                'color' =>
                    $this->obtenerColorPorEstado(
                        $r['estado']
                    ),

                'extendedProps' => [
                    'estado' => $r['estado']
                ]
            ];

            // Solo se agrega el motivo cuando pertenece
            // al usuario que está consultando.
            if (
                $r['motivo'] !== null &&
                $r['motivo'] !== ''
            ) {
                $evento['extendedProps']['motivo'] =
                    $r['motivo'];
            }

            $eventos[] = $evento;
        }

        return $eventos;
    }

    /**
     * Mapea el estado de la reserva a un color hexadecimal.
     */
    private function obtenerColorPorEstado(
        string $estado
    ): string {
        return match ($estado) {

            'Aprobada' => '#198754',

            'Pendiente' => '#fd7e14',

            'Rechazada' => '#dc3545',

            'Cancelada' => '#6c757d',

            default => '#0d6efd'
        };
    }

    /**
     * Obtiene las reservas pendientes para el Responsable.
     */
    public function listarPendientes(): array
    {
        return $this->reservaModel->listarPendientes();
    }

    /**
     * Cambia el estado de una reserva.
     */
    public function cambiarEstado(
        int $idReserva,
        string $nuevoEstado
    ): array {

        $estadosValidos = [
            'Aprobada',
            'Rechazada'
        ];

        if (
            !in_array(
                $nuevoEstado,
                $estadosValidos,
                true
            )
        ) {
            return [
                "success" => false,
                "http_code" => 400,
                "message" => "Estado no válido."
            ];
        }

        $pdo = $this->reservaModel->getConexion();

        try {

            $pdo->beginTransaction();

            // Verificar que la reserva exista
            // y siga pendiente.
            $reserva =
                $this->reservaModel->buscarPorId(
                    $idReserva
                );

            if (!$reserva) {

                $pdo->rollBack();

                return [
                    "success" => false,
                    "http_code" => 404,
                    "message" => "La reserva no existe."
                ];
            }

            if ($reserva['estado'] !== 'Pendiente') {

                $pdo->rollBack();

                return [
                    "success" => false,
                    "http_code" => 409,
                    "message" =>
                        "Esta reserva ya fue procesada anteriormente."
                ];
            }

            // -------------------------------------------------------
            // ACTUALIZAR ESTADO
            // -------------------------------------------------------

            $this->reservaModel->actualizarEstado(
                $idReserva,
                $nuevoEstado
            );

            // -------------------------------------------------------
            // CREAR AVISO PARA EL DOCENTE
            // -------------------------------------------------------

            if ($nuevoEstado === 'Aprobada') {

                $titulo = 'Reserva aprobada';

                $mensaje =
                    "Tu reserva del laboratorio " .
                    $reserva['nombre_laboratorio'] .
                    " para el " .
                    $reserva['fecha'] .
                    " de " .
                    $reserva['hora_inicio'] .
                    " a " .
                    $reserva['hora_fin'] .
                    " fue aprobada.";

            } else {

                $titulo = 'Reserva rechazada';

                $mensaje =
                    "Tu reserva del laboratorio " .
                    $reserva['nombre_laboratorio'] .
                    " para el " .
                    $reserva['fecha'] .
                    " de " .
                    $reserva['hora_inicio'] .
                    " a " .
                    $reserva['hora_fin'] .
                    " fue rechazada.";
            }

            $this->avisoModel->crear(
                (int) $reserva['id_usuario'],
                $idReserva,
                $titulo,
                $mensaje
            );

            // -------------------------------------------------------
            // REGISTRAR AUDITORÍA
            // -------------------------------------------------------

            $this->auditoriaModel->registrar(
                (int) $_SESSION['id_usuario'],
                strtoupper(
                    $nuevoEstado === 'Aprobada'
                        ? 'APROBAR'
                        : 'RECHAZAR'
                ),
                'Reservas',
                "Reserva #{$idReserva} del laboratorio " .
                "{$reserva['nombre_laboratorio']} {$nuevoEstado}."
            );

            // -------------------------------------------------------
            // CONFIRMAR CAMBIOS
            // -------------------------------------------------------

            $pdo->commit();

            // -------------------------------------------------------
            // CORREO INSTITUCIONAL
            // -------------------------------------------------------
            //
            // El servicio actualmente no está disponible porque
            // todavía no existe una conexión real con el correo
            // institucional de la UNS.
            //
            // Si posteriormente se configura el servicio, esta
            // misma llamada permitirá enviar el correo sin modificar
            // el flujo principal de aprobación/rechazo.
            //

            if (
                !empty($reserva['correo_docente']) &&
                $this->correoInstitucional->estaDisponible()
            ) {

                $asunto =
                    "Reserva {$nuevoEstado} - " .
                    $reserva['nombre_laboratorio'];

                $correoEnviado =
                    $this->correoInstitucional->enviar(
                        $reserva['correo_docente'],
                        $asunto,
                        $mensaje
                    );

                if (!$correoEnviado) {
                    error_log(
                        "No se pudo enviar el correo de la reserva #{$idReserva}."
                    );
                }
            }

            return [
                "success" => true,
                "http_code" => 200,
                "message" =>
                    "Reserva {$nuevoEstado} correctamente."
            ];

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Error PDO al cambiar estado de reserva: ' .
                $e->getMessage()
            );

            return [
                "success" => false,
                "http_code" => 500,
                "message" =>
                    "Error al procesar la solicitud."
            ];
        }
    }
}