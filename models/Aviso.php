<?php
require_once __DIR__ . '/../config/Database.php';

class Aviso {
    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Crea un aviso para un usuario
    public function crear(
        int $idUsuario,
        int $idReserva,
        string $titulo,
        string $mensaje
    ): int {
        $sql = "
            INSERT INTO avisos (id_usuario, id_reserva, titulo, mensaje, leido)
            VALUES (:id_usuario, :id_reserva, :titulo, :mensaje, 0)
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':id_reserva', $idReserva, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $titulo, PDO::PARAM_STR);
        $stmt->bindParam(':mensaje', $mensaje, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->conn->lastInsertId();
    }

    // Obtiene los avisos de un usuario
    public function listarPorUsuario(int $idUsuario): array {
        $sql = "
            SELECT id_aviso, id_reserva, titulo, mensaje, leido, fecha
            FROM avisos
            WHERE id_usuario = :id_usuario
            ORDER BY fecha DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cuenta los avisos no leidos
    public function contarNoLeidos(int $idUsuario): int {
        $sql = "
            SELECT COUNT(*)
            FROM avisos
            WHERE id_usuario = :id_usuario
              AND leido = 0
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    // Marca los avisos como leidos
    public function marcarTodosComoLeidos(int $idUsuario): bool {
        $sql = "
            UPDATE avisos
            SET leido = 1
            WHERE id_usuario = :id_usuario
              AND leido = 0
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }
}