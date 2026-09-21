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
 * =========================================================
 * CARGAR RESERVAS PENDIENTES
 * =========================================================
 */

async function cargarPendientes() {

    const cargando =
        document.getElementById('cargando');

    const tabla =
        document.getElementById('tablaPendientes');

    const cuerpo =
        document.getElementById('cuerpoTablaPendientes');

    const estadoVacio =
        document.getElementById('estadoVacio');

    try {

        const respuesta =
            await fetch('../api/reservas.php?pendientes=1');

        const resultado =
            await respuesta.json();

        console.log(
            'Respuesta pendientes:',
            resultado
        );

        if (!resultado.success) {

            throw new Error(
                resultado.message ||
                'No se pudieron cargar las reservas'
            );

        }

        cargando.classList.add('d-none');

        cuerpo.innerHTML = '';

        if (
            !resultado.data ||
            resultado.data.length === 0
        ) {

            tabla.classList.add('d-none');
            estadoVacio.style.display = 'block';

            return;
        }

        estadoVacio.style.display = 'none';

        tabla.classList.remove('d-none');

        renderizarFilas(resultado.data);

    } catch (error) {

        console.error(
            'Error al cargar reservas:',
            error
        );

        cargando.classList.add('d-none');

        estadoVacio.style.display = 'block';

        estadoVacio.innerHTML = `
            <i class="bi bi-exclamation-circle fs-2"></i>

            <p class="mt-2 mb-0">
                No se pudieron cargar las reservas.
            </p>
        `;

    }

}


/**
 * =========================================================
 * RENDERIZAR FILAS
 * =========================================================
 */

function renderizarFilas(reservas) {

    const cuerpo =
        document.getElementById(
            'cuerpoTablaPendientes'
        );

    cuerpo.innerHTML = '';

    reservas.forEach(function (reserva) {

        const fila =
            document.createElement('tr');

        fila.dataset.id =
            reserva.id_reserva;

        fila.innerHTML = `

            <td>
                ${escaparHtml(reserva.nombre_laboratorio)}
            </td>

            <td>
                ${escaparHtml(reserva.usuario)}
            </td>

            <td>
                ${escaparHtml(reserva.fecha)}
            </td>

            <td>
                ${escaparHtml(reserva.hora_inicio)}
                -
                ${escaparHtml(reserva.hora_fin)}
            </td>

            <td>
                ${escaparHtml(reserva.motivo)}
            </td>

            <td>
                <span class="badge badge-pendiente">
                    Pendiente
                </span>
            </td>

            <td class="text-center">

                <button
                    type="button"
                    class="btn btn-success btn-sm me-1"
                    onclick="procesarReserva(${reserva.id_reserva}, 'Aprobada')"
                >
                    <i class="bi bi-check-lg"></i>
                    Aprobar
                </button>

                <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    onclick="procesarReserva(${reserva.id_reserva}, 'Rechazada')"
                >
                    <i class="bi bi-x-lg"></i>
                    Rechazar
                </button>

            </td>

        `;

        cuerpo.appendChild(fila);

    });

}


/**
 * =========================================================
 * PROCESAR RESERVA
 * =========================================================
 */

async function procesarReserva(
    idReserva,
    nuevoEstado
) {

    const fila =
        document.querySelector(
            `#tablaPendientes tbody tr[data-id="${idReserva}"]`
        );

    if (!fila) {
        return;
    }

    fila.classList.add('fila-procesando');

    try {

        const respuesta =
            await fetch('../api/reservas.php', {

                method: 'PATCH',

                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify({

                    id_reserva: idReserva,

                    estado: nuevoEstado

                })

            });

        const data =
            await respuesta.json();

        console.log(
            'Respuesta procesar reserva:',
            data
        );

        if (!data.success) {

            throw new Error(
                data.message ||
                'No se pudo procesar la reserva'
            );

        }

        cargarIndicadores();

        fila.style.transform =
            'translateX(30px)';

        fila.style.opacity = '0';

        setTimeout(function () {

            fila.remove();

            verificarTablaVacia();

        }, 300);

        mostrarAlertaGeneral(
            data.message ||
            'Reserva procesada correctamente.',
            'success'
        );

    } catch (error) {

        console.error(
            'Error al procesar reserva:',
            error
        );

        fila.classList.remove(
            'fila-procesando'
        );

        mostrarAlertaGeneral(
            error.message ||
            'No se pudo procesar la reserva.',
            'danger'
        );

    }

}


/**
 * =========================================================
 * VERIFICAR SI LA TABLA QUEDO VACIA
 * =========================================================
 */

