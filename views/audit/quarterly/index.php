<?php
$title = 'Quarterly Audits';
$active = 'audit';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

// Ensure $audits is set and is an array (should come from controller)
$audits = isset($audits) && is_array($audits) ? $audits : [];

// Ensure $commands is set and is an array (should come from controller)
$commands = isset($commands) && is_array($commands) ? $commands : [];

// Initialize statistics
$auditStats = [
    'total' => count($audits),
    'draft' => 0,
    'submitted' => 0,
    'reviewed' => 0,
    'approved' => 0
];

// Calculate statistics from audits
foreach ($audits as $audit) {
    $status = strtolower($audit['status'] ?? '');
    if (isset($auditStats[$status])) {
        $auditStats[$status]++;
    }
}

// Generate CSRF token using Security class
$csrfToken = Security::csrfToken();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-clipboard-check"></i>
                Quarterly Audits
            </h1>
            <p>Manage quarterly weapons and ammunition audits</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('audit.create')): ?>
            <a href="<?php echo BASE_URL; ?>/audit/quarterly/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> New Quarterly Audit
            </a>
            <?php endif; ?>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/audit/export" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="stat-details">
                <h4>Total Audits</h4>
                <p class="stat-number"><?php echo number_format($auditStats['total']); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon draft">
                <i class="fas fa-pencil-alt"></i>
            </div>
            <div class="stat-details">
                <h4>Draft</h4>
                <p class="stat-number"><?php echo number_format($auditStats['draft']); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon submitted">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="stat-details">
                <h4>Submitted</h4>
                <p class="stat-number"><?php echo number_format($auditStats['submitted']); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon reviewed">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-details">
                <h4>Reviewed</h4>
                <p class="stat-number"><?php echo number_format($auditStats['reviewed']); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Approved</h4>
                <p class="stat-number"><?php echo number_format($auditStats['approved']); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Audits</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: block;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchAudits" class="form-control" placeholder="Search by audit #, officer...">
                </div>
                <div class="filter-group">
                    <label>Quarter</label>
                    <select id="filterQuarter" class="form-control">
                        <option value="">All Quarters</option>
                        <option value="Q1">Q1 (Jan-Mar)</option>
                        <option value="Q2">Q2 (Apr-Jun)</option>
                        <option value="Q3">Q3 (Jul-Sep)</option>
                        <option value="Q4">Q4 (Oct-Dec)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Year</label>
                    <select id="filterYear" class="form-control">
                        <option value="">All Years</option>
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        <option value="Draft">Draft</option>
                        <option value="Submitted">Submitted</option>
                        <option value="Reviewed">Reviewed</option>
                        <option value="Approved">Approved</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Command</label>
                    <select id="filterCommand" class="form-control">
                        <option value="">All Commands</option>
                        <?php if (!empty($commands)): ?>
                            <?php foreach ($commands as $cmd): ?>
                            <option value="<?php echo htmlspecialchars($cmd['id'] ?? ''); ?>">
                                <?php echo htmlspecialchars($cmd['command_name'] ?? ''); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <button class="btn btn-outline" onclick="resetFilters()">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Audits Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Quarterly Audits</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo count($audits); ?></span> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($audits)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <p>No audits found</p>
                    <?php if (Auth::can('audit.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/audit/quarterly/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First Audit
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="auditsTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Audit #</th>
                        <th data-sort="date">Audit Date</th>
                        <th data-sort="text">Quarter</th>
                        <th data-sort="text">Year</th>
                        <th data-sort="text">Audit Officer</th>
                        <th data-sort="text">Command</th>
                        <th data-sort="number">Weapons</th>
                        <th data-sort="number">Ammunition</th>
                        <th data-sort="text">Variances</th>
                        <th data-sort="text">Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audits as $index => $audit): ?>
                    <tr data-quarter="<?php echo htmlspecialchars($audit['quarter'] ?? ''); ?>" 
                        data-year="<?php echo htmlspecialchars($audit['year'] ?? ''); ?>"
                        data-status="<?php echo htmlspecialchars($audit['status'] ?? ''); ?>"
                        data-command="<?php echo htmlspecialchars($audit['command_id'] ?? ''); ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo htmlspecialchars($audit['audit_number'] ?? ''); ?></span>
                        </td>
                        <td><?php echo !empty($audit['audit_date']) ? date('d/m/Y', strtotime($audit['audit_date'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($audit['quarter'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($audit['year'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($audit['audit_officer'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($audit['command_name'] ?? 'N/A'); ?></td>
                        <td class="text-center"><?php echo number_format($audit['total_weapons_audited'] ?? 0); ?></td>
                        <td class="text-center"><?php echo number_format($audit['total_ammunition_audited'] ?? 0); ?></td>
                        <td>
                            <?php 
                            $varianceCount = ($audit['weapons_with_variance'] ?? 0) + ($audit['ammunition_with_variance'] ?? 0);
                            if ($varianceCount > 0):
                            ?>
                            <span class="variance-badge variance-warning">
                                <?php echo $varianceCount; ?> variance(s)
                            </span>
                            <?php else: ?>
                            <span class="variance-badge variance-clean">Clean</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $status = $audit['status'] ?? '';
                            $statusClass = '';
                            if ($status == 'Draft') $statusClass = 'status-draft';
                            elseif ($status == 'Submitted') $statusClass = 'status-pending';
                            elseif ($status == 'Reviewed') $statusClass = 'status-reviewed';
                            elseif ($status == 'Approved') $statusClass = 'status-approved';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo BASE_URL; ?>/audit/quarterly/show/<?php echo $audit['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (($audit['status'] ?? '') == 'Draft' && Auth::can('audit.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/audit/quarterly/edit/<?php echo $audit['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (($audit['status'] ?? '') == 'Submitted' && Auth::can('audit.approve')): ?>
                                <a href="#" class="btn-icon" title="Review" onclick="reviewAudit(<?php echo $audit['id'] ?? 0; ?>)">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" class="btn-icon success" title="Approve" onclick="approveAudit(<?php echo $audit['id'] ?? 0; ?>)">
                                    <i class="fas fa-check-circle"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (($audit['status'] ?? '') == 'Draft' && Auth::can('audit.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/audit/quarterly/delete/<?php echo $audit['id'] ?? ''; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this audit?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
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
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
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
    width: 45px;
    height: 45px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}

.stat-icon.total {
    background: #e3f2fd;
    color: #1976d2;
}

.stat-icon.draft {
    background: #e0e0e0;
    color: #616161;
}

.stat-icon.submitted {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.reviewed {
    background: #d1ecf1;
    color: #0c5460;
}

.stat-icon.approved {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-details h4 {
    margin: 0 0 3px 0;
    font-size: 0.8rem;
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

/* Page Header */
.page-header {
    background: var(--surface);
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-content h1 {
    margin-bottom: 5px;
    color: var(--text-primary);
}

.header-content p {
    color: var(--text-secondary);
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

/* Filter Section */
.filter-section {
    background: var(--surface);
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}

.filter-header {
    padding: 15px 20px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    color: white;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filter-header h3 {
    margin: 0;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-body {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 5px;
    font-weight: 600;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.9rem;
}

.filter-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Content Card */
.content-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.section-title h2 {
    margin: 0;
    font-size: 1.4rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.record-count {
    font-size: 0.9rem;
    color: var(--text-secondary);
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

/* Variance Badges */
.variance-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.variance-warning {
    background: #fff3cd;
    color: #856404;
}

.variance-clean {
    background: #d4edda;
    color: #155724;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-draft {
    background: #e0e0e0;
    color: #616161;
}

.status-pending {
    background: #fff3e0;
    color: #f57c00;
}

.status-reviewed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

/* Asset Code */
.asset-code {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: var(--primary-color);
    background: var(--light-bg);
    padding: 4px 8px;
    border-radius: 4px;
}

.text-center {
    text-align: center;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: var(--light-bg);
    color: var(--text-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-icon:hover {
    background: var(--success-color);
    color: white;
    transform: translateY(-2px);
}

.btn-icon.success:hover {
    background: var(--success-color);
}

.btn-icon.delete:hover {
    background: var(--danger-color);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-success {
    background: var(--success-color);
    color: white;
}

.btn-success:hover {
    background: var(--secondary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(32, 112, 39, 0.3);
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #1a252f;
}

.btn-outline {
    background: transparent;
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}

.btn-outline:hover {
    background: var(--light-bg);
    color: var(--text-primary);
    border-color: var(--success-color);
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
    margin: 0 0 15px 0;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.page-link {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    color: var(--text-primary);
    text-decoration: none;
    transition: all 0.3s;
    cursor: pointer;
    background: var(--surface);
}

.page-link:hover {
    background: var(--light-bg);
    border-color: var(--success-color);
}

.page-link:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-info {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .header-actions {
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .section-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<script>
let currentPage = 1;
const rowsPerPage = 10;
let filteredRows = [];

function toggleFilters() {
    const filterBody = document.getElementById('filterBody');
    const toggleIcon = document.getElementById('filterToggleIcon');
    
    if (filterBody.style.display === 'none') {
        filterBody.style.display = 'block';
        toggleIcon.className = 'fas fa-chevron-up';
    } else {
        filterBody.style.display = 'none';
        toggleIcon.className = 'fas fa-chevron-down';
    }
}

function applyFilters() {
    const searchTerm = document.getElementById('searchAudits').value.toLowerCase().trim();
    const quarter = document.getElementById('filterQuarter').value;
    const year = document.getElementById('filterYear').value;
    const status = document.getElementById('filterStatus').value;
    const command = document.getElementById('filterCommand').value;
    
    const rows = document.querySelectorAll('#auditsTable tbody tr');
    filteredRows = [];
    
    rows.forEach(row => {
        let show = true;
        
        // Search filter
        if (searchTerm !== '') {
            const text = row.textContent.toLowerCase();
            show = show && text.includes(searchTerm);
        }
        
        // Quarter filter
        if (quarter !== '' && show) {
            show = row.dataset.quarter === quarter;
        }
        
        // Year filter
        if (year !== '' && show) {
            show = row.dataset.year === year;
        }
        
        // Status filter
        if (status !== '' && show) {
            show = row.dataset.status === status;
        }
        
        // Command filter
        if (command !== '' && show) {
            show = row.dataset.command === command;
        }
        
        if (show) {
            filteredRows.push(row);
        }
    });
    
    currentPage = 1;
    updateDisplay();
}

function resetFilters() {
    document.getElementById('searchAudits').value = '';
    document.getElementById('filterQuarter').value = '';
    document.getElementById('filterYear').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterCommand').value = '';
    
    const rows = document.querySelectorAll('#auditsTable tbody tr');
    filteredRows = Array.from(rows);
    
    currentPage = 1;
    updateDisplay();
}

function updateDisplay() {
    const totalRecords = filteredRows.length;
    document.getElementById('recordCount').textContent = totalRecords;
    
    // Hide all rows first
    document.querySelectorAll('#auditsTable tbody tr').forEach(row => {
        row.style.display = 'none';
    });
    
    if (totalRecords === 0) {
        document.getElementById('pagination').innerHTML = '';
        return;
    }
    
    // Show only current page rows
    const start = (currentPage - 1) * rowsPerPage;
    const end = Math.min(start + rowsPerPage, totalRecords);
    
    for (let i = start; i < end; i++) {
        if (filteredRows[i]) {
            filteredRows[i].style.display = '';
        }
    }
    
    updatePagination(totalRecords);
}

function updatePagination(totalRecords) {
    const totalPages = Math.ceil(totalRecords / rowsPerPage);
    const pagination = document.getElementById('pagination');
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let html = `
        <button class="page-link" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i> Previous
        </button>
        <span class="page-info">Page ${currentPage} of ${totalPages}</span>
        <button class="page-link" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            Next <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    pagination.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    updateDisplay();
}

function reviewAudit(id) {
    window.location.href = '<?php echo BASE_URL; ?>/audit/quarterly/review/' + id;
}

function approveAudit(id) {
    if (confirm('Are you sure you want to approve this audit?')) {
        window.location.href = '<?php echo BASE_URL; ?>/audit/quarterly/approve/' + id;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('#auditsTable tbody tr');
    filteredRows = Array.from(rows);
    document.getElementById('recordCount').textContent = filteredRows.length;
    updateDisplay();
});

// Search with debounce
let searchTimeout;
document.getElementById('searchAudits').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        applyFilters();
    }, 300);
});

// Filter change events
document.getElementById('filterQuarter').addEventListener('change', function() {
    currentPage = 1;
    applyFilters();
});
document.getElementById('filterYear').addEventListener('change', function() {
    currentPage = 1;
    applyFilters();
});
document.getElementById('filterStatus').addEventListener('change', function() {
    currentPage = 1;
    applyFilters();
});
document.getElementById('filterCommand').addEventListener('change', function() {
    currentPage = 1;
    applyFilters();
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
