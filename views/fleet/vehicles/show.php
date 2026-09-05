<?php
$title = 'Vehicle Details';
$active = 'vehicles';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

$vehicle = $vehicle ?? $asset ?? [];
$documents = $documents ?? [];
$vehicleId = $vehicle['id'] ?? '';
$assetCode = $vehicle['asset_code'] ?? 'VEH-000';
$regNo = !empty($vehicle['registration_number']) ? $vehicle['registration_number'] : $assetCode;
$makeModel = ($vehicle['make_manufacturer'] ?? 'Vehicle') . ' ' . ($vehicle['model_year'] ?? '');
$status = $vehicle['operational_status'] ?? $vehicle['status'] ?? 'Active';
$condition = $vehicle['condition'] ?? 'Good';
$driver = !empty($vehicle['assigned_driver']) ? $vehicle['assigned_driver'] : (!empty($vehicle['assigned_officer']) ? $vehicle['assigned_officer'] : 'Pool Vehicle');
$rank = !empty($vehicle['assigned_rank']) ? $vehicle['assigned_rank'] : ($vehicle['driver_rank'] ?? 'N/A');
$nisNo = !empty($vehicle['assigned_nis']) ? $vehicle['assigned_nis'] : 'N/A';

$historyList = $maintenanceLogs ?? $history ?? [];
$totalHistory = count($historyList);

$locParts = [];
if (!empty($vehicle['lga_name'])) $locParts[] = $vehicle['lga_name'];
if (!empty($vehicle['state_name'])) $locParts[] = $vehicle['state_name'];
$fullLocation = !empty($locParts) ? implode(', ', $locParts) : (!empty($vehicle['current_location']) ? $vehicle['current_location'] : 'N/A');

$createdTime = !empty($vehicle['created_at']) ? date('d/m/Y H:i:s', strtotime($vehicle['created_at'])) : 'N/A';
$updatedTime = !empty($vehicle['updated_at']) ? date('d/m/Y H:i:s', strtotime($vehicle['updated_at'])) : 'N/A';
?>

<div class="container-fluid vehicle-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/fleet/vehicles"><i class="fas fa-car-side"></i> Fleet Management</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Comprehensive Details</span>
            </div>
            <h1 class="page-title">
                <?php echo Security::escape($makeModel); ?>
                <span class="header-badge-code" title="Click to copy Reg #" onclick="copyToClipboard('<?php echo Security::escape($regNo); ?>', 'Registration #')">
                    <?php echo Security::escape($regNo); ?> <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/fleet/vehicles" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
            <?php if (Auth::can('vehicle.edit') && !empty($vehicleId)): ?>
            <a href="<?php echo BASE_URL; ?>/fleet/vehicles/edit/<?php echo $vehicleId; ?>" class="pro-btn pro-btn-primary">
                <i class="fas fa-edit"></i> <span>Edit Vehicle</span>
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
            <div class="kpi-icon icon-serial"><i class="fas fa-id-card-clip"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Registration Number</span>
                <span class="kpi-value text-mono font-medium"><?php echo Security::escape($regNo); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-clipboard-check"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Condition</span>
                <span class="kpi-value">
                    <span class="custom-badge badge-success"><?php echo Security::escape($condition); ?></span>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-map-marker-alt"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Location</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($fullLocation); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-user-gear"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Assigned Custodian</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($driver); ?></span>
            </div>
        </div>
    </div>

    <!-- Main Content Layout (2 Columns) -->
    <div class="show-layout-grid">
        <div class="show-main-column">
            <!-- 1. Location Information Section -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Location & Station Information</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Zone</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['zone_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Command / Formation</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['command_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">State</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['state_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Local Government Area (LGA)</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['lga_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item full-width">
                            <span class="item-label">Current Station / Location Description</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['current_location'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Vehicle Specifications Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sliders"></i> Basic Information & Vehicle Specifications</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Asset Code</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($assetCode); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Registration Number</span>
                            <span class="item-value text-mono font-medium text-success"><?php echo Security::escape($regNo); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Make & Manufacturer</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['make_manufacturer'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Model Year</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['model_year'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Vehicle Type</span>
                            <span class="item-value font-medium">
                                <?php echo Security::escape(!empty($vehicle['vehicle_type']) ? ($vehicle['vehicle_type'] === 'Other' ? ($vehicle['vehicle_type_other'] ?? 'Other') : $vehicle['vehicle_type']) : 'N/A'); ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Color</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['color'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Use / Purpose</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['use_purpose'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Ownership Type</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['ownership_type'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Engine & Technical Details Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-gears"></i> Engine & Technical Specifications</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">VIN / Chassis Number</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($vehicle['vin_chassis_number'] ?? $vehicle['chassis_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Engine Number</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($vehicle['engine_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Engine Capacity</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['engine_capacity'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Fuel Type</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['fuel_type'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Current Odometer / Mileage</span>
                            <span class="item-value font-medium text-primary">
                                <?php echo !empty($vehicle['mileage']) ? number_format($vehicle['mileage']) . ' km' : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Condition Rating</span>
                            <span class="item-value font-medium"><?php echo Security::escape($condition); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Acquisition & Financial Details Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Acquisition & Financial Details</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Acquisition Mode</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['acquisition_type'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Acquisition Date</span>
                            <span class="item-value font-medium">
                                <?php echo !empty($vehicle['acquisition_date']) ? date('d/m/Y', strtotime($vehicle['acquisition_date'])) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Purchase Value</span>
                            <span class="item-value font-medium text-success">
                                <?php echo !empty($vehicle['purchase_value']) ? '₦' . number_format($vehicle['purchase_value'], 2) : 'N/A'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Maintenance & Vendor Details Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-wrench"></i> Service & Maintenance Log Summary</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Last Service Date</span>
                            <span class="item-value font-medium">
                                <?php echo !empty($vehicle['last_service_date']) ? date('d/m/Y', strtotime($vehicle['last_service_date'])) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Next Service Date</span>
                            <span class="item-value font-medium text-warning">
                                <?php echo !empty($vehicle['next_service_date']) ? date('d/m/Y', strtotime($vehicle['next_service_date'])) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Last Maintenance Cost</span>
                            <span class="item-value font-medium text-success">
                                <?php echo !empty($vehicle['last_maintenance_cost']) ? '₦' . number_format($vehicle['last_maintenance_cost'], 2) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Maintenance Vendor / Workshop</span>
                            <span class="item-value font-medium"><?php echo Security::escape($vehicle['maintenance_vendor'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Uploaded Supporting Documents Card -->
            <div class="pro-card">
                <div class="pro-card-header flex-between">
                    <h3><i class="fas fa-paperclip"></i> Uploaded Vehicle Documents & License Files</h3>
                    <span class="history-count-badge"><?php echo count($documents ?? []); ?> Attached File(s)</span>
                </div>
                <div class="pro-card-body">
                    <?php if (empty($documents)): ?>
                        <div class="pro-empty-state">
                            <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                            <p>No documents uploaded for this vehicle.</p>
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
            <?php if (!empty($vehicle['remarks'])): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-note-sticky"></i> Remarks & Historical Notes</h3>
                </div>
                <div class="pro-card-body">
                    <div class="remarks-box">
                        <?php echo nl2br(Security::escape($vehicle['remarks'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Column (Right 30%) -->
        <div class="show-sidebar-column">
            <!-- Assignment & Custody Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-user-gear"></i> Vehicle Custody & Assignment</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Assigned Officer / Driver</span>
                        <span class="side-value font-semibold"><?php echo Security::escape($driver); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Rank</span>
                        <span class="side-value font-medium"><?php echo Security::escape($rank); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">NIS Service Number</span>
                        <span class="side-value text-mono font-medium"><?php echo Security::escape($nisNo); ?></span>
                    </div>
                </div>
            </div>

            <!-- Compliance Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-clipboard-check"></i> Service & Insurance</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Insurance Status</span>
                        <span class="side-value font-medium"><?php echo Security::escape($vehicle['insurance_status'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Insurance Expiry</span>
                        <span class="side-value text-mono">
                            <?php echo !empty($vehicle['insurance_expiry']) ? date('d/m/Y', strtotime($vehicle['insurance_expiry'])) : 'N/A'; ?>
                        </span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Next Service Due</span>
                        <span class="side-value text-mono text-warning">
                            <?php echo !empty($vehicle['next_service_date']) ? date('d/m/Y', strtotime($vehicle['next_service_date'])) : 'N/A'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Record Metadata Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Record Metadata</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Logged By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($vehicle['created_by_name'] ?? 'System Administrator'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Created Timestamp</span>
                        <span class="side-value text-mono small"><?php echo Security::escape($createdTime); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Last Modified</span>
                        <span class="side-value text-mono small"><?php echo Security::escape($updatedTime); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Actions Bar -->
    <div class="pro-bottom-actions">
        <?php if (Auth::can('fleet.edit') && !empty($vehicleId)): ?>
        <a href="<?php echo BASE_URL; ?>/fleet/vehicles/edit/<?php echo $vehicleId; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-edit"></i> <span>Edit Vehicle</span>
        </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/fleet/vehicles" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to Vehicles</span>
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

.vehicle-show-container { padding-bottom: 40px; }
.vehicle-show-container .page-header { display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 16px !important; background: #ffffff !important; padding: 20px 24px !important; border-radius: 12px !important; border: 1px solid #E2E8F0 !important; margin-bottom: 24px !important; }
.vehicle-show-container .header-content { flex: 1 1 280px !important; }
.vehicle-show-container .header-content h1.page-title { font-size: 1.25rem !important; font-weight: 600 !important; color: #1E293B !important; margin: 4px 0 0 0 !important; display: flex !important; align-items: center !important; flex-wrap: wrap !important; gap: 10px !important; letter-spacing: normal !important; }
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
.vehicle-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; }
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
    .vehicle-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 16px !important; gap: 12px !important; }
    .vehicle-show-container .header-content h1 { font-size: 1.25rem !important; }
    .vehicle-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .vehicle-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid, .doc-files-grid { grid-template-columns: 1fr; }
    .pro-detail-item.full-width { grid-column: span 1; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .vehicle-show-container .header-actions { grid-template-columns: 1fr !important; } }
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

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>