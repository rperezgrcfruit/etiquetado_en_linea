<?php
/**
 * Script: migrate_passwords.php
 * Purpose: Migar masivamente MD5 a password_hash() en la tabla usuarios.
 *
 * IMPORTANTE — leer antes de ejecutar:
 *
 * El DB almacena md5(contrasenna_plana) como valor de password.
 * Este script envuelve cada hash MD5 existente con password_hash(), por lo que:
 *   stored_password = password_hash(md5_hash, PASSWORD_DEFAULT)
 *
 * ESTO ROMPERA el login de otras apps que usan:
 *   md5(input) + SELECT WHERE password = md5(input)
 * ya que password_hash(md5(input)) != md5(input).
 *
 * REQUISITOS ANTES DE EJECUTAR:
 * 1. Identificar todas las apps que usan la tabla usuarios con MD5.
 * 2. Actualizar esas apps para usar: md5(input) -> password_verify()
 *    (ver config/password_helper.php para el helper reutilizable).
 * 3. Testear el login en TODAS las apps con un usuario de prueba.
 * 4. Ejecutar este script en horario de bajo uso.
 * 5. Tener el backup listo: backups-packing-* (directorio con grcfruit_grc.sql.gz)
 *
 * Despues de la migracion, el login en cada app debe ser:
 *   $md5 = md5($password);
 *   password_verify($md5, $stored_hash);
 *
 * Usage: php migrate_passwords.php
 * WARNING: Run from CLI only. Do NOT expose this file on the web.
 */
require_once __DIR__ . '/config/db.php';

echo "=== Migracion masiva: MD5 -> password_hash(md5()) ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Safety check: confirm user knows about the impact
echo "ADVERTENCIA: Esto afectara a otras apps que usan MD5 + SELECT WHERE password.\n";
echo "Presione Ctrl+C en los proximos 5 segundos si no ha coordinado con todas las apps.\n";
echo "Continuando en...\n";
for ($i = 3; $i >= 1; $i--) {
    echo "$i...\n";
    sleep(1);
}

// Fetch all users
$sql = "SELECT user, password FROM usuarios";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "No se encontraron usuarios.\n";
    exit;
}

$upgraded = 0;
$skipped = 0;
$failed = 0;

foreach ($users as $user) {
    $username = $user['user'];
    $currentPass = $user['password'];

    // Skip if already a bcrypt/argon2 hash (already migrated)
    if ($currentPass && strlen($currentPass) > 32 && (
        strpos($currentPass, '$2y$') === 0 || strpos($currentPass, '$2a$') === 0 ||
        strpos($currentPass, '$argon2') === 0
    )) {
        echo "  [SKIP] {$username}: ya es hash fuerte\n";
        $skipped++;
        continue;
    }

    if (empty($currentPass)) {
        echo "  [SKIP] {$username}: password vacia\n";
        $skipped++;
        continue;
    }

    // The current value is an MD5 hash. Wrap it with password_hash().
    $newHash = password_hash($currentPass, PASSWORD_DEFAULT);

    $updateSql = "UPDATE usuarios SET password = ? WHERE user = ?";
    $updateStmt = $pdo->prepare($updateSql);

    try {
        $updateStmt->execute([$newHash, $username]);
        echo "  [OK]   {$username}: migrada (" . substr($newHash, 0, 20) . "...)\n";
        $upgraded++;
    } catch (Exception $e) {
        echo "  [FAIL] {$username}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n=== Resumen ===\n";
echo "Total usuarios:    " . count($users) . "\n";
echo "Migrados:          {$upgraded}\n";
echo "Saltados:          {$skipped}\n";
echo "Fallidos:          {$failed}\n";
echo "\nListo. Verifique el login en todas las apps afectadas.\n";
