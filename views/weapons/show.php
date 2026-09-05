<?php
$title = 'Weapon Details';
$active = 'weapons';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$historyList = $issueHistory ?? [];
$totalHistory = count($historyList);
$historyPages = array_chunk($historyList, 30);
$totalPages = count($historyPages);
?>

<div class="container-fluid weapon-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/weapons"><i class="fas fa-gun"></i> Weapon Registry</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Weapon Details</span>
            </div>
            <h1 class="page-title">
                <?php echo Security::escape($weapon['make_model'] ?? 'Weapon Asset'); ?>
                <span class="header-badge-code" title="Click to copy Serial Number" onclick="copyToClipboard('<?php echo Security::escape($weapon['serial_no']); ?>', 'Serial Number')">
                    <?php echo Security::escape($weapon['serial_no']); ?> <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/weapons" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
            <?php if (Auth::can('weapons.edit')): ?>
            <a href="<?php echo BASE_URL; ?>/weapons/edit/<?php echo $weapon['id']; ?>" class="pro-btn pro-btn-primary">
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
            <div class="kpi-icon icon-serial"><i class="fas fa-barcode"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Serial Number</span>
                <span class="kpi-value text-mono font-medium" onclick="copyToClipboard('<?php echo Security::escape($weapon['serial_no']); ?>', 'Serial Number')" style="cursor:pointer;" title="Click to copy">
                    <?php echo Security::escape($weapon['serial_no']); ?> <i class="fas fa-copy copy-subicon"></i>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-shield-halved"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Condition Status</span>
                <span class="kpi-value">
                    <?php 
                    $cond = $weapon['condition'] ?? 'Serviceable';
                    $badgeClass = 'badge-success';
                    if ($cond == 'Unserviceable') $badgeClass = 'badge-warning';
                    elseif ($cond == 'Condemned' || $cond == 'Damaged') $badgeClass = 'badge-danger';
                    ?>
                    <span class="custom-badge <?php echo $badgeClass; ?>"><?php echo Security::escape($cond); ?></span>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-location-dot"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Current Location</span>
                <span class="kpi-value font-medium">
                    <?php echo Security::escape($weapon['current_location'] ?? 'Armoury'); ?>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-crosshairs"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Type & Calibre</span>
                <span class="kpi-value font-medium">
                    <?php echo Security::escape($weapon['type_name'] ?? 'N/A'); ?> (<?php echo Security::escape($weapon['calibre_name'] ?? 'N/A'); ?>)
                </span>
            </div>
        </div>
    </div>

    <!-- Status Alerts -->
    <?php if ($weapon['condition'] == 'Unserviceable'): ?>
    <div class="pro-alert-banner alert-warning">
        <div class="alert-icon"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="alert-body">
            <h4>Unserviceable Status Alert</h4>
            <p>This weapon is flagged as <strong>Unserviceable</strong>. Mandatory inspection or repair is required before re-issuing to active duty.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($weapon['custody_officer_name'])): ?>
    <div class="pro-alert-banner alert-info">
        <div class="alert-icon"><i class="fas fa-user-shield"></i></div>
        <div class="alert-body">
            <h4>Currently Issued Out</h4>
            <p>Assigned Custodian: <strong><?php echo Security::escape($weapon['custody_officer_name']); ?></strong> (<?php echo Security::escape($weapon['custody_officer_rank'] ?? 'Officer'); ?>) - Issued on <?php echo date('d/m/Y', strtotime($weapon['custody_issue_date'])); ?>.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Layout (2 Columns) -->
    <div class="show-layout-grid">
        <!-- Main Column (Left 70%) -->
        <div class="show-main-column">
            <!-- 1. Location Information Section -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Location Information</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Zone</span>
                            <span class="item-value font-medium"><?php echo Security::escape($weapon['zone_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Command / Formation</span>
                            <span class="item-value font-medium"><?php echo Security::escape($weapon['command_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">State</span>
                            <span class="item-value font-medium"><?php echo Security::escape($weapon['state_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Local Government Area (LGA)</span>
                            <span class="item-value font-medium"><?php echo Security::escape($weapon['lga_name'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if (!empty($weapon['armoury_name'])): ?>
                        <div class="pro-detail-item full-width">
                            <span class="item-label">Armoury Name / Details</span>
                            <span class="item-value font-medium"><?php echo Security::escape($weapon['armoury_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="pro-detail-item full-width">
                            <span class="item-label">Current Location Status</span>
                            <span class="item-value font-medium"><?php echo Security::escape(!empty($weapon['current_location']) ? ($weapon['current_location'] === 'Other' ? ($weapon['current_location_other'] ?? 'Other') : $weapon['current_location']) : 'Armoury'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weapon Specifications Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sliders"></i> Weapon Specifications & Source Details</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Weapon ID Code</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($weapon['weapon_id'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Serial Number</span>
                            <span class="item-value text-mono font-medium text-success"><?php echo Security::escape($weapon['serial_no'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Make & Model</span>
                            <span class="item-value font-medium"><?php echo Security::escape($weapon['make_model'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Weapon Type</span>
                            <span class="item-value font-medium"><?php echo Security::escape(!empty($weapon['weapon_type_name']) ? $weapon['weapon_type_name'] : ($weapon['type_name'] ?? ($weapon['weapon_type_other'] ?? 'N/A'))); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Calibre</span>
                            <span class="item-value font-medium"><?php echo Security::escape(!empty($weapon['calibre_name']) ? $weapon['calibre_name'] : ($weapon['calibre_other'] ?? 'N/A')); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Condition Status</span>
                            <span class="item-value"><span class="custom-badge badge-success"><?php echo Security::escape($weapon['condition'] ?? 'Serviceable'); ?></span></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Source of Acquisition</span>
                            <span class="item-value font-medium"><?php echo Security::escape(!empty($weapon['source']) ? ($weapon['source'] === 'Other' ? ($weapon['source_other'] ?? 'Other') : $weapon['source']) : 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Date Acquired</span>
                            <span class="item-value font-medium"><?php echo !empty($weapon['date_acquired']) ? date('d/m/Y', strtotime($weapon['date_acquired'])) : 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Movement & Issue History Card (Paginated at 30 records per page) -->
            <div class="pro-card" id="historyCardSection">
                <div class="pro-card-header flex-between">
                    <h3><i class="fas fa-clock-rotate-left"></i> Issue & Movement History Log</h3>
                    <div class="history-header-meta">
                        <span class="history-count-badge"><?php echo $totalHistory; ?> Total Records</span>
                        <?php if ($totalPages > 1): ?>
                            <span class="page-indicator-badge">Page <span id="currentPageNum">1</span> of <?php echo $totalPages; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="pro-card-body pad-none">
                    <?php if (empty($historyList)): ?>
                        <div class="pro-empty-state">
                            <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                            <p>No movement or issue history logged for this weapon</p>
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
                                        <th>Station / Unit</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $globalIndex = 1;
                                    foreach ($historyPages as $pageIdx => $pageRecords): 
                                    ?>
                                        <?php foreach ($pageRecords as $rowIdx => $history): ?>
                                        <tr class="history-row history-page-<?php echo ($pageIdx + 1); ?> <?php echo (($globalIndex % 30 == 0) ? 'print-page-break' : ''); ?>" 
                                            style="<?php echo ($pageIdx > 0 ? 'display: none;' : ''); ?>">
                                            <td class="text-muted small"><?php echo $globalIndex; ?></td>
                                            <td class="text-mono"><?php echo date('d/m/Y', strtotime($history['issue_date'])); ?></td>
                                            <td class="font-medium"><?php echo Security::escape($history['officer_name']); ?></td>
                                            <td><?php echo Security::escape($history['officer_rank']); ?></td>
                                            <td><?php echo Security::escape($history['unit'] ?? $history['command_name'] ?? 'HQ'); ?></td>
                                            <td><?php echo Security::escape($history['purpose']); ?></td>
                                            <td>
                                                <?php if (!empty($history['return_date']) || ($history['status'] ?? '') === 'Returned'): ?>
                                                    <span class="custom-badge badge-neutral">Returned</span>
                                                <?php else: ?>
                                                    <span class="custom-badge badge-success">Active Duty</span>
                                                <?php endif; ?>
                                            </td>
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
                        <?php echo nl2br(Security::escape($weapon['remarks'] ?? 'No special administrative remarks logged for this weapon.')); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column (Right 30%) -->
        <div class="show-sidebar-column">
            <!-- Storage & Location Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-warehouse"></i> Storage & Command</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Armoury Location</span>
                        <span class="side-value font-medium"><?php echo Security::escape($weapon['current_location'] ?? 'Central Armoury'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Command / Station</span>
                        <span class="side-value font-medium"><?php echo Security::escape($weapon['command_name'] ?? 'Headquarters'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Zonal Office</span>
                        <span class="side-value"><?php echo Security::escape($weapon['zone_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Inspection Status Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-clipboard-check"></i> Inspection Audit</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Last Inspection</span>
                        <span class="side-value text-mono">
                            <?php echo !empty($weapon['last_inspection_date']) ? date('d/m/Y', strtotime($weapon['last_inspection_date'])) : 'Not recorded'; ?>
                        </span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Next Inspection Due</span>
                        <span class="side-value text-mono">
                            <?php if (!empty($weapon['next_inspection_date'])): ?>
                                <?php echo date('d/m/Y', strtotime($weapon['next_inspection_date'])); ?>
                                <?php
                                $daysToInsp = round((strtotime($weapon['next_inspection_date']) - time()) / (60 * 60 * 24));
                                if ($daysToInsp <= 30 && $daysToInsp >= 0) {
                                    echo '<br><span class="insp-pill insp-warn">Due in ' . $daysToInsp . ' days</span>';
                                } elseif ($daysToInsp < 0) {
                                    echo '<br><span class="insp-pill insp-danger">Overdue (' . abs($daysToInsp) . ' days)</span>';
                                }
                                ?>
                            <?php else: ?>
                                Not scheduled
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Audit Metadata Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Record Information</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Created By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($weapon['created_by_name'] ?? 'System Admin'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Created At</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i:s', strtotime($weapon['created_at'])); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Last Updated</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i:s', strtotime($weapon['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Actions Bar -->
    <div class="pro-bottom-actions">
        <?php if (Auth::can('weapons.edit')): ?>
        <a href="<?php echo BASE_URL; ?>/weapons/edit/<?php echo $weapon['id']; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-pen-to-square"></i> <span>Edit Record</span>
        </a>
        <?php endif; ?>
        
        <?php if (Auth::can('weapons.delete')): ?>
        <a href="<?php echo BASE_URL; ?>/weapons/delete/<?php echo $weapon['id']; ?>" class="pro-btn pro-btn-danger" 
           onclick="return confirm('Are you sure you want to delete this weapon record? This action cannot be undone.')">
            <i class="fas fa-trash-can"></i> <span>Delete Record</span>
        </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/weapons" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to Weapons</span>
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
    --text-dark: #1E293B;
    --text-muted: #64748B;
}
[data-theme="dark"] {
    --nis-forest: #299631;
    --nis-emerald: #52bf57;
    --card-bg: #1f1f1f;
    --border-light: #2b323b;
    --text-dark: #dbdfe5;
    --text-muted: #dee0e3;
}


.weapon-show-container {
    padding-bottom: 40px;
    font-family: var(--font-family, inherit);
}

.weapon-show-container .page-header {
    display: flex !important; justify-content: space-between !important; align-items: center !important;
    flex-wrap: wrap !important; gap: 16px !important; background: #ffffff !important; padding: 16px 20px !important;
    border-radius: 8px !important; border: 1px solid #E2E8F0 !important; box-shadow: 0 2px 8px rgba(0,0,0,0.03) !important; margin-bottom: 20px !important;
}

.weapon-show-container .header-content { flex: 1 1 280px !important; min-width: 0 !important; }
.weapon-show-container .header-content h1.page-title {
    font-size: 1.25rem !important;
    font-weight: 600 !important;
    color: #1E293B !important;
    margin: 4px 0 0 0 !important;
    display: flex !important;
    align-items: center !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    letter-spacing: normal !important;
}

.header-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: var(--text-muted); margin-bottom: 3px; font-weight: 500; }
.header-breadcrumb a { color: var(--nis-emerald); text-decoration: none; font-weight: 500; }
.breadcrumb-separator { font-size: 0.68rem; color: #94A3B8; }

.header-badge-code {
    display: inline-flex; align-items: center; gap: 6px; background: #F8FAFC; color: #334155;
    border: 1px solid #E2E8F0; font-family: 'SF Mono', 'Courier New', monospace; font-size: 0.85rem; font-weight: 500; padding: 2px 8px; border-radius: 4px; cursor: pointer;
}

.pro-btn {
    display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px !important;
    padding: 8px 16px !important; font-size: 0.85rem !important; font-weight: 500 !important; border-radius: 6px !important;
    white-space: nowrap !important; height: 38px !important; box-sizing: border-box !important; text-decoration: none !important;
    line-height: 1 !important; cursor: pointer !important; user-select: none !important; outline: none !important; border: 1px solid transparent !important;
}

.pro-btn span { display: inline-block !important; color: inherit !important; background: transparent !important; }
.pro-btn i { font-size: 0.9rem !important; color: inherit !important; }

.pro-btn-secondary { background: #F1F5F9 !important; color: #334155 !important; border-color: #CBD5E1 !important; }
.pro-btn-primary { background: #134617 !important; color: #FFFFFF !important; }
.pro-btn-outline { background: #FFFFFF !important; color: #1E293B !important; border-color: #CBD5E1 !important; }
.pro-btn-danger { background: #DC2626 !important; color: #FFFFFF !important; }

.weapon-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; flex-wrap: nowrap !important; }

.kpi-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.kpi-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 8px; padding: 14px 18px; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
.kpi-icon { width: 42px; height: 42px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.icon-serial { background: #F0F9FF; color: #0284C7; }
.icon-condition { background: #F0FDF4; color: #16A34A; }
.icon-location { background: #FFFBEB; color: #D97706; }
.icon-calibre { background: #FAF5FF; color: #9333EA; }

.kpi-details { display: flex; flex-direction: column; gap: 2px; }
.kpi-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted); }
.kpi-value { font-size: 0.95rem; color: var(--text-dark); font-weight: 500; }

.custom-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 500; }
.badge-success { background: #DEF7EC; color: #03543F; }
.badge-warning { background: #FEF08A; color: #713F12; }
.badge-danger  { background: #FDE8E8; color: #9B1C1C; }
.badge-neutral { background: #F1F5F9; color: #334155; }

.pro-alert-banner { background: white; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); border-left: 4px solid; }
.pro-alert-banner.alert-warning { border-left-color: transparent; background: #FFFBEB; }
.pro-alert-banner.alert-info { border-left-color: transparent; background: #F0F9FF; }
.alert-icon i { font-size: 1.5rem; }
.alert-body h4 { margin: 0 0 2px 0; font-size: 0.95rem; font-weight: 600; }
.alert-body p { margin: 0; font-size: 0.88rem; color: var(--text-dark); }

.show-layout-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 20px; margin-bottom: 20px; }
.pro-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 20px; overflow: hidden; }
.pro-card-header { padding: 14px 18px; background: #F8FAFC; border-bottom: 1px solid var(--border-light); }
.pro-card-header h3 { margin: 0; font-size: 0.98rem; font-weight: 600; color: var(--nis-forest); display: flex; align-items: center; gap: 8px; }
.pro-card-body { padding: 18px; }
.pro-card-body.pad-none { padding: 0; }

.pro-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 20px; }
.pro-detail-item { display: flex; flex-direction: column; gap: 3px; }
.item-label { font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
.item-value { font-size: 0.9rem; color: var(--text-dark); font-weight: 400; }

.pro-side-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F1F5F9; }
.pro-side-item:last-child { border-bottom: none; }
.side-label { font-size: 0.82rem; color: var(--text-muted); }
.side-value { font-size: 0.88rem; color: var(--text-dark); text-align: right; }

.pro-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.pro-table th { background: #F8FAFC; padding: 10px 14px; text-align: left; font-weight: 600; color: var(--text-muted); border-bottom: 1px solid var(--border-light); }
.pro-table td { padding: 10px 14px; border-bottom: 1px solid #F1F5F9; color: var(--text-dark); }

.history-pagination-wrapper { display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; background: #F8FAFC; border-top: 1px solid var(--border-light); flex-wrap: wrap; gap: 10px; }
.pagination-info { font-size: 0.82rem; color: var(--text-muted); }
.pagination-controls { display: flex; align-items: center; gap: 6px; }
.pg-btn { display: inline-flex; align-items: center; gap: 4px; background: #FFFFFF; border: 1px solid #CBD5E1; padding: 5px 12px; border-radius: 4px; font-size: 0.82rem; font-weight: 500; cursor: pointer; }
.pg-num { background: #FFFFFF; border: 1px solid #CBD5E1; min-width: 28px; height: 28px; border-radius: 4px; font-size: 0.82rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.pg-num.active { background: var(--nis-forest); color: #FFFFFF; border-color: var(--nis-forest); }

.remarks-box { background: #F8FAFC; border-left: 3px solid var(--nis-emerald); padding: 12px 16px; border-radius: 0 6px 6px 0; font-size: 0.88rem; line-height: 1.5; }
.insp-pill { display: inline-block; font-size: 0.72rem; padding: 2px 6px; border-radius: 8px; margin-top: 2px; font-weight: 500; }
.insp-warn { background: #FEF9C3; color: #854D0E; }
.insp-danger { background: #FEE2E2; color: #991B1B; }

.text-mono { font-family: 'SF Mono', 'Courier New', monospace; }
.font-medium { font-weight: 500; }
.flex-between { display: flex; justify-content: space-between; align-items: center; }
.history-count-badge { font-size: 0.75rem; background: #E2E8F0; color: #475569; padding: 2px 8px; border-radius: 10px; font-weight: 500; }
.pro-empty-state { text-align: center; padding: 24px; color: var(--text-muted); }
.empty-icon { font-size: 2rem; margin-bottom: 6px; opacity: 0.6; }
.pro-bottom-actions { display: flex; gap: 10px; padding-top: 14px; border-top: 1px solid var(--border-light); flex-wrap: wrap; }

.copy-toast { position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: white; padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); opacity: 0; transform: translateY(10px); transition: all 0.3s ease; pointer-events: none; z-index: 9999; }
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
    .weapon-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 14px !important; gap: 12px !important; }
    .weapon-show-container .header-content { flex: 1 1 100% !important; width: 100% !important; }
    .weapon-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .weapon-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid { grid-template-columns: 1fr; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .weapon-show-container .header-actions { grid-template-columns: 1fr !important; } }
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
