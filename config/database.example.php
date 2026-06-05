<?php
/**
 * Configuración de conexión a la base de datos
 * Sistema de Juicios Evaluativos SENA
 *
 * INSTRUCCIONES:
 * 1. Copia este archivo y renómbralo a: database.php
 * 2. Rellena los valores con tus credenciales reales.
 * 3. NUNCA subas database.php al repositorio (ya está en .gitignore).
 */

define('DB_HOST',    'localhost');          // Host de la base de datos
define('DB_USER',    'tu_usuario_aqui');    // Usuario de MySQL
define('DB_PASS',    'tu_password_aqui');   // Contraseña de MySQL
define('DB_NAME',    'sena_juicios');       // Nombre de la base de datos
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna una instancia PDO conectada a la base de datos.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'error'   => true,
                'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()
            ]));
        }
    }
    return $pdo;
}

/**
 * Envía respuesta JSON y termina la ejecución.
 */
function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Valida que el método HTTP sea el esperado.
 */
function requireMethod(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        jsonResponse(['error' => true, 'message' => 'Método no permitido'], 405);
    }
}
