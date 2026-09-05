<?php
$title = 'Weapons Reports';
$active = 'weapon-reports';
$extra_css = [BASE_URL . '/assets/css/reports.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$type = $_GET['type'] ?? 'inventory';

// Ensure $data is always defined and is an array
$data = $data ?? [];

// Check if data is empty and show appropriate message
$hasData = !empty($data);

// Pagination (the table below is one page of the full report — see
// ReportController::weapons()).
$page = isset($page) ? (int) $page : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$totalCount = isset($totalCount) ? (int) $totalCount : count($data);

// Condition breakdown for the summary cards/chart — computed by the
// controller from the FULL data set, not just this page (see $summary).
$summary = isset($summary) ? $summary : null;
$serviceable = $summary['serviceable'] ?? 0;
$unserviceable = $summary['unserviceable'] ?? 0;
$underRepair = $summary['under_repair'] ?? 0;
$totalWeaponsCount = $summary['total'] ?? $totalCount;
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-gun"></i>
                Weapons Reports
            </h1>
            <p>Weapons inventory and status reports</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-success" onclick="saveReport()">
                <i class="fas fa-bookmark"></i> Save Report
            </button>
            <div class="btn-group">
                <button class="btn btn-outline" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-outline" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button class="btn btn-outline" onclick="exportReport('csv')">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button class="btn btn-outline" onclick="exportReport('excel')">
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
        <form method="GET" action="<?php echo BASE_URL; ?>/reports/weapons" id="reportForm">
            <div class="controls-grid">
                <div class="control-group">
                    <label>Report Type</label>
                    <select name="type" id="reportType" onchange="this.form.submit()">
                        <option value="inventory" <?php echo $type == 'inventory' ? 'selected' : ''; ?>>Full Inventory</option>
                        <option value="issued" <?php echo $type == 'issued' ? 'selected' : ''; ?>>Issued Weapons</option>
                        <option value="unserviceable" <?php echo $type == 'unserviceable' ? 'selected' : ''; ?>>Unserviceable Weapons</option>
                        <option value="by_type" <?php echo $type == 'by_type' ? 'selected' : ''; ?>>Weapons by Type</option>
                    </select>
                </div>
                <?php if (!empty($commands) && in_array($type, ['inventory', 'issued', 'unserviceable'], true)): ?>
                <div class="control-group">
                    <label>Command / Formation</label>
                    <select name="command_id" onchange="this.form.submit()">
                        <option value="">All Commands / Formations</option>
                        <?php foreach ($commands as $cmd): ?>
                            <option value="<?php echo $cmd['id']; ?>" <?php echo ((int) ($selectedCommandId ?? 0) === (int) $cmd['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cmd['command_name']); ?><?php echo $cmd['command_type'] ? ' (' . htmlspecialchars($cmd['command_type']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Report Content -->
    <div class="report-content" id="reportContent">
        <?php if (!$hasData): ?>
            <div class="empty-state">
                <i class="fas fa-gun"></i>
                <p>No weapons data found for the selected report type.</p>
            </div>
        <?php elseif ($type == 'inventory'): ?>
            <div class="report-section">
                <h2>Weapons Inventory Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <!-- Summary Cards with Professional Colors -->
                <div class="summary-section">
                    <div class="summary-cards">
                        <div class="summary-card" style="background: linear-gradient(135deg, #134617 0%, #207027 100%);">
                            <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="card-content">
                                <div class="card-value"><?php echo $serviceable; ?></div>
                                <div class="card-label">Serviceable</div>
                            </div>
                        </div>
                        <div class="summary-card" style="background: linear-gradient(135deg, #B71C1C 0%, #D32F2F 100%);">
                            <div class="card-icon"><i class="fas fa-times-circle"></i></div>
                            <div class="card-content">
                                <div class="card-value"><?php echo $unserviceable; ?></div>
                                <div class="card-label">Unserviceable</div>
                            </div>
                        </div>
                        <div class="summary-card" style="background: linear-gradient(135deg, #B26A00 0%, #FF8F00 100%);">
                            <div class="card-icon"><i class="fas fa-tools"></i></div>
                            <div class="card-content">
                                <div class="card-value"><?php echo $underRepair; ?></div>
                                <div class="card-label">Under Repair</div>
                            </div>
                        </div>
                        <div class="summary-card" style="background: linear-gradient(135deg, #18561d 0%, #2a8a35 100%);">
                            <div class="card-icon"><i class="fas fa-gun"></i></div>
                            <div class="card-content">
                                <div class="card-value"><?php echo number_format($totalWeaponsCount); ?></div>
                                <div class="card-label">Total Weapons</div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Section -->
                    <div class="chart-container">
                        <canvas id="weaponsChart" style="width:100%; height:300px;"></canvas>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Weapon ID</th>
                                <th>Type</th>
                                <th>Make/Model</th>
                                <th>Serial Number</th>
                                <th>Calibre</th>
                                <th>Source</th>
                                <th>Location</th>
                                <th>Custodian</th>
                                <th>Condition</th>
                                <th>Date Acquired</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $weapon): ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($weapon['weapon_id']) ? htmlspecialchars($weapon['weapon_id']) : '-'; ?></span></td>
                                <td><?php echo isset($weapon['type_name']) ? htmlspecialchars($weapon['type_name']) : 'Other'; ?></td>
                                <td><?php echo isset($weapon['make_model']) ? htmlspecialchars($weapon['make_model']) : '-'; ?></td>
                                <td><code><?php echo isset($weapon['serial_no']) ? htmlspecialchars($weapon['serial_no']) : '-'; ?></code></td>
                                <td><?php echo isset($weapon['calibre_name']) ? htmlspecialchars($weapon['calibre_name']) : 'N/A'; ?></td>
                                <td><?php echo $weapon['source'] ?? '-'; ?></td>
                                <td><?php echo $weapon['current_location'] ?? '-'; ?></td>
                                <td><?php echo $weapon['custodian'] ?? '-'; ?></td>
                                <td>
                                    <?php 
                                    $condition = $weapon['condition'] ?? 'Unknown';
                                    $conditionClass = '';
                                    if ($condition == 'Serviceable') $conditionClass = 'badge-success';
                                    elseif ($condition == 'Unserviceable') $conditionClass = 'badge-danger';
                                    elseif ($condition == 'Under Repair') $conditionClass = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $conditionClass; ?>">
                                        <?php echo $condition; ?>
                                    </span>
                                </td>
                                <td><?php echo isset($weapon['date_acquired']) ? date('d/m/Y', strtotime($weapon['date_acquired'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?type=<?php echo urlencode($type); ?>&command_id=<?php echo urlencode((string) ($selectedCommandId ?? ((string) ""))); ?>&page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?type=<?php echo urlencode($type); ?>&command_id=<?php echo urlencode((string) ($selectedCommandId ?? ((string) ""))); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($type == 'issued'): ?>
            <div class="report-section">
                <h2>Issued Weapons Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Weapon ID</th>
                                <th>Type</th>
                                <th>Serial Number</th>
                                <th>Issue Date</th>
                                <th>Officer</th>
                                <th>Rank</th>
                                <th>Unit</th>
                                <th>Purpose</th>
                                <th>Expected Return</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $issue): ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($issue['weapon_id']) ? htmlspecialchars($issue['weapon_id']) : '-'; ?></span></td>
                                <td><?php echo isset($issue['type_name']) ? htmlspecialchars($issue['type_name']) : '-'; ?></td>
                                <td><code><?php echo isset($issue['serial_no']) ? htmlspecialchars($issue['serial_no']) : '-'; ?></code></td>
                                <td><?php echo isset($issue['issue_date']) ? date('d/m/Y', strtotime($issue['issue_date'])) : '-'; ?></td>
                                <td><?php echo isset($issue['officer_name']) ? htmlspecialchars($issue['officer_name']) : '-'; ?></td>
                                <td><?php echo isset($issue['officer_rank']) ? htmlspecialchars($issue['officer_rank']) : '-'; ?></td>
                                <td><?php echo isset($issue['unit']) ? htmlspecialchars($issue['unit']) : '-'; ?></td>
                                <td><?php echo $issue['purpose'] ?? '-'; ?></td>
                                <td><?php echo isset($issue['expected_return_date']) ? date('d/m/Y', strtotime($issue['expected_return_date'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?type=<?php echo urlencode($type); ?>&command_id=<?php echo urlencode((string) ($selectedCommandId ?? ((string) ""))); ?>&page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?type=<?php echo urlencode($type); ?>&command_id=<?php echo urlencode((string) ($selectedCommandId ?? ((string) ""))); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($type == 'unserviceable'): ?>
            <div class="report-section">
                <h2>Unserviceable Weapons Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Weapon ID</th>
                                <th>Type</th>
                                <th>Make/Model</th>
                                <th>Serial Number</th>
                                <th>Location</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $weapon): ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($weapon['weapon_id']) ? htmlspecialchars($weapon['weapon_id']) : '-'; ?></span></td>
                                <td><?php echo isset($weapon['type_name']) ? htmlspecialchars($weapon['type_name']) : '-'; ?></td>
                                <td><?php echo isset($weapon['make_model']) ? htmlspecialchars($weapon['make_model']) : '-'; ?></td>
                                <td><code><?php echo isset($weapon['serial_no']) ? htmlspecialchars($weapon['serial_no']) : '-'; ?></code></td>
                                <td><?php echo $weapon['current_location'] ?? '-'; ?></td>
                                <td><?php echo $weapon['remarks'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?type=<?php echo urlencode($type); ?>&command_id=<?php echo urlencode((string) ($selectedCommandId ?? ((string) ""))); ?>&page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?type=<?php echo urlencode($type); ?>&command_id=<?php echo urlencode((string) ($selectedCommandId ?? ((string) ""))); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($type == 'by_type'): ?>
            <div class="report-section">
                <h2>Weapons by Type</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <!-- Chart for weapons by type with professional color palette -->
                <div class="chart-container" style="margin-bottom: 30px;">
                    <canvas id="typeChart" style="width:100%; height:300px;"></canvas>
                </div>
                
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Weapon Type</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = array_sum(array_column($data, 'count'));
                            foreach ($data as $item): 
                                $count = $item['count'] ?? 0;
                                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo isset($item['type']) ? htmlspecialchars($item['type']) : '-'; ?></td>
                                <td class="text-center"><strong><?php echo $count; ?></strong></td>
                                <td>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%; background: #207027;"></div>
                                        <span class="progress-text"><?php echo $percentage; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Styles loaded via $extra_css in <head> (assets/css/reports.css) to eliminate FOUC -->
<script>
function exportReport(format) {
    const type = document.getElementById('reportType').value;
    window.location.href = '<?php echo BASE_URL; ?>/reports/weapons?type=' + type + '&format=' + format;
}

function saveReport() {
    const reportName = prompt('Enter a name for this report:');
    if (reportName) {
        const type = document.getElementById('reportType').value;
        
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
        typeInput.value = 'weapons';
        form.appendChild(typeInput);
        
        const paramsInput = document.createElement('input');
        paramsInput.type = 'hidden';
        paramsInput.name = 'parameters';
        paramsInput.value = JSON.stringify({type});
        form.appendChild(paramsInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize charts with professional color palette
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($hasData && $type == 'inventory'): ?>
    // Weapons Status Chart - Professional color palette
    const el1 = document.getElementById('weaponsChart');
    if (el1) {
        if (typeof Chart.getChart === 'function') {
            const existing1 = Chart.getChart(el1);
            if (existing1) existing1.destroy();
        }
        const ctx1 = el1.getContext('2d');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Serviceable', 'Unserviceable', 'Under Repair'],
                datasets: [{
                    data: [<?php echo $serviceable; ?>, <?php echo $unserviceable; ?>, <?php echo $underRepair; ?>],
                    backgroundColor: [
                        '#4CAF50',
                        '#D32F2F',
                        '#FF8F00'
                    ],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12,
                                family: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Weapons by Condition',
                        font: {
                            size: 16,
                            weight: '500',
                            family: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
                        },
                        padding: {
                            bottom: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(19, 70, 23, 0.9)',
                        titleFont: { size: 13, weight: '500' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 6
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if ($hasData && $type == 'by_type'): ?>
    // Weapons by Type Chart - Professional color palette
    const el2 = document.getElementById('typeChart');
    if (el2) {
        if (typeof Chart.getChart === 'function') {
            const existing2 = Chart.getChart(el2);
            if (existing2) existing2.destroy();
        }
        const ctx2 = el2.getContext('2d');
        
        // Professional color palette for bars
        const professionalColors = [
            '#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#F44336',
            '#009688', '#673AB7', '#FFC107', '#E91E63', '#3F51B5',
            '#8BC34A', '#00BCD4', '#FF5722', '#795548', '#607D8B'
        ];
        
        const labels = [];
        const counts = [];
        const colors = [];
        
        <?php 
        $index = 0;
        foreach ($data as $item): 
        ?>
        labels.push('<?php echo isset($item['type']) ? addslashes($item['type']) : 'Other'; ?>');
        counts.push(<?php echo $item['count'] ?? 0; ?>);
        colors.push(professionalColors[<?php echo $index % 15; ?>]);
        <?php 
        $index++;
        endforeach; 
        ?>
        
        new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Weapons',
                data: counts,
                backgroundColor: colors,
                borderRadius: 4,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Weapons Distribution by Type',
                    font: {
                        size: 16,
                        weight: '500',
                        family: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
                    },
                    padding: {
                        bottom: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(19, 70, 23, 0.9)',
                    titleFont: { size: 13, weight: '500' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 6,
                    callbacks: {
                        label: function(context) {
                            return `Count: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: { size: 11 }
                    }
                }
            }
        }
    });
    }
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>