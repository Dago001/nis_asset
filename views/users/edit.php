<?php
$title = 'Edit User';
$active = 'users';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Check if user exists
if (!isset($user) || empty($user)) {
    $_SESSION['error'] = 'User not found';
    header('Location: ' . BASE_URL . '/users');
    exit;
}

$errors = Session::get('errors', []);
Session::remove('errors');
$old = Session::get('old', []);
Session::remove('old');

// Get user's current role IDs (or old input if validation failed)
$userRoleIds = [];
if (!empty($old['roles'])) {
    $userRoleIds = array_map('intval', (array) $old['roles']);
} elseif (!empty($user['id'])) {
    $userRoles = Database::fetchAll("SELECT role_id FROM user_roles WHERE user_id = ?", [$user['id']]);
    if ($userRoles) {
        $userRoleIds = array_column($userRoles, 'role_id');
    }
}

// NIS Ranks in hierarchical order
$nisRanks = [
    'CGIS' => 'Comptroller General of Immigration Service',
    'DCG' => 'Deputy Comptroller General',
    'ACG' => 'Assistant Comptroller General',
    'CIS' => 'Comptroller of Immigration Service',
    'DCI' => 'Deputy Comptroller of Immigration',
    'ACI' => 'Assistant Comptroller of Immigration',
    'CSI' => 'Chief Superintendent of Immigration',
    'SI' => 'Superintendent of Immigration',
    'DSI' => 'Deputy Superintendent of Immigration',
    'ASI-1' => 'Assistant Superintendent of Immigration I',
    'ASI-2' => 'Assistant Superintendent of Immigration II',
    'II' => 'Inspector of Immigration',
    'AII' => 'Assistant Inspector of Immigration',
    'IA1' => 'Immigration Assistant I',
    'IA2' => 'Immigration Assistant II',
    'IA3' => 'Immigration Assistant III'
];

// Get all zones for dropdown
$zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
if ($zones === false) $zones = [];

// Get all roles
$roles = Database::fetchAll("SELECT * FROM roles ORDER BY role_name");
if ($roles === false) $roles = [];

// A non-Super-Admin must not see Super Admin as an assignable option
if (!Auth::isSuperAdmin()) {
    $roles = array_values(array_filter($roles, function ($r) {
        $name = strtolower(trim($r['role_name'] ?? ''));
        return strpos($name, 'super admin') === false && strpos($name, 'superadmin') === false;
    }));
}

// Get user's current zone and command (with old input fallback)
$currentCommandId = $old['command_id'] ?? ($user['command_id'] ?? null);
$currentZoneId = $old['zone_id'] ?? null;
if (empty($currentZoneId) && !empty($currentCommandId)) {
    $command = Database::fetchOne("SELECT zone_id FROM commands WHERE id = ?", [$currentCommandId]);
    $currentZoneId = $command ? $command['zone_id'] : null;
}

$commandsForZone = [];
if (!empty($currentZoneId)) {
    $commandsForZone = Database::fetchAll("SELECT * FROM commands WHERE zone_id = ? ORDER BY command_name ASC", [$currentZoneId]) ?: [];
}

// Generate CSRF token using Security class
$csrfToken = Security::csrfToken();
?>

