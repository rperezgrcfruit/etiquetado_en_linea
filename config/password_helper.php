<?php
/**
 * password_helper.php
 * Verificacion y migracion segura de contraseñas.
 *
 * Soporta dos formatos en la misma columna 'password':
 *   - MD5 puro (32 hex chars): legacy, usado por otras apps del sistema
 *   - bcrypt/argon2 (password_hash): formato seguro
 *
 * La migracion es INCREMENTAL y OPT-IN:
 *   - Por defecto, NO actualiza la BD. Login funciona con MD5 y bcrypt.
 *   - Para activar el upgrade automatico al loguearse exitosamente, pasar
 *     $autoUpgrade = true en verifyLogin(). Esto solo afecta a usuarios que
 *     inician sesion en packing — las otras apps siguen con MD5 hasta que
 *     ellas migren. Coordinar con esas apps antes de activarlo.
 */

/**
 * Verifica una contrasenna contra el hash almacenado.
 * Soporta MD5 puro y bcrypt/argon2 (password_hash).
 *
 * @param PDO    $pdo
 * @param string $username
 * @param string $password    Contrasenna en texto plano
 * @param bool   $autoUpgrade Si true, re-hace el hash a password_hash() tras login exitoso
 * @return array|false
 */
function verifyLogin($pdo, $username, $password, $autoUpgrade = false)
{
    // 1. Buscar usuario por username SOLO (no comparar password en SQL)
    $sql = "SELECT * FROM usuarios WHERE user = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return false;
    }

    $storedHash = $user['password'];

    if (empty($storedHash)) {
        return false;
    }

    $authenticated = false;
    $needsUpgrade = false;

    // 2. Caso A: bcrypt/argon2 (password_hash directo)
    if (strpos($storedHash, '$2y$') === 0 || strpos($storedHash, '$2a$') === 0 || strpos($storedHash, '$argon2') === 0) {
        if (password_verify($password, $storedHash)) {
            $authenticated = true;
            if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                $needsUpgrade = true;
            }
        }
    }

    // 3. Caso B: MD5 puro (legacy — usado por otras apps)
    if (!$authenticated && strlen($storedHash) === 32 && ctype_xdigit($storedHash)) {
        if (strtolower($storedHash) === strtolower(md5($password))) {
            $authenticated = true;
            $needsUpgrade = true;  // Flag; only applied if $autoUpgrade=true
        }
    }

    // 4. Upgrade opcional (solo cuando se activa y coordina con otras apps)
    if ($authenticated && $needsUpgrade && $autoUpgrade) {
        upgradePassword($pdo, $username, $password);
    }

    return $authenticated ? $user : false;
}

/**
 * Upgrades a user's password to password_hash() format.
 * Call this ONLY when ready to migrate (after coordinating with all apps).
 *
 * @param PDO    $pdo
 * @param string $username
 * @param string $plainPassword
 * @return bool
 */
function upgradePassword($pdo, $username, $plainPassword)
{
    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE usuarios SET password = ? WHERE user = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$newHash, $username]);
}
