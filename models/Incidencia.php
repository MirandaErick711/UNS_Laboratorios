<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Modelo Incidencia
 * Encapsula el acceso a la tabla `incidencias` y la sincronización
 * del estado del laboratorio asociado.
 */
class Incidencia
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Lista todas las incidencias con el nombre del laboratorio
     * y del técnico que las reportó (JOIN).
     */
    public function listarTodas(): array
    {
        $sql = "SELECT i.id_incidencia, i.descripcion, i.estado, i.fecha_reporte, i.fecha_resolucion,
                       l.id_laboratorio, l.nombre AS nombre_laboratorio,
                       CONCAT(u.nombres, ' ', u.apellidos) AS nombre_tecnico
                FROM incidencias i
                INNER JOIN laboratorios l ON l.id_laboratorio = i.id_laboratorio
                INNER JOIN usuarios u ON u.id_usuario = i.id_tecnico
                ORDER BY i.estado ASC, i.fecha_reporte DESC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lista los laboratorios (para el select del formulario de reporte).
     */
    public function listarLaboratorios(): array
    {
        $sql = "SELECT id_laboratorio, nombre, estado FROM laboratorios ORDER BY nombre ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Inserta una nueva incidencia (estado 'Pendiente' por defecto).
     */
    public function crear(int $idLaboratorio, int $idTecnico, string $descripcion): int
    {
        $sql = "INSERT INTO incidencias (id_laboratorio, id_tecnico, descripcion, estado)
                VALUES (:id_laboratorio, :id_tecnico, :descripcion, 'Pendiente')";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_laboratorio', $idLaboratorio, PDO::PARAM_INT);
        $stmt->bindParam(':id_tecnico', $idTecnico, PDO::PARAM_INT);
        $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Busca una incidencia por ID (para validar antes de actualizar).
     */
    public function buscarPorId(int $idIncidencia): array|false
    {
        $sql = "SELECT id_incidencia, id_laboratorio, estado FROM incidencias WHERE id_incidencia = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $idIncidencia, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Marca una incidencia como 'Resuelto' y registra la fecha de resolución.
     */
    public function marcarResuelta(int $idIncidencia): bool
    {
        $sql = "UPDATE incidencias 
                SET estado = 'Resuelto', fecha_resolucion = NOW() 
                WHERE id_incidencia = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $idIncidencia, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Actualiza el campo `estado` de un laboratorio
     * ('Operativo', 'Mantenimiento', 'Inactivo').
     */
    public function actualizarEstadoLaboratorio(int $idLaboratorio, string $nuevoEstado): void
    {
        $sql = "UPDATE laboratorios SET estado = :estado WHERE id_laboratorio = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado, PDO::PARAM_STR);
        $stmt->bindParam(':id', $idLaboratorio, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Verifica si un laboratorio tiene otras incidencias Pendientes
     * distintas a la que se acaba de resolver (para no reactivarlo
     * como Operativo si aún tiene otra avería abierta).
     */
    public function tieneOtrasIncidenciasPendientes(int $idLaboratorio, int $idIncidenciaExcluida): bool
    {
        $sql = "SELECT id_incidencia FROM incidencias 
                WHERE id_laboratorio = :id_laboratorio 
                  AND estado = 'Pendiente' 
                  AND id_incidencia != :id_excluida";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_laboratorio', $idLaboratorio, PDO::PARAM_INT);
        $stmt->bindParam(':id_excluida', $idIncidenciaExcluida, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getConexion(): PDO
    {
        return $this->conn;
    }
}