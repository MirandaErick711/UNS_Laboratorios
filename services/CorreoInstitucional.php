<?php

/**
 * Servicio de integración con el correo institucional.
 *
 * Actualmente funciona como una capa preparada para una futura
 * integración con el servicio de correo de la UNS.
 */
class CorreoInstitucional
{
    /**
     * Indica si existe una configuración real de correo.
     *
     * En el prototipo permanece desactivada porque no se dispone
     * de las credenciales SMTP institucionales.
     */
    public function estaDisponible(): bool
    {
        return false;
    }

    /**
     * Envía un correo electrónico.
     *
     * La implementación real dependerá del servidor SMTP
     * o servicio de correo que proporcione la universidad.
     */
    public function enviar(
        string $destinatario,
        string $asunto,
        string $mensaje
    ): bool {
        if (!$this->estaDisponible()) {
            return false;
        }

        // Aquí se implementaría el envío mediante SMTP
        // o el servicio institucional correspondiente.

        return false;
    }
}