<div class="container-fluid edit-user-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-user-pen"></i>
                Edit User: <?php echo htmlspecialchars($user['username'] ?? ''); ?>
            </h1>
            <p>Update officer credentials, profile details, deployments, and security restrictions</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/users/show/<?php echo $user['id'] ?? ''; ?>" class="btn btn-outline">
                <i class="fas fa-eye"></i> View Profile
            </a>
            <a href="<?php echo BASE_URL; ?>/users" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    <!-- Display any errors -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px; border-radius: 8px; padding: 14px 18px;">
        <h4 style="margin: 0 0 8px 0; font-size: 0.95rem; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</h4>
        <ul style="margin: 0; padding-left: 20px; font-size: 0.88rem;">
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars(is_array($error) ? implode(', ', $error) : $error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- User Form Container -->
    <div class="form-container-card">
        <form method="POST" action="<?php echo BASE_URL; ?>/users/update/<?php echo $user['id'] ?? ''; ?>" id="userForm" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <!-- Section 1: Account Credentials -->
            <div class="form-card-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-user-lock"></i>
                    </div>
                    <div class="section-header-text">
                        <h3>Account Credentials</h3>
                        <p>Login identity and authentication security settings</p>
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="username" class="required">Username (Service Number)</label>
                        <div class="input-with-prefix">
                            <i class="fas fa-id-card"></i>
                            <input type="text" name="username" id="username"
                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                                   required minlength="4" maxlength="5" inputmode="numeric" pattern="\d{4,5}"
                                   autocomplete="off" class="form-control <?php echo isset($errors['username']) ? 'error' : ''; ?>">
                        </div>
                        <div id="usernameFeedback" class="username-feedback" style="margin-top: 4px; font-size: 0.82rem; min-height: 18px;">
                            <?php if (isset($errors['username'])): ?>
                                <small class="error-text"><?php echo htmlspecialchars($errors['username']); ?></small>
                            <?php endif; ?>
                        </div>
                        <small class="form-hint">Service number only — 4 or 5 digits without letters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="required">Official Email Address</label>
                        <div class="input-with-prefix">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email"
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                   required maxlength="100" autocomplete="off"
                                   class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>">
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['email']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="password">New Password (leave blank to keep current)</label>
                        <div class="input-with-prefix input-with-toggle">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password"
                                   minlength="8" autocomplete="new-password" placeholder="••••••••"
                                   class="form-control <?php echo isset($errors['password']) ? 'error' : ''; ?>">
                            <button type="button" class="password-toggle" data-target="password" title="Toggle visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['password']); ?></small>
                        <?php endif; ?>
                        <div class="strength-meter-box">
                            <div class="strength-bar"><div class="strength-bar-fill" id="strengthBarFill"></div></div>
                            <small class="strength-text" id="passwordStrength">Password Strength: Keep current password</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-with-prefix input-with-toggle">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   autocomplete="new-password" placeholder="••••••••"
                                   class="form-control <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>">
                            <button type="button" class="password-toggle" data-target="confirm_password" title="Toggle visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['confirm_password']); ?></small>
                        <?php endif; ?>
                        <small id="passwordMatch" class="form-hint"></small>
                    </div>
                </div>

                <div class="active-status-box">
                    <label class="checkbox-container">
                        <input type="checkbox" name="is_active" value="1" <?php echo ($user['is_active'] ?? 0) ? 'checked' : ''; ?>>
                        <span class="custom-check-box"></span>
                        <span class="status-toggle-label">
                            <strong>Account Active Status</strong>
                            <small class="form-hint" style="display: block; margin-top: 2px;">Inactive accounts are immediately blocked from logging into the portal.</small>
                        </span>
                    </label>
                </div>
            </div>
            
            <!-- Section 2: Officer Profile -->
            <div class="form-card-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-id-card-clip"></i>
                    </div>
                    <div class="section-header-text">
                        <h3>Officer Profile Details</h3>
                        <p>Personal identification and service rank information</p>
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="full_name" class="required">Full Name</label>
                        <div class="input-with-prefix">
                            <i class="fas fa-user"></i>
                            <input type="text" name="full_name" id="full_name"
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                   required maxlength="100" pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                                   class="form-control <?php echo isset($errors['full_name']) ? 'error' : ''; ?>">
                        </div>
                        <?php if (isset($errors['full_name'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['full_name']); ?></small>
                        <?php endif; ?>
                        <small class="form-hint"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="nis_number" class="required">NIS Service Number</label>
                        <div class="input-with-prefix">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" name="nis_number" id="nis_number"
                                   value="<?php echo htmlspecialchars($user['nis_number'] ?? ''); ?>"
                                   required maxlength="20" inputmode="numeric" pattern="[0-9]+" title="Numbers only"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   class="form-control <?php echo isset($errors['nis_number']) ? 'error' : ''; ?>">
                        </div>
                        <?php if (isset($errors['nis_number'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['nis_number']); ?></small>
                        <?php endif; ?>
                        <small class="form-hint"></small>
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="rank" class="required">Rank</label>
                        <div class="input-with-prefix">
                            <i class="fas fa-award"></i>
                            <select name="rank" id="rank" required class="form-control <?php echo isset($errors['rank']) ? 'error' : ''; ?>">
                                <option value="">Select Rank</option>
                                <?php foreach ($nisRanks as $rankCode => $rankTitle): ?>
                                    <option value="<?php echo $rankCode; ?>" <?php echo ($user['rank'] ?? '') == $rankCode ? 'selected' : ''; ?>>
                                        <?php echo $rankCode . ' - ' . $rankTitle; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (isset($errors['rank'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['rank']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="required">Phone Number</label>
                        <div class="input-with-prefix">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" id="phone"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   required minlength="11" maxlength="11" inputmode="numeric" pattern="\d{11}" title="Phone number must be exactly 11 digits"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                                   class="form-control <?php echo isset($errors['phone']) ? 'error' : ''; ?>">
                        </div>
                        <?php if (isset($errors['phone'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['phone']); ?></small>
                        <?php endif; ?>
                        <small class="form-hint"></small>
                    </div>
                </div>
            </div>
            
            <!-- Section 3: Command Assignment -->
            <div class="form-card-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-building-shield"></i>
                    </div>
                    <div class="section-header-text">
                        <h3>Command & Formation Deployment</h3>
                        <p>Select the officer's administrative zone and state/special command</p>
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="zone_id" class="required">Zonal Command</label>
                        <div class="input-with-prefix">
                            <i class="fas fa-map-location-dot"></i>
                            <select name="zone_id" id="zone_id" required 
                                    class="form-control <?php echo isset($errors['zone_id']) ? 'error' : ''; ?>">
                                <option value="">Select Zone</option>
                                <?php foreach ($zones as $zone): ?>
                                <option value="<?php echo $zone['id']; ?>" 
                                        <?php echo ($currentZoneId == $zone['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($zone['zone_name'] . ' - ' . ($zone['zone_headquarters'] ?? '')); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (isset($errors['zone_id'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['zone_id']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="command_id" class="required">Command Formation</label>
                        <div class="input-with-prefix">
                            <i class="fas fa-building"></i>
                            <select name="command_id" id="command_id" required 
                                    class="form-control <?php echo isset($errors['command_id']) ? 'error' : ''; ?>">
                                <option value="">Select Command</option>
                                <?php foreach ($commandsForZone as $cmd): ?>
                                <option value="<?php echo $cmd['id']; ?>" 
                                        <?php echo ($currentCommandId == $cmd['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cmd['command_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (isset($errors['command_id'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['command_id']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Section 4: Role Assignment -->
            <div class="form-card-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="section-header-text">
                        <h3>Role & System Access Privileges</h3>
                        <p>Assign functional access roles determining permissions across assets and workflows</p>
                    </div>
                </div>
                
                <div class="role-cards-grid">
                    <?php foreach ($roles as $role): 
                        $isSuperAdmin = ($role['role_name'] === 'Super Admin Officer');
                        $isChecked = in_array($role['id'], $userRoleIds);
                    ?>
                    <label class="role-card-item <?php echo $isSuperAdmin ? 'role-card-super' : ''; ?>">
                        <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" 
                               <?php echo $isChecked ? 'checked' : ''; ?>>
                        <div class="role-card-content">
                            <div class="role-checkbox-box">
                                <span class="custom-check-box"></span>
                            </div>
                            <div class="role-card-info">
                                <div class="role-card-title">
                                    <strong><?php echo htmlspecialchars($role['role_name']); ?></strong>
                                    <?php if ($isSuperAdmin): ?>
                                        <span class="role-badge-super">Elevated</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($role['description']): ?>
                                    <p class="role-card-desc"><?php echo htmlspecialchars($role['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['roles'])): ?>
                    <small class="error-text" style="margin-top: 10px; display: block;"><?php echo htmlspecialchars($errors['roles']); ?></small>
                <?php endif; ?>
            </div>
            
            <!-- Section 5: Location Restriction (Geofencing) -->
            <div class="form-card-section">
                <div class="section-header">
                    <div class="section-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fas fa-location-crosshairs"></i>
                    </div>
                    <div class="section-header-text">
                        <h3>Location Restriction (Geofencing)</h3>
                        <p>Enforce physical location boundaries for login security</p>
                    </div>
                </div>

                <div class="geofence-toggle-box">
                    <label class="checkbox-container">
                        <input type="checkbox" name="geofence_enabled" id="geofenceEnabled" value="1" <?php echo !empty($user['geofence_enabled']) ? 'checked' : ''; ?>>
                        <span class="custom-check-box"></span>
                        <span class="status-toggle-label">
                            <strong>Restrict this account to a specific physical location</strong>
                        </span>
                    </label>
                    <small class="form-hint" style="margin-top: 4px; display: block;">
                        When enabled, this officer can only log in while their device GPS reports coordinates within the radius boundary. Click the map to set the allowed coordinate center.
                    </small>
                </div>

                <div id="geofenceFields" style="<?php echo !empty($user['geofence_enabled']) ? '' : 'display:none;'; ?> margin-top: 16px;">
                    <div class="map-container-box">
                        <div id="geofenceMap" style="height: 320px; width: 100%; border-radius: 8px; border: 1px solid var(--border-color, #D7E3DC); margin-bottom: 10px;"></div>
                        <small class="form-hint" style="display: flex; align-items: center; gap: 6px; margin-bottom: 14px;">
                            <i class="fas fa-info-circle text-primary"></i> Click anywhere on the map to place the allowed center point.
                        </small>
                    </div>
                    
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label for="geofenceLatDisplay">Latitude</label>
                            <div class="input-with-prefix">
                                <i class="fas fa-map-pin"></i>
                                <input type="text" id="geofenceLatDisplay" readonly class="form-control" 
                                       value="<?php echo htmlspecialchars($user['geofence_lat'] ?? ''); ?>" placeholder="Click map">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="geofenceLngDisplay">Longitude</label>
                            <div class="input-with-prefix">
                                <i class="fas fa-map-pin"></i>
                                <input type="text" id="geofenceLngDisplay" readonly class="form-control" 
                                       value="<?php echo htmlspecialchars($user['geofence_lng'] ?? ''); ?>" placeholder="Click map">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="geofenceRadius">Allowed Radius (meters)</label>
                            <div class="input-with-prefix">
                                <i class="fas fa-ruler-combined"></i>
                                <input type="number" name="geofence_radius_m" id="geofenceRadius" min="10" max="50000" step="10" 
                                       class="form-control" value="<?php echo htmlspecialchars($user['geofence_radius_m'] ?? '500'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="geofence_lat" id="geofenceLat" value="<?php echo htmlspecialchars($user['geofence_lat'] ?? ''); ?>">
                    <input type="hidden" name="geofence_lng" id="geofenceLng" value="<?php echo htmlspecialchars($user['geofence_lng'] ?? ''); ?>">
                </div>
                
                <?php if (isset($errors['geofence'])): ?>
                    <small class="error-text" style="margin-top: 10px; display: block;"><?php echo htmlspecialchars($errors['geofence']); ?></small>
                <?php endif; ?>
            </div>
            
            <!-- Form Action Buttons -->
            <div class="form-actions-bar">
                <button type="submit" class="btn btn-success submit-btn" id="submitUserBtn">
                    <i class="fas fa-floppy-disk"></i> Update User Account
                </button>
                <a href="<?php echo BASE_URL; ?>/users" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Edit User Master Styling */
.edit-user-wrapper {
    padding-bottom: 35px;
}

/* Page Header - moderate title font-weight (no heavy bold) */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color, #D7E3DC);
}

.page-header .header-content h1,
.page-title {
    font-size: 1.5rem;
    font-weight: 600 !important; /* Disabled excessive boldness */
    color: var(--primary-color, #134617);
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-header .header-content p {
    font-size: 0.9rem;
    color: var(--text-secondary, #53665E);
    margin: 0;
}

/* Form Container Card */
.form-container-card {
    background: var(--surface, #ffffff);
    border-radius: 10px;
    border: 1px solid var(--border-color, #D7E3DC);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    overflow: hidden;
}

.form-card-section {
    padding: 24px;
    border-bottom: 1px solid var(--border-color, #D7E3DC);
}

.form-card-section:last-of-type {
    border-bottom: none;
}

/* Section Header */
.section-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}

.section-icon {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background: #e8f5e9;
    color: var(--primary-light, #207027);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.section-header-text h3 {
    font-size: 1.15rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
    margin: 0 0 3px 0;
}

.section-header-text p {
    font-size: 0.85rem;
    color: var(--text-secondary, #53665E);
    margin: 0;
}

/* Form Grids */
.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    margin-bottom: 16px;
}

.form-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
    margin-bottom: 6px;
}

.required::after {
    content: " *";
    color: #dc2626;
    font-weight: 700;
}

/* Input with icon prefix */
.input-with-prefix {
    position: relative;
    width: 100%;
}

.input-with-prefix > i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #8fa097;
    font-size: 0.9rem;
    pointer-events: none;
    z-index: 2;
}

.input-with-prefix input,
.input-with-prefix select {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 6px;
    font-size: 0.92rem;
    color: var(--text-primary, #212529);
    background: var(--surface, #ffffff);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.input-with-prefix input:focus,
.input-with-prefix select:focus {
    border-color: var(--primary-light, #207027);
    box-shadow: 0 0 0 3px rgba(32, 112, 39, 0.15);
}

.input-with-prefix input.error,
.input-with-prefix select.error {
    border-color: #dc2626;
    background-color: #fff8f8;
}

/* Password Toggle */
.input-with-toggle input {
    padding-right: 40px;
}

.password-toggle {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    color: #8fa097;
    cursor: pointer;
    font-size: 0.9rem;
    border-radius: 4px;
    transition: color 0.2s;
}

.password-toggle:hover {
    color: var(--primary-light, #207027);
}

/* Password Strength Meter */
.strength-meter-box {
    margin-top: 6px;
}

.strength-bar {
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 4px;
}

.strength-bar-fill {
    height: 100%;
    width: 0%;
    transition: width 0.3s ease, background 0.3s ease;
}

.strength-text {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-secondary, #53665E);
}

/* Active Status & Geofence Toggle Boxes */
.active-status-box,
.geofence-toggle-box {
    background: var(--light-bg, #F7FAF8);
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 8px;
    padding: 14px 16px;
    margin-top: 8px;
}

.checkbox-container {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    margin: 0;
    position: relative;
}

.checkbox-container input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.custom-check-box {
    width: 20px;
    height: 20px;
    border: 2px solid var(--border-color, #D7E3DC);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    transition: all 0.2s;
    margin-top: 1px;
    flex-shrink: 0;
}

.checkbox-container input[type="checkbox"]:checked + .custom-check-box {
    background: var(--primary-light, #207027);
    border-color: var(--primary-light, #207027);
}

.checkbox-container input[type="checkbox"]:checked + .custom-check-box::after {
    content: "✓";
    color: white;
    font-size: 12px;
    font-weight: 700;
}

.status-toggle-label strong {
    font-size: 0.92rem;
    color: var(--text-primary, #212529);
}

/* Role Cards Grid */
.role-cards-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.role-card-item {
    display: block;
    position: relative;
    cursor: pointer;
    margin: 0;
}

.role-card-item input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.role-card-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 8px;
    background: var(--surface, #ffffff);
    transition: all 0.2s;
}

.role-card-item:hover .role-card-content {
    border-color: var(--primary-light, #207027);
    background: var(--light-bg, #F7FAF8);
}

.role-card-item input[type="checkbox"]:checked + .role-card-content {
    border-color: var(--primary-light, #207027);
    background: #f0f8f2;
    box-shadow: 0 0 0 1px var(--primary-light, #207027);
}

.role-card-item input[type="checkbox"]:checked + .role-card-content .custom-check-box {
    background: var(--primary-light, #207027);
    border-color: var(--primary-light, #207027);
}

.role-card-item input[type="checkbox"]:checked + .role-card-content .custom-check-box::after {
    content: "✓";
    color: white;
    font-size: 12px;
    font-weight: 700;
}

.role-card-info {
    flex: 1;
}

.role-card-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 3px;
}

.role-card-title strong {
    font-size: 0.92rem;
    color: var(--text-primary, #212529);
}

.role-badge-super {
    background: #fee2e2;
    color: #b91c1c;
    font-size: 0.68rem;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 700;
    text-transform: uppercase;
}

.role-card-desc {
    font-size: 0.8rem;
    color: var(--text-secondary, #53665E);
    margin: 0;
    line-height: 1.35;
}

/* Form Actions Bar */
.form-actions-bar {
    padding: 18px 24px;
    background: var(--light-bg, #F7FAF8);
    border-top: 1px solid var(--border-color, #D7E3DC);
    display: flex;
    align-items: center;
    gap: 12px;
}

.submit-btn {
    min-width: 170px;
    padding: 10px 22px;
    font-size: 0.95rem;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
}

.btn-success {
    background: var(--primary-light, #207027);
    color: white;
}

.btn-success:hover {
    background: var(--primary-color, #134617);
    box-shadow: 0 3px 8px rgba(19, 70, 23, 0.25);
    transform: translateY(-1px);
    color: white;
}

.btn-secondary {
    background: #64748b;
    color: white;
}

.btn-secondary:hover {
    background: #475569;
    color: white;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-color, #D7E3DC);
    color: var(--text-secondary, #53665E);
}

.btn-outline:hover {
    background: #e2e8f0;
    color: var(--text-primary, #212529);
}

.error-text {
    color: #dc2626;
    font-size: 0.82rem;
    margin-top: 4px;
}

.form-hint {
    font-size: 0.8rem;
    color: var(--text-secondary, #53665E);
    margin-top: 4px;
}

/* Responsive */
@media (max-width: 820px) {
    .form-grid-2, .form-grid-3 {
        grid-template-columns: 1fr;
        gap: 14px;
    }
    .role-cards-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Define base URL for API calls
const baseUrl = '<?php echo BASE_URL; ?>';

function debug(message, data = null) {
    console.log(message, data);
}

document.addEventListener('DOMContentLoaded', function() {
    debug('Edit Page loaded');
    debug('Base URL:', baseUrl);
    
    // Password toggle
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        return Math.min(strength, 4);
    }

    const pwInput = document.getElementById('password');
    const meter = document.getElementById('passwordStrength');
    const meterFill = document.getElementById('strengthBarFill');

    if (pwInput && meter && meterFill) {
        pwInput.addEventListener('input', function() {
            if (!this.value) {
                meter.textContent = 'Password Strength: Keep current password';
                meter.style.color = '#53665E';
                meterFill.style.width = '0%';
                return;
            }
            const strength = checkPasswordStrength(this.value);
            const messages = ['Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
            const colors = ['#dc2626', '#d97706', '#ca8a04', '#16a34a', '#059669'];
            const widths = ['20%', '40%', '65%', '85%', '100%'];
            
            meter.textContent = 'Password Strength: ' + messages[strength];
            meter.style.color = colors[strength];
            meterFill.style.width = widths[strength];
            meterFill.style.background = colors[strength];
        });
    }

    // Password match validation
    const confirmInput = document.getElementById('confirm_password');
    const match = document.getElementById('passwordMatch');
    if (confirmInput && match) {
        confirmInput.addEventListener('input', function() {
            const newPass = document.getElementById('password').value;
            if (!this.value && !newPass) {
                match.innerHTML = '';
                return;
            }
            if (this.value === newPass) {
                match.innerHTML = '<span style="color: #16a34a; font-weight: 600;"><i class="fas fa-check-circle"></i> Passwords match</span>';
                this.setCustomValidity('');
            } else {
                match.innerHTML = '<span style="color: #dc2626; font-weight: 600;"><i class="fas fa-times-circle"></i> Passwords do not match</span>';
                this.setCustomValidity('Passwords do not match');
            }
        });
    }

    // Username validation & real-time existence checking
    const usernameInput = document.getElementById('username');
    const nisNumberInput = document.getElementById('nis_number');
    const usernameFeedback = document.getElementById('usernameFeedback');
    const currentUserId = '<?php echo $user['id'] ?? 0; ?>';
    let usernameCheckTimeout = null;
    let isUsernameTaken = false;

    function checkUsernameAvailability(usernameVal) {
        if (!usernameVal || usernameVal.length < 4) {
            if (usernameFeedback) usernameFeedback.innerHTML = '';
            if (usernameInput) {
                usernameInput.classList.remove('error');
                usernameInput.setCustomValidity('');
            }
            isUsernameTaken = false;
            return;
        }

        if (usernameFeedback) {
            usernameFeedback.innerHTML = '<span style="color: #64748b; font-size: 0.82rem;"><i class="fas fa-spinner fa-spin"></i> Checking system records...</span>';
        }

        const apiUrl = baseUrl.replace(/\/$/, '') + '/users/check-username?username=' + encodeURIComponent(usernameVal) + '&exclude_id=' + currentUserId;
        
        fetch(apiUrl)
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    isUsernameTaken = true;
                    if (usernameFeedback) {
                        usernameFeedback.innerHTML = `<span style="color: #dc2626; font-size: 0.82rem; font-weight: 600;"><i class="fas fa-times-circle"></i> ${data.message}</span>`;
                    }
                    if (usernameInput) {
                        usernameInput.classList.add('error');
                        usernameInput.setCustomValidity('Service number already exists in the system');
                    }
                } else {
                    isUsernameTaken = false;
                    if (usernameFeedback) {
                        usernameFeedback.innerHTML = `<span style="color: #16a34a; font-size: 0.82rem; font-weight: 600;"><i class="fas fa-check-circle"></i> ${data.message}</span>`;
                    }
                    if (usernameInput) {
                        usernameInput.classList.remove('error');
                        usernameInput.setCustomValidity('');
                    }
                }
            })
            .catch(err => {
                console.error('Username check error:', err);
                if (usernameFeedback) usernameFeedback.innerHTML = '';
            });
    }

    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 5);
            if (nisNumberInput && (!nisNumberInput.value || nisNumberInput.value === '')) {
                nisNumberInput.value = this.value;
            }

            clearTimeout(usernameCheckTimeout);
            const val = this.value.trim();
            if (val.length >= 4) {
                usernameCheckTimeout = setTimeout(() => {
                    checkUsernameAvailability(val);
                }, 300);
            } else {
                if (usernameFeedback) usernameFeedback.innerHTML = '';
                this.classList.remove('error');
                this.setCustomValidity('');
                isUsernameTaken = false;
            }
        });

        usernameInput.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val.length >= 4) {
                checkUsernameAvailability(val);
            }
        });
    }

    if (nisNumberInput) {
        nisNumberInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 5);
        });
    }
    
    // Zone to Command dropdown cascade
    const zoneSelect = document.getElementById('zone_id');
    const commandSelect = document.getElementById('command_id');
    const currentCommandId = '<?php echo $user['command_id'] ?? ''; ?>';
    
    function loadCommands(zoneId, selectedCommandId = null) {
        if (!zoneId) {
            commandSelect.innerHTML = '<option value="">Select Zone First</option>';
            return;
        }
        
        commandSelect.innerHTML = '<option value="">Loading commands...</option>';
        commandSelect.disabled = true;
        
        const apiUrl = baseUrl.replace(/\/$/, '') + '/api/get_commands.php?zone_id=' + zoneId;
        debug('Fetching commands from:', apiUrl);
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    commandSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
                    commandSelect.disabled = false;
                    return;
                }
                
                commandSelect.innerHTML = '<option value="">Select Command</option>';
                
                if (data && data.length > 0) {
                    data.forEach(command => {
                        const option = document.createElement('option');
                        option.value = command.id;
                        option.textContent = command.command_name;
                        if (selectedCommandId && command.id == selectedCommandId) {
                            option.selected = true;
                        }
                        commandSelect.appendChild(option);
                    });
                    commandSelect.disabled = false;
                } else {
                    commandSelect.innerHTML = '<option value="">No commands found for this zone</option>';
                    commandSelect.disabled = false;
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                commandSelect.innerHTML = '<option value="">Error loading commands: ' + error.message + '</option>';
                commandSelect.disabled = false;
            });
    }

    if (zoneSelect) {
        zoneSelect.addEventListener('change', function() {
            loadCommands(this.value);
        });
        
        // Initial load if zone is selected
        if (zoneSelect.value) {
            loadCommands(zoneSelect.value, currentCommandId);
        }
    }

    // Geofencing: click-to-place map + live radius circle.
    const geoEnabledCb = document.getElementById('geofenceEnabled');
    const geoFields = document.getElementById('geofenceFields');
    const geoMapEl = document.getElementById('geofenceMap');

    if (geoEnabledCb && geoFields && geoMapEl && typeof L !== 'undefined') {
        const latInput = document.getElementById('geofenceLat');
        const lngInput = document.getElementById('geofenceLng');
        const latDisplay = document.getElementById('geofenceLatDisplay');
        const lngDisplay = document.getElementById('geofenceLngDisplay');
        const radiusInput = document.getElementById('geofenceRadius');

        const hasExisting = latInput.value !== '' && lngInput.value !== '';
        const startLat = hasExisting ? parseFloat(latInput.value) : 9.0820;
        const startLng = hasExisting ? parseFloat(lngInput.value) : 8.6753;

        let map = null;
        let marker = null;
        let circle = null;
        let initialized = false;

        function setPoint(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            latDisplay.value = lat.toFixed(7);
            lngDisplay.value = lng.toFixed(7);

            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
            updateCircle();
        }

        function updateCircle() {
            if (!latInput.value || !lngInput.value) return;
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            const radius = parseInt(radiusInput.value, 10) || 0;

            if (circle) {
                circle.setLatLng([lat, lng]);
                circle.setRadius(radius);
            } else {
                circle = L.circle([lat, lng], {
                    radius: radius,
                    color: '#207027',
                    fillColor: '#207027',
                    fillOpacity: 0.15
                }).addTo(map);
            }
        }

        function initMap() {
            if (initialized) return;
            initialized = true;

            map = L.map('geofenceMap').setView([startLat, startLng], hasExisting ? 15 : 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            if (hasExisting) {
                setPoint(startLat, startLng);
            }

            map.on('click', function (e) {
                setPoint(e.latlng.lat, e.latlng.lng);
            });

            setTimeout(function () { map.invalidateSize(); }, 100);
        }

        if (radiusInput) {
            radiusInput.addEventListener('input', updateCircle);
        }

        geoEnabledCb.addEventListener('change', function () {
            geoFields.style.display = this.checked ? '' : 'none';
            if (this.checked) {
                initMap();
                setTimeout(function () { if (map) map.invalidateSize(); }, 150);
            }
        });

        if (geoEnabledCb.checked) {
            initMap();
        }
    }

    // Form submission validation
    document.getElementById('userForm').addEventListener('submit', function(e) {
        if (isUsernameTaken) {
            e.preventDefault();
            alert('This Service Number (Username) already exists in the system. Please enter a unique service number.');
            if (usernameInput) usernameInput.focus();
            return false;
        }

        const fullNameInput = document.getElementById('full_name');
        const fullName = fullNameInput ? fullNameInput.value.trim() : '';
        const fullNamePattern = /^[a-zA-Z\s\-'.]+$/;
        
        if (!fullName) {
            e.preventDefault();
            alert('Full Name is required');
            fullNameInput.focus();
            return false;
        }
        
        if (!fullNamePattern.test(fullName)) {
            e.preventDefault();
            alert("Full Name must contain only alphabets, spaces, hyphens (-), and apostrophes (')");
            fullNameInput.focus();
            return false;
        }

        const phoneInput = document.getElementById('phone');
        const phone = phoneInput ? phoneInput.value.trim() : '';
        
        if (!phone || phone.length !== 11 || !/^\d{11}$/.test(phone)) {
            e.preventDefault();
            alert('Phone Number must be exactly 11 digits (numbers only)');
            phoneInput.focus();
            return false;
        }

        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        
        if (password || confirm) {
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            if (password.length < 8 && password.length > 0) {
                e.preventDefault();
                alert('Password must be at least 8 characters');
                return false;
            }
        }
        
        const roles = document.querySelectorAll('input[name="roles[]"]:checked');
        if (roles.length === 0) {
            e.preventDefault();
            alert('Please select at least one role');
            return false;
        }
        
        const command = document.getElementById('command_id').value;
        if (!command || command === '') {
            e.preventDefault();
            alert('Please select a command');
            return false;
        }

        const submitBtn = document.getElementById('submitUserBtn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Account...';
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>