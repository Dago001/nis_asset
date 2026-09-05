<?php
/**
 * Auth Controller for Login, 2FA, and Logout
 */
class AuthController extends Controller {
    
    public function __construct() {
        if (method_exists(get_parent_class($this), '__construct')) {
            parent::__construct();
        }
    }

    public function logout() {
        if (class_exists('AuditLogger') && Auth::check()) {
            AuditLogger::log('LOGOUT', 'users', Auth::id(), null, 'User logged out');
        }

        // Session::destroy() also clears this session's active_sessions
        // tracking row — the raw session_destroy() this used to call
        // directly left a stale "still online" row behind until it aged
        // out of the Active Sessions admin view on its own.
        Session::destroy();

        // Redirect to clean login URL (http://localhost/nis_ams)
        header('Location: ' . BASE_URL);
        exit;
    }
    
    public function loginForm() {
        if (Auth::check()) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        require BASE_PATH . '/views/auth/login.php';
    }

    /**
     * Process the login form (password stage).
     */
    public function login() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->loginRedirect('Invalid or expired security token. Please try again.');
        }

        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->loginRedirect('Please enter your username and password');
        }

        try {
            $result = Auth::passwordStageLogin($username, $password);
        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $this->loginRedirect('A system error occurred. Please try again later.');
        }

        if (empty($result['success'])) {
            $this->loginRedirect($result['message'] ?? 'Invalid username or password');
        }

        // Password verified.
        $userId = $result['user_id'];
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$userId]);

        if (!$user) {
            $this->loginRedirect('User account is inactive or missing.');
        }

        session_regenerate_id(true);

        // 1. If password must be changed (e.g. newly created user), direct to change password prompt first!
        if (!empty($user['must_change_password'])) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['must_change_password_pending'] = 1;
            $_SESSION['info'] = 'Please create a new password before setting up Google Authenticator.';
            header('Location: ' . BASE_URL . '/auth/change-password');
            exit;
        }

        // 2. Accounts with a location restriction stop here first
        if (!empty($user['geofence_enabled']) && !$this->isSuperAdminUser($userId)) {
            $_SESSION['temp_geo_user_id'] = $userId;
            $_SESSION['temp_geo_started'] = time();
            unset($_SESSION['temp_geo_error']);

            header('Location: ' . BASE_URL . '/auth/geo-check');
            exit;
        }

        // 3. Prompt for Google Authenticator 2FA (QR setup on first time, code entry on returning logins)
        $_SESSION['temp_2fa_user_id'] = $userId;
        $_SESSION['temp_2fa_started'] = time();
        $_SESSION['temp_2fa_tries'] = 0;
        unset($_SESSION['temp_2fa_secret'], $_SESSION['temp_2fa_redirect_profile']);

        header('Location: ' . BASE_URL . '/auth/two-factor');
        exit;
    }

    private function loginRedirect($message) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if ($message) {
            $_SESSION['error'] = $message;
        }
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }

    /** Show the "not authorised" page. */
    public function unauthorized() {
        http_response_code(403);
        if (is_file(BASE_PATH . '/views/auth/unauthorized.php')) {
            require BASE_PATH . '/views/auth/unauthorized.php';
        } else {
            echo 'You are not authorised to access this resource.';
        }
    }

    /** Generate a fresh CAPTCHA string into the session. */
    private function generateCaptcha() {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $_SESSION['captcha'] = $code;
        return $code;
    }

    /** AJAX endpoint to refresh the CAPTCHA. */
    public function refreshCaptcha() {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'captcha' => $this->generateCaptcha()]);
        exit;
    }

    /**
     * Show Two-Factor Authentication Page
     */
    public function twoFactorForm() {
        // enable2FA() (voluntary re-enrolment from the profile page) is only
        // reachable while already logged in, so a bare Auth::check() guard
        // would bounce that flow straight back to the dashboard before it
        // ever showed the QR code. Only redirect away when there's no 2FA
        // step actually pending — that still covers "already logged in,
        // just poked this URL directly".
        if (Auth::check() && !isset($_SESSION['temp_2fa_user_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        if (!isset($_SESSION['temp_2fa_user_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $userId = $_SESSION['temp_2fa_user_id'];
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$userId]);
        
        if (!$user) {
            unset($_SESSION['temp_2fa_user_id']);
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('User not found or inactive'));
            exit;
        }

        $isFirstTime = empty($user['two_factor_secret']);
        $secret = '';
        $qrCodeUrl = '';

        if ($isFirstTime) {
            // Retrieve or generate setup secret
            if (!isset($_SESSION['temp_2fa_secret'])) {
                $_SESSION['temp_2fa_secret'] = TOTP::generateSecret();
            }
            $secret = $_SESSION['temp_2fa_secret'];
            
            // Generate QR Code URL
            $appName = 'NIS-AMS';
            $label = $user['username'];
            $qrCodeUrl = "otpauth://totp/" . rawurlencode($appName) . ":" . rawurlencode($label) . "?secret=" . $secret . "&issuer=" . rawurlencode($appName);
        }

        $this->view('auth/two-factor', [
            'isFirstTime' => $isFirstTime,
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
            'username' => $user['username'],
            'title' => 'Two-Factor Authentication'
        ]);
    }

    /**
     * Verify Two-Factor Authentication code
     */
    public function twoFactorVerify() {
        if (!isset($_SESSION['temp_2fa_user_id'])) {
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('Please login first'));
            exit;
        }

        // The password stage grants a short window to complete 2FA.
        $started = (int) ($_SESSION['temp_2fa_started'] ?? 0);
        if ($started === 0 || (time() - $started) > 600) {
            $this->clearTwoFactorState();
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('Your sign-in session expired. Please log in again.'));
            exit;
        }

        $userId = $_SESSION['temp_2fa_user_id'];
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$userId]);

        if (!$user) {
            $this->clearTwoFactorState();
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('User not found or inactive'));
            exit;
        }

        // Validate CSRF token
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: ' . BASE_URL . '/auth/two-factor?error=' . urlencode('Invalid security token'));
            exit;
        }

        // Throttle code guessing per session.
        $_SESSION['temp_2fa_tries'] = (int) ($_SESSION['temp_2fa_tries'] ?? 0) + 1;
        if ($_SESSION['temp_2fa_tries'] > 8) {
            $this->clearTwoFactorState();
            AuditLogger::log('LOGIN_FAILED', 'users', $userId, $userId, 'Too many 2FA attempts');
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('Too many invalid codes. Please log in again.'));
            exit;
        }

        $code = trim($_POST['code'] ?? '');
        if ($code === '') {
            header('Location: ' . BASE_URL . '/auth/two-factor?error=' . urlencode('Please enter verification code'));
            exit;
        }

        $isFirstTime = empty($user['two_factor_secret']);
        $secret = $isFirstTime ? ($_SESSION['temp_2fa_secret'] ?? '') : $user['two_factor_secret'];

        if (empty($secret)) {
            header('Location: ' . BASE_URL . '/auth/two-factor?error=' . urlencode('2FA session expired. Please log in again.'));
            exit;
        }

        $slice = TOTP::verify($secret, $code);

        // Replay protection: a given code (time-slice) can only be used once.
        $lastSlice = isset($user['two_factor_last_slice']) ? (int) $user['two_factor_last_slice'] : 0;
        if ($slice !== false && $lastSlice > 0 && $slice <= $lastSlice) {
            header('Location: ' . BASE_URL . '/auth/two-factor?error=' . urlencode('That code was already used. Wait for the next one.'));
            exit;
        }

        if ($slice === false) {
            header('Location: ' . BASE_URL . '/auth/two-factor?error=' . urlencode('Invalid verification code. Please try again.'));
            exit;
        }

        Database::beginTransaction();
        try {
            $update = [
                'two_factor_last_slice' => $slice,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($isFirstTime) {
                $update['two_factor_secret'] = $secret;
                $update['two_factor_enabled'] = 1;
            }
            $this->safeUserUpdate($update, $userId);
            unset($_SESSION['temp_2fa_secret']);

            $this->loginUserSession($user);

            $redirectUrl = BASE_URL . '/dashboard';
            if (isset($_SESSION['temp_2fa_redirect_profile'])) {
                $redirectUrl = BASE_URL . '/users/profile';
            }

            $this->clearTwoFactorState();
            Database::commit();

            header('Location: ' . $redirectUrl);
            exit;
        } catch (Exception $e) {
            Database::rollBack();
            error_log("2FA completion error: " . $e->getMessage());
            header('Location: ' . BASE_URL . '/auth/two-factor?error=' . urlencode('Could not complete sign-in. Please try again.'));
            exit;
        }
    }

    /** Clear all transient login/2FA session keys. */
    private function clearTwoFactorState() {
        foreach ([
            'temp_2fa_user_id', 'temp_2fa_remember', 'temp_2fa_secret',
            'temp_2fa_started', 'temp_2fa_tries', 'temp_2fa_redirect_profile',
        ] as $k) {
            unset($_SESSION[$k]);
        }
    }

    /**
     * Update `users`, transparently dropping the two_factor_last_slice column
     * if the schema migration has not been applied yet.
     */
    private function safeUserUpdate(array $data, $userId) {
        try {
            Database::update('users', $data, 'id = ?', [$userId]);
        } catch (Throwable $e) {
            unset($data['two_factor_last_slice']);
            Database::update('users', $data, 'id = ?', [$userId]);
        }
    }

    /**
     * True if $userId currently holds the Super Admin Officer role. Checked
     * directly against the DB (not Auth::isSuperAdmin(), which reads the
     * session's role list) because this runs mid-login, before
     * loginUserSession() has populated that session state.
     */
    private function isSuperAdminUser($userId) {
        $row = Database::fetchOne(
            "SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id = r.id
              WHERE ur.user_id = ? AND r.role_name = 'Super Admin Officer' LIMIT 1",
            [$userId]
        );
        return !empty($row);
    }

    /** Great-circle distance between two lat/lng points, in meters. */
    private function haversineDistanceMeters($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Clear all transient geofence-login session keys. */
    private function clearGeoState() {
        foreach (['temp_geo_user_id', 'temp_geo_started', 'temp_geo_error'] as $k) {
            unset($_SESSION[$k]);
        }
    }

    /**
     * Show the "checking your location" page — pure JS, requests the
     * browser's GPS position and auto-submits it to geoVerify(). Mirrors
     * twoFactorForm()'s guard: only reachable with a pending geo check.
     */
    public function geoCheck() {
        if (!isset($_SESSION['temp_geo_user_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $error = $_SESSION['temp_geo_error'] ?? null;
        unset($_SESSION['temp_geo_error']);

        $this->view('auth/geo-check', [
            'error' => $error,
            'title' => 'Verifying Location',
        ]);
    }

    /**
     * Complete (or reject) a login pending a location check. Same 10-minute
     * window as the 2FA stage; on success, falls through to the existing
     * 2FA branch or straight to loginUserSession(), whichever this account
     * needs next — exactly what login() itself does once past this gate.
     */
    public function geoVerify() {
        if (!isset($_SESSION['temp_geo_user_id'])) {
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('Please login first'));
            exit;
        }

        $started = (int) ($_SESSION['temp_geo_started'] ?? 0);
        if ($started === 0 || (time() - $started) > 600) {
            $this->clearGeoState();
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('Your sign-in session expired. Please log in again.'));
            exit;
        }

        $userId = $_SESSION['temp_geo_user_id'];
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$userId]);

        if (!$user) {
            $this->clearGeoState();
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('User not found or inactive'));
            exit;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['temp_geo_error'] = 'Invalid security token. Please try again.';
            header('Location: ' . BASE_URL . '/auth/geo-check');
            exit;
        }

        // The browser reports "denied" / "unavailable" / "timeout" as an
        // empty coordinate pair rather than failing the POST outright, so
        // this account's requirement can be explained clearly instead of
        // just erroring out.
        $lat = $_POST['lat'] ?? '';
        $lng = $_POST['lng'] ?? '';
        if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
            $_SESSION['temp_geo_error'] = 'Location access is required for this account. Please allow location access in your browser and try again.';
            header('Location: ' . BASE_URL . '/auth/geo-check');
            exit;
        }

        if ($user['geofence_lat'] === null || $user['geofence_lng'] === null || empty($user['geofence_radius_m'])) {
            // Enabled but never actually configured — fail closed, not open.
            AuditLogger::log('LOGIN_FAILED', 'users', $userId, $userId, 'Geofence enabled but not configured');
            $this->clearGeoState();
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('This account is not fully configured. Contact your administrator.'));
            exit;
        }

        $distance = $this->haversineDistanceMeters(
            (float) $lat, (float) $lng,
            (float) $user['geofence_lat'], (float) $user['geofence_lng']
        );

        if ($distance > (float) $user['geofence_radius_m']) {
            AuditLogger::log('LOGIN_FAILED', 'users', $userId, $userId,
                'Login blocked by geofence (' . round($distance) . 'm from allowed location, radius ' . $user['geofence_radius_m'] . 'm)');
            $_SESSION['temp_geo_error'] = 'You are outside the allowed location for this account. Contact your administrator if you believe this is wrong.';
            header('Location: ' . BASE_URL . '/auth/geo-check');
            exit;
        }

        $this->clearGeoState();

        // Same branch login() itself takes once past this gate: 2FA next if
        // enabled, otherwise straight into the session.
        if (!empty($user['two_factor_enabled'])) {
            $_SESSION['temp_2fa_user_id'] = $userId;
            $_SESSION['temp_2fa_started'] = time();
            $_SESSION['temp_2fa_tries'] = 0;
            unset($_SESSION['temp_2fa_secret'], $_SESSION['temp_2fa_redirect_profile']);

            header('Location: ' . BASE_URL . '/auth/two-factor');
            exit;
        }

        $this->loginUserSession($user);
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    /**
     * Disable 2FA for the currently logged-in user.
     * Requires POST + CSRF + the account password (a security downgrade must
     * never be doable via a cross-site GET).
     */
    public function disable2FA() {
        if (!Auth::check()) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
            || !Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: ' . BASE_URL . '/users/profile?error=' . urlencode('Invalid request. Use the Disable button on your profile.'));
            exit;
        }

        $userId = Auth::id();
        $user = Database::fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        if (!$user || !password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            header('Location: ' . BASE_URL . '/users/profile?error=' . urlencode('Password incorrect. 2FA was not changed.'));
            exit;
        }

        try {
            $this->safeUserUpdate([
                'two_factor_secret'     => null,
                'two_factor_enabled'    => 0,
                'two_factor_last_slice' => 0,
                'updated_at'            => date('Y-m-d H:i:s'),
            ], $userId);
            $_SESSION['two_factor_enabled'] = false;
            AuditLogger::log('2FA_DISABLED', 'users', $userId, $userId, 'User disabled two-factor authentication');
            header('Location: ' . BASE_URL . '/users/profile?success=' . urlencode('Two-Factor Authentication has been disabled.'));
            exit;
        } catch (Exception $e) {
            error_log('Disable 2FA error: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/users/profile?error=' . urlencode('Could not disable 2FA. Please try again.'));
            exit;
        }
    }

    /**
     * Start the 2FA (re-)enrolment flow for the logged-in user.
     * Requires POST + CSRF.
     */
    public function enable2FA() {
        if (!Auth::check()) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
            || !Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: ' . BASE_URL . '/users/profile?error=' . urlencode('Invalid request. Use the Enable button on your profile.'));
            exit;
        }

        $_SESSION['temp_2fa_user_id'] = Auth::id();
        $_SESSION['temp_2fa_started'] = time();
        $_SESSION['temp_2fa_tries'] = 0;
        $_SESSION['temp_2fa_redirect_profile'] = true;
        unset($_SESSION['temp_2fa_secret']);

        header('Location: ' . BASE_URL . '/auth/two-factor');
        exit;
    }

    /**
     * Helper to set full logged-in user session
     */
    private function loginUserSession($user) {
        $pdo = getDBConnection();
        
        // Update last login details and reset attempts
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = ?, login_attempts = 0, lockout_until = NULL WHERE id = ?");
        $updateStmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', $user['id']]);
        
        // Get user roles
        $stmt = $pdo->prepare("
            SELECT r.role_name, r.id 
            FROM user_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $roleNames = array_column($roles, 'role_name');
        $roleIds = array_column($roles, 'id');
        
        // Get user permissions
        $permissions = getUserPermissions($user['id'], $pdo);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['nis_number'] = $user['nis_number'];
        $_SESSION['rank'] = $user['rank'];
        $_SESSION['profile_image'] = $user['profile_image'] ?? null;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['_last_activity'] = time();
        $_SESSION['roles'] = $roleNames;
        $_SESSION['role_ids'] = $roleIds;
        $_SESSION['permissions'] = $permissions;
        $_SESSION['is_super_admin'] = in_array('Super Admin Officer', $roleNames);
        $_SESSION['command_id'] = $user['command_id'] ?? null;
        
        // Log audit log
        if (class_exists('AuditLogger')) {
            AuditLogger::log('LOGIN_SUCCESS', 'users', $user['id'], null, 'User logged in via 2FA');
        }
    }

    /**
     * Show Change Password Form
     */
    public function changePasswordForm() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $row = Database::fetchOne("SELECT must_change_password FROM users WHERE id = ?", [$_SESSION['user_id']]);
        $forced = ($row && (int) $row['must_change_password'] === 1) || !empty($_SESSION['must_change_password_pending']);

        $this->view('auth/change_password', [
            'title'  => 'Change Password',
            'active' => 'profile',
            'forced' => $forced,
        ]);
    }

    /**
     * Process Password Change Request
     */
    public function changePassword() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Validate CSRF token
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::set('error', 'Invalid security token');
            header('Location: ' . BASE_URL . '/auth/change-password');
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Session::set('error', 'All fields are required');
            header('Location: ' . BASE_URL . '/auth/change-password');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            Session::set('error', 'New passwords do not match');
            header('Location: ' . BASE_URL . '/auth/change-password');
            exit;
        }

        if ($policyError = Security::passwordPolicyError($newPassword)) {
            Session::set('error', $policyError);
            header('Location: ' . BASE_URL . '/auth/change-password');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            Session::set('error', 'Current password is incorrect');
            header('Location: ' . BASE_URL . '/auth/change-password');
            exit;
        }

        if (password_verify($newPassword, $user['password_hash'])) {
            Session::set('error', 'Your new password must be different from the current one.');
            header('Location: ' . BASE_URL . '/auth/change-password');
            exit;
        }

        // Update password
        Database::beginTransaction();
        try {
            $wasForced = !empty($user['must_change_password']) || !empty($_SESSION['must_change_password_pending']);

            Database::update('users', [
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'must_change_password' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$userId]);

            if (class_exists('AuditLogger')) {
                AuditLogger::log('PASSWORD_CHANGE', 'users', $userId, null, 'User changed password');
            }

            Database::commit();

            if ($wasForced) {
                // Step 2: Transition directly to Google Authenticator QR Code setup!
                $_SESSION['temp_2fa_user_id'] = $userId;
                $_SESSION['temp_2fa_started'] = time();
                $_SESSION['temp_2fa_tries'] = 0;
                unset($_SESSION['temp_2fa_secret'], $_SESSION['must_change_password_pending']);
                unset($_SESSION['logged_in']);

                $_SESSION['success'] = 'Password changed successfully! Please scan the QR code with Google Authenticator to complete setup and sign in.';
                header('Location: ' . BASE_URL . '/auth/two-factor');
                exit;
            } else {
                Session::set('success', 'Password updated successfully!');
                header('Location: ' . BASE_URL . '/users/profile');
                exit;
            }
        } catch (Exception $e) {
            Database::rollBack();
            error_log('Password change error: ' . $e->getMessage());
            Session::set('error', 'Could not update your password. Please try again.');
            header('Location: ' . BASE_URL . '/auth/change-password');
            exit;
        }
    }

    // =====================================================================
    //  Forgotten-password flow
    // =====================================================================

    public function forgotPasswordForm() {
        if (Auth::check()) { header('Location: ' . BASE_URL . '/dashboard'); exit; }
        require BASE_PATH . '/views/auth/forgot_password.php';
    }

    public function forgotPassword() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
            || !Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: ' . BASE_URL . '/auth/forgot-password?error=' . urlencode('Invalid security token.'));
            exit;
        }

        // Rate-limit reset requests per IP.
        if (!Auth::checkResetRateLimit(Security::getClientIp())) {
            header('Location: ' . BASE_URL . '/auth/forgot-password?error=' . urlencode('Too many requests. Please try again later.'));
            exit;
        }

        $identifier = trim($_POST['email'] ?? $_POST['username'] ?? '');
        $genericMsg = 'If that account exists, a password-reset link has been sent to its registered email address.';

        if ($identifier !== '') {
            $user = Database::fetchOne(
                "SELECT id, email, full_name FROM users WHERE (email = ? OR username = ?) AND is_active = 1",
                [$identifier, $identifier]
            );

            if ($user && !empty($user['email'])) {
                $token = bin2hex(random_bytes(32));
                Database::update('users', [
                    'password_reset_token'   => hash('sha256', $token),
                    'password_reset_expires' => date('Y-m-d H:i:s', time() + 3600),
                ], 'id = ?', [$user['id']]);

                $link = BASE_URL . '/auth/reset-password?token=' . $token . '&uid=' . $user['id'];
                Mailer::send(
                    $user['email'],
                    'Password reset — NIS Asset Management System',
                    "Hello " . ($user['full_name'] ?: '') . ",\n\n"
                    . "A password reset was requested for your account. This link is valid for 1 hour:\n\n"
                    . $link . "\n\n"
                    . "If you did not request this, you can ignore this email; your password will not change.\n"
                );
                AuditLogger::log('PASSWORD_RESET_REQUEST', 'users', $user['id'], $user['id'], 'Password reset requested');
            }
        }

        header('Location: ' . BASE_URL . '/auth/login?success=' . urlencode($genericMsg));
        exit;
    }

    public function resetPasswordForm() {
        if (Auth::check()) { header('Location: ' . BASE_URL . '/dashboard'); exit; }
        $token = $_GET['token'] ?? '';
        $uid   = $_GET['uid'] ?? '';
        $valid = $this->findResetUser($token, $uid) !== null;
        require BASE_PATH . '/views/auth/reset_password.php';
    }

    public function resetPassword() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
            || !Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('Invalid security token.'));
            exit;
        }

        $token = $_POST['token'] ?? '';
        $uid   = $_POST['uid'] ?? '';
        $user  = $this->findResetUser($token, $uid);

        if ($user === null) {
            header('Location: ' . BASE_URL . '/auth/login?error=' . urlencode('That reset link is invalid or has expired.'));
            exit;
        }

        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if ($new !== $confirm) {
            header('Location: ' . BASE_URL . '/auth/reset-password?token=' . urlencode($token) . '&uid=' . urlencode($uid) . '&error=' . urlencode('Passwords do not match.'));
            exit;
        }
        if ($err = Security::passwordPolicyError($new)) {
            header('Location: ' . BASE_URL . '/auth/reset-password?token=' . urlencode($token) . '&uid=' . urlencode($uid) . '&error=' . urlencode($err));
            exit;
        }

        Database::update('users', [
            'password_hash'          => password_hash($new, PASSWORD_DEFAULT),
            'password_reset_token'   => null,
            'password_reset_expires' => null,
            'login_attempts'         => 0,
            'lockout_until'          => null,
            'updated_at'             => date('Y-m-d H:i:s'),
        ], 'id = ?', [$user['id']]);

        AuditLogger::log('PASSWORD_RESET', 'users', $user['id'], $user['id'], 'Password reset via emailed link');
        header('Location: ' . BASE_URL . '/auth/login?success=' . urlencode('Your password has been reset. Please sign in.'));
        exit;
    }

    /** Resolve a (token, uid) pair to a user row, or null if invalid/expired. */
    private function findResetUser($token, $uid) {
        if (!is_string($token) || strlen($token) !== 64 || !ctype_xdigit($token) || !ctype_digit((string) $uid)) {
            return null;
        }
        $user = Database::fetchOne(
            "SELECT id, password_reset_token, password_reset_expires
               FROM users WHERE id = ? AND is_active = 1",
            [$uid]
        );
        if (!$user || empty($user['password_reset_token']) || empty($user['password_reset_expires'])) {
            return null;
        }
        if (strtotime($user['password_reset_expires']) < time()) {
            return null;
        }
        if (!hash_equals($user['password_reset_token'], hash('sha256', $token))) {
            return null;
        }
        return $user;
    }
}