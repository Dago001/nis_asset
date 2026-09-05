<?php
$title = 'Forgot Password';
$extra_css = [BASE_URL . '/assets/css/auth.css'];
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="auth-page">
    <div class="auth-container" style="max-width: 400px;">
        <div class="auth-header">
            <img src="<?php echo BASE_URL; ?>/assets/images/nis-logo.png" alt="NIS Logo" class="auth-logo">
            <h2>Reset Password</h2>
            <p>Enter your email to receive reset instructions</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="auth-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="auth-alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php else: ?>
            <form method="POST" action="<?php echo BASE_URL; ?>/auth/forgot-password" class="auth-form">
                <?php echo Security::csrfField(); ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" 
                               required placeholder="Enter your email address"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-auth">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
        <?php endif; ?>

        <div class="auth-links">
            <p><a href="<?php echo BASE_URL; ?>/auth/login">Back to Login</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>