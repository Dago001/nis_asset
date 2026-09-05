<?php
$title = 'Requisitions Management';
$active = 'requisitions';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// These variables MUST come from the controller
$requisitions = isset($requisitions) ? $requisitions : [];
$statistics = isset($statistics) ? $statistics : [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'issued' => 0,
    'partially_issued' => 0,
    'completed' => 0
];

$page = $pagination['page'] ?? 1;
$totalPages = $pagination['totalPages'] ?? 1;
$totalCount = $pagination['totalCount'] ?? 0;

// Generate CSRF token using Security class
$csrfToken = Security::csrfToken();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-file-alt"></i>
                Requisitions Management
            </h1>
            <p>Manage weapon and ammunition requisitions</p>
        </div>
        <div class="header-actions">
            <?php 
            $userRoles = $_SESSION['roles'] ?? [];
            $isAdminOrSuper = in_array('admin', $userRoles, true) || in_array('Super Admin Officer', $userRoles, true) || Auth::isSuperAdmin();
            ?>
            <?php if (Auth::can('requisition.create') || $isAdminOrSuper): ?>
            <a href="<?php echo BASE_URL; ?>/requisition/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> New Requisition
            </a>
            <?php endif; ?>
            <?php if (Auth::can('requisition.approve') || $isAdminOrSuper): ?>
            <a href="<?php echo BASE_URL; ?>/requisition/pending" class="btn btn-warning">
                <i class="fas fa-clock"></i> Pending Approvals
            </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/requisition/my" class="btn btn-info">
                <i class="fas fa-user"></i> My Requisitions
            </a>
            <?php if (Auth::can('reports.export') || $isAdminOrSuper): ?>
            <a href="<?php echo BASE_URL; ?>/requisition/export" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-details">
                <h4>Total Requisitions</h4>
                <p class="stat-number"><?php echo number_format($statistics['total'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h4>Pending</h4>
                <p class="stat-number"><?php echo number_format($statistics['pending'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Approved</h4>
                <p class="stat-number"><?php echo number_format($statistics['approved'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon issued">
                <i class="fas fa-hand-holding"></i>
            </div>
            <div class="stat-details">
                <h4>Issued</h4>
                <p class="stat-number"><?php echo number_format($statistics['issued'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon completed">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-details">
                <h4>Completed</h4>
                <p class="stat-number"><?php echo number_format($statistics['completed'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rejected">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Rejected</h4>
                <p class="stat-number"><?php echo number_format($statistics['rejected'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Requisitions</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: block;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchReqs" class="form-control" placeholder="Search by number, officer...">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Issued">Issued</option>
                        <option value="Partially Issued">Partially Issued</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Priority</label>
                    <select id="filterPriority" class="form-control">
                        <option value="">All Priorities</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" id="filterDateFrom" class="form-control">
                </div>
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" id="filterDateTo" class="form-control">
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

    <!-- Requisitions Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Requisitions List</h2>
            <div class="card-actions">
                <span class="record-count">Showing page <?php echo $page; ?> of <?php echo $totalPages; ?> (Total: <span id="recordCount"><?php echo $totalCount; ?></span> records)</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($requisitions)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>No requisitions found</p>
                    <?php if (Auth::can('requisition.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/requisition/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First Requisition
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="requisitionsTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Requisition #</th>
                        <th data-sort="date">Date</th>
                        <th data-sort="text">Requesting Officer</th>
                        <th data-sort="text">Rank</th>
                        <th data-sort="text">Command</th>
                        <th data-sort="text">Type</th>
                        <th data-sort="text">Priority</th>
                        <th data-sort="number">Items</th>
                        <th data-sort="text">Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requisitions as $index => $req): ?>
                    <tr data-status="<?php echo htmlspecialchars($req['status'] ?? ''); ?>" 
                        data-priority="<?php echo htmlspecialchars($req['priority_level'] ?? ''); ?>"
                        data-date="<?php echo htmlspecialchars($req['requisition_date'] ?? ''); ?>">
                        <td><?php echo ($page - 1) * 50 + $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo htmlspecialchars($req['requisition_number'] ?? ''); ?></span>
                        </td>
                        <td><?php echo !empty($req['requisition_date']) ? date('d/m/Y', strtotime($req['requisition_date'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($req['requesting_officer_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($req['requesting_rank'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($req['command_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                            $rType = !empty($req['requisition_type']) ? $req['requisition_type'] : 'Both';
                            $typeLabel = $rType;
                            if ($rType === 'Both') $typeLabel = 'Weapons & Ammo';
                            elseif ($rType === 'All') $typeLabel = 'All Types';
                            
                            $typeClass = 'badge-type-both';
                            if ($rType === 'Weapon') $typeClass = 'badge-type-weapon';
                            elseif ($rType === 'Ammunition') $typeClass = 'badge-type-ammo';
                            elseif ($rType === 'Non-Lethal') $typeClass = 'badge-type-nonlethal';
                            ?>
                            <span class="badge <?php echo $typeClass; ?>"><?php echo htmlspecialchars($typeLabel); ?></span>
                        </td>
                        <td>
                            <?php 
                            $priorityClass = '';
                            $priority = $req['priority_level'] ?? '';
                            if ($priority == 'Urgent') $priorityClass = 'priority-urgent';
                            elseif ($priority == 'High') $priorityClass = 'priority-high';
                            elseif ($priority == 'Medium') $priorityClass = 'priority-medium';
                            elseif ($priority == 'Low') $priorityClass = 'priority-low';
                            ?>
                            <span class="priority-badge <?php echo $priorityClass; ?>">
                                <?php echo htmlspecialchars($priority); ?>
                            </span>
                        </td>
                        <td class="text-center"><?php echo $req['item_count'] ?? 0; ?></td>
                        <td>
                            <?php 
                            $status = $req['status'] ?? '';
                            $statusClass = '';
                            if ($status == 'Pending') $statusClass = 'status-pending';
                            elseif ($status == 'Approved') $statusClass = 'status-approved';
                            elseif ($status == 'Rejected') $statusClass = 'status-rejected';
                            elseif ($status == 'Issued') $statusClass = 'status-issued';
                            elseif ($status == 'Partially Issued') $statusClass = 'status-partial';
                            elseif ($status == 'Completed') $statusClass = 'status-completed';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $req['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (($req['status'] ?? '') == 'Pending' && Auth::can('requisition.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/requisition/edit/<?php echo $req['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (($req['status'] ?? '') == 'Pending' && Auth::can('requisition.approve')): ?>
                                <a href="#" class="btn-icon" title="Approve" onclick="approveRequisition(<?php echo $req['id'] ?? 0; ?>)">
                                    <i class="fas fa-check-circle" style="color: #207027;"></i>
                                </a>
                                <a href="#" class="btn-icon" title="Reject" onclick="rejectRequisition(<?php echo $req['id'] ?? 0; ?>)">
                                    <i class="fas fa-times-circle" style="color: #B42318;"></i>
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
        <div class="pagination" id="pagination">
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <?php
                $queryParams = $_GET;
                unset($queryParams['page']);
                $queryString = http_build_query($queryParams);
                $queryString = $queryString ? '&' . $queryString : '';
                ?>
                <a href="?page=<?php echo max(1, $page - 1); ?><?php echo $queryString; ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <a href="?page=<?php echo min($totalPages, $page + 1); ?><?php echo $queryString; ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
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
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
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

    min-width: 0;
    overflow: hidden;
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

.stat-icon.pending {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.approved {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.issued {
    background: #e0f2f1;
    color: #00796b;
}

.stat-icon.completed {
    background: #d1c4e9;
    color: #512da8;
}

.stat-icon.rejected {
    background: #ffebee;
    color: #c62828;
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
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

/* Priority Badges */
.priority-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.priority-urgent {
    background: #ffebee;
    color: #c62828;
}

.priority-high {
    background: #fff3e0;
    color: #ef6c00;
}

.priority-medium {
    background: #e8f5e9;
    color: #2e7d32;
}

.priority-low {
    background: #e3f2fd;
    color: #1565c0;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-pending {
    background: #fff3e0;
    color: #f57c00;
}

.status-approved {
    background: #e8f5e9;
    color: #388e3c;
}

.status-rejected {
    background: #ffebee;
    color: #c62828;
}

.status-issued {
    background: #e0f2f1;
    color: #00796b;
}

.status-partial {
    background: #fff3e0;
    color: #ef6c00;
}

.status-completed {
    background: #d1c4e9;
    color: #512da8;
}

/* Badge */
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-info {
    background: #e0f2f1;
    color: #00695c;
}

.badge-type-weapon {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #BBF7D0;
}

.badge-type-ammo {
    background: #FEF3C7;
    color: #B45309;
    border: 1px solid #FDE68A;
}

.badge-type-both {
    background: #E0E7FF;
    color: #4338CA;
    border: 1px solid #C7D2FE;
}

.badge-type-nonlethal {
    background: #F3E8FF;
    color: #7E22CE;
    border: 1px solid #E9D5FF;
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

.btn-icon:hover i {
    color: white !important;
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

.btn-warning {
    background: var(--warning-color);
    color: white;
}

.btn-warning:hover {
    background: #B7791F;
    transform: translateY(-2px);
}

.btn-info {
    background: var(--info-color);
    color: white;
}

.btn-info:hover {
    background: #155E75;
    transform: translateY(-2px);
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

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #1a252f;
}

.text-center {
    text-align: center;
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
    const searchTerm = document.getElementById('searchReqs').value.trim();
    const status = document.getElementById('filterStatus').value;
    const priority = document.getElementById('filterPriority').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    
    let url = window.location.pathname + '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (status) url += '&filterStatus=' + encodeURIComponent(status);
    if (priority) url += '&filterPriority=' + encodeURIComponent(priority);
    if (dateFrom) url += '&filterDateFrom=' + encodeURIComponent(dateFrom);
    if (dateTo) url += '&filterDateTo=' + encodeURIComponent(dateTo);
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

function approveRequisition(id) {
    if (confirm('Are you sure you want to approve this requisition?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/requisition/approve/' + id;
        form.style.display = 'none';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = '<?php echo $csrfToken; ?>';
        form.appendChild(csrf);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectRequisition(id) {
    const reason = prompt('Please enter reason for rejection:');
    if (reason && reason.trim() !== '') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/requisition/reject/' + id;
        form.style.display = 'none';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = '<?php echo $csrfToken; ?>';
        form.appendChild(csrf);
        
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'rejection_reason';
        reasonInput.value = reason.trim();
        form.appendChild(reasonInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Pre-fill filters and hook events on load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) document.getElementById('searchReqs').value = urlParams.get('search');
    if (urlParams.has('filterStatus')) document.getElementById('filterStatus').value = urlParams.get('filterStatus');
    if (urlParams.has('filterPriority')) document.getElementById('filterPriority').value = urlParams.get('filterPriority');
    if (urlParams.has('filterDateFrom')) document.getElementById('filterDateFrom').value = urlParams.get('filterDateFrom');
    if (urlParams.has('filterDateTo')) document.getElementById('filterDateTo').value = urlParams.get('filterDateTo');
    
    // Auto-apply filters when selections change
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterPriority').addEventListener('change', applyFilters);
    document.getElementById('filterDateFrom').addEventListener('change', applyFilters);
    document.getElementById('filterDateTo').addEventListener('change', applyFilters);
    
    // Debounced search input
    let searchTimeout;
    document.getElementById('searchReqs').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 600);
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
