<?php

require_once __DIR__ . '/../config/Database.php';

class Laboratorio
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function obtenerTodos(): array
    {
        $sql = "SELECT 
                    id_laboratorio,
                    nombre,
                    ubicacion,
                    capacidad,
                    estado
                FROM laboratorios
                ORDER BY nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerOperativos(): array
    {
        $sql = "SELECT 
                    id_laboratorio,
                    nombre,
                    ubicacion,
                    capacidad
                FROM laboratorios
                WHERE estado = 'Operativo'
                ORDER BY nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}