<?php
/**
 * Secure Session Management
 */
class Session {
    
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session settings. The Secure flag is enabled automatically
            // whenever the request arrived over HTTPS (or SESSION_SECURE=true).
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
                || (defined('TRUST_PROXY') && TRUST_PROXY === true
                    && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            $secureEnv = getenv('SESSION_SECURE');
            $cookieSecure = ($secureEnv === 'true' || $secureEnv === '1') ? true
                : (($secureEnv === 'false' || $secureEnv === '0') ? false : $isHttps);

            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', $cookieSecure ? 1 : 0);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.gc_maxlifetime', Config::get('session_lifetime', 600));
            
            session_name(Config::get('session_name', 'NIS_AMS_SESSION'));
            session_start();
            
            // Inactivity timeout — "Security → Session timeout" setting,
            // defaulting to 10 minutes if unset.
            if (isset($_SESSION['user_id'])) {
                $maxInactivity = class_exists('Config') ? (int) Config::get('session_timeout', 600) : 600;
                if ($maxInactivity < 60) $maxInactivity = 600; // guard against an accidental near-zero value locking everyone out
                if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > $maxInactivity)) {
                    self::destroy();
                    $loginUrl = defined('BASE_URL') ? BASE_URL . '/auth/login?timeout=1' : '/auth/login?timeout=1';
                    header('Location: ' . $loginUrl);
                    exit();
                }
                $_SESSION['_last_activity'] = time();
            }
            
            // Skip session regeneration for AJAX/API requests.
            // Regenerating on an API call would invalidate the CSRF token that was
            // embedded in the HTML page, causing every subsequent API request to fail.
            $isApi = (
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['REQUEST_URI']) &&
                    strpos($_SERVER['REQUEST_URI'], '/api/') !== false)
                || (isset($_SERVER['HTTP_ACCEPT']) &&
                    strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            );
            
            if (!$isApi) {
                // Regenerate session ID periodically (HTML page requests only)
                if (!isset($_SESSION['_last_regenerate']) ||
                    time() - $_SESSION['_last_regenerate'] > 300) {
                    $previousSessionId = session_id();
                    session_regenerate_id(true);
                    $_SESSION['_last_regenerate'] = time();
                    // The active_sessions row keyed on the old id would
                    // otherwise become an orphaned ghost entry every 5
                    // minutes — trackActiveSession() below writes a fresh
                    // row for the new id.
                    if (isset($_SESSION['user_id']) && class_exists('Database')) {
                        try {
                            Database::delete('active_sessions', 'session_id = ?', [$previousSessionId]);
                        } catch (Throwable $e) { /* table not migrated yet — fine, nothing to clean up */ }
                    }
                }
            }

            // Validate session fingerprint
            self::validateFingerprint();

            // Admin "who's online" view + force-logout support.
            self::trackActiveSession();
        }
    }

    /**
     * Record/refresh this session in `active_sessions` (throttled to once
     * every 30s so it isn't a DB write on literally every request), and
     * honor a Super Admin having force-terminated it from the Active
     * Sessions screen — the next request on a revoked session gets logged
     * out immediately rather than waiting for it to expire naturally.
     */
    private static function trackActiveSession() {
        if (!isset($_SESSION['user_id']) || !class_exists('Database')) {
            return;
        }

        try {
            $sid = session_id();
            $row = Database::fetchOne("SELECT revoked FROM active_sessions WHERE session_id = ?", [$sid]);

            if ($row && (int) $row['revoked'] === 1) {
                self::destroy();
                $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . '/auth/login?revoked=1';
                header('Location: ' . $loginUrl);
                exit();
            }

            if (isset($_SESSION['_last_track']) && time() - $_SESSION['_last_track'] < 30) {
                return; // recently recorded — skip the write this request
            }
            $_SESSION['_last_track'] = time();

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

            if ($row) {
                Database::update('active_sessions', [
                    'last_activity' => date('Y-m-d H:i:s'),
                    'ip_address' => $ip,
                    'user_agent' => $ua,
                ], 'session_id = ?', [$sid]);
            } else {
                Database::insert('active_sessions', [
                    'session_id' => $sid,
                    'user_id' => $_SESSION['user_id'],
                    'ip_address' => $ip,
                    'user_agent' => $ua,
                    'last_activity' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (Throwable $e) {
            // Table not migrated yet / DB hiccup — never block a request over this.
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        if (class_exists('Database') && session_id()) {
            try {
                Database::delete('active_sessions', 'session_id = ?', [session_id()]);
            } catch (Throwable $e) { /* table not migrated yet — nothing to clean up */ }
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    private static function validateFingerprint() {
        if (!isset($_SESSION['_fingerprint'])) {
            $_SESSION['_fingerprint'] = self::generateFingerprint();
        }
    }
    
    private static function generateFingerprint() {
        return session_id() ? hash('sha256', session_id()) : 'NIS_AMS_SECURE_SESSION';
    }
    
    public static function regenerate() {
        $previousSessionId = session_id();
        session_regenerate_id(true);
        $_SESSION['_last_regenerate'] = time();

        if ($previousSessionId && class_exists('Database')) {
            try {
                Database::delete('active_sessions', 'session_id = ?', [$previousSessionId]);
            } catch (Throwable $e) { /* table not migrated yet — nothing to clean up */ }
        }
    }
}