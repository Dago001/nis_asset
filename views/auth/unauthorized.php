<?php
$title = 'Unauthorized';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
    <div class="error-container" style="text-align: center; padding: 60px 20px;">
        <div class="error-icon" style="font-size: 5rem; color: #B42318; margin-bottom: 20px;">
            <i class="fas fa-ban"></i>
        </div>
        <h1 style="color: #B42318; margin-bottom: 20px;">Access Denied</h1>
        <p style="font-size: 1.2rem; color: #53665E; margin-bottom: 30px;">
            You do not have permission to access this page.
        </p>
        <div class="error-actions">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-primary">
                <i class="fas fa-home"></i> Return to Dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>