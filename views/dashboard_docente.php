<?php
session_start();

// -------------------------------------------------------
// Control de acceso: solo Docentes con sesión activa
// -------------------------------------------------------
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Docente') {
    header('Location: ../index.html');
    exit;
}

// Datos de sesión para mostrar en la interfaz
$nombreCompleto = htmlspecialchars($_SESSION['nombres'] . ' ' . $_SESSION['apellidos']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente | Sistema de Reservas UNS</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- FullCalendar (CSS incluido en el bundle JS, no requiere hoja aparte) -->


    <!-- Estilos institucionales -->
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        /* Ajustes puntuales para que el calendario luzca dentro de una card */
        #calendar {
            background: #fff;
        }
        .fc-toolbar-title {
            font-size: 1.2rem !important;
            text-transform: capitalize;
        }
        .fc-daygrid-day-number {
            color: var(--uns-gris-texto);
        }
        .fc-button-primary {
            background-color: var(--uns-rojo) !important;
            border-color: var(--uns-rojo) !important;
        }
        .fc-button-primary:hover {
            background-color: var(--uns-rojo-oscuro) !important;
            border-color: var(--uns-rojo-oscuro) !important;
        }
        .fc-button-active {
            background-color: var(--uns-rojo-oscuro) !important;
        }
    </style>
</head>
<body class="bg-light">

<!-- ============================ -->
<!-- NAVBAR INSTITUCIONAL         -->
<!-- ============================ -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--uns-rojo);">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-building me-2"></i>UNS · Reserva de Laboratorios
        </a>

        <div class="d-flex align-items-center text-white">
            <span class="me-3 d-none d-md-inline">
                <i class="bi bi-person-circle me-1"></i>
                <?php echo $nombreCompleto; ?>
                <span class="badge bg-light text-dark ms-1">Docente</span>
            </span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
            </a>
        </div>
    </div>
</nav>

<!-- ============================ -->
<!-- CONTENIDO PRINCIPAL          -->
<!-- ============================ -->
<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--uns-rojo);">Mi Calendario de Reservas</h4>
            <small class="text-muted">Visualiza la disponibilidad y gestiona tus solicitudes</small>
        </div>

        <button type="button" class="btn btn-uns" data-bs-toggle="modal" data-bs-target="#modalNuevaReserva">
            <i class="bi bi-plus-circle me-1"></i> Nueva Reserva
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>

</div>

<!-- ============================ -->
<!-- MODAL: NUEVA RESERVA         -->
<!-- ============================ -->
<div class="modal fade" id="modalNuevaReserva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header text-white" style="background-color: var(--uns-rojo);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-calendar-plus me-2"></i>Nueva Reserva de Laboratorio
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formReserva">
                <div class="modal-body">

                    <!-- Alerta de resultado (éxito / error) dentro del modal -->
                    <div id="reservaAlert" class="alert py-2 d-none"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Laboratorio</label>
                        <select class="form-select" id="id_laboratorio" required>
                            <option value="" selected disabled>Selecciona un laboratorio...</option>
                            <!--
                                NOTA: Estas opciones son estáticas por ahora.
                                En el Paso 3 se cargarán dinámicamente desde
                                la tabla `laboratorios` vía fetch() a api/laboratorios.php
                            -->
                            <option value="1">Laboratorio 101</option>
                            <option value="2">Laboratorio 102</option>
                            <option value="3">Laboratorio 103 (Redes)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha</label>
                        <input type="date" class="form-control" id="fecha" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Hora Inicio</label>
                            <input type="time" class="form-control" id="hora_inicio" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Hora Fin</label>
                            <input type="time" class="form-control" id="hora_fin" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Motivo</label>
                        <textarea class="form-control" id="motivo" rows="2"
                                  placeholder="Ej: Práctica de Base de Datos - Grupo A" required></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-uns" id="btnGuardarReserva">
                        <span id="btnGuardarText">Confirmar Reserva</span>
                        <span id="btnGuardarSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>