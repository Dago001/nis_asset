<?php
http_response_code(403);
$title = '403 - Forbidden';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
    <div class="error-container" style="text-align: center; padding: 100px 20px;">
        <div class="error-icon" style="font-size: 6rem; color: #B42318; margin-bottom: 20px;">
            <i class="fas fa-ban"></i>
        </div>
        <h1 style="font-size: 3rem; margin-bottom: 20px;">403</h1>
        <h2 style="color: #134617; margin-bottom: 20px;">Access Forbidden</h2>
        <p style="font-size: 1.2rem; color: #53665E; margin-bottom: 30px;">
            You don't have permission to access this resource.
        </p>
        <div class="error-actions">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-primary btn-lg">
                <i class="fas fa-home"></i> Go to Dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>