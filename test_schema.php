<?php
require_once "/var/www/grc/packing/config/db.php";
session_start();
$_SESSION['empresa'] = 'GRCFRUIT SPA';

// Check procesos table columns
$stmt = $pdo->query("SHOW CREATE TABLE procesos");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "=== procesos table schema ==\n";
echo $row['Create Table'] . "\n\n";

// Check if there's a REEMBALAJE process with n_proceso = 1
$stmt2 = $pdo->prepare("SELECT * FROM procesos WHERE n_proceso = :n AND tipo_proceso = 'REEMBALAJE' AND empresa = :emp");
$stmt2->execute(['n' => '1', 'emp' => 'GRCFRUIT SPA']);
$result = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "=== procesos REEMBALAJE with n_proceso=1 ===\n";
if (!empty($result)) {
    echo "Found " . count($result) . " row(s)\n";
    echo "Columns: " . implode(', ', array_keys($result[0])) . "\n";
    echo json_encode($result[0], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No REEMBALAJE process found\n";
    // Try listing all REEMBALAJE processes
    $stmt3 = $pdo->query("SELECT n_proceso, tipo_proceso, estado, lote, empresa FROM procesos WHERE tipo_proceso = 'REEMBALAJE' LIMIT 10");
    $all = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    echo "Total REEMBALAJE processes: " . count($all) . "\n";
    foreach ($all as $r) {
        echo json_encode($r) . "\n";
    }
}