function verificarTablaVacia() {

    const cuerpo =
        document.getElementById(
            'cuerpoTablaPendientes'
        );

    const tabla =
        document.getElementById(
            'tablaPendientes'
        );

    const estadoVacio =
        document.getElementById(
            'estadoVacio'
        );

    if (
        !cuerpo ||
        cuerpo.children.length === 0
    ) {

        tabla.classList.add('d-none');

        estadoVacio.style.display =
            'block';

    }

}


/**
 * =========================================================
 * MOSTRAR ALERTA GENERAL
 * =========================================================
 */

function mostrarAlertaGeneral(
    mensaje,
    tipo
) {

    const alerta =
        document.getElementById(
            'alertaGeneral'
        );

    if (!alerta) {
        return;
    }

    alerta.className =
        `alert alert-${tipo} py-2`;

    alerta.textContent =
        mensaje;

    alerta.classList.remove(
        'd-none'
    );

    setTimeout(function () {

        alerta.classList.add(
            'd-none'
        );

    }, 4000);

}


/**
 * =========================================================
 * FORMATEAR FECHA
 * =========================================================
 */

function formatearFecha(fecha) {

    if (!fecha) {
        return '';
    }

    const partes =
        fecha.split('-');

    if (partes.length !== 3) {
        return fecha;
    }

    return (
        partes[2] +
        '/' +
        partes[1] +
        '/' +
        partes[0]
    );

}


/**
 * =========================================================
 * ESCAPAR HTML
 * =========================================================
 */

