<?php
$title = 'Executive Summary';
$active = 'reports';
$init_charts = true;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Ensure $data is always defined with default values
$data = $data ?? [
    'total_assets' => 0,
    'total_value' => 0,
    'assets_by_type' => [],
    'assets_by_zone' => [],
    'recent_additions' => [],
    'expiring_items' => [
        'ammunition' => [],
        'insurance' => []
    ]
];

// Ensure assets_by_type is an array
if (!is_array($data['assets_by_type'])) {
    $data['assets_by_type'] = [];
}

// Ensure assets_by_zone is an array
if (!is_array($data['assets_by_zone'])) {
    $data['assets_by_zone'] = [];
}

// Ensure recent_additions is an array
if (!is_array($data['recent_additions'])) {
    $data['recent_additions'] = [];
}

// Ensure expiring_items has required keys
if (!is_array($data['expiring_items'])) {
    $data['expiring_items'] = ['ammunition' => [], 'insurance' => []];
} else {
    if (!isset($data['expiring_items']['ammunition']) || !is_array($data['expiring_items']['ammunition'])) {
        $data['expiring_items']['ammunition'] = [];
    }
    if (!isset($data['expiring_items']['insurance']) || !is_array($data['expiring_items']['insurance'])) {
        $data['expiring_items']['insurance'] = [];
    }
}

// Ensure value_by_category is an array
if (!isset($data['value_by_category']) || !is_array($data['value_by_category'])) {
    $data['value_by_category'] = [];
}

// Ensure growth_trend has the shape the chart expects
if (!isset($data['growth_trend']) || !is_array($data['growth_trend']) || !isset($data['growth_trend']['labels'])) {
    $data['growth_trend'] = ['labels' => [], 'series' => []];
}

// Calculate total assets if not provided
if ($data['total_assets'] === 0 && !empty($data['assets_by_type'])) {
    $data['total_assets'] = array_sum($data['assets_by_type']);
}

// Zones that actually hold at least one asset (for the "Zones Covered" KPI)
$zonesCovered = 0;
foreach ($data['assets_by_zone'] as $zone) {
    $zoneTotal = (int) ($zone['land_count'] ?? 0) + (int) ($zone['building_count'] ?? 0)
        + (int) ($zone['rented_count'] ?? 0) + (int) ($zone['movable_count'] ?? 0)
        + (int) ($zone['ict_count'] ?? 0) + (int) ($zone['vehicle_count'] ?? 0);
    if ($zoneTotal > 0) $zonesCovered++;
}
$categoriesTracked = count(array_filter($data['assets_by_type'], function ($c) { return (int) $c > 0; }));

// Check if we have any data to display
$hasData = $data['total_assets'] > 0 || !empty($data['assets_by_type']) || !empty($data['assets_by_zone']);
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-chart-pie"></i>
                Executive Summary
            </h1>
            <p>High-level overview of all assets and key statistics</p>
        </div>
        <div class="header-actions">
            <div class="btn-group">
                <button class="btn btn-outline" onclick="exportReport('pdf')" <?php echo !$hasData ? 'disabled' : ''; ?>>
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button class="btn btn-outline" onclick="window.print()" <?php echo !$hasData ? 'disabled' : ''; ?>>
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>

    <?php if (!$hasData): ?>
        <div class="empty-state">
            <i class="fas fa-chart-pie"></i>
            <p>No summary data available</p>
        </div>
    <?php else: ?>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card total">
            <div class="card-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="card-content">
                <div class="card-label">Total Assets</div>
                <div class="card-value"><?php echo number_format($data['total_assets']); ?></div>
            </div>
        </div>
        <div class="summary-card value">
            <div class="card-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div class="card-content">
                <div class="card-label">Total Value</div>
                <div class="card-value">₦<?php echo number_format($data['total_value'], 2); ?></div>
            </div>
        </div>
        <div class="summary-card categories">
            <div class="card-icon">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="card-content">
                <div class="card-label">Categories Tracked</div>
                <div class="card-value"><?php echo number_format($categoriesTracked); ?></div>
            </div>
        </div>
        <div class="summary-card zones">
            <div class="card-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="card-content">
                <div class="card-label">Zones Covered</div>
                <div class="card-value"><?php echo number_format($zonesCovered); ?> / <?php echo number_format(count($data['assets_by_zone'])); ?></div>
            </div>
        </div>
    </div>

    <!-- Visual Analytics -->
    <div class="summary-section charts-section">
        <h2><i class="fas fa-chart-line"></i> Visual Analytics</h2>
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Assets by Type</h3>
                <div class="chart-wrap">
                    <canvas id="summaryTypeChart"></canvas>
                    <div class="chart-empty" id="summaryTypeChartEmpty" style="display:none;">No data available</div>
                </div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Assets by Zone</h3>
                <div class="chart-wrap">
                    <canvas id="summaryZoneChart"></canvas>
                    <div class="chart-empty" id="summaryZoneChartEmpty" style="display:none;">No data available</div>
                </div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-coins"></i> Asset Value by Category</h3>
                <div class="chart-wrap">
                    <canvas id="summaryValueChart"></canvas>
                    <div class="chart-empty" id="summaryValueChartEmpty" style="display:none;">No data available</div>
                </div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-arrow-trend-up"></i> Monthly Intake Trend (6 Months)</h3>
                <div class="chart-wrap">
                    <canvas id="summaryGrowthChart"></canvas>
                    <div class="chart-empty" id="summaryGrowthChartEmpty" style="display:none;">No data available</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets by Type -->
    <?php if (!empty($data['assets_by_type'])): ?>
    <div class="summary-section">
        <h2><i class="fas fa-list-ul"></i> Assets by Type - Detail</h2>
        <div class="stats-grid">
            <?php foreach ($data['assets_by_type'] as $type => $count): ?>
            <div class="stat-item">
                <div class="stat-label"><?php echo htmlspecialchars($type); ?></div>
                <div class="stat-value"><?php echo number_format((int)$count); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Assets by Zone -->
    <?php if (!empty($data['assets_by_zone'])): ?>
    <div class="summary-section">
        <h2><i class="fas fa-table"></i> Assets by Zone - Detail</h2>
        <div class="table-responsive">
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Zone</th>
                        <th>Land</th>
                        <th>Buildings</th>
                        <th>Rented</th>
                        <th>Movable</th>
                        <th>ICT</th>
                        <th>Vehicles</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['assets_by_zone'] as $zone): ?>
                    <?php 
                    $landCount = (int)($zone['land_count'] ?? 0);
                    $buildingCount = (int)($zone['building_count'] ?? 0);
                    $rentedCount = (int)($zone['rented_count'] ?? 0);
                    $movableCount = (int)($zone['movable_count'] ?? 0);
                    $ictCount = (int)($zone['ict_count'] ?? 0);
                    $vehicleCount = (int)($zone['vehicle_count'] ?? 0);
                    $total = $landCount + $buildingCount + $rentedCount + $movableCount + $ictCount + $vehicleCount;
                    ?>
                    <tr>
                        <td><strong><?php echo isset($zone['zone_name']) ? htmlspecialchars($zone['zone_name']) : 'Unknown'; ?></strong></td>
                        <td class="text-center"><?php echo $landCount; ?></td>
                        <td class="text-center"><?php echo $buildingCount; ?></td>
                        <td class="text-center"><?php echo $rentedCount; ?></td>
                        <td class="text-center"><?php echo $movableCount; ?></td>
                        <td class="text-center"><?php echo $ictCount; ?></td>
                        <td class="text-center"><?php echo $vehicleCount; ?></td>
                        <td class="text-center total"><?php echo $total; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Additions -->
    <?php if (!empty($data['recent_additions'])): ?>
    <div class="summary-section">
        <h2><i class="fas fa-history"></i> Recent Additions</h2>
        <div class="recent-list">
            <?php foreach ($data['recent_additions'] as $item): ?>
            <?php 
            $type = $item['type'] ?? 'Asset';
            $code = $item['code'] ?? $item['asset_code'] ?? $item['weapon_id'] ?? 'N/A';
            $createdAt = $item['created_at'] ?? date('Y-m-d H:i:s');
            
            // Determine icon based on type
            $icon = 'box';
            $typeLower = strtolower($type);
            if (strpos($typeLower, 'land') !== false) $icon = 'map-marked-alt';
            elseif (strpos($typeLower, 'building') !== false) $icon = 'building';
            elseif (strpos($typeLower, 'vehicle') !== false || strpos($typeLower, 'car') !== false) $icon = 'car';
            elseif (strpos($typeLower, 'weapon') !== false || strpos($typeLower, 'gun') !== false) $icon = 'gun';
            elseif (strpos($typeLower, 'ict') !== false) $icon = 'microchip';
            elseif (strpos($typeLower, 'movable') !== false) $icon = 'truck';
            elseif (strpos($typeLower, 'rented') !== false) $icon = 'home';
            ?>
            <div class="recent-item">
                <div class="recent-icon">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                </div>
                <div class="recent-details">
                    <div class="recent-title"><?php echo htmlspecialchars($type); ?> - <?php echo htmlspecialchars($code); ?></div>
                    <div class="recent-date"><?php echo date('d/m/Y H:i', strtotime($createdAt)); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alerts -->
    <?php if (!empty($data['expiring_items']['ammunition']) || !empty($data['expiring_items']['insurance'])): ?>
    <div class="summary-section">
        <h2><i class="fas fa-exclamation-triangle"></i> Alerts & Notifications</h2>
        <div class="alerts-grid">
            <?php if (!empty($data['expiring_items']['ammunition'])): ?>
            <div class="alert-card warning">
                <div class="alert-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="alert-content">
                    <h4>Expiring Ammunition</h4>
                    <p><?php echo count($data['expiring_items']['ammunition']); ?> ammunition items expiring within 60 days</p>
                    <?php if (!empty($data['expiring_items']['ammunition'][0]['expiry_date'])): ?>
                    <small>Earliest expiry: <?php echo date('d/m/Y', strtotime($data['expiring_items']['ammunition'][0]['expiry_date'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['expiring_items']['insurance'])): ?>
            <div class="alert-card warning">
                <div class="alert-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="alert-content">
                    <h4>Insurance Expiring</h4>
                    <p><?php echo count($data['expiring_items']['insurance']); ?> vehicles with insurance expiring within 30 days</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
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


.report-content {
    background: var(--surface);
    border-radius: 8px;
    padding: 30px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border);
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    border-radius: 8px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
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

/* On-brand green family (was purple/pink/blue/gold) — each card still gets
   its own shade to stay visually distinguishable, but all read as "the
   app's green" instead of an unrelated rainbow. .total reuses the exact
   gradient already used for the header's profile button/notification
   panel, so it ties directly back to the rest of the UI. */
.summary-card.total {
    background: linear-gradient(135deg, #134617 0%, #207027 100%);
    color: white;
}

.summary-card.value {
    background: linear-gradient(135deg, #18561d 0%, #2a8a35 100%);
    color: white;
}

.summary-card.categories {
    background: linear-gradient(135deg, #207027 0%, #3bb54a 100%);
    color: white;
}

.summary-card.zones {
    background: linear-gradient(135deg, #0d5c3f 0%, #17a679 100%);
    color: white;
}

.card-icon {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.card-content {
    flex: 1;
}

.card-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 5px;
    color: white;
}

.card-value {
    font-size: 2rem;
    font-weight: 400;
    line-height: 1.2;
    color: white;
}

/* Summary Sections */
.summary-section {
    background: var(--surface);
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border);
}

.summary-section h2 {
    margin: 0 0 20px 0;
    font-size: 1.2rem;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.summary-section h2 i {
    color: var(--primary);
}

/* Visual Analytics */
.charts-section {
    padding: 25px 25px 15px;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.chart-card {
    background: var(--bg-light);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 18px;
}

.chart-card h3 {
    margin: 0 0 15px 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.chart-card h3 i {
    color: var(--primary);
    font-size: 0.9rem;
}

.chart-wrap {
    position: relative;
    height: 280px;
}

.chart-empty {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: var(--bg-light);
    border-radius: 6px;
    border: 1px solid var(--border);
}

.stat-label {
    font-size: 0.85rem;
    color: var(--text-light);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-dark);
}

/* Summary Table */
.table-responsive {
    overflow-x: auto;
    margin-top: 15px;
    border-radius: 6px;
    border: 1px solid var(--border);
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.summary-table th {
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

.summary-table td {
    padding: 10px 8px;
    border-bottom: 1px solid var(--border);
}

.summary-table tbody tr:hover {
    background: var(--bg-light);
}

.summary-table td.total {
    font-weight: 600;
    color: var(--primary-dark);
}

.text-center {
    text-align: center;
}

/* Recent List */
.recent-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.recent-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: var(--bg-light);
    border-radius: 6px;
    border: 1px solid var(--border);
    transition: all 0.2s;
}

.recent-item:hover {
    background: var(--surface);
    box-shadow: var(--shadow-sm);
}

.recent-icon {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 1.2rem;
    border: 1px solid var(--border);
}

.recent-details {
    flex: 1;
}

.recent-title {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 3px;
}

.recent-date {
    font-size: 0.75rem;
    color: var(--text-light);
}

/* Alerts Grid */
.alerts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.alert-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid;
    transition: all 0.2s;
}

.alert-card:hover {
    transform: translateX(3px);
    box-shadow: var(--shadow-sm);
}

.alert-card.warning {
    background: #FFF3E0;
    border-left-color: transparent;
}
[data-theme="dark"] .alert-card.warning {
    /* was hardcoded #FFF3E0 in both themes — a pale peach box stayed pale
       in dark mode, and its text (color: var(--warning-dark), a variable
       that was never actually defined anywhere) inherited the page's
       dark-mode light text color, landing pale-on-pale and unreadable. */
    background: rgba(238, 192, 82, 0.16);
}

.alert-icon i {
    font-size: 2rem;
    color: var(--warning-color);
}

.alert-content h4 {
    margin: 0 0 5px 0;
    font-size: 1rem;
    color: var(--warning-color);
    font-weight: 600;
}

.alert-content p {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    color: var(--warning-color);
}

.alert-content small {
    font-size: 0.75rem;
    opacity: 0.8;
    color: var(--warning-color);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-light);
    background: var(--surface);
    border-radius: 8px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #ccc;
}

.empty-state p {
    font-size: 1rem;
}

/* Button group */
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
@media (max-width: 992px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .alerts-grid {
        grid-template-columns: 1fr;
    }

    .btn-group {
        flex-direction: column;
        width: 100%;
    }

    .btn-group .btn {
        width: 100%;
    }

    .chart-wrap {
        height: 240px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .summary-cards {
        grid-template-columns: 1fr;
    }

    .summary-card {
        flex-direction: column;
        text-align: center;
    }
}

/* Print styles */
@media print {
    .header-actions,
    .sidebar,
    footer,
    .btn-group {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 20px !important;
    }
    
    .summary-card {
        break-inside: avoid;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .summary-card.total,
    .summary-card.value,
    .summary-card.categories,
    .summary-card.zones {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .charts-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .chart-card {
        break-inside: avoid;
    }

    .chart-wrap {
        height: 220px;
    }
}
</style>

<script>
function exportReport(format) {
    window.location.href = '<?php echo BASE_URL; ?>/reports/summary?format=' + format;
}

(function() {
    if (typeof Chart === 'undefined') return;

    const PALETTE = ['#207027', '#1F6F8B', '#C69214', '#B42318', '#764ba2', '#f5576c', '#3498db', '#f39c12', '#9b59b6', '#16a085', '#95a5a6'];

    const byType = <?php echo json_encode($data['assets_by_type'], JSON_NUMERIC_CHECK); ?>;
    const byZone = <?php echo json_encode($data['assets_by_zone']); ?>;
    const valueByCategory = <?php echo json_encode($data['value_by_category'], JSON_NUMERIC_CHECK); ?>;
    const growthTrend = <?php echo json_encode($data['growth_trend']); ?>;

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
        const labels = Object.keys(byType || {});
        const values = Object.values(byType || {});
        if (!labels.length || values.every(v => !v)) return showEmpty('summaryTypeChart');

        new Chart(document.getElementById('summaryTypeChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: PALETTE,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, boxWidth: 8, font: { size: 11 } } },
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

    // --- Assets by Zone (stacked bar) ---
    (function() {
        const zones = byZone || [];
        if (!zones.length) return showEmpty('summaryZoneChart');

        const categories = [
            { key: 'land_count', label: 'Land' },
            { key: 'building_count', label: 'Buildings' },
            { key: 'rented_count', label: 'Rented' },
            { key: 'movable_count', label: 'Movable' },
            { key: 'ict_count', label: 'ICT' },
            { key: 'vehicle_count', label: 'Vehicles' }
        ];
        const labels = zones.map(z => z.zone_name || 'Unknown');
        const hasAny = zones.some(z => categories.some(c => Number(z[c.key] || 0) > 0));
        if (!hasAny) return showEmpty('summaryZoneChart');

        new Chart(document.getElementById('summaryZoneChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: categories.map((c, i) => ({
                    label: c.label,
                    data: zones.map(z => Number(z[c.key] || 0)),
                    backgroundColor: PALETTE[i % PALETTE.length]
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, ticks: { font: { size: 10 } } },
                    y: { stacked: true, beginAtZero: true }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 10, usePointStyle: true, boxWidth: 8, font: { size: 11 } } }
                }
            }
        });
    })();

    // --- Asset Value by Category (doughnut) ---
    (function() {
        const labels = Object.keys(valueByCategory || {});
        const values = Object.values(valueByCategory || {});
        if (!labels.length || values.every(v => !v)) return showEmpty('summaryValueChart');

        new Chart(document.getElementById('summaryValueChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: PALETTE.slice().reverse(),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, boxWidth: 8, font: { size: 11 } } },
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

    // --- Monthly Intake Trend (line) ---
    (function() {
        const labels = (growthTrend && growthTrend.labels) || [];
        const series = (growthTrend && growthTrend.series) || {};
        const seriesKeys = Object.keys(series);
        if (!labels.length || !seriesKeys.length || seriesKeys.every(k => (series[k] || []).every(v => !v))) {
            return showEmpty('summaryGrowthChart');
        }

        new Chart(document.getElementById('summaryGrowthChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: seriesKeys.map((label, i) => ({
                    label: label,
                    data: series[label],
                    borderColor: PALETTE[i % PALETTE.length],
                    backgroundColor: PALETTE[i % PALETTE.length] + '22',
                    tension: 0.3,
                    fill: false,
                    pointRadius: 3
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 10, usePointStyle: true, boxWidth: 8, font: { size: 11 } } }
                },
                interaction: { mode: 'index', intersect: false }
            }
        });
    })();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>