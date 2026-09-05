<?php
$title = 'Ammunition Dashboard';
$active = 'ammunition';
$init_charts = false;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// These variables should come from the controller
$stats = isset($stats) ? $stats : [
    'total_types' => 0,
    'total_rounds' => 0,
    'expiring_soon' => 0,
    'low_stock' => 0,
    'by_calibre' => []
];

$byCalibre = isset($byCalibre) ? $byCalibre : [];
$expiringSoon = isset($expiringSoon) ? $expiringSoon : [];
$lowStock = isset($lowStock) ? $lowStock : [];

// Prepare chart data
$calibreLabels = [];
$typeCounts = [];
$roundCounts = [];

if (!empty($byCalibre)) {
    foreach ($byCalibre as $item) {
        $calibreLabels[] = $item['calibre'] ?? 'Unknown';
        $typeCounts[] = (int)($item['type_count'] ?? 0);
        $roundCounts[] = (int)($item['total_rounds'] ?? 0);
    }
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-chart-pie"></i>
                Ammunition Dashboard
            </h1>
            <p>Analytics and statistics for ammunition inventory</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/ammunition" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Ammunition
            </a>
            <button class="btn btn-info" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-details">
                <h4>Total Types</h4>
                <p class="stat-number"><?php echo number_format($stats['total_types'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rounds">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stat-details">
                <h4>Total Rounds</h4>
                <p class="stat-number"><?php echo number_format($stats['total_rounds'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon expiring">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h4>Expiring Soon</h4>
                <p class="stat-number"><?php echo number_format($stats['expiring_soon'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon low">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h4>Low Stock</h4>
                <p class="stat-number"><?php echo number_format($stats['low_stock'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="dashboard-details">
        <div class="detail-section">
            <div class="section-header">
                <h3><i class="fas fa-chart-pie"></i> Ammunition by Calibre</h3>
            </div>
            <div class="chart-container-sm">
                <canvas id="ammoCalibreChart"></canvas>
            </div>
            <?php if (empty($byCalibre)): ?>
                <div class="empty-state-chart">
                    <p>No data available for chart</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail-section">
            <div class="section-header">
                <h3><i class="fas fa-chart-bar"></i> Stock Levels by Calibre</h3>
            </div>
            <div class="chart-container-sm">
                <canvas id="stockLevelChart"></canvas>
            </div>
            <?php if (empty($byCalibre)): ?>
                <div class="empty-state-chart">
                    <p>No data available for chart</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Expiring Soon List -->
    <div class="detail-section full-width">
        <div class="section-header">
            <h3><i class="fas fa-clock"></i> Expiring Soon (Next 90 Days)</h3>
            <?php if (!empty($expiringSoon)): ?>
                <span class="badge badge-warning"><?php echo count($expiringSoon); ?> items</span>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <?php if (empty($expiringSoon)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-check"></i>
                    <p>No ammunition expiring in the next 90 days</p>
                </div>
            <?php else: ?>
            <table class="asset-table">
                <thead>
                    <tr>
                        <th>Ammo ID</th>
                        <th>Type</th>
                        <th>Calibre</th>
                        <th>Batch Number</th>
                        <th>Expiry Date</th>
                        <th>Days Left</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiringSoon as $ammo): 
                        $daysRemaining = $ammo['days_remaining'] ?? 0;
                        $badgeClass = $daysRemaining <= 30 ? 'critical' : 'warning';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ammo['ammo_id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ammo['ammo_type'] ?? $ammo['ammo_type_other'] ?? 'Other'); ?></td>
                        <td><?php echo htmlspecialchars($ammo['calibre'] ?? $ammo['calibre_other'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($ammo['batch_number'] ?? '-'); ?></td>
                        <td><?php echo !empty($ammo['expiry_date']) ? date('d/m/Y', strtotime($ammo['expiry_date'])) : '-'; ?></td>
                        <td>
                            <span class="days-badge <?php echo $badgeClass; ?>">
                                <?php echo $daysRemaining; ?> days
                            </span>
                        </td>
                        <td><?php echo number_format($ammo['balance'] ?? 0); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/ammunition/show/<?php echo $ammo['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Low Stock List -->
    <div class="detail-section full-width">
        <div class="section-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Alert (Below 100 units)</h3>
            <?php if (!empty($lowStock)): ?>
                <span class="badge badge-danger"><?php echo count($lowStock); ?> items</span>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <?php if (empty($lowStock)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No low stock items</p>
                </div>
            <?php else: ?>
            <table class="asset-table">
                <thead>
                    <tr>
                        <th>Ammo ID</th>
                        <th>Type</th>
                        <th>Calibre</th>
                        <th>Batch Number</th>
                        <th>Current Balance</th>
                        <th>Reorder Level</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStock as $ammo): 
                        $balance = $ammo['balance'] ?? 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ammo['ammo_id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ammo['ammo_type'] ?? $ammo['ammo_type_other'] ?? 'Other'); ?></td>
                        <td><?php echo htmlspecialchars($ammo['calibre'] ?? $ammo['calibre_other'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($ammo['batch_number'] ?? '-'); ?></td>
                        <td class="text-danger font-weight-bold"><?php echo number_format($balance); ?></td>
                        <td>100</td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/ammunition/show/<?php echo $ammo['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="<?php echo BASE_URL; ?>/requisitions/create?type=ammunition&ammo_id=<?php echo $ammo['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-shopping-cart"></i> Reorder
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #134617;
    --primary-light: #207027;
    --secondary-color: #207027;
    --secondary-dark: #134617;
    --success-color: #207027;
    --danger-color: #B42318;
    --warning-color: #C69214;
    --info-color: #1F6F8B;
    --light-bg: #F7FAF8;
    --border-color: #D7E3DC;
    --text-primary: #212529;
    --text-secondary: #53665E;
}
[data-theme="dark"] {
    --primary-color: #299631;
    --primary-light: #37bf43;
    --secondary-color: #37bf43;
    --secondary-dark: #299631;
    --success-color: #37bf43;
    --danger-color: #e7564b;
    --warning-color: #eec052;
    --info-color: #3cacd4;
    --light-bg: #1a231d;
    --border-color: #2f3832;
    --text-primary: #d8e9d9;
    --text-secondary: #dfe2e1;
}


.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.2s, box-shadow 0.2s;

    min-width: 0;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-icon.total {
    background: #e3f2fd;
    color: #1976d2;
}

.stat-icon.rounds {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.expiring {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.low {
    background: #ffebee;
    color: #c62828;
}

.stat-details h4 {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.stat-number {
    font-size: clamp(0.9rem, 1.35vw + 0.35rem, 1.6rem);
    font-weight: 400;
    margin: 0;
    color: var(--text-primary);
    line-height: 1.2;
    overflow-wrap: anywhere;
    word-break: break-word;
    max-width: 100%;
}

.dashboard-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.dashboard-details > div {
    min-width: 0;
    overflow: hidden;
}

.detail-section {
    background: var(--surface);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.chart-container-sm {
    height: 300px;
    position: relative;
    width: 100%;
    box-sizing: border-box;
    padding: 5px;
}

.chart-container-sm canvas {
    max-width: 100% !important;
    width: 100% !important;
    display: block;
}

.detail-section.full-width {
    grid-column: 1 / -1;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.section-header h3 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Badge Styles */
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

/* Days badge */
.days-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.days-badge.warning {
    background: #fff3cd;
    color: #856404;
}

.days-badge.critical {
    background: #f8d7da;
    color: #721c24;
}

/* Button styles */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
}

.btn-info {
    background: var(--info-color);
    color: white;
}

.btn-info:hover {
    background: #155E75;
}

.btn-warning {
    background: var(--warning-color);
    color: white;
}

.btn-warning:hover {
    background: #B7791F;
}

.btn-secondary {
    background: var(--text-secondary);
    color: white;
}

.btn-secondary:hover {
    background: #6c757d;
}

.text-danger {
    color: var(--danger-color);
}

.font-weight-bold {
    font-weight: 700;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state p {
    font-size: 1rem;
    margin: 0;
}

.empty-state-chart {
    text-align: center;
    padding: 20px;
    color: var(--text-secondary);
    font-style: italic;
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
}

.asset-table {
    width: 100%;
    border-collapse: collapse;
}

.asset-table th {
    background: var(--primary-color);
    color: white;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    white-space: nowrap;
}

.asset-table td {
    padding: 12px 15px;
    border-bottom: 1px solid var(--border-color);
}

.asset-table tbody tr:hover {
    background: var(--light-bg);
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-details {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<script>
// Refresh dashboard function
function refreshDashboard() {
    location.reload();
}

document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
    }
    
    // Ammunition by Calibre Chart (Pie Chart)
    const calibreCtx = document.getElementById('ammoCalibreChart');
    if (calibreCtx) {
        const labels = <?php echo json_encode($calibreLabels); ?>;
        const typeData = <?php echo json_encode($typeCounts); ?>;
        
        if (labels.length > 0) {
            new Chart(calibreCtx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: typeData,
                        backgroundColor: [
                            '#207027', '#1F6F8B', '#B42318', '#C69214', 
                            '#556B2F', '#0B7A5A', '#B7791F', '#134617',
                            '#8A9A91', '#0F2F24', '#0B7A5A', '#207027',
                            '#155E75', '#52643B', '#134617', '#8A4B0F'
                        ],
                        borderWidth: 1,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const dataset = context.dataset;
                                    const total = dataset.data.reduce((acc, data) => acc + data, 0);
                                    const percentage = ((value * 100) / total).toFixed(1);
                                    return `${label}: ${value} type${value !== 1 ? 's' : ''} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            calibreCtx.style.display = 'none';
            calibreCtx.parentNode.innerHTML += '<div class="empty-state-chart">No data available for chart</div>';
        }
    }
    
    // Stock Level Chart (Bar Chart)
    const stockCtx = document.getElementById('stockLevelChart');
    if (stockCtx) {
        const labels = <?php echo json_encode($calibreLabels); ?>;
        const roundData = <?php echo json_encode($roundCounts); ?>;
        
        if (labels.length > 0) {
            new Chart(stockCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Rounds',
                        data: roundData,
                        backgroundColor: '#207027',
                        borderRadius: 5,
                        barPercentage: 0.7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw || 0;
                                    return `Total Rounds: ${value.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Rounds',
                                font: {
                                    size: 12
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        } else {
            stockCtx.style.display = 'none';
            stockCtx.parentNode.innerHTML += '<div class="empty-state-chart">No data available for chart</div>';
        }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
