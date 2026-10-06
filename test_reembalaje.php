<?php
// Use absolute paths instead of DOCUMENT_ROOT
require_once "/var/www/grc/packing/config/db.php";
require_once "/var/www/grc/packing/api/get_proceso_reembalaje_detalle.php";

session_start();
$_SESSION['empresa'] = 'GRCFRUIT SPA';

echo "TEMPORADA_ACTUAL: " . TEMPORADA_ACTUAL . "\n";
echo "DB Host: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "DB Name: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
echo "Empresa: " . $_SESSION['empresa'] . "\n\n";

echo "=== Testing obtenerDetalleProcesoReembalaje ===\n";
$result1 = obtenerDetalleProcesoReembalaje('1');
echo "Count: " . count($result1) . "\n";
if (!empty($result1)) {
    echo "Keys: " . implode(', ', array_keys($result1[0])) . "\n";
    echo "Especie: " . ($result1[0]['especie'] ?? 'UNDEFINED') . "\n";
    echo "Productor: " . ($result1[0]['productor'] ?? 'UNDEFINED') . "\n";
    echo "Productor_nombre: " . ($result1[0]['productor_nombre'] ?? 'UNDEFINED') . "\n";
} else {
    echo "No results from procesos query\n";
}

echo "\n=== Testing obtenerDetalleProcesoReembalajeEntrada ===\n";
$result2 = obtenerDetalleProcesoReembalajeEntrada('1');
echo "Count: " . count($result2) . "\n";

echo "\n=== Testing obtenerDetalleProcesoReembalajeExpo ===\n";
$result3 = obtenerDetalleProcesoReembalajeExpo('1');
echo "Count: " . count($result3) . "\n";

echo "\nDone.\n";
