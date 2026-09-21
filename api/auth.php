<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);

    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$correo = trim($input['correo'] ?? '');
$password = $input['password'] ?? '';

$correo = filter_var($correo, FILTER_SANITIZE_EMAIL);

try {

    $auth = new AuthController();

    $resultado = $auth->login($correo, $password);

    http_response_code($resultado['success'] ? 200 : 401);

    echo json_encode($resultado);

} catch (Throwable $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Error interno del servidor."
    ]);
}