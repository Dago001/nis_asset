<?php
require_once '../config/config.php';
requireLogin();
$active = 'profile';
$title = 'Change Password';
require_once '../views/layouts/header.php';
require_once '../views/layouts/sidebar.php';
?>
<div class="container-fluid">
    <div class="page-header">
        <h1>Change Password</h1>
        <p>Update your account password</p>
    </div>
    <div class="form-section">
        <form method="POST" action="change-password-process.php">
            <?php echo Security::csrfField(); ?>
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>
<?php require_once '../views/layouts/footer.php'; ?>