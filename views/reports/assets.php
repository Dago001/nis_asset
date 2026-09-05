<?php
$title = 'Asset Reports';
$active = 'asset-reports';
$init_charts = true;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$type = $_GET['type'] ?? 'summary';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$search = isset($search) ? (string) $search : ($_GET['search'] ?? '');
$status = isset($status) ? (string) $status : ($_GET['status'] ?? '');

// Ensure $data is passed from controller and is an array
$data = isset($data) && is_array($data) ? $data : [];

// Pagination for the individual-type views (land/buildings/rented/movable/ict/projects) —
// see ReportController::assets(). 'summary' is a fixed-size overview, not paged.
$page = isset($page) ? (int) $page : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$totalCount = isset($totalCount) ? (int) $totalCount : count($data);

// Extra data for the 'summary' type's Visual Analytics section
$summaryCounts = isset($summaryCounts) && is_array($summaryCounts) ? $summaryCounts : [];
$valueByCategory = isset($valueByCategory) && is_array($valueByCategory) ? $valueByCategory : [];
$zoneBreakdown = isset($zoneBreakdown) && is_array($zoneBreakdown) ? $zoneBreakdown : [];

// Status/condition options per type, for the filter dropdown — kept here
// (rather than a DISTINCT query) since these are the values the seed/real
// data actually uses per column.
$statusOptionsByType = [
    'land' => ['Active', 'Developed', 'Vacant', 'Disputed', 'Disposed'],
    'buildings' => ['Good', 'Fair', 'Poor', 'Under Renovation', 'Condemned'],
    'rented' => ['Active', 'Expired', 'Terminated', 'Under Negotiation'],
    'movable' => ['Good', 'Fair', 'Poor', 'Under Repair', 'Condemned'],
    'ict' => ['Active', 'In Repair', 'Retired', 'Disposed'],
    'projects' => ['In Progress', 'Completed', 'Delayed', 'Suspended', 'Cancelled'],
];
$currentStatusOptions = $statusOptionsByType[$type] ?? [];

// Shared base query string for pagination links, so paging forward/back
// never silently drops the active search/status/date filters.
$pageQueryBase = http_build_query(array_filter([
    'type' => $type,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'search' => $search,
    'status' => $status,
]));
$pageQueryBase = $pageQueryBase ? ($pageQueryBase . '&') : '';

// For summary type, ensure data has all required keys
if ($type == 'summary') {
    $data = array_merge([
        'land' => [],
        'buildings' => [],
        'rented' => [],
        'movable' => [],
        'ict' => [],
        'projects' => []
    ], $data);
}

// Check if we have data to display
$hasData = !empty($data);

