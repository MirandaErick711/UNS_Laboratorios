<?php

require_once __DIR__ . '/../config/Database.php';

class Estadistica
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene indicadores generales de los laboratorios.
     */
    public function obtenerIndicadores(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_laboratorios,

                SUM(
                    CASE
                        WHEN estado = 'Operativo' THEN 1
                        ELSE 0
                    END
                ) AS laboratorios_operativos,

                SUM(
                    CASE
                        WHEN estado = 'Mantenimiento' THEN 1
                        ELSE 0
                    END
                ) AS laboratorios_mantenimiento,

                SUM(
                    CASE
                        WHEN estado = 'Inactivo' THEN 1
                        ELSE 0
                    END
                ) AS laboratorios_inactivos

            FROM laboratorios
        ";

        $stmt = $this->conn->query($sql);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_laboratorios' => (int) ($resultado['total_laboratorios'] ?? 0),
            'laboratorios_operativos' => (int) ($resultado['laboratorios_operativos'] ?? 0),
            'laboratorios_mantenimiento' => (int) ($resultado['laboratorios_mantenimiento'] ?? 0),
            'laboratorios_inactivos' => (int) ($resultado['laboratorios_inactivos'] ?? 0)
        ];
    }

    /**
     * Obtiene las reservas pendientes.
     */
    public function obtenerReservasPendientes(): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM reservas
            WHERE estado = 'Pendiente'
        ";

        $stmt = $this->conn->query($sql);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtiene las reservas aprobadas del mes actual.
     */
    public function obtenerReservasAprobadasMes(): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM reservas
            WHERE estado = 'Aprobada'
              AND YEAR(fecha) = YEAR(CURDATE())
              AND MONTH(fecha) = MONTH(CURDATE())
        ";

        $stmt = $this->conn->query($sql);

        return (int) $stmt->fetchColumn();
    }

    public function obtenerReservasRechazadasMes(): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM reservas
            WHERE estado = 'Rechazada'
            AND YEAR(fecha) = YEAR(CURDATE())
            AND MONTH(fecha) = MONTH(CURDATE())
        ";

        $stmt = $this->conn->query($sql);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtiene el número de reservas por laboratorio
     * durante el mes actual.
     */
    public function obtenerUsoPorLaboratorio(): array
    {
        $sql = "
            SELECT
                l.id_laboratorio,
                l.nombre,
                l.estado,
                COUNT(
                    CASE
                        WHEN r.estado = 'Aprobada'
                        THEN r.id_reserva
                    END
                ) AS reservas_mes

            FROM laboratorios l

            LEFT JOIN reservas r
                ON r.id_laboratorio = l.id_laboratorio
                AND YEAR(r.fecha) = YEAR(CURDATE())
                AND MONTH(r.fecha) = MONTH(CURDATE())

            GROUP BY
                l.id_laboratorio,
                l.nombre,
                l.estado

            ORDER BY reservas_mes DESC, l.nombre ASC
        ";

        $stmt = $this->conn->query($sql);

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados as &$laboratorio) {
            $laboratorio['id_laboratorio'] = (int) $laboratorio['id_laboratorio'];
            $laboratorio['reservas_mes'] = (int) $laboratorio['reservas_mes'];
        }

        return $resultados;
    }

    /**
     * Obtiene todos los indicadores necesarios
     * para el dashboard del Responsable.
     */
    public function obtenerResumen(): array
    {
        return [
            'laboratorios' => $this->obtenerIndicadores(),
            'reservas_pendientes' => $this->obtenerReservasPendientes(),
            'reservas_aprobadas_mes' => $this->obtenerReservasAprobadasMes(),
            'reservas_rechazadas_mes' => $this->obtenerReservasRechazadasMes(),
            'uso_por_laboratorio' => $this->obtenerUsoPorLaboratorio()
        ];
    }
}