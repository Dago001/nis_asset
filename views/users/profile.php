<?php
$title = 'My Profile';
$active = 'profile';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$errors = Session::get('errors', []);
Session::remove('errors');

// Ensure user data is populated from database or fallback to session
if (!isset($user) || empty($user)) {
    $user = Database::fetchOne(
        "SELECT u.*, c.command_name, z.zone_name
         FROM users u
         LEFT JOIN commands c ON u.command_id = c.id
         LEFT JOIN zones z ON c.zone_id = z.id
         WHERE u.id = ?",
        [Auth::id()]
    );
}

if (!$user) {
    $user = [
        'id' => $_SESSION['user_id'] ?? 0,
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'rank' => $_SESSION['rank'] ?? '',
        'nis_number' => $_SESSION['nis_number'] ?? '',
        'phone' => $_SESSION['phone'] ?? '',
        'profile_image' => $_SESSION['profile_image'] ?? null,
        'two_factor_enabled' => $_SESSION['two_factor_enabled'] ?? false,
        'created_at' => $_SESSION['created_at'] ?? date('Y-m-d H:i:s'),
        'last_login' => $_SESSION['last_login'] ?? null,
        'last_ip' => $_SESSION['last_ip'] ?? null
    ];
}

$roles = $_SESSION['roles'] ?? [];

// Calculate initials for avatar fallback
$initials = '';
$nameParts = explode(' ', trim($user['full_name'] ?? 'User'));
foreach (array_slice($nameParts, 0, 2) as $np) {
    $initials .= strtoupper(substr($np, 0, 1));
}
if (empty($initials)) $initials = 'NIS';

$baseDir = defined('BASE_PATH') ? BASE_PATH : (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2));
$avatarUrl = !empty($user['profile_image']) && file_exists($baseDir . '/' . $user['profile_image'])
    ? BASE_URL . '/' . htmlspecialchars($user['profile_image'])
    : null;
?>

