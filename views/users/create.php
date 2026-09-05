<?php
$title = 'Add New User';
$active = 'users';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$old = Session::get('old', []);
$errors = Session::get('errors', []);
Session::remove('old');
Session::remove('errors');

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

// A non-Super-Admin (e.g. the 'admin' role) must not see Super Admin as an assignable option
if (!Auth::isSuperAdmin()) {
    $roles = array_values(array_filter($roles, function ($r) {
        $name = strtolower(trim($r['role_name'] ?? ''));
        return strpos($name, 'super admin') === false && strpos($name, 'superadmin') === false;
    }));
}

// Generate CSRF token using Security class
$csrfToken = Security::csrfToken();
?>

<div class="container-fluid create-user-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-user-plus"></i>
                Add New User
            </h1>
            <p>Create a new officer user account and configure system privileges</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/users" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    <!-- User Creation Form -->
    <div class="form-container-card">
        <form method="POST" action="<?php echo BASE_URL; ?>/users/store" id="userForm" autocomplete="off">
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
                                   value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>"
                                   required minlength="4" maxlength="5" inputmode="numeric" pattern="\d{4,5}"
                                   placeholder="e.g. 12345"
                                   autocomplete="off" class="form-control <?php echo isset($errors['username']) ? 'error' : ''; ?>">
                        </div>
                        <div id="usernameFeedback" class="username-feedback" style="margin-top: 4px; font-size: 0.82rem; min-height: 18px;">
                            <?php if (isset($errors['username'])): ?>
                                <small class="error-text"><?php echo htmlspecialchars($errors['username']); ?></small>
                            <?php endif; ?>
                        </div>
                        <small class="form-hint"></small>
                    </div>

                    <div class="form-group">
                        <label for="email" class="required">Email Address</label>
                        <div class="input-with-prefix">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email"
                                   value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
                                   required maxlength="100" placeholder="officer@immigration.gov.ng"
                                   autocomplete="off" class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>">
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['email']); ?></small>
                        <?php endif; ?>
                        <small class="form-hint"></small>
                    </div>
                </div>

                <div class="password-tools-bar">
                    <div class="tools-left">
                        <button type="button" id="generatePasswordBtn" class="btn btn-generator">
                            <i class="fas fa-key"></i> Generate Strong Password
                        </button>
                    </div>
                    <div class="tools-right">
                        <small class="form-hint"></small>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="password" class="required">Initial Password</label>
                        <div class="input-with-prefix input-with-toggle">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password"
                                   required minlength="8" placeholder="••••••••"
                                   autocomplete="new-password" class="form-control <?php echo isset($errors['password']) ? 'error' : ''; ?>">
                            <button type="button" class="password-toggle" data-target="password" title="Toggle visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['password']); ?></small>
                        <?php endif; ?>
                        <div class="strength-meter-box">
                            <div class="strength-bar"><div class="strength-bar-fill" id="strengthBarFill"></div></div>
                            <small class="strength-text" id="passwordStrength">Password Strength: Not set</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="required">Confirm Password</label>
                        <div class="input-with-prefix input-with-toggle">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   required placeholder="••••••••"
                                   autocomplete="new-password" class="form-control <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>">
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

                <div class="info-callout">
                    <i class="fas fa-shield-halved"></i>
                    <div>
                        <strong>Mandatory Onboarding Policy:</strong> The user will be required to change this password and scan their <strong>Google Authenticator QR Code</strong> upon their first login before accessing the application.
                    </div>
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
                                   value="<?php echo htmlspecialchars($old['full_name'] ?? ''); ?>" 
                                   required maxlength="100" placeholder="e.g. GIFT DAGOGO"
                                   pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
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
                                   value="<?php echo htmlspecialchars($old['nis_number'] ?? ''); ?>"
                                   required maxlength="20" inputmode="numeric" pattern="[0-9]+" title="Numbers only"
                                   placeholder="e.g. 12345"
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
                                    <option value="<?php echo $rankCode; ?>" <?php echo ($old['rank'] ?? '') == $rankCode ? 'selected' : ''; ?>>
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
                                   value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>"
                                   required minlength="11" maxlength="11" inputmode="numeric" pattern="\d{11}" title="Phone number must be exactly 11 digits"
                                   placeholder="e.g. 08012345678"
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
                                        <?php echo ($old['zone_id'] ?? '') == $zone['id'] ? 'selected' : ''; ?>>
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
                                <option value="">Select Zone First</option>
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
                        $isChecked = (isset($old['roles']) && in_array($role['id'], $old['roles']));
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
            
            <!-- Form Action Buttons -->
            <div class="form-actions-bar">
                <button type="submit" class="btn btn-success submit-btn" id="submitUserBtn">
                    <i class="fas fa-floppy-disk"></i> Create User Account
                </button>
                <a href="<?php echo BASE_URL; ?>/users" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* User Create View Master Styles */