// For summary type, check if any category has data
if ($type == 'summary') {
    $hasData = !empty($data['land']) || !empty($data['buildings']) || !empty($data['rented']) ||
               !empty($data['movable']) || !empty($data['ict']) || !empty($data['projects']);
} elseif ($type == 'projects') {
    $hasData = !empty($data);
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-boxes"></i>
                Asset Reports
            </h1>
            <p>Generate comprehensive asset reports</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-success" onclick="saveReport()" <?php echo !$hasData ? 'disabled' : ''; ?>>
                <i class="fas fa-bookmark"></i> Save Report
            </button>
            <div class="btn-group">
                <button class="btn btn-outline" onclick="window.print()" <?php echo !$hasData ? 'disabled' : ''; ?>>
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-outline" onclick="exportReport('pdf')" <?php echo !$hasData ? 'disabled' : ''; ?>>
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button class="btn btn-outline" onclick="exportReport('csv')" <?php echo !$hasData ? 'disabled' : ''; ?>>
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button class="btn btn-outline" onclick="exportReport('excel')" <?php echo !$hasData ? 'disabled' : ''; ?>>
                    <i class="fas fa-file-excel"></i> Excel
                </button>
            </div>
            <a href="<?php echo BASE_URL; ?>/reports" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Report Controls -->
    <div class="report-controls">
        <form method="GET" action="<?php echo BASE_URL; ?>/reports/assets" id="reportForm">
            <div class="controls-grid">
                <div class="control-group">
                    <label>Report Type</label>
                    <select name="type" id="reportType" onchange="this.form.submit()">
                        <option value="summary" <?php echo $type == 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                        <option value="land" <?php echo $type == 'land' ? 'selected' : ''; ?>>Land Assets</option>
                        <option value="buildings" <?php echo $type == 'buildings' ? 'selected' : ''; ?>>Building Assets</option>
                        <option value="rented" <?php echo $type == 'rented' ? 'selected' : ''; ?>>Rented Properties</option>
                        <option value="projects" <?php echo $type == 'projects' ? 'selected' : ''; ?>>Ongoing Projects</option>
                        <option value="movable" <?php echo $type == 'movable' ? 'selected' : ''; ?>>Movable Assets</option>
                        <option value="ict" <?php echo $type == 'ict' ? 'selected' : ''; ?>>ICT Assets</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" onchange="this.form.submit()">
                </div>
                <div class="control-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" onchange="this.form.submit()">
                </div>
                <?php if ($type !== 'summary'): ?>
                <div class="control-group">
                    <label>Search</label>
                    <input type="text" name="search" id="searchInput" placeholder="Code, name, contractor..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="control-group">
                    <label><?php echo in_array($type, ['buildings', 'movable']) ? 'Condition' : 'Status'; ?></label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All</option>
                        <?php foreach ($currentStatusOptions as $opt): ?>
                        <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo $status === $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="control-group control-group-btn">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Report Content -->
    <div class="report-content" id="reportContent">
        <?php if (!$hasData): ?>
            <div class="empty-state">
                <i class="fas fa-boxes"></i>
                <p>No asset data found for the selected period.</p>
            </div>
        <?php elseif ($type == 'summary'): ?>
            <!-- Summary Report -->
            <div class="report-section">
                <h2>Asset Summary Report</h2>
                <p class="report-period">Period: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?></p>
                
                <!-- Summary Cards (real COUNT(*) for the period — not just the capped
                     100-row preview below, see ReportController::getAssetPageSummaryCounts()).
                     These now double as tabs: click one to show just that
                     category's table below instead of scrolling through all
                     six stacked one after another. -->
                <?php
                $landCount = $summaryCounts['land'] ?? count($data['land'] ?? []);
                $buildingCount = $summaryCounts['buildings'] ?? count($data['buildings'] ?? []);
                $rentedCount = $summaryCounts['rented'] ?? count($data['rented'] ?? []);
                $movableCount = $summaryCounts['movable'] ?? count($data['movable'] ?? []);
                $ictCount = $summaryCounts['ict'] ?? count($data['ict'] ?? []);
                $projectCount = $summaryCounts['projects'] ?? count($data['projects'] ?? []);
                $totalAssets = $landCount + $buildingCount + $rentedCount + $movableCount + $ictCount + $projectCount;

                $assetTabs = [
                    'land'      => ['label' => 'Land Assets', 'count' => $landCount,     'gradient' => '#2E7D32 0%, #4CAF50 100%', 'icon' => 'fa-map-marked-alt', 'has' => !empty($data['land'])],
                    'buildings' => ['label' => 'Buildings',   'count' => $buildingCount, 'gradient' => '#B71C1C 0%, #D32F2F 100%', 'icon' => 'fa-building',       'has' => !empty($data['buildings'])],
                    'rented'    => ['label' => 'Rented',      'count' => $rentedCount,   'gradient' => '#B26A00 0%, #FF8F00 100%', 'icon' => 'fa-key',            'has' => !empty($data['rented'])],
                    'movable'   => ['label' => 'Movable',     'count' => $movableCount,  'gradient' => '#6A1B9A 0%, #8E24AA 100%', 'icon' => 'fa-dolly',          'has' => !empty($data['movable'])],
                    'ict'       => ['label' => 'ICT',         'count' => $ictCount,      'gradient' => '#00796B 0%, #009688 100%', 'icon' => 'fa-laptop',         'has' => !empty($data['ict'])],
                    'projects'  => ['label' => 'Projects',    'count' => $projectCount,  'gradient' => '#37474F 0%, #607D8B 100%', 'icon' => 'fa-drafting-compass', 'has' => !empty($data['projects'])],
                ];
                // Land is the first tab; default to it, but if it happens to be
                // empty, land on the first category that actually has rows.
                $defaultTab = 'land';
                if (empty($assetTabs[$defaultTab]['has'])) {
                    foreach ($assetTabs as $key => $tab) {
                        if ($tab['has']) { $defaultTab = $key; break; }
                    }
                }
                // Small helper so each empty panel says what's missing, instead
                // of a tab you can click into that just shows nothing.
                $renderEmptyPanelNotice = function ($label) {
                    echo '<div class="empty-state" style="padding:30px 20px; margin:0;"><p>No ' . htmlspecialchars($label) . ' records for this period.</p></div>';
                };
                ?>
                <div class="summary-total-line"><i class="fas fa-boxes"></i> Total Assets: <strong><?php echo number_format($totalAssets); ?></strong></div>
                <div class="summary-cards" role="tablist" aria-label="Asset category">
                    <?php foreach ($assetTabs as $key => $tab): ?>
                    <button type="button"
                            class="summary-card summary-tab-card<?php echo $key === $defaultTab ? ' active' : ''; ?>"
                            style="background: linear-gradient(135deg, <?php echo $tab['gradient']; ?>);"
                            onclick="showAssetPanel('<?php echo $key; ?>', this)"
                            role="tab"
                            aria-selected="<?php echo $key === $defaultTab ? 'true' : 'false'; ?>">
                        <div class="card-title"><i class="fas <?php echo $tab['icon']; ?>"></i> <?php echo htmlspecialchars($tab['label']); ?></div>
                        <div class="card-value"><?php echo number_format($tab['count']); ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Visual Analytics -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h3><i class="fas fa-chart-pie"></i> Assets by Type</h3>
                        <div class="chart-wrap">
                            <canvas id="assetsTypeChart"></canvas>
                            <div class="chart-empty" id="assetsTypeChartEmpty" style="display:none;">No data available</div>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3><i class="fas fa-chart-bar"></i> Assets by Zone (All-Time)</h3>
                        <div class="chart-wrap">
                            <canvas id="assetsZoneChart"></canvas>
                            <div class="chart-empty" id="assetsZoneChartEmpty" style="display:none;">No data available</div>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3><i class="fas fa-coins"></i> Value by Category (Period)</h3>
                        <div class="chart-wrap">
                            <canvas id="assetsValueChart"></canvas>
                            <div class="chart-empty" id="assetsValueChartEmpty" style="display:none;">No data available</div>
                        </div>
                    </div>
                </div>

                <div class="asset-panel<?php echo $defaultTab === 'land' ? ' active' : ''; ?>" data-panel="land">
                <?php if (!empty($data['land'])): ?>
                <h3>Land Assets</h3>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Title Holder</th>
                                <th>Location</th>
                                <th>Size</th>
                                <th>Status</th>
                                <th>Date Acquired</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['land'] as $asset): ?>
                            <?php 
                            $status = $asset['status'] ?? 'Unknown';
                            $statusClass = $status == 'Active' ? 'badge-success' : 'badge-warning';
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($asset['title_holder']) ? htmlspecialchars($asset['title_holder']) : '-'; ?></td>
                                <td><?php echo (isset($asset['state_name']) ? htmlspecialchars($asset['state_name']) : '') . ', ' . (isset($asset['lga_name']) ? htmlspecialchars($asset['lga_name']) : ''); ?></td>
                                <td><?php echo isset($asset['size']) ? number_format($asset['size'], 2) . ' ' . ($asset['size_unit'] ?? '') : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td><?php echo isset($asset['date_acquired']) ? date('d/m/Y', strtotime($asset['date_acquired'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: $renderEmptyPanelNotice('Land Assets'); ?>
                <?php endif; ?>
                </div>

                <div class="asset-panel<?php echo $defaultTab === 'buildings' ? ' active' : ''; ?>" data-panel="buildings">
                <?php if (!empty($data['buildings'])): ?>
                <h3>Building Assets</h3>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Building Name</th>
                                <th>Location</th>
                                <th>Floors</th>
                                <th>Condition</th>
                                <th>Contract Sum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['buildings'] as $asset): ?>
                            <?php 
                            $condition = $asset['condition_status'] ?? 'Unknown';
                            $conditionClass = $condition == 'Good' ? 'badge-success' : 'badge-warning';
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($asset['building_name']) ? htmlspecialchars($asset['building_name']) : '-'; ?></td>
                                <td><?php echo (isset($asset['state_name']) ? htmlspecialchars($asset['state_name']) : '') . ', ' . (isset($asset['lga_name']) ? htmlspecialchars($asset['lga_name']) : ''); ?></td>
                                <td class="text-center"><?php echo $asset['floor_count'] ?? '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $conditionClass; ?>">
                                        <?php echo htmlspecialchars($condition); ?>
                                    </span>
                                </td>
                                <td class="text-right"><?php echo isset($asset['contract_sum']) ? '₦' . number_format($asset['contract_sum'], 2) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: $renderEmptyPanelNotice('Building Assets'); ?>
                <?php endif; ?>
                </div>

                <div class="asset-panel<?php echo $defaultTab === 'rented' ? ' active' : ''; ?>" data-panel="rented">
                <?php if (!empty($data['rented'])): ?>
                <h3>Rented Properties</h3>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Address</th>
                                <th>Location</th>
                                <th>Annual Rent</th>
                                <th>Landlord</th>
                                <th>Lease Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['rented'] as $asset): ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($asset['property_address']) ? htmlspecialchars($asset['property_address']) : '-'; ?></td>
                                <td><?php echo (isset($asset['state_name']) ? htmlspecialchars($asset['state_name']) : '') . ', ' . (isset($asset['lga_name']) ? htmlspecialchars($asset['lga_name']) : ''); ?></td>
                                <td class="text-right"><?php echo isset($asset['annual_rent']) ? '₦' . number_format($asset['annual_rent'], 2) : '-'; ?></td>
                                <td><?php echo isset($asset['owner_lessor_name']) ? htmlspecialchars($asset['owner_lessor_name']) : '-'; ?></td>
                                <td><?php echo isset($asset['expiry_date']) ? date('d/m/Y', strtotime($asset['expiry_date'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: $renderEmptyPanelNotice('Rented Properties'); ?>
                <?php endif; ?>
                </div>

                <div class="asset-panel<?php echo $defaultTab === 'movable' ? ' active' : ''; ?>" data-panel="movable">
                <?php if (!empty($data['movable'])): ?>
                <h3>Movable Assets</h3>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Asset Type</th>
                                <th>Make/Model</th>
                                <th>Condition</th>
                                <th>Location</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['movable'] as $asset): ?>
                            <?php
                            $condition = $asset['condition_status'] ?? 'Unknown';
                            $conditionClass = $condition == 'Good' ? 'badge-success' : 'badge-warning';
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($asset['asset_type']) ? htmlspecialchars($asset['asset_type']) : '-'; ?></td>
                                <td><?php echo isset($asset['make_model']) ? htmlspecialchars($asset['make_model']) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $conditionClass; ?>">
                                        <?php echo htmlspecialchars($condition); ?>
                                    </span>
                                </td>
                                <td><?php echo isset($asset['current_location']) ? htmlspecialchars($asset['current_location']) : '-'; ?></td>
                                <td class="text-right"><?php echo isset($asset['current_value']) ? '₦' . number_format($asset['current_value'], 2) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: $renderEmptyPanelNotice('Movable Assets'); ?>
                <?php endif; ?>
                </div>

                <div class="asset-panel<?php echo $defaultTab === 'ict' ? ' active' : ''; ?>" data-panel="ict">
                <?php if (!empty($data['ict'])): ?>
                <h3>ICT Assets</h3>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Model/Version</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['ict'] as $asset): ?>
                            <?php
                            $condition = $asset['current_status'] ?? 'Unknown';
                            $conditionClass = $condition == 'Active' ? 'badge-success' : 'badge-warning';
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($asset['asset_description']) ? htmlspecialchars($asset['asset_description']) : '-'; ?></td>
                                <td><?php echo isset($asset['asset_category']) ? htmlspecialchars($asset['asset_category']) : '-'; ?></td>
                                <td><?php echo isset($asset['model_version']) ? htmlspecialchars($asset['model_version']) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $conditionClass; ?>">
                                        <?php echo htmlspecialchars($condition); ?>
                                    </span>
                                </td>
                                <td><?php echo isset($asset['responsible_officer']) ? htmlspecialchars($asset['responsible_officer']) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: $renderEmptyPanelNotice('ICT Assets'); ?>
                <?php endif; ?>
                </div>

                <div class="asset-panel<?php echo $defaultTab === 'projects' ? ' active' : ''; ?>" data-panel="projects">
                <?php if (!empty($data['projects'])): ?>
                <h3>Ongoing Projects</h3>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Project Code</th>
                                <th>Title</th>
                                <th>Contractor</th>
                                <th>Contract Sum</th>
                                <th>Physical %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['projects'] as $project): ?>
                            <?php
                            $status = $project['status'] ?? 'Unknown';
                            $statusClass = $status == 'Completed' ? 'badge-success' : ($status == 'In Progress' ? 'badge-info' : 'badge-warning');
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($project['project_code']) ? htmlspecialchars($project['project_code']) : '-'; ?></span></td>
                                <td><?php echo isset($project['project_title']) ? htmlspecialchars($project['project_title']) : '-'; ?></td>
                                <td><?php echo isset($project['contractor']) ? htmlspecialchars($project['contractor']) : '-'; ?></td>
                                <td class="text-right"><?php echo isset($project['contract_sum']) ? '₦' . number_format($project['contract_sum'], 2) : '-'; ?></td>
                                <td class="text-center"><?php echo isset($project['physical_progress']) ? number_format($project['physical_progress'], 1) . '%' : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: $renderEmptyPanelNotice('Ongoing Projects'); ?>
                <?php endif; ?>
                </div>
            </div>

        <?php elseif ($type == 'land'): ?>
            <!-- Detailed Land Assets Report -->
            <div class="report-section">
                <h2>Land Assets Detailed Report</h2>
                <p class="report-period">Period: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?></p>
                
                <?php if (!empty($data)): ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Ownership</th>
                                <th>Title Holder</th>
                                <th>Address</th>
                                <th>State/LGA</th>
                                <th>Size</th>
                                <th>Survey Plan</th>
                                <th>C of O</th>
                                <th>Purpose</th>
                                <th>Date Acquired</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $asset): ?>
                            <?php 
                            $status = $asset['status'] ?? 'Unknown';
                            $statusClass = $status == 'Active' ? 'badge-success' : 'badge-warning';
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo $asset['ownership_type'] ?? '-'; ?></td>
                                <td><?php echo isset($asset['title_holder']) ? htmlspecialchars($asset['title_holder']) : '-'; ?></td>
                                <td><?php echo isset($asset['address']) ? htmlspecialchars($asset['address']) : '-'; ?></td>
                                <td><?php echo (isset($asset['state_name']) ? htmlspecialchars($asset['state_name']) : '') . '/' . (isset($asset['lga_name']) ? htmlspecialchars($asset['lga_name']) : ''); ?></td>
                                <td><?php echo isset($asset['size']) ? number_format($asset['size'], 2) . ' ' . ($asset['size_unit'] ?? '') : '-'; ?></td>
                                <td><?php echo $asset['survey_plan_no'] ?? '-'; ?></td>
                                <td><?php echo $asset['certificate_of_occupancy_no'] ?? '-'; ?></td>
                                <td><?php echo $asset['purpose_use'] ?? '-'; ?></td>
                                <td><?php echo isset($asset['date_acquired']) ? date('d/m/Y', strtotime($asset['date_acquired'])) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-map-marked-alt"></i>
                    <p>No land assets found for the selected period.</p>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'buildings'): ?>
            <!-- Detailed Building Assets Report -->
            <div class="report-section">
                <h2>Building Assets Detailed Report</h2>
                <p class="report-period">Period: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?></p>
                
                <?php if (!empty($data)): ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Building Name</th>
                                <th>Type</th>
                                <th>Address</th>
                                <th>Location</th>
                                <th>Floors</th>
                                <th>Area</th>
                                <th>Contractor</th>
                                <th>Contract Sum</th>
                                <th>Completion Date</th>
                                <th>Condition</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $asset): ?>
                            <?php 
                            $condition = $asset['condition_status'] ?? 'Unknown';
                            $conditionClass = $condition == 'Good' ? 'badge-success' : 'badge-warning';
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($asset['building_name']) ? htmlspecialchars($asset['building_name']) : '-'; ?></td>
                                <td><?php echo $asset['building_type'] ?? '-'; ?></td>
                                <td><?php echo isset($asset['address']) ? htmlspecialchars($asset['address']) : '-'; ?></td>
                                <td><?php echo (isset($asset['state_name']) ? htmlspecialchars($asset['state_name']) : '') . '/' . (isset($asset['lga_name']) ? htmlspecialchars($asset['lga_name']) : ''); ?></td>
                                <td class="text-center"><?php echo $asset['floor_count'] ?? '-'; ?></td>
                                <td><?php echo isset($asset['total_area']) ? number_format($asset['total_area'], 2) . ' m²' : '-'; ?></td>
                                <td><?php echo $asset['construction_contractor'] ?? '-'; ?></td>
                                <td class="text-right"><?php echo isset($asset['contract_sum']) ? '₦' . number_format($asset['contract_sum'], 2) : '-'; ?></td>
                                <td><?php echo isset($asset['completion_date']) ? date('d/m/Y', strtotime($asset['completion_date'])) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $conditionClass; ?>">
                                        <?php echo htmlspecialchars($condition); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <p>No building assets found for the selected period.</p>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'rented'): ?>
            <!-- Detailed Rented Properties Report -->
            <div class="report-section">
                <h2>Rented Properties Detailed Report</h2>
                <p class="report-period">Period: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?></p>
                
                <?php if (!empty($data)): ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Address</th>
                                <th>Location</th>
                                <th>Purpose</th>
                                <th>Annual Rent</th>
                                <th>Landlord</th>
                                <th>Landlord Phone</th>
                                <th>Lease Start</th>
                                <th>Lease Expiry</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $asset): ?>
                            <?php
                            $status = $asset['status'] ?? 'Unknown';
                            $statusClass = $status == 'Active' ? 'badge-success' : 'badge-warning';
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($asset['property_address']) ? htmlspecialchars($asset['property_address']) : '-'; ?></td>
                                <td><?php echo (isset($asset['state_name']) ? htmlspecialchars($asset['state_name']) : '') . '/' . (isset($asset['lga_name']) ? htmlspecialchars($asset['lga_name']) : ''); ?></td>
                                <td><?php echo isset($asset['purpose']) ? htmlspecialchars($asset['purpose']) : '-'; ?></td>
                                <td class="text-right"><?php echo isset($asset['annual_rent']) ? '₦' . number_format($asset['annual_rent'], 2) : '-'; ?></td>
                                <td><?php echo isset($asset['owner_lessor_name']) ? htmlspecialchars($asset['owner_lessor_name']) : '-'; ?></td>
                                <td><?php echo $asset['owner_phone'] ?? '-'; ?></td>
                                <td><?php echo isset($asset['start_date']) ? date('d/m/Y', strtotime($asset['start_date'])) : '-'; ?></td>
                                <td><?php echo isset($asset['expiry_date']) ? date('d/m/Y', strtotime($asset['expiry_date'])) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-home"></i>
                    <p>No rented properties found for the selected period.</p>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'movable'): ?>
            <!-- Detailed Movable Assets Report -->
            <div class="report-section">
                <h2>Movable Assets Detailed Report</h2>
                <p class="report-period">Period: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?></p>
                
                <?php if (!empty($data)): ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Asset Type</th>
                                <th>Make/Model</th>
                                <th>Specification</th>
                                <th>Serial No.</th>
                                <th>Condition</th>
                                <th>Location</th>
                                <th>Custodian</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $asset): ?>
                            <?php
                            $condition = $asset['condition_status'] ?? 'Unknown';
                            $conditionClass = $condition == 'Good' ? 'badge-success' : 'badge-warning';
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($asset['asset_type']) ? htmlspecialchars($asset['asset_type']) : '-'; ?></td>
                                <td><?php echo isset($asset['make_model']) ? htmlspecialchars($asset['make_model']) : '-'; ?></td>
                                <td><?php echo isset($asset['capacity_specification']) ? htmlspecialchars($asset['capacity_specification']) : '-'; ?></td>
                                <td><code><?php echo $asset['serial_number'] ?? '-'; ?></code></td>
                                <td>
                                    <span class="badge <?php echo $conditionClass; ?>">
                                        <?php echo htmlspecialchars($condition); ?>
                                    </span>
                                </td>
                                <td><?php echo isset($asset['current_location']) ? htmlspecialchars($asset['current_location']) : '-'; ?></td>
                                <td><?php echo isset($asset['custodian_name']) ? htmlspecialchars($asset['custodian_name']) . (isset($asset['custodian_rank']) && $asset['custodian_rank'] !== '' ? ' (' . htmlspecialchars($asset['custodian_rank']) . ')' : '') : '-'; ?></td>
                                <td class="text-right"><?php echo isset($asset['current_value']) ? '₦' . number_format($asset['current_value'], 2) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-truck"></i>
                    <p>No movable assets found for the selected period.</p>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'ict'): ?>
            <!-- Detailed ICT Assets Report -->
            <div class="report-section">
                <h2>ICT Assets Detailed Report</h2>
                <p class="report-period">Period: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?></p>
                
                <?php if (!empty($data)): ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Manufacturer</th>
                                <th>Model/Version</th>
                                <th>Serial No.</th>
                                <th>Ownership</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $asset): ?>
                            <?php
                            $condition = $asset['current_status'] ?? 'Unknown';
                            $conditionClass = $condition == 'Active' ? 'badge-success' : 'badge-warning';
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($asset['asset_code']) ? htmlspecialchars($asset['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($asset['asset_description']) ? htmlspecialchars($asset['asset_description']) : '-'; ?></td>
                                <td><?php echo $asset['asset_category'] ?? '-'; ?></td>
                                <td><?php echo $asset['manufacturer'] ?? '-'; ?></td>
                                <td><?php echo $asset['model_version'] ?? '-'; ?></td>
                                <td><code><?php echo $asset['serial_number'] ?? '-'; ?></code></td>
                                <td><?php echo $asset['ownership_type'] ?? '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $conditionClass; ?>">
                                        <?php echo htmlspecialchars($condition); ?>
                                    </span>
                                </td>
                                <td><?php echo isset($asset['responsible_officer']) ? htmlspecialchars($asset['responsible_officer']) : '-'; ?></td>
                                <td class="text-right"><?php echo isset($asset['current_value']) ? '₦' . number_format($asset['current_value'], 2) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-microchip"></i>
                    <p>No ICT assets found for the selected period.</p>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'projects'): ?>
            <!-- Ongoing Projects Report -->
            <div class="report-section">
                <h2>Ongoing Projects Report</h2>
                <p class="report-period">Period: <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?></p>
                
                <?php if (!empty($data)): ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Project Code</th>
                                <th>Project Title</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Contractor</th>
                                <th>Contract Sum</th>
                                <th>Awarded</th>
                                <th>Expected Completion</th>
                                <th>Physical / Financial %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $project): ?>
                            <?php
                            $status = $project['status'] ?? 'Unknown';
                            $statusClass = $status == 'Completed' ? 'badge-success' : ($status == 'In Progress' ? 'badge-info' : 'badge-warning');
                            $physical = (float) ($project['physical_progress'] ?? 0);
                            $financial = (float) ($project['financial_progress'] ?? 0);
                            ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($project['project_code']) ? htmlspecialchars($project['project_code']) : '-'; ?></span></td>
                                <td><?php echo isset($project['project_title']) ? htmlspecialchars($project['project_title']) : '-'; ?></td>
                                <td><?php echo isset($project['project_type']) ? htmlspecialchars($project['project_type']) : '-'; ?></td>
                                <td><?php echo (isset($project['state_name']) ? htmlspecialchars($project['state_name']) : '') . '/' . (isset($project['lga_name']) ? htmlspecialchars($project['lga_name']) : ''); ?></td>
                                <td><?php echo isset($project['contractor']) ? htmlspecialchars($project['contractor']) : '-'; ?></td>
                                <td class="text-right"><?php echo isset($project['contract_sum']) ? '₦' . number_format($project['contract_sum'], 2) : '-'; ?></td>
                                <td><?php echo isset($project['date_awarded']) ? date('d/m/Y', strtotime($project['date_awarded'])) : '-'; ?></td>
                                <td><?php echo isset($project['expected_completion_date']) ? date('d/m/Y', strtotime($project['expected_completion_date'])) : '-'; ?></td>
                                <td class="text-center">
                                    <div class="progress-container">
                                        <div class="progress-bar" style="width: <?php echo $physical; ?>%;"></div>
                                        <span><?php echo number_format($physical, 1); ?>% / <?php echo number_format($financial, 1); ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?<?php echo $pageQueryBase; ?>page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-project-diagram"></i>
                    <p>No ongoing projects found for the selected period.</p>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
