<?php
/**
 * Configuration File - COMPLETE WORKING VERSION
 */

// =============================================
// SESSION CONFIGURATION - MUST BE FIRST
// =============================================

// Determine transport up front so the cookie flags can react to it.
$__isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on')
    || (getenv('APP_URL') && strpos(getenv('APP_URL'), 'https://') === 0);


$__envSecure = getenv('SESSION_SECURE');
$__cookieSecure = ($__envSecure === false || $__envSecure === '')
    ? ($__isHttps ? 1 : 0)          // auto: secure whenever the request is HTTPS
    : (($__envSecure === 'true' || $__envSecure === '1') ? 1 : 0);

// Set session parameters BEFORE session starts
ini_set('session.name', getenv('SESSION_NAME') ?: 'NIS_AMS_SESSION');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', $__cookieSecure);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', (int) (getenv('SESSION_LIFETIME') ?: 1800));

// =============================================
// DATABASE CONFIGURATION  (environment wins; these are local-dev fallbacks)
// =============================================

if (!defined('DB_HOST'))    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_NAME'))     define('DB_NAME', getenv('DB_NAME') ?: 'nis_ams');
if (!defined('DB_USER'))    define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS'))    define('DB_PASS', (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));
if (!defined('DB_CHARSET')) define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// =============================================
// APPLICATION CONFIGURATION
// =============================================

$protocol = $__isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = str_replace('\\', '/', dirname($scriptName));
$basePath = ($basePath === '/' || $basePath === '\\') ? '' : $basePath;

$envAppUrl = getenv('APP_URL');
if ($envAppUrl && filter_var($envAppUrl, FILTER_VALIDATE_URL)) {
    // If request comes from a live domain (not localhost/127.0.0.1) but .env has localhost,
    // auto-correct to the current live host so users aren't redirected to localhost.
    $isLiveHost = !empty($_SERVER['HTTP_HOST'])
        && strpos($_SERVER['HTTP_HOST'], 'localhost') === false
        && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false;
    if ($isLiveHost && strpos($envAppUrl, 'localhost') !== false) {
        $dynamicUrl = $protocol . $host . $basePath;
    } else {
        $dynamicUrl = rtrim($envAppUrl, '/');
    }
} else {
    $dynamicUrl = $protocol . $host . $basePath;
}

if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__)); }
if (!defined('ROOT_PATH')) { define('ROOT_PATH', BASE_PATH); }

define('APP_NAME', getenv('APP_NAME') ?: 'NIS Asset Management System');
define('APP_URL', $dynamicUrl);
if (!defined('BASE_URL')) { define('BASE_URL', APP_URL); } // Guarded: init.php may define this too
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// =============================================
// SECURITY CONFIGURATION
// =============================================

define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 1800);

// Key used by SettingsModel to encrypt/decrypt sensitive system_settings
// values at rest (e.g. smtp_password). Set ENCRYPTION_KEY in .env for real
// deployments — generate with: php -r "echo bin2hex(random_bytes(32));"
// The fallback below only keeps local dev usable when .env hasn't set one;
// it must never be relied on anywhere real secrets are stored.
if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'your-secret-key-here');
}

// =============================================
// FUNCTION DEFINITIONS
// =============================================

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!is_string($token) || $token === '' || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    return hash_equals((string) $_SESSION['_csrf_token'], $token);
}

/**
 * Get database connection
 */
function getDBConnection() {
    return Database::getInstance();
}

/**
 * Initialize session
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', 600);
        session_start();
    }
    
    if (isset($_SESSION['user_id'])) {
        $maxInactivity = 600; // 10 minutes
        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > $maxInactivity)) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            header('Location: ' . BASE_URL . '/auth/login?timeout=1');
            exit();
        }
        $_SESSION['_last_activity'] = time();
    }
    
    if (!isset($_SESSION['_last_regenerate']) || time() - $_SESSION['_last_regenerate'] > 300) {
        session_regenerate_id(true);
        $_SESSION['_last_regenerate'] = time();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/index.php?error=' . urlencode('Please login to access this page'));
        exit;
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'rank' => $_SESSION['rank'],
        'nis_number' => $_SESSION['nis_number'],
        'roles' => $_SESSION['roles'] ?? [],
        'permissions' => $_SESSION['permissions'] ?? []
    ];
}

/**
 * Check if user has a specific role
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    $roles = $_SESSION['roles'] ?? [];
    return in_array($role, $roles);
}

/**
 * Check if user has any of the given roles
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) return false;
    $userRoles = $_SESSION['roles'] ?? [];
    foreach ($roles as $role) {
        if (in_array($role, $userRoles)) return true;
    }
    return false;
}

function hasPermission($permission) {
    if (!isLoggedIn()) return false;
    if (class_exists('Auth')) {
        return Auth::can($permission);
    }
    if (hasRole('Super Admin Officer') || (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true)) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    return in_array($permission, $permissions);
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'error') {
    if ($message) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . $type . '=' . urlencode($message);
    }
    header('Location: ' . APP_URL . '/' . ltrim($url, '/'));
    exit;
}

/**
 * The full Nigeria Immigration Service rank hierarchy, highest to lowest.
 * Single source of truth for every "Rank" dropdown in the app — several
 * forms used to accept rank as free text (or duplicated their own copy of
 * this same list locally), which meant no two records were guaranteed to
 * spell a rank the same way. Values are "Title (CODE)" strings, matching
 * what the requisition module already stored before this existed.
 */