.create-user-wrapper {
    padding-bottom: 35px;
}

/* Page Header */
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
    font-weight: 600;
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
    font-weight: 700;
    color: var(--text-primary, #212529);
    margin: 0 0 3px 0;
}

.section-header-text p {
    font-size: 0.85rem;
    color: var(--text-secondary, #53665E);
    margin: 0;
}

/* Form Grid */
.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    margin-bottom: 16px;
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

/* Password Tools Bar */
.password-tools-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--light-bg, #F7FAF8);
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 6px;
    padding: 10px 14px;
    margin-bottom: 16px;
}

.btn-generator {
    background: #e8f5e9;
    color: var(--primary-color, #134617);
    border: 1px solid #c8e6c9;
    padding: 6px 14px;
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-generator:hover {
    background: #c8e6c9;
    color: var(--primary-color, #134617);
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

/* Info Callout */
.info-callout {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    color: #0369a1;
    font-size: 0.86rem;
    line-height: 1.45;
    margin-top: 10px;
}

.info-callout i {
    font-size: 1.1rem;
    margin-top: 2px;
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
    .form-grid-2 {
        grid-template-columns: 1fr;
        gap: 14px;
    }
    .role-cards-grid {
        grid-template-columns: 1fr;
    }
    .password-tools-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
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
    debug('Page loaded');
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

    // Generate a strong random password into both Password fields.
    function generateStrongPassword(length = 16) {
        const lower   = 'abcdefghijkmnopqrstuvwxyz';
        const upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const digits  = '23456789';
        const special = '!@#$%^&*()-_=+?';
        const all = lower + upper + digits + special;

        function pick(charset) {
            const arr = new Uint32Array(1);
            crypto.getRandomValues(arr);
            return charset[arr[0] % charset.length];
        }

        const required = [pick(lower), pick(upper), pick(digits), pick(special)];
        const rest = [];
        for (let i = required.length; i < length; i++) rest.push(pick(all));

        const chars = required.concat(rest);
        for (let i = chars.length - 1; i > 0; i--) {
            const r = new Uint32Array(1);
            crypto.getRandomValues(r);
            const j = r[0] % (i + 1);
            [chars[i], chars[j]] = [chars[j], chars[i]];
        }
        return chars.join('');
    }

    const generateBtn = document.getElementById('generatePasswordBtn');
    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            const generated = generateStrongPassword();
            const pw = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            pw.value = generated;
            confirm.value = generated;
            
            [pw, confirm].forEach(input => {
                input.type = 'text';
                const btn = input.closest('.input-with-toggle')?.querySelector('.password-toggle i');
                if (btn) { btn.classList.remove('fa-eye'); btn.classList.add('fa-eye-slash'); }
            });
            pw.dispatchEvent(new Event('input'));
            confirm.dispatchEvent(new Event('input'));
            pw.focus();
            pw.select();
        });
    }

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
                meter.textContent = 'Password Strength: Not set';
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
            if (!this.value) {
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

        const apiUrl = baseUrl.replace(/\/$/, '') + '/users/check-username?username=' + encodeURIComponent(usernameVal);
        
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
            if (nisNumberInput) {
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

        if (usernameInput.value.trim().length >= 4) {
            checkUsernameAvailability(usernameInput.value.trim());
        }
    }

    if (nisNumberInput) {
        nisNumberInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 5);
        });
    }
    
    // Zone to Command dropdown cascade
    const zoneSelect = document.getElementById('zone_id');
    const commandSelect = document.getElementById('command_id');
    
    if (zoneSelect) {
        debug('Zone select found');
        
        zoneSelect.addEventListener('change', function() {
            const zoneId = this.value;
            debug('Zone selected:', zoneId);
            
            if (!zoneId) {
                commandSelect.innerHTML = '<option value="">Select Zone First</option>';
                return;
            }
            
            // Show loading
            commandSelect.innerHTML = '<option value="">Loading commands...</option>';
            commandSelect.disabled = true;
            
            // Construct the API URL
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
                            commandSelect.appendChild(option);
                        });
                        commandSelect.disabled = false;
                    } else {
                        commandSelect.innerHTML = '<option value="">No commands found for this zone</option>';
                        commandSelect.disabled = false;
                    }
                    
                    <?php if (!empty($old['command_id'])): ?>
                    commandSelect.value = '<?php echo $old['command_id']; ?>';
                    <?php endif; ?>
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    commandSelect.innerHTML = '<option value="">Error loading commands: ' + error.message + '</option>';
                    commandSelect.disabled = false;
                });
        });
        
        <?php if (!empty($old['zone_id'])): ?>
        zoneSelect.value = '<?php echo $old['zone_id']; ?>';
        zoneSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
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
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match');
            return false;
        }
        
        if (checkPasswordStrength(password) < 2) {
            e.preventDefault();
            alert('Password is too weak. Please use a stronger password.');
            return false;
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
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
