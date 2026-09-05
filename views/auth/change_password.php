<?php
$title = 'Change Password';
$active = 'profile';
$extra_css = [BASE_URL . '/assets/css/auth.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$forced = $forced ?? false;
$flashError   = $_SESSION['error']   ?? null;
$flashSuccess = $_SESSION['success'] ?? null;
$flashInfo    = $_SESSION['info']    ?? null;
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['info']);
?>

<style>
    .alert-flash {
        max-width: 480px;
        margin: 0 auto 12px;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .alert-flash-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-flash-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .alert-flash-info    { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    .alert-step-guide    { background: #fafcf9; color: #134617; border: 1px solid #c7e6cc; box-shadow: 0 2px 6px rgba(19,70,23,0.06); }
    .alert-step-guide i, .alert-step-guide strong, .alert-step-guide div { color: #134617 !important; }
    [data-theme="dark"] .alert-flash-success { background: rgba(16, 185, 129, 0.16); color: #34d399; border-color: rgba(16, 185, 129, 0.35); }
    [data-theme="dark"] .alert-flash-error   { background: rgba(239, 68, 68, 0.14); color: #f87171; border-color: rgba(239, 68, 68, 0.3); }
    [data-theme="dark"] .alert-flash-info    { background: rgba(59, 130, 246, 0.16); color: #93c5fd; border-color: rgba(59, 130, 246, 0.35); }
    [data-theme="dark"] .alert-step-guide    { background: rgba(19, 70, 23, 0.18); color: #4ade80; border-color: rgba(19, 70, 23, 0.4); }
    [data-theme="dark"] .alert-step-guide i, [data-theme="dark"] .alert-step-guide strong, [data-theme="dark"] .alert-step-guide div { color: #4ade80 !important; }

    .form-section {
        max-width: 480px;
        margin: 1.5rem auto;
        padding: 2rem;
        background: var(--surface, #ffffff);
        color: var(--text-primary, #212529);
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color, #e0e0e0);
    }
    
    .input-with-icon {
        position: relative;
        width: 100%;
    }
    
    .input-with-icon > i.fa-lock {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #777;
        pointer-events: none;
        font-size: 0.9rem;
        z-index: 5;
    }
    
    .input-with-icon input {
        width: 100% !important;
        padding: 10px 40px 10px 36px !important;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 0.95rem;
        box-sizing: border-box;
        height: 42px;
        transition: border-color 0.2s;
    }
    
    .input-with-icon input:focus {
        border-color: #207027;
        outline: none;
    }
    
    .password-toggle {
        position: absolute;
        top: 50%;
        right: 8px;
        transform: translateY(-50%);
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        background: none;
        border: none;
        cursor: pointer;
        color: #777;
        padding: 0;
        z-index: 5;
    }
    
    .password-toggle:hover {
        color: #207027;
    }
</style>

<div class="container-fluid">
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-key"></i> Change Password</h1>
            <p><?php echo $forced ? 'Set a new password to continue' : 'Update your account password'; ?></p>
        </div>
        <?php if (!$forced): ?>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php if ($forced): ?>
            <div class="alert-flash alert-step-guide" style="display: flex; align-items: flex-start; gap: 10px;">
                <i class="fas fa-shield-halved" style="margin-top: 3px; font-size: 1.1rem;"></i>
                <div>
                    <strong>Step 1 of 2: Create Your New Password</strong><br>
                    Please enter the temporary password as your "Current Password", then choose a strong personal password. After saving, you will be guided to <strong>Step 2: Scan your Google Authenticator QR Code</strong> to complete your setup.
                </div>
            </div>
            <?php endif; ?>
            <?php if ($flashInfo): ?>
            <div class="alert-flash alert-flash-info">
                <i class="fas fa-circle-info"></i> <?php echo htmlspecialchars($flashInfo); ?>
            </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
            <div class="alert-flash alert-flash-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($flashError); ?>
            </div>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
            <div class="alert-flash alert-flash-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flashSuccess); ?>
            </div>
            <?php endif; ?>

            <div class="form-section">
                <div class="section-title">
                    <h3><i class="fas fa-lock"></i> Password Update</h3>
                </div>

                <form method="POST" action="<?php echo BASE_URL; ?>/auth/change-password" id="passwordForm" autocomplete="off">
                    <?php echo Security::csrfField(); ?>

                    <div class="form-group">
                        <label class="required">Current Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="current_password" id="current_password" required autocomplete="current-password">
                            <button type="button" class="password-toggle" data-target="current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required">New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="new_password" id="new_password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="strength-meter" id="passwordStrength"></div>
                        <small class="form-hint">
                            Minimum 8 characters with uppercase, lowercase, number and special character
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="required">Confirm New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small id="passwordMatch" class="form-hint"></small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success submit-btn">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                        <?php if (!$forced): ?>
                        <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
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

document.getElementById('new_password').addEventListener('input', function() {
    const strength = checkPasswordStrength(this.value);
    const meter = document.getElementById('passwordStrength');
    
    const messages = ['Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
    meter.textContent = messages[strength];
    meter.className = 'strength-meter strength-' + strength;
});

// Password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPass = document.getElementById('new_password').value;
    const match = document.getElementById('passwordMatch');
    
    if (this.value === newPass) {
        match.innerHTML = '<span class="text-success">✓ Passwords match</span>';
        this.setCustomValidity('');
    } else {
        match.innerHTML = '<span class="text-danger">✗ Passwords do not match</span>';
        this.setCustomValidity('Passwords do not match');
    }
});

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    
    if (newPass !== confirmPass) {
        e.preventDefault();
        alert('Passwords do not match');
        return false;
    }
    
    if (checkPasswordStrength(newPass) < 2) {
        e.preventDefault();
        alert('Password is too weak. Please use a stronger password.');
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>