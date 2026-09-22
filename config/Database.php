<?php
class Database{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct(){
        $config = $this->cargarConfiguracion();

        $host = $config['DB_HOST'] ?? '127.0.0.1';
        $dbName = $config['DB_NAME'] ?? 'uns_laboratorios';
        $user = $config['DB_USER'] ?? 'root';
        $password = $config['DB_PASSWORD'] ?? '';
        $charset = $config['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        try {
            $this->connection = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión BD: " . $e->getMessage());

            die(json_encode([
                "success" => false,
                "message" => "Error al conectar con la base de datos."
            ]));
        }
    }

    // Carga la configuracion del archivo .env
    private function cargarConfiguracion(): array{
        $rutaEnv = dirname(__DIR__) . '/.env';

        if (!file_exists($rutaEnv)) {
            die(json_encode([
                "success" => false,
                "message" => "No se encontró el archivo .env."
            ]));
        }

        $config = [];
        $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            $posicion = strpos($linea, '=');

            if ($posicion === false) {
                continue;
            }

            $clave = trim(substr($linea, 0, $posicion));
            $valor = trim(substr($linea, $posicion + 1));
            $valor = trim($valor, "\"'");

            $config[$clave] = $valor;
        }

        return $config;
    }

    private function __clone() {}

    public static function getInstance(): Database{
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    public function getConnection(): PDO{
        return $this->connection;
    }
}