<?php
/**
 * security_headers.php — Headers de seguridad HTTP
 * Incluir al inicio de TODOS los entry points (antes de cualquier output)
 */

// Prevenir clickjacking
header('X-Frame-Options: DENY');

// Prevenir MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Referrer Policy
header('Referrer-Policy: strict-origin-when-cross-origin');

// Permissions Policy (feature policy)
header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=()');

// HSTS (solo en producción con HTTPS)
// if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
//     header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
// }

// CSP básica - ajustar según necesidades
// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");

// Cache control para APIs
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}