<?php
/**
 * Configuration Manager
 *
 * Resolution order for a key:
 *   1. request-local cache
 *   2. the `system_settings` DB table (loaded once per request)
 *   3. constants / environment variables / built-in defaults
 *
 * Database credential keys are NEVER read from `system_settings`.
 */
class Config {
    private static $cache = [];
    private static $dbSettings = null;

    /** Keys that must only ever come from env/constants, never from the DB. */
    private static $protectedKeys = [
        'db_host', 'db_name', 'db_user', 'db_pass', 'db_charset',
        'app_env', 'app_url', 'base_url', 'app_path', 'trust_proxy',
    ];

    public static function get($key, $default = null) {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        if (!in_array($key, self::$protectedKeys, true)) {
            $dbVal = self::fromDatabase($key);
            if ($dbVal !== null) {
                return self::$cache[$key] = $dbVal;
            }
        }

        $value = self::defaults()[$key] ?? $default;
        return self::$cache[$key] = $value;
    }

    /**
     * Load every row of system_settings once, then serve from memory.
     */
    private static function fromDatabase($key) {
        if (self::$dbSettings === null) {
            self::$dbSettings = [];
            if (class_exists('Database')) {
                try {
                    $rows = Database::fetchAll("SELECT setting_key, setting_value, data_type FROM system_settings") ?: [];
                    foreach ($rows as $row) {
                        self::$dbSettings[$row['setting_key']] = $row;
                    }
                } catch (Throwable $e) {
                    self::$dbSettings = [];
                }
            }
        }

        if (!isset(self::$dbSettings[$key])) {
            return null;
        }

        $row = self::$dbSettings[$key];
        $val = $row['setting_value'];
        switch ($row['data_type'] ?? 'string') {
            case 'boolean':
            case 'bool':
                return ($val === '1' || $val === 'true' || $val === true || $val === 1);
            case 'integer':
                return (int) $val;
            case 'float':
            case 'double':
                return (float) $val;
            default:
                return $val;
        }
    }

    private static function defaults() {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $envBool = static fn($name, $fallback = false) => (
            ($v = getenv($name)) === false ? $fallback : in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true)
        );

        $config = [
            'app_name'    => defined('APP_NAME') ? APP_NAME : (getenv('APP_NAME') ?: 'NIS Asset Management System'),
            'app_url'     => defined('APP_URL') ? APP_URL : (getenv('APP_URL') ?: 'http://localhost/nis_ams'),
            'base_url'    => defined('BASE_URL') ? BASE_URL : (getenv('BASE_URL') ?: 'http://localhost/nis_ams'),
            'app_env'     => defined('APP_ENV') ? APP_ENV : (getenv('APP_ENV') ?: 'production'),
            'app_path'    => defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__),
            'app_version' => '2.0.0',
            'timezone'    => getenv('APP_TIMEZONE') ?: 'Africa/Lagos',
            'date_format' => 'Y-m-d',
            'datetime_format' => 'Y-m-d H:i:s',
            'trust_proxy' => $envBool('TRUST_PROXY', false),

            'db_host'    => defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost'),
            'db_name'    => defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'nis_ams'),
            'db_user'    => defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root'),
            'db_pass'    => defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''),
            'db_charset' => defined('DB_CHARSET') ? DB_CHARSET : (getenv('DB_CHARSET') ?: 'utf8mb4'),

            'session_name'        => defined('SESSION_NAME') ? SESSION_NAME : (getenv('SESSION_NAME') ?: 'NIS_AMS_SESSION'),
            'session_lifetime'    => (int) (defined('SESSION_LIFETIME') ? SESSION_LIFETIME : (getenv('SESSION_LIFETIME') ?: 600)),
            'csrf_token_name'     => defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : (getenv('CSRF_TOKEN_NAME') ?: 'csrf_token'),
            'password_min_length' => (int) (defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : (getenv('PASSWORD_MIN_LENGTH') ?: 8)),
            'max_login_attempts'  => (int) (defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : (getenv('MAX_LOGIN_ATTEMPTS') ?: 5)),
            // Key matches the system_settings row (setting_key = 'lockout_duration'),
            // so a Super Admin editing "Security → Lockout duration" actually takes
            // effect; LOCKOUT_TIME (env/constant) is only the pre-DB-setting fallback.
            'lockout_duration'    => (int) (defined('LOCKOUT_TIME') ? LOCKOUT_TIME : (getenv('LOCKOUT_TIME') ?: 1200)),
            'rate_limit'          => (int) (getenv('RATE_LIMIT') ?: 120),

            'max_upload_size'    => (int) (getenv('UPLOAD_MAX_SIZE') ?: 10485760),
            'allowed_file_types' => array_filter(explode(',', getenv('ALLOWED_FILE_TYPES') ?: 'pdf,jpg,jpeg,png,doc,docx')),
            'upload_path'        => dirname(__DIR__) . '/assets/uploads/',
        ];
        return $config;
    }
}
