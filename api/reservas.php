<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/ReservaController.php';

// Validar sesión
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Debes iniciar sesión."
    ]);

    exit;
}

try {

    $controller = new ReservaController();

    $metodo = $_SERVER['REQUEST_METHOD'];

    switch ($metodo) {

        // =====================================================
        // GET - Calendario
        // =====================================================

        case 'GET':

            $eventos = $controller->listarParaCalendario();

            http_response_code(200);

            echo json_encode(
                $eventos,
                JSON_UNESCAPED_UNICODE
            );

            break;


        // =====================================================
        // POST - Nueva reserva
        // =====================================================

        case 'POST':

            if ($_SESSION['rol'] !== 'Docente') {

                http_response_code(403);

                echo json_encode([
                    "success" => false,
                    "message" => "Solo los docentes pueden registrar reservas."
                ]);

                exit;
            }

            $input = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($input)) {

                http_response_code(400);

                echo json_encode([
                    "success" => false,
                    "message" => "Cuerpo de la peticion invalido."
                ]);

                exit;
            }

            $resultado = $controller->crear(
                (int) $_SESSION['id_usuario'],
                $input
            );

            $httpCode = $resultado['http_code'] ?? 500;

            unset($resultado['http_code']);

            http_response_code($httpCode);

            echo json_encode(
                $resultado,
                JSON_UNESCAPED_UNICODE
            );

            break;


        // =====================================================
        // PATCH - Aprobar / Rechazar
        // =====================================================

        case 'PATCH':

            if ($_SESSION['rol'] !== 'Responsable') {

                http_response_code(403);

                echo json_encode([
                    "success" => false,
                    "message" => "Solo un Responsable puede aprobar o rechazar reservas."
                ]);

                exit;
            }

            $input = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (
                !is_array($input) ||
                empty($input['id_reserva']) ||
                empty($input['estado'])
            ) {

                http_response_code(400);

                echo json_encode([
                    "success" => false,
                    "message" => "Debe indicar id_reserva y estado."
                ]);

                exit;
            }

            $resultado = $controller->cambiarEstado(
                (int) $input['id_reserva'],
                $input['estado']
            );

            $httpCode = $resultado['http_code'] ?? 500;

            unset($resultado['http_code']);

            http_response_code($httpCode);

            echo json_encode(
                $resultado,
                JSON_UNESCAPED_UNICODE
            );

            break;


        default:

            http_response_code(405);

            echo json_encode([
                "success" => false,
                "message" => "Metodo no permitido."
            ]);

            break;
    }

} catch (Throwable $e) {

    error_log(
        'Error inesperado en api/reservas.php: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Error interno del servidor.",
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}