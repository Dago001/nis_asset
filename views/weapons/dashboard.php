<?php
$title = 'Weapons Dashboard';
$active = 'weapons';
$init_charts = false;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-chart-pie"></i>
                Weapons Dashboard
            </h1>
            <p>Analytics and statistics for weapons inventory</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/weapons" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Weapons
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
                <i class="fas fa-gun"></i>
            </div>
            <div class="stat-details">
                <h4>Total Weapons</h4>
                <p class="stat-number"><?php echo number_format($stats['total']); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon issued">
                <i class="fas fa-hand-holding"></i>
            </div>
            <div class="stat-details">
                <h4>Issued</h4>
                <p class="stat-number"><?php echo number_format($stats['issued']); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon serviceable">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Serviceable</h4>
                <p class="stat-number"><?php echo number_format($stats['serviceable']); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon unserviceable">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h4>Unserviceable</h4>
                <p class="stat-number"><?php echo number_format($stats['unserviceable']); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon repair">
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-details">
                <h4>In Repair</h4>
                <p class="stat-number"><?php echo number_format($stats['in_repair']); ?></p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="dashboard-details">
        <div class="detail-section">
            <div class="section-header">
                <h3><i class="fas fa-chart-bar"></i> Weapons by Type</h3>
            </div>
            <div class="chart-container-sm">
                <canvas id="weaponTypeChart"></canvas>
            </div>
            <?php if (empty($stats['by_type'])): ?>
                <div class="empty-state-chart">
                    <p>No data available for chart</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail-section">
            <div class="section-header">
                <h3><i class="fas fa-chart-bar"></i> Weapons by Calibre</h3>
            </div>
            <div class="chart-container-sm">
                <canvas id="weaponCalibreChart"></canvas>
            </div>
            <?php if (empty($stats['by_calibre'])): ?>
                <div class="empty-state-chart">
                    <p>No data available for chart</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Issues -->
    <div class="detail-section full-width">
        <div class="section-header">
            <h3><i class="fas fa-history"></i> Recent Issues</h3>
        </div>
        <div class="table-responsive">
            <?php if (empty($recentIssues)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No recent issues</p>
                </div>
            <?php else: ?>
            <table class="asset-table">
                <thead>
                    <tr>
                        <th>Weapon</th>
                        <th>Issue Date</th>
                        <th>Officer</th>
                        <th>Unit</th>
                        <th>Purpose</th>
                        <th>Expected Return</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentIssues as $issue): ?>
                    <tr>
                        <td><?php echo Security::escape($issue['make_model']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($issue['issue_date'])); ?></td>
                        <td><?php echo Security::escape($issue['officer_name']); ?></td>
                        <td><?php echo Security::escape($issue['unit']); ?></td>
                        <td><?php echo Security::escape($issue['purpose']); ?></td>
                        <td><?php echo $issue['expected_return_date'] ? date('d/m/Y', strtotime($issue['expected_return_date'])) : '-'; ?></td>
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
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

.stat-icon.issued {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.serviceable {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.unserviceable {
    background: #ffebee;
    color: #c62828;
}

.stat-icon.repair {
    background: #e0f2f1;
    color: #00695c;
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

.btn-secondary {
    background: var(--text-secondary);
    color: white;
}

.btn-secondary:hover {
    background: #6c757d;
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
function refreshDashboard() {
    location.reload();
}

document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
    }
    
    // Weapons by Type Chart — horizontal bar (was a pie chart)
    const typeCtx = document.getElementById('weaponTypeChart');
    if (typeCtx) {
        new Chart(typeCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($stats['by_type'])); ?>,
                datasets: [{
                    label: 'Number of Weapons',
                    data: <?php echo json_encode(array_values($stats['by_type'])); ?>,
                    backgroundColor: [
                        '#207027',
                        '#1F6F8B',
                        '#B42318',
                        '#C69214',
                        '#556B2F',
                        '#0B7A5A',
                        '#B7791F',
                        '#134617'
                    ]
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
    // Weapons by Calibre Chart
    const calibreCtx = document.getElementById('weaponCalibreChart');
    if (calibreCtx) {
        new Chart(calibreCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($stats['by_calibre'])); ?>,
                datasets: [{
                    label: 'Number of Weapons',
                    data: <?php echo json_encode(array_values($stats['by_calibre'])); ?>,
                    backgroundColor: '#207027'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
