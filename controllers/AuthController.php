<?php
require_once __DIR__ . '/../models/Usuario.php';

/**
 * Controlador de Autenticación
 * Contiene la lógica de negocio del login, separada del endpoint API.
 */
class AuthController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    /**
     * Valida credenciales y retorna un arreglo con el resultado.
     */
    public function login(string $correo, string $passwordPlano): array
    {
        if (empty($correo) || empty($passwordPlano)) {
            return ["success" => false, "message" => "Correo y contraseña son obligatorios."];
        }

        $usuario = $this->usuarioModel->buscarPorCorreo($correo);

        if (!$usuario) {
            return ["success" => false, "message" => "Credenciales inválidas."];
        }

        if ((int)$usuario['estado'] !== 1) {
            return ["success" => false, "message" => "Tu cuenta se encuentra inactiva. Contacta al administrador."];
        }

        // Verificación segura del hash (bcrypt / argon2 según cómo se generó)
        if (!password_verify($passwordPlano, $usuario['password_hash'])) {
            return ["success" => false, "message" => "Credenciales inválidas."];
        }

        // Login correcto: iniciamos sesión
        session_regenerate_id(true); // Previene fijación de sesión
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombres']    = $usuario['nombres'];
        $_SESSION['apellidos']  = $usuario['apellidos'];
        $_SESSION['rol']        = $usuario['rol'];

        return [
            "success" => true,
            "message" => "Bienvenido, {$usuario['nombres']}",
            "rol"     => $usuario['rol']
        ];
    }
}