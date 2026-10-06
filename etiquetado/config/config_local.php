<?php
// etiquetado/config/config_local.php — conexion local/LAN para impresion ZPL
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

$env = $_ENV['APP_ENV'] ?? 'production';

ini_set('display_errors', $env === 'local' ? 1 : 0);
ini_set('display_startup_errors', $env === 'local' ? 1 : 0);
error_reporting(E_ALL);

// Local Database Configuration (internal LAN)
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
  $_ENV['DB_LOCAL_HOST'] ?? '192.168.1.50',
  $_ENV['DB_NAME'] ?? 'grcfruit_grc',
  $_ENV['DB_CHARSET'] ?? 'utf8mb4'
);
$user = $_ENV['DB_LOCAL_USER'] ?? 'grc_app';
$pass = $_ENV['DB_LOCAL_PASS'] ?? '';
$opt = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);

// Application Settings
define('APP_NAME', 'Etiquetado');
define('APP_URL', $_SERVER['DOCUMENT_ROOT']);
define('APP_DEBUG', $env === 'local');

// Temporada dinamica
if (!defined('TEMPORADA_ACTUAL')) {
    define('TEMPORADA_ACTUAL', $_ENV['TEMPORADA_ACTUAL'] ?? date('Y'));
}

// Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Santiago');
?>
