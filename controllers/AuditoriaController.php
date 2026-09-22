<?php
require_once __DIR__ . '/../models/Auditoria.php';

class AuditoriaController {
    private Auditoria $auditoriaModel;

    public function __construct() {
        $this->auditoriaModel = new Auditoria();
    }

    // Registra una accion
    public function registrar(
        int $idUsuario,
        string $accion,
        string $modulo,
        string $detalle
    ): int {
        return $this->auditoriaModel->registrar(
            $idUsuario,
            $accion,
            $modulo,
            $detalle
        );
    }

    // Obtiene los registros de auditoria
    public function listar(): array {
        return $this->auditoriaModel->listar();
    }
}