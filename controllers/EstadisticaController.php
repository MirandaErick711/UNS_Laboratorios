<?php
require_once __DIR__ . '/../models/Estadistica.php';

class EstadisticaController{
    private Estadistica $estadisticaModel;

    public function __construct(){
        $this->estadisticaModel = new Estadistica();
    }

    // Obtiene el resumen de estadisticas
    public function obtenerResumen(): array{
        return $this->estadisticaModel->obtenerResumen();
    }
}