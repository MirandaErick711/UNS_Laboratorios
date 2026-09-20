<?php

session_start();

require_once __DIR__ . '/../models/Laboratorio.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $laboratorio = new Laboratorio();

    $laboratorios = $laboratorio->obtenerOperativos();

    echo json_encode([
        'success' => true,
        'data' => $laboratorios
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los laboratorios'
    ]);
}