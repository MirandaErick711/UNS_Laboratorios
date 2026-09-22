<?php
require_once __DIR__ . '/../config/Database.php';

class Auditoria
{
    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Registra una accion en la auditoria
    public function registrar(
        int $idUsuario,
        string $accion,
        string $modulo,
        string $detalle
    ): int {
        $sql = "
            INSERT INTO auditoria (id_usuario, accion, modulo, detalle)
            VALUES (:id_usuario, :accion, :modulo, :detalle)
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
        $stmt->bindParam(':modulo', $modulo, PDO::PARAM_STR);
        $stmt->bindParam(':detalle', $detalle, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->conn->lastInsertId();
    }

    // Obtiene el historial de auditoria
    public function listar(): array {
        $sql = "
            SELECT
                a.id_auditoria,
                a.id_usuario,
                CONCAT(u.nombres, ' ', u.apellidos) AS usuario,
                u.rol,
                a.accion,
                a.modulo,
                a.detalle,
                a.fecha
            FROM auditoria a
            INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
            ORDER BY a.fecha DESC, a.id_auditoria DESC
        ";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}