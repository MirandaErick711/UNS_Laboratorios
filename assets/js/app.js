// Logica del calendario y reservas del docente
document.addEventListener('DOMContentLoaded', function () {
    const formReserva = document.getElementById('formReserva');
    const modalNuevaReserva = document.getElementById('modalNuevaReserva');
    const btnGuardar = document.getElementById('btnGuardarReserva');

    if (!formReserva || !modalNuevaReserva || !btnGuardar) {
        console.error('No se encontraron elementos del formulario de reserva.');
        return;
    }

    // Reinicia el formulario al abrir
    modalNuevaReserva.addEventListener('show.bs.modal', function () {
        formReserva.reset();
        btnGuardar.disabled = false;
        btnGuardar.textContent = 'Guardar Reserva';
        ocultarAlertaModal();
    });

    const calendarEl = document.getElementById('calendar');

    if (calendarEl && typeof FullCalendar !== 'undefined') {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            height: 'auto',
            titleFormat: {
                year: 'numeric',
                month: 'long'
            },
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

            // Carga las reservas
            events: function (fetchInfo, successCallback, failureCallback) {
                fetch('../api/reservas.php', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('No se pudo cargar el calendario.');
                    }
                    return response.json();
                })
                .then(data => {
                    successCallback(data);
                })
                .catch(error => {
                    console.error('Error al cargar eventos:', error);
                    failureCallback(error);
                });
            },

            // Aplica el color segun el estado
            eventDidMount: function (info) {
                const estado = info.event.extendedProps.estado || '';
                let claseEstado = '';

                if (estado === 'Pendiente') {
                    claseEstado = 'evento-pendiente';
                } else if (estado === 'Aprobada') {
                    claseEstado = 'evento-aprobada';
                } else if (estado === 'Rechazada') {
                    claseEstado = 'evento-rechazada';
                } else if (estado === 'Cancelada') {
                    claseEstado = 'evento-cancelada';
                }

                if (claseEstado) {
                    info.el.classList.add(claseEstado);
                }
            },

            // Muestra la informacion de la reserva
            eventContent: function (info) {
                const inicio = info.event.start;
                const fin = info.event.end;

                const horaInicio = inicio
                    ? inicio.toLocaleTimeString('es-PE', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    })
                    : '';

                const horaFin = fin
                    ? fin.toLocaleTimeString('es-PE', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    })
                    : '';

                const estado = info.event.extendedProps.estado || '';
                const practica = info.event.extendedProps.practica || '';
                const laboratorio =
                    info.event.extendedProps.laboratorio || info.event.title;

                const contenedor = document.createElement('div');
                contenedor.classList.add('evento-reserva');

                const elementoHora = document.createElement('div');
                elementoHora.classList.add('evento-hora');
                elementoHora.textContent = `${horaInicio} - ${horaFin}`;

                const elementoLaboratorio = document.createElement('div');
                elementoLaboratorio.classList.add('evento-laboratorio');
                elementoLaboratorio.textContent = laboratorio;

                const elementoPractica = document.createElement('div');
                elementoPractica.classList.add('evento-practica');
                elementoPractica.textContent = practica;

                const elementoEstado = document.createElement('div');
                elementoEstado.classList.add('evento-estado');
                elementoEstado.textContent = estado;

                contenedor.appendChild(elementoHora);
                contenedor.appendChild(elementoLaboratorio);

                if (practica) {
                    contenedor.appendChild(elementoPractica);
                }

                contenedor.appendChild(elementoEstado);

                contenedor.title = practica
                    ? `${laboratorio} - ${practica}`
                    : laboratorio;

                return {
                    domNodes: [contenedor]
                };
            }
        });

        calendar.render();
        window.calendarInstance = calendar;
    } else {
        console.warn('FullCalendar no esta disponible o no existe #calendar');
    }

    // Registra una nueva reserva
    formReserva.addEventListener('submit', async function (e) {
        e.preventDefault();

        const payload = {
            id_laboratorio: document.getElementById('id_laboratorio').value,
            fecha: document.getElementById('fecha').value,
            hora_inicio: document.getElementById('hora_inicio').value,
            hora_fin: document.getElementById('hora_fin').value,
            motivo: document.getElementById('motivo').value.trim()
        };

        // Validaciones
        if (!payload.id_laboratorio) {
            mostrarAlertaModal('Debe seleccionar un laboratorio.', 'danger');
            return;
        }

        if (!payload.fecha) {
            mostrarAlertaModal('Debe seleccionar una fecha.', 'danger');
            return;
        }

        if (!payload.hora_inicio || !payload.hora_fin) {
            mostrarAlertaModal(
                'Debe indicar la hora de inicio y la hora de fin.',
                'danger'
            );
            return;
        }

        if (payload.hora_fin <= payload.hora_inicio) {
            mostrarAlertaModal(
                'La hora de fin debe ser posterior a la hora de inicio.',
                'danger'
            );
            return;
        }

        if (!payload.motivo) {
            mostrarAlertaModal('Debe indicar el motivo de la reserva.', 'danger');
            return;
        }

        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando...';
        ocultarAlertaModal();

        try {
            const response = await fetch('../api/reservas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const textoRespuesta = await response.text();
            let data;

            try {
                data = JSON.parse(textoRespuesta);
            } catch (error) {
                throw new Error(
                    'El servidor no devolvio JSON. Respuesta: ' +
                    textoRespuesta
                );
            }

            if (data.success) {
                mostrarAlertaModal(
                    data.message || 'Reserva registrada correctamente.',
                    'success'
                );

                // Actualiza el calendario
                if (window.calendarInstance) {
                    window.calendarInstance.refetchEvents();
                }

                setTimeout(function () {
                    const modalEl =
                        document.getElementById('modalNuevaReserva');
                    const modalInstance =
                        bootstrap.Modal.getInstance(modalEl);

                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    formReserva.reset();
                    btnGuardar.disabled = false;
                    btnGuardar.textContent = 'Guardar Reserva';
                    ocultarAlertaModal();
                }, 1200);
            } else {
                mostrarAlertaModal(
                    data.message || 'No se pudo registrar la reserva.',
                    'danger'
                );
            }
        } catch (error) {
            console.error('Error al registrar la reserva:', error);

            mostrarAlertaModal(
                'Error de conexion con el servidor.',
                'danger'
            );
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Guardar Reserva';
        }
    });

    // Reinicia el formulario al cerrar
    modalNuevaReserva.addEventListener('hidden.bs.modal', function () {
        formReserva.reset();
        btnGuardar.disabled = false;
        btnGuardar.textContent = 'Guardar Reserva';
        ocultarAlertaModal();
    });

    // Muestra un mensaje
    function mostrarAlertaModal(mensaje, tipo) {
        const alertBox = document.getElementById('reservaAlert');

        if (!alertBox) {
            return;
        }

        alertBox.textContent = mensaje;
        alertBox.className = `alert alert-${tipo} py-2`;
        alertBox.classList.remove('d-none');
    }

    // Oculta el mensaje
    function ocultarAlertaModal() {
        const alertBox = document.getElementById('reservaAlert');

        if (!alertBox) {
            return;
        }

        alertBox.classList.add('d-none');
    }
});