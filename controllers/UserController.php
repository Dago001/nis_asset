<?php
/**
 * User Management Controller
 */
class UserController extends Controller {

    /**
     * Roles that only a Super Admin Officer may grant, revoke, or otherwise
     * touch on another account. The 'admin' role is a full user-management
     * role in its own right — it may assign/edit any role except this one.
     */
    private const PRIVILEGED_ROLES = ['Super Admin Officer'];

    /**
     * Filter a submitted list of role IDs down to those the current actor is
     * actually allowed to assign. Unknown IDs are dropped; privileged roles are
     * only kept for a Super Admin. Returns [validRoleIds, blockedNames].
     */
    private function sanitizeRoleAssignment($submittedRoleIds) {
        $submitted = array_values(array_unique(array_map('intval', (array) $submittedRoleIds)));
        if (empty($submitted)) {
            return [[], []];
        }

        $placeholders = implode(',', array_fill(0, count($submitted), '?'));
        $rows = Database::fetchAll(
            "SELECT id, role_name FROM roles WHERE id IN ($placeholders)",
            $submitted
        ) ?: [];

        $allowed = [];
        $blocked = [];
        $isSuper = Auth::isSuperAdmin();
        foreach ($rows as $r) {
            if (!$isSuper && in_array($r['role_name'], self::PRIVILEGED_ROLES, true)) {
                $blocked[] = $r['role_name'];
                continue;
            }
            $allowed[] = (int) $r['id'];
        }
        return [$allowed, $blocked];
    }

    /** True if the given user currently holds any PRIVILEGED_ROLES role or is literal ADMIN. */
    private function userHasPrivilegedRole($userId) {
        $user = Database::fetchOne("SELECT username FROM users WHERE id = ?", [$userId]);
        if ($user && $user['username'] === 'ADMIN') {
            return true;
        }
        $rows = Database::fetchAll(
            "SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.id
              WHERE ur.user_id = ? AND r.role_name IN ('" . implode("','", self::PRIVILEGED_ROLES) . "')",
            [$userId]
        ) ?: [];
        return !empty($rows);
    }


    /**
     * List all users
     */
    public function index() {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage users']);
            return;
        }
        
