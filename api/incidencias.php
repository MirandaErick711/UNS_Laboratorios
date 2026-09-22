<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/IncidenciaController.php';

// Solo permite acceso al Tecnico
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Tecnico') {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Acceso restringido al Personal Técnico."
    ]);
    exit;
}
$metodo = $_SERVER['REQUEST_METHOD'];
$controller = new IncidenciaController();

try {
    switch ($metodo) {
        // Lista incidencias y laboratorios
        case 'GET':
            $respuesta = [
                "success" => true,
                "incidencias" => $controller->listarTodas(),
                "laboratorios" => $controller->listarLaboratorios()
            ];
            http_response_code(200);
            echo json_encode($respuesta);
            break;
        // Registra una incidencia
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if (!is_array($input)) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Cuerpo de la petición inválido."
                ]);
                exit;
            }

            $resultado = $controller->crear(
                (int) $_SESSION['id_usuario'],
                $input
            );

            http_response_code($resultado['http_code']);
            unset($resultado['http_code']);
            echo json_encode($resultado);
            break;

        // Resuelve una incidencia
        case 'PATCH':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) || empty($input['id_incidencia'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Debe indicar id_incidencia."
                ]);
                exit;
            }

            $resultado = $controller->resolver(
                (int) $input['id_incidencia']
            );

            http_response_code($resultado['http_code']);
            unset($resultado['http_code']);
            echo json_encode($resultado);
            break;

        default:
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "message" => "Método no permitido."
            ]);
            break;
    }
} catch (Exception $e) {
    error_log('Error inesperado en api/incidencias.php: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Error interno del servidor."
    ]);
}