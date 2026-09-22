<?php

// Servicio preparado para el correo institucional
class CorreoInstitucional{
    // Indica si el correo esta disponible
    public function estaDisponible(): bool{
        return false;
    }

    // Envia un correo
    public function enviar(
        string $destinatario,
        string $asunto,
        string $mensaje
    ): bool {
        if (!$this->estaDisponible()) {
            return false;
        }

        // Aqui se implementara el envio real
        return false;
    }
}