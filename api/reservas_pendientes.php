<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/ReservaController.php';

// Solo el Responsable puede consultar pendientes
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Responsable') {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "No tienes permisos para esta acción."
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);
    exit;
}

try {
    $controller = new ReservaController();
    $pendientes = $controller->listarPendientes();

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "data" => $pendientes
    ]);
} catch (Exception $e) {
    error_log('Error en api/reservas_pendientes.php: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Error interno del servidor."
    ]);
}