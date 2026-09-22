<?php
require_once __DIR__ . '/../config/Database.php';

class Usuario {
    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Busca un usuario por su correo
    public function buscarPorCorreo(string $correo): array|false {
        $sql = "
            SELECT id_usuario, nombres, apellidos, correo, password_hash, rol, estado
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }
}