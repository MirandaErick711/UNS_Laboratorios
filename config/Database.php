<?php
/**
 * Clase Database
 * Maneja la conexión única (Singleton) a la base de datos MySQL
 * mediante PDO, con manejo de excepciones y modo de errores estricto.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    // Credenciales de conexión (ajusta según tu entorno local)
    private string $host = "127.0.0.1";
    private string $dbName = "uns_laboratorios";
    private string $user = "root";
    private string $password = "";
    private string $charset = "utf8mb4";

    // Constructor privado: evita instanciar la clase desde fuera
    private function __construct()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Resultados como arrays asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                 // Usa sentencias preparadas reales (más seguro)
        ];

        try {
            $this->connection = new PDO($dsn, $this->user, $this->password, $options);
        } catch (PDOException $e) {
            // No exponemos detalles sensibles al cliente en producción
            error_log("Error de conexión BD: " . $e->getMessage());
            die(json_encode([
                "success" => false,
                "message" => "Error al conectar con la base de datos."
            ]));
        }
    }

    // Evita clonar la instancia (parte del patrón Singleton)
    private function __clone() {}

    /**
     * Punto de acceso global a la única instancia de conexión.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Retorna el objeto PDO listo para ejecutar consultas.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}