function escaparHtml(texto) {

    if (
        texto === null ||
        texto === undefined
    ) {

        return '';

    }

    return String(texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


/**
 * =========================================================
 * CARGAR INDICADORES
 * =========================================================
 */

async function cargarIndicadores() {

    try {

        const respuesta =
            await fetch(
                '../api/estadisticas.php'
            );

        const resultado =
            await respuesta.json();

        console.log(
            'Respuesta indicadores:',
            resultado
        );

        if (!resultado.success) {

            throw new Error(
                resultado.message ||
                'No se pudieron cargar los indicadores'
            );

        }

        const datos =
            resultado.data;


        /**
         * =====================================================
         * INDICADORES DE LABORATORIOS
         * =====================================================
         */

        if (datos.laboratorios) {

            const operativos =
                datos.laboratorios.laboratorios_operativos;

            const mantenimiento =
                datos.laboratorios.laboratorios_mantenimiento;

            const elementoOperativos =
                document.getElementById(
                    'indicadorOperativos'
                );

            const elementoMantenimiento =
                document.getElementById(
                    'indicadorMantenimiento'
                );

            if (elementoOperativos) {

                elementoOperativos.textContent =
                    operativos;

            }

            if (elementoMantenimiento) {

                elementoMantenimiento.textContent =
                    mantenimiento;

            }

        }


        /**
         * =====================================================
         * RESERVAS PENDIENTES
         * =====================================================
         */

        if (
            datos.reservas_pendientes !==
            undefined
        ) {

            const elementoPendientes =
                document.getElementById(
                    'indicadorPendientes'
                );

            if (elementoPendientes) {

                elementoPendientes.textContent =
                    datos.reservas_pendientes;

            }

        }


        /**
         * =====================================================
         * RESERVAS APROBADAS DEL MES
         * =====================================================
         */

        if (
            datos.reservas_aprobadas_mes !==
            undefined
        ) {

            const elementoAprobadas =
                document.getElementById(
                    'indicadorAprobadas'
                );

            if (elementoAprobadas) {

                elementoAprobadas.textContent =
                    datos.reservas_aprobadas_mes;

            }

        }


        /**
         * =====================================================
         * RESERVAS RECHAZADAS DEL MES
         * =====================================================
         */

        if (
            datos.reservas_rechazadas_mes !==
            undefined
        ) {

            const elementoRechazadas =
                document.getElementById(
                    'indicadorRechazadas'
                );

            if (elementoRechazadas) {

                elementoRechazadas.textContent =
                    datos.reservas_rechazadas_mes;

            }

        }


        /**
         * =====================================================
         * USO DE LABORATORIOS
         * =====================================================
         */

        renderizarUsoLaboratorios(
            datos.uso_por_laboratorio
        );

    } catch (error) {

        console.error(
            'Error al cargar indicadores:',
            error
        );

    }

}


/**
 * =========================================================
 * RENDERIZAR USO DE LABORATORIOS
 * =========================================================
 */

function renderizarUsoLaboratorios(laboratorios) {

    const cuerpo =
        document.getElementById(
            'cuerpoUsoLaboratorios'
        );

    const tabla =
        document.getElementById(
            'tablaUsoLaboratorios'
        );

    const cargando =
        document.getElementById(
            'cargandoIndicadores'
        );

    if (!cuerpo || !tabla || !cargando) {

        console.error(
            'No se encontraron los elementos de la tabla de uso.'
        );

        return;
    }

    // Ocultar mensaje de carga
    cargando.classList.add('d-none');

    // Mostrar tabla
    tabla.classList.remove('d-none');

    // Limpiar contenido anterior
    cuerpo.innerHTML = '';

    // Verificar si existen datos
    if (
        !laboratorios ||
        laboratorios.length === 0
    ) {

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

    // Crear filas
    laboratorios.forEach(function (laboratorio) {

        let badgeEstado = '';

        if (
            laboratorio.estado === 'Operativo'
        ) {

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

        const fila =
            document.createElement('tr');

        fila.innerHTML = `

            <td class="fw-semibold">
                ${escaparHtml(
                    laboratorio.nombre
                )}
            </td>

            <td>
                ${badgeEstado}
            </td>

            <td class="text-center">
                <span class="badge bg-primary rounded-pill">
                    ${laboratorio.reservas_mes}
                </span>
            </td>

        `;

        cuerpo.appendChild(fila);

    });

}

/**
 * =========================================================
 * CARGAR AUDITORIA
 * =========================================================
 */

async function cargarAuditoria() {

    console.log('1. cargarAuditoria() inicio');

    const cargando =
        document.getElementById('cargandoAuditoria');

    const sinAuditoria =
        document.getElementById('sinAuditoria');

    const contenedor =
        document.getElementById('contenedorAuditoria');

    const cuerpo =
        document.getElementById('cuerpoAuditoria');

    console.log('2. Elementos:', {
        cargando: cargando,
        sinAuditoria: sinAuditoria,
        contenedor: contenedor,
        cuerpo: cuerpo
    });

    try {

        cargando.classList.remove('d-none');
        sinAuditoria.classList.add('d-none');
        contenedor.classList.add('d-none');

        console.log('3. Antes del fetch');

        const respuesta =
            await fetch('../api/auditoria.php');

        console.log(
            '4. Fetch terminado',
            respuesta.status,
            respuesta.statusText
        );

        const texto =
            await respuesta.text();

        console.log(
            '5. Respuesta RAW:',
            texto
        );

        const resultado =
            JSON.parse(texto);

        console.log(
            '6. JSON convertido:',
            resultado
        );

        if (!resultado.success) {

            throw new Error(
                resultado.message ||
                'La API devolvio success=false'
            );

        }

        console.log('7. API correcta');

        cargando.classList.add('d-none');

        cuerpo.innerHTML = '';

        if (
            !resultado.data ||
            resultado.data.length === 0
        ) {

            console.log(
                '8. No hay registros'
            );

            sinAuditoria.classList.remove(
                'd-none'
            );

            return;
        }

        console.log(
            '8. Registros encontrados:',
            resultado.data.length
        );

        contenedor.classList.remove(
            'd-none'
        );

        resultado.data.forEach(
            function(registro) {

                console.log(
                    '9. Registro:',
                    registro
                );

                const fila =
                    document.createElement('tr');

                fila.innerHTML = `

                    <td>
                        ${escaparHtml(
                            registro.fecha
                        )}
                    </td>

                    <td>
                        ${escaparHtml(
                            registro.usuario
                        )}
                    </td>

                    <td>
                        <span class="badge bg-secondary">
                            ${escaparHtml(
                                registro.accion
                            )}
                        </span>
                    </td>

                    <td>
                        ${escaparHtml(
                            registro.modulo
                        )}
                    </td>

                    <td>
                        ${escaparHtml(
                            registro.detalle
                        )}
                    </td>

                `;

                cuerpo.appendChild(fila);

            }
        );

        console.log(
            '10. Auditoria cargada correctamente'
        );

    } catch (error) {

        console.error(
            'ERROR REAL AUDITORIA:',
            error
        );

        cargando.classList.add(
            'd-none'
        );

        contenedor.classList.add(
            'd-none'
        );

        sinAuditoria.classList.remove(
            'd-none'
        );

        sinAuditoria.innerHTML = `

            <i class="bi bi-exclamation-circle fs-2"></i>

            <p class="mt-2 mb-0">
                Error: ${escaparHtml(error.message)}
            </p>

        `;

    }
}