        $whereSql = "";
        if (!Auth::isSuperAdmin()) {
            $whereSql = " WHERE u.id NOT IN (
                SELECT ur.user_id FROM user_roles ur 
                JOIN roles r ON ur.role_id = r.id 
                WHERE r.role_name IN ('" . implode("','", self::PRIVILEGED_ROLES) . "')
            ) AND u.username != 'ADMIN' ";
        }

        $users = Database::fetchAll(
            "SELECT u.*, 
                    GROUP_CONCAT(r.role_name SEPARATOR ', ') as role_names,
                    c.command_name,
                    z.zone_name
             FROM users u
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             LEFT JOIN commands c ON u.command_id = c.id
             LEFT JOIN zones z ON c.zone_id = z.id
             {$whereSql}
             GROUP BY u.id
             ORDER BY u.created_at DESC"
        );
        
        if ($users === false) $users = [];
        
        $this->view('users/index', ['users' => $users]);
    }
    
    /**
     * Show create user form
     */
    public function create() {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create users']);
            return;
        }

        Auth::ensureRequiredRolesExist();

        // views/users/create.php fetches its own $roles/$zones independently
        // of what's passed here (and applies its own Super-Admin role filter
        // to match) — nothing to pass through, the view is self-sufficient.
        $this->view('users/create', []);
    }
    
    /**
     * Show edit user form
     */
    public function edit($id) {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit users']);
            return;
        }
        
        if (!Auth::isSuperAdmin() && $this->userHasPrivilegedRole($id)) {
            $this->redirect('users', ['error' => 'You do not have permission to edit a Super Administrator account.']);
            return;
        }

        Auth::ensureRequiredRolesExist();
        
        // Get user details
        $user = Database::fetchOne(
            "SELECT u.*, 
                    c.command_name,
                    z.zone_name
             FROM users u
             LEFT JOIN commands c ON u.command_id = c.id
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE u.id = ?",
            [$id]
        );
        
        if (!$user) {
            $this->redirect('users', ['error' => 'User not found']);
            return;
        }

        // views/users/edit.php fetches roles/zones/userRoleIds itself (and
        // applies its own Super-Admin role filter to match create.php) — only
        // $user is genuinely required from here.
        $this->view('users/edit', [
            'user' => $user
        ]);
    }
    
    /**
     * Store new user
     */
    public function store() {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create users']);
            return;
        }
        
        // Validate CSRF token
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('users/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        // New accounts are identified by service number only (4-5 digits) —
        // existing accounts predating this rule (e.g. named/legacy usernames)
        // are untouched here; this validation is create-only, see update().
        if (empty($_POST['username'])) {
            $errors['username'] = 'Service number is required';
        } elseif (!preg_match('/^\d{4,5}$/', $_POST['username'])) {
            $errors['username'] = 'Username must be a service number of 4 or 5 digits only';
        } else {
            $existing = Database::fetchOne("SELECT id FROM users WHERE username = ?", [$_POST['username']]);
            if ($existing) {
                $errors['username'] = 'Username already exists';
            }
        }

        if (empty($_POST['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } else {
            $existing = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$_POST['email']]);
            if ($existing) {
                $errors['email'] = 'Email already exists';
            }
        }
        
        if (empty($_POST['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($_POST['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        if (empty($_POST['confirm_password'])) {
            $errors['confirm_password'] = 'Confirm password is required';
        } elseif ($_POST['password'] !== $_POST['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        if (empty($_POST['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        } elseif (strlen($_POST['full_name']) > 100) {
            $errors['full_name'] = 'Full name must not exceed 100 characters';
        } elseif (!preg_match("/^[a-zA-Z\s\-'.]+$/", $_POST['full_name'])) {
            $errors['full_name'] = "Full name must contain only letters, spaces, hyphens (-), apostrophes ('), and dots";
        }
        
        if (empty($_POST['nis_number'])) {
            $errors['nis_number'] = 'NIS number is required';
        } elseif (!isDigitsOnly($_POST['nis_number'])) {
            $errors['nis_number'] = 'NIS number must contain numbers only';
        } else {
            $existing = Database::fetchOne("SELECT id FROM users WHERE nis_number = ?", [$_POST['nis_number']]);
            if ($existing) {
                $errors['nis_number'] = 'NIS number already exists';
            }
        }

        if (empty($_POST['rank'])) {
            $errors['rank'] = 'Rank is required';
        }

        if (empty($_POST['command_id'])) {
            $errors['command_id'] = 'Command is required';
        }

        if (empty($_POST['phone'])) {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^\d{11}$/', $_POST['phone'])) {
            $errors['phone'] = 'Phone number must be exactly 11 digits';
        }
        
        if (!isset($_POST['roles']) || empty($_POST['roles'])) {
            $errors['roles'] = 'At least one role must be selected';
        }

        if ($pwError = Security::passwordPolicyError($_POST['password'] ?? '')) {
            $errors['password'] = $pwError;
        }

        [$roleIdsToAssign, $blockedRoles] = $this->sanitizeRoleAssignment($_POST['roles'] ?? []);
        if (!empty($blockedRoles)) {
            $errors['roles'] = 'You are not allowed to assign: ' . implode(', ', $blockedRoles);
        } elseif (empty($roleIdsToAssign)) {
            $errors['roles'] = 'Select at least one valid role';
        }

        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', array_diff_key($_POST, array_flip(['password', 'confirm_password'])));
            $this->redirect('users/create');
            return;
        }

        Database::beginTransaction();

        try {
            // Hash password
            $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $userId = Database::insert('users', [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'password_hash' => $passwordHash,
                // The admin — not the account holder — chose this password,
                // so force a change before it can be used for anything else.
                'must_change_password' => 1,
                'full_name' => $_POST['full_name'],
                'nis_number' => $_POST['nis_number'],
                'rank' => $_POST['rank'],
                'command_id' => $_POST['command_id'],
                'phone' => $_POST['phone'],
                'is_active' => 1,
                'created_by' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$userId) {
                throw new Exception("Failed to insert user");
            }
            
            // Assign roles (already validated / permission-filtered above)
            foreach ($roleIdsToAssign as $roleId) {
                if (!Database::insert('user_roles', [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => date('Y-m-d H:i:s')
                ])) {
                    throw new Exception("Failed to assign role");
                }
            }

            Database::commit();

            // Log audit (never log the plaintext password)
            if (class_exists('AuditLogger')) {
                AuditLogger::logCreate('users', $userId, array_diff_key($_POST, array_flip(['password', 'confirm_password', 'csrf_token'])));
            }
            
            Session::set('success', 'User created successfully!');
            $this->redirect('users');
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("User creation error: " . $e->getMessage());
            Session::set('error', 'Could not create the user. Please check the details and try again.');
            $this->redirect('users/create');
        }
    }
    
    /**
     * Update user
     */
    public function update($id) {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit users']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("users/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $oldData = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        
        if (!$oldData) {
            $this->redirect('users', ['error' => 'User not found']);
            return;
        }
        
        // Validation logic
        $errors = [];
        
        if (empty($_POST['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($_POST['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (strlen($_POST['username']) > 50) {
            $errors['username'] = 'Username must not exceed 50 characters';
        } elseif ($_POST['username'] !== $oldData['username']) {
            $existing = Database::fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$_POST['username'], $id]);
            if ($existing) {
                $errors['username'] = 'Username already exists';
            }
        }
        
        if (empty($_POST['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } elseif ($_POST['email'] !== $oldData['email']) {
            $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$_POST['email'], $id]);
            if ($existing) {
                $errors['email'] = 'Email already exists';
            }
        }
        
        if (empty($_POST['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        } elseif (strlen($_POST['full_name']) > 100) {
            $errors['full_name'] = 'Full name must not exceed 100 characters';
        } elseif (!preg_match("/^[a-zA-Z\s\-'.]+$/", $_POST['full_name'])) {
            $errors['full_name'] = "Full name must contain only letters, spaces, hyphens (-), apostrophes ('), and dots";
        }
        
        if (empty($_POST['nis_number'])) {
            $errors['nis_number'] = 'NIS number is required';
        } elseif (!isDigitsOnly($_POST['nis_number'])) {
            $errors['nis_number'] = 'NIS number must contain numbers only';
        } elseif ($_POST['nis_number'] !== $oldData['nis_number']) {
            $existing = Database::fetchOne("SELECT id FROM users WHERE nis_number = ? AND id != ?", [$_POST['nis_number'], $id]);
            if ($existing) {
                $errors['nis_number'] = 'NIS number already exists';
            }
        }

        if (empty($_POST['rank'])) {
            $errors['rank'] = 'Rank is required';
        }

        if (empty($_POST['command_id'])) {
            $errors['command_id'] = 'Command is required';
        }

        if (empty($_POST['phone'])) {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^\d{11}$/', $_POST['phone'])) {
            $errors['phone'] = 'Phone number must be exactly 11 digits';
        }
        
        if (!empty($_POST['password'])) {
            if ($pwError = Security::passwordPolicyError($_POST['password'])) {
                $errors['password'] = $pwError;
            }
            if (empty($_POST['confirm_password'])) {
                $errors['confirm_password'] = 'Confirm password is required';
            } elseif ($_POST['password'] !== $_POST['confirm_password']) {
                $errors['confirm_password'] = 'Passwords do not match';
            }
        }

        [$roleIdsToAssign, $blockedRoles] = $this->sanitizeRoleAssignment($_POST['roles'] ?? []);
        if (!empty($blockedRoles)) {
            $errors['roles'] = 'You are not allowed to assign: ' . implode(', ', $blockedRoles);
        } elseif (empty($roleIdsToAssign)) {
            $errors['roles'] = 'Select at least one valid role';
        }

        // A non-Super-Admin must not be able to strip privileged roles from an
        // account that currently has them (privilege tampering).
        if (!Auth::isSuperAdmin() && $this->userHasPrivilegedRole($id)) {
            $errors['roles'] = 'Only a Super Admin Officer can modify the roles of this account.';
        }

        // Location restriction (geofencing) — a point picked on the map plus
        // a radius in meters. Only required when the admin actually turned
        // it on; a Super Admin's own account can have it set (for
        // consistency) but AuthController::login() always exempts them, so
        // there's no lockout risk from a mistake here.
        $geofenceEnabled = isset($_POST['geofence_enabled']) ? 1 : 0;
        $geofenceLat = null;
        $geofenceLng = null;
        $geofenceRadius = null;
        if ($geofenceEnabled) {
            $lat = $_POST['geofence_lat'] ?? '';
            $lng = $_POST['geofence_lng'] ?? '';
            $radius = $_POST['geofence_radius_m'] ?? '';
            if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
                $errors['geofence'] = 'Click a location on the map to set the allowed point.';
            } elseif ((float) $lat < -90 || (float) $lat > 90 || (float) $lng < -180 || (float) $lng > 180) {
                $errors['geofence'] = 'That location is not a valid coordinate.';
            } elseif (!is_numeric($radius) || (int) $radius < 10 || (int) $radius > 50000) {
                $errors['geofence'] = 'Radius must be between 10 and 50,000 meters.';
            } else {
                $geofenceLat = (float) $lat;
                $geofenceLng = (float) $lng;
                $geofenceRadius = (int) $radius;
            }
        }

        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect("users/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $updateData = [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'full_name' => $_POST['full_name'],
                'nis_number' => $_POST['nis_number'],
                'rank' => $_POST['rank'],
                'command_id' => $_POST['command_id'],
                'phone' => $_POST['phone'],
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'geofence_enabled' => $geofenceEnabled,
                'geofence_lat' => $geofenceLat,
                'geofence_lng' => $geofenceLng,
                'geofence_radius_m' => $geofenceRadius,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!empty($_POST['password'])) {
                $updateData['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                // Someone other than the account holder just set this password
                // (unless they're resetting their own account) — force a change
                // on next login rather than leaving them on a password only the
                // admin knows.
                if ((int) $id !== (int) Auth::id()) {
                    $updateData['must_change_password'] = 1;
                }
            }

            Database::update('users', $updateData, 'id = ?', [$id]);
            
            // Update roles (already validated / permission-filtered above)
            Database::delete('user_roles', 'user_id = ?', [$id]);

            foreach ($roleIdsToAssign as $roleId) {
                Database::insert('user_roles', [
                    'user_id' => $id,
                    'role_id' => $roleId,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => date('Y-m-d H:i:s')
                ]);
            }

            Database::commit();

            // Refresh active session if the edited user is currently logged in
            if ((int) $id === (int) Auth::id()) {
                $_SESSION['full_name'] = $_POST['full_name'];
                $_SESSION['rank'] = $_POST['rank'];
                $_SESSION['command_id'] = $_POST['command_id'];
                $cmd = Database::fetchOne("SELECT command_name FROM commands WHERE id = ?", [$_POST['command_id']]);
                if ($cmd) {
                    $_SESSION['command_name'] = $cmd['command_name'];
                }
                $newRoles = Database::fetchAll("SELECT r.role_name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?", [$id]);
                if ($newRoles) {
                    $_SESSION['roles'] = array_column($newRoles, 'role_name');
                }
            }

            if (class_exists('AuditLogger')) {
                AuditLogger::logUpdate('users', $id, $oldData, $updateData);
            }

            Session::set('success', 'User updated successfully!');
            $this->redirect('users');

        } catch (Exception $e) {
            Database::rollBack();
            error_log("User update error: " . $e->getMessage());
            Session::set('error', 'Could not update the user. Please try again.');
            $this->redirect("users/edit/$id");
        }
    }
    
    /**
     * Toggle user active status
     */
    public function toggleStatus($id) {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to change user status']);
            return;
        }
        
        if ($id == Auth::id()) {
            Session::set('error', 'You cannot change your own status');
            $this->redirect('users');
            return;
        }
        
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$user) {
            Session::set('error', 'User not found');
            $this->redirect('users');
            return;
        }

        if (!Auth::isSuperAdmin() && $this->userHasPrivilegedRole($id)) {
            Session::set('error', 'Only a Super Admin Officer can change the status of this account.');
            $this->redirect('users');
            return;
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $statusText = $newStatus ? 'activated' : 'deactivated';

        Database::update('users', ['is_active' => $newStatus], 'id = ?', [$id]);

        if (class_exists('AuditLogger')) {
            AuditLogger::log('STATUS_CHANGE', 'users', $id, null,
                "User status changed to " . ($newStatus ? 'active' : 'inactive'));
        }

        Session::set('success', 'User "' . $user['username'] . '" has been ' . $statusText . ' successfully.');
        $this->redirect('users');
    }

    /**
     * Generate a cryptographically random password an admin can read once and
     * hand to a user — same excluded-ambiguous-character charset as the
     * client-side generator on the Create User form (no 0/O, 1/l/I).
     */
    private function generateRandomPassword($length = 16) {
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digits = '23456789';
        $special = '!@#$%^&*()-_=+?';
        $all = $lower . $upper . $digits . $special;

        $pick = function ($charset) {
            return $charset[random_int(0, strlen($charset) - 1)];
        };

        $chars = [$pick($lower), $pick($upper), $pick($digits), $pick($special)];
        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $pick($all);
        }
        // Fisher-Yates shuffle so the required chars aren't always up front.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
        return implode('', $chars);
    }

    /**
     * Admin-triggered password reset — generates a new temporary password,
     * forces a change on next login (same must_change_password contract as a
     * freshly-created account or an admin-set password via the Edit form),
     * and clears any active lockout so this doubles as an unlock action.
     * The new password is shown to the admin exactly once via a one-shot
     * session flash, never logged or persisted anywhere in the clear.
     */
    public function resetPassword($id) {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to reset passwords']);
            return;
        }

        if ($id == Auth::id()) {
            Session::set('error', 'Use "Change Password" on your own profile instead.');
            $this->redirect('users');
            return;
        }

        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$user) {
            Session::set('error', 'User not found');
            $this->redirect('users');
            return;
        }

        if (!Auth::isSuperAdmin() && $this->userHasPrivilegedRole($id)) {
            Session::set('error', 'Only a Super Admin Officer can reset the password of this account.');
            $this->redirect('users');
            return;
        }

        $newPassword = $this->generateRandomPassword();

        Database::update('users', [
            'password_hash'       => password_hash($newPassword, PASSWORD_DEFAULT),
            'must_change_password' => 1,
            'login_attempts'       => 0,
            'lockout_until'        => null,
            'updated_at'           => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        if (class_exists('AuditLogger')) {
            AuditLogger::log('PASSWORD_RESET', 'users', $id, null,
                'Password reset by admin for user: ' . $user['username']);
        }

        Session::set('generated_password', $newPassword);
        Session::set('generated_password_for', $user['username']);
        Session::set('success', 'Password reset for "' . $user['username'] . '". Copy the new password shown below now — it will not be shown again.');
        $this->redirect('users');
    }

    /**
     * Admin-triggered Google Authenticator reset — for a user who lost their
     * device/authenticator and is locked out of the 2FA step. Clears their
     * enrolment the same way the user's own "Disable 2FA" on the profile
     * page does (see AuthController::disable2FA()); they log in with just
     * their password afterward and can re-enrol from their profile once
     * they have a working authenticator again.
     */
    public function reset2fa($id) {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to reset two-factor authentication']);
            return;
        }

        if ($id == Auth::id()) {
            Session::set('error', 'Use "Disable 2FA" on your own profile instead.');
            $this->redirect('users');
            return;
        }

        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$user) {
            Session::set('error', 'User not found');
            $this->redirect('users');
            return;
        }

        if (!Auth::isSuperAdmin() && $this->userHasPrivilegedRole($id)) {
            Session::set('error', 'Only a Super Admin Officer can reset two-factor authentication for this account.');
            $this->redirect('users');
            return;
        }

        if (empty($user['two_factor_enabled'])) {
            Session::set('error', 'User "' . $user['username'] . '" does not have two-factor authentication enabled.');
            $this->redirect('users');
            return;
        }

        Database::update('users', [
            'two_factor_secret'     => null,
            'two_factor_enabled'    => 0,
            'two_factor_last_slice' => 0,
            'updated_at'            => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        if (class_exists('AuditLogger')) {
            AuditLogger::log('2FA_RESET', 'users', $id, null,
                'Google Authenticator reset by admin for user: ' . $user['username']);
        }

        Session::set('success', 'Google Authenticator has been reset for "' . $user['username'] . '". They can log in with just their password and re-enrol from their profile.');
        $this->redirect('users');
    }

    /**
     * Permanently delete a user account.
     *
     * Every FK referencing users.id already resolves safely (SET NULL on
     * historical records like audit_logs/created_by fields, CASCADE on
     * genuinely per-user rows like user_roles/user_sessions/notifications),
     * so a real DELETE is safe at the database level — the guard here is
     * about who is allowed to remove a privileged account, not data integrity.
     */
    public function delete($id) {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete users']);
            return;
        }

        if ($id == Auth::id()) {
            Session::set('error', 'You cannot delete your own account');
            $this->redirect('users');
            return;
        }

        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$user) {
            Session::set('error', 'User not found');
            $this->redirect('users');
            return;
        }

        if (!Auth::isSuperAdmin() && $this->userHasPrivilegedRole($id)) {
            Session::set('error', 'Only a Super Admin Officer can delete this account.');
            $this->redirect('users');
            return;
        }

        if (class_exists('AuditLogger')) {
            AuditLogger::log('DELETE', 'users', $id, null,
                'User deleted: ' . $user['username'], $user, null);
        }

        Database::delete('users', 'id = ?', [$id]);

        Session::set('success', 'User "' . $user['username'] . '" has been deleted successfully.');
        $this->redirect('users');
    }
    
    /**
     * Show user details
     */
    public function show($id) {
        if (!Auth::can('users.manage')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view user details']);
            return;
        }

        if (!Auth::isSuperAdmin() && $this->userHasPrivilegedRole($id)) {
            $this->redirect('users', ['error' => 'You do not have permission to view a Super Administrator account.']);
            return;
        }
        
        $user = Database::fetchOne(
            "SELECT u.*, 
                    GROUP_CONCAT(r.role_name SEPARATOR ', ') as role_names,
                    c.command_name,
                    z.zone_name
             FROM users u
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             LEFT JOIN commands c ON u.command_id = c.id
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE u.id = ?
             GROUP BY u.id",
            [$id]
        );
        
        if (!$user) {
            $this->redirect('users', ['error' => 'User not found']);
            return;
        }
        
        $activities = [];
        if (class_exists('AuditLogger')) {
            $activities = AuditLogger::getLogs(['user_id' => $id], 20);
        }
        
        $this->view('users/show', [
            'user' => $user,
            'activities' => $activities
        ]);
    }
    
    /**
     * Profile page (for current user)
     */
    public function profile() {
        if (!Auth::check()) {
            $this->redirect('auth/login', ['error' => 'Please login to continue']);
            return;
        }
        
        $user = Database::fetchOne(
            "SELECT u.*, c.command_name, z.zone_name
             FROM users u
             LEFT JOIN commands c ON u.command_id = c.id
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE u.id = ?",
            [Auth::id()]
        );
        
        $this->view('users/profile', ['user' => $user]);
    }
    
    /**
     * Update profile (current user)
     */
    public function updateProfile() {
        if (!Auth::check()) {
            $this->redirect('auth/login', ['error' => 'Please login to continue']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('users/profile', ['error' => 'Invalid security token']);
            return;
        }
        
        $id = Auth::id();
        $oldData = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        
        if (!$oldData) {
            $this->redirect('users/profile', ['error' => 'User not found']);
            return;
        }
        
        $errors = [];
        
        if (empty($_POST['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        } elseif (strlen($_POST['full_name']) > 100) {
            $errors['full_name'] = 'Full name must not exceed 100 characters';
        } elseif (!preg_match("/^[a-zA-Z\s\-'.]+$/", $_POST['full_name'])) {
            $errors['full_name'] = "Full name must contain only letters, spaces, hyphens (-), apostrophes ('), and dots";
        }
        
        if (empty($_POST['phone'])) {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^\d{11}$/', $_POST['phone'])) {
            $errors['phone'] = 'Phone number must be exactly 11 digits';
        }

        if (empty($_POST['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } elseif ($_POST['email'] !== $oldData['email']) {
            $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$_POST['email'], $id]);
            if ($existing) {
                $errors['email'] = 'Email already exists';
            }
        }
        
        if (!empty($_POST['current_password']) || !empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                $errors['current_password'] = 'Current password is required';
            } elseif (!password_verify($_POST['current_password'], $oldData['password_hash'])) {
                $errors['current_password'] = 'Current password is incorrect';
            }
            
            if (!empty($_POST['new_password'])) {
                if (strlen($_POST['new_password']) < 8) {
                    $errors['new_password'] = 'New password must be at least 8 characters';
                }
                if (empty($_POST['confirm_password'])) {
                    $errors['confirm_password'] = 'Please confirm your new password';
                } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
                    $errors['confirm_password'] = 'New passwords do not match';
                }
            }
        }
        
        // Handle avatar upload / removal
        $baseDir = defined('BASE_PATH') ? BASE_PATH : (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__));
        $avatarPath = $oldData['profile_image'] ?? null;
        if (!empty($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
            if ($avatarPath && file_exists($baseDir . '/' . $avatarPath)) {
                @unlink($baseDir . '/' . $avatarPath);
            }
            $avatarPath = null;
        }

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($ext, $allowedExts)) {
                $errors['avatar'] = 'Allowed image formats: JPG, JPEG, PNG, WEBP, GIF';
            } elseif ($file['size'] > $maxSize) {
                $errors['avatar'] = 'Image size must be less than 5MB';
            } else {
                $uploadDir = $baseDir . '/assets/uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'avatar_' . $id . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    // Remove old avatar file if different
                    if ($avatarPath && file_exists($baseDir . '/' . $avatarPath) && $baseDir . '/' . $avatarPath !== $targetFile) {
                        @unlink($baseDir . '/' . $avatarPath);
                    }
                    $avatarPath = 'assets/uploads/avatars/' . $newFileName;
                } else {
                    $errors['avatar'] = 'Failed to save uploaded image. Please try again.';
                }
            }
        }

        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect('users/profile');
            return;
        }
        
        $updateData = [
            'full_name' => $_POST['full_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'profile_image' => $avatarPath,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($_POST['new_password'])) {
            $updateData['password_hash'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }
        
        Database::update('users', $updateData, 'id = ?', [$id]);
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logUpdate('users', $id, $oldData, $updateData);
        }
        
        Session::set('full_name', $_POST['full_name']);
        Session::set('email', $_POST['email']);
        Session::set('profile_image', $avatarPath);
        
        Session::set('success', 'Your profile and avatar have been updated successfully!');
        $this->redirect('users/profile');
    }
    
    /**
     * Export users to CSV
     */
    public function export() {
        if (!Auth::can('users.export') && !Auth::can('reports.export') && !Auth::can('users.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to export user data']);
            return;
        }

        $sql = "SELECT u.*, r.role_name, c.command_name, z.zone_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN commands c ON u.command_id = c.id
                LEFT JOIN zones z ON c.zone_id = z.id
                ORDER BY u.full_name ASC";
        
        $users = Database::fetchAll($sql);
        if ($users === false) $users = [];

        $filename = 'users_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        Security::fputcsv($output, [
            'ID', 'Service Number', 'Full Name', 'Rank', 'Username', 'Email',
            'Phone', 'Role', 'Zone', 'Command', 'Status', 'Last Login', 'Created At'
        ]);

        foreach ($users as $user) {
            Security::fputcsv($output, [
                $user['id'] ?? '',
                $user['service_number'] ?? '',
                $user['full_name'] ?? '',
                $user['rank'] ?? '',
                $user['username'] ?? '',
                $user['email'] ?? '',
                $user['phone'] ?? '',
                $user['role_name'] ?? '',
                $user['zone_name'] ?? '',
                $user['command_name'] ?? '',
                $user['status'] ?? '',
                $user['last_login_at'] ?? '',
                $user['created_at'] ?? ''
            ]);
        }

        fclose($output);
        AuditLogger::logExport('users', 'csv');
        exit;
    }

    /**
     * API: Get Formations by Zone
     */
    public function apiGetFormationsByZone() {
        $zoneId = isset($_GET['zone_id']) ? (int)$_GET['zone_id'] : 0;
        if (!$zoneId) {
            $this->json(['success' => false, 'commands' => []]);
            return;
        }
        $commands = Database::fetchAll("SELECT id, command_name FROM commands WHERE zone_id = ? ORDER BY command_name", [$zoneId]) ?: [];
        $this->json(['success' => true, 'commands' => $commands]);
    }

    /**
     * API: Check if username / service number already exists
     */
    public function checkUsername() {
        if (!Auth::check()) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
            return;
        }

        $username = trim($_GET['username'] ?? '');
        $excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;

        if ($username === '') {
            $this->json(['success' => true, 'exists' => false, 'message' => '']);
            return;
        }

        if ($excludeId > 0) {
            $existing = Database::fetchOne(
                "SELECT id, username, full_name, rank FROM users WHERE (username = ? OR nis_number = ?) AND id != ?",
                [$username, $username, $excludeId]
            );
        } else {
            $existing = Database::fetchOne(
                "SELECT id, username, full_name, rank FROM users WHERE username = ? OR nis_number = ?",
                [$username, $username]
            );
        }

        if ($existing) {
            $officerInfo = !empty($existing['full_name']) ? ' (' . $existing['full_name'] . ')' : '';
            $this->json([
                'success' => true,
                'exists' => true,
                'message' => 'Service Number already exists in the system' . $officerInfo,
                'user' => [
                    'username' => $existing['username'],
                    'full_name' => $existing['full_name'] ?? '',
                    'rank' => $existing['rank'] ?? ''
                ]
            ]);
        } else {
            $this->json([
                'success' => true,
                'exists' => false,
                'message' => 'Service Number is available'
            ]);
        }
    }
}
