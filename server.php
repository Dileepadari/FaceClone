<?php
/**
 * Router for PHP's built-in development server:
 *
 *   php -S localhost:8000 -t public server.php
 *
 * Without it the built-in server treats any URL whose last segment contains a
 * dot (for example /u/dileep.adari) as a static file and returns its own 404.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$file = __DIR__ . '/public' . $path;

// Serve real files (CSS, JS, uploads) straight from disk.
if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/public/index.php';
