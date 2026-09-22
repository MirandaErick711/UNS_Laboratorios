<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/AvisoController.php';

// Verifica la sesion
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sesión no válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int) $_SESSION['id_usuario'];

try {
    $controller = new AvisoController();
    // Obtiene los avisos
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $avisos = $controller->listar($idUsuario);
        $noLeidos = $controller->contarNoLeidos($idUsuario);

        echo json_encode([
            'success' => true,
            'data' => $avisos,
            'no_leidos' => $noLeidos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Marca los avisos como leidos
    if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $controller->marcarTodosComoLeidos($idUsuario);

        echo json_encode([
            'success' => true,
            'message' => 'Avisos marcados como leídos.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Error en api/avisos.php: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'No se pudieron procesar los avisos.'
    ], JSON_UNESCAPED_UNICODE);
}