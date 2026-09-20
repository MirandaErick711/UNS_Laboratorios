document.getElementById('formLogin').addEventListener('submit', async function (e) {
    e.preventDefault();

    const correo = document.getElementById('correo').value.trim();
    const password = document.getElementById('password').value;

    const alertBox = document.getElementById('loginAlert');
    const btnLogin = document.getElementById('btnLogin');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    // UI: estado de carga
    alertBox.style.display = 'none';
    btnLogin.disabled = true;
    btnText.textContent = 'Verificando...';
    btnSpinner.classList.remove('d-none');

    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ correo, password })
        });

        const data = await response.json();

        if (data.success) {
            // Redirección dinámica según el rol del usuario
            switch (data.rol) {
                case 'Docente':
                    window.location.href = 'views/dashboard_docente.php';
                    break;
                case 'Responsable':
                    window.location.href = 'views/dashboard_responsable.php';
                    break;
                case 'Tecnico':
                    window.location.href = 'views/dashboard_tecnico.php';
                    break;
                default:
                    console.error('Rol no reconocido:', data.rol);
                    mostrarError('Rol de usuario no válido.');
            }
        } else {
            mostrarError(data.message || 'No se pudo iniciar sesión.');
        }

    } catch (error) {
        console.error('Error en la solicitud:', error);
        mostrarError('Error de conexión con el servidor.');
    } finally {
        btnLogin.disabled = false;
        btnText.textContent = 'Ingresar';
        btnSpinner.classList.add('d-none');
    }
});

function mostrarError(mensaje) {
    const alertBox = document.getElementById('loginAlert');
    alertBox.textContent = mensaje;
    alertBox.style.display = 'block';
}