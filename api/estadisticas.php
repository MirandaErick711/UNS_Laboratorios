<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/EstadisticaController.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Responsable') {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Acceso restringido al Responsable.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $controller = new EstadisticaController();
    $datos = $controller->obtenerResumen();

    echo json_encode([
        'success' => true,
        'data' => $datos
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'archivo' => $e->getFile(),
        'linea' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}