<div class="container-fluid user-profile-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-user-circle"></i>
                My Officer Profile
            </h1>
            <p>Manage your personal profile, officer credentials, avatar photo, and account security</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/auth/change-password" class="btn btn-outline">
                <i class="fas fa-key"></i> Change Password
            </a>
            <button type="button" class="btn btn-outline" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Errors Display -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px; border-radius: 8px; padding: 14px 18px;">
        <h4 style="margin: 0 0 8px 0; font-size: 0.95rem; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> Please resolve the following issues:</h4>
        <ul style="margin: 0; padding-left: 20px; font-size: 0.88rem;">
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars(is_array($error) ? implode(', ', $error) : $error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo BASE_URL; ?>/users/update-profile" enctype="multipart/form-data" id="profileForm" autocomplete="off">
        <?php echo Security::csrfField(); ?>
        <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">

        <!-- Top Overview Hero Card with Avatar Upload -->
        <div class="profile-hero-card">
            <div class="profile-avatar-section">
                <div class="avatar-preview-wrapper" id="avatarWrapper">
                    <?php if ($avatarUrl): ?>
                        <img src="<?php echo $avatarUrl; ?>" alt="Officer Avatar" class="avatar-img-preview" id="avatarPreviewImg">
                        <div class="avatar-initials-fallback" id="avatarInitials" style="display: none;"><?php echo htmlspecialchars($initials); ?></div>
                    <?php else: ?>
                        <img src="" alt="Officer Avatar" class="avatar-img-preview" id="avatarPreviewImg" style="display: none;">
                        <div class="avatar-initials-fallback" id="avatarInitials"><?php echo htmlspecialchars($initials); ?></div>
                    <?php endif; ?>
                    
                    <label for="avatarInput" class="avatar-upload-overlay" title="Upload new avatar photo">
                        <i class="fas fa-camera"></i>
                        <span>Upload Photo</span>
                    </label>
                </div>
                <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;">
            </div>

            <div class="profile-hero-info">
                <div class="hero-header-row">
                    <div>
                        <h2><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></h2>
                        <p class="hero-subtext">
                            <span class="rank-chip"><i class="fas fa-award"></i> <?php echo htmlspecialchars($user['rank'] ?? 'Officer'); ?></span>
                            <span class="service-chip"><i class="fas fa-hashtag"></i> NIS #<?php echo htmlspecialchars($user['nis_number'] ?? 'N/A'); ?></span>
                        </p>
                    </div>
                    <div class="avatar-action-buttons">
                        <label for="avatarInput" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-arrow-up-from-bracket"></i> Change Photo
                        </label>
                        <?php if ($avatarUrl): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="removeAvatarBtn">
                            <i class="fas fa-trash-can"></i> Remove Photo
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <small class="form-hint" style="margin-top: 8px; display: block;">
                    <i class="fas fa-info-circle text-primary"></i> Recommended: Square image (JPG, PNG, WEBP), max 5MB.
                </small>
            </div>
        </div>

        <!-- Main Form Grid (2 Columns) -->
        <div class="profile-layout-grid">
            <!-- Left Column: Personal Profile & Details -->
            <div class="profile-main-column">
                <div class="pro-card">
                    <div class="pro-card-header">
                        <div class="header-title-box">
                            <i class="fas fa-id-card"></i>
                            <h3>Personal Profile Information</h3>
                        </div>
                    </div>
                    <div class="pro-card-body">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="full_name" class="required">Full Name</label>
                                <div class="input-with-prefix">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="full_name" id="full_name"
                                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                           required maxlength="100" pattern="[a-zA-Z\s\-'.]+" 
                                           title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                                           class="form-control <?php echo isset($errors['full_name']) ? 'error' : ''; ?>">
                                </div>
                                <?php if (isset($errors['full_name'])): ?>
                                    <small class="error-text"><?php echo htmlspecialchars($errors['full_name']); ?></small>
                                <?php endif; ?>
                                <small class="form-hint"></small>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="required">Phone Number</label>
                                <div class="input-with-prefix">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" name="phone" id="phone"
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                           required minlength="11" maxlength="11" inputmode="numeric" pattern="\d{11}" 
                                           title="Phone number must be exactly 11 digits"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                                           class="form-control <?php echo isset($errors['phone']) ? 'error' : ''; ?>">
                                </div>
                                <?php if (isset($errors['phone'])): ?>
                                    <small class="error-text"><?php echo htmlspecialchars($errors['phone']); ?></small>
                                <?php endif; ?>
                                <small class="form-hint"></small>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="email" class="required">Official Email Address</label>
                                <div class="input-with-prefix">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" id="email"
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                           required maxlength="100"
                                           class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>">
                                </div>
                                <?php if (isset($errors['email'])): ?>
                                    <small class="error-text"><?php echo htmlspecialchars($errors['email']); ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Service Number (Read-Only)</label>
                                <div class="input-with-prefix">
                                    <i class="fas fa-hashtag"></i>
                                    <input type="text" readonly class="form-control readonly-field"
                                           value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                                </div>
                                <small class="form-hint"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Update Section -->
                <div class="pro-card">
                    <div class="pro-card-header">
                        <div class="header-title-box">
                            <i class="fas fa-lock"></i>
                            <h3>Update Password (Optional)</h3>
                        </div>
                    </div>
                    <div class="pro-card-body">
                        <div class="form-group" style="margin-bottom: 14px;">
                            <label for="current_password">Current Password</label>
                            <div class="input-with-prefix">
                                <i class="fas fa-key"></i>
                                <input type="password" name="current_password" id="current_password"
                                       placeholder="Enter current password to make changes"
                                       class="form-control <?php echo isset($errors['current_password']) ? 'error' : ''; ?>">
                            </div>
                            <?php if (isset($errors['current_password'])): ?>
                                <small class="error-text"><?php echo htmlspecialchars($errors['current_password']); ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="input-with-prefix">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="new_password" id="new_password" minlength="8"
                                           placeholder="At least 8 characters"
                                           class="form-control <?php echo isset($errors['new_password']) ? 'error' : ''; ?>">
                                </div>
                                <?php if (isset($errors['new_password'])): ?>
                                    <small class="error-text"><?php echo htmlspecialchars($errors['new_password']); ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="input-with-prefix">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="confirm_password" id="confirm_password"
                                           placeholder="Repeat new password"
                                           class="form-control <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>">
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <small class="error-text"><?php echo htmlspecialchars($errors['confirm_password']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions-bar">
                    <button type="submit" class="btn btn-success submit-btn" id="saveProfileBtn">
                        <i class="fas fa-floppy-disk"></i> Save Profile & Avatar
                    </button>
                </div>
            </div>

            <!-- Right Column: Formations, Roles & 2FA -->
            <div class="profile-side-column">
                <!-- Deployment Info -->
                <div class="pro-card">
                    <div class="pro-card-header">
                        <div class="header-title-box">
                            <i class="fas fa-building-shield"></i>
                            <h3>Deployment Formation</h3>
                        </div>
                    </div>
                    <div class="pro-card-body">
                        <div class="side-info-row">
                            <span class="side-label">Command</span>
                            <span class="side-value font-semibold text-primary">
                                <?php echo htmlspecialchars($user['command_name'] ?? 'HQ / Unassigned'); ?>
                            </span>
                        </div>
                        <div class="side-info-row">
                            <span class="side-label">Zonal Command</span>
                            <span class="side-value">
                                <?php echo htmlspecialchars($user['zone_name'] ?? 'N/A'); ?>
                            </span>
                        </div>
                        <div class="side-info-row">
                            <span class="side-label">Officer Rank</span>
                            <span class="side-value">
                                <?php echo htmlspecialchars($user['rank'] ?? 'Unranked'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Roles -->
                <div class="pro-card">
                    <div class="pro-card-header">
                        <div class="header-title-box">
                            <i class="fas fa-shield-halved"></i>
                            <h3>System Roles</h3>
                        </div>
                    </div>
                    <div class="pro-card-body">
                        <div class="roles-pills-wrap">
                            <?php if (!empty($roles)): ?>
                                <?php foreach ($roles as $role): ?>
                                    <div class="role-pill-chip">
                                        <i class="fas fa-shield-check"></i>
                                        <span><?php echo htmlspecialchars($role); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.85rem;">Standard User Permissions</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Google 2FA Security -->
                <div class="pro-card">
                    <div class="pro-card-header">
                        <div class="header-title-box">
                            <i class="fas fa-mobile-screen-button"></i>
                            <h3>Two-Factor Authentication</h3>
                        </div>
                    </div>
                    <div class="pro-card-body">
                        <?php if (!empty($user['two_factor_enabled'])): ?>
                            <div class="geofence-status-banner active" style="margin-bottom: 12px;">
                                <i class="fas fa-shield-check" style="color: #16a34a;"></i>
                                <div>
                                    <strong style="color: #15803d;">Google 2FA Active</strong>
                                    <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: #166534;">Your account requires an authenticator code at login.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="geofence-status-banner inactive" style="margin-bottom: 12px;">
                                <i class="fas fa-shield-slash" style="color: #d97706;"></i>
                                <div>
                                    <strong style="color: #b45309;">2FA Not Enabled</strong>
                                    <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: #78350f;">Secure your account by enabling Google Authenticator.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* User Profile Master Styles */
.user-profile-wrapper {
    padding-bottom: 40px;
}

/* Page Header - moderate font-weight */
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
    font-weight: 600 !important;
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

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Profile Hero Card with Avatar Upload */
.profile-hero-card {
    background: var(--surface, #ffffff);
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 10px;
    padding: 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.profile-avatar-section {
    position: relative;
    flex-shrink: 0;
}

.avatar-preview-wrapper {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    position: relative;
    overflow: hidden;
    border: 3px solid var(--primary-light, #207027);
    box-shadow: 0 4px 12px rgba(19, 70, 23, 0.2);
    background: #f1f5f9;
}

.avatar-img-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.avatar-initials-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #134617 0%, #207027 100%);
    color: white;
    font-size: 2rem;
    font-weight: 700;
}

.avatar-upload-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.65);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4px 0;
    font-size: 0.68rem;
    font-weight: 600;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.avatar-preview-wrapper:hover .avatar-upload-overlay {
    opacity: 1;
}

.avatar-upload-overlay i {
    font-size: 0.85rem;
    margin-bottom: 2px;
}

.profile-hero-info {
    flex: 1;
}

.hero-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

.hero-header-row h2 {
    margin: 0 0 6px 0;
    font-size: 1.35rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
}

.hero-subtext {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.rank-chip, .service-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--light-bg, #F7FAF8);
    border: 1px solid var(--border-color, #D7E3DC);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-secondary, #53665E);
}

.avatar-action-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.82rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.btn-outline-primary {
    background: white;
    border-color: var(--primary-light, #207027);
    color: var(--primary-light, #207027);
}

.btn-outline-primary:hover {
    background: #e8f5e9;
}

.btn-outline-danger {
    background: white;
    border-color: #ef4444;
    color: #ef4444;
}

.btn-outline-danger:hover {
    background: #fee2e2;
}

/* 2-Column Profile Layout */
.profile-layout-grid {
    display: grid;
    grid-template-columns: 7fr 3fr;
    gap: 20px;
}

/* Cards */
.pro-card {
    background: var(--surface, #ffffff);
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    margin-bottom: 20px;
    overflow: hidden;
}

.pro-card-header {
    padding: 14px 18px;
    background: var(--light-bg, #F7FAF8);
    border-bottom: 1px solid var(--border-color, #D7E3DC);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title-box {
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-title-box i {
    color: var(--primary-light, #207027);
    font-size: 1rem;
}

.header-title-box h3 {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
}

.pro-card-body {
    padding: 18px;
}

/* Form Elements */
.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 14px;
}

.form-grid-2:last-child {
    margin-bottom: 0;
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
}

.input-with-prefix input {
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

.input-with-prefix input:focus {
    border-color: var(--primary-light, #207027);
    box-shadow: 0 0 0 3px rgba(32, 112, 39, 0.15);
}

.readonly-field {
    background: #f8fafc !important;
    color: #64748b !important;
    cursor: not-allowed;
}

.form-actions-bar {
    padding: 16px 0 0 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.side-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color, #D7E3DC);
    font-size: 0.88rem;
}

.side-info-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.side-label {
    color: var(--text-secondary, #53665E);
}

.side-value {
    color: var(--text-primary, #212529);
}

.roles-pills-wrap {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.role-pill-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #e8f5e9;
    color: #134617;
    border: 1px solid #c8e6c9;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}

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

.btn-success { background: var(--primary-light, #207027); color: white; }
.btn-success:hover { background: var(--primary-color, #134617); color: white; transform: translateY(-1px); }
.btn-outline { background: transparent; border: 1px solid var(--border-color, #D7E3DC); color: var(--text-secondary, #53665E); }
.btn-outline:hover { background: #e2e8f0; color: var(--text-primary, #212529); }

.error-text { color: #dc2626; font-size: 0.82rem; margin-top: 4px; }
.form-hint { font-size: 0.8rem; color: var(--text-secondary, #53665E); margin-top: 4px; }

@media (max-width: 992px) {
    .profile-layout-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .profile-hero-card { flex-direction: column; text-align: center; }
    .hero-header-row { flex-direction: column; }
    .hero-subtext { justify-content: center; }
    .avatar-action-buttons { justify-content: center; }
    .form-grid-2 { grid-template-columns: 1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreviewImg = document.getElementById('avatarPreviewImg');
    const avatarInitials = document.getElementById('avatarInitials');
    const removeAvatarInput = document.getElementById('removeAvatarInput');
    const removeAvatarBtn = document.getElementById('removeAvatarBtn');

    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validate size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Selected image exceeds 5MB. Please choose a smaller photo.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreviewImg.src = e.target.result;
                    avatarPreviewImg.style.display = 'block';
                    if (avatarInitials) avatarInitials.style.display = 'none';
                    if (removeAvatarInput) removeAvatarInput.value = '0';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeAvatarBtn) {
        removeAvatarBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove your profile photo?')) {
                if (avatarInput) avatarInput.value = '';
                if (removeAvatarInput) removeAvatarInput.value = '1';
                avatarPreviewImg.src = '';
                avatarPreviewImg.style.display = 'none';
                if (avatarInitials) avatarInitials.style.display = 'flex';
                this.style.display = 'none';
            }
        });
    }

    // Client-side submit validation
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
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

            const newPw = document.getElementById('new_password') ? document.getElementById('new_password').value : '';
            const confirmPw = document.getElementById('confirm_password') ? document.getElementById('confirm_password').value : '';
            const currentPw = document.getElementById('current_password') ? document.getElementById('current_password').value : '';

            if (newPw || confirmPw) {
                if (!currentPw) {
                    e.preventDefault();
                    alert('Please enter your Current Password to change passwords');
                    document.getElementById('current_password').focus();
                    return false;
                }
                if (newPw.length < 8) {
                    e.preventDefault();
                    alert('New Password must be at least 8 characters');
                    document.getElementById('new_password').focus();
                    return false;
                }
                if (newPw !== confirmPw) {
                    e.preventDefault();
                    alert('New passwords do not match');
                    document.getElementById('confirm_password').focus();
                    return false;
                }
            }

            const btn = document.getElementById('saveProfileBtn');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Changes...';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>