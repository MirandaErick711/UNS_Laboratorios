<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/Database.php';

/**
 * Endpoint PÚBLICO de solo lectura.
 * A propósito NO requiere session_start() ni validación de rol,
 * porque el requisito R3 exige que cualquier persona (estudiante
 * sin cuenta) pueda ver la disponibilidad de los laboratorios.
 *
 * Por seguridad:
 *  - Solo acepta GET (nunca escribe en la base de datos).
 *  - Solo expone reservas 'Aprobada' y 'Pendiente' (nunca datos
 *    sensibles como el motivo detallado o el correo del docente).
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Consulta deliberadamente limitada: NO seleccionamos id_usuario,
    // motivo completo ni ningún dato del docente, solo lo necesario
    // para pintar el bloque ocupado en el calendario público.
    $sql = "SELECT r.fecha, r.hora_inicio, r.hora_fin, r.estado,
                   l.nombre AS nombre_laboratorio
            FROM reservas r
            INNER JOIN laboratorios l ON l.id_laboratorio = r.id_laboratorio
            WHERE r.estado IN ('Aprobada', 'Pendiente')
            ORDER BY r.fecha, r.hora_inicio";

    $stmt = $pdo->query($sql);
    $reservas = $stmt->fetchAll();

    $eventos = [];
    foreach ($reservas as $r) {
        $eventos[] = [
            'title' => "{$r['nombre_laboratorio']} · Ocupado",
            'start' => "{$r['fecha']}T{$r['hora_inicio']}",
            'end'   => "{$r['fecha']}T{$r['hora_fin']}",
            'color' => $r['estado'] === 'Aprobada' ? '#198754' : '#fd7e14',
            'display' => 'block'
        ];
    }

    http_response_code(200);
    echo json_encode($eventos);

} catch (PDOException $e) {
    error_log('Error en api/reservas_publicas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al cargar la disponibilidad."]);
}