function getNisRanks() {
    return [
        'Comptroller General (CGIS)',
        'Deputy Comptroller General (DCG)',
        'Assistant Comptroller General (ACG)',
        'Comptroller of Immigration (CI)',
        'Deputy Comptroller of Immigration (DCI)',
        'Assistant Comptroller of Immigration (ACI)',
        'Chief Superintendent of Immigration (CSI)',
        'Superintendent of Immigration (SI)',
        'Deputy Superintendent of Immigration (DSI)',
        'Assistant Superintendent of Immigration I (ASI-1)',
        'Assistant Superintendent of Immigration II (ASI-2)',
        'Inspector of Immigration (II)',
        'Assistant Inspector of Immigration (AII)',
        'Immigration Assistant I (IA1)',
        'Immigration Assistant II (IA2)',
        'Immigration Assistant III (IA3)',
    ];
}

/**
 * True if $value is digits only (and non-empty). Shared by every controller
 * that validates an NIS number or phone number field — those must be
 * numbers only; client-side input filtering on the form strips non-digits
 * as you type, but that's cosmetic and never a substitute for checking the
 * submitted value itself.
 */
function isDigitsOnly($value) {
    return is_string($value) && $value !== '' && preg_match('/^[0-9]+$/', $value) === 1;
}

/**
 * True if $value is an 11-digit phone number (numbers only).
 */
function isValidPhone($value): bool {
    return is_string($value) && preg_match('/^\d{11}$/', trim($value)) === 1;
}

/**
 * True if $value is a valid name containing only alphabets, spaces, hyphens (-), apostrophes ('), and dots (.).
 */
function isValidName($value): bool {
    return is_string($value) && trim($value) !== '' && preg_match("/^[a-zA-Z\s\-'.]+$/", trim($value)) === 1;
}

/**
 * Format a date/datetime per the "General → Date Format"/"Time Format"
 * settings. Accepts a DateTime, a parseable string, or a Unix timestamp;
 * returns '' for null/empty input rather than today's date, so it's safe
 * to use directly on a possibly-missing DB value.
 */
function appDate($value, bool $withTime = false): string {
    if ($value === null || $value === '') {
        return '';
    }
    try {
        if ($value instanceof DateTimeInterface) {
            $dt = $value;
        } elseif (is_numeric($value)) {
            $dt = (new DateTime())->setTimestamp((int) $value);
        } else {
            $dt = new DateTime((string) $value);
        }
    } catch (Exception $e) {
        return (string) $value;
    }

    $dateFormat = class_exists('Config') ? Config::get('date_format', 'Y-m-d') : 'Y-m-d';
    if (!$withTime) {
        return $dt->format($dateFormat);
    }
    $timeFormat = class_exists('Config') ? Config::get('time_format', 'H:i') : 'H:i';
    return $dt->format($dateFormat) . ' ' . $dt->format($timeFormat);
}

/**
 * Get user roles from database
 */
function getUserRoles($userId, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT r.role_name, r.id FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get user permissions from database
 */
function getUserPermissions($userId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.permission_key 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ? AND (rp.can_view = 1 OR rp.can_create = 1 OR rp.can_edit = 1 OR rp.can_delete = 1 OR rp.can_approve = 1)
        ");
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll();
        return array_column($results, 'permission_key');
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Return the real column names of a table (cached per request).
 * Used to validate dynamic filter/sort input.
 */
function _tableColumns($tableName) {
    static $cache = [];
    if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $tableName)) {
        return [];
    }
    if (!array_key_exists($tableName, $cache)) {
        try {
            $rows = Database::fetchAll("SHOW COLUMNS FROM `{$tableName}`") ?: [];
            $cache[$tableName] = array_map(static fn($r) => $r['Field'], $rows);
        } catch (Throwable $e) {
            $cache[$tableName] = [];
        }
    }
    return $cache[$tableName];
}

/**
 * Paginate and filter database table queries dynamically.
 *
 * $limit defaults to null, which resolves to the "General → Items Per Page"
 * setting — pass an explicit number only where a page genuinely needs a
 * different page size than the rest of the app.
 */
