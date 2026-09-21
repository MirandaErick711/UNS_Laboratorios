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

            <!-- Avisos -->
            <button
                type="button"
                class="btn btn-outline-light btn-sm me-3 position-relative"
                data-bs-toggle="modal"
                data-bs-target="#modalAvisos"
                onclick="cargarAvisos()"
                title="Ver avisos"
            >
                <i class="bi bi-bell me-1"></i>
                Avisos

                <span
                    id="contadorAvisos"
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark d-none"
                >
                    0
                </span>
            </button>

            <!-- Usuario -->
            <span class="me-3 d-none d-md-inline">
                <i class="bi bi-person-circle me-1"></i>
                <?php echo $nombreCompleto; ?>

                <span class="badge bg-light text-dark ms-1">
                    Docente
                </span>
            </span>

            <!-- Cerrar sesión -->
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

<!-- Modal Avisos -->
<div
    class="modal fade"
    id="modalAvisos"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <!-- Encabezado -->
            <div
                class="modal-header text-white"
                style="background-color: var(--uns-rojo);"
            >
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-bell me-2"></i>
                    Mis Avisos
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <!-- Contenido -->
            <div class="modal-body">

                <div
                    id="cargandoAvisos"
                    class="text-center py-4"
                >
                    <div
                        class="spinner-border"
                        style="color: var(--uns-rojo);"
                        role="status"
                    ></div>

                    <p class="text-muted mt-2 mb-0">
                        Cargando avisos...
                    </p>
                </div>

                <div
                    id="sinAvisos"
                    class="text-center text-muted py-4 d-none"
                >
                    <i class="bi bi-bell-slash fs-2"></i>

                    <p class="mt-2 mb-0">
                        No tienes avisos.
                    </p>
                </div>

                <div
                    id="listaAvisos"
                    class="list-group d-none"
                ></div>

            </div>

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
 * Cargar laboratorios
 */
async function cargarLaboratorios() {

    const select = document.getElementById('id_laboratorio');

    try {

        const respuesta = await fetch('../api/laboratorios.php');
        const resultado = await respuesta.json();

        if (!resultado.success) {
            throw new Error('No se pudieron cargar los laboratorios');
        }

        select.innerHTML =
            '<option value="">Seleccione un laboratorio</option>';

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

        console.error('Error laboratorios:', error);

        select.innerHTML =
            '<option value="">Error al cargar laboratorios</option>';
    }
}


/*
 * Cargar avisos
 */
async function cargarAvisos() {

    const cargando =
        document.getElementById('cargandoAvisos');

    const sinAvisos =
        document.getElementById('sinAvisos');

    const lista =
        document.getElementById('listaAvisos');

    try {

        const respuesta =
            await fetch('../api/avisos.php');

        const resultado =
            await respuesta.json();

        console.log('Respuesta de avisos:', resultado);

        if (!resultado.success) {
            throw new Error(
                resultado.message || 'Error al cargar avisos'
            );
        }

        cargando.classList.add('d-none');

        lista.innerHTML = '';

        /*
         * Si no hay avisos
         */
        if (!resultado.data || resultado.data.length === 0) {

            sinAvisos.classList.remove('d-none');
            lista.classList.add('d-none');

            return;
        }

        /*
         * Mostrar avisos
         */
        sinAvisos.classList.add('d-none');
        lista.classList.remove('d-none');

        resultado.data.forEach(function(aviso) {

            const elemento =
                document.createElement('div');

            elemento.className =
                'list-group-item';

            const estadoLeido =
                Number(aviso.leido) === 0
                    ? 'fw-bold'
                    : '';

            elemento.innerHTML = `
                <div class="d-flex justify-content-between">

                    <div>

                        <div class="${estadoLeido}">
                            <i class="bi bi-bell me-1"></i>
                            ${escapeHtml(aviso.titulo)}
                        </div>

                        <div class="text-muted mt-1">
                            ${escapeHtml(aviso.mensaje)}
                        </div>

                    </div>

                    <small class="text-muted ms-3 text-nowrap">
                        ${escapeHtml(aviso.fecha)}
                    </small>

                </div>
            `;

            lista.appendChild(elemento);
        });

        /*
         * Marcar como leídos
         */
        await marcarAvisosComoLeidos();

        mostrarContadorAvisos(0);

    } catch (error) {

        console.error(
            'Error al cargar avisos:',
            error
        );

        cargando.classList.add('d-none');

        lista.classList.add('d-none');

        sinAvisos.classList.remove('d-none');

        sinAvisos.innerHTML = `
            <i class="bi bi-exclamation-circle fs-2"></i>

            <p class="mt-2 mb-0">
                No se pudieron cargar los avisos.
            </p>
        `;
    }
}


/*
 * Cargar contador de avisos
 */
async function cargarContadorAvisos() {

    try {

        const respuesta =
            await fetch('../api/avisos.php');

        const resultado =
            await respuesta.json();

        if (!resultado.success) {
            return;
        }

        mostrarContadorAvisos(
            Number(resultado.no_leidos)
        );

    } catch (error) {

        console.error(
            'Error al cargar contador:',
            error
        );
    }
}


/*
 * Mostrar contador
 */
function mostrarContadorAvisos(cantidad) {

    const contador =
        document.getElementById('contadorAvisos');

    if (cantidad > 0) {

        contador.textContent = cantidad;
        contador.classList.remove('d-none');

    } else {

        contador.classList.add('d-none');
    }
}


/*
 * Marcar avisos como leídos
 */
async function marcarAvisosComoLeidos() {

    try {

        const respuesta =
            await fetch('../api/avisos.php', {
                method: 'PATCH'
            });

        const resultado =
            await respuesta.json();

        console.log(
            'Avisos marcados como leídos:',
            resultado
        );

    } catch (error) {

        console.error(
            'Error al marcar avisos:',
            error
        );
    }
}


/*
 * Proteger contenido recibido de la BD
 */
function escapeHtml(texto) {

    const div =
        document.createElement('div');

    div.textContent =
        texto ?? '';

    return div.innerHTML;
}


/*
 * Inicializar página
 */
document.addEventListener(
    'DOMContentLoaded',
    function() {

        cargarLaboratorios();

        cargarContadorAvisos();

    }
);

</script>