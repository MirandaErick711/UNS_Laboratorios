<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../services/DirectorioInstitucional.php';

class AuthController {
    private Usuario $usuarioModel;
    private DirectorioInstitucional $directorio;

    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->directorio = new DirectorioInstitucional();
    }

    // Valida las credenciales del usuario
    public function login(string $correo, string $passwordPlano): array {
        if (empty($correo) || empty($passwordPlano)) {
            return [
                "success" => false,
                "message" => "Correo y contraseña son obligatorios."
            ];
        }

        // Intenta usar el directorio institucional
        if ($this->directorio->estaDisponible()) {
            $usuarioDirectorio = $this->directorio->autenticar(
                $correo,
                $passwordPlano
            );

            if (!$usuarioDirectorio) {
                return [
                    "success" => false,
                    "message" => "Credenciales inválidas."
                ];
            }

            return $this->crearSesion($usuarioDirectorio);
        }

        // Usa la autenticacion local del prototipo
        $usuario = $this->usuarioModel->buscarPorCorreo($correo);

        if (!$usuario) {
            return [
                "success" => false,
                "message" => "Credenciales inválidas."
            ];
        }

        if ((int) $usuario['estado'] !== 1) {
            return [
                "success" => false,
                "message" => "Tu cuenta se encuentra inactiva. Contacta al administrador."
            ];
        }

        if (!password_verify($passwordPlano, $usuario['password_hash'])) {
            return [
                "success" => false,
                "message" => "Credenciales inválidas."
            ];
        }

        return $this->crearSesion($usuario);
    }

    // Crea la sesion despues de autenticar
    private function crearSesion(array $usuario): array {
        session_regenerate_id(true);

        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombres'] = $usuario['nombres'];
        $_SESSION['apellidos'] = $usuario['apellidos'];
        $_SESSION['rol'] = $usuario['rol'];

        return [
            "success" => true,
            "message" => "Bienvenido, {$usuario['nombres']}",
            "rol" => $usuario['rol']
        ];
    }
}