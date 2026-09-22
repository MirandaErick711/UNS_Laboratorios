<?php
// Servicio preparado para el sistema academico
class SistemaAcademico{
    // Indica si el sistema esta disponible
    public function estaDisponible(): bool{
        return false;
    }

    // Obtiene los cursos de un docente
    public function obtenerCursosDocente(int $idDocente): array{
        if (!$this->estaDisponible()) {
            return [];
        }

        // Aqui se implementara la consulta real
        return [];
    }

    // Obtiene informacion de un curso
    public function obtenerCurso(int $idCurso): array|false{
        if (!$this->estaDisponible()) {
            return false;
        }

        // Aqui se implementara la consulta real
        return false;
    }
}