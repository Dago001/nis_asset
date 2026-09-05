<?php
$title = 'ICT Asset Details';
$active = 'ict';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$ict = $ict ?? $asset ?? [];
$documents = $documents ?? [];
$ictId = $ict['id'] ?? '';
$assetCode = $ict['asset_code'] ?? 'ICT-000';
$description = !empty($ict['asset_description']) ? $ict['asset_description'] : (!empty($ict['make_model']) ? $ict['make_model'] : 'ICT Equipment');
$category = !empty($ict['asset_category']) ? ($ict['asset_category'] === 'Other' ? ($ict['asset_category_other'] ?? 'Other') : $ict['asset_category']) : ($ict['equipment_type'] ?? 'N/A');
$manufacturer = !empty($ict['manufacturer']) ? $ict['manufacturer'] : 'N/A';
$modelVersion = !empty($ict['model_version']) ? $ict['model_version'] : 'N/A';
$serialNo = !empty($ict['serial_number']) ? $ict['serial_number'] : 'N/A';
$status = $ict['current_status'] ?? $ict['status'] ?? 'In Use';
$ownershipType = !empty($ict['ownership_type']) ? $ict['ownership_type'] : 'N/A';
$officer = !empty($ict['responsible_officer']) ? $ict['responsible_officer'] : 'N/A';

$locParts = [];
if (!empty($ict['lga_name'])) $locParts[] = $ict['lga_name'];
if (!empty($ict['state_name'])) $locParts[] = $ict['state_name'];
$fullLocation = !empty($locParts) ? implode(', ', $locParts) : (!empty($ict['location']) ? $ict['location'] : 'N/A');

$createdTime = !empty($ict['created_at']) ? date('d/m/Y H:i:s', strtotime($ict['created_at'])) : 'N/A';
$updatedTime = !empty($ict['updated_at']) ? date('d/m/Y H:i:s', strtotime($ict['updated_at'])) : 'N/A';
?>

