<?php
// config.php — paths y configuracion global
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Santiago');
define('RUTA', '/var/www/grc/');
