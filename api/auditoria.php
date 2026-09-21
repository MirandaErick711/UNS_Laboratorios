<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/AuditoriaController.php';

if (
    !isset($_SESSION['id_usuario']) ||
    $_SESSION['rol'] !== 'Responsable'
) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Acceso restringido al Responsable.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $controller = new AuditoriaController();

    $datos = $controller->listar();

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'data' => $datos
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log(
        'Error en api/auditoria.php: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'No se pudo cargar la auditoria.'
    ], JSON_UNESCAPED_UNICODE);
}