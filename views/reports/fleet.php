<?php
$title = 'Fleet Reports';
$active = 'reports';
$init_charts = true;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$type = $_GET['type'] ?? 'summary';

// Ensure $data is always defined and is an array
$data = $data ?? [];

// For summary type, ensure data has all required keys
if ($type == 'summary') {
    $data = array_merge([
        'vehicles' => [],
        'aircraft' => [],
        'marine' => [],
        'motorcycles' => []
    ], $data);
}

// Check if data is empty for specific types
$hasData = !empty($data);
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-truck"></i>
                Fleet Reports
            </h1>
            <p>Vehicles, aircraft, marine, and motorcycles reports</p>
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
        <form method="GET" action="<?php echo BASE_URL; ?>/reports/fleet" id="reportForm">
            <div class="controls-grid">
                <div class="control-group">
                    <label>Report Type</label>
                    <select name="type" id="reportType" onchange="this.form.submit()">
                        <option value="summary" <?php echo $type == 'summary' ? 'selected' : ''; ?>>Fleet Summary</option>
                        <option value="vehicles" <?php echo $type == 'vehicles' ? 'selected' : ''; ?>>Vehicles</option>
                        <option value="aircraft" <?php echo $type == 'aircraft' ? 'selected' : ''; ?>>Aircraft</option>
                        <option value="marine" <?php echo $type == 'marine' ? 'selected' : ''; ?>>Marine</option>
                        <option value="motorcycles" <?php echo $type == 'motorcycles' ? 'selected' : ''; ?>>Motorcycles</option>
                        <option value="maintenance" <?php echo $type == 'maintenance' ? 'selected' : ''; ?>>Maintenance Due</option>
                        <option value="insurance" <?php echo $type == 'insurance' ? 'selected' : ''; ?>>Insurance Expiring</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Report Content -->
    <div class="report-content" id="reportContent">
        <?php if ($type == 'summary'): ?>
            <div class="report-section">
                <h2>Fleet Summary Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card" style="background: linear-gradient(135deg, #0D47A1 0%, #1976D2 100%);">
                        <div class="card-icon"><i class="fas fa-car"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?php echo count($data['vehicles'] ?? []); ?></div>
                            <div class="card-label">Vehicles</div>
                        </div>
                    </div>
                    
                    <div class="summary-card" style="background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);">
                        <div class="card-icon"><i class="fas fa-helicopter"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?php echo count($data['aircraft'] ?? []); ?></div>
                            <div class="card-label">Aircraft</div>
                        </div>
                    </div>
                    
                    <div class="summary-card" style="background: linear-gradient(135deg, #B71C1C 0%, #D32F2F 100%);">
                        <div class="card-icon"><i class="fas fa-ship"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?php echo count($data['marine'] ?? []); ?></div>
                            <div class="card-label">Marine</div>
                        </div>
                    </div>
                    
                    <div class="summary-card" style="background: linear-gradient(135deg, #B26A00 0%, #FF8F00 100%);">
                        <div class="card-icon"><i class="fas fa-motorcycle"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?php echo count($data['motorcycles'] ?? []); ?></div>
                            <div class="card-label">Motorcycles</div>
                        </div>
                    </div>
                </div>
                
                <div class="fleet-summary">
                    <!-- Vehicles Summary -->
                    <div class="fleet-category">
                        <h3><i class="fas fa-car" style="color: #1976D2;"></i> Vehicles</h3>
                        <div class="category-stats">
                            <?php
                            $vehicles = $data['vehicles'] ?? [];
                            $activeVehicles = count(array_filter($vehicles, function($v) { return ($v['operational_status'] ?? '') == 'Active'; }));
                            $inRepairVehicles = count(array_filter($vehicles, function($v) { return ($v['operational_status'] ?? '') == 'In Repair'; }));
                            $groundedVehicles = count(array_filter($vehicles, function($v) { return ($v['operational_status'] ?? '') == 'Grounded'; }));
                            ?>
                            <div class="stat">
                                <span class="stat-label">Total:</span>
                                <span class="stat-value"><?php echo count($vehicles); ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Active:</span>
                                <span class="stat-value active"><?php echo $activeVehicles; ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">In Repair:</span>
                                <span class="stat-value warning"><?php echo $inRepairVehicles; ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Grounded:</span>
                                <span class="stat-value rejected"><?php echo $groundedVehicles; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aircraft Summary -->
                    <div class="fleet-category">
                        <h3><i class="fas fa-helicopter" style="color: #4CAF50;"></i> Aircraft</h3>
                        <div class="category-stats">
                            <?php
                            $aircraft = $data['aircraft'] ?? [];
                            $operationalAircraft = count(array_filter($aircraft, function($a) { return ($a['operational_status'] ?? '') == 'Operational'; }));
                            $maintenanceAircraft = count(array_filter($aircraft, function($a) { return ($a['operational_status'] ?? '') == 'Maintenance'; }));
                            ?>
                            <div class="stat">
                                <span class="stat-label">Total:</span>
                                <span class="stat-value"><?php echo count($aircraft); ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Operational:</span>
                                <span class="stat-value active"><?php echo $operationalAircraft; ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Maintenance:</span>
                                <span class="stat-value warning"><?php echo $maintenanceAircraft; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Marine Summary -->
                    <div class="fleet-category">
                        <h3><i class="fas fa-ship" style="color: #D32F2F;"></i> Marine</h3>
                        <div class="category-stats">
                            <?php
                            $marine = $data['marine'] ?? [];
                            $operationalMarine = count(array_filter($marine, function($m) { return ($m['operational_status'] ?? '') == 'Operational'; }));
                            ?>
                            <div class="stat">
                                <span class="stat-label">Total:</span>
                                <span class="stat-value"><?php echo count($marine); ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Operational:</span>
                                <span class="stat-value active"><?php echo $operationalMarine; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Motorcycles Summary -->
                    <div class="fleet-category">
                        <h3><i class="fas fa-motorcycle" style="color: #FF8F00;"></i> Motorcycles</h3>
                        <div class="category-stats">
                            <?php
                            $motorcycles = $data['motorcycles'] ?? [];
                            $activeMotorcycles = count(array_filter($motorcycles, function($m) { return ($m['operational_status'] ?? '') == 'Active'; }));
                            ?>
                            <div class="stat">
                                <span class="stat-label">Total:</span>
                                <span class="stat-value"><?php echo count($motorcycles); ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Active:</span>
                                <span class="stat-value active"><?php echo $activeMotorcycles; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($type == 'vehicles'): ?>
            <div class="report-section">
                <h2>Vehicle Fleet Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <?php if (empty($data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-car"></i>
                        <p>No vehicle data found</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Registration</th>
                                <th>Make/Model</th>
                                <th>Year</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Condition</th>
                                <th>Mileage</th>
                                <th>Assigned Officer</th>
                                <th>Insurance Expiry</th>
                                <th>Next Service</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $vehicle): ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($vehicle['asset_code']) ? htmlspecialchars($vehicle['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($vehicle['registration_number']) ? htmlspecialchars($vehicle['registration_number']) : '-'; ?></td>
                                <td><?php echo isset($vehicle['make_manufacturer']) ? htmlspecialchars($vehicle['make_manufacturer']) : '-'; ?></td>
                                <td><?php echo $vehicle['model_year'] ?? '-'; ?></td>
                                <td><?php echo $vehicle['vehicle_type'] ?? '-'; ?></td>
                                <td>
                                    <?php 
                                    $status = $vehicle['operational_status'] ?? 'Unknown';
                                    $statusClass = '';
                                    if ($status == 'Active') $statusClass = 'badge-success';
                                    elseif ($status == 'In Repair') $statusClass = 'badge-warning';
 elseif ($status == 'Grounded') $statusClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td><?php echo $vehicle['condition'] ?? '-'; ?></td>
                                <td class="text-right"><?php echo isset($vehicle['mileage']) ? number_format($vehicle['mileage']) . ' km' : '-'; ?></td>
                                <td><?php echo $vehicle['assigned_officer'] ?? '-'; ?></td>
                                <td>
                                    <?php if (!empty($vehicle['insurance_expiry'])): 
                                        $expiryDate = strtotime($vehicle['insurance_expiry']);
                                        $today = time();
                                        $daysToExpiry = round(($expiryDate - $today) / (60 * 60 * 24));
                                    ?>
                                        <?php echo date('d/m/Y', $expiryDate); ?>
                                        <?php if ($daysToExpiry <= 60): ?>
                                            <br>
                                            <span class="days-badge <?php echo $daysToExpiry < 0 ? 'expired' : ($daysToExpiry <= 30 ? 'critical' : 'warning'); ?>">
                                                <?php echo $daysToExpiry < 0 ? 'Expired' : $daysToExpiry . ' days'; ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($vehicle['next_service_date'])): ?>
                                        <?php echo date('d/m/Y', strtotime($vehicle['next_service_date'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'aircraft'): ?>
            <div class="report-section">
                <h2>Aircraft Fleet Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <?php if (empty($data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-helicopter"></i>
                        <p>No aircraft data found</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Registration</th>
                                <th>Make/Model</th>
                                <th>Year</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Hours Flown</th>
                                <th>Last Inspection</th>
                                <th>Next Inspection</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $aircraft): ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($aircraft['asset_code']) ? htmlspecialchars($aircraft['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($aircraft['registration_number']) ? htmlspecialchars($aircraft['registration_number']) : '-'; ?></td>
                                <td><?php echo isset($aircraft['make_manufacturer']) ? htmlspecialchars($aircraft['make_manufacturer']) : '-'; ?></td>
                                <td><?php echo $aircraft['model_year'] ?? '-'; ?></td>
                                <td><?php echo $aircraft['aircraft_type'] ?? '-'; ?></td>
                                <td>
                                    <?php 
                                    $status = $aircraft['operational_status'] ?? 'Unknown';
                                    $statusClass = '';
                                    if ($status == 'Operational') $statusClass = 'badge-success';
                                    elseif ($status == 'Maintenance') $statusClass = 'badge-warning';
                                    elseif ($status == 'Grounded') $statusClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td class="text-right"><?php echo isset($aircraft['hours_flown']) ? number_format($aircraft['hours_flown']) . ' hrs' : '-'; ?></td>
                                <td><?php echo isset($aircraft['last_inspection_date']) ? date('d/m/Y', strtotime($aircraft['last_inspection_date'])) : '-'; ?></td>
                                <td><?php echo isset($aircraft['next_inspection_date']) ? date('d/m/Y', strtotime($aircraft['next_inspection_date'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'marine'): ?>
            <div class="report-section">
                <h2>Marine Fleet Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <?php if (empty($data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-ship"></i>
                        <p>No marine vessel data found</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Vessel Name</th>
                                <th>Type</th>
                                <th>Registration</th>
                                <th>Length</th>
                                <th>Status</th>
                                <th>Last Inspection</th>
                                <th>Next Inspection</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $vessel): ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($vessel['asset_code']) ? htmlspecialchars($vessel['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($vessel['vessel_name']) ? htmlspecialchars($vessel['vessel_name']) : '-'; ?></td>
                                <td><?php echo $vessel['vessel_type'] ?? '-'; ?></td>
                                <td><?php echo isset($vessel['registration_number']) ? htmlspecialchars($vessel['registration_number']) : '-'; ?></td>
                                <td><?php echo isset($vessel['length']) ? $vessel['length'] . 'm' : '-'; ?></td>
                                <td>
                                    <?php 
                                    $status = $vessel['operational_status'] ?? 'Unknown';
                                    $statusClass = '';
                                    if ($status == 'Operational') $statusClass = 'badge-success';
                                    elseif ($status == 'Maintenance') $statusClass = 'badge-warning';
                                    elseif ($status == 'Decommissioned') $statusClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td><?php echo isset($vessel['last_inspection_date']) ? date('d/m/Y', strtotime($vessel['last_inspection_date'])) : '-'; ?></td>
                                <td><?php echo isset($vessel['next_inspection_date']) ? date('d/m/Y', strtotime($vessel['next_inspection_date'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'motorcycles'): ?>
            <div class="report-section">
                <h2>Motorcycle Fleet Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                
                <?php if (empty($data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-motorcycle"></i>
                        <p>No motorcycle data found</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Registration</th>
                                <th>Make/Model</th>
                                <th>Year</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Mileage</th>
                                <th>Assigned Officer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $motorcycle): ?>
                            <tr>
                                <td><span class="asset-code"><?php echo isset($motorcycle['asset_code']) ? htmlspecialchars($motorcycle['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($motorcycle['registration_number']) ? htmlspecialchars($motorcycle['registration_number']) : '-'; ?></td>
                                <td><?php echo isset($motorcycle['make_manufacturer']) ? htmlspecialchars($motorcycle['make_manufacturer']) : '-'; ?></td>
                                <td><?php echo $motorcycle['model_year'] ?? '-'; ?></td>
                                <td><?php echo $motorcycle['motorcycle_type'] ?? '-'; ?></td>
                                <td>
                                    <?php 
                                    $status = $motorcycle['operational_status'] ?? 'Unknown';
                                    $statusClass = '';
                                    if ($status == 'Active') $statusClass = 'badge-success';
                                    elseif ($status == 'In Repair') $statusClass = 'badge-warning';
                                    elseif ($status == 'Grounded') $statusClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td class="text-right"><?php echo isset($motorcycle['mileage']) ? number_format($motorcycle['mileage']) . ' km' : '-'; ?></td>
                                <td><?php echo $motorcycle['assigned_officer'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'maintenance'): ?>
            <div class="report-section">
                <h2>Maintenance Due Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                <p class="report-note">Vehicles requiring service within the next 30 days</p>
                
                <?php if (empty($data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tools"></i>
                        <p>No vehicles due for maintenance</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Registration</th>
                                <th>Make/Model</th>
                                <th>Last Service</th>
                                <th>Next Service</th>
                                <th>Days Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $vehicle): 
                                $daysToService = $vehicle['days_to_service'] ?? 0;
                            ?>
                            <tr class="<?php echo $daysToService < 0 ? 'expired-row' : 'warning-row'; ?>">
                                <td><span class="asset-code"><?php echo isset($vehicle['asset_code']) ? htmlspecialchars($vehicle['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($vehicle['registration_number']) ? htmlspecialchars($vehicle['registration_number']) : '-'; ?></td>
                                <td><?php echo isset($vehicle['make_manufacturer']) ? htmlspecialchars($vehicle['make_manufacturer']) : '-'; ?></td>
                                <td><?php echo isset($vehicle['last_service_date']) ? date('d/m/Y', strtotime($vehicle['last_service_date'])) : '-'; ?></td>
                                <td><?php echo isset($vehicle['next_service_date']) ? date('d/m/Y', strtotime($vehicle['next_service_date'])) : '-'; ?></td>
                                <td class="text-center">
                                    <span class="days-badge <?php echo $daysToService < 0 ? 'expired' : 'warning'; ?>">
                                        <?php echo $daysToService < 0 ? 'Overdue' : $daysToService . ' days'; ?>
                                    </span>
                                </td>
                                <td><?php echo $vehicle['operational_status'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($type == 'insurance'): ?>
            <div class="report-section">
                <h2>Insurance Expiring Report</h2>
                <p class="report-date">Generated: <?php echo date('d/m/Y H:i'); ?></p>
                <p class="report-note">Vehicle insurance expiring within the next 60 days</p>
                
                <?php if (empty($data)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-invoice"></i>
                        <p>No vehicles with expiring insurance</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Registration</th>
                                <th>Make/Model</th>
                                <th>Insurance Status</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $vehicle): 
                                $daysToExpiry = $vehicle['days_to_expiry'] ?? 0;
                            ?>
                            <tr class="<?php echo $daysToExpiry < 0 ? 'expired-row' : ($daysToExpiry <= 30 ? 'critical-row' : 'warning-row'); ?>">
                                <td><span class="asset-code"><?php echo isset($vehicle['asset_code']) ? htmlspecialchars($vehicle['asset_code']) : '-'; ?></span></td>
                                <td><?php echo isset($vehicle['registration_number']) ? htmlspecialchars($vehicle['registration_number']) : '-'; ?></td>
                                <td><?php echo isset($vehicle['make_manufacturer']) ? htmlspecialchars($vehicle['make_manufacturer']) : '-'; ?></td>
                                <td><?php echo $vehicle['insurance_status'] ?? '-'; ?></td>
                                <td><?php echo isset($vehicle['insurance_expiry']) ? date('d/m/Y', strtotime($vehicle['insurance_expiry'])) : '-'; ?></td>
                                <td class="text-center">
                                    <span class="days-badge <?php echo $daysToExpiry < 0 ? 'expired' : ($daysToExpiry <= 30 ? 'critical' : 'warning'); ?>">
                                        <?php echo $daysToExpiry < 0 ? 'Expired' : $daysToExpiry . ' days'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
:root {
    --primary-dark: #2E7D32;
    --primary: #4CAF50;
    --danger-dark: #B71C1C;
    --danger: #D32F2F;
    --warning-dark: #B26A00;
    --warning: #FF8F00;
    --info-dark: #0D47A1;
    --info: #1976D2;
    --text-dark: #212529;
    --text-light: #53665E;
    --border: #D7E3DC;
    --bg-light: #F7FAF8;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.05);
    --shadow-lg: 0 10px 15px rgba(0,0,0,0.05);
}
[data-theme="dark"] {
    --primary-dark: #52bf57;
    --primary: #8dce90;
    --danger-dark: #e55454;
    --danger: #e37878;
    --warning-dark: #ffa219;
    --warning: #ffb75c;
    --info-dark: #2674ee;
    --info: #64a9ed;
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
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
    margin-bottom: 6px;
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
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
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

.report-date {
    color: var(--text-light);
    margin-bottom: 25px;
    font-size: 0.85rem;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border);
}

.report-note {
    color: var(--warning);
    font-style: italic;
    margin-bottom: 15px;
    font-size: 0.9rem;
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    color: white;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
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

.summary-card .card-icon {
    font-size: 2.2rem;
    opacity: 0.9;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    color: white;
}

.summary-card .card-content {
    flex: 1;
}

.summary-card .card-value {
    font-size: 2rem;
    font-weight: 400;
    line-height: 1.2;
    letter-spacing: -1px;
    color: white;
}

.summary-card .card-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
    color: white;
}

/* Fleet Summary */
.fleet-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.fleet-category {
    background: var(--bg-light);
    border-radius: 8px;
    padding: 20px;
    border: 1px solid var(--border);
}

.fleet-category h3 {
    margin: 0 0 15px 0;
    font-size: 1.1rem;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.stat {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
}

.stat:last-child {
    border-bottom: none;
}

.stat-label {
    color: var(--text-light);
    font-size: 0.9rem;
}

.stat-value {
    font-weight: 600;
    color: var(--text-dark);
}

.stat-value.active {
    color: var(--primary-dark);
}

.stat-value.warning {
    color: var(--warning-dark);
}

.stat-value.rejected {
    color: var(--danger-dark);
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
    margin-top: 20px;
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
    padding: 14px 12px;
    text-align: left;
    font-weight: 600;
    color: var(--text-dark);
    border-bottom: 2px solid var(--border);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.report-table td {
    padding: 12px;
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

.badge-danger {
    background: #FFEBEE;
    color: #B71C1C;
}

.badge-warning {
    background: #FFF3E0;
    color: #B26A00;
}

.badge-info {
    background: #E3F2FD;
    color: #0D47A1;
}

/* Days badge */
.days-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 4px;
}

.days-badge.warning {
    background: #FFF3E0;
    color: #B26A00;
}

.days-badge.critical {
    background: #FFEBEE;
    color: #B71C1C;
}

.days-badge.expired {
    background: #FFEBEE;
    color: #B71C1C;
}

/* Row highlighting */
.expired-row {
    background-color: #FFEBEE;
}

.expired-row:hover {
    background-color: #FFCDD2 !important;
}

.critical-row {
    background-color: #FFEBEE;
}

.critical-row:hover {
    background-color: #FFCDD2 !important;
}

.warning-row {
    background-color: #FFF8E1;
}

.warning-row:hover {
    background-color: #FFECB3 !important;
}

/* Asset code */
.asset-code {
    font-family: 'SF Mono', 'Fira Code', monospace;
    background: var(--bg-light);
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: var(--info-dark);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-light);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #ccc;
}

.empty-state p {
    font-size: 1rem;
}

/* Text utilities */
.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

/* Button group */
.btn-group {
    display: flex;
    gap: 5px;
}

.btn {
    transition: all 0.2s;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

/* Responsive */
@media (max-width: 768px) {
    .report-content {
        padding: 20px;
    }
    
    .summary-cards {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .summary-card {
        padding: 15px;
    }
    
    .summary-card .card-icon {
        font-size: 1.8rem;
    }
    
    .summary-card .card-value {
        font-size: 1.5rem;
    }
    
    .fleet-summary {
        grid-template-columns: 1fr;
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
function exportReport(format) {
    const type = document.getElementById('reportType').value;
    window.location.href = '<?php echo BASE_URL; ?>/reports/fleet?type=' + type + '&format=' + format;
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
        typeInput.value = 'fleet';
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