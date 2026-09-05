<?php
/**
 * Global security middleware — invoked once per request from index.php.
 *
 * Individual checks fail OPEN when their supporting table is missing so a
 * partially-migrated install still boots, but fail CLOSED on an explicit
 * policy match (maintenance mode on, IP not on the allow-list, rate exceeded).
 */
class Middleware {

    public static function handle($request) {
        self::sendSecurityHeaders();
        self::checkHttps();
        self::checkMaintenance();
        self::checkIpWhitelist();
        self::checkRateLimit();
        self::checkMustChangePassword();
    }

    /**
     * Belt-and-braces security headers (also set in .htaccess for Apache;
     * this covers PHP-FPM/nginx and built-in server setups).
     */
    private static function sendSecurityHeaders() {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Cross-Origin-Opener-Policy: same-origin');
        // geolocation=(self) — the app's own pages need this for per-user
        // login geofencing (see AuthController::geoCheck()); still denied to
        // any third-party/embedded content, which is what this header is
        // actually guarding against.
        header('Permissions-Policy: geolocation=(self), microphone=(), camera=(self), payment=()');
        // Single source of truth for CSP — do not also set this in .htaccess.
        // (Apache's "Header always set" adds a second CSP header rather than
        // replacing this one, and browsers enforce the intersection of all
        // CSP headers present, so a second policy here would silently
        // re-narrow this one instead of being redundant with it.)
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'none'; object-src 'none'; "
            . "img-src 'self' data: https://api.qrserver.com https://*.tile.openstreetmap.org; "
            . "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
            . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; "
            . "font-src 'self' data: https://cdnjs.cloudflare.com; connect-src 'self'");
        header_remove('X-Powered-By');

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private static function isLocalHost() {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $host = preg_replace('/:\d+$/', '', $host);
        return in_array($host, ['localhost', '127.0.0.1', '::1', 'host.docker.internal'], true)
            || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    }

    private static function checkHttps() {
        if (self::isLocalHost()) {
            return;
        }
        $forceHttps = Config::get('app_env') === 'production'
            || (getenv('FORCE_HTTPS') === 'true');
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
            || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on')
            || (getenv('APP_URL') && strpos(getenv('APP_URL'), 'https://') === 0);


        if ($forceHttps && !$isHttps) {
            header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
            exit;
        }
    }

    private static function checkMaintenance() {
        try {
            $maintenance = Database::fetchOne(
                "SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'"
            );
        } catch (Throwable $e) {
            return;
        }

        if ($maintenance && $maintenance['setting_value'] === '1') {
            if (!Auth::check() || !Auth::hasAnyRole(['Super Admin Officer', 'admin'])) {
                http_response_code(503);
                header('Retry-After: 3600');
                $view = Config::get('app_path', BASE_PATH) . '/views/errors/maintenance.php';
                if (is_file($view)) { include $view; } else { echo 'Service temporarily unavailable.'; }
                exit;
            }
        }
    }

    /**
     * Force a logged-in user with users.must_change_password = 1 (set when
     * an admin creates their account or resets their password on their
     * behalf) through the change-password form before touching anything
     * else — they never end up actually using a password only the admin
     * who set it knows.
     */
    private static function checkMustChangePassword() {
        if (!Auth::check()) {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $exempt = ['/auth/change-password', '/auth/logout', '/auth/two-factor'];
        foreach ($exempt as $path) {
            if (strpos($uri, $path) !== false) {
                return;
            }
        }

        try {
            $row = Database::fetchOne(
                "SELECT must_change_password FROM users WHERE id = ?",
                [Auth::id()]
            );
        } catch (Throwable $e) {
            return; // column/table missing on a not-yet-migrated install — fail open
        }

        if ($row && (int) $row['must_change_password'] === 1) {
            Session::set('info', 'Please set a new password before continuing.');
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/auth/change-password');
            exit;
        }
    }

    private static function checkIpWhitelist() {
        try {
            $enabled = Database::fetchOne(
                "SELECT setting_value FROM system_settings WHERE setting_key = 'ip_whitelist_enabled'"
            );
            if (!$enabled || $enabled['setting_value'] !== '1') {
                return;
            }
            $whitelisted = Database::fetchAll("SELECT ip_address FROM ip_whitelist WHERE is_active = 1") ?: [];
        } catch (Throwable $e) {
            return;
        }

        $clientIp = Security::getClientIp();
        foreach ($whitelisted as $entry) {
            if (self::ipInRange($clientIp, $entry['ip_address'])) {
                return;
            }
        }

        http_response_code(403);
        exit('Access denied: your IP address is not permitted.');
    }

    private static function checkRateLimit() {
        if (Auth::check()) {
            return;
        }

        $ip = Security::getClientIp();
        $limit = (int) Config::get('rate_limit', 120);
        $window = 60;

        try {
            $requests = Database::fetchOne(
                "SELECT COUNT(*) as count FROM request_log
                  WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
                [$ip, $window]
            );

            if ($requests && (int) $requests['count'] > $limit) {
                http_response_code(429);
                header('Retry-After: 60');
                exit('Too many requests. Please slow down and try again shortly.');
            }

            Database::insert('request_log', [
                'ip_address' => $ip,
                'url'        => substr($_SERVER['REQUEST_URI'] ?? '', 0, 255),
                'method'     => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            ]);
        } catch (Throwable $e) {
            // request_log table missing / DB hiccup — don't block traffic.
            return;
        }
    }

    private static function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        list($subnet, $mask) = explode('/', $range);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = ~((1 << (32 - (int) $mask)) - 1);
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