<div class="container-fluid ict-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/ict"><i class="fas fa-laptop-code"></i> ICT Infrastructure</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Comprehensive Details</span>
            </div>
            <h1 class="page-title">
                <?php echo Security::escape($assetCode); ?>
                <span class="header-badge-code" title="Click to copy Asset Code" onclick="copyToClipboard('<?php echo Security::escape($assetCode); ?>', 'Asset Code')">
                    <i class="fas fa-copy copy-icon"></i> Copy Code
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/ict" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
            <?php if (Auth::can('ict.edit') && !empty($ictId)): ?>
            <a href="<?php echo BASE_URL; ?>/ict/edit/<?php echo $ictId; ?>" class="pro-btn pro-btn-primary">
                <i class="fas fa-pen-to-square"></i> <span>Edit ICT Asset</span>
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
            <div class="kpi-icon icon-serial"><i class="fas fa-microchip"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Serial Number</span>
                <span class="kpi-value text-mono font-medium"><?php echo Security::escape($serialNo); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-signal"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Current Status</span>
                <span class="kpi-value">
                    <span class="custom-badge badge-success"><?php echo Security::escape($status); ?></span>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-location-dot"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Assigned Location</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($fullLocation); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-user-shield"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Responsible Officer</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($officer); ?></span>
            </div>
        </div>
    </div>

    <!-- Layout Grid -->
    <div class="show-layout-grid">
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
                            <span class="item-value font-medium"><?php echo Security::escape($ict['zone_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Command / Formation</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ict['command_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">State</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ict['state_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Local Government Area (LGA)</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ict['lga_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item full-width">
                            <span class="item-label">Room / Specific Location</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ict['location'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. ICT Specifications Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sliders"></i> Basic Information & Hardware Specifications</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Asset Tag / Code</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($assetCode); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Asset Description</span>
                            <span class="item-value font-medium"><?php echo Security::escape($description); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Asset Category</span>
                            <span class="item-value font-medium"><?php echo Security::escape($category); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Manufacturer</span>
                            <span class="item-value font-medium"><?php echo Security::escape($manufacturer); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Model / Version</span>
                            <span class="item-value font-medium"><?php echo Security::escape($modelVersion); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Serial Number</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($serialNo); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Ownership Type</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ownershipType); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Current Status</span>
                            <span class="item-value"><span class="custom-badge badge-success"><?php echo Security::escape($status); ?></span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Technical & Network Details Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-network-wired"></i> Technical, Network & Software Details</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">IP Address</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($ict['ip_address'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">MAC Address</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($ict['mac_address'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Operating System</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ict['operating_system'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Software License</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ict['software_license'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if (!empty($ict['license_expiry'])): ?>
                        <div class="pro-detail-item">
                            <span class="item-label">License Expiry Date</span>
                            <span class="item-value font-medium"><?php echo date('d/m/Y', strtotime($ict['license_expiry'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="pro-detail-item">
                            <span class="item-label">Responsible Officer</span>
                            <span class="item-value font-medium"><?php echo Security::escape($officer); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Acquisition & Financial Details Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Acquisition & Valuation Details</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Purchase Date</span>
                            <span class="item-value font-medium">
                                <?php echo !empty($ict['purchase_date']) ? date('d/m/Y', strtotime($ict['purchase_date'])) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Purchase Value</span>
                            <span class="item-value font-medium text-success">
                                <?php echo !empty($ict['purchase_value']) ? '₦' . number_format($ict['purchase_value'], 2) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Current Value (Depreciated)</span>
                            <span class="item-value font-medium text-primary">
                                <?php echo !empty($ict['current_value']) ? '₦' . number_format($ict['current_value'], 2) : 'N/A'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Warranty & Maintenance Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-shield-alt"></i> Warranty & Maintenance History</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Warranty Period</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ict['warranty_period'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Warranty Expiry Date</span>
                            <span class="item-value font-medium">
                                <?php echo !empty($ict['warranty_expiry']) ? date('d/m/Y', strtotime($ict['warranty_expiry'])) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Maintenance Provider</span>
                            <span class="item-value font-medium"><?php echo Security::escape($ict['maintenance_provider'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Last Service Date</span>
                            <span class="item-value font-medium">
                                <?php echo !empty($ict['last_service_date']) ? date('d/m/Y', strtotime($ict['last_service_date'])) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Next Scheduled Service Date</span>
                            <span class="item-value font-medium text-warning">
                                <?php echo !empty($ict['next_service_date']) ? date('d/m/Y', strtotime($ict['next_service_date'])) : 'N/A'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Uploaded Documents Card -->
            <div class="pro-card">
                <div class="pro-card-header flex-between">
                    <h3><i class="fas fa-paperclip"></i> Uploaded Documents & Certificates</h3>
                    <span class="history-count-badge"><?php echo count($documents ?? []); ?> Attached File(s)</span>
                </div>
                <div class="pro-card-body">
                    <?php if (empty($documents)): ?>
                        <div class="pro-empty-state">
                            <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                            <p>No documents uploaded for this ICT asset.</p>
                        </div>
                    <?php else: ?>
                        <div class="doc-files-grid">
                            <?php foreach ($documents as $doc): ?>
                                <?php 
                                $fileName = $doc['file_name'] ?? 'Document';
                                $filePath = $doc['file_path'] ?? '';
                                $fileSize = !empty($doc['file_size']) ? round($doc['file_size'] / 1024, 1) . ' KB' : 'N/A';
                                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                $iconClass = 'fa-file-pdf text-danger';
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    $iconClass = 'fa-file-image text-primary';
                                } elseif (in_array($ext, ['doc', 'docx'])) {
                                    $iconClass = 'fa-file-word text-info';
                                }
                                $rawPath = str_replace('\\', '/', $filePath);
                                if (strpos($rawPath, 'htdocs/nis_ams/') !== false) {
                                    $rel = substr($rawPath, strpos($rawPath, 'htdocs/nis_ams/') + strlen('htdocs/nis_ams/'));
                                    $fileUrl = BASE_URL . '/' . ltrim($rel, '/');
                                } else {
                                    $fileUrl = BASE_URL . '/' . ltrim($rawPath, '/');
                                }
                                ?>
                                <div class="doc-file-card">
                                    <div class="doc-icon"><i class="fas <?php echo $iconClass; ?>"></i></div>
                                    <div class="doc-info">
                                        <span class="doc-name" title="<?php echo Security::escape($fileName); ?>"><?php echo Security::escape($fileName); ?></span>
                                        <span class="doc-meta"><?php echo $fileSize; ?> • <?php echo strtoupper($ext); ?></span>
                                    </div>
                                    <div class="doc-actions">
                                        <a href="<?php echo Security::escape($fileUrl); ?>" target="_blank" class="pro-btn pro-btn-secondary pro-btn-sm" title="View Document">
                                            <i class="fas fa-eye"></i> <span>View</span>
                                        </a>
                                        <a href="<?php echo Security::escape($fileUrl); ?>" download class="pro-btn pro-btn-outline pro-btn-sm" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 7. Remarks Section -->
            <?php if (!empty($ict['remarks'])): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-note-sticky"></i> Remarks & Historical Notes</h3>
                </div>
                <div class="pro-card-body">
                    <div class="remarks-box">
                        <?php echo nl2br(Security::escape($ict['remarks'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="show-sidebar-column">
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Record Metadata</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Logged By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($ict['created_by_name'] ?? 'System Administrator'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Created Timestamp</span>
                        <span class="side-value text-mono small"><?php echo Security::escape($createdTime); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Last Updated</span>
                        <span class="side-value text-mono small"><?php echo Security::escape($updatedTime); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Actions Bar -->
    <div class="pro-bottom-actions">
        <?php if (Auth::can('ict.edit') && !empty($ictId)): ?>
        <a href="<?php echo BASE_URL; ?>/ict/edit/<?php echo $ictId; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-pen-to-square"></i> <span>Edit ICT Asset</span>
        </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/ict" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to ICT Infrastructure</span>
        </a>
    </div>
</div>

<div id="copyToast" class="copy-toast"></div>

<!-- CSS Styling & Mobile Responsiveness -->
<style>
:root { --nis-forest: #134617; --nis-emerald: #2E7D32; --card-bg: #FFFFFF; --border-light: #E2E8F0; --text-dark: #0F172A; --text-muted: #64748B; }
[data-theme="dark"] {
    --nis-forest: #299631;
    --nis-emerald: #52bf57;
    --card-bg: #1f1f1f;
    --border-light: #2b323b;
    --text-dark: #d9dde8;
    --text-muted: #dee0e3;
}

.ict-show-container { padding-bottom: 40px; }
.ict-show-container .page-header { display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 16px !important; background: #ffffff !important; padding: 20px 24px !important; border-radius: 12px !important; border: 1px solid #E2E8F0 !important; margin-bottom: 24px !important; }
.ict-show-container .header-content { flex: 1 1 280px !important; }
.ict-show-container .header-content h1.page-title { font-size: 1.25rem !important; font-weight: 600 !important; color: #1E293B !important; margin: 4px 0 0 0 !important; display: flex !important; align-items: center !important; flex-wrap: wrap !important; gap: 10px !important; letter-spacing: normal !important; }
.header-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px; }
.header-breadcrumb a { color: var(--nis-emerald); text-decoration: none; font-weight: 500; }
.breadcrumb-separator { font-size: 0.7rem; color: #94A3B8; }
.header-badge-code { display: inline-flex; align-items: center; gap: 6px; background: #F1F5F9; color: var(--nis-forest); border: 1px solid #CBD5E1; font-family: 'SF Mono', monospace; font-size: 0.95rem; padding: 3px 10px; border-radius: 6px; cursor: pointer; }
.pro-btn { display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; padding: 9px 18px !important; font-size: 0.88rem !important; font-weight: 600 !important; border-radius: 8px !important; white-space: nowrap !important; height: 40px !important; border: 1px solid transparent !important; text-decoration: none !important; }
.pro-btn span { display: inline-block !important; color: inherit !important; background: transparent !important; }
.pro-btn i { font-size: 0.95rem !important; color: inherit !important; }
.pro-btn-secondary { background: #F1F5F9 !important; color: #334155 !important; border-color: #CBD5E1 !important; }
.pro-btn-primary { background: #134617 !important; color: #FFFFFF !important; }
.pro-btn-outline { background: #FFFFFF !important; color: #0F172A !important; border-color: #94A3B8 !important; }
.ict-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; }
.kpi-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; }
.kpi-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.icon-serial { background: #E0F2FE; color: #0284C7; }
.icon-condition { background: #DCFCE7; color: #16A34A; }
.icon-location { background: #FEF3C7; color: #D97706; }
.icon-calibre { background: #F3E8FF; color: #9333EA; }
.kpi-details { display: flex; flex-direction: column; gap: 2px; }
.kpi-label { font-size: 0.78rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted); }
.kpi-value { font-size: 1rem; color: var(--text-dark); }
.custom-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; }
.badge-success { background: #DEF7EC; color: #03543F; }
.show-layout-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 24px; margin-bottom: 24px; }
.flex-between { display: flex; justify-content: space-between; align-items: center; }
.history-count-badge { font-size: 0.75rem; background: #E2E8F0; color: #475569; padding: 2px 8px; border-radius: 10px; font-weight: 500; }
.pro-btn-sm { height: 32px !important; padding: 4px 10px !important; font-size: 0.78rem !important; }
.doc-files-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.doc-file-card { display: flex; align-items: center; gap: 12px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px 14px; }
.doc-icon { font-size: 1.4rem; flex-shrink: 0; }
.doc-info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.doc-name { font-size: 0.85rem; font-weight: 600; color: #1E293B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.doc-meta { font-size: 0.75rem; color: #64748B; margin-top: 1px; }
.doc-actions { display: flex; gap: 6px; flex-shrink: 0; }
.pro-empty-state { text-align: center; padding: 20px; color: var(--text-muted); }
.empty-icon { font-size: 1.8rem; margin-bottom: 4px; opacity: 0.5; }
.pro-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; margin-bottom: 24px; overflow: hidden; }
.pro-card-header { padding: 16px 20px; background: #F8FAFC; border-bottom: 1px solid var(--border-light); }
.pro-card-header h3 { margin: 0; font-size: 1.05rem; font-weight: 600; color: var(--nis-forest); display: flex; align-items: center; gap: 10px; }
.pro-card-body { padding: 20px; }
.pro-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px 24px; }
.pro-detail-item { display: flex; flex-direction: column; gap: 4px; }
.pro-detail-item.full-width { grid-column: span 2; }
.item-label { font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
.item-value { font-size: 0.95rem; color: var(--text-dark); }
.pro-side-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F1F5F9; }
.side-label { font-size: 0.85rem; color: var(--text-muted); }
.side-value { font-size: 0.9rem; color: var(--text-dark); }
.remarks-box { background: #F8FAFC; border-left: 4px solid var(--nis-emerald); padding: 16px; border-radius: 0 8px 8px 0; font-size: 0.92rem; line-height: 1.6; }
.pro-bottom-actions { display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-light); flex-wrap: wrap; }

.copy-toast { position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: white; padding: 12px 20px; border-radius: 8px; font-size: 0.88rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); opacity: 0; transform: translateY(10px); transition: all 0.3s ease; pointer-events: none; z-index: 9999; }
.copy-toast.show { opacity: 1; transform: translateY(0); }

@media print {
    .no-print, .header-actions, .pro-bottom-actions, .sidebar, footer { display: none !important; }
    .page-header { border: none !important; box-shadow: none !important; padding: 0 !important; margin-bottom: 15px !important; }
    .pro-card { box-shadow: none !important; border: 1px solid #CBD5E1 !important; break-inside: avoid; }
}

@media (max-width: 1024px) { .show-layout-grid { grid-template-columns: 1fr; } .kpi-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .ict-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 16px !important; gap: 12px !important; }
    .ict-show-container .header-content h1 { font-size: 1.25rem !important; }
    .ict-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .ict-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid, .doc-files-grid { grid-template-columns: 1fr; }
    .pro-detail-item.full-width { grid-column: span 1; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .ict-show-container .header-actions { grid-template-columns: 1fr !important; } }
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
