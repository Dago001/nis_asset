<?php
$title = 'Process Return';
$active = 'weapon_issue';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$issue = isset($issue) ? $issue : [];
$type = isset($type) ? $type : 'weapon';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-undo-alt"></i>
                Process Return
            </h1>
            <p>
                <?php if ($type === 'weapon'): ?>
                    Return Weapon: <?php echo Security::escape($issue['weapon_id'] ?? ''); ?>
                <?php else: ?>
                    Return Ammunition: <?php echo Security::escape($issue['ammo_id'] ?? ''); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/weapon_issue" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Issue
            </a>
            <a href="<?php echo BASE_URL; ?>/weapon_issue/history" class="btn btn-info">
                <i class="fas fa-history"></i> View History
            </a>
        </div>
    </div>

    <div class="form-section">
        <div class="section-title">
            <h3><i class="fas fa-info-circle"></i> Issue Details</h3>
        </div>

        <!-- Issue Details Summary -->
        <div class="summary-grid">
            <?php if ($type === 'weapon'): ?>
                <div class="summary-item">
                    <span class="label">Weapon ID:</span>
                    <span class="value"><?php echo Security::escape($issue['weapon_id'] ?? ''); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Make/Model:</span>
                    <span class="value"><?php echo Security::escape($issue['make_model'] ?? ''); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Serial Number:</span>
                    <span class="value"><?php echo Security::escape($issue['serial_no'] ?? ''); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Issued To:</span>
                    <span class="value"><?php echo Security::escape($issue['officer_name'] ?? ''); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Issue Date:</span>
                    <span class="value"><?php echo date('d/m/Y', strtotime($issue['issue_date'])); ?></span>
                </div>
                <?php if ($issue['expected_return_date']): ?>
                <div class="summary-item">
                    <span class="label">Expected Return:</span>
                    <span class="value"><?php echo date('d/m/Y', strtotime($issue['expected_return_date'])); ?></span>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="summary-item">
                    <span class="label">Ammunition:</span>
                    <span class="value"><?php echo Security::escape($issue['ammo_id'] ?? ''); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Type/Calibre:</span>
                    <span class="value"><?php echo Security::escape(($issue['ammo_type'] ?? '') . ' (' . ($issue['calibre'] ?? '') . ')'); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Units Issued:</span>
                    <span class="value"><?php echo $issue['units_issued'] ?? 0; ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Total Rounds:</span>
                    <span class="value"><?php echo $issue['total_rounds'] ?? 0; ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Issued To:</span>
                    <span class="value"><?php echo Security::escape($issue['issued_to'] ?? ''); ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Issue Date:</span>
                    <span class="value"><?php echo date('d/m/Y', strtotime($issue['issue_date'])); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Return Form -->
        <div class="section-title" style="margin-top: 30px;">
            <h3><i class="fas fa-undo-alt"></i> Return Details</h3>
        </div>

        <form method="POST" action="<?php echo BASE_URL; ?>/weapon_issue/processReturn/<?php echo $issue['id']; ?>" id="returnForm">
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="return_type" value="<?php echo $type; ?>">

            <?php if ($type === 'weapon'): ?>
                <!-- Weapon Return Form -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="return_date" class="required">Return Date</label>
                        <input type="date" name="return_date" id="return_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="return_condition" class="required">Return Condition</label>
                        <select name="return_condition" id="return_condition" class="form-control" required>
                            <option value="">Select Condition</option>
                            <option value="Serviceable">Serviceable</option>
                            <option value="Unserviceable">Unserviceable</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Missing Parts">Missing Parts</option>
                        </select>
                    </div>
                </div>

            <?php else: ?>
                <!-- Ammunition Return Form -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="return_date" class="required">Return Date</label>
                        <input type="date" name="return_date" id="return_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="rounds_returned" class="required">Rounds Returned</label>
                        <input type="number" name="rounds_returned" id="rounds_returned" class="form-control" 
                               min="0" max="<?php echo $issue['total_rounds'] ?? 0; ?>" required 
                               value="<?php echo $issue['total_rounds'] ?? 0; ?>" oninput="calculateRoundsUsed()">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rounds_used">Rounds Used (Calculated)</label>
                        <input type="number" name="rounds_used" id="rounds_used" class="form-control" 
                               value="0" readonly>
                    </div>

                    <div class="form-group">
                        <label for="return_condition">Condition</label>
                        <select name="return_condition" id="return_condition" class="form-control">
                            <option value="">Select Condition</option>
                            <option value="Serviceable">Serviceable</option>
                            <option value="Unserviceable">Unserviceable</option>
                            <option value="Damaged">Damaged</option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea name="remarks" id="remarks" rows="3" class="form-control"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Process Return
                </button>
                <a href="<?php echo BASE_URL; ?>/weapon_issue" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    background: var(--light-bg);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.summary-item {
    display: flex;
    flex-direction: column;
}

.summary-item .label {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 5px;
}

.summary-item .value {
    font-weight: 600;
    color: var(--text-primary);
}

@media (max-width: 768px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
<?php if ($type === 'ammunition'): ?>
function calculateRoundsUsed() {
    const roundsReturned = parseInt(document.getElementById('rounds_returned').value) || 0;
    const totalRounds = <?php echo $issue['total_rounds'] ?? 0; ?>;
    const roundsUsed = totalRounds - roundsReturned;
    
    document.getElementById('rounds_used').value = roundsUsed >= 0 ? roundsUsed : 0;
    
    if (roundsReturned > totalRounds) {
        alert('Rounds returned cannot exceed total rounds issued');
        document.getElementById('rounds_returned').value = totalRounds;
        document.getElementById('rounds_used').value = 0;
    }
}
<?php endif; ?>

document.getElementById('returnForm')?.addEventListener('submit', function(e) {
    <?php if ($type === 'weapon'): ?>
    if (!confirm('Are you sure you want to process this weapon return?')) {
        e.preventDefault();
        return false;
    }
    <?php else: ?>
    if (!confirm('Are you sure you want to process this ammunition return?')) {
        e.preventDefault();
        return false;
    }
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
