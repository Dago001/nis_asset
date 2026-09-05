<?php
/**
 * System Initialization and Bootstrapper
 */

// Define base path if not defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', BASE_PATH);
}

// Composer dependencies (PHPMailer, TCPDF, PhpSpreadsheet, etc.) — optional:
// code that uses them (Mailer, PDF export) already checks class_exists()
// first and degrades gracefully when vendor/ hasn't been installed.
if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// Register Autoloader for Core, Controllers, and Models
spl_autoload_register(function ($class) {
    // Core classes
    $coreFile = BASE_PATH . '/core/' . $class . '.php';
    if (file_exists($coreFile)) {
        require_once $coreFile;
        return true;
    }
    
    // Controllers
    $controllerFile = BASE_PATH . '/controllers/' . $class . '.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        return true;
    }
    
    // Models
    $modelFile = BASE_PATH . '/models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
        return true;
    }
    
    return false;
});

// Load environment variables from .env (tolerant parser: supports quotes,
// `export`, `#` comments and blank lines; does NOT evaluate anything).
if (is_file(BASE_PATH . '/.env') && is_readable(BASE_PATH . '/.env')) {
    foreach (file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        if (stripos($line, 'export ') === 0) {
            $line = substr($line, 7);
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '') {
            continue;
        }
        // Strip surrounding quotes; for unquoted values drop trailing "# comment".
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        } elseif (($hash = strpos($value, ' #')) !== false) {
            $value = rtrim(substr($value, 0, $hash));
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Define constants
require_once __DIR__ . '/constants.php';

// Set an initial timezone *before* config.php runs — config.php starts the
// session (Session::init()), which stamps timestamps (last-activity, active
// session tracking) using date(), and PHP's built-in default (from php.ini's
// date.timezone, typically UTC) can differ from ours by a real, non-trivial
// offset. Getting this right only after config.php has already run left
// early timestamps off by exactly that offset. This can't yet consult the
// DB-configurable "General → Timezone" setting (the DB isn't wired up until
// config.php runs), so it's env/default-only for now; the line below at the
// end of this file re-applies Config's fully DB-aware value once possible.
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Lagos');

// Include configuration settings and helpers (this will trigger Session::init())
require_once __DIR__ . '/config.php';

// Set error reporting based on environment configuration
if (Config::get('app_env') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/php_errors.log');

// Re-apply the timezone now that the DB is reachable, in case "General →
// Timezone" overrides the env/default value used for the early pass above.
date_default_timezone_set(Config::get('timezone', 'Africa/Lagos'));

// Set base URL safely
if (!defined('BASE_URL')) {
    define('BASE_URL', Config::get('app_url', 'http://localhost/nis_ams'));
}