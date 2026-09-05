<?php
http_response_code(503);
$title = 'Maintenance Mode';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
    <div class="error-container" style="text-align: center; padding: 100px 20px;">
        <div class="error-icon" style="font-size: 6rem; color: #C69214; margin-bottom: 20px;">
            <i class="fas fa-tools"></i>
        </div>
        <h1 style="font-size: 3rem; margin-bottom: 20px;">Maintenance Mode</h1>
        <p style="font-size: 1.2rem; color: #53665E; margin-bottom: 30px; white-space: pre-line;">
            <?php echo htmlspecialchars(Config::get('maintenance_message', "The system is currently undergoing scheduled maintenance.\nPlease check back later.")); ?>
        </p>
        <p style="color: #8A9A91; margin-top: 50px;">
            Expected completion: <span id="eta"></span>
        </p>
    </div>
</div>

<script>
// Set ETA from server if available
const eta = '<?php echo Config::get('maintenance_eta', 'Unknown'); ?>';
document.getElementById('eta').textContent = eta;
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>