<?php
$title = 'Issue Details';
$active = 'weapon_issue';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$issue = isset($issue) ? $issue : [];
$type = isset($type) ? $type : 'weapon';
$issueStatus = $type === 'weapon' ? ($issue['status'] ?? 'Issued') : ($issue['status'] ?? 'Issued');
$isOverdue = ($type === 'weapon' && $issueStatus == 'Issued' && !empty($issue['expected_return_date']) && strtotime($issue['expected_return_date']) < time());
?>

<div class="container-fluid issue-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/weapon_issue/history"><i class="fas fa-hand-holding-hand"></i> Weapon & Ammo Issue</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Issue Details</span>
            </div>
            <h1 class="page-title">
                <?php echo $type === 'weapon' ? 'Weapon Issue Log' : 'Ammunition Issue Log'; ?>
                <span class="header-badge-code" title="Click to copy Issue ID" onclick="copyToClipboard('#<?php echo Security::escape($issue['id']); ?>', 'Issue ID')">
                    #<?php echo Security::escape($issue['id']); ?> <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/weapon_issue/history" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back to History</span>
            </a>
            <?php if (($type === 'weapon' && $issueStatus == 'Issued') || ($type === 'ammunition' && $issueStatus == 'Issued')): ?>
            <a href="<?php echo BASE_URL; ?>/weapon_issue/return/<?php echo $issue['id']; ?>?type=<?php echo $type; ?>" class="pro-btn pro-btn-primary">
                <i class="fas fa-undo-alt"></i> <span>Process Return</span>
            </a>
            <?php endif; ?>
            <button type="button" class="pro-btn pro-btn-outline" onclick="window.print()">
                <i class="fas fa-print"></i> <span>Print Record</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Metrics Bar -->
    <div class="kpi-metrics-grid">
        <div class="kpi-card">
            <div class="kpi-icon icon-serial"><i class="fas fa-receipt"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Issue Reference</span>
                <span class="kpi-value text-mono font-medium">#<?php echo Security::escape($issue['id']); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-shield-halved"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Current Status</span>
                <span class="kpi-value">
                    <?php 
                    $stBadge = 'badge-success';
                    if ($issueStatus == 'Issued') $stBadge = 'badge-warning';
                    if ($isOverdue) { $stBadge = 'badge-danger'; $issueStatus = 'Overdue'; }
                    ?>
                    <span class="custom-badge <?php echo $stBadge; ?>"><?php echo Security::escape($issueStatus); ?></span>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-gun"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Issued Item</span>
                <span class="kpi-value font-medium">
                    <?php if ($type === 'weapon'): ?>
                        <?php echo Security::escape($issue['weapon_id']); ?> (<?php echo Security::escape($issue['make_model']); ?>)
                    <?php else: ?>
                        <?php echo Security::escape($issue['ammo_id']); ?> (<?php echo number_format($issue['total_rounds'] ?? 0); ?> rounds)
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-user-check"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Issued To Officer</span>
                <span class="kpi-value font-medium">
                    <?php echo Security::escape($issue['officer_name'] ?? $issue['issued_to'] ?? 'N/A'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Status Alert Banners -->
    <?php if ($isOverdue): ?>
    <div class="pro-alert-banner alert-danger">
        <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="alert-body">
            <h4>Weapon Return Overdue</h4>
            <p>This weapon was expected back on <strong><?php echo date('d/m/Y', strtotime($issue['expected_return_date'])); ?></strong>. Contact the custodian officer immediately.</p>
        </div>
    </div>
    <?php elseif ($issueStatus == 'Issued'): ?>
    <div class="pro-alert-banner alert-warning">
        <div class="alert-icon"><i class="fas fa-clock"></i></div>
        <div class="alert-body">
            <h4>Active Issue Out</h4>
            <p>This item is currently out on active duty custody.</p>
        </div>
    </div>
    <?php elseif ($issueStatus == 'Returned'): ?>
    <div class="pro-alert-banner alert-success">
        <div class="alert-icon"><i class="fas fa-circle-check"></i></div>
        <div class="alert-body">
            <h4>Returned & Restocked</h4>
            <p>Item has been returned to the armory and logged in inventory.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Layout (2 Columns) -->
    <div class="show-layout-grid">
        <!-- Main Column (Left 70%) -->
        <div class="show-main-column">
            <?php if ($type === 'weapon'): ?>
            <!-- Weapon Info Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-gun"></i> Weapon Information</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Weapon ID Code</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($issue['weapon_id']); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Serial Number</span>
                            <span class="item-value text-mono font-medium text-success"><?php echo Security::escape($issue['serial_no']); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Make & Model</span>
                            <span class="item-value font-medium"><?php echo Security::escape($issue['make_model']); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Type & Calibre</span>
                            <span class="item-value font-medium"><?php echo Security::escape($issue['type_name'] ?? 'N/A'); ?> (<?php echo Security::escape($issue['calibre_name'] ?? 'N/A'); ?>)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Officer Info Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-user-shield"></i> Officer Custodian</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Officer Name</span>
                            <span class="item-value font-medium"><?php echo Security::escape($issue['officer_name']); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Rank</span>
                            <span class="item-value font-medium"><?php echo Security::escape($issue['officer_rank']); ?></span>
                        </div>
                        <?php if (!empty($issue['officer_nis'])): ?>
                        <div class="pro-detail-item">
                            <span class="item-label">NIS Number</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($issue['officer_nis']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="pro-detail-item">
                            <span class="item-label">Station Unit</span>
                            <span class="item-value font-medium"><?php echo Security::escape($issue['unit']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- Ammunition Info Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-bullseye"></i> Ammunition Information</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Ammunition ID</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($issue['ammo_id']); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Type & Calibre</span>
                            <span class="item-value font-medium"><?php echo Security::escape($issue['ammo_type'] ?? 'N/A'); ?> (<?php echo Security::escape($issue['calibre'] ?? 'N/A'); ?>)</span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Units Issued</span>
                            <span class="item-value font-medium"><?php echo number_format($issue['units_issued'] ?? 1); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Total Rounds Issued</span>
                            <span class="item-value font-medium text-success"><?php echo number_format($issue['total_rounds'] ?? 0); ?> rounds</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Remarks Card -->
            <?php if (!empty($issue['remarks'])): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-note-sticky"></i> Remarks & Official Notes</h3>
                </div>
                <div class="pro-card-body">
                    <div class="remarks-box">
                        <?php echo nl2br(Security::escape($issue['remarks'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Column (Right 30%) -->
        <div class="show-sidebar-column">
            <!-- Issue Info Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Issue Information</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Issue Date</span>
                        <span class="side-value text-mono"><?php echo date('d/m/Y', strtotime($issue['issue_date'])); ?></span>
                    </div>
                    <?php if (!empty($issue['requisition_number'])): ?>
                    <div class="pro-side-item">
                        <span class="side-label">Requisition #</span>
                        <span class="side-value text-mono">
                            <a href="<?php echo BASE_URL; ?>/requisitions/show/<?php echo $issue['requisition_id']; ?>">
                                <?php echo Security::escape($issue['requisition_number']); ?>
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="pro-side-item">
                        <span class="side-label">Purpose</span>
                        <span class="side-value"><?php echo Security::escape($issue['purpose']); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Approved By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($issue['approved_by']); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Issued By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($issue['issued_by_name'] ?? 'Armory Officer'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Return Info Card -->
            <?php if (($type === 'weapon' && !empty($issue['actual_return_date'])) || ($type === 'ammunition' && !empty($issue['return_date']))): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-undo-alt"></i> Return Breakdown</h3>
                </div>
                <div class="pro-card-body">
                    <?php if ($type === 'weapon'): ?>
                    <div class="pro-side-item">
                        <span class="side-label">Actual Return Date</span>
                        <span class="side-value text-mono"><?php echo date('d/m/Y', strtotime($issue['actual_return_date'])); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Return Condition</span>
                        <span class="side-value font-semibold"><?php echo Security::escape($issue['return_condition']); ?></span>
                    </div>
                    <?php else: ?>
                    <div class="pro-side-item">
                        <span class="side-label">Return Date</span>
                        <span class="side-value text-mono"><?php echo date('d/m/Y', strtotime($issue['return_date'])); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Rounds Returned</span>
                        <span class="side-value font-bold text-success"><?php echo number_format($issue['rounds_returned'] ?? 0); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Rounds Expended</span>
                        <span class="side-value font-bold text-danger"><?php echo number_format($issue['rounds_used'] ?? 0); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Actions Bar -->
    <div class="pro-bottom-actions">
        <?php if (($type === 'weapon' && $issueStatus == 'Issued') || ($type === 'ammunition' && $issueStatus == 'Issued')): ?>
        <a href="<?php echo BASE_URL; ?>/weapon_issue/return/<?php echo $issue['id']; ?>?type=<?php echo $type; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-undo-alt"></i> <span>Process Return</span>
        </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/weapon_issue/history" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to History</span>
        </a>
    </div>
</div>

<div id="copyToast" class="copy-toast"></div>

<!-- CSS Styling & Mobile Responsiveness -->
<style>
:root {
    --nis-forest: #134617;
    --nis-emerald: #2E7D32;
    --card-bg: #FFFFFF;
    --border-light: #E2E8F0;
    --text-dark: #0F172A;
    --text-muted: #64748B;
}
[data-theme="dark"] {
    --nis-forest: #299631;
    --nis-emerald: #52bf57;
    --card-bg: #1f1f1f;
    --border-light: #2b323b;
    --text-dark: #d9dde8;
    --text-muted: #dee0e3;
}


.issue-show-container { padding-bottom: 40px; }

.issue-show-container .page-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    flex-wrap: wrap !important;
    gap: 16px !important;
    background: #ffffff !important;
    padding: 20px 24px !important;
    border-radius: 12px !important;
    border: 1px solid #E2E8F0 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02) !important;
    margin-bottom: 24px !important;
}

.issue-show-container .header-content { flex: 1 1 280px !important; min-width: 0 !important; }
.issue-show-container .header-content h1 {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: #0F172A !important;
    margin: 4px 0 0 0 !important;
    display: flex !important;
    align-items: center !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
}

.header-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px; }
.header-breadcrumb a { color: var(--nis-emerald); text-decoration: none; font-weight: 500; }
.breadcrumb-separator { font-size: 0.7rem; color: #94A3B8; }

.header-badge-code {
    display: inline-flex; align-items: center; gap: 6px; background: #F1F5F9; color: var(--nis-forest);
    border: 1px solid #CBD5E1; font-family: 'SF Mono', monospace; font-size: 0.95rem; padding: 3px 10px; border-radius: 6px; cursor: pointer;
}

.pro-btn {
    display: inline-flex !important; align-items: center !important; justify-content: center !important;
    gap: 8px !important; padding: 9px 18px !important; font-size: 0.88rem !important; font-weight: 600 !important;
    border-radius: 8px !important; white-space: nowrap !important; height: 40px !important; box-sizing: border-box !important;
    text-decoration: none !important; line-height: 1 !important; cursor: pointer !important; user-select: none !important; outline: none !important; border: 1px solid transparent !important;
}

.pro-btn span { display: inline-block !important; color: inherit !important; background: transparent !important; }
.pro-btn i { font-size: 0.95rem !important; color: inherit !important; }

.pro-btn-secondary { background: #F1F5F9 !important; color: #334155 !important; border-color: #CBD5E1 !important; }
.pro-btn-primary { background: #134617 !important; color: #FFFFFF !important; }
.pro-btn-outline { background: #FFFFFF !important; color: #0F172A !important; border-color: #94A3B8 !important; }

.issue-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; flex-wrap: nowrap !important; }

.kpi-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
.kpi-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.icon-serial { background: #E0F2FE; color: #0284C7; }
.icon-condition { background: #DCFCE7; color: #16A34A; }
.icon-location { background: #FEF3C7; color: #D97706; }
.icon-calibre { background: #F3E8FF; color: #9333EA; }

.kpi-details { display: flex; flex-direction: column; gap: 2px; }
.kpi-label { font-size: 0.78rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted); }
.kpi-value { font-size: 1rem; color: var(--text-dark); }

.custom-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; }
.badge-success { background: #DEF7EC; color: #03543F; }
.badge-warning { background: #FEF08A; color: #713F12; }
.badge-danger  { background: #FDE8E8; color: #9B1C1C; }

.pro-alert-banner { background: white; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-left: 5px solid; }
.pro-alert-banner.alert-danger { border-left-color: transparent; background: #FFF1F2; }
.pro-alert-banner.alert-warning { border-left-color: transparent; background: #FFFBEB; }
.pro-alert-banner.alert-success { border-left-color: transparent; background: #F0FDF4; }
.alert-icon i { font-size: 1.8rem; }

.show-layout-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 24px; margin-bottom: 24px; }
.pro-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); margin-bottom: 24px; overflow: hidden; }
.pro-card-header { padding: 16px 20px; background: #F8FAFC; border-bottom: 1px solid var(--border-light); }
.pro-card-header h3 { margin: 0; font-size: 1.05rem; font-weight: 600; color: var(--nis-forest); display: flex; align-items: center; gap: 10px; }
.pro-card-body { padding: 20px; }

.pro-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px 24px; }
.pro-detail-item { display: flex; flex-direction: column; gap: 4px; }
.item-label { font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
.item-value { font-size: 0.95rem; color: var(--text-dark); }

.pro-side-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F1F5F9; }
.pro-side-item:last-child { border-bottom: none; }
.side-label { font-size: 0.85rem; color: var(--text-muted); }
.side-value { font-size: 0.9rem; color: var(--text-dark); text-align: right; }

.remarks-box { background: #F8FAFC; border-left: 4px solid var(--nis-emerald); padding: 14px 18px; border-radius: 0 8px 8px 0; font-size: 0.92rem; line-height: 1.6; }
.text-mono { font-family: 'SF Mono', monospace; }
.bold { font-weight: 700; }
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
.font-bold { font-weight: 700; }
.pro-bottom-actions { display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-light); flex-wrap: wrap; }

.copy-toast { position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: white; padding: 10px 18px; border-radius: 8px; font-size: 0.88rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); opacity: 0; transform: translateY(10px); transition: all 0.3s ease; pointer-events: none; z-index: 9999; }
.copy-toast.show { opacity: 1; transform: translateY(0); }

@media print {
    .no-print, .header-actions, .pro-bottom-actions, .sidebar, footer { display: none !important; }
    .page-header { border: none !important; box-shadow: none !important; padding: 0 !important; margin-bottom: 15px !important; }
    .pro-card { box-shadow: none !important; border: 1px solid #CBD5E1 !important; break-inside: avoid; }
}

@media (max-width: 1024px) { .show-layout-grid { grid-template-columns: 1fr; } .kpi-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .issue-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 16px !important; gap: 14px !important; }
    .issue-show-container .header-content { flex: 1 1 100% !important; width: 100% !important; }
    .issue-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .issue-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid { grid-template-columns: 1fr; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .issue-show-container .header-actions { grid-template-columns: 1fr !important; } }
</style>

<script>
function copyToClipboard(text, label) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.getElementById('copyToast');
        toast.innerHTML = `<i class="fas fa-check-circle" style="color:#4ADE80;"></i> Copied ${label}: <strong>${text}</strong>`;
        toast.classList.add('show');
        setTimeout(() => { toast.classList.remove('show'); }, 3000);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
