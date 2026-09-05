<?php
$title = 'Ammunition Details';
$active = 'ammunition';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$historyList = $issueHistory ?? [];
$totalHistory = count($historyList);
$historyPages = array_chunk($historyList, 30);
$totalPages = count($historyPages);
$balance = ($ammo['quantity_received'] ?? 0) - ($ammo['quantity_issued'] ?? 0);
?>

<div class="container-fluid ammo-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/ammunition"><i class="fas fa-boxes-stacked"></i> Ammunition Stock</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Ammunition Details</span>
            </div>
            <h1>
                <?php echo Security::escape($ammo['ammo_type'] ?? 'Ammunition Batch'); ?>
                <span class="header-badge-code" title="Click to copy Ammunition ID" onclick="copyToClipboard('<?php echo Security::escape($ammo['ammo_id']); ?>', 'Ammunition ID')">
                    <?php echo Security::escape($ammo['ammo_id']); ?> <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/ammunition" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
            <?php if (Auth::can('ammunition.edit')): ?>
            <a href="<?php echo BASE_URL; ?>/ammunition/edit/<?php echo $ammo['id']; ?>" class="pro-btn pro-btn-primary">
                <i class="fas fa-pen-to-square"></i> <span>Edit Record</span>
            </a>
            <?php endif; ?>
            <button type="button" class="pro-btn pro-btn-outline" onclick="window.print()">
                <i class="fas fa-print"></i> <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Metrics Bar -->
    <div class="kpi-metrics-grid">
        <div class="kpi-card">
            <div class="kpi-icon icon-serial"><i class="fas fa-layer-group"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Batch Number</span>
                <span class="kpi-value text-mono" onclick="copyToClipboard('<?php echo Security::escape($ammo['batch_number'] ?? 'N/A'); ?>', 'Batch Number')" style="cursor:pointer;" title="Click to copy">
                    <?php echo Security::escape($ammo['batch_number'] ?? 'N/A'); ?> <i class="fas fa-copy copy-subicon"></i>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-cubes-stacked"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Current Stock Balance</span>
                <span class="kpi-value font-medium <?php echo $balance < 100 ? 'text-danger' : 'text-success'; ?>">
                    <?php echo number_format($balance); ?> rounds
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-warehouse"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Storage Form & Location</span>
                <span class="kpi-value font-medium">
                    <?php echo Security::escape($ammo['storage_form'] ?? 'Boxed'); ?> (<?php echo Security::escape($ammo['storage_location'] ?? 'Armoury'); ?>)
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-bullseye"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Calibre</span>
                <span class="kpi-value font-medium">
                    <?php echo Security::escape($ammo['calibre'] ?? 'N/A'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Status Alerts -->
    <?php if ($balance < 100): ?>
    <div class="pro-alert-banner alert-warning">
        <div class="alert-icon"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="alert-body">
            <h4>Low Ammunition Stock Alert</h4>
            <p>Current balance is low at <strong><?php echo number_format($balance); ?> rounds</strong>. Please initiate stock reorder requisition.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php 
    if (!empty($ammo['expiry_date'])):
        $expiryDate = strtotime($ammo['expiry_date']);
        $daysToExpiry = round(($expiryDate - time()) / (60 * 60 * 24));
        if ($daysToExpiry <= 90 && $daysToExpiry >= 0):
    ?>
    <div class="pro-alert-banner alert-warning">
        <div class="alert-icon"><i class="fas fa-clock"></i></div>
        <div class="alert-body">
            <h4>Ammunition Expiry Warning</h4>
            <p>This batch expires in <strong><?php echo $daysToExpiry; ?> days</strong> (<?php echo date('d/m/Y', strtotime($ammo['expiry_date'])); ?>). Plan usage or inspection.</p>
        </div>
    </div>
    <?php elseif ($daysToExpiry < 0): ?>
    <div class="pro-alert-banner alert-danger">
        <div class="alert-icon"><i class="fas fa-skull-crossbones"></i></div>
        <div class="alert-body">
            <h4>Expired Ammunition Batch</h4>
            <p>This batch expired on <strong><?php echo date('d/m/Y', strtotime($ammo['expiry_date'])); ?></strong>. Quarantine and dispose immediately.</p>
        </div>
    </div>
    <?php 
        endif;
    endif;
    ?>

    <!-- Main Content Layout (2 Columns) -->
    <div class="show-layout-grid">
        <!-- Main Column (Left 70%) -->
        <div class="show-main-column">
            <!-- Basic Specs Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sliders"></i> Ammunition Specifications</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Ammunition ID</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($ammo['ammo_id']); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Batch / Lot Number</span>
                            <span class="item-value text-mono font-medium text-success"><?php echo Security::escape($ammo['batch_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Type Category</span>
                            <span class="item-value font-medium">
                                <span class="custom-badge badge-neutral"><?php echo Security::escape($ammo['ammo_type'] ?? 'Other'); ?></span>
                                <?php if (!empty($ammo['ammo_type_other'])): ?>
                                    <small class="text-muted">(<?php echo Security::escape($ammo['ammo_type_other']); ?>)</small>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Calibre</span>
                            <span class="item-value font-medium">
                                <?php echo Security::escape($ammo['calibre'] ?? 'N/A'); ?>
                                <?php if (!empty($ammo['calibre_other'])): ?>
                                    <small class="text-muted">(<?php echo Security::escape($ammo['calibre_other']); ?>)</small>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quantity & Inventory Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-boxes-packing"></i> Quantity & Inventory Breakdown</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Storage Form</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ammo['storage_form'] ?? 'Boxed'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Quantity Received</span>
                            <span class="item-value font-medium"><?php echo number_format($ammo['quantity_received'] ?? 0); ?> rounds</span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Quantity Issued</span>
                            <span class="item-value font-medium"><?php echo number_format($ammo['quantity_issued'] ?? 0); ?> rounds</span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Current Available Balance</span>
                            <span class="item-value font-medium <?php echo $balance < 100 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo number_format($balance); ?> rounds
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Issue History Card (Paginated at 30 records per page) -->
            <div class="pro-card" id="historyCardSection">
                <div class="pro-card-header flex-between">
                    <h3><i class="fas fa-clock-rotate-left"></i> Ammunition Issue History</h3>
                    <div class="history-header-meta">
                        <span class="history-count-badge"><?php echo $totalHistory; ?> Total Issues</span>
                        <?php if ($totalPages > 1): ?>
                            <span class="page-indicator-badge">Page <span id="currentPageNum">1</span> of <?php echo $totalPages; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="pro-card-body pad-none">
                    <?php if (empty($historyList)): ?>
                        <div class="pro-empty-state">
                            <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                            <p>No issue history recorded for this ammunition batch</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="pro-table" id="historyLogTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Issue Date</th>
                                        <th>Officer Name</th>
                                        <th>Rank</th>
                                        <th>Unit</th>
                                        <th>Purpose</th>
                                        <th class="text-right">Rounds Issued</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $globalIndex = 1;
                                    foreach ($historyPages as $pageIdx => $pageRecords): 
                                    ?>
                                        <?php foreach ($pageRecords as $rowIdx => $issue): ?>
                                        <tr class="history-row history-page-<?php echo ($pageIdx + 1); ?> <?php echo (($globalIndex % 30 == 0) ? 'print-page-break' : ''); ?>" 
                                            style="<?php echo ($pageIdx > 0 ? 'display: none;' : ''); ?>">
                                            <td class="text-muted small"><?php echo $globalIndex; ?></td>
                                            <td class="text-mono"><?php echo date('d/m/Y', strtotime($issue['issue_date'])); ?></td>
                                            <td class="font-medium"><?php echo Security::escape($issue['officer_name']); ?></td>
                                            <td><?php echo Security::escape($issue['officer_rank']); ?></td>
                                            <td><?php echo Security::escape($issue['unit']); ?></td>
                                            <td><?php echo Security::escape($issue['purpose']); ?></td>
                                            <td class="text-right font-bold"><?php echo number_format($issue['rounds_issued']); ?></td>
                                        </tr>
                                        <?php 
                                        $globalIndex++;
                                        endforeach; 
                                        ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                        <div class="history-pagination-wrapper no-print">
                            <div class="pagination-info">
                                Showing 30 records per page (Total <?php echo $totalHistory; ?> records)
                            </div>
                            <div class="pagination-controls">
                                <button type="button" class="pg-btn pg-prev" id="btnPrevHistory" onclick="changeHistoryPage(-1)" disabled>
                                    <i class="fas fa-chevron-left"></i> Previous
                                </button>
                                <div class="pg-numbers" id="pgNumbersList">
                                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                        <button type="button" class="pg-num <?php echo $p === 1 ? 'active' : ''; ?>" 
                                                onclick="goToHistoryPage(<?php echo $p; ?>)" id="pgNumBtn_<?php echo $p; ?>">
                                            <?php echo $p; ?>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                                <button type="button" class="pg-btn pg-next" id="btnNextHistory" onclick="changeHistoryPage(1)">
                                    Next <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Remarks Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-note-sticky"></i> Remarks & Official Notes</h3>
                </div>
                <div class="pro-card-body">
                    <div class="remarks-box">
                        <?php echo nl2br(Security::escape($ammo['remarks'] ?? 'No special administrative remarks logged for this batch.')); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column (Right 30%) -->
        <div class="show-sidebar-column">
            <!-- Storage Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-warehouse"></i> Storage Location</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Location Category</span>
                        <span class="side-value font-semibold">
                            <?php echo Security::escape($ammo['storage_location'] ?? 'Armoury'); ?>
                        </span>
                    </div>
                    <?php if (!empty($ammo['zone_name'])): ?>
                    <div class="pro-side-item">
                        <span class="side-label">Zonal Command</span>
                        <span class="side-value font-semibold"><?php echo Security::escape($ammo['zone_name']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($ammo['command_name'])): ?>
                    <div class="pro-side-item">
                        <span class="side-label">Command / Formation</span>
                        <span class="side-value font-semibold"><?php echo Security::escape($ammo['command_name']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($ammo['storage_location_other'])): ?>
                    <div class="pro-side-item">
                        <span class="side-label">Specified Location</span>
                        <span class="side-value font-semibold"><?php echo Security::escape($ammo['storage_location_other']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Manufacturer Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-industry"></i> Manufacturer & Expiry</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Manufacturer</span>
                        <span class="side-value font-medium"><?php echo Security::escape($ammo['manufacturer'] ?? 'Not specified'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Manufactured Date</span>
                        <span class="side-value text-mono">
                            <?php echo !empty($ammo['date_manufactured']) ? date('d/m/Y', strtotime($ammo['date_manufactured'])) : 'Not specified'; ?>
                        </span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Expiry Date</span>
                        <span class="side-value text-mono">
                            <?php if (!empty($ammo['expiry_date'])): ?>
                                <?php echo date('d/m/Y', strtotime($ammo['expiry_date'])); ?>
                                <?php
                                $dToExp = round((strtotime($ammo['expiry_date']) - time()) / (60 * 60 * 24));
                                if ($dToExp <= 90 && $dToExp >= 0) {
                                    echo '<br><span class="insp-pill insp-warn">' . $dToExp . ' days left</span>';
                                } elseif ($dToExp < 0) {
                                    echo '<br><span class="insp-pill insp-danger">Expired</span>';
                                }
                                ?>
                            <?php else: ?>
                                Not specified
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Condition Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-clipboard-check"></i> Batch Condition</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Condition Status</span>
                        <span class="side-value">
                            <?php 
                            $cState = $ammo['condition'] ?? 'Serviceable';
                            $cBadge = 'badge-success';
                            if ($cState == 'Unserviceable') $cBadge = 'badge-warning';
                            elseif ($cState == 'Condemned') $cBadge = 'badge-danger';
                            ?>
                            <span class="custom-badge <?php echo $cBadge; ?>"><?php echo Security::escape($cState); ?></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Metadata Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Record Metadata</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Logged By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($ammo['created_by_name'] ?? 'System Admin'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Created Timestamp</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i:s', strtotime($ammo['created_at'])); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Last Modified</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i:s', strtotime($ammo['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Actions Bar -->
    <div class="pro-bottom-actions">
        <?php if (Auth::can('ammunition.edit')): ?>
        <a href="<?php echo BASE_URL; ?>/ammunition/edit/<?php echo $ammo['id']; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-pen-to-square"></i> <span>Edit Ammunition</span>
        </a>
        <?php endif; ?>
        
        <?php if (Auth::can('ammunition.delete')): ?>
        <a href="<?php echo BASE_URL; ?>/ammunition/delete/<?php echo $ammo['id']; ?>" class="pro-btn pro-btn-danger" 
           onclick="return confirm('Are you sure you want to delete this ammunition record? This operation cannot be undone.')">
            <i class="fas fa-trash-can"></i> <span>Delete Record</span>
        </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/ammunition" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to Ammunition</span>
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


.ammo-show-container { padding-bottom: 40px; }

.ammo-show-container .page-header {
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

.ammo-show-container .header-content { flex: 1 1 280px !important; min-width: 0 !important; }
.ammo-show-container .header-content h1 {
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
.pro-btn-danger { background: #DC2626 !important; color: #FFFFFF !important; }

.ammo-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; flex-wrap: nowrap !important; }

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
.pro-alert-banner.alert-danger { border-left-color: transparent; background: #FFF1F2; }
.pro-alert-banner.alert-warning { border-left-color: transparent; background: #FFFBEB; }
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

.history-pagination-wrapper { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; background: #F8FAFC; border-top: 1px solid var(--border-light); flex-wrap: wrap; gap: 12px; }
.pagination-controls { display: flex; align-items: center; gap: 8px; }
.pg-btn { display: inline-flex; align-items: center; gap: 6px; background: #FFFFFF; border: 1px solid #CBD5E1; padding: 6px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; }
.pg-num { background: #FFFFFF; border: 1px solid #CBD5E1; min-width: 32px; height: 32px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.pg-num.active { background: var(--nis-forest); color: #FFFFFF; border-color: var(--nis-forest); }

.remarks-box { background: #F8FAFC; border-left: 4px solid var(--nis-emerald); padding: 14px 18px; border-radius: 0 8px 8px 0; font-size: 0.92rem; line-height: 1.6; }
.insp-pill { display: inline-block; font-size: 0.75rem; padding: 2px 8px; border-radius: 10px; margin-top: 4px; font-weight: 600; }
.insp-warn { background: #FEF9C3; color: #854D0E; }
.insp-danger { background: #FEE2E2; color: #991B1B; }

.text-mono { font-family: 'SF Mono', monospace; }
.bold { font-weight: 700; }
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
.font-bold { font-weight: 700; }
.flex-between { display: flex; justify-content: space-between; align-items: center; }
.history-count-badge { font-size: 0.75rem; background: #E2E8F0; color: #475569; padding: 3px 10px; border-radius: 12px; font-weight: 600; }
.pro-empty-state { text-align: center; padding: 30px; color: var(--text-muted); }
.empty-icon { font-size: 2.2rem; margin-bottom: 8px; opacity: 0.6; }
.pro-bottom-actions { display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-light); flex-wrap: wrap; }

.copy-toast { position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: white; padding: 10px 18px; border-radius: 8px; font-size: 0.88rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); opacity: 0; transform: translateY(10px); transition: all 0.3s ease; pointer-events: none; z-index: 9999; }
.copy-toast.show { opacity: 1; transform: translateY(0); }

@media print {
    .no-print, .history-pagination-wrapper, .header-actions, .pro-bottom-actions, .sidebar, footer { display: none !important; }
    .history-row { display: table-row !important; }
    tr.print-page-break { break-after: page !important; page-break-after: always !important; }
    .page-header { border: none !important; box-shadow: none !important; padding: 0 !important; margin-bottom: 15px !important; }
    .pro-card { box-shadow: none !important; border: 1px solid #CBD5E1 !important; break-inside: avoid; }
}

@media (max-width: 1024px) { .show-layout-grid { grid-template-columns: 1fr; } .kpi-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .ammo-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 16px !important; gap: 14px !important; }
    .ammo-show-container .header-content { flex: 1 1 100% !important; width: 100% !important; }
    .ammo-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .ammo-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid { grid-template-columns: 1fr; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .ammo-show-container .header-actions { grid-template-columns: 1fr !important; } }
</style>

<script>
let currentHistoryPage = 1;
const maxHistoryPages = <?php echo $totalPages; ?>;

function changeHistoryPage(delta) {
    const newPage = currentHistoryPage + delta;
    if (newPage >= 1 && newPage <= maxHistoryPages) { goToHistoryPage(newPage); }
}

function goToHistoryPage(pageNum) {
    if (pageNum < 1 || pageNum > maxHistoryPages) return;
    currentHistoryPage = pageNum;
    document.querySelectorAll('#historyLogTable .history-row').forEach(row => row.style.display = 'none');
    document.querySelectorAll('#historyLogTable .history-page-' + pageNum).forEach(row => row.style.display = '');
    document.querySelectorAll('.pg-num').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById('pgNumBtn_' + pageNum);
    if (activeBtn) activeBtn.classList.add('active');
    const badge = document.getElementById('currentPageNum');
    if (badge) badge.textContent = pageNum;
    const prevBtn = document.getElementById('btnPrevHistory');
    const nextBtn = document.getElementById('btnNextHistory');
    if (prevBtn) prevBtn.disabled = (pageNum === 1);
    if (nextBtn) nextBtn.disabled = (pageNum === maxHistoryPages);
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
