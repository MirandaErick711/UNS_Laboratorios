<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/ReservaController.php';

// -------------------------------------------------------
// Verificar sesión
// -------------------------------------------------------

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
        // GET
        // =====================================================

        case 'GET':

            // -------------------------------------------------
            // GET ?pendientes=1
            // Solo Responsable
            // -------------------------------------------------

            if (
                isset($_GET['pendientes']) &&
                $_GET['pendientes'] === '1'
            ) {

                if (
                    $_SESSION['rol'] !== 'Responsable'
                ) {

                    http_response_code(403);

                    echo json_encode([
                        "success" => false,
                        "message" =>
                            "Solo un Responsable puede consultar las reservas pendientes."
                    ], JSON_UNESCAPED_UNICODE);

                    exit;
                }

                $reservas =
                    $controller->listarPendientes();

                http_response_code(200);

                echo json_encode([
                    "success" => true,
                    "data" => $reservas
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            // -------------------------------------------------
            // Calendario
            // -------------------------------------------------

            $idUsuario =
                (int) $_SESSION['id_usuario'];

            $eventos =
                $controller->listarParaCalendario(
                    $idUsuario
                );

            http_response_code(200);

            echo json_encode(
                $eventos,
                JSON_UNESCAPED_UNICODE
            );

            break;


        // =====================================================
        // POST
        // =====================================================

        case 'POST':

            // Solo Docente puede crear reservas
            if (
                $_SESSION['rol'] !== 'Docente'
            ) {

                http_response_code(403);

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Solo los docentes pueden registrar reservas."
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $input =
                json_decode(
                    file_get_contents('php://input'),
                    true
                );

            if (!is_array($input)) {

                http_response_code(400);

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Cuerpo de la peticion invalido."
                ]);

                exit;
            }

            $resultado =
                $controller->crear(
                    (int) $_SESSION['id_usuario'],
                    $input
                );

            $httpCode =
                $resultado['http_code'] ?? 500;

            unset($resultado['http_code']);

            http_response_code($httpCode);

            echo json_encode(
                $resultado,
                JSON_UNESCAPED_UNICODE
            );

            break;


        // =====================================================
        // PATCH
        // =====================================================

        case 'PATCH':

            // Solo Responsable puede aprobar/rechazar
            if (
                $_SESSION['rol'] !== 'Responsable'
            ) {

                http_response_code(403);

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Solo un Responsable puede aprobar o rechazar reservas."
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $input =
                json_decode(
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
                    "message" =>
                        "Debe indicar id_reserva y estado."
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $resultado =
                $controller->cambiarEstado(
                    (int) $input['id_reserva'],
                    $input['estado']
                );

            $httpCode =
                $resultado['http_code'] ?? 500;

            unset($resultado['http_code']);

            http_response_code($httpCode);

            echo json_encode(
                $resultado,
                JSON_UNESCAPED_UNICODE
            );

            break;


        // =====================================================
        // OTROS METODOS
        // =====================================================

        default:

            http_response_code(405);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Metodo no permitido."
            ], JSON_UNESCAPED_UNICODE);

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
        "message" =>
            "Error interno del servidor."
    ], JSON_UNESCAPED_UNICODE);
}