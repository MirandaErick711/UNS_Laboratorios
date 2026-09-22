/**
 * app.js
 * Logica del Dashboard del Docente:
 *  - Inicializacion de FullCalendar
 *  - Carga de reservas desde la BD
 *  - Registro de nuevas reservas
 */

document.addEventListener('DOMContentLoaded', function () {

    // ===========================================================
    // ELEMENTOS DEL FORMULARIO Y MODAL
    // ===========================================================

    const formReserva =
        document.getElementById('formReserva');

    const modalNuevaReserva =
        document.getElementById('modalNuevaReserva');

    const btnGuardar =
        document.getElementById('btnGuardarReserva');


    // Verificar que los elementos existan

    if (!formReserva) {
        console.error(
            'No se encontro el formulario formReserva'
        );
        return;
    }

    if (!modalNuevaReserva) {
        console.error(
            'No se encontro el modal modalNuevaReserva'
        );
        return;
    }

    if (!btnGuardar) {
        console.error(
            'No se encontro el boton btnGuardarReserva'
        );
        return;
    }


    // ===========================================================
    // REINICIAR FORMULARIO AL ABRIR EL MODAL
    // ===========================================================

    modalNuevaReserva.addEventListener(
        'show.bs.modal',
        function () {

            formReserva.reset();

            btnGuardar.disabled = false;

            btnGuardar.textContent =
                'Guardar Reserva';

            ocultarAlertaModal();

            console.log(
                'Modal de nueva reserva abierto'
            );
        }
    );


    // ===========================================================
    // 1. INICIALIZACION DEL CALENDARIO
    // ===========================================================

    const calendarEl =
        document.getElementById('calendar');


    if (
        calendarEl &&
        typeof FullCalendar !== 'undefined'
    ) {

        const calendar =
            new FullCalendar.Calendar(
                calendarEl,
                {

                    initialView:
                        'dayGridMonth',

                    locale:
                        'es',

                    height:
                        'auto',

                    titleFormat: {
                        year: 'numeric',
                        month: 'long'
                    },


                    // ------------------------------------------------
                    // BOTONES DEL CALENDARIO
                    // ------------------------------------------------

                    headerToolbar: {
                        left:
                            'prev,next today',

                        center:
                            'title',

                        right:
                            'dayGridMonth,timeGridWeek'
                    },


                    buttonText: {
                        today:
                            'Hoy',

                        month:
                            'Mes',

                        week:
                            'Semana'
                    },


                    // ------------------------------------------------
                    // CARGAR RESERVAS
                    // ------------------------------------------------

                    events:
                        function (
                            fetchInfo,
                            successCallback,
                            failureCallback
                        ) {

                            fetch(
                                '../api/reservas.php',
                                {
                                    method:
                                        'GET',

                                    headers: {
                                        'Content-Type':
                                            'application/json'
                                    }
                                }
                            )

                                .then(
                                    response => {

                                        if (
                                            !response.ok
                                        ) {

                                            throw new Error(
                                                'No se pudo cargar el calendario.'
                                            );
                                        }

                                        return response.json();
                                    }
                                )

                                .then(
                                    data => {

                                        successCallback(
                                            data
                                        );
                                    }
                                )

                                .catch(
                                    error => {

                                        console.error(
                                            'Error al cargar eventos:',
                                            error
                                        );

                                        failureCallback(
                                            error
                                        );
                                    }
                                );
                        },


                    // ------------------------------------------------
                    // CONTENIDO DE CADA RESERVA
                    // ------------------------------------------------

                    eventContent:
                        function (info) {

                            const inicio =
                                info.event.start;

                            const fin =
                                info.event.end;


                            // ================================
                            // HORA DE INICIO
                            // ================================

                            const horaInicio =
                                inicio
                                    ? inicio.toLocaleTimeString(
                                        'es-PE',
                                        {
                                            hour:
                                                '2-digit',

                                            minute:
                                                '2-digit',

                                            hour12:
                                                false
                                        }
                                    )
                                    : '';


                            // ================================
                            // HORA DE FIN
                            // ================================

                            const horaFin =
                                fin
                                    ? fin.toLocaleTimeString(
                                        'es-PE',
                                        {
                                            hour:
                                                '2-digit',

                                            minute:
                                                '2-digit',

                                            hour12:
                                                false
                                        }
                                    )
                                    : '';


                            // ================================
                            // DATOS DE LA RESERVA
                            // ================================

                            const estado =
                                info.event
                                    .extendedProps
                                    .estado || '';


                            const practica =
                                info.event
                                    .extendedProps
                                    .practica || '';


                            const laboratorio =
                                info.event
                                    .extendedProps
                                    .laboratorio ||
                                info.event.title;


                            // ================================
                            // CONTENEDOR
                            // ================================

                            const contenedor =
                                document.createElement(
                                    'div'
                                );

                            contenedor.classList.add(
                                'evento-reserva'
                            );


                            // ================================
                            // HORA
                            // ================================

                            const elementoHora =
                                document.createElement(
                                    'div'
                                );

                            elementoHora.classList.add(
                                'evento-hora'
                            );

                            elementoHora.textContent =
                                horaInicio &&
                                horaFin
                                    ? `${horaInicio} - ${horaFin}`
                                    : horaInicio;


                            // ================================
                            // LABORATORIO
                            // ================================

                            const elementoLaboratorio =
                                document.createElement(
                                    'div'
                                );

                            elementoLaboratorio.classList.add(
                                'evento-laboratorio'
                            );

                            elementoLaboratorio.textContent =
                                laboratorio;


                            // ================================
                            // PRACTICA
                            // ================================

                            const elementoPractica =
                                document.createElement(
                                    'div'
                                );

                            elementoPractica.classList.add(
                                'evento-practica'
                            );

                            elementoPractica.textContent =
                                practica;


                            // ================================
                            // ESTADO
                            // ================================

                            const elementoEstado =
                                document.createElement(
                                    'div'
                                );

                            elementoEstado.classList.add(
                                'evento-estado'
                            );

                            elementoEstado.textContent =
                                estado;


                            // ================================
                            // AGREGAR ELEMENTOS
                            // ================================

                            contenedor.appendChild(
                                elementoHora
                            );

                            contenedor.appendChild(
                                elementoLaboratorio
                            );

                            if (practica) {

                                contenedor.appendChild(
                                    elementoPractica
                                );
                            }

                            contenedor.appendChild(
                                elementoEstado
                            );


                            // ================================
                            // TOOLTIP
                            // ================================

                            contenedor.title =
                                practica
                                    ? `${laboratorio} - ${practica}`
                                    : laboratorio;


                            return {
                                domNodes:
                                    [contenedor]
                            };
                        }
                }
            );


        // =======================================================
        // RENDERIZAR CALENDARIO
        // =======================================================

        calendar.render();


        // =======================================================
        // CAMBIAR TITULO DEL CALENDARIO A MAYUSCULAS
        // =======================================================

        const tituloCalendario =
            calendarEl.querySelector(
                '.fc-toolbar-title'
            );


        if (tituloCalendario) {

            tituloCalendario.textContent =
                tituloCalendario.textContent.toUpperCase();
        }


        // Guardar instancia

        window.calendarInstance =
            calendar;


    } else {

        console.warn(
            'FullCalendar no esta disponible o no existe #calendar'
        );
    }


    // ===========================================================
    // 2. FORMULARIO DE NUEVA RESERVA
    // ===========================================================

    formReserva.addEventListener(
        'submit',
        async function (e) {

            e.preventDefault();

            console.log(
                'BOTON GUARDAR PRESIONADO'
            );


            // =======================================================
            // DATOS DEL FORMULARIO
            // =======================================================

            const payload = {

                id_laboratorio:
                    document.getElementById(
                        'id_laboratorio'
                    ).value,

                fecha:
                    document.getElementById(
                        'fecha'
                    ).value,

                hora_inicio:
                    document.getElementById(
                        'hora_inicio'
                    ).value,

                hora_fin:
                    document.getElementById(
                        'hora_fin'
                    ).value,

                motivo:
                    document.getElementById(
                        'motivo'
                    ).value.trim()
            };


            console.log(
                'Datos de reserva:',
                payload
            );


            // =======================================================
            // VALIDACIONES
            // =======================================================

            if (!payload.id_laboratorio) {

                mostrarAlertaModal(
                    'Debe seleccionar un laboratorio.',
                    'danger'
                );

                return;
            }


            if (!payload.fecha) {

                mostrarAlertaModal(
                    'Debe seleccionar una fecha.',
                    'danger'
                );

                return;
            }


            if (
                !payload.hora_inicio ||
                !payload.hora_fin
            ) {

                mostrarAlertaModal(
                    'Debe indicar la hora de inicio y la hora de fin.',
                    'danger'
                );

                return;
            }


            if (
                payload.hora_fin <=
                payload.hora_inicio
            ) {

                mostrarAlertaModal(
                    'La hora de fin debe ser posterior a la hora de inicio.',
                    'danger'
                );

                return;
            }


            if (!payload.motivo) {

                mostrarAlertaModal(
                    'Debe indicar el motivo de la reserva.',
                    'danger'
                );

                return;
            }


            // =======================================================
            // DESHABILITAR BOTON
            // =======================================================

            btnGuardar.disabled = true;

            btnGuardar.textContent =
                'Guardando...';

            ocultarAlertaModal();


            // =======================================================
            // ENVIAR RESERVA
            // =======================================================

            try {

                console.log(
                    'Enviando reserva al servidor...'
                );


                const response =
                    await fetch(
                        '../api/reservas.php',
                        {
                            method:
                                'POST',

                            headers: {
                                'Content-Type':
                                    'application/json'
                            },

                            body:
                                JSON.stringify(
                                    payload
                                )
                        }
                    );


                console.log(
                    'Codigo HTTP:',
                    response.status
                );


                const textoRespuesta =
                    await response.text();


                console.log(
                    'Respuesta PHP:',
                    textoRespuesta
                );


                // ===================================================
                // CONVERTIR RESPUESTA A JSON
                // ===================================================

                let data;


                try {

                    data =
                        JSON.parse(
                            textoRespuesta
                        );

                } catch (error) {

                    throw new Error(
                        'El servidor no devolvio JSON. Respuesta: ' +
                        textoRespuesta
                    );
                }


                console.log(
                    'Respuesta del servidor:',
                    data
                );


                // ===================================================
                // RESERVA GUARDADA
                // ===================================================

                if (data.success) {

                    mostrarAlertaModal(
                        data.message ||
                        'Reserva registrada correctamente.',
                        'success'
                    );


                    // Actualizar calendario

                    if (
                        window.calendarInstance
                    ) {

                        window.calendarInstance
                            .refetchEvents();
                    }


                    // Cerrar modal

                    setTimeout(
                        function () {

                            const modalEl =
                                document.getElementById(
                                    'modalNuevaReserva'
                                );


                            const modalInstance =
                                bootstrap.Modal.getInstance(
                                    modalEl
                                );


                            if (modalInstance) {

                                modalInstance.hide();
                            }


                            formReserva.reset();

                            btnGuardar.disabled =
                                false;

                            btnGuardar.textContent =
                                'Guardar Reserva';

                            ocultarAlertaModal();

                        },
                        1200
                    );


                } else {

                    mostrarAlertaModal(
                        data.message ||
                        'No se pudo registrar la reserva.',
                        'danger'
                    );
                }


            } catch (error) {

                console.error(
                    'Error al registrar la reserva:',
                    error
                );


                mostrarAlertaModal(
                    'Error de conexion con el servidor.',
                    'danger'
                );


            } finally {

                btnGuardar.disabled =
                    false;

                btnGuardar.textContent =
                    'Guardar Reserva';
            }

        }
    );


    // ===========================================================
    // 3. REINICIAR AL CERRAR EL MODAL
    // ===========================================================

    modalNuevaReserva.addEventListener(
        'hidden.bs.modal',
        function () {

            formReserva.reset();

            btnGuardar.disabled =
                false;

            btnGuardar.textContent =
                'Guardar Reserva';

            ocultarAlertaModal();

            console.log(
                'Formulario de reserva reiniciado'
            );
        }
    );


    // ===========================================================
    // MOSTRAR MENSAJE
    // ===========================================================

    function mostrarAlertaModal(
        mensaje,
        tipo
    ) {

        const alertBox =
            document.getElementById(
                'reservaAlert'
            );


        if (!alertBox) {
            return;
        }


        alertBox.textContent =
            mensaje;


        alertBox.className =
            `alert alert-${tipo} py-2`;


        alertBox.classList.remove(
            'd-none'
        );
    }


    // ===========================================================
    // OCULTAR MENSAJE
    // ===========================================================

    function ocultarAlertaModal() {

        const alertBox =
            document.getElementById(
                'reservaAlert'
            );


        if (!alertBox) {
            return;
        }


        alertBox.classList.add(
            'd-none'
        );
    }

});