:root {
    --primary: #207027;
    --primary-dark: #134617;
    --danger: #B42318;
    --warning: #C69214;
    --info: #1F6F8B;
    --text-dark: #212529;
    --text-light: #53665E;
    --border: #D7E3DC;
    --bg-light: #F7FAF8;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.05);
    --shadow-lg: 0 10px 15px rgba(0,0,0,0.05);
}
[data-theme="dark"] {
    --primary: #37bf43;
    --primary-dark: #299631;
    --danger: #e7564b;
    --warning: #eec052;
    --info: #3cacd4;
    --text-dark: #d8e9d9;
    --text-light: #dfe2e1;
    --border: #2f3832;
    --bg-light: #1a231d;
}


.report-controls {
    background: var(--surface);
    border-radius: 8px;
    padding: 20px 25px;
    margin-bottom: 25px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border);
}

.controls-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.control-group {
    display: flex;
    flex-direction: column;
}

.control-group label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-light);
    margin-bottom: 5px;
    font-weight: 600;
}

.control-group select,
.control-group input {
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.2s;
    background: var(--surface);
}

.control-group select:focus,
.control-group input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(32, 112, 39, 0.1);
}

.control-group-btn {
    justify-content: flex-end;
}

.control-group-btn .btn-primary {
    background: var(--primary);
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.control-group-btn .btn-primary:hover {
    background: var(--primary-dark);
}

.report-content {
    background: var(--surface);
    border-radius: 8px;
    padding: 30px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border);
}

.report-section h2 {
    margin: 0 0 8px 0;
    color: var(--text-dark);
    font-size: 1.5rem;
    font-weight: 500;
    letter-spacing: -0.5px;
}

.report-period {
    color: var(--text-light);
    margin-bottom: 25px;
    font-size: 0.85rem;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border);
}

.report-section h3 {
    margin: 30px 0 15px 0;
    color: var(--text-dark);
    font-size: 1.2rem;
    font-weight: 500;
    border-bottom: 2px solid var(--border);
    padding-bottom: 8px;
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

/* Visual Analytics (summary type only) */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.chart-card {
    background: var(--bg-light);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 16px;
}

.chart-card h3 {
    margin: 0 0 12px 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.chart-card h3 i {
    color: var(--primary);
    font-size: 0.85rem;
}

.chart-wrap {
    position: relative;
    height: 240px;
}

.chart-empty {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
    font-size: 0.85rem;
}

@media (max-width: 992px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
}

.summary-card {
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: var(--shadow-lg);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}

.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.summary-card .card-title {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
}

.summary-card .card-value {
    font-size: 2rem;
    font-weight: 400;
    line-height: 1.2;
    color: white;
}

/* Compact clickable cards that double as tabs for the summary report — see
   .asset-panel below. Smaller than the plain .summary-card used elsewhere
   (reduced padding/value size) since there are six of them in a row here,
   and each one is now also a navigation control, not just a stat. */
.summary-total-line {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.summary-total-line i {
    color: var(--primary);
}
.summary-tab-card {
    cursor: pointer;
    font: inherit;
    border: 2px solid transparent;
    padding: 12px 10px;
    opacity: 0.82;
}
.summary-tab-card .card-title {
    font-size: 0.72rem;
}
.summary-tab-card .card-value {
    font-size: 1.4rem;
}
.summary-tab-card:hover {
    opacity: 1;
}
.summary-tab-card.active {
    opacity: 1;
    border-color: rgba(255, 255, 255, 0.85);
    box-shadow: 0 0 0 3px rgba(19, 70, 23, 0.15), var(--shadow-lg);
}

/* Only the selected category's table shows at a time — six full tables used
   to render stacked on one page, forcing a long scroll to reach "Ongoing
   Projects" at the bottom. */
.asset-panel {
    display: none;
}
.asset-panel.active {
    display: block;
}
@media print {
    /* A manual browser print (Ctrl+P) should still include every category,
       not just whichever tab happened to be open — PDF export is a separate
       server-rendered request and is unaffected either way. */
    .asset-panel {
        display: block !important;
    }
    .summary-tab-card {
        display: none !important;
    }
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
    margin-top: 15px;
    border-radius: 6px;
    border: 1px solid var(--border);
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.report-table th {
    background: var(--bg-light);
    padding: 12px 8px;
    text-align: left;
    font-weight: 600;
    color: var(--text-light);
    border-bottom: 2px solid var(--border);
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
}

.report-table td {
    padding: 10px 8px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.report-table tbody tr:hover {
    background: var(--bg-light);
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-success {
    background: #E8F5E9;
    color: #2E7D32;
}

.badge-warning {
    background: #FFF3E0;
    color: #B26A00;
}

.badge-info {
    background: #E3F2FD;
    color: #0D47A1;
}

/* Asset Code */
.asset-code {
    font-family: 'SF Mono', 'Fira Code', monospace;
    background: var(--bg-light);
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: var(--info);
}

code {
    font-family: 'SF Mono', 'Fira Code', monospace;
    background: var(--bg-light);
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.8rem;
}

/* Progress Bar */
.progress-container {
    width: 100px;
    height: 6px;
    background: var(--bg-light);
    border-radius: 3px;
    position: relative;
    display: inline-block;
}

.progress-bar {
    height: 100%;
    background: var(--primary);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-container span {
    margin-left: 8px;
    font-size: 0.75rem;
    color: var(--text-light);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-light);
    background: var(--bg-light);
    border-radius: 8px;
    margin: 20px 0;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #ccc;
}

.empty-state p {
    font-size: 1rem;
    margin-bottom: 20px;
}

/* Text utilities */
.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

/* Responsive */
@media (max-width: 768px) {
    .report-content {
        padding: 20px;
    }
    
    .controls-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
}

/* Print styles */
@media print {
    .header-actions,
    .sidebar,
    footer,
    .report-controls,
    .btn-group {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 20px !important;
    }
    
    .report-content {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }
    
    .report-table th {
        background: var(--bg-light) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<script>
// Summary report tabs: each .summary-tab-card click shows only the matching
// .asset-panel (Land / Buildings / Rented / Movable / ICT / Projects),
// instead of all six full tables rendering stacked on one long page.
function showAssetPanel(key, btnEl) {
    document.querySelectorAll('.asset-panel').forEach(function (panel) {
        panel.classList.toggle('active', panel.dataset.panel === key);
    });
    document.querySelectorAll('.summary-tab-card').forEach(function (card) {
        const isActive = card === btnEl;
        card.classList.toggle('active', isActive);
        card.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
}

function exportReport(format) {
    const type = document.getElementById('reportType').value;
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    const searchEl = document.getElementById('searchInput');
    const statusEl = document.querySelector('select[name="status"]');

    const params = new URLSearchParams({ type, start_date: startDate, end_date: endDate, format });
    if (searchEl && searchEl.value) params.set('search', searchEl.value);
    if (statusEl && statusEl.value) params.set('status', statusEl.value);

    window.location.href = '<?php echo BASE_URL; ?>/reports/assets?' + params.toString();
}

function saveReport() {
    const reportName = prompt('Enter a name for this report:');
    if (reportName) {
        const type = document.getElementById('reportType').value;
        const startDate = document.querySelector('input[name="start_date"]').value;
        const endDate = document.querySelector('input[name="end_date"]').value;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/reports/save';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = '<?php echo Security::csrfToken(); ?>';

        form.appendChild(csrf);

        const nameInput = document.createElement('input');
        nameInput.type = 'hidden';
        nameInput.name = 'report_name';
        nameInput.value = reportName;
        form.appendChild(nameInput);

        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'report_type';
        typeInput.value = 'assets';
        form.appendChild(typeInput);

        const paramsInput = document.createElement('input');
        paramsInput.type = 'hidden';
        paramsInput.name = 'parameters';
        paramsInput.value = JSON.stringify({type, start_date: startDate, end_date: endDate});
        form.appendChild(paramsInput);

        document.body.appendChild(form);
        form.submit();
     }
 }

(function() {
    if (typeof Chart === 'undefined') return;
    if (document.getElementById('assetsTypeChart') === null) return; // only present on the 'summary' type

    const PALETTE = ['#207027', '#1F6F8B', '#C69214', '#B42318', '#764ba2', '#f5576c', '#3498db', '#37474F'];

    const typeCounts = <?php echo json_encode([
        'Land' => $landCount ?? 0,
        'Buildings' => $buildingCount ?? 0,
        'Rented' => $rentedCount ?? 0,
        'Movable' => $movableCount ?? 0,
        'ICT' => $ictCount ?? 0,
        'Projects' => $projectCount ?? 0,
    ], JSON_NUMERIC_CHECK); ?>;
    const zoneBreakdown = <?php echo json_encode($zoneBreakdown); ?>;
    const valueByCategory = <?php echo json_encode($valueByCategory, JSON_NUMERIC_CHECK); ?>;

    function showEmpty(canvasId) {
        const canvas = document.getElementById(canvasId);
        const empty = document.getElementById(canvasId + 'Empty');
        if (canvas) canvas.style.display = 'none';
        if (empty) empty.style.display = 'flex';
    }

    function formatNaira(value) {
        return '₦' + Number(value || 0).toLocaleString('en-NG', { maximumFractionDigits: 0 });
    }

    // --- Assets by Type (doughnut) ---
    (function() {
        const labels = Object.keys(typeCounts);
        const values = Object.values(typeCounts);
        if (values.every(v => !v)) return showEmpty('assetsTypeChart');

        new Chart(document.getElementById('assetsTypeChart').getContext('2d'), {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: PALETTE, borderWidth: 2, borderColor: '#ffffff' }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 10, usePointStyle: true, boxWidth: 8, font: { size: 10 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total ? Math.round((ctx.raw * 100) / total) : 0;
                                return `${ctx.label}: ${ctx.raw.toLocaleString()} (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: '58%'
            }
        });
    })();

    // --- Assets by Zone (stacked bar, all-time) ---
    (function() {
        const zones = zoneBreakdown || [];
        if (!zones.length) return showEmpty('assetsZoneChart');
        const categories = [
            { key: 'land_count', label: 'Land' },
            { key: 'building_count', label: 'Buildings' },
            { key: 'rented_count', label: 'Rented' },
            { key: 'movable_count', label: 'Movable' },
            { key: 'ict_count', label: 'ICT' }
        ];
        const hasAny = zones.some(z => categories.some(c => Number(z[c.key] || 0) > 0));
        if (!hasAny) return showEmpty('assetsZoneChart');

        new Chart(document.getElementById('assetsZoneChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: zones.map(z => z.zone_name || 'Unknown'),
                datasets: categories.map((c, i) => ({
                    label: c.label,
                    data: zones.map(z => Number(z[c.key] || 0)),
                    backgroundColor: PALETTE[i % PALETTE.length]
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { stacked: true, ticks: { font: { size: 9 } } }, y: { stacked: true, beginAtZero: true } },
                plugins: { legend: { position: 'bottom', labels: { padding: 8, usePointStyle: true, boxWidth: 8, font: { size: 10 } } } }
            }
        });
    })();

    // --- Value by Category (doughnut) ---
    (function() {
        const labels = Object.keys(valueByCategory || {});
        const values = Object.values(valueByCategory || {});
        if (!labels.length || values.every(v => !v)) return showEmpty('assetsValueChart');

        new Chart(document.getElementById('assetsValueChart').getContext('2d'), {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: PALETTE.slice().reverse(), borderWidth: 2, borderColor: '#ffffff' }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 10, usePointStyle: true, boxWidth: 8, font: { size: 10 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total ? Math.round((ctx.raw * 100) / total) : 0;
                                return `${ctx.label}: ${formatNaira(ctx.raw)} (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: '58%'
            }
        });
    })();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>