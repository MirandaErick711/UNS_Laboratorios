<?php
require_once __DIR__ . '/../models/Incidencia.php';

/**
 * Controlador de Incidencias
 * Orquesta el registro y resolución de averías, sincronizando
 * el estado del laboratorio afectado mediante transacciones.
 */
class IncidenciaController
{
    private Incidencia $incidenciaModel;

    public function __construct()
    {
        $this->incidenciaModel = new Incidencia();
    }

    public function listarTodas(): array
    {
        return $this->incidenciaModel->listarTodas();
    }

    public function listarLaboratorios(): array
    {
        return $this->incidenciaModel->listarLaboratorios();
    }

    /**
     * Registra una nueva incidencia y pone el laboratorio en 'Mantenimiento'.
     */
    public function crear(int $idTecnico, array $datos): array
    {
        if (empty($datos['id_laboratorio']) || empty($datos['descripcion'])) {
            return ["success" => false, "http_code" => 400, "message" => "Debe indicar laboratorio y descripción."];
        }

        $idLaboratorio = (int) $datos['id_laboratorio'];
        $descripcion   = trim($datos['descripcion']);

        if (strlen($descripcion) < 5) {
            return ["success" => false, "http_code" => 400, "message" => "La descripción es demasiado corta."];
        }

        $pdo = $this->incidenciaModel->getConexion();

        try {
            $pdo->beginTransaction();

            $idIncidencia = $this->incidenciaModel->crear($idLaboratorio, $idTecnico, $descripcion);

            // Sincronizamos: el laboratorio pasa a 'Mantenimiento' automáticamente
            $this->incidenciaModel->actualizarEstadoLaboratorio($idLaboratorio, 'Mantenimiento');

            $pdo->commit();

            return [
                "success"    => true,
                "http_code"  => 201,
                "message"    => "Incidencia registrada. El laboratorio fue marcado en Mantenimiento.",
                "id_incidencia" => $idIncidencia
            ];

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error PDO al crear incidencia: ' . $e->getMessage());
            return ["success" => false, "http_code" => 500, "message" => "Error al registrar la incidencia."];
        }
    }

    /**
     * Marca una incidencia como resuelta y, si no quedan otras
     * incidencias pendientes para ese laboratorio, lo regresa a 'Operativo'.
     */
    public function resolver(int $idIncidencia): array
    {
        $pdo = $this->incidenciaModel->getConexion();

        try {
            $pdo->beginTransaction();

            $incidencia = $this->incidenciaModel->buscarPorId($idIncidencia);

            if (!$incidencia) {
                $pdo->rollBack();
                return ["success" => false, "http_code" => 404, "message" => "La incidencia no existe."];
            }

            if ($incidencia['estado'] === 'Resuelto') {
                $pdo->rollBack();
                return ["success" => false, "http_code" => 409, "message" => "Esta incidencia ya fue resuelta anteriormente."];
            }

            $this->incidenciaModel->marcarResuelta($idIncidencia);

            // Solo reactivamos el laboratorio si no tiene otras averías abiertas
            $tieneOtras = $this->incidenciaModel->tieneOtrasIncidenciasPendientes(
                (int) $incidencia['id_laboratorio'],
                $idIncidencia
            );

            if (!$tieneOtras) {
                $this->incidenciaModel->actualizarEstadoLaboratorio((int) $incidencia['id_laboratorio'], 'Operativo');
            }

            $pdo->commit();

            return ["success" => true, "http_code" => 200, "message" => "Incidencia marcada como resuelta."];

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error PDO al resolver incidencia: ' . $e->getMessage());
            return ["success" => false, "http_code" => 500, "message" => "Error al procesar la solicitud."];
        }
    }
}