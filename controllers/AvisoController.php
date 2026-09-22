<?php
require_once __DIR__ . '/../models/Aviso.php';

class AvisoController {
    private Aviso $avisoModel;

    public function __construct() {
        $this->avisoModel = new Aviso();
    }

    // Obtiene los avisos del usuario
    public function listar(int $idUsuario): array {
        return $this->avisoModel->listarPorUsuario($idUsuario);
    }

    // Cuenta los avisos no leidos
    public function contarNoLeidos(int $idUsuario): int {
        return $this->avisoModel->contarNoLeidos($idUsuario);
    }

    // Marca los avisos como leidos
    public function marcarTodosComoLeidos(int $idUsuario): bool {
        return $this->avisoModel->marcarTodosComoLeidos($idUsuario);
    }
}