/**
 * app.js
 * Lógica del Dashboard del Docente:
 *  - Inicialización de FullCalendar (con eventos reales desde la BD)
 *  - Envío asíncrono del formulario de reservas (SPA con fetch)
 */

document.addEventListener('DOMContentLoaded', function () {

    // -----------------------------------------------------------
    // 1. INICIALIZACIÓN DE FULLCALENDAR (con fuente de datos real)
    // -----------------------------------------------------------
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana'
        },

        // -----------------------------------------------------------
        // Fuente de eventos dinámica: FullCalendar llama automáticamente
        // a esta función cada vez que necesita (re)pintar el calendario,
        // incluyendo cuando se llama a calendar.refetchEvents().
        // -----------------------------------------------------------
        events: function (fetchInfo, successCallback, failureCallback) {
            fetch('../api/reservas.php', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('No se pudo cargar el calendario.');
                    }
                    return response.json();
                })
                .then(data => {
                    // 'data' ya viene en el formato { id, title, start, end, color } listo para FullCalendar
                    successCallback(data);
                })
                .catch(error => {
                    console.error('Error al cargar eventos:', error);
                    failureCallback(error);
                });
        },

        // Tooltip simple con el motivo al pasar el mouse sobre un evento
        eventDidMount: function (info) {
            const motivo = info.event.extendedProps.motivo;
            if (motivo) {
                info.el.setAttribute('title', motivo);
            }
        }
    });

    calendar.render();

    // Guardamos la instancia en el scope global para poder refrescarla
    // desde el bloque de guardado de reservas (ver más abajo).
    window.calendarInstance = calendar;

    // -----------------------------------------------------------
    // 2. ENVÍO DEL FORMULARIO DE NUEVA RESERVA (fetch -> JSON)
    // -----------------------------------------------------------
    const formReserva = document.getElementById('formReserva');

    formReserva.addEventListener('submit', async function (e) {
        e.preventDefault();

        const alertBox = document.getElementById('reservaAlert');
        const btnGuardar = document.getElementById('btnGuardarReserva');
        const btnText = document.getElementById('btnGuardarText');
        const btnSpinner = document.getElementById('btnGuardarSpinner');

        const payload = {
            id_laboratorio: document.getElementById('id_laboratorio').value,
            fecha: document.getElementById('fecha').value,
            hora_inicio: document.getElementById('hora_inicio').value,
            hora_fin: document.getElementById('hora_fin').value,
            motivo: document.getElementById('motivo').value.trim()
        };

        if (payload.hora_fin <= payload.hora_inicio) {
            mostrarAlertaModal('La hora de fin debe ser posterior a la hora de inicio.', 'danger');
            return;
        }

        btnGuardar.disabled = true;
        btnText.textContent = 'Guardando...';
        btnSpinner.classList.remove('d-none');
        ocultarAlertaModal();

        try {
            const response = await fetch('../api/reservas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            // Importante: leemos el JSON incluso si el status es 409 o 400,
            // porque el backend siempre devuelve un mensaje útil en el body.
            const data = await response.json();

            if (data.success) {
                mostrarAlertaModal(data.message, 'success');

                // -----------------------------------------------------
                // Refrescamos el calendario al instante, sin recargar
                // la página, para que la nueva reserva aparezca ya.
                // -----------------------------------------------------
                window.calendarInstance.refetchEvents();

                setTimeout(() => {
                    const modalEl = document.getElementById('modalNuevaReserva');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    modalInstance.hide();

                    formReserva.reset();
                    ocultarAlertaModal();
                }, 1200);

            } else {
                // Aquí llegan tanto errores de validación (400) como
                // el caso clave: HTTP 409 por cruce de horario.
                mostrarAlertaModal(data.message || 'No se pudo registrar la reserva.', 'danger');
            }

        } catch (error) {
            console.error('Error en la solicitud:', error);
            mostrarAlertaModal('Error de conexión con el servidor.', 'danger');
        } finally {
            btnGuardar.disabled = false;
            btnText.textContent = 'Confirmar Reserva';
            btnSpinner.classList.add('d-none');
        }
    });

    function mostrarAlertaModal(mensaje, tipo) {
        const alertBox = document.getElementById('reservaAlert');
        alertBox.textContent = mensaje;
        alertBox.className = `alert alert-${tipo} py-2`;
        alertBox.classList.remove('d-none');
    }

    function ocultarAlertaModal() {
        const alertBox = document.getElementById('reservaAlert');
        alertBox.classList.add('d-none');
    }

});