function paginateTable($tableName, $alias, $searchColumns, $baseSql, &$params, $limit = null, $commandColumn = 'command_id') {
    if ($limit === null) {
        $limit = class_exists('Config') ? (int) Config::get('items_per_page', 50) : 50;
        if ($limit < 1) $limit = 50;
    }
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    $whereParts = [];
    
    // 1. General search
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    if ($search !== '') {
        $searchTerms = [];
        foreach ($searchColumns as $col) {
            $searchTerms[] = "{$alias}.{$col} LIKE ?";
            $params[] = "%{$search}%";
        }
        $whereParts[] = '(' . implode(' OR ', $searchTerms) . ')';
    }
    
    // Real columns of the target table — a GET filter may only reference one
    // of these. This prevents attacker-chosen column names being spliced into
    // the WHERE clause (blind-filter oracle / error-based enumeration).
    $allowedColumns = _tableColumns($tableName);

    // 2. Extra GET filters
    foreach ($_GET as $key => $val) {
        if (in_array($key, ['page', 'search', 'csrf_token', 'sort', 'dir', 'export'], true)) continue;
        if (!is_scalar($val)) continue;
        $val = trim((string) $val);
        if ($val !== '') {
            $cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if ($cleanKey) {
                $colName = $cleanKey;
                if (strpos($colName, 'filter') === 0) {
                    $colName = strtolower(substr($colName, 6));
                }

                // Column name mapping
                if ($colName === 'type') {
                    if ($tableName === 'weapons_inventory') {
                        $colName = 'weapon_type_id';
                    } elseif ($tableName === 'ammunition_inventory') {
                        $colName = 'ammunition_type_id';
                    } else {
                        $colName = ($tableName === 'aircraft_assets') ? 'aircraft_type' : (($tableName === 'motorcycle_assets') ? 'motorcycle_type' : 'asset_type');
                    }
                }
                if ($colName === 'calibre') {
                    $colName = 'calibre_id';
                }
                if ($colName === 'location') {
                    $colName = 'current_location';
                }
                if ($colName === 'condition') {
                    $colName = ($tableName === 'building_assets' || $tableName === 'movable_assets') ? 'condition_status' : 'condition';
                }
                if ($colName === 'ownership') {
                    $colName = 'ownership_type';
                }
                if ($colName === 'zone') {
                    $colName = 'zone_id';
                }
                if ($colName === 'category') {
                    $colName = 'asset_category';
                }
                if ($colName === 'status') {
                    if ($tableName === 'vehicle_assets' || $tableName === 'aircraft_assets') {
                        $colName = 'operational_status';
                    } elseif ($tableName === 'ict_assets') {
                        $colName = 'current_status';
                    } else {
                        $colName = 'status';
                    }
                }
                
                // Only allow filtering on a column that actually exists on
                // the base table. Unknown columns are silently ignored.
                if (!empty($allowedColumns) && !in_array($colName, $allowedColumns, true)) {
                    continue;
                }

                $whereParts[] = "{$alias}.{$colName} = ?";
                $params[] = $val;
            }
        }
    }

    // Apply the command filter to $baseSql/$params first, then splice in any
    // search/GET-filter conditions collected above — same order/logic as
    // before.
    $baseSql = Database::applyCommandFilter($baseSql, $alias, $params, $commandColumn);

    if (!empty($whereParts)) {
        $whereClause = implode(' AND ', $whereParts);

        if (strpos($baseSql, ' WHERE ') !== false) {
            $baseSql = str_ireplace(' WHERE ', ' WHERE ' . $whereClause . ' AND ', $baseSql);
        } else {
            $insertPos = stripos($baseSql, ' GROUP BY ');
            if ($insertPos === false) {
                $insertPos = stripos($baseSql, ' ORDER BY ');
            }

            if ($insertPos !== false) {
                $baseSql = substr_replace($baseSql, ' WHERE ' . $whereClause . ' ', $insertPos, 0);
            } else {
                $baseSql .= ' WHERE ' . $whereClause;
            }
        }
    }

    // Count query: wrap the now-fully-built $baseSql (its own WHERE/JOINs/
    // GROUP BY, command filter and search/GET filters all already applied)
    // in SELECT COUNT(*) FROM (...), reusing $params as-is. This used to be
    // a bare "SELECT COUNT(*) FROM {table} {alias}" built from scratch,
    // which silently ignored any WHERE clause a caller had already baked
    // into $baseSql before calling this function (e.g.
    // RequisitionController::my()'s "WHERE r.created_by = ?") — giving
    // either a wrong total (My Requisitions counting every command's
    // requisitions instead of just the current user's) or, once the
    // command filter's own placeholder landed on top of the caller's
    // pre-existing param, an outright "Invalid parameter number" crash that
    // made the count silently default to 0. Deriving the count from the
    // exact same SQL/params the paginated query itself uses guarantees they
    // always agree.
    $countBaseSql = preg_replace('/\s+ORDER BY\s+.*$/is', '', $baseSql, 1);
    $countSql = "SELECT COUNT(*) as count FROM ({$countBaseSql}) AS __pt_count";
    $countParams = $params;

    $totalCount = Database::fetchOne($countSql, $countParams)['count'] ?? 0;
    $totalPages = ceil($totalCount / $limit);
    if ($totalPages < 1) $totalPages = 1;
    
    $paginatedSql = $baseSql . " LIMIT ? OFFSET ?";
    $params[] = (int)$limit;
    $params[] = (int)$offset;
    
    return [
        'sql' => $paginatedSql,
        'page' => $page,
        'totalPages' => $totalPages,
        'totalCount' => $totalCount,
        'limit' => $limit
    ];
}

// Initialize session using the core Session class
Session::init();
?>