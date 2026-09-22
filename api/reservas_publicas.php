<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/Database.php';

/**
 * Endpoint PUBLICO de solo lectura.
 *
 * Permite consultar las reservas que deben aparecer
 * en el calendario publico.
 *
 * NO requiere iniciar sesion.
 *
 * Solo se muestran:
 * - Reservas Aprobadas
 * - Reservas Pendientes
 *
 * No se exponen:
 * - id_usuario
 * - datos personales del docente
 * - correo
 * - motivo de la reserva
 */

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

    /*
     * ==========================================================
     * CONSULTAR RESERVAS
     * ==========================================================
     *
     * Se obtiene solamente la informacion necesaria
     * para pintar visualmente la reserva en FullCalendar.
     *
     * La practica se obtiene desde el motivo porque actualmente
     * la tabla reservas utiliza ese campo para guardar la
     * descripcion de la actividad.
     *
     * No se devuelve ningun dato del docente.
     */

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
        ORDER BY
            r.fecha,
            r.hora_inicio
    ";

    $stmt = $pdo->query($sql);

    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eventos = [];

    foreach ($reservas as $r) {

        /*
         * ======================================================
         * PRACTICA
         * ======================================================
         *
         * El calendario del docente utiliza "practica".
         * Como la reserva actualmente guarda la descripcion
         * en "motivo", se utiliza ese valor.
         */

        $practica = trim($r['motivo'] ?? '');

        /*
         * ======================================================
         * COLOR
         * ======================================================
         */

        $color = '#fd7e14';

        if ($r['estado'] === 'Aprobada') {

            $color = '#198754';
        }

        /*
         * ======================================================
         * EVENTO FULLCALENDAR
         * ======================================================
         */

        $eventos[] = [

            /*
             * El titulo sirve como respaldo para FullCalendar.
             */
            'title' =>
                $r['nombre_laboratorio'] . ' - Ocupado',

            /*
             * Fecha y hora de inicio.
             */
            'start' =>
                $r['fecha'] . 'T' . $r['hora_inicio'],

            /*
             * Fecha y hora de finalizacion.
             */
            'end' =>
                $r['fecha'] . 'T' . $r['hora_fin'],

            /*
             * Color general del evento.
             */
            'color' => $color,

            /*
             * Mostrar normalmente.
             */
            'display' => 'block',

            /*
             * Informacion adicional utilizada por
             * eventContent y eventDidMount.
             */
            'extendedProps' => [

                'estado' =>
                    $r['estado'],

                'laboratorio' =>
                    $r['nombre_laboratorio'],

                'practica' =>
                    $practica
            ]
        ];
    }

    /*
     * ==========================================================
     * RESPUESTA
     * ==========================================================
     */

    http_response_code(200);

    echo json_encode(
        $eventos,
        JSON_UNESCAPED_UNICODE
    );

} catch (PDOException $e) {

    /*
     * El detalle del error solamente se registra
     * en el servidor.
     */

    error_log(
        'Error en api/reservas_publicas.php: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar la disponibilidad.'
    ]);
}