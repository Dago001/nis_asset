<?php
/**
 * Authentication & Authorization Class
 */
class Auth {
    
    /**
     * Attempt to login user
     */
    public static function attempt($username, $password, $remember = false) {
        // Check rate limiting
        if (!self::checkRateLimit($_SERVER['REMOTE_ADDR'])) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }
        
        // Get user by username/email
        $user = Database::fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username, $username]
        );
        
        if (!$user) {
            self::recordFailedAttempt($_SERVER['REMOTE_ADDR']);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Check if account is locked
        if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
            return ['success' => false, 'message' => 'Account is temporarily locked. Please try again later.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            self::recordFailedAttempt($_SERVER['REMOTE_ADDR']);
            self::incrementLoginAttempts($user['id']);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Check if 2FA is enabled
        if ($user['two_factor_enabled']) {
            Session::set('2fa_user_id', $user['id']);
            return ['success' => true, 'requires_2fa' => true];
        }
        
        // Login successful
        self::loginUser($user, $remember);
        
        return ['success' => true, 'requires_2fa' => false];
    }
    
    /**
     * Complete login after successful 2FA
     */
    public static function completeTwoFactorLogin($userId, $remember = false) {
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$userId]);
        
        if (!$user) {
            return false;
        }
        
        self::loginUser($user, $remember);
        Session::remove('2fa_user_id');
        
        return true;
    }
    
    /**
     * Login user - set session variables
     */
    private static function loginUser($user, $remember = false) {
        // Reset login attempts
        Database::update('users', 
            ['login_attempts' => 0, 'lockout_until' => null, 'last_login' => date('Y-m-d H:i:s'), 'last_ip' => $_SERVER['REMOTE_ADDR']],
            'id = ?', [$user['id']]
        );
        
        // Set session
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('full_name', $user['full_name']);
        Session::set('email', $user['email']);
        Session::set('nis_number', $user['nis_number']);
        Session::set('rank', $user['rank']);
        Session::set('profile_image', $user['profile_image'] ?? null);
        Session::set('logged_in', true);
        Session::set('login_time', time());
        Session::set('_last_activity', time());
        Session::set('command_id', $user['command_id'] ?? null);
        
        // Get user roles
        $roles = Database::fetchAll(
            "SELECT r.role_name FROM user_roles ur 
             JOIN roles r ON ur.role_id = r.id 
             WHERE ur.user_id = ?",
            [$user['id']]
        );
        $roleNames = array_column($roles, 'role_name');
        Session::set('roles', $roleNames);
        Session::set('is_super_admin', in_array('Super Admin Officer', $roleNames));
        
        // Get user permissions
        $permissions = self::getUserPermissions($user['id']);
        Session::set('permissions', $permissions);
        
        // Set remember me cookie
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + 30 * 24 * 60 * 60; // 30 days
            
            setcookie('remember_token', $token, $expires, '/', '', true, true);
            
            Database::insert('user_sessions', [
                'user_id' => $user['id'],
                'session_token' => $token,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'login_time' => date('Y-m-d H:i:s'),
                'last_activity' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', $expires),
                'is_active' => 1
            ]);
        }
        
        // Log audit
        AuditLogger::log('LOGIN_SUCCESS', 'users', $user['id'], null, 'User logged in successfully');
    }
    
    /**
     * Check if user is logged in
     */
    public static function check() {
        if (Session::has('user_id') && Session::get('logged_in') === true) {
            self::ensureRequiredRolesExist();
            return true;
        }
        return false;
    }
    
    /**
     * Get current user ID
     */
    public static function id() {
        return Session::get('user_id');
    }
    
    /**
     * Get current user Command ID
     */
    public static function commandId() {
        return Session::get('command_id');
    }
    
    /**
     * Check if user is restricted to their command
     * HQ Armorer, HQ officers, and Super Admin bypass command isolation.
     * Command Armorer and command-level officers are restricted.
     */
    public static function isCommandRestricted() {
        if (!self::check()) {
            return false;
        }
        
        $roles = Session::get('roles', []);
        
        // These roles see ALL data across all commands
        $hqRoles = [
            'Super Admin Officer',
            'admin',
            'CGIS',
            'HQ Sectional Supervisor',
            'HQ Vetting Officer',
            'HQ Armorer',
            // Legacy 'Armorer' kept for backward compatibility — treated as HQ
            'Armorer'
        ];
        
        foreach ($hqRoles as $hqRole) {
            if (in_array($hqRole, $roles)) {
                return false;
            }
        }
        
        return self::commandId() !== null;
    }
    
    /**
     * Check if the logged-in user is any kind of Armorer
     */
    public static function isArmorer() {
        $roles = Session::get('roles', []);
        return in_array('Armorer', $roles)
            || in_array('Command Armorer', $roles)
            || in_array('HQ Armorer', $roles);
    }
    
    /**
     * Check if the logged-in user is an HQ-level Armorer (sees all weapons service-wide)
     */
    public static function isHQArmorer() {
        $roles = Session::get('roles', []);
        return in_array('HQ Armorer', $roles) || in_array('Armorer', $roles);
    }
    
    /**
     * Check if the logged-in user is a Command-level Armorer (command-restricted)
     */
    public static function isCommandArmorer() {
        $roles = Session::get('roles', []);
        return in_array('Command Armorer', $roles);
    }
    
    /**
     * Get current user data
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        return Database::fetchOne("SELECT * FROM users WHERE id = ?", [self::id()]);
    }
    
    /**
     * Returns true if the current user is a Super Admin Officer.
     */
    public static function isSuperAdmin(): bool {
        if (!self::check()) return false;
        $roles = Session::get('roles', []);
        return in_array('Super Admin Officer', $roles)
            || (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true);
    }
    
    /**
     * Check if user has a specific role
     */
    public static function hasRole($role) {
        $roles = Session::get('roles', []);
        
        // Super Admin has all roles
        if (self::isSuperAdmin()) {
            return true;
        }
        
        return in_array($role, $roles);
    }
    
    /**
     * Check if user has any of the given roles
     */
    public static function hasAnyRole($roles) {
        $userRoles = Session::get('roles', []);
        
        // Super Admin has all roles
        if (self::isSuperAdmin()) {
            return true;
        }
        
        foreach ($roles as $role) {
            if (in_array($role, $userRoles)) {
                return true;
            }
        }
        return false;
    }
    
    public static function can($permission) {
        $permissions = Session::get('permissions', []);
        $roles = Session::get('roles', []);

        // Super Admin has all permissions — checked first so nothing below
        // can ever accidentally block an actual Super Admin.
        if (self::isSuperAdmin()) {
            return true;
        }

        // CGIS sees everything in the system, but strictly read-only —
        // view/export only, never create/edit/delete/approve/manage/issue/
        // process, no matter what role_permissions grants. Centralized here
        // so every module gets this for free instead of needing its own
        // CGIS-specific carve-out. Also blocks users.manage/roles.manage/
        // settings.manage outright, since 'manage' isn't view/export.
        if (in_array('CGIS', $roles)) {
            $action = substr($permission, strrpos($permission, '.') + 1);
            if (!in_array($action, ['view', 'export'], true)) {
                return false;
            }
        }

        // The 'admin' role is a full user-management role, but Role and
        // Settings management stay Super-Admin-only — those govern the
        // permission system itself, not day-to-day operations. The literal
        // 'ADMIN' seed account is additionally blocked from all three
        // regardless of its actual role, as a guard on that specific account.
        if ($permission === 'roles.manage' || $permission === 'settings.manage') {
            if (Session::get('username') === 'ADMIN' || in_array('admin', $roles)) {
                return false;
            }
        } elseif ($permission === 'users.manage' && Session::get('username') === 'ADMIN') {
            return false;
        }

        // Requisition permission constraints: only authorized roles can view/manage requisitions
        if (strpos($permission, 'requisition.') === 0) {
            $allowedRequisitionRoles = [
                'Super Admin Officer',
                'admin',
                // CGIS is safe to include here now: the read-only rule above
                // already refuses anything but requisition.view for a CGIS
                // user before this block even runs.
                'CGIS',
                'Command Armorer',
                'Command Approval Officer',
                'HQ Armorer',
                'HQ Sectional Supervisor',
                'Armorer'
            ];
            
            $hasAllowedRole = false;
            foreach ($allowedRequisitionRoles as $r) {
                if (in_array($r, $roles)) {
                    $hasAllowedRole = true;
                    break;
                }
            }
            
            if (!$hasAllowedRole) {
                return false;
            }
            
            // Allow Command Armorer, HQ Armorer, admin and Super Admin to create
            if ($permission === 'requisition.create') {
                return in_array('Command Armorer', $roles) || in_array('HQ Armorer', $roles) || in_array('admin', $roles) || in_array('Super Admin Officer', $roles);
            }

            // Allow HQ Armorer, Armorer to issue
            if ($permission === 'requisition.issue') {
                return in_array('Armorer', $roles) || in_array('HQ Armorer', $roles);
            }

            // Separation of duties: only the designated operational workflow roles can approve/reject:
            // Command Approval Officer (at Command Approval stage) and HQ Armorer (at HQ Vetting stage).
            // Admin and Super Admin are explicitly excluded from approving weapon & ammunition requisitions.
            if ($permission === 'requisition.approve') {
                $approverRoles = ['HQ Armorer', 'Command Approval Officer'];
                foreach ($approverRoles as $ar) {
                    if (in_array($ar, $roles)) return true;
                }
                return false;
            }

            // Allow view, edit, delete for authorized roles
            return true;
        }

        // Reports permission constraints: Command Approval Officer has read/export access to their command reports
        if (($permission === 'reports.view' || $permission === 'reports.export') && in_array('Command Approval Officer', $roles)) {
            return true;
        }

        return in_array($permission, $permissions);
    }
    
    /**
     * Check if user has any of the given permissions
     */
    public static function canAny($permissions) {
        // Super Admin has all permissions
        if (self::isSuperAdmin()) {
            return true;
        }
        
        $userPermissions = Session::get('permissions', []);
        
        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has all of the given permissions
     */
    public static function canAll($permissions) {
        // Super Admin has all permissions
        if (self::isSuperAdmin()) {
            return true;
        }
        
        $userPermissions = Session::get('permissions', []);
        
        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * True when the caller expects JSON (XHR, /api/ path, or Accept: json).
     */
    public static function wantsJson() {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false)
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    private static function denyRequest($httpCode, $message, $redirectPath) {
        if (self::wantsJson()) {
            http_response_code($httpCode);
            if (!headers_sent()) { header('Content-Type: application/json'); }
            echo json_encode(['success' => false, 'error' => $message]);
        } else {
            header('Location: ' . Config::get('app_url') . $redirectPath);
        }
        exit();
    }

    /**
     * Require authentication (401 JSON for APIs, redirect for pages).
     */
    public static function requireAuth() {
        if (!self::check()) {
            self::denyRequest(401, 'Authentication required', '/auth/login');
        }
    }

    /**
     * Require a specific permission (403 JSON for APIs, redirect for pages).
     */
    public static function requirePermission($permission) {
        self::requireAuth();
        if (!self::can($permission)) {
            self::denyRequest(403, 'You are not authorised to perform this action', '/auth/unauthorized');
        }
    }

    /**
     * Require any of the given permissions.
     */
    public static function requireAnyPermission($permissions) {
        self::requireAuth();
        if (!self::canAny($permissions)) {
            self::denyRequest(403, 'You are not authorised to perform this action', '/auth/unauthorized');
        }
    }
    
    /**
     * Get all permissions for a user
     */
    private static function getUserPermissions($userId) {
        $sql = "SELECT DISTINCT p.permission_key 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ? AND (rp.can_view = 1 OR rp.can_create = 1 OR rp.can_edit = 1 OR rp.can_delete = 1 OR rp.can_approve = 1)";
        
        $permissions = Database::fetchAll($sql, [$userId]);
        return array_column($permissions, 'permission_key');
    }
    
    /**
     * Check high-frequency flood rate limiting for IP address (anti-DDoS / flood protection).
     * Set high enough (50 attempts per 5 mins) so legitimate multiple users sharing a computer/network are never blocked.
     */
    private static function checkRateLimit($ip) {
        $floodLimit = (int) Config::get('ip_flood_limit', 50);
        $floodWindow = (int) Config::get('ip_flood_window', 300); // 5 minutes
        
        $attempts = Database::fetchOne(
            "SELECT COUNT(*) as count 
             FROM audit_logs 
             WHERE ip_address = ? AND action = 'LOGIN_FAILED' 
             AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$ip, $floodWindow]
        );
        
        return ($attempts['count'] ?? 0) < $floodLimit;
    }
    
    /**
     * Record failed login attempt
     */
    private static function recordFailedAttempt($ip) {
        AuditLogger::log('LOGIN_FAILED', null, null, null, 'Failed login attempt', null, null, $ip);
    }

    /**
     * Throttle password-reset requests: max 5 per IP per hour.
     */
    public static function checkResetRateLimit($ip) {
        try {
            $row = Database::fetchOne(
                "SELECT COUNT(*) as count FROM audit_logs
                  WHERE ip_address = ? AND action = 'PASSWORD_RESET_REQUEST'
                  AND created_at > DATE_SUB(NOW(), INTERVAL 3600 SECOND)",
                [$ip]
            );
        } catch (Throwable $e) {
            return true;
        }
        return !$row || (int) $row['count'] < 5;
    }
    
    /**
     * Increment login attempts for a specific user account.
     * Enforces 5 attempts -> 20 minutes (1200s) account lockout.
     */
    private static function incrementLoginAttempts($userId) {
        $user = Database::fetchOne("SELECT login_attempts FROM users WHERE id = ?", [$userId]);
        
        $maxAttempts = (int) Config::get('max_login_attempts', 5);
        $lockoutDuration = (int) Config::get('lockout_duration', 1200); // 20 minutes
        
        $attempts = ($user['login_attempts'] ?? 0) + 1;
        $lockoutUntil = null;
        $isLocked = false;
        
        if ($attempts >= $maxAttempts) {
            $lockoutUntil = date('Y-m-d H:i:s', time() + $lockoutDuration);
            $isLocked = true;
        }
        
        Database::update('users', 
            ['login_attempts' => $attempts, 'lockout_until' => $lockoutUntil],
            'id = ?', [$userId]
        );

        return [
            'attempts' => $attempts,
            'remaining' => max(0, $maxAttempts - $attempts),
            'is_locked' => $isLocked,
            'lockout_duration_mins' => (int) round($lockoutDuration / 60)
        ];
    }
    
    // =====================================================================
    //  Password stage of the interactive login (used by AuthController::login())
    // =====================================================================

    /**
     * Verify username/password with account-specific lockout protection.
     *
     * - Gives the specific account 5 attempts.
     * - Restricts that account for 20 minutes upon reaching 5 failed attempts.
     * - Allows other users on the same computer to log in with their correct details.
     *
     * On success returns ['success' => true, 'user_id' => <id>].
     * On failure returns ['success' => false, 'message' => <safe text>].
     */
    public static function passwordStageLogin($username, $password) {
        $ip = Security::getClientIp();
        $generic = 'Invalid username or password. Please check your credentials and try again.';

        // 1. Anti-DDoS flood protection against high-rate bot attacks from single IP
        if (!self::checkRateLimit($ip)) {
            return ['success' => false, 'message' => 'Too many failed requests from this network. Please wait a few minutes and try again.'];
        }

        $username = trim((string) $username);
        $user = Database::fetchOne(
            "SELECT id, username, email, password_hash, is_active, login_attempts, lockout_until
               FROM users
              WHERE (LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?))",
            [$username, $username]
        );

        if (!$user) {
            password_verify($password, '$2y$10$usesomesillystringforsalt0000000000000000000000000000000');
            self::recordFailedAttempt($ip);
            return ['success' => false, 'message' => $generic];
        }

        // 2. Per-account lockout check (20 minutes restriction)
        if (!empty($user['lockout_until'])) {
            $lockoutTimestamp = strtotime($user['lockout_until']);
            if ($lockoutTimestamp > time()) {
                $minsRemaining = (int) ceil(($lockoutTimestamp - time()) / 60);
                return [
                    'success' => false, 
                    'message' => "This account is temporarily restricted due to 5 consecutive failed login attempts. Please try again in {$minsRemaining} minute(s)."
                ];
            }
            // Lockout period has expired — clear it and reset attempts
            Database::update('users', ['login_attempts' => 0, 'lockout_until' => null], 'id = ?', [$user['id']]);
            $user['login_attempts'] = 0;
            $user['lockout_until'] = null;
        }

        $password = trim((string) $password);
        $hash = $user['password_hash'] ?? '';

        $valid = password_verify($password, $hash)
              || ($password === $hash)
              || (md5($password) === $hash)
              || (sha1($password) === $hash);

        if (!$valid) {
            self::recordFailedAttempt($ip);
            $lockInfo = self::incrementLoginAttempts($user['id']);
            
            if ($lockInfo['is_locked']) {
                $mins = $lockInfo['lockout_duration_mins'];
                return [
                    'success' => false,
                    'message' => "Maximum login attempts (5) exceeded. This account has been restricted for {$mins} minutes."
                ];
            } else {
                $remaining = $lockInfo['remaining'];
                return [
                    'success' => false,
                    'message' => "Invalid password. {$remaining} attempt(s) remaining before this account is restricted for 20 minutes."
                ];
            }
        }

        // Auto-rehash to BCrypt if stored as plain text or legacy format
        if ($password === $hash || md5($password) === $hash || sha1($password) === $hash) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            Database::update('users', ['password_hash' => $newHash], 'id = ?', [$user['id']]);
        }

        // 3. Disabled account check (checked after password verify)
        if (empty($user['is_active'])) {
            return ['success' => false, 'message' => 'This account is inactive. Contact your administrator.'];
        }

        // Success — clear failed attempts counter and lockout
        Database::update('users',
            ['login_attempts' => 0, 'lockout_until' => null],
            'id = ?', [$user['id']]
        );

        return ['success' => true, 'user_id' => $user['id']];
    }

    /**
     * Logout user
     */
    public static function logout() {
        // Log audit
        if (self::check()) {
            AuditLogger::log('LOGOUT', 'users', self::id(), null, 'User logged out');
            
            // Invalidate remember token
            if (isset($_COOKIE['remember_token'])) {
                Database::update('user_sessions', 
                    ['is_active' => 0],
                    'session_token = ? AND user_id = ?',
                    [$_COOKIE['remember_token'], self::id()]
                );
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }
        }
        
        Session::destroy();
    }
    
    /**
     * Ensure required roles (admin, CGIS, Armorer roles) exist in the database and have correct permissions
     */
    public static function ensureRequiredRolesExist() {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        
        try {
            // 1. CGIS Role
            $role = Database::fetchOne("SELECT id FROM roles WHERE role_name = 'CGIS'");
            if (!$role) {
                $roleId = Database::insert('roles', [
                    'role_name' => 'CGIS',
                    'description' => 'Comptroller General - Analytical Dashboard Access Only'
                ]);
                
                $permissions = Database::fetchAll("SELECT * FROM permissions");
                foreach ($permissions as $p) {
                    $key = $p['permission_key'];
                    if (strpos($key, '.view') !== false || $key === 'reports.view' || $key === 'audit.view' || $key === 'dashboard.view') {
                        Database::insert('role_permissions', [
                            'role_id' => $roleId,
                            'permission_id' => $p['id'],
                            'can_view' => 1,
                            'can_create' => 0,
                            'can_edit' => 0,
                            'can_delete' => 0,
                            'can_approve' => 0
                        ]);
                    }
                }
            }
            
            // 2. admin Role
            $adminRole = Database::fetchOne("SELECT id FROM roles WHERE role_name = 'admin'");
            if (!$adminRole) {
                $roleId = Database::insert('roles', [
                    'role_name' => 'admin',
                    'description' => 'System Administrator with limited privileges (no User Management)'
                ]);
                
                $permissions = Database::fetchAll("SELECT * FROM permissions");
                foreach ($permissions as $p) {
                    $key = $p['permission_key'];
                    if ($key !== 'users.manage' && $key !== 'roles.manage') {
                        Database::insert('role_permissions', [
                            'role_id' => $roleId,
                            'permission_id' => $p['id'],
                            'can_view' => 1,
                            'can_create' => 1,
                            'can_edit' => 1,
                            'can_delete' => 1,
                            'can_approve' => 1
                        ]);
                    }
                }
            }
            
            // 3. Armorer Roles
            $armorerRoles = [
                'Armorer' => 'Officer managing weapons and ammunition inventory',
                'Command Armorer' => 'Armorer restricted to their command weapons and ammunition',
                'HQ Armorer' => 'HQ Armorer with visibility of all weapons and ammunition service-wide'
            ];
            
            foreach ($armorerRoles as $name => $desc) {
                $r = Database::fetchOne("SELECT id FROM roles WHERE role_name = ?", [$name]);
                if (!$r) {
                    $roleId = Database::insert('roles', [
                        'role_name' => $name,
                        'description' => $desc
                    ]);
                    
                    $permissions = Database::fetchAll("SELECT * FROM permissions");
                    foreach ($permissions as $p) {
                        $key = $p['permission_key'];
                        if (strpos($key, 'weapons.') === 0 || strpos($key, 'ammunition.') === 0 || strpos($key, 'requisition.') === 0 || strpos($key, 'returns.') === 0 || strpos($key, 'weapon_issue.') === 0 || strpos($key, 'audit.') === 0 || $key === 'reports.view') {
                            Database::insert('role_permissions', [
                                'role_id' => $roleId,
                                'permission_id' => $p['id'],
                                'can_view' => 1,
                                'can_create' => 1,
                                'can_edit' => 1,
                                'can_delete' => 1,
                                'can_approve' => 1
                            ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Failed to auto-migrate roles: " . $e->getMessage());
        }
    }
}