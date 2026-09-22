<?php
require_once __DIR__ . '/../config/Database.php';

class Reserva {
    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Verifica si el laboratorio esta operativo
    public function laboratorioEstaOperativo(int $idLaboratorio): bool {
        $sql = "
            SELECT id_laboratorio
            FROM laboratorios
            WHERE id_laboratorio = :id_laboratorio
              AND estado = 'Operativo'
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_laboratorio', $idLaboratorio, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    // Verifica si existe un cruce de horario
    public function existeCruce(
        int $idLaboratorio,
        string $fecha,
        string $horaInicio,
        string $horaFin
    ): bool {
        $sql = "
            SELECT id_reserva
            FROM reservas
            WHERE id_laboratorio = :id_laboratorio
              AND fecha = :fecha
              AND estado IN ('Pendiente', 'Aprobada')
              AND hora_inicio < :hora_fin
              AND hora_fin > :hora_inicio
            FOR UPDATE
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_laboratorio', $idLaboratorio, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':hora_inicio', $horaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':hora_fin', $horaFin, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Crea una reserva pendiente
    public function crear(
        int $idUsuario,
        int $idLaboratorio,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        string $motivo
    ): int {
        $sql = "
            INSERT INTO reservas (
                id_usuario,
                id_laboratorio,
                fecha,
                hora_inicio,
                hora_fin,
                motivo,
                estado
            )
            VALUES (
                :id_usuario,
                :id_laboratorio,
                :fecha,
                :hora_inicio,
                :hora_fin,
                :motivo,
                'Pendiente'
            )
        ";

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

    // Obtiene las reservas para el calendario
    public function listarParaCalendario(int $idUsuario): array {
        $sql = "
            SELECT
                r.id_reserva,
                r.fecha,
                r.hora_inicio,
                r.hora_fin,
                r.estado,
                CASE
                    WHEN r.id_usuario = :id_usuario
                    THEN r.motivo
                    ELSE NULL
                END AS motivo,
                l.nombre AS nombre_laboratorio
            FROM reservas r
            INNER JOIN laboratorios l
                ON l.id_laboratorio = r.id_laboratorio
            ORDER BY r.fecha, r.hora_inicio
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Devuelve la conexion para las transacciones
    public function getConexion(): PDO {
        return $this->conn;
    }

    // Obtiene las reservas pendientes
    public function listarPendientes(): array {
        $sql = "
            SELECT
                r.id_reserva,
                r.fecha,
                r.hora_inicio,
                r.hora_fin,
                r.motivo,
                l.nombre AS nombre_laboratorio,
                CONCAT(u.nombres, ' ', u.apellidos) AS nombre_docente
            FROM reservas r
            INNER JOIN laboratorios l
                ON l.id_laboratorio = r.id_laboratorio
            INNER JOIN usuarios u
                ON u.id_usuario = r.id_usuario
            WHERE r.estado = 'Pendiente'
            ORDER BY r.fecha ASC, r.hora_inicio ASC
        ";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll();
    }

    // Busca una reserva por ID
    public function buscarPorId(int $idReserva): array|false {
        $sql = "
            SELECT
                r.id_reserva,
                r.id_usuario,
                r.id_laboratorio,
                r.fecha,
                r.hora_inicio,
                r.hora_fin,
                r.estado,
                l.nombre AS nombre_laboratorio,
                u.correo AS correo_docente
            FROM reservas r
            INNER JOIN laboratorios l
                ON l.id_laboratorio = r.id_laboratorio
            INNER JOIN usuarios u
                ON u.id_usuario = r.id_usuario
            WHERE r.id_reserva = :id_reserva
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_reserva', $idReserva, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualiza el estado de una reserva
    public function actualizarEstado(
        int $idReserva,
        string $nuevoEstado
    ): bool {
        $sql = "
            UPDATE reservas
            SET estado = :estado
            WHERE id_reserva = :id_reserva
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado, PDO::PARAM_STR);
        $stmt->bindParam(':id_reserva', $idReserva, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
