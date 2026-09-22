// Carga los datos al iniciar
document.addEventListener('DOMContentLoaded', function () {
    cargarDatosIniciales();
    document.getElementById('formIncidencia').addEventListener('submit', registrarIncidencia);
});

// Carga laboratorios e incidencias
async function cargarDatosIniciales() {
    try {
        const response = await fetch('../api/incidencias.php', { method: 'GET' });
        const data = await response.json();

        if (!data.success) {
            mostrarAlertaModal(data.message || 'Error al cargar los datos.', 'danger');
            return;
        }

        renderizarLaboratorios(data.laboratorios);
        llenarSelectLaboratorios(data.laboratorios);
        renderizarIncidencias(data.incidencias);
    } catch (error) {
        console.error('Error al cargar datos iniciales:', error);
    }
}

// Muestra las tarjetas de laboratorios
function renderizarLaboratorios(laboratorios) {
    const contenedor = document.getElementById('contenedorLaboratorios');
    contenedor.innerHTML = '';

    const claseEstado = {
        'Operativo': 'lab-operativo',
        'Mantenimiento': 'lab-mantenimiento',
        'Inactivo': 'lab-inactivo'
    };

    const colorBadge = {
        'Operativo': 'success',
        'Mantenimiento': 'warning',
        'Inactivo': 'secondary'
    };

    laboratorios.forEach(lab => {
        const col = document.createElement('div');
        col.className = 'col-md-4 col-sm-6';
        col.innerHTML = `
            <div class="card shadow-sm border-0 lab-card ${claseEstado[lab.estado] || ''}">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-1">${escaparHtml(lab.nombre)}</h6>
                        <span class="badge bg-${colorBadge[lab.estado] || 'secondary'}">${lab.estado}</span>
                    </div>
                    <i class="bi bi-pc-display fs-2 text-muted"></i>
                </div>
            </div>
        `;
        contenedor.appendChild(col);
    });
}

// Llena el select de laboratorios
function llenarSelectLaboratorios(laboratorios) {
    const select = document.getElementById('id_laboratorio');
    select.innerHTML = '<option value="" selected disabled>Selecciona un laboratorio...</option>';

    laboratorios.forEach(lab => {
        const option = document.createElement('option');
        option.value = lab.id_laboratorio;
        option.textContent = `${lab.nombre} (${lab.estado})`;
        select.appendChild(option);
    });
}

// Muestra el historial de incidencias
function renderizarIncidencias(incidencias) {
    document.getElementById('cargandoIncidencias').classList.add('d-none');
    document.getElementById('tablaIncidencias').classList.remove('d-none');

    const cuerpo = document.getElementById('cuerpoTablaIncidencias');
    cuerpo.innerHTML = '';

    if (incidencias.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No hay incidencias registradas.</td></tr>`;
        return;
    }

    incidencias.forEach(inc => {
        const fila = document.createElement('tr');
        fila.id = `fila-incidencia-${inc.id_incidencia}`;

        if (inc.estado === 'Resuelto') {
            fila.classList.add('fila-resuelta');
        }

        const badgeEstado = inc.estado === 'Resuelto'
            ? '<span class="badge bg-success">Resuelto</span>'
            : '<span class="badge bg-warning text-dark">Pendiente</span>';

        const botonAccion = inc.estado === 'Pendiente'
            ? `<button class="btn btn-outline-success btn-sm btn-resolver" data-id="${inc.id_incidencia}">
                   <i class="bi bi-check-lg"></i> Resolver
               </button>`
            : `<span class="text-muted small">—</span>`;

        fila.innerHTML = `
            <td>${escaparHtml(inc.nombre_laboratorio)}</td>
            <td class="text-truncate" style="max-width: 250px;" title="${escaparHtml(inc.descripcion)}">
                ${escaparHtml(inc.descripcion)}
            </td>
            <td>${escaparHtml(inc.nombre_tecnico)}</td>
            <td>${formatearFecha(inc.fecha_reporte)}</td>
            <td>${badgeEstado}</td>
            <td class="text-center">${botonAccion}</td>
        `;
        cuerpo.appendChild(fila);
    });

    document.querySelectorAll('.btn-resolver').forEach(btn => {
        btn.addEventListener('click', () => resolverIncidencia(btn.dataset.id));
    });
}

// Registra una nueva incidencia
async function registrarIncidencia(e) {
    e.preventDefault();

    const btn = document.getElementById('btnGuardarIncidencia');
    const btnText = document.getElementById('btnGuardarIncText');
    const btnSpinner = document.getElementById('btnGuardarIncSpinner');

    const payload = {
        id_laboratorio: document.getElementById('id_laboratorio').value,
        descripcion: document.getElementById('descripcion').value.trim()
    };

    btn.disabled = true;
    btnText.textContent = 'Guardando...';
    btnSpinner.classList.remove('d-none');

    try {
        const response = await fetch('../api/incidencias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            mostrarAlertaModal(data.message, 'success');

            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalNuevaIncidencia')).hide();
                document.getElementById('formIncidencia').reset();
                ocultarAlertaModal();
                cargarDatosIniciales();
            }, 1000);
        } else {
            mostrarAlertaModal(data.message || 'No se pudo registrar la incidencia.', 'danger');
        }
    } catch (error) {
        console.error('Error al registrar incidencia:', error);
        mostrarAlertaModal('Error de conexión con el servidor.', 'danger');
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Registrar';
        btnSpinner.classList.add('d-none');
    }
}

// Marca una incidencia como resuelta
async function resolverIncidencia(idIncidencia) {
    try {
        const response = await fetch('../api/incidencias.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_incidencia: idIncidencia })
        });

        const data = await response.json();

        if (data.success) {
            cargarDatosIniciales();
        } else {
            alert(data.message || 'No se pudo resolver la incidencia.');
        }
    } catch (error) {
        console.error('Error al resolver incidencia:', error);
    }
}

// Muestra una alerta en el modal
function mostrarAlertaModal(mensaje, tipo) {
    const alertBox = document.getElementById('incidenciaAlert');
    alertBox.textContent = mensaje;
    alertBox.className = `alert alert-${tipo} py-2`;
    alertBox.classList.remove('d-none');
}

function ocultarAlertaModal() {
    document.getElementById('incidenciaAlert').classList.add('d-none');
}

function formatearFecha(fechaSQL) {
    const fecha = new Date(fechaSQL.replace(' ', 'T'));
    return fecha.toLocaleDateString('es-PE', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}

function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}