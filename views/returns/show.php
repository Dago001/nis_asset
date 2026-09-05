<?php
$title = 'Return Details';
$active = 'returns';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$weaponsList = $weapons ?? [];
$ammunitionList = $ammunition ?? [];
?>

<div class="container-fluid return-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/returns"><i class="fas fa-undo-alt"></i> Returns Management</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Return Details</span>
            </div>
            <h1 class="page-title">
                Return Record
                <span class="header-badge-code" title="Click to copy Return Number" onclick="copyToClipboard('<?php echo Security::escape($return['return_number']); ?>', 'Return #')">
                    <?php echo Security::escape($return['return_number']); ?> <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/returns" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back to Returns</span>
            </a>
            <?php if ($return['status'] == 'Pending' && Auth::can('returns.edit')): ?>
            <a href="<?php echo BASE_URL; ?>/returns/edit/<?php echo $return['id']; ?>" class="pro-btn pro-btn-primary">
                <i class="fas fa-pen-to-square"></i> <span>Edit Return</span>
            </a>
            <?php endif; ?>
            <?php if ($return['status'] == 'Pending' && Auth::can('returns.process')): ?>
            <button type="button" class="pro-btn pro-btn-primary" onclick="processReturn()">
                <i class="fas fa-check-double"></i> <span>Process Return</span>
            </button>
            <?php endif; ?>
            <?php if ($return['status'] == 'Pending' && Auth::can('returns.delete')): ?>
            <a href="<?php echo BASE_URL; ?>/returns/delete/<?php echo $return['id']; ?>" class="pro-btn pro-btn-danger"
               onclick="return confirm('Delete this return? Any weapons/ammunition it recorded as returned will go back to Issued status. This cannot be undone.')">
                <i class="fas fa-trash"></i> <span>Delete</span>
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
            <div class="kpi-icon icon-serial"><i class="fas fa-file-invoice"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Return Reference</span>
                <span class="kpi-value text-mono font-medium"><?php echo Security::escape($return['return_number']); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-shield-halved"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Status State</span>
                <span class="kpi-value">
                    <?php 
                    $rStatus = $return['status'];
                    $rBadge = 'badge-info';
                    if ($rStatus == 'Pending') $rBadge = 'badge-warning';
                    elseif ($rStatus == 'Verified' || $rStatus == 'Completed') $rBadge = 'badge-success';
                    ?>
                    <span class="custom-badge <?php echo $rBadge; ?>"><?php echo Security::escape($rStatus); ?></span>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-boxes-packing"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Return Type</span>
                <span class="kpi-value font-medium">
                    <?php echo Security::escape($return['return_type']); ?>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-user-check"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Returning Officer</span>
                <span class="kpi-value font-medium">
                    <?php echo Security::escape($return['returning_officer_name']); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Status Alert Banners -->
    <?php
    $statusClass = 'alert-info';
    $statusMessage = '';
    if ($return['status'] == 'Pending') { $statusClass = 'alert-warning'; $statusMessage = 'This return record is pending armory verification and processing.'; }
    elseif ($return['status'] == 'Processed') { $statusClass = 'alert-info'; $statusMessage = 'This return has been processed and is awaiting final verification sign-off.'; }
    elseif ($return['status'] == 'Verified' || $return['status'] == 'Completed') { $statusClass = 'alert-success'; $statusMessage = 'This return has been verified, approved, and restocked to inventory.'; }
    ?>
    <div class="pro-alert-banner <?php echo $statusClass; ?>">
        <div class="alert-icon"><i class="fas fa-circle-info"></i></div>
        <div class="alert-body">
            <h4>Return Status: <?php echo Security::escape($return['status']); ?></h4>
            <p><?php echo $statusMessage; ?></p>
        </div>
    </div>

    <!-- Main Content Layout (2 Columns) -->
    <div class="show-layout-grid">
        <!-- Main Column (Left 70%) -->
        <div class="show-main-column">
            <!-- Return Specifications Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sliders"></i> Return Specifications</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Return Number</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($return['return_number']); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Return Date</span>
                            <span class="item-value text-mono font-medium text-success"><?php echo date('d/m/Y', strtotime($return['return_date'])); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Return Category</span>
                            <span class="item-value">
                                <span class="custom-badge badge-neutral"><?php echo Security::escape($return['return_type']); ?></span>
                            </span>
                        </div>
                        <?php if ($return['requisition_id']): ?>
                        <div class="pro-detail-item">
                            <span class="item-label">Associated Requisition</span>
                            <span class="item-value text-mono">
                                <a href="<?php echo BASE_URL; ?>/requisitions/show/<?php echo $return['requisition_id']; ?>">
                                    <?php echo Security::escape($return['requisition_number']); ?>
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Returning Officer Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-user-shield"></i> Returning Officer Custodian</h3>
                </div>
                <div class="pro-card-body">
                    <div class="custodian-info-box">
                        <div class="custodian-avatar">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="custodian-details">
                            <h4><?php echo Security::escape($return['returning_officer_name']); ?></h4>
                            <div class="custodian-pills">
                                <span class="c-pill"><i class="fas fa-award"></i> Rank: <?php echo Security::escape($return['returning_rank']); ?></span>
                                <span class="c-pill"><i class="fas fa-id-card"></i> NIS #: <?php echo Security::escape($return['returning_nis']); ?></span>
                                <span class="c-pill"><i class="fas fa-building"></i> Unit: <?php echo Security::escape($return['returning_unit']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weapons Returned Table -->
            <?php if (!empty($weaponsList)): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-gun"></i> Weapons Returned List</h3>
                </div>
                <div class="pro-card-body pad-none">
                    <div class="table-responsive">
                        <table class="pro-table">
                            <thead>
                                <tr>
                                    <th>Weapon ID</th>
                                    <th>Type</th>
                                    <th>Make / Model</th>
                                    <th>Serial Number</th>
                                    <th>Condition</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($weaponsList as $wIdx => $weapon): ?>
                                <tr class="<?php echo ((($wIdx + 1) % 30 == 0) ? 'print-page-break' : ''); ?>">
                                    <td class="text-mono bold"><?php echo Security::escape($weapon['inventory_code'] ?? $weapon['weapon_id'] ?? '-'); ?></td>
                                    <td><?php echo Security::escape($weapon['weapon_type'] ?? $weapon['type_name'] ?? 'Weapon'); ?></td>
                                    <td><?php echo Security::escape($weapon['make_model'] ?? '-'); ?></td>
                                    <td class="text-mono text-success"><?php echo Security::escape($weapon['serial_number'] ?? $weapon['serial_no'] ?? '-'); ?></td>
                                    <td>
                                        <?php 
                                        $cSt = $weapon['condition'] ?? 'Serviceable';
                                        $cBadg = 'badge-success';
                                        if ($cSt == 'Unserviceable') $cBadg = 'badge-warning';
                                        elseif ($cSt == 'Damaged') $cBadg = 'badge-danger';
                                        ?>
                                        <span class="custom-badge <?php echo $cBadg; ?>"><?php echo Security::escape($cSt); ?></span>
                                    </td>
                                    <td><?php echo Security::escape($weapon['remarks'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ammunition Returned Table -->
            <?php if (!empty($ammunitionList)): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-bullseye"></i> Ammunition Returned List</h3>
                </div>
                <div class="pro-card-body pad-none">
                    <div class="table-responsive">
                        <table class="pro-table">
                            <thead>
                                <tr>
                                    <th>Ammunition ID</th>
                                    <th>Type & Calibre</th>
                                    <th>Batch Number</th>
                                    <th class="text-right">Rounds Returned</th>
                                    <th class="text-right">Rounds Expended</th>
                                    <th>Condition</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ammunitionList as $aIdx => $ammo): ?>
                                <tr class="<?php echo ((($aIdx + 1) % 30 == 0) ? 'print-page-break' : ''); ?>">
                                    <td class="text-mono bold"><?php echo Security::escape($ammo['inventory_code'] ?? $ammo['ammo_id'] ?? '-'); ?></td>
                                    <td>
                                        <?php echo Security::escape($ammo['ammo_type'] ?? 'Ammunition'); ?>
                                        <?php if (!empty($ammo['calibre'])): ?>
                                            (<?php echo Security::escape($ammo['calibre']); ?>)
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-mono"><?php echo Security::escape($ammo['batch_number'] ?? '-'); ?></td>
                                    <td class="text-right font-bold text-success"><?php echo number_format($ammo['rounds_returned'] ?? 0); ?></td>
                                    <td class="text-right font-bold text-danger"><?php echo number_format($ammo['rounds_used'] ?? 0); ?></td>
                                    <td>
                                        <span class="custom-badge badge-neutral"><?php echo Security::escape($ammo['condition'] ?? 'Serviceable'); ?></span>
                                    </td>
                                    <td><?php echo Security::escape($ammo['remarks'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Remarks Card -->
            <?php if (!empty($return['remarks'])): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-note-sticky"></i> Remarks & Official Notes</h3>
                </div>
                <div class="pro-card-body">
                    <div class="remarks-box">
                        <?php echo nl2br(Security::escape($return['remarks'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Column (Right 30%) -->
        <div class="show-sidebar-column">
            <!-- Receiving Officer Card -->
            <?php if (!empty($return['received_by_name'])): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-user-check"></i> Receiving Armory Officer</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Received By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($return['received_by_name']); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Received Time</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i', strtotime($return['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Record Metadata Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Record Metadata</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Logged By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($return['created_by_name'] ?? 'System Admin'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Created Timestamp</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i:s', strtotime($return['created_at'])); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Last Modified</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i:s', strtotime($return['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Actions Bar -->
    <div class="pro-bottom-actions">
        <?php if ($return['status'] == 'Pending' && Auth::can('returns.edit')): ?>
        <a href="<?php echo BASE_URL; ?>/returns/edit/<?php echo $return['id']; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-pen-to-square"></i> <span>Edit Return</span>
        </a>
        <?php endif; ?>
        
        <?php if ($return['status'] == 'Pending' && Auth::can('returns.process')): ?>
        <button type="button" class="pro-btn pro-btn-primary" onclick="processReturn()">
            <i class="fas fa-check-double"></i> <span>Process Return</span>
        </button>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/returns" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to Returns</span>
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


.return-show-container { padding-bottom: 40px; }

.return-show-container .page-header {
    display: flex !important; justify-content: space-between !important; align-items: center !important;
    flex-wrap: wrap !important; gap: 16px !important; background: #ffffff !important; padding: 20px 24px !important;
    border-radius: 12px !important; border: 1px solid #E2E8F0 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.02) !important; margin-bottom: 24px !important;
}

.return-show-container .header-content { flex: 1 1 280px !important; min-width: 0 !important; }
.return-show-container .header-content h1 { font-size: 1.5rem !important; font-weight: 700 !important; color: #0F172A !important; margin: 4px 0 0 0 !important; display: flex !important; align-items: center !important; flex-wrap: wrap !important; gap: 10px !important; }

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
.pro-btn-danger { background: #B42318 !important; color: #FFFFFF !important; }
.pro-btn-danger:hover { background: #8f1c13 !important; }

.return-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; flex-wrap: nowrap !important; }

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
.badge-info    { background: #E1EFFE; color: #1E429F; }
.badge-neutral { background: #F1F5F9; color: #334155; }

.pro-alert-banner { background: white; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-left: 5px solid; }
.pro-alert-banner.alert-warning { border-left-color: transparent; background: #FFFBEB; }
.pro-alert-banner.alert-info { border-left-color: transparent; background: #EFF6FF; }
.pro-alert-banner.alert-success { border-left-color: transparent; background: #F0FDF4; }
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

.custodian-info-box { display: flex; align-items: center; gap: 16px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 16px; }
.custodian-avatar { width: 48px; height: 48px; border-radius: 50%; background: #E2E8F0; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.custodian-details h4 { margin: 0 0 6px 0; font-size: 1.05rem; color: var(--text-dark); }
.custodian-pills { display: flex; gap: 10px; flex-wrap: wrap; }
.c-pill { font-size: 0.8rem; background: white; border: 1px solid #CBD5E1; padding: 2px 10px; border-radius: 15px; color: var(--text-muted); }

.pro-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.pro-table th { background: #F8FAFC; padding: 12px 16px; text-align: left; font-weight: 600; color: var(--text-muted); border-bottom: 1px solid var(--border-light); }
.pro-table td { padding: 12px 16px; border-bottom: 1px solid #F1F5F9; color: var(--text-dark); }

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
    tr.print-page-break { break-after: page !important; page-break-after: always !important; }
    .page-header { border: none !important; box-shadow: none !important; padding: 0 !important; margin-bottom: 15px !important; }
    .pro-card { box-shadow: none !important; border: 1px solid #CBD5E1 !important; break-inside: avoid; }
}

@media (max-width: 1024px) { .show-layout-grid { grid-template-columns: 1fr; } .kpi-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .return-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 16px !important; gap: 14px !important; }
    .return-show-container .header-content { flex: 1 1 100% !important; width: 100% !important; }
    .return-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .return-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid { grid-template-columns: 1fr; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .return-show-container .header-actions { grid-template-columns: 1fr !important; } }
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
