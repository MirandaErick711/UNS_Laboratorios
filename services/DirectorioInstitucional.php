<?php
// Servicio preparado para el directorio institucional
class DirectorioInstitucional
{
    // Indica si el directorio esta disponible
    public function estaDisponible(): bool
    {
        return false;
    }

    // Autentica contra el directorio
    public function autenticar(string $correo, string $password): array|false
    {
        if (!$this->estaDisponible()) {
            return false;
        }

        // Aqui se implementara la conexion real
        return false;
    }
}