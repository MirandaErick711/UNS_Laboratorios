<?php

/**
 * Servicio de integración con el Directorio Institucional.
 *
 * Actualmente funciona como una capa preparada para una futura
 * integración con el directorio institucional de la UNS.
 *
 * La implementación real dependerá del mecanismo que proporcione
 * la universidad, por ejemplo LDAP, Active Directory, OAuth u otro.
 */
class DirectorioInstitucional
{
    /**
     * Indica si existe una integración real disponible.
     *
     * En el prototipo se mantiene desactivada porque no se dispone
     * de acceso técnico al directorio institucional.
     */
    public function estaDisponible(): bool
    {
        return false;
    }

    /**
     * Valida las credenciales contra el directorio institucional.
     *
     * Esta función queda preparada para implementar la conexión
     * real cuando la UNS proporcione el servicio correspondiente.
     */
    public function autenticar(string $correo, string $password): array|false
    {
        if (!$this->estaDisponible()) {
            return false;
        }

        // Aquí se implementaría la conexión real al directorio.
        return false;
    }
}