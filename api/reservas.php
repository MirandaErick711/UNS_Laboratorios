<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/ReservaController.php';

// Validar que el usuario tenga sesión
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Debes iniciar sesión."]);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$controller = new ReservaController();

try {
    switch ($metodo) {
        // =====================================================
        // GET → Listar reservas para pintar el calendario
        // =====================================================
        case 'GET':
            $eventos = $controller->listarParaCalendario();
            http_response_code(200);
            echo json_encode($eventos);
            break;

        // =====================================================
        // POST → Crear una nueva reserva (Solo Docente)
        // =====================================================
        case 'POST':
            if ($_SESSION['rol'] !== 'Docente') {
                http_response_code(403);
                echo json_encode(["success" => false, "message" => "Solo los docentes pueden registrar reservas."]);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Cuerpo de la petición inválido."]);
                exit;
            }
            $resultado = $controller->crear((int) $_SESSION['id_usuario'], $input);
            http_response_code($resultado['http_code']);
            unset($resultado['http_code']);
            echo json_encode($resultado);
            break;

        // =====================================================
        // PATCH → Aprobar o Rechazar una reserva (Solo Responsable)
        // =====================================================
        case 'PATCH':
            if ($_SESSION['rol'] !== 'Responsable') {
                http_response_code(403); 
                echo json_encode(["success" => false, "message" => "Solo un Responsable puede aprobar o rechazar reservas."]);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) || empty($input['id_reserva']) || empty($input['estado'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Debe indicar id_reserva y estado."]);
                exit;
            }
            $resultado = $controller->cambiarEstado((int) $input['id_reserva'], $input['estado']);
            http_response_code($resultado['http_code']);
            unset($resultado['http_code']);
            echo json_encode($resultado);
            break;

        // =====================================================
        // Método no soportado
        // =====================================================
        default:
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Método no permitido."]);
            break;
    }
} catch (Exception $e) {
    error_log('Error inesperado en api/reservas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error interno del servidor."]);
}