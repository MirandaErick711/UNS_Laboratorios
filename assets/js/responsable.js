/**
 * responsable.js
 * Lógica del Dashboard del Responsable:
 *  - Carga las reservas pendientes vía fetch() y las pinta en una tabla
 *  - Gestiona los clics de "Aprobar" / "Rechazar" mediante PATCH
 *  - Elimina la fila de la tabla en tiempo real tras una respuesta exitosa
 */

document.addEventListener('DOMContentLoaded', function () {
    cargarPendientes();
    cargarIndicadores();
});

/**
 * Obtiene las reservas pendientes desde el backend y las renderiza.
 */
async function cargarPendientes() {
    const cargando = document.getElementById('cargando');
    const tabla = document.getElementById('tablaPendientes');
    const estadoVacio = document.getElementById('estadoVacio');

    try {
        const response = await fetch('../api/reservas_pendientes.php', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();

        cargando.classList.add('d-none');

        if (!data.success) {
            mostrarAlertaGeneral(data.message || 'No se pudieron cargar las solicitudes.', 'danger');
            return;
        }

        if (data.data.length === 0) {
            estadoVacio.style.display = 'block';
            return;
        }

        tabla.classList.remove('d-none');
        renderizarFilas(data.data);

    } catch (error) {
        console.error('Error al cargar pendientes:', error);
        cargando.classList.add('d-none');
        mostrarAlertaGeneral('Error de conexión con el servidor.', 'danger');
    }
}

/**
 * Construye y pinta las filas de la tabla a partir del arreglo de reservas.
 */
function renderizarFilas(reservas) {
    const cuerpo = document.getElementById('cuerpoTablaPendientes');
    cuerpo.innerHTML = ''; // Limpiamos por si se vuelve a llamar

    reservas.forEach(reserva => {
        const fila = document.createElement('tr');
        fila.id = `fila-reserva-${reserva.id_reserva}`;

        fila.innerHTML = `
            <td>${escaparHtml(reserva.nombre_docente)}</td>
            <td><span class="badge bg-secondary">${escaparHtml(reserva.nombre_laboratorio)}</span></td>
            <td>${formatearFecha(reserva.fecha)}</td>
            <td>${reserva.hora_inicio.substring(0, 5)} - ${reserva.hora_fin.substring(0, 5)}</td>
            <td class="text-truncate" style="max-width: 220px;" title="${escaparHtml(reserva.motivo)}">
                ${escaparHtml(reserva.motivo)}
            </td>
            <td class="text-center">
                <button class="btn btn-success btn-sm me-1 btn-aprobar" data-id="${reserva.id_reserva}">
                    <i class="bi bi-check-lg"></i> Aprobar
                </button>
                <button class="btn btn-danger btn-sm btn-rechazar" data-id="${reserva.id_reserva}">
                    <i class="bi bi-x-lg"></i> Rechazar
                </button>
            </td>
        `;

        cuerpo.appendChild(fila);
    });

    // Delegamos los eventos de los botones recién creados
    document.querySelectorAll('.btn-aprobar').forEach(btn => {
        btn.addEventListener('click', () => procesarReserva(btn.dataset.id, 'Aprobada'));
    });

    document.querySelectorAll('.btn-rechazar').forEach(btn => {
        btn.addEventListener('click', () => procesarReserva(btn.dataset.id, 'Rechazada'));
    });
}

/**
 * Envía el cambio de estado (Aprobada/Rechazada) al backend
 * y, si es exitoso, elimina la fila de la tabla con una transición suave.
 */
async function procesarReserva(idReserva, nuevoEstado) {
    const fila = document.getElementById(`fila-reserva-${idReserva}`);
    fila.classList.add('fila-procesando'); // feedback visual inmediato

    try {
        const response = await fetch('../api/reservas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_reserva: idReserva,
                estado: nuevoEstado
            })
        });

        const data = await response.json();

        if (data.success) {
            // Animación breve de salida antes de remover la fila del DOM
            fila.style.transform = 'translateX(30px)';
            fila.style.opacity = '0';

            setTimeout(() => {
                fila.remove();
                verificarTablaVacia();
            }, 300);

        } else {
            fila.classList.remove('fila-procesando');
            mostrarAlertaGeneral(data.message || 'No se pudo procesar la solicitud.', 'danger');
        }

    } catch (error) {
        console.error('Error al procesar reserva:', error);
        fila.classList.remove('fila-procesando');
        mostrarAlertaGeneral('Error de conexión con el servidor.', 'danger');
    }
}

/**
 * Si ya no quedan filas en la tabla, mostramos el estado "vacío".
 */
function verificarTablaVacia() {
    const cuerpo = document.getElementById('cuerpoTablaPendientes');
    if (cuerpo.children.length === 0) {
        document.getElementById('tablaPendientes').classList.add('d-none');
        document.getElementById('estadoVacio').style.display = 'block';
    }
}

function mostrarAlertaGeneral(mensaje, tipo) {
    const alertBox = document.getElementById('alertaGeneral');
    alertBox.textContent = mensaje;
    alertBox.className = `alert alert-${tipo} py-2`;
    alertBox.classList.remove('d-none');

    setTimeout(() => alertBox.classList.add('d-none'), 4000);
}

/**
 * Formatea 'YYYY-MM-DD' a un formato más legible, ej: "15 sept. 2026".
 */
function formatearFecha(fechaSQL) {
    const fecha = new Date(fechaSQL + 'T00:00:00');
    return fecha.toLocaleDateString('es-PE', { day: 'numeric', month: 'short', year: 'numeric' });
}

/**
 * Escapa texto antes de insertarlo en innerHTML, previniendo XSS
 * en caso de que un motivo contenga caracteres HTML.
 */
function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

/**
 * =========================================================
 * CARGAR INDICADORES
 * =========================================================
 */

async function cargarIndicadores() {

    const cargando =
        document.getElementById('cargandoIndicadores');

    const tabla =
        document.getElementById('tablaUsoLaboratorios');

    try {

        const response = await fetch(
            '../api/estadisticas.php',
            {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            }
        );


        const data = await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                'No se pudieron cargar los indicadores.'
            );
        }


        const indicadores =
            data.data;


        // =====================================================
        // INDICADORES GENERALES
        // =====================================================

        document.getElementById(
            'indicadorOperativos'
        ).textContent =
            indicadores.laboratorios.laboratorios_operativos;


        document.getElementById(
            'indicadorMantenimiento'
        ).textContent =
            indicadores.laboratorios.laboratorios_mantenimiento;


        document.getElementById(
            'indicadorPendientes'
        ).textContent =
            indicadores.reservas_pendientes;


        document.getElementById(
            'indicadorAprobadas'
        ).textContent =
            indicadores.reservas_aprobadas_mes;


        // =====================================================
        // TABLA DE USO
        // =====================================================

        renderizarUsoLaboratorios(
            indicadores.uso_por_laboratorio
        );


        if (cargando) {
            cargando.classList.add('d-none');
        }

        if (tabla) {
            tabla.classList.remove('d-none');
        }


    } catch (error) {

        console.error(
            'Error al cargar indicadores:',
            error
        );

        if (cargando) {
            cargando.classList.add('d-none');
        }

        const cuerpo =
            document.getElementById(
                'cuerpoUsoLaboratorios'
            );

        if (cuerpo) {

            cuerpo.innerHTML = `
                <tr>
                    <td
                        colspan="3"
                        class="text-center text-danger py-4"
                    >
                        No se pudieron cargar los indicadores.
                    </td>
                </tr>
            `;

            tabla.classList.remove('d-none');
        }
    }
}


/**
 * =========================================================
 * MOSTRAR USO POR LABORATORIO
 * =========================================================
 */

function renderizarUsoLaboratorios(laboratorios) {

    const cuerpo =
        document.getElementById(
            'cuerpoUsoLaboratorios'
        );


    if (!cuerpo) {
        return;
    }


    cuerpo.innerHTML = '';


    if (!laboratorios || laboratorios.length === 0) {

        cuerpo.innerHTML = `
            <tr>
                <td
                    colspan="3"
                    class="text-center text-muted py-4"
                >
                    No hay laboratorios registrados.
                </td>
            </tr>
        `;

        return;
    }


    laboratorios.forEach(function (laboratorio) {

        let badgeEstado = '';

        if (laboratorio.estado === 'Operativo') {

            badgeEstado =
                '<span class="badge bg-success">Operativo</span>';

        } else if (
            laboratorio.estado === 'Mantenimiento'
        ) {

            badgeEstado =
                '<span class="badge bg-warning text-dark">Mantenimiento</span>';

        } else {

            badgeEstado =
                '<span class="badge bg-secondary">' +
                escaparHtml(laboratorio.estado) +
                '</span>';
        }


        cuerpo.innerHTML += `
            <tr>

                <td class="fw-semibold">
                    ${escaparHtml(laboratorio.nombre)}
                </td>

                <td>
                    ${badgeEstado}
                </td>

                <td class="text-center">

                    <span class="badge bg-primary rounded-pill">
                        ${laboratorio.reservas_mes}
                    </span>

                </td>

            </tr>
        `;
    });
}