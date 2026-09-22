<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metodo no permitido.'
    ]);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $sql = "
        SELECT
            r.fecha,
            r.hora_inicio,
            r.hora_fin,
            r.estado,
            r.motivo,
            l.nombre AS nombre_laboratorio
        FROM reservas r
        INNER JOIN laboratorios l
            ON l.id_laboratorio = r.id_laboratorio
        WHERE r.estado IN ('Aprobada', 'Pendiente')
        ORDER BY r.fecha, r.hora_inicio
    ";

    $stmt = $pdo->query($sql);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $eventos = [];

    foreach ($reservas as $r) {
        $practica = trim($r['motivo'] ?? '');
        $color = $r['estado'] === 'Aprobada' ? '#198754' : '#fd7e14';
        $eventos[] = [
            'title' => $r['nombre_laboratorio'] . ' - Ocupado',
            'start' => $r['fecha'] . 'T' . $r['hora_inicio'],
            'end' => $r['fecha'] . 'T' . $r['hora_fin'],
            'color' => $color,
            'display' => 'block',
            'extendedProps' => [
                'estado' => $r['estado'],
                'laboratorio' => $r['nombre_laboratorio'],
                'practica' => $practica
            ]
        ];
    }

    http_response_code(200);

    echo json_encode($eventos, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('Error en api/reservas_publicas.php: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar la disponibilidad.'
    ]);
}