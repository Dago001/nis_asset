<?php
$title = 'Land Asset Details';
$active = 'land';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$land = $land ?? $asset ?? [];
$documents = $documents ?? [];
$landId = $land['id'] ?? '';
$assetCode = $land['asset_code'] ?? 'LND-000';
$plotName = !empty($land['address']) ? $land['address'] : (!empty($land['title_holder']) ? $land['title_holder'] : 'Land Parcel');
$landSize = (!empty($land['size']) && is_numeric($land['size'])) ? (number_format($land['size'], 2) . ' ' . ($land['size_unit'] ?? 'hectares')) : ($land['size'] ?? 'N/A');
$titleDoc = !empty($land['certificate_of_occupancy_no']) ? ('C of O: ' . $land['certificate_of_occupancy_no']) : (!empty($land['survey_plan_no']) ? ('Survey Plan: ' . $land['survey_plan_no']) : 'N/A');

$locParts = [];
if (!empty($land['lga_name'])) $locParts[] = $land['lga_name'];
if (!empty($land['state_name'])) $locParts[] = $land['state_name'];
$fullLocation = !empty($locParts) ? implode(', ', $locParts) : (!empty($land['address']) ? $land['address'] : 'N/A');

$usageStr = !empty($land['purpose_use']) ? $land['purpose_use'] : 'Command / Operational Use';
$landStatus = $land['status'] ?? 'Active';
$createdTime = !empty($land['created_at']) ? date('d/m/Y H:i:s', strtotime($land['created_at'])) : 'N/A';
$updatedTime = !empty($land['updated_at']) ? date('d/m/Y H:i:s', strtotime($land['updated_at'])) : 'N/A';
?>

<div class="container-fluid land-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/land"><i class="fas fa-map-location-dot"></i> Land Assets</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Comprehensive Land Details</span>
            </div>
            <h1 class="page-title">
                <?php echo Security::escape($assetCode); ?>
                <span class="header-badge-code" title="Click to copy Asset Code" onclick="copyToClipboard('<?php echo Security::escape($assetCode); ?>', 'Asset Code')">
                    <i class="fas fa-copy copy-icon"></i> Copy Code
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/land" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
            <?php if (Auth::can('land.edit') && !empty($landId)): ?>
            <a href="<?php echo BASE_URL; ?>/land/edit/<?php echo $landId; ?>" class="pro-btn pro-btn-primary">
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
            <div class="kpi-icon icon-serial"><i class="fas fa-ruler-combined"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Land Size / Area</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($landSize); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-certificate"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Title / Document</span>
                <span class="kpi-value">
                    <span class="custom-badge badge-success"><?php echo Security::escape($titleDoc); ?></span>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-location-dot"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">State / LGA Location</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($fullLocation); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-building-user"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Assigned Command</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($land['command_name'] ?? 'N/A'); ?></span>
            </div>
        </div>
    </div>

    <!-- Main Layout Grid -->
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
                            <span class="item-value font-medium"><?php echo Security::escape($land['zone_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Command / Formation</span>
                            <span class="item-value font-medium"><?php echo Security::escape($land['command_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">State</span>
                            <span class="item-value font-medium"><?php echo Security::escape($land['state_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Local Government Area (LGA)</span>
                            <span class="item-value font-medium"><?php echo Security::escape($land['lga_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item full-width">
                            <span class="item-label">Specific Location / Address</span>
                            <span class="item-value font-medium"><?php echo Security::escape($land['address'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if (!empty($land['latitude']) || !empty($land['longitude'])): ?>
                        <div class="pro-detail-item full-width">
                            <span class="item-label">GPS Coordinates</span>
                            <span class="item-value text-mono">
                                Lat: <?php echo Security::escape($land['latitude'] ?? 'N/A'); ?>, 
                                Long: <?php echo Security::escape($land['longitude'] ?? 'N/A'); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 2. Land Asset Specifications Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sliders"></i> Land Asset Specifications & Ownership</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Asset Code</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($assetCode); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Ownership Type</span>
                            <span class="item-value"><span class="badge badge-info"><?php echo Security::escape($land['ownership_type'] ?? 'N/A'); ?></span></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Title Holder</span>
                            <span class="item-value font-medium"><?php echo Security::escape($land['title_holder'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Size / Area</span>
                            <span class="item-value font-medium"><?php echo Security::escape($landSize); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Purpose / Use</span>
                            <span class="item-value font-medium"><?php echo Security::escape($usageStr); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Development Status</span>
                            <span class="item-value"><span class="custom-badge badge-success"><?php echo Security::escape($landStatus); ?></span></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Survey Plan No.</span>
                            <span class="item-value font-medium"><?php echo Security::escape($land['survey_plan_no'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Certificate of Occupancy (C of O) No.</span>
                            <span class="item-value font-medium"><?php echo Security::escape($land['certificate_of_occupancy_no'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Date Acquired</span>
                            <span class="item-value font-medium"><?php echo !empty($land['date_acquired']) ? date('d/m/Y', strtotime($land['date_acquired'])) : 'N/A'; ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Encumbrance / Legal Issues</span>
                            <span class="item-value font-medium"><?php echo Security::escape($land['encumbrance'] ?? 'None'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Uploaded Documents Card -->
            <div class="pro-card">
                <div class="pro-card-header flex-between">
                    <h3><i class="fas fa-paperclip"></i> Uploaded Documents & Certificates</h3>
                    <span class="history-count-badge"><?php echo count($documents); ?> Attached File(s)</span>
                </div>
                <div class="pro-card-body">
                    <?php if (empty($documents)): ?>
                        <div class="pro-empty-state">
                            <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                            <p>No documents uploaded for this land asset.</p>
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

            <!-- 4. Remarks & Historical Notes -->
            <?php if (!empty($land['remarks'])): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-note-sticky"></i> Remarks & Historical Notes</h3>
                </div>
                <div class="pro-card-body">
                    <div class="remarks-box">
                        <?php echo nl2br(Security::escape($land['remarks'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Column -->
        <div class="show-sidebar-column">
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Record Metadata</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Logged By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($land['created_by_name'] ?? 'System Administrator'); ?></span>
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
        <?php if (Auth::can('land.edit') && !empty($landId)): ?>
        <a href="<?php echo BASE_URL; ?>/land/edit/<?php echo $landId; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-pen-to-square"></i> <span>Edit Land Asset</span>
        </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/land" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to Land Assets</span>
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


.land-show-container {
    padding-bottom: 40px;
    font-family: var(--font-family, inherit);
}

.land-show-container .page-header {
    display: flex !important; justify-content: space-between !important; align-items: center !important;
    flex-wrap: wrap !important; gap: 16px !important; background: #ffffff !important; padding: 16px 20px !important;
    border-radius: 8px !important; border: 1px solid #E2E8F0 !important; box-shadow: 0 2px 8px rgba(0,0,0,0.03) !important; margin-bottom: 20px !important;
}

.land-show-container .header-content { flex: 1 1 280px !important; min-width: 0 !important; }
.land-show-container .header-content h1.page-title {
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

.pro-btn-sm { height: 32px !important; padding: 4px 10px !important; font-size: 0.78rem !important; }
.pro-btn span { display: inline-block !important; color: inherit !important; background: transparent !important; }
.pro-btn i { font-size: 0.9rem !important; color: inherit !important; }

.pro-btn-secondary { background: #F1F5F9 !important; color: #334155 !important; border-color: #CBD5E1 !important; }
.pro-btn-primary { background: #134617 !important; color: #FFFFFF !important; }
.pro-btn-outline { background: #FFFFFF !important; color: #1E293B !important; border-color: #CBD5E1 !important; }

.land-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; flex-wrap: nowrap !important; }

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
.badge-info { background: #E1F5FE; color: #0288D1; }

.show-layout-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 20px; margin-bottom: 20px; }
.pro-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 20px; overflow: hidden; }
.pro-card-header { padding: 14px 18px; background: #F8FAFC; border-bottom: 1px solid var(--border-light); }
.pro-card-header h3 { margin: 0; font-size: 0.98rem; font-weight: 600; color: var(--nis-forest); display: flex; align-items: center; gap: 8px; }
.pro-card-body { padding: 18px; }

.flex-between { display: flex; justify-content: space-between; align-items: center; }
.history-count-badge { font-size: 0.75rem; background: #E2E8F0; color: #475569; padding: 2px 8px; border-radius: 10px; font-weight: 500; }

.doc-files-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.doc-file-card { display: flex; align-items: center; gap: 12px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px 14px; }
.doc-icon { font-size: 1.4rem; flex-shrink: 0; }
.doc-info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.doc-name { font-size: 0.85rem; font-weight: 600; color: #1E293B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.doc-meta { font-size: 0.75rem; color: #64748B; margin-top: 1px; }
.doc-actions { display: flex; gap: 6px; flex-shrink: 0; }

.pro-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 20px; }
.pro-detail-item { display: flex; flex-direction: column; gap: 3px; }
.pro-detail-item.full-width { grid-column: span 2; }
.item-label { font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
.item-value { font-size: 0.9rem; color: var(--text-dark); font-weight: 400; }

.pro-side-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F1F5F9; }
.pro-side-item:last-child { border-bottom: none; }
.side-label { font-size: 0.82rem; color: var(--text-muted); }
.side-value { font-size: 0.88rem; color: var(--text-dark); text-align: right; }

.remarks-box { background: #F8FAFC; border-left: 3px solid var(--nis-emerald); padding: 12px 16px; border-radius: 0 6px 6px 0; font-size: 0.88rem; line-height: 1.5; }
.pro-empty-state { text-align: center; padding: 20px; color: var(--text-muted); }
.empty-icon { font-size: 1.8rem; margin-bottom: 4px; opacity: 0.5; }

.text-mono { font-family: 'SF Mono', 'Courier New', monospace; }
.font-medium { font-weight: 500; }
.pro-bottom-actions { display: flex; gap: 10px; padding-top: 14px; border-top: 1px solid var(--border-light); flex-wrap: wrap; }

.copy-toast { position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: white; padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); opacity: 0; transform: translateY(10px); transition: all 0.3s ease; pointer-events: none; z-index: 9999; }
.copy-toast.show { opacity: 1; transform: translateY(0); }

@media print {
    .no-print, .header-actions, .pro-bottom-actions, .sidebar, footer { display: none !important; }
    .page-header { border: none !important; box-shadow: none !important; padding: 0 !important; margin-bottom: 15px !important; }
    .pro-card { box-shadow: none !important; border: 1px solid #CBD5E1 !important; break-inside: avoid; }
}

@media (max-width: 1024px) { .show-layout-grid { grid-template-columns: 1fr; } .kpi-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .land-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 14px !important; gap: 12px !important; }
    .land-show-container .header-content { flex: 1 1 100% !important; width: 100% !important; }
    .land-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .land-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid, .doc-files-grid { grid-template-columns: 1fr; }
    .pro-detail-item.full-width { grid-column: span 1; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .land-show-container .header-actions { grid-template-columns: 1fr !important; } }
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
