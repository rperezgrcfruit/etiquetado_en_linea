<?php
/**
 * csrf_helper.php
 * Generacion y verificacion de tokens CSRF para proteger forms contra
 * ataques Cross-Site Request Forgery.
 *
 * Uso en view (agregar a cada <form>):
 *   <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
 *
 * Uso en handler POST (al inicio):
 *   require_once 'config/csrf_helper.php';
 *   if (!verifyCsrf()) {
 *       http_response_code(419);
 *       echo json_encode(['error' => 'Token CSRF invalido']);
 *       exit;
 *   }
 *   // ... o para forms HTML:
 *   // if (!verifyCsrf()) { die('Solicitud invalida.'); }
 */

/**
 * Obtiene (o genera) el token CSRF para la sesion actual.
 * El token se almacena en $_SESSION y se reutiliza hasta que cambie la sesion.
 *
 * @return string
 */
function getCsrfToken()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        // 32 bytes = 64 hex chars; cryptographically secure
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verifica que el token enviado por POST coincida con el de la sesion.
 *
 * @param string|null $token  Token a verificar (null = leer de $_POST['csrf_token'])
 * @return bool
 */
function verifyCsrf($token = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    $token = $token ?? ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if (empty($token)) {
        return false;
    }

    // use hash_equals para evitar timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Genera un token CSRF y lo imprime como input hidden para usar en forms.
 * Convenience method para views.
 *
 * @return string HTML <input> tag
 */
function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken()) . '">';
}

/**
 * Middleware: aborta si el token CSRF no verifica.
 * Ideal para llamar al inicio de handlers POST.
 *
 * @param int $httpCode Codigo HTTP a devolver en fallo
 * @return void
 */
function requireCsrf($httpCode = 419)
{
    if (!verifyCsrf()) {
        http_response_code($httpCode);
        // Detectar si la peticion es AJAX (JSON esperado)
        if (!empty($_SERVER['HTTP_ACCEPT']) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid or missing CSRF token']);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h3>Error: Solicitud inválida (CSRF).</h3>';
        }
        exit;
    }
}
