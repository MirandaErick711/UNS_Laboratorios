<?php

session_start();
// Solo pueden entrar docentes
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Docente') {
    header('Location: ../index.html');
    exit;
}

$nombreCompleto = htmlspecialchars(
    ($_SESSION['nombres'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? ''),
    ENT_QUOTES,
    'UTF-8'
);

$idUsuario = (int) $_SESSION['id_usuario'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente | Reserva de Laboratorios UNS</title>
    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <!-- Iconos -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >
    <!-- Estilos del proyecto -->
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        #calendar {
            background: white;
            min-height: 500px;
        }
        .fc-toolbar-title {
            font-size: 1.2rem !important;
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
            border-color: var(--uns-rojo-oscuro) !important;
        }
        .fc-event {
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-light">

<!-- Navbar -->
<nav
    class="navbar navbar-dark"
    style="background-color: var(--uns-rojo);"
>
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-building me-2"></i>
            UNS - Reserva de Laboratorios
        </a>

        <div class="d-flex align-items-center text-white">
            <span class="me-3 d-none d-md-inline">
                <i class="bi bi-person-circle me-1"></i>
                <?php echo $nombreCompleto; ?>
                <span class="badge bg-light text-dark ms-1">
                    Docente
                </span>
            </span>

            <a
                href="../logout.php"
                class="btn btn-outline-light btn-sm"
            >
                <i class="bi bi-box-arrow-right me-1"></i>
                Cerrar sesión
            </a>
        </div>
    </div>
</nav>

<!-- Contenido -->
<div class="container-fluid px-4 py-4">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4
                class="fw-bold mb-0"
                style="color: var(--uns-rojo);"
            >
                Mi Calendario de Reservas
            </h4>
            <small class="text-muted">
                Consulta la disponibilidad y registra tus solicitudes.
            </small>
        </div>

        <button
            type="button"
            class="btn btn-uns"
            data-bs-toggle="modal"
            data-bs-target="#modalNuevaReserva"
        >
            <i class="bi bi-plus-circle me-1"></i>
            Nueva Reserva
        </button>
    </div>

    <!-- Calendario -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- Modal Nueva Reserva -->
<div
    class="modal fade"
    id="modalNuevaReserva"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Encabezado -->
            <div
                class="modal-header text-white"
                style="background-color: var(--uns-rojo);"
            >
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-calendar-plus me-2"></i>
                    Nueva Reserva
                </h5>
                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <!-- Formulario -->
            <form id="formReserva">
                <div class="modal-body">
                    <!-- Mensaje -->
                    <div
                        id="reservaAlert"
                        class="alert py-2 d-none"
                        role="alert"
                    ></div>
                    <!-- Laboratorio -->
                    <div class="mb-3">
                        <label
                            for="id_laboratorio"
                            class="form-label"
                        >
                            Laboratorio
                        </label>
                        <select
                            class="form-select"
                            id="id_laboratorio"
                            name="id_laboratorio"
                            required
                        >
                            <option value="">
                                Cargando laboratorios...
                            </option>
                        </select>
                    </div>
                    <!-- Fecha -->
                    <div class="mb-3">
                        <label
                            for="fecha"
                            class="form-label"
                        >
                            Fecha
                        </label>
                        <input
                            type="date"
                            class="form-control"
                            id="fecha"
                            name="fecha"
                            required
                        >
                    </div>
                    <!-- Horarios -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label
                                for="hora_inicio"
                                class="form-label"
                            >
                                Hora de inicio
                            </label>
                            <input
                                type="time"
                                class="form-control"
                                id="hora_inicio"
                                name="hora_inicio"
                                required
                            >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label
                                for="hora_fin"
                                class="form-label"
                            >
                                Hora de fin
                            </label>
                            <input
                                type="time"
                                class="form-control"
                                id="hora_fin"
                                name="hora_fin"
                                required
                            >
                        </div>
                    </div>
                    <!-- Motivo -->
                    <div class="mb-2">
                        <label
                            for="motivo"
                            class="form-label"
                        >
                            Motivo
                        </label>
                        <textarea
                            class="form-control"
                            id="motivo"
                            name="motivo"
                            rows="3"
                            placeholder="Ejemplo: Practica de Electronica"
                            required
                        ></textarea>
                    </div>
                </div>
                <!-- Botones -->
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="btn btn-uns"
                        id="btnGuardarReserva"
                    >
                        Guardar Reserva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<!-- FullCalendar -->
<script
    src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"
></script>

<!-- JavaScript del proyecto -->
<script src="../assets/js/app.js"></script>
<script>
/*
 * Cargar laboratorios desde la base de datos
 */
async function cargarLaboratorios() {
    const select = document.getElementById('id_laboratorio');
    try {
        const respuesta = await fetch('../api/laboratorios.php');
        const resultado = await respuesta.json();
        if (!resultado.success) {
            throw new Error('No se pudieron cargar los laboratorios');
        }
        select.innerHTML ='<option value="">Seleccione un laboratorio</option>';
        resultado.data.forEach(function(laboratorio) {
            const option = document.createElement('option');

            option.value = laboratorio.id_laboratorio;

            option.textContent =
                laboratorio.nombre +
                ' - Capacidad: ' +
                laboratorio.capacidad;
            select.appendChild(option);
        });
    } catch (error) {
        console.error(error);
        select.innerHTML ='<option value="">Error al cargar laboratorios</option>';
    }
}

/*
 * Cargar laboratorios cuando se abre la página
 */
document.addEventListener(
    'DOMContentLoaded',
    function() {
        cargarLaboratorios();
    }
);
</script>
</body>
</html>