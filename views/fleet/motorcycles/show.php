<?php
$title = 'Motorcycle Details';
$active = 'motorcycles';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

$motorcycleId = $motorcycle['id'] ?? 0;
$assetCode = $motorcycle['asset_code'] ?? 'N/A';
$regNo = $motorcycle['registration_number'] ?? 'N/A';
$makeModel = $motorcycle['make_model'] ?? 'Motorcycle';
$motoType = !empty($motorcycle['motorcycle_type']) ? ($motorcycle['motorcycle_type'] === 'Other' ? ($motorcycle['motorcycle_type_other'] ?? 'Other') : $motorcycle['motorcycle_type']) : 'N/A';
$condition = $motorcycle['condition'] ?? 'Good';
$currentLocation = $motorcycle['current_location'] ?? 'N/A';
$assignedOfficerUnit = $motorcycle['assigned_officer_unit'] ?? 'N/A';
$mileage = isset($motorcycle['current_mileage']) ? number_format((float)$motorcycle['current_mileage'], 0) : '0';
$purchaseValue = isset($motorcycle['purchase_value']) ? '₦' . number_format((float)$motorcycle['purchase_value'], 2) : 'N/A';
$currentValue = isset($motorcycle['current_value']) ? '₦' . number_format((float)$motorcycle['current_value'], 2) : 'N/A';

$documents = $documents ?? [];
?>

<div class="container-fluid moto-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/fleet/motorcycles"><i class="fas fa-motorcycle"></i> Fleet Management</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Motorcycle Details</span>
            </div>
            <h1 class="page-title">
                <?php echo Security::escape($makeModel); ?>
                <span class="header-badge-code" title="Click to copy Registration #" onclick="copyToClipboard('<?php echo Security::escape($regNo); ?>', 'Registration #')">
                    <?php echo Security::escape($regNo); ?> <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/fleet/motorcycles" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
            <?php if (Auth::can('fleet.edit') && !empty($motorcycleId)): ?>
            <a href="<?php echo BASE_URL; ?>/fleet/motorcycles/edit/<?php echo $motorcycleId; ?>" class="pro-btn pro-btn-primary">
                <i class="fas fa-edit"></i> <span>Edit Record</span>
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
            <div class="kpi-icon icon-serial"><i class="fas fa-id-card"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Registration No</span>
                <span class="kpi-value text-mono font-medium"><?php echo Security::escape($regNo); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-shield-alt"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Condition</span>
                <span class="kpi-value">
                    <?php
                    $badgeClass = 'badge-success';
                    if ($condition === 'Fair') $badgeClass = 'badge-warning';
                    elseif ($condition === 'Poor') $badgeClass = 'badge-danger';
                    ?>
                    <span class="custom-badge <?php echo $badgeClass; ?>"><?php echo Security::escape($condition); ?></span>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-map-marker-alt"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Current Location</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($currentLocation); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-officer"><i class="fas fa-user-shield"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Assigned Officer / Unit</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($assignedOfficerUnit); ?></span>
            </div>
        </div>
    </div>

    <!-- Layout Grid (2 Columns) -->
    <div class="show-layout-grid">
        <!-- Main Column -->
        <div class="show-main-column">
            
            <!-- 1. Location & Station Assignment Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Location & Station Assignment</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Zone</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motorcycle['zone_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Command / Formation</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motorcycle['command_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">State</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motorcycle['state_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Local Government Area (LGA)</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motorcycle['lga_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Current Location / Garage</span>
                            <span class="item-value font-medium"><?php echo Security::escape($currentLocation); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Assigned Officer / Unit</span>
                            <span class="item-value font-medium"><?php echo Security::escape($assignedOfficerUnit); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Basic Information & Motorcycle Specifications -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sliders-h"></i> Basic Information & Specifications</h3>
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
                            <span class="item-label">Make & Model</span>
                            <span class="item-value font-medium"><?php echo Security::escape($makeModel); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Motorcycle Type</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motoType); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Engine Capacity</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motorcycle['engine_capacity'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Chassis / VIN Number</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($motorcycle['chassis_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Engine Number</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($motorcycle['engine_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Fuel Type</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motorcycle['fuel_type'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Current Mileage</span>
                            <span class="item-value font-medium"><?php echo Security::escape($mileage); ?> km</span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Use Purpose</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motorcycle['use_purpose'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Ownership Type</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motorcycle['ownership_type'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Acquisition & Financial Valuation Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Acquisition & Valuation Details</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Acquisition Type</span>
                            <span class="item-value font-medium"><?php echo Security::escape($motorcycle['acquisition_type'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Acquisition Date</span>
                            <span class="item-value font-medium"><?php echo !empty($motorcycle['acquisition_date']) ? date('d/m/Y', strtotime($motorcycle['acquisition_date'])) : 'N/A'; ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Purchase Value</span>
                            <span class="item-value font-medium text-success"><?php echo Security::escape($purchaseValue); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Current Estimated Value</span>
                            <span class="item-value font-medium"><?php echo Security::escape($currentValue); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Condition, Insurance & Service Schedule -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-clipboard-check"></i> Status, Insurance & Maintenance Schedule</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Condition</span>
                            <span class="item-value font-medium">
                                <span class="custom-badge <?php echo $badgeClass; ?>"><?php echo Security::escape($condition); ?></span>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Insurance Status</span>
                            <span class="item-value font-medium">
                                <?php
                                $insStatus = $motorcycle['insurance_status'] ?? 'N/A';
                                $insBadge = 'badge-success';
                                if ($insStatus === 'Expired') $insBadge = 'badge-danger';
                                elseif ($insStatus === 'Not Insured') $insBadge = 'badge-warning';
                                ?>
                                <span class="custom-badge <?php echo $insBadge; ?>"><?php echo Security::escape($insStatus); ?></span>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Insurance Expiry Date</span>
                            <span class="item-value font-medium"><?php echo !empty($motorcycle['insurance_expiry']) ? date('d/m/Y', strtotime($motorcycle['insurance_expiry'])) : 'N/A'; ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Last Serviced Date</span>
                            <span class="item-value font-medium"><?php echo !empty($motorcycle['last_serviced_date']) ? date('d/m/Y', strtotime($motorcycle['last_serviced_date'])) : 'N/A'; ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Next Service Due</span>
                            <span class="item-value font-medium"><?php echo !empty($motorcycle['next_service_due']) ? date('d/m/Y', strtotime($motorcycle['next_service_due'])) : 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Remarks Card -->
            <?php if (!empty($motorcycle['remarks'])): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sticky-note"></i> Remarks & Additional Notes</h3>
                </div>
                <div class="pro-card-body">
                    <p class="remarks-text font-medium"><?php echo nl2br(Security::escape($motorcycle['remarks'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- 6. Supporting Documents Section -->
            <div class="pro-card">
                <div class="pro-card-header flex-between">
                    <h3><i class="fas fa-folder-open"></i> Motorcycle Attachments & Documents</h3>
                    <span class="badge badge-info"><?php echo count($documents); ?> document(s)</span>
                </div>
                <div class="pro-card-body">
                    <?php if (!empty($documents)): ?>
                    <div class="documents-grid">
                        <?php foreach ($documents as $doc): ?>
                        <?php
                        $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                        $iconClass = 'fa-file-pdf text-danger';
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $iconClass = 'fa-file-image text-primary';
                        elseif (in_array($ext, ['doc', 'docx'])) $iconClass = 'fa-file-word text-info';
                        elseif (in_array($ext, ['xls', 'xlsx'])) $iconClass = 'fa-file-excel text-success';

                        $rawPath = str_replace('\\', '/', $doc['file_path']);
                        if (strpos($rawPath, 'htdocs/nis_ams/') !== false) {
                            $rel = substr($rawPath, strpos($rawPath, 'htdocs/nis_ams/') + strlen('htdocs/nis_ams/'));
                            $fileUrl = BASE_URL . '/' . ltrim($rel, '/');
                        } else {
                            $fileUrl = BASE_URL . '/' . ltrim($rawPath, '/');
                        }
                        ?>
                        <div class="doc-card">
                            <div class="doc-icon"><i class="fas <?php echo $iconClass; ?>"></i></div>
                            <div class="doc-info">
                                <span class="doc-name" title="<?php echo Security::escape($doc['file_name']); ?>"><?php echo Security::escape($doc['file_name']); ?></span>
                                <span class="doc-meta">
                                    <?php echo !empty($doc['document_type']) ? ucfirst(Security::escape($doc['document_type'])) . ' • ' : ''; ?>
                                    <?php echo round($doc['file_size'] / 1024, 1); ?> KB
                                </span>
                            </div>
                            <div class="doc-actions">
                                <a href="<?php echo Security::escape($fileUrl); ?>" target="_blank" class="btn-doc-action" title="View Document">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo Security::escape($fileUrl); ?>" download class="btn-doc-action" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-docs-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No supporting documents uploaded for this motorcycle.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Sidebar Column -->
        <div class="show-sidebar-column">
            <!-- Record Metadata Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Record Metadata</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Asset Code</span>
                        <span class="side-value text-mono font-medium"><?php echo Security::escape($assetCode); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Registration #</span>
                        <span class="side-value text-mono font-medium text-success"><?php echo Security::escape($regNo); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Logged By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($motorcycle['created_by_name'] ?? 'System Admin'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Created Date</span>
                        <span class="side-value text-mono small"><?php echo !empty($motorcycle['created_at']) ? date('d/m/Y H:i', strtotime($motorcycle['created_at'])) : 'N/A'; ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Last Updated</span>
                        <span class="side-value text-mono small"><?php echo !empty($motorcycle['updated_at']) ? date('d/m/Y H:i', strtotime($motorcycle['updated_at'])) : 'N/A'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Status Overview -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-tachometer-alt"></i> Asset Overview</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Use Purpose</span>
                        <span class="side-value font-medium"><?php echo Security::escape($motorcycle['use_purpose'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Ownership</span>
                        <span class="side-value font-medium"><?php echo Security::escape($motorcycle['ownership_type'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Condition</span>
                        <span class="side-value font-medium"><?php echo Security::escape($condition); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Actions -->
    <div class="pro-bottom-actions">
        <?php if (Auth::can('fleet.edit') && !empty($motorcycleId)): ?>
        <a href="<?php echo BASE_URL; ?>/fleet/motorcycles/edit/<?php echo $motorcycleId; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-edit"></i> <span>Edit Record</span>
        </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/fleet/motorcycles" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to List</span>
        </a>
    </div>
</div>

<div id="copyToast" class="copy-toast"></div>

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

.moto-show-container { padding-bottom: 40px; }
.moto-show-container .page-header { display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 16px !important; background: #ffffff !important; padding: 20px 24px !important; border-radius: 12px !important; border: 1px solid #E2E8F0 !important; margin-bottom: 24px !important; }
.moto-show-container .header-content { flex: 1 1 280px !important; }
.moto-show-container .header-content h1.page-title { font-size: 1.25rem !important; font-weight: 600 !important; color: #1E293B !important; margin: 4px 0 0 0 !important; display: flex !important; align-items: center !important; flex-wrap: wrap !important; gap: 10px !important; letter-spacing: normal !important; }
.header-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px; }
.header-breadcrumb a { color: var(--nis-emerald); text-decoration: none; font-weight: 500; }
.breadcrumb-separator { font-size: 0.7rem; color: #94A3B8; }
.header-badge-code { display: inline-flex; align-items: center; gap: 6px; background: #F1F5F9; color: var(--nis-forest); border: 1px solid #CBD5E1; font-family: 'SF Mono', monospace; font-size: 0.9rem; padding: 3px 10px; border-radius: 6px; cursor: pointer; }
.pro-btn { display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; padding: 9px 18px !important; font-size: 0.88rem !important; font-weight: 600 !important; border-radius: 8px !important; white-space: nowrap !important; height: 40px !important; border: 1px solid transparent !important; text-decoration: none !important; }
.pro-btn span { display: inline-block !important; color: inherit !important; background: transparent !important; }
.pro-btn i { font-size: 0.95rem !important; color: inherit !important; }
.pro-btn-secondary { background: #F1F5F9 !important; color: #334155 !important; border-color: #CBD5E1 !important; }
.pro-btn-primary { background: #134617 !important; color: #FFFFFF !important; }
.pro-btn-outline { background: #FFFFFF !important; color: #0F172A !important; border-color: #94A3B8 !important; }
.moto-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; }

/* KPI Section */
.kpi-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; }
.kpi-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.icon-serial { background: #E0F2FE; color: #0284C7; }
.icon-condition { background: #DCFCE7; color: #16A34A; }
.icon-location { background: #FEF3C7; color: #D97706; }
.icon-officer { background: #F3E8FF; color: #9333EA; }
.kpi-details { display: flex; flex-direction: column; gap: 2px; }
.kpi-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: #64748B; }
.kpi-value { font-size: 0.95rem; color: #1E293B; }
.custom-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
.badge-success { background: #DEF7EC; color: #03543F; }
.badge-warning { background: #FEF08A; color: #854D0E; }
.badge-danger { background: #FEE2E2; color: #991B1B; }
.badge-info { background: #E0F2FE; color: #0369A1; }

/* Main Grid & Cards */
.show-layout-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 24px; margin-bottom: 24px; }
.pro-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; margin-bottom: 24px; overflow: hidden; }
.pro-card-header { padding: 16px 20px; background: #F8FAFC; border-bottom: 1px solid var(--border-light); }
.pro-card-header.flex-between { display: flex; justify-content: space-between; align-items: center; }
.pro-card-header h3 { margin: 0; font-size: 1.05rem; font-weight: 600; color: var(--nis-forest); display: flex; align-items: center; gap: 10px; }
.pro-card-body { padding: 20px; }
.pro-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px 24px; }
.pro-detail-item { display: flex; flex-direction: column; gap: 4px; }
.pro-detail-item.full-width { grid-column: 1 / -1; }
.item-label { font-size: 0.75rem; text-transform: uppercase; color: #64748B; font-weight: 600; }
.item-value { font-size: 0.9rem; color: #1E293B; }
.pro-side-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F1F5F9; }
.side-label { font-size: 0.85rem; color: var(--text-muted); }
.side-value { font-size: 0.9rem; color: var(--text-dark); }
.text-mono { font-family: 'SF Mono', monospace; }
.font-medium { font-weight: 500; }
.text-success { color: #16A34A; }
.remarks-text { font-size: 0.95rem; color: #334155; line-height: 1.6; margin: 0; }

/* Documents Grid */
.documents-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
.doc-card { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; transition: all 0.2s ease; }
.doc-card:hover { border-color: #2E7D32; background: #F0FDF4; }
.doc-icon { font-size: 1.4rem; }
.doc-info { display: flex; flex-direction: column; gap: 2px; flex: 1; overflow: hidden; }
.doc-name { font-size: 0.88rem; font-weight: 500; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.doc-meta { font-size: 0.75rem; color: #64748B; }
.doc-actions { display: flex; gap: 6px; }
.btn-doc-action { width: 32px; height: 32px; border-radius: 6px; background: white; border: 1px solid #CBD5E1; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; text-decoration: none; transition: all 0.2s; }
.btn-doc-action:hover { background: #134617; color: white; border-color: #134617; }
.empty-docs-state { text-align: center; padding: 30px 20px; color: #94A3B8; }
.empty-docs-state i { font-size: 2.2rem; margin-bottom: 8px; display: block; }
.empty-docs-state p { margin: 0; font-size: 0.9rem; }

.pro-bottom-actions { display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-light); }
.copy-toast { position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: white; padding: 10px 18px; border-radius: 8px; opacity: 0; pointer-events: none; z-index: 9999; transition: opacity 0.3s; }
.copy-toast.show { opacity: 1; }

@media (max-width: 1024px) { .show-layout-grid { grid-template-columns: 1fr; } .kpi-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .moto-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 16px !important; gap: 14px !important; }
    .moto-show-container .header-content { width: 100% !important; }
    .moto-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .moto-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid { grid-template-columns: 1fr; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .moto-show-container .header-actions { grid-template-columns: 1fr !important; } }
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
