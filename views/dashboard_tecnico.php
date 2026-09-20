<?php
session_start();

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Tecnico') {
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
    <title>Panel Técnico | Sistema de Reservas UNS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        .fila-resuelta { opacity: 0.4; pointer-events: none; }
        .lab-card { border-left: 5px solid #ccc; }
        .lab-operativo { border-left-color: #198754; }
        .lab-mantenimiento { border-left-color: #fd7e14; }
        .lab-inactivo { border-left-color: #6c757d; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--uns-rojo);">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-building me-2"></i>UNS · Reserva de Laboratorios
        </a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3 d-none d-md-inline">
                <i class="bi bi-person-circle me-1"></i>
                <?php echo $nombreCompleto; ?>
                <span class="badge bg-light text-dark ms-1">Técnico</span>
            </span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--uns-rojo);">
                <i class="bi bi-tools me-2"></i>Gestión de Incidencias Técnicas
            </h4>
            <small class="text-muted">Reporta averías de equipos o problemas de red</small>
        </div>
        <button type="button" class="btn btn-uns" data-bs-toggle="modal" data-bs-target="#modalNuevaIncidencia">
            <i class="bi bi-exclamation-triangle me-1"></i> Reportar Incidencia
        </button>
    </div>

    <!-- Estado de laboratorios (tarjetas) -->
    <div class="row g-3 mb-4" id="contenedorLaboratorios">
        <!-- Se llena dinámicamente vía JS -->
    </div>

    <!-- Tabla de incidencias -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold" style="color: var(--uns-rojo);">
            Historial de Incidencias
        </div>
        <div class="card-body">
            <div id="cargandoIncidencias" class="text-center py-4">
                <div class="spinner-border" style="color: var(--uns-rojo);"></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle d-none" id="tablaIncidencias">
                    <thead>
                        <tr class="table-light">
                            <th>Laboratorio</th>
                            <th>Descripción</th>
                            <th>Reportado por</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaIncidencias"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Nueva Incidencia -->
<div class="modal fade" id="modalNuevaIncidencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: var(--uns-rojo);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-exclamation-triangle me-2"></i>Reportar Incidencia
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formIncidencia">
                <div class="modal-body">
                    <div id="incidenciaAlert" class="alert py-2 d-none"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Laboratorio afectado</label>
                        <select class="form-select" id="id_laboratorio" required>
                            <option value="" selected disabled>Selecciona un laboratorio...</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Descripción de la avería</label>
                        <textarea class="form-control" id="descripcion" rows="3"
                                  placeholder="Ej: PC #05 no enciende / Sin conexión a internet en la red del lab" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-uns" id="btnGuardarIncidencia">
                        <span id="btnGuardarIncText">Registrar</span>
                        <span id="btnGuardarIncSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/tecnico.js"></script>
</body>
</html>