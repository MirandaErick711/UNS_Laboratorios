<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

if (
    !isset($_SESSION['id_usuario']) ||
    !in_array($_SESSION['rol'], ['Docente', 'Responsable'], true)
) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para consultar los laboratorios.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

require_once __DIR__ . '/../models/Laboratorio.php';

try {

    $laboratorio = new Laboratorio();

    $datos = $laboratorio->obtenerOperativos();

    echo json_encode([
        'success' => true,
        'data' => $datos
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log('Error en api/laboratorios.php: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar los laboratorios'
    ], JSON_UNESCAPED_UNICODE);
}