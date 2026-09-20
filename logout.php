<?php
/**
 * logout.php
 * Destruye la sesión activa del usuario y redirige al login.
 */

session_start();

// Vaciamos todas las variables de sesión
$_SESSION = [];

// Si la sesión usa cookies, también la eliminamos del navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruimos la sesión en el servidor
session_destroy();

// Redirigimos al login
header('Location: index.html');
exit;