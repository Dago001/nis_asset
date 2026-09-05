<?php
$title = 'Quarterly Audit Details';
$active = 'audit';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

$weaponAudits = $weapons ?? [];
$ammoAudits = $ammunition ?? [];
?>

<div class="container-fluid audit-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/audit/quarterly"><i class="fas fa-clipboard-check"></i> Quarterly Audits</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Audit Details</span>
            </div>
            <h1 class="page-title">
                <?php echo Security::escape(($audit['quarter'] ?? 'Q1') . ' ' . ($audit['year'] ?? date('Y'))); ?> Quarterly Audit
                <span class="header-badge-code" title="Click to copy Audit #" onclick="copyToClipboard('<?php echo Security::escape($audit['audit_number']); ?>', 'Audit #')">
                    <?php echo Security::escape($audit['audit_number']); ?> <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/audit/quarterly" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
            <?php if ($audit['status'] == 'Draft' && Auth::can('audit.edit')): ?>
            <a href="<?php echo BASE_URL; ?>/audit/quarterly/edit/<?php echo $audit['id']; ?>" class="pro-btn pro-btn-primary">
                <i class="fas fa-pen-to-square"></i> <span>Edit Audit</span>
            </a>
            <?php endif; ?>
            
            <?php if ($audit['status'] == 'Submitted' && Auth::can('audit.approve')): ?>
            <button type="button" class="pro-btn pro-btn-primary" onclick="reviewAudit()">
                <i class="fas fa-eye"></i> <span>Review</span>
            </button>
            <button type="button" class="pro-btn pro-btn-primary" onclick="approveAudit()">
                <i class="fas fa-check-circle"></i> <span>Approve</span>
            </button>
            <?php endif; ?>

            <?php if ($audit['status'] == 'Reviewed' && Auth::can('audit.approve')): ?>
            <button type="button" class="pro-btn pro-btn-primary" onclick="approveAudit()">
                <i class="fas fa-check-circle"></i> <span>Approve Audit</span>
            </button>
            <?php endif; ?>

            <button type="button" class="pro-btn pro-btn-outline" onclick="window.print()">
                <i class="fas fa-print"></i> <span>Print Report</span>
            </button>
            <a href="<?php echo BASE_URL; ?>/reports/audit?audit_id=<?php echo $audit['id']; ?>&format=pdf" class="pro-btn pro-btn-outline">
                <i class="fas fa-file-pdf"></i> <span>PDF</span>
            </a>
        </div>
    </div>

    <!-- KPI Summary Metrics Bar -->
    <div class="kpi-metrics-grid">
        <div class="kpi-card">
            <div class="kpi-icon icon-serial"><i class="fas fa-file-signature"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Audit Number</span>
                <span class="kpi-value text-mono font-medium"><?php echo Security::escape($audit['audit_number']); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-shield-halved"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Status State</span>
                <span class="kpi-value">
                    <?php 
                    $aSt = $audit['status'];
                    $aB = 'badge-info';
                    if ($aSt == 'Draft') $aB = 'badge-neutral';
                    elseif ($aSt == 'Submitted') $aB = 'badge-warning';
                    elseif ($aSt == 'Approved') $aB = 'badge-success';
                    ?>
                    <span class="custom-badge <?php echo $aB; ?>"><?php echo Security::escape($aSt); ?></span>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-building"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Audited Command</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($audit['command_name'] ?? 'All Commands'); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Variances Found</span>
                <span class="kpi-value font-medium <?php echo (($audit['weapons_with_variance'] ?? 0) > 0 || ($audit['ammunition_with_variance'] ?? 0) > 0) ? 'text-danger' : 'text-success'; ?>">
                    <?php echo number_format(($audit['weapons_with_variance'] ?? 0) + ($audit['ammunition_with_variance'] ?? 0)); ?> Items
                </span>
            </div>
        </div>
    </div>

    <!-- Status Banners -->
    <?php if (($audit['weapons_with_variance'] ?? 0) > 0 || ($audit['ammunition_with_variance'] ?? 0) > 0): ?>
    <div class="pro-alert-banner alert-danger">
        <div class="alert-icon"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="alert-body">
            <h4>Inventory Discrepancy Alert</h4>
            <p>This audit contains <strong><?php echo $audit['weapons_with_variance'] ?? 0; ?> weapon variance(s)</strong> and <strong><?php echo $audit['ammunition_with_variance'] ?? 0; ?> ammo variance(s)</strong>.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Layout (2 Columns) -->
    <div class="show-layout-grid">
        <!-- Main Column (Left 70%) -->
        <div class="show-main-column">
            <!-- Weapons Audited Table -->
            <div class="pro-card">
                <div class="pro-card-header flex-between">
                    <h3><i class="fas fa-gun"></i> Audited Weapons Checklist</h3>
                    <span class="history-count-badge"><?php echo count($weaponAudits); ?> Records</span>
                </div>
                <div class="pro-card-body pad-none">
                    <?php if (empty($weaponAudits)): ?>
                        <div class="pro-empty-state"><p>No weapon audit items recorded</p></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="pro-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Weapon ID</th>
                                        <th>Serial Number</th>
                                        <th>Expected State</th>
                                        <th>Audited State</th>
                                        <th>Variance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($weaponAudits as $wIdx => $w): ?>
                                    <tr class="<?php echo ((($wIdx + 1) % 30 == 0) ? 'print-page-break' : ''); ?>">
                                        <td class="text-muted small"><?php echo $wIdx + 1; ?></td>
                                        <td class="text-mono font-medium"><?php echo Security::escape($w['weapon_id'] ?? 'N/A'); ?></td>
                                        <td class="text-mono text-success"><?php echo Security::escape($w['serial_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo Security::escape($w['expected_status'] ?? 'Serviceable'); ?></td>
                                        <td><span class="custom-badge badge-neutral"><?php echo Security::escape($w['actual_status'] ?? 'Verified'); ?></span></td>
                                        <td>
                                            <?php if (!empty($w['has_variance'])): ?>
                                                <span class="custom-badge badge-danger">Variance</span>
                                            <?php else: ?>
                                                <span class="custom-badge badge-success">Match</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Remarks Card -->
            <?php if (!empty($audit['remarks'])): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-note-sticky"></i> Auditor Notes & Recommendations</h3>
                </div>
                <div class="pro-card-body">
                    <div class="remarks-box">
                        <?php echo nl2br(Security::escape($audit['remarks'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Column (Right 30%) -->
        <div class="show-sidebar-column">
            <!-- Audit Metadata Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Audit Metadata</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Lead Auditor</span>
                        <span class="side-value font-medium"><?php echo Security::escape($audit['created_by_name'] ?? 'System Auditor'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Start Date</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y', strtotime($audit['start_date'] ?? $audit['created_at'])); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">End Date</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y', strtotime($audit['end_date'] ?? $audit['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Actions Bar -->
    <div class="pro-bottom-actions">
        <?php if ($audit['status'] == 'Draft' && Auth::can('audit.edit')): ?>
        <a href="<?php echo BASE_URL; ?>/audit/quarterly/edit/<?php echo $audit['id']; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-pen-to-square"></i> <span>Edit Audit</span>
        </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/audit/quarterly" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to Audits</span>
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


.audit-show-container { padding-bottom: 40px; }

.audit-show-container .page-header {
    display: flex !important; justify-content: space-between !important; align-items: center !important;
    flex-wrap: wrap !important; gap: 16px !important; background: #ffffff !important; padding: 20px 24px !important;
    border-radius: 12px !important; border: 1px solid #E2E8F0 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.02) !important; margin-bottom: 24px !important;
}

.audit-show-container .header-content { flex: 1 1 280px !important; min-width: 0 !important; }
.audit-show-container .header-content h1 { font-size: 1.5rem !important; font-weight: 700 !important; color: #0F172A !important; margin: 4px 0 0 0 !important; display: flex !important; align-items: center !important; flex-wrap: wrap !important; gap: 10px !important; }

.header-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px; }
.header-breadcrumb a { color: var(--nis-emerald); text-decoration: none; font-weight: 500; }
.breadcrumb-separator { font-size: 0.7rem; color: #94A3B8; }

.header-badge-code { display: inline-flex; align-items: center; gap: 6px; background: #F1F5F9; color: var(--nis-forest); border: 1px solid #CBD5E1; font-family: 'SF Mono', monospace; font-size: 0.95rem; padding: 3px 10px; border-radius: 6px; cursor: pointer; }

.pro-btn {
    display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important;
    padding: 9px 18px !important; font-size: 0.88rem !important; font-weight: 600 !important; border-radius: 8px !important;
    white-space: nowrap !important; height: 40px !important; box-sizing: border-box !important; text-decoration: none !important;
    line-height: 1 !important; cursor: pointer !important; user-select: none !important; outline: none !important; border: 1px solid transparent !important;
}

.pro-btn span { display: inline-block !important; color: inherit !important; background: transparent !important; }
.pro-btn i { font-size: 0.95rem !important; color: inherit !important; }

.pro-btn-secondary { background: #F1F5F9 !important; color: #334155 !important; border-color: #CBD5E1 !important; }
.pro-btn-primary { background: #134617 !important; color: #FFFFFF !important; }
.pro-btn-outline { background: #FFFFFF !important; color: #0F172A !important; border-color: #94A3B8 !important; }

.audit-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; flex-wrap: nowrap !important; }

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
.badge-neutral { background: #F1F5F9; color: #334155; }

.pro-alert-banner { background: white; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-left: 5px solid; }
.pro-alert-banner.alert-danger { border-left-color: #E11D48; background: #FFF1F2; }
.alert-icon i { font-size: 1.8rem; }

.show-layout-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 24px; margin-bottom: 24px; }
.pro-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); margin-bottom: 24px; overflow: hidden; }
.pro-card-header { padding: 16px 20px; background: #F8FAFC; border-bottom: 1px solid var(--border-light); }
.pro-card-header h3 { margin: 0; font-size: 1.05rem; font-weight: 600; color: var(--nis-forest); display: flex; align-items: center; gap: 10px; }
.pro-card-body { padding: 20px; }
.pro-card-body.pad-none { padding: 0; }

.pro-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px 24px; }
.pro-detail-item { display: flex; flex-direction: column; gap: 4px; }
.item-label { font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
.item-value { font-size: 0.95rem; color: var(--text-dark); }

.pro-side-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F1F5F9; }
.pro-side-item:last-child { border-bottom: none; }
.side-label { font-size: 0.85rem; color: var(--text-muted); }
.side-value { font-size: 0.9rem; color: var(--text-dark); text-align: right; }

.pro-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.pro-table th { background: #F8FAFC; padding: 12px 16px; text-align: left; font-weight: 600; color: var(--text-muted); border-bottom: 1px solid var(--border-light); }
.pro-table td { padding: 12px 16px; border-bottom: 1px solid #F1F5F9; color: var(--text-dark); }

.remarks-box { background: #F8FAFC; border-left: 4px solid var(--nis-emerald); padding: 14px 18px; border-radius: 0 8px 8px 0; font-size: 0.92rem; line-height: 1.6; }
.text-mono { font-family: 'SF Mono', monospace; }
.bold { font-weight: 700; }
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
.font-bold { font-weight: 700; }
.flex-between { display: flex; justify-content: space-between; align-items: center; }
.history-count-badge { font-size: 0.75rem; background: #E2E8F0; color: #475569; padding: 3px 10px; border-radius: 12px; font-weight: 600; }
.pro-empty-state { text-align: center; padding: 30px; color: var(--text-muted); }
.pro-bottom-actions { display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-light); flex-wrap: wrap; }

.copy-toast { position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: white; padding: 10px 18px; border-radius: 8px; font-size: 0.88rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); opacity: 0; transform: translateY(10px); transition: all 0.3s ease; pointer-events: none; z-index: 9999; }
.copy-toast.show { opacity: 1; transform: translateY(0); }

@media print {
    .no-print, .header-actions, .pro-bottom-actions, .sidebar, footer { display: none !important; }
    tr.print-page-break { break-after: page !important; page-break-after: always !important; }
    .page-header { border: none !important; box-shadow: none !important; padding: 0 !important; margin-bottom: 15px !important; }
    .pro-card { box-shadow: none !important; border: 1px solid #CBD5E1 !important; break-inside: avoid; }
}

@media (max-width: 1024px) { .show-layout-grid { grid-template-columns: 1fr; } .kpi-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .audit-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 16px !important; gap: 14px !important; }
    .audit-show-container .header-content { flex: 1 1 100% !important; width: 100% !important; }
    .audit-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .audit-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid { grid-template-columns: 1fr; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .audit-show-container .header-actions { grid-template-columns: 1fr !important; } }
</style>

<script>
function reviewAudit() {
    if (confirm('Mark this audit report as reviewed?')) {
        window.location.href = '<?php echo BASE_URL; ?>/audit/quarterly/review/<?php echo $audit['id']; ?>';
    }
}
function approveAudit() {
    if (confirm('Approve and finalize this quarterly audit report?')) {
        window.location.href = '<?php echo BASE_URL; ?>/audit/quarterly/approve/<?php echo $audit['id']; ?>';
    }
}
function copyToClipboard(text, label) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.getElementById('copyToast');
        toast.innerHTML = `<i class="fas fa-check-circle" style="color:#4ADE80;"></i> Copied ${label}: <strong>${text}</strong>`;
        toast.classList.add('show');
        setTimeout(() => { toast.classList.remove('show'); }, 3000);
    });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
