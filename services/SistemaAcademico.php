<?php

/**
 * Servicio de integración con el Sistema Académico.
 *
 * Actualmente funciona como una capa preparada para una futura
 * integración con el sistema académico institucional de la UNS.
 */
class SistemaAcademico
{
    /**
     * Indica si existe una integración real disponible.
     *
     * En el prototipo permanece desactivada porque no se dispone
     * de acceso técnico al sistema académico institucional.
     */
    public function estaDisponible(): bool
    {
        return false;
    }

    /**
     * Obtiene los cursos asociados a un docente.
     *
     * La implementación real dependerá del servicio que
     * proporcione la universidad.
     */
    public function obtenerCursosDocente(int $idDocente): array
    {
        if (!$this->estaDisponible()) {
            return [];
        }

        // Aquí se implementaría la consulta al sistema académico.
        return [];
    }

    /**
     * Obtiene información académica de un curso.
     */
    public function obtenerCurso(int $idCurso): array|false
    {
        if (!$this->estaDisponible()) {
            return false;
        }

        // Aquí se implementaría la consulta al sistema académico.
        return false;
    }
}