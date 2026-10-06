<?php
// db.php — credenciales desde .env via vlucas/phpdotenv
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/csrf_helper.php';
require_once __DIR__ . '/password_helper.php';
require_once __DIR__ . '/security_headers.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Santiago');

// Temporada dinámica: usa .env o año actual por defecto
if (!defined('TEMPORADA_ACTUAL')) {
    define('TEMPORADA_ACTUAL', $_ENV['TEMPORADA_ACTUAL'] ?? date('Y'));
}

// Domain constants — extraídos de .env para evitar números mágicos en views
if (!defined('BIN_TARA')) {
    define('BIN_TARA', (int)($_ENV['BIN_TARA'] ?? 43));
}
if (!defined('PLANTA_CENTRAL_CSP')) {
    define('PLANTA_CENTRAL_CSP', $_ENV['PLANTA_CENTRAL_CSP'] ?? '121293');
}

try {
  $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_NAME'] ?? 'grcfruit_grc',
    $_ENV['DB_CHARSET'] ?? 'utf8mb4'
  );
  $user = $_ENV['DB_USER'] ?? 'root';
  $pass = $_ENV['DB_PASS'] ?? '';
  $opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];
  $pdo = new PDO($dsn, $user, $pass, $opt);
} catch (\Throwable $th) {
  error_log("DB connection error: " . $th->getMessage());
  header('Location: /packing/view/layouts/error.php?mensaje=' . urlencode('Error de conexión a la base de datos. Contacte al administrador.'));
  exit;
}
