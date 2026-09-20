<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/Laboratorio.php';

try {

    $laboratorio = new Laboratorio();

    $datos = $laboratorio->obtenerOperativos();

    echo json_encode([
        'success' => true,
        'data' => $datos
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar los laboratorios',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}