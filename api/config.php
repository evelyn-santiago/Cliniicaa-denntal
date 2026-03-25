<?php
// ============================================================
//  Configuración de conexión a MySQL
//  Ajusta estos valores según tu instalación de XAMPP o Azure MySQL
// ============================================================

define('DB_HOST',     getenv('DB_HOST')     ?: 'clinic-db-final-2026.mysql.database.azure.com');
define('DB_PORT',     getenv('DB_PORT')     ?: '3306');
define('DB_NAME',     getenv('DB_NAME')     ?: 'clinicadental');
define('DB_USER',     getenv('DB_USER')     ?: 'root@clinic-db-final-2026');       // Ajusta a tu usuario de Azure MySQL
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'nayeli21');   // Ajusta a la contraseña de Azure MySQL
define('DB_CHARSET',  'utf8mb4');

// ============================================================
//  Función que retorna la conexión PDO
// ============================================================
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
            exit;
        }
    }

    return $pdo;
}

// ============================================================
//  Headers CORS y JSON para todas las respuestas de la API
// ============================================================
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
