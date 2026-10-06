<?php
// etiquetado/config/config.php — Configuracion del modulo etiquetado.
// Reutiliza la conexion PDO y constantes centralizadas de config/db.php.
// NOTA: config_local.php mantiene una conexion separada a la DB local (192.168.1.50)
//       para operaciones de impresion ZPL que requieren acceso LAN directo.

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Environment
$env = $_ENV['APP_ENV'] ?? 'production';

// Mostrar errores solo en desarrollo
ini_set('display_errors', $env === 'local' ? 1 : 0);
ini_set('display_startup_errors', $env === 'local' ? 1 : 0);
error_reporting(E_ALL);

// Application Settings (etiquetado-specific)
define('APP_NAME', 'Etiquetado');
define('APP_URL', $_SERVER['DOCUMENT_ROOT']);
define('APP_DEBUG', $env === 'local');

// Timezone (already set by config/db.php, but ensure consistency)
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Santiago');
?>
