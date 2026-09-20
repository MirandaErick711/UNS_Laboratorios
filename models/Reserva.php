<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Modelo Reserva
 * Encapsula el acceso a la tabla `reservas`, incluyendo la verificación
 * de cruces de horario y la consulta para el calendario (JOIN con laboratorios).
 */
class Reserva
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Verifica si existe una reserva (Pendiente o Aprobada) que se solape
     * en tiempo con el rango solicitado, para el mismo laboratorio y fecha.
     *
     * Lógica de solapamiento: dos rangos [A_inicio, A_fin] y [B_inicio, B_fin]
     * se cruzan si: A_inicio < B_fin  Y  A_fin > B_inicio
     *
     * Se usa SELECT ... FOR UPDATE dentro de una transacción para bloquear
     * las filas candidatas y evitar condiciones de carrera en concurrencia.
     */
    public function existeCruce(int $idLaboratorio, string $fecha, string $horaInicio, string $horaFin): bool
    {
        $sql = "SELECT id_reserva 
                FROM reservas 
                WHERE id_laboratorio = :id_laboratorio
                  AND fecha = :fecha
                  AND estado IN ('Pendiente', 'Aprobada')
                  AND hora_inicio < :hora_fin
                  AND hora_fin > :hora_inicio
                FOR UPDATE";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_laboratorio', $idLaboratorio, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':hora_inicio', $horaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':hora_fin', $horaFin, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Inserta una nueva reserva con estado 'Pendiente'.
     */
    public function crear(int $idUsuario, int $idLaboratorio, string $fecha, string $horaInicio, string $horaFin, string $motivo): int
    {
        $sql = "INSERT INTO reservas (id_usuario, id_laboratorio, fecha, hora_inicio, hora_fin, motivo, estado)
                VALUES (:id_usuario, :id_laboratorio, :fecha, :hora_inicio, :hora_fin, :motivo, 'Pendiente')";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':id_laboratorio', $idLaboratorio, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':hora_inicio', $horaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':hora_fin', $horaFin, PDO::PARAM_STR);
        $stmt->bindParam(':motivo', $motivo, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Obtiene todas las reservas con el nombre del laboratorio (JOIN),
     * listas para ser mapeadas al formato que espera FullCalendar.
     */
    public function listarParaCalendario(): array
    {
        $sql = "SELECT r.id_reserva, r.fecha, r.hora_inicio, r.hora_fin, r.estado, r.motivo,
                       l.nombre AS nombre_laboratorio
                FROM reservas r
                INNER JOIN laboratorios l ON l.id_laboratorio = r.id_laboratorio
                ORDER BY r.fecha, r.hora_inicio";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Acceso directo a la conexión, usado por el controlador
     * para manejar la transacción (beginTransaction/commit/rollBack).
     */
    public function getConexion(): PDO
    {
        return $this->conn;
    }
    
        /**
     * Obtiene todas las reservas en estado 'Pendiente', incluyendo
     * el nombre del docente (JOIN con usuarios) y del laboratorio.
     * Usado por el dashboard del Responsable.
     */
    public function listarPendientes(): array
    {
        $sql = "SELECT r.id_reserva, r.fecha, r.hora_inicio, r.hora_fin, r.motivo,
                       l.nombre AS nombre_laboratorio,
                       CONCAT(u.nombres, ' ', u.apellidos) AS nombre_docente
                FROM reservas r
                INNER JOIN laboratorios l ON l.id_laboratorio = r.id_laboratorio
                INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
                WHERE r.estado = 'Pendiente'
                ORDER BY r.fecha ASC, r.hora_inicio ASC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca una reserva por su ID. Se usa antes de actualizar
     * para confirmar que existe y sigue en estado 'Pendiente'.
     */
    public function buscarPorId(int $idReserva): array|false
    {
        $sql = "SELECT id_reserva, estado FROM reservas WHERE id_reserva = :id_reserva LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_reserva', $idReserva, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Actualiza el estado de una reserva (Aprobada / Rechazada).
     * Retorna true si se actualizó al menos una fila.
     */
    public function actualizarEstado(int $idReserva, string $nuevoEstado): bool
    {
        $sql = "UPDATE reservas SET estado = :estado WHERE id_reserva = :id_reserva";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado, PDO::PARAM_STR);
        $stmt->bindParam(':id_reserva', $idReserva, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}