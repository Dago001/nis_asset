<?php
$title = 'Ammunition Reports';
$active = 'ammo-reports';
$extra_css = [BASE_URL . '/assets/css/reports.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$type = $_GET['type'] ?? 'inventory';

// Ensure $data is always defined and is an array
$data = $data ?? [];

// Check if data is empty
$hasData = !empty($data);

// Pagination (see ReportController::ammunition() / getAmmunitionReportData())
$page = isset($page) ? (int) $page : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$totalCount = isset($totalCount) ? (int) $totalCount : count($data);
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-bullseye"></i>
                Ammunition Reports
            </h1>
            <p>Ammunition inventory, expiry tracking, and usage reports</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-success" onclick="saveReport()">
                <i class="fas fa-bookmark"></i> Save Report
            </button>
            <div class="btn-group">
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
        <form method="GET" action="<?php echo BASE_URL; ?>/reports/ammunition" id="reportForm">
            <div class="controls-grid">
                <div class="control-group">
                    <label>Report Type</label>
                    <select name="type" id="reportType" onchange="this.form.submit()">
                        <option value="inventory" <?php echo $type == 'inventory' ? 'selected' : ''; ?>>Full Inventory</option>
                        <option value="expiring" <?php echo $type == 'expiring' ? 'selected' : ''; ?>>Expiring Ammunition</option>
                        <option value="low_stock" <?php echo $type == 'low_stock' ? 'selected' : ''; ?>>Low Stock Alert</option>
                        <option value="issued" <?php echo $type == 'issued' ? 'selected' : ''; ?>>Issued Ammunition</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Report Content -->
    <div class="report-content" id="reportContent">
        <?php if (!$hasData): ?>
            <div class="empty-state">
                <i class="fas fa-bullseye"></i>
                <p>No ammunition data found for the selected report type.</p>
            </div>
        <?php elseif ($type == 'inventory'): ?>
            <div class="report-section">
                <h2>Ammunition Inventory Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <!-- Summary Cards -->
                <div class="summary-cards">
                    <?php
                    $summary = isset($summary) && is_array($summary) ? $summary : [];
                    $totalRounds = $summary['total_rounds'] ?? 0;
                    $expiringCount = $summary['expiring_count'] ?? 0;
                    $lowStockCount = $summary['low_stock_count'] ?? 0;
                    $totalBatches = $summary['total_batches'] ?? count($data);
                    ?>
                    
                    <div class="summary-card" style="background: linear-gradient(135deg, #0D47A1 0%, #1976D2 100%);">
                        <div class="card-icon"><i class="fas fa-bullseye"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?php echo number_format($totalRounds); ?></div>
                            <div class="card-label">Total Rounds</div>
                        </div>
                    </div>
                    
                    <div class="summary-card" style="background: linear-gradient(135deg, #B71C1C 0%, #D32F2F 100%);">
                        <div class="card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?php echo number_format($expiringCount); ?></div>
                            <div class="card-label">Expiring Soon</div>
                        </div>
                    </div>
                    
                    <div class="summary-card" style="background: linear-gradient(135deg, #B26A00 0%, #FF8F00 100%);">
                        <div class="card-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?php echo number_format($lowStockCount); ?></div>
                            <div class="card-label">Low Stock</div>
                        </div>
                    </div>
                    
                    <div class="summary-card" style="background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);">
                        <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?php echo number_format($totalBatches); ?></div>
                            <div class="card-label">Total Batches</div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Ammo ID</th>
                                <th>Type</th>
                                <th>Calibre</th>
                                <th>Batch Number</th>
                                <th>Storage Location</th>
                                <th>Received</th>
                                <th>Issued</th>
                                <th>Balance</th>
                                <th>Expiry Date</th>
                                <th>Condition</th>
                                <th>Manufacturer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $ammo): 
                                $received = (int)($ammo['quantity_received'] ?? 0);
                                $issued = (int)($ammo['quantity_issued'] ?? 0);
                                $balance = $received - $issued;
                                $expiryDate = isset($ammo['expiry_date']) ? strtotime($ammo['expiry_date']) : null;
                                $today = time();
                                $daysToExpiry = $expiryDate ? round(($expiryDate - $today) / (60 * 60 * 24)) : null;
                                
                                $rowClass = '';
                                if ($balance < 100) $rowClass = 'low-stock-row';
                                elseif ($daysToExpiry && $daysToExpiry <= 90) $rowClass = 'expiring-row';
                                if ($daysToExpiry && $daysToExpiry < 0) $rowClass = 'expired-row';
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td><span class="asset-code"><?php echo isset($ammo['ammo_id']) ? htmlspecialchars($ammo['ammo_id']) : '-'; ?></span></td>
                                <td><?php echo isset($ammo['ammo_type']) ? htmlspecialchars($ammo['ammo_type']) : 'Other'; ?></td>
                                <td><?php echo isset($ammo['calibre']) ? htmlspecialchars($ammo['calibre']) : 'N/A'; ?></td>
                                <td><code><?php echo isset($ammo['batch_number']) ? htmlspecialchars($ammo['batch_number']) : '-'; ?></code></td>
                                <td><?php echo $ammo['storage_location'] ?? '-'; ?></td>
                                <td class="text-right"><?php echo number_format($received); ?></td>
                                <td class="text-right"><?php echo number_format($issued); ?></td>
                                <td class="text-right <?php echo $balance < 100 ? 'text-danger' : ''; ?>">
                                    <strong><?php echo number_format($balance); ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($ammo['expiry_date'])): ?>
                                        <?php echo date('d/m/Y', strtotime($ammo['expiry_date'])); ?>
                                        <?php if ($daysToExpiry && $daysToExpiry <= 90): ?>
                                            <br>
                                            <span class="days-badge <?php 
                                                echo $daysToExpiry < 0 ? 'expired' : 
                                                    ($daysToExpiry <= 30 ? 'critical' : 'warning'); 
                                            ?>">
                                                <?php echo $daysToExpiry < 0 ? 'Expired' : $daysToExpiry . ' days'; ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $condition = $ammo['condition'] ?? 'Unknown';
                                    $conditionClass = '';
                                    if ($condition == 'Serviceable') $conditionClass = 'badge-success';
                                    elseif ($condition == 'Unserviceable') $conditionClass = 'badge-warning';
                                    elseif ($condition == 'Condemned') $conditionClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $conditionClass; ?>">
                                        <?php echo $condition; ?>
                                    </span>
                                </td>
                                <td><?php echo $ammo['manufacturer'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if (!empty($data)): ?>
                        <tfoot>
                            <?php
                            $totalReceived = array_sum(array_column($data, 'quantity_received'));
                            $totalIssued = array_sum(array_column($data, 'quantity_issued'));
                            $totalBalance = $totalReceived - $totalIssued;
                            ?>
                            <tr>
                                <th colspan="5" class="text-right">Totals:</th>
                                <th class="text-right"><?php echo number_format($totalReceived); ?></th>
                                <th class="text-right"><?php echo number_format($totalIssued); ?></th>
                                <th class="text-right"><?php echo number_format($totalBalance); ?></th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?type=<?php echo urlencode($type); ?>&page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?type=<?php echo urlencode($type); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($type == 'expiring'): ?>
            <div class="report-section">
                <h2>Expiring Ammunition Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                <p class="report-note">Ammunition expiring within 90 days</p>
                
                <?php if (empty($data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <p>No expiring ammunition found</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Ammo ID</th>
                                <th>Type</th>
                                <th>Calibre</th>
                                <th>Batch Number</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                                <th>Balance</th>
                                <th>Storage Location</th>
                                <th>Condition</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $ammo): 
                                $daysRemaining = $ammo['days_remaining'] ?? 0;
                            ?>
                            <tr class="<?php 
                                echo $daysRemaining < 0 ? 'expired-row' : 
                                    ($daysRemaining <= 30 ? 'critical-row' : 'warning-row'); 
                            ?>">
                                <td><span class="asset-code"><?php echo isset($ammo['ammo_id']) ? htmlspecialchars($ammo['ammo_id']) : '-'; ?></span></td>
                                <td><?php echo isset($ammo['ammo_type']) ? htmlspecialchars($ammo['ammo_type']) : '-'; ?></td>
                                <td><?php echo isset($ammo['calibre']) ? htmlspecialchars($ammo['calibre']) : '-'; ?></td>
                                <td><code><?php echo isset($ammo['batch_number']) ? htmlspecialchars($ammo['batch_number']) : '-'; ?></code></td>
                                <td><?php echo isset($ammo['expiry_date']) ? date('d/m/Y', strtotime($ammo['expiry_date'])) : '-'; ?></td>
                                <td class="text-center">
                                    <span class="days-badge <?php 
                                        echo $daysRemaining < 0 ? 'expired' : 
                                            ($daysRemaining <= 30 ? 'critical' : 'warning'); 
                                    ?>">
                                        <?php echo $daysRemaining < 0 ? 'Expired' : $daysRemaining . ' days'; ?>
                                    </span>
                                </td>
                                <td class="text-right"><?php echo number_format($ammo['balance'] ?? 0); ?></td>
                                <td><?php echo $ammo['storage_location'] ?? '-'; ?></td>
                                <td><?php echo $ammo['condition'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?type=<?php echo urlencode($type); ?>&page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?type=<?php echo urlencode($type); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="report-summary">
                    <h3>Summary</h3>
                    <?php
                    $expired = count(array_filter($data, function($a) { return ($a['days_remaining'] ?? 0) < 0; }));
                    $critical = count(array_filter($data, function($a) { 
                        $days = $a['days_remaining'] ?? 0;
                        return $days >= 0 && $days <= 30;
                    }));
                    $warning = count(array_filter($data, function($a) { 
                        $days = $a['days_remaining'] ?? 0;
                        return $days > 30 && $days <= 90;
                    }));
                    ?>
                    <p>Total Expiring Items: <?php echo count($data); ?></p>
                    <p><span class="badge badge-danger">Expired</span>: <?php echo $expired; ?></p>
                    <p><span class="badge badge-warning">Critical (≤30 days)</span>: <?php echo $critical; ?></p>
                    <p><span class="badge badge-info">Warning (31-90 days)</span>: <?php echo $warning; ?></p>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'low_stock'): ?>
            <div class="report-section">
                <h2>Low Stock Ammunition Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                <p class="report-note">Ammunition with balance below 100 rounds</p>
                
                <?php if (empty($data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No low stock ammunition found</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Ammo ID</th>
                                <th>Type</th>
                                <th>Calibre</th>
                                <th>Batch Number</th>
                                <th>Current Balance</th>
                                <th>Reorder Level</th>
                                <th>Storage Location</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $ammo): 
                                $balance = (int)($ammo['quantity_received'] ?? 0) - (int)($ammo['quantity_issued'] ?? 0);
                            ?>
                            <tr class="low-stock-row">
                                <td><span class="asset-code"><?php echo isset($ammo['ammo_id']) ? htmlspecialchars($ammo['ammo_id']) : '-'; ?></span></td>
                                <td><?php echo isset($ammo['ammo_type']) ? htmlspecialchars($ammo['ammo_type']) : '-'; ?></td>
                                <td><?php echo isset($ammo['calibre']) ? htmlspecialchars($ammo['calibre']) : '-'; ?></td>
                                <td><code><?php echo isset($ammo['batch_number']) ? htmlspecialchars($ammo['batch_number']) : '-'; ?></code></td>
                                <td class="text-right"><strong class="text-danger"><?php echo number_format($balance); ?></strong></td>
                                <td class="text-center"><span class="badge badge-warning">100</span></td>
                                <td><?php echo $ammo['storage_location'] ?? '-'; ?></td>
                                <td><?php echo isset($ammo['expiry_date']) ? date('d/m/Y', strtotime($ammo['expiry_date'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?type=<?php echo urlencode($type); ?>&page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?type=<?php echo urlencode($type); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'issued'): ?>
            <div class="report-section">
                <h2>Issued Ammunition Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <?php if (empty($data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No issued ammunition records found</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Issue Date</th>
                                <th>Ammunition</th>
                                <th>Type</th>
                                <th>Calibre</th>
                                <th>Officer</th>
                                <th>Rank</th>
                                <th>Unit</th>
                                <th>Purpose</th>
                                <th>Rounds Issued</th>
                                <th>Requisition #</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $issue): ?>
                            <tr>
                                <td><?php echo isset($issue['issue_date']) ? date('d/m/Y', strtotime($issue['issue_date'])) : '-'; ?></td>
                                <td><?php echo isset($issue['ammo_id']) ? htmlspecialchars($issue['ammo_id']) : '-'; ?></td>
                                <td><?php echo isset($issue['ammo_type']) ? htmlspecialchars($issue['ammo_type']) : '-'; ?></td>
                                <td><?php echo isset($issue['calibre']) ? htmlspecialchars($issue['calibre']) : '-'; ?></td>
                                <td><?php echo isset($issue['officer_name']) ? htmlspecialchars($issue['officer_name']) : '-'; ?></td>
                                <td><?php echo isset($issue['officer_rank']) ? htmlspecialchars($issue['officer_rank']) : '-'; ?></td>
                                <td><?php echo isset($issue['unit']) ? htmlspecialchars($issue['unit']) : '-'; ?></td>
                                <td><?php echo $issue['purpose'] ?? '-'; ?></td>
                                <td class="text-right"><strong><?php echo number_format($issue['rounds_issued'] ?? 0); ?></strong></td>
                                <td><code><?php echo $issue['requisition_number'] ?? '-'; ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if (!empty($data)): ?>
                        <tfoot>
                            <tr>
                                <th colspan="8" class="text-right">Total Rounds Issued:</th>
                                <th class="text-right"><?php echo number_format(array_sum(array_column($data, 'rounds_issued'))); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?type=<?php echo urlencode($type); ?>&page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
                        <a href="?type=<?php echo urlencode($type); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Styles loaded via $extra_css in <head> (assets/css/reports.css) to eliminate FOUC -->

<script>
function exportReport(format) {
    const type = document.getElementById('reportType').value;
    window.location.href = '<?php echo BASE_URL; ?>/reports/ammunition?type=' + type + '&format=' + format;
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
        typeInput.value = 'ammunition';
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
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>