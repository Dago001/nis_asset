<?php
$title = 'Audit Reports';
$active = 'reports';
$init_charts = true;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$quarter = $_GET['quarter'] ?? 'Q' . ceil(date('n') / 3);
$year = $_GET['year'] ?? date('Y');

// Ensure $data is always defined and is an array
$data = $data ?? [];
$hasData = !empty($data);
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-clipboard-check"></i>
                Audit Reports
            </h1>
            <p>Quarterly audit results and variance reports</p>
        </div>
        <div class="header-actions">
            <div class="btn-group">
                <button class="btn btn-outline" onclick="exportReport('pdf')" <?php echo !$hasData ? 'disabled' : ''; ?>>
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button class="btn btn-outline" onclick="exportReport('csv')" <?php echo !$hasData ? 'disabled' : ''; ?>>
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button class="btn btn-outline" onclick="window.print()" <?php echo !$hasData ? 'disabled' : ''; ?>>
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
            <a href="<?php echo BASE_URL; ?>/reports" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Report Controls -->
    <div class="report-controls">
        <form method="GET" action="<?php echo BASE_URL; ?>/reports/audit" id="reportForm">
            <div class="controls-grid">
                <div class="control-group">
                    <label>Quarter</label>
                    <select name="quarter" onchange="this.form.submit()">
                        <option value="Q1" <?php echo $quarter == 'Q1' ? 'selected' : ''; ?>>Q1 (Jan-Mar)</option>
                        <option value="Q2" <?php echo $quarter == 'Q2' ? 'selected' : ''; ?>>Q2 (Apr-Jun)</option>
                        <option value="Q3" <?php echo $quarter == 'Q3' ? 'selected' : ''; ?>>Q3 (Jul-Sep)</option>
                        <option value="Q4" <?php echo $quarter == 'Q4' ? 'selected' : ''; ?>>Q4 (Oct-Dec)</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>Year</label>
                    <select name="year" onchange="this.form.submit()">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Report Content -->
    <div class="report-content" id="reportContent">
        <?php if (!$hasData): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <p>No audit reports found for <?php echo $quarter . ' ' . $year; ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($data as $index => $audit): ?>
            <div class="audit-report">
                <div class="audit-header">
                    <h2>Quarterly Audit Report</h2>
                    <h3><?php echo isset($audit['audit_number']) ? htmlspecialchars($audit['audit_number']) : 'N/A'; ?></h3>
                </div>
                
                <div class="audit-meta">
                    <div class="meta-item">
                        <span class="meta-label">Audit Date:</span>
                        <span class="meta-value"><?php echo isset($audit['audit_date']) ? date('d/m/Y', strtotime($audit['audit_date'])) : '-'; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Quarter:</span>
                        <span class="meta-value"><?php echo ($audit['quarter'] ?? '') . ' ' . ($audit['year'] ?? ''); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Audit Officer:</span>
                        <span class="meta-value"><?php echo isset($audit['audit_officer']) ? htmlspecialchars($audit['audit_officer']) : '-'; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Command:</span>
                        <span class="meta-value"><?php echo isset($audit['command_name']) ? htmlspecialchars($audit['command_name']) : 'N/A'; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Location:</span>
                        <span class="meta-value"><?php echo isset($audit['audit_location']) ? htmlspecialchars($audit['audit_location']) : '-'; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Status:</span>
                        <span class="meta-value">
                            <?php 
                            $status = $audit['status'] ?? 'Draft';
                            $statusClass = '';
                            if ($status == 'Approved') $statusClass = 'badge-success';
                            elseif ($status == 'Reviewed') $statusClass = 'badge-info';
                            elseif ($status == 'Submitted') $statusClass = 'badge-warning';
                            else $statusClass = 'badge-secondary';
                            ?>
                            <span class="badge <?php echo $statusClass; ?>">
                                <?php echo $status; ?>
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="audit-summary">
                    <h3>Audit Summary</h3>
                    <div class="summary-cards">
                        <div class="summary-card" style="background: linear-gradient(135deg, #0D47A1 0%, #1976D2 100%);">
                            <div class="card-icon"><i class="fas fa-gun"></i></div>
                            <div class="card-content">
                                <div class="card-value"><?php echo $audit['total_weapons_audited'] ?? 0; ?></div>
                                <div class="card-label">Weapons Audited</div>
                            </div>
                        </div>
                        
                        <div class="summary-card" style="background: linear-gradient(135deg, <?php echo ($audit['weapons_with_variance'] ?? 0) > 0 ? '#B71C1C 0%, #D32F2F 100%' : '#2E7D32 0%, #4CAF50 100%'; ?>);">
                            <div class="card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="card-content">
                                <div class="card-value"><?php echo $audit['weapons_with_variance'] ?? 0; ?></div>
                                <div class="card-label">Weapons with Variance</div>
                            </div>
                        </div>
                        
                        <div class="summary-card" style="background: linear-gradient(135deg, #B26A00 0%, #FF8F00 100%);">
                            <div class="card-icon"><i class="fas fa-bullseye"></i></div>
                            <div class="card-content">
                                <div class="card-value"><?php echo $audit['total_ammunition_audited'] ?? 0; ?></div>
                                <div class="card-label">Ammunition Audited</div>
                            </div>
                        </div>
                        
                        <div class="summary-card" style="background: linear-gradient(135deg, <?php echo ($audit['ammunition_with_variance'] ?? 0) > 0 ? '#B71C1C 0%, #D32F2F 100%' : '#2E7D32 0%, #4CAF50 100%'; ?>);">
                            <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="card-content">
                                <div class="card-value"><?php echo $audit['ammunition_with_variance'] ?? 0; ?></div>
                                <div class="card-label">Ammo with Variance</div>
                            </div>
                        </div>
                        
                        <div class="summary-card" style="background: linear-gradient(135deg, <?php echo ($audit['total_missing_weapons'] ?? 0) > 0 ? '#B71C1C 0%, #D32F2F 100%' : '#2E7D32 0%, #4CAF50 100%'; ?>);">
                            <div class="card-icon"><i class="fas fa-search"></i></div>
                            <div class="card-content">
                                <div class="card-value"><?php echo $audit['total_missing_weapons'] ?? 0; ?></div>
                                <div class="card-label">Missing Weapons</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weapons Audit Results -->
                <?php if (!empty($audit['weapons'])): ?>
                <div class="audit-results">
                    <h3>Weapons Audit Results</h3>
                    <div class="table-responsive">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Weapon ID</th>
                                    <th>Type</th>
                                    <th>Make/Model</th>
                                    <th>Serial Number</th>
                                    <th>System Status</th>
                                    <th>Physical Status</th>
                                    <th>Variance</th>
                                    <th>Condition</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($audit['weapons'] as $weapon): 
                                    $varianceValue = $weapon['variance_value'] ?? 0;
                                ?>
                                <tr class="<?php echo $varianceValue != 0 ? 'variance-row' : ''; ?>">
                                    <td><span class="asset-code"><?php echo isset($weapon['weapon_id']) ? htmlspecialchars($weapon['weapon_id']) : '-'; ?></span></td>
                                    <td><?php echo isset($weapon['weapon_type']) ? htmlspecialchars($weapon['weapon_type']) : '-'; ?></td>
                                    <td><?php echo isset($weapon['make_model']) ? htmlspecialchars($weapon['make_model']) : '-'; ?></td>
                                    <td><code><?php echo isset($weapon['serial_number']) ? htmlspecialchars($weapon['serial_number']) : '-'; ?></code></td>
                                    <td><?php echo $weapon['system_status'] ?? '-'; ?></td>
                                    <td><?php echo $weapon['physical_status'] ?? '-'; ?></td>
                                    <td class="<?php echo $varianceValue < 0 ? 'text-danger' : ($varianceValue > 0 ? 'text-warning' : ''); ?>">
                                        <strong><?php echo $weapon['variance'] ?? '0'; ?></strong>
                                    </td>
                                    <td><?php echo $weapon['condition'] ?? '-'; ?></td>
                                    <td><?php echo $weapon['remarks'] ?? '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ammunition Audit Results -->
                <?php if (!empty($audit['ammunition'])): ?>
                <div class="audit-results">
                    <h3>Ammunition Audit Results</h3>
                    <div class="table-responsive">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Ammunition ID</th>
                                    <th>Type</th>
                                    <th>Calibre</th>
                                    <th>System Units</th>
                                    <th>Physical Units</th>
                                    <th>Variance</th>
                                    <th>Condition</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($audit['ammunition'] as $ammo): 
                                    $varianceValue = $ammo['variance_value'] ?? 0;
                                ?>
                                <tr class="<?php echo $varianceValue != 0 ? 'variance-row' : ''; ?>">
                                    <td><span class="asset-code"><?php echo isset($ammo['ammo_id']) ? htmlspecialchars($ammo['ammo_id']) : '-'; ?></span></td>
                                    <td><?php echo isset($ammo['ammo_type']) ? htmlspecialchars($ammo['ammo_type']) : '-'; ?></td>
                                    <td><?php echo isset($ammo['calibre']) ? htmlspecialchars($ammo['calibre']) : '-'; ?></td>
                                    <td class="text-right"><?php echo isset($ammo['system_units']) ? number_format($ammo['system_units']) : '0'; ?></td>
                                    <td class="text-right"><?php echo isset($ammo['physical_units']) ? number_format($ammo['physical_units']) : '0'; ?></td>
                                    <td class="<?php echo $varianceValue < 0 ? 'text-danger' : ($varianceValue > 0 ? 'text-warning' : ''); ?> text-right">
                                        <strong><?php echo $ammo['variance'] ?? '0'; ?></strong>
                                    </td>
                                    <td><?php echo $ammo['condition'] ?? '-'; ?></td>
                                    <td><?php echo $ammo['remarks'] ?? '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Missing Weapons -->
                <?php if (!empty($audit['missing'])): ?>
                <div class="audit-results">
                    <h3>Missing Weapons Report</h3>
                    <div class="table-responsive">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Arm Type</th>
                                    <th>Serial Number</th>
                                    <th>Last Known Location</th>
                                    <th>Date Missing</th>
                                    <th>Reported By</th>
                                    <th>Investigation Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($audit['missing'] as $item): ?>
                                <tr class="missing-row">
                                    <td><?php echo isset($item['arm_type']) ? htmlspecialchars($item['arm_type']) : '-'; ?></td>
                                    <td><code><?php echo isset($item['serial_number']) ? htmlspecialchars($item['serial_number']) : '-'; ?></code></td>
                                    <td><?php echo isset($item['last_known_location']) ? htmlspecialchars($item['last_known_location']) : 'N/A'; ?></td>
                                    <td><?php echo isset($item['date_missing']) ? date('d/m/Y', strtotime($item['date_missing'])) : 'N/A'; ?></td>
                                    <td><?php echo isset($item['reported_by']) ? htmlspecialchars($item['reported_by']) : 'N/A'; ?></td>
                                    <td>
                                        <?php 
                                        $invStatus = $item['investigation_status'] ?? 'Pending';
                                        $invClass = '';
                                        if ($invStatus == 'Reported') $invClass = 'badge-warning';
                                        elseif ($invStatus == 'Under Investigation') $invClass = 'badge-info';
                                        elseif ($invStatus == 'Board of Inquiry') $invClass = 'badge-danger';
                                        elseif ($invStatus == 'Closed') $invClass = 'badge-success';
                                        else $invClass = 'badge-secondary';
                                        ?>
                                        <span class="badge <?php echo $invClass; ?>">
                                            <?php echo $invStatus; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Audit Conclusion -->
                <?php if (!empty($audit['audit_conclusion'])): ?>
                <div class="audit-conclusion">
                    <h3>Audit Conclusion</h3>
                    <p><?php echo nl2br(htmlspecialchars($audit['audit_conclusion'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Signatures -->
                <div class="audit-signatures">
                    <div class="signature-line">
                        <span class="signature-label">Audit Officer:</span>
                        <span class="signature-name"><?php echo isset($audit['audit_officer']) ? htmlspecialchars($audit['audit_officer']) : '-'; ?></span>
                        <span class="signature-date"><?php echo isset($audit['audit_date']) ? date('d/m/Y', strtotime($audit['audit_date'])) : '-'; ?></span>
                    </div>
                    <?php if (!empty($audit['recommending_officer'])): ?>
                    <div class="signature-line">
                        <span class="signature-label">Recommending Officer:</span>
                        <span class="signature-name"><?php echo htmlspecialchars($audit['recommending_officer']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($audit['approving_officer'])): ?>
                    <div class="signature-line">
                        <span class="signature-label">Approving Officer:</span>
                        <span class="signature-name"><?php echo htmlspecialchars($audit['approving_officer']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($index < count($data) - 1): ?>
                <div class="page-break"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
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

/* Audit Header */
.audit-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--border);
}

.audit-header h2 {
    margin: 0 0 10px 0;
    color: var(--text-dark);
    font-size: 1.8rem;
    font-weight: 500;
    letter-spacing: -0.5px;
}

.audit-header h3 {
    margin: 0;
    color: var(--text-light);
    font-size: 1.2rem;
    font-weight: 400;
}

/* Audit Meta */
.audit-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
    padding: 20px;
    background: var(--bg-light);
    border-radius: 8px;
    border: 1px solid var(--border);
}

.meta-item {
    display: flex;
    align-items: center;
}

.meta-label {
    font-weight: 600;
    color: var(--text-light);
    width: 100px;
    font-size: 0.85rem;
}

.meta-value {
    color: var(--text-dark);
    font-weight: 500;
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.summary-card {
    color: white;
    padding: 15px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
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
    font-size: 2rem;
    opacity: 0.9;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    color: white;
}

.summary-card .card-content {
    flex: 1;
}

.summary-card .card-value {
    font-size: 1.8rem;
    font-weight: 400;
    line-height: 1.2;
    letter-spacing: -1px;
    color: white;
}

.summary-card .card-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
    color: white;
}

/* Audit Results */
.audit-results {
    margin-bottom: 35px;
}

.audit-results h3 {
    margin: 0 0 15px 0;
    font-size: 1.2rem;
    color: var(--text-dark);
    font-weight: 500;
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
    margin-top: 15px;
    border-radius: 6px;
    border: 1px solid var(--border);
}

.audit-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.audit-table th {
    background: var(--bg-light);
    padding: 12px 10px;
    text-align: left;
    font-weight: 600;
    color: var(--text-light);
    border-bottom: 2px solid var(--border);
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
}

.audit-table td {
    padding: 10px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.audit-table tbody tr:hover {
    background: var(--bg-light);
}

/* Variance Row */
.variance-row {
    background-color: #FFF3E0;
}

.variance-row:hover {
    background-color: #FFE0B2 !important;
}

.missing-row {
    background-color: #FFEBEE;
}

.missing-row:hover {
    background-color: #FFCDD2 !important;
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

.badge-secondary {
    background: #EEEEEE;
    color: #616161;
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

code {
    font-family: 'SF Mono', 'Fira Code', monospace;
    background: var(--bg-light);
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.8rem;
}

/* Text utilities */
.text-right {
    text-align: right;
}

.text-danger {
    color: var(--danger-dark) !important;
    font-weight: 600;
}

.text-warning {
    color: var(--warning-dark) !important;
    font-weight: 600;
}

/* Audit Conclusion */
.audit-conclusion {
    margin: 30px 0;
    padding: 20px;
    background: var(--bg-light);
    border-radius: 8px;
    border: 1px solid var(--border);
}

.audit-conclusion h3 {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
    color: var(--text-dark);
}

.audit-conclusion p {
    margin: 0;
    line-height: 1.6;
    color: var(--text-dark);
}

/* Signatures */
.audit-signatures {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 2px solid var(--border);
}

.signature-line {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.signature-label {
    font-weight: 600;
    color: var(--text-light);
    width: 150px;
    font-size: 0.9rem;
}

.signature-name {
    flex: 1;
    border-bottom: 1px solid var(--border);
    padding-bottom: 5px;
    margin-right: 20px;
    font-weight: 500;
}

.signature-date {
    color: var(--text-light);
    font-size: 0.85rem;
}

/* Page Break */
.page-break {
    page-break-after: always;
    margin: 30px 0;
    border-top: 2px dashed var(--border);
}

/* Empty State */
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
@media (max-width: 768px) {
    .report-content {
        padding: 20px;
    }
    
    .audit-meta {
        grid-template-columns: 1fr;
    }
    
    .meta-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .meta-label {
        width: auto;
        margin-bottom: 5px;
    }
    
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .signature-line {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .signature-name {
        width: 100%;
        margin-right: 0;
        margin: 5px 0;
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
    .audit-header h2 {
        font-size: 1.5rem;
    }
    
    .audit-header h3 {
        font-size: 1rem;
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
    
    .audit-table th {
        background: var(--bg-light) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .page-break {
        border: none;
        margin: 20px 0;
    }
}
</style>

<script>
function exportReport(format) {
    const quarter = document.querySelector('select[name="quarter"]').value;
    const year = document.querySelector('select[name="year"]').value;
    window.location.href = '<?php echo BASE_URL; ?>/reports/audit?quarter=' + quarter + '&year=' + year + '&format=' + format;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>