<?php
/**
 * Reset Password view.
 * Provided by AuthController::resetPasswordForm(): $valid, $token, $uid
 */
$title = 'Reset Password';
$extra_css = [BASE_URL . '/assets/css/auth.css'];
require_once __DIR__ . '/../layouts/header.php';

$token = $token ?? ($_GET['token'] ?? '');
$uid   = $uid   ?? ($_GET['uid'] ?? '');
$valid = $valid ?? false;
?>

<div class="auth-page">
    <div class="auth-container" style="max-width: 400px;">
        <div class="auth-header">
            <img src="<?php echo BASE_URL; ?>/assets/images/nis-logo.png" alt="NIS Logo" class="auth-logo">
            <h2>Reset Password</h2>
            <p>Choose a new password for your account</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="auth-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (!$valid): ?>
            <div class="auth-alert error">
                <i class="fas fa-exclamation-circle"></i>
                This password-reset link is invalid or has expired.
            </div>
            <div class="auth-links">
                <p><a href="<?php echo BASE_URL; ?>/auth/forgot-password">Request a new link</a></p>
                <p><a href="<?php echo BASE_URL; ?>/auth/login">Back to Login</a></p>
            </div>
        <?php else: ?>
            <form method="POST" action="<?php echo BASE_URL; ?>/auth/reset-password" class="auth-form" id="resetForm">
                <?php echo Security::csrfField(); ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="uid" value="<?php echo htmlspecialchars($uid); ?>">

                <div class="form-group">
                    <label class="required">New Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="new_password" id="new_password" required minlength="12"
                               autocomplete="new-password">
                    </div>
                    <small class="form-hint">At least 12 characters, including letters and numbers.</small>
                </div>

                <div class="form-group">
                    <label class="required">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" required
                               autocomplete="new-password">
                    </div>
                    <small id="passwordMatch" class="form-hint"></small>
                </div>

                <button type="submit" class="btn-auth">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>

            <div class="auth-links">
                <p><a href="<?php echo BASE_URL; ?>/auth/login">Back to Login</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var f = document.getElementById('resetForm');
    if (!f) return;
    var p = document.getElementById('new_password');
    var c = document.getElementById('confirm_password');
    var m = document.getElementById('passwordMatch');
    c.addEventListener('input', function () {
        if (c.value === p.value) {
            m.innerHTML = '<span class="text-success">&#10003; Passwords match</span>';
            c.setCustomValidity('');
        } else {
            m.innerHTML = '<span class="text-danger">&#10007; Passwords do not match</span>';
            c.setCustomValidity('Passwords do not match');
        }
    });
    f.addEventListener('submit', function (e) {
        if (p.value !== c.value) { e.preventDefault(); alert('Passwords do not match'); }
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
