<?php
session_start();

// -------------------------------------------------------
// Control de acceso: solo Responsables con sesión activa
// -------------------------------------------------------
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Responsable') {
    header('Location: ../index.html');
    exit;
}

$nombreCompleto = htmlspecialchars($_SESSION['nombres'] . ' ' . $_SESSION['apellidos']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Responsable | Sistema de Reservas UNS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        .badge-pendiente {
            background-color: #fd7e14;
        }
        #tablaPendientes tbody tr {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .fila-procesando {
            opacity: 0.4;
            pointer-events: none;
        }
        #estadoVacio {
            display: none;
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
                <span class="badge bg-light text-dark ms-1">Responsable</span>
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

    <div class="mb-4">
        <h4 class="fw-bold mb-0" style="color: var(--uns-rojo);">
            <i class="bi bi-hourglass-split me-2"></i>Solicitudes Pendientes de Aprobación
        </h4>
        <small class="text-muted">Revisa y gestiona las reservas solicitadas por los docentes</small>
    </div>

    <!-- Alerta general de la página (errores de carga, etc.) -->
    <div id="alertaGeneral" class="alert py-2 d-none"></div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <!-- Estado vacío: se muestra cuando no hay pendientes -->
            <div id="estadoVacio" class="text-center py-5 text-muted">
                <i class="bi bi-check2-circle" style="font-size: 3rem; color: #198754;"></i>
                <p class="mt-3 mb-0 fs-5">No hay solicitudes pendientes por revisar.</p>
            </div>

            <!-- Spinner de carga inicial -->
            <div id="cargando" class="text-center py-5">
                <div class="spinner-border" style="color: var(--uns-rojo);" role="status"></div>
                <p class="text-muted mt-2 mb-0">Cargando solicitudes...</p>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle d-none" id="tablaPendientes">
                    <thead>
                        <tr class="table-light">
                            <th>Docente</th>
                            <th>Laboratorio</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Motivo</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaPendientes">
                        <!-- Las filas se insertan dinámicamente vía JS -->
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/responsable.js"></script>
</body>
</html>