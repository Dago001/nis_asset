<?php
/**
 * NIS Asset Management System
 * Front Controller - Handles ALL requests
 */
define('BASE_PATH', __DIR__);
define('APP_START', microtime(true));

// Load unified system initializer and autoloader
require_once BASE_PATH . '/config/init.php';

// Run global security middleware (HTTPS, maintenance mode, IP allow-list,
// unauthenticated rate limiting).
Middleware::handle($_SERVER['REQUEST_URI'] ?? '/');

// Work out the path portion of the request, relative to the app root.
$request = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = str_replace('\\', '/', dirname($scriptName));
$base = ($base === '/' || $base === '\\') ? '' : $base;

if ($base !== '' && strpos($request, $base) === 0) {
    $request = substr($request, strlen($base));
}
if (strpos($request, '?') !== false) {
    $request = substr($request, 0, strpos($request, '?'));
}
$request = trim($request, '/');

// Landing page → login or dashboard.
if ($request === '' || $request === 'index.php') {
    header('Location: ' . BASE_URL . '/' . (isLoggedIn() ? 'dashboard' : 'auth/login'));
    exit;
}

// Route everything else.
require_once BASE_PATH . '/core/Router.php';
require_once BASE_PATH . '/config/routes.php';

Router::dispatch($_SERVER['REQUEST_URI']);
exit;
