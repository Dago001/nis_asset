<?php
$title = 'Returns Management';
$active = 'returns';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Ensure variables are set
$returns = isset($returns) && is_array($returns) ? $returns : [];
$statistics = isset($statistics) && is_array($statistics) ? $statistics : [
    'total' => 0,
    'pending' => 0,
    'processed' => 0,
    'verified' => 0,
    'completed' => 0,
    'total_weapons_returned' => 0,
    'total_ammunition_returned' => 0
];

// Pagination (see ReturnsController::index())
$page = isset($page) ? (int) $page : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$totalCount = isset($totalCount) ? (int) $totalCount : count($returns);

// Restore filter values from the query string
$filterSearch = $_GET['search'] ?? '';
$filterStatusVal = $_GET['status'] ?? '';
$filterTypeVal = $_GET['type'] ?? '';
$filterDateFromVal = $_GET['date_from'] ?? '';
$filterDateToVal = $_GET['date_to'] ?? '';

// Generate CSRF token using Security class
$csrfToken = Security::csrfToken();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-undo-alt"></i>
                Returns Management
            </h1>
            <p>Manage weapon and ammunition returns</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('returns.create')): ?>
            <a href="<?php echo BASE_URL; ?>/returns/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> New Return
            </a>
            <?php endif; ?>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/returns/export" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-undo-alt"></i>
            </div>
            <div class="stat-details">
                <h4>Total Returns</h4>
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
            <div class="stat-icon processed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Processed</h4>
                <p class="stat-number"><?php echo number_format($statistics['processed'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon weapons">
                <i class="fas fa-gun"></i>
            </div>
            <div class="stat-details">
                <h4>Weapons Returned</h4>
                <p class="stat-number"><?php echo number_format($statistics['total_weapons_returned'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ammo">
                <i class="fas fa-bullseye"></i>
            </div>
            <div class="stat-details">
                <h4>Ammunition Returned</h4>
                <p class="stat-number"><?php echo number_format($statistics['total_ammunition_returned'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Returns</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: block;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchReturns" class="form-control" placeholder="Search by return #, officer..." value="<?php echo htmlspecialchars($filterSearch); ?>">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo $filterStatusVal === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processed" <?php echo $filterStatusVal === 'Processed' ? 'selected' : ''; ?>>Processed</option>
                        <option value="Verified" <?php echo $filterStatusVal === 'Verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="Completed" <?php echo $filterStatusVal === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Return Type</label>
                    <select id="filterType" class="form-control">
                        <option value="">All Types</option>
                        <option value="Weapon" <?php echo $filterTypeVal === 'Weapon' ? 'selected' : ''; ?>>Weapon</option>
                        <option value="Ammunition" <?php echo $filterTypeVal === 'Ammunition' ? 'selected' : ''; ?>>Ammunition</option>
                        <option value="Both" <?php echo $filterTypeVal === 'Both' ? 'selected' : ''; ?>>Both</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" id="filterDateFrom" class="form-control" value="<?php echo htmlspecialchars($filterDateFromVal); ?>">
                </div>
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" id="filterDateTo" class="form-control" value="<?php echo htmlspecialchars($filterDateToVal); ?>">
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

    <!-- Returns Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Returns Register</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo count($returns); ?></span> of <?php echo number_format($totalCount); ?> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($returns)): ?>
                <div class="empty-state">
                    <i class="fas fa-undo-alt"></i>
                    <p>No returns found</p>
                    <?php if (Auth::can('returns.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/returns/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First Return
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="returnsTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Return #</th>
                        <th data-sort="date">Return Date</th>
                        <th data-sort="text">Returning Officer</th>
                        <th data-sort="text">Rank</th>
                        <th data-sort="text">Unit</th>
                        <th data-sort="text">Type</th>
                        <th data-sort="text">Requisition #</th>
                        <th data-sort="text">Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returns as $index => $return): ?>
                    <tr data-status="<?php echo htmlspecialchars($return['status'] ?? ''); ?>" 
                        data-type="<?php echo htmlspecialchars($return['return_type'] ?? ''); ?>"
                        data-date="<?php echo htmlspecialchars($return['return_date'] ?? ''); ?>">
                        <td><?php echo (($page - 1) * 50) + $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo htmlspecialchars($return['return_number'] ?? ''); ?></span>
                        </td>
                        <td><?php echo !empty($return['return_date']) ? date('d/m/Y', strtotime($return['return_date'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($return['returning_officer_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($return['returning_rank'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($return['returning_unit'] ?? ''); ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo htmlspecialchars($return['return_type'] ?? ''); ?></span>
                        </td>
                        <td>
                            <?php if (!empty($return['requisition_number'])): ?>
                                <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $return['requisition_id'] ?? ''; ?>">
                                    <?php echo htmlspecialchars($return['requisition_number']); ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $status = $return['status'] ?? '';
                            $statusClass = '';
                            if ($status == 'Pending') $statusClass = 'status-pending';
                            elseif ($status == 'Processed') $statusClass = 'status-processed';
                            elseif ($status == 'Verified') $statusClass = 'status-verified';
                            elseif ($status == 'Completed') $statusClass = 'status-completed';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo BASE_URL; ?>/returns/show/<?php echo $return['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (($return['status'] ?? '') == 'Pending' && Auth::can('returns.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/returns/edit/<?php echo $return['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (($return['status'] ?? '') == 'Pending' && Auth::can('returns.process')): ?>
                                <a href="#" class="btn-icon success" title="Process Return" onclick="processReturn(<?php echo $return['id'] ?? 0; ?>)">
                                    <i class="fas fa-check-double"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (($return['status'] ?? '') == 'Pending' && Auth::can('returns.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/returns/delete/<?php echo $return['id'] ?? ''; ?>"
                                   class="btn-icon danger" title="Delete"
                                   onclick="return confirm('Delete this return? Any weapons/ammunition it recorded as returned will go back to Issued status. This cannot be undone.')">
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
        <?php
        $paramsForLinks = $_GET;
        unset($paramsForLinks['page']);
        $queryString = http_build_query($paramsForLinks);
        $queryPrefix = $queryString ? ($queryString . '&') : '';
        ?>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <a href="?<?php echo $queryPrefix; ?>page=<?php echo max(1, $page - 1); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
            <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> &middot; <?php echo number_format($totalCount); ?> total</span>
            <a href="?<?php echo $queryPrefix; ?>page=<?php echo min($totalPages, $page + 1); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>
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

.stat-icon.pending {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.processed {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.weapons {
    background: #ffebee;
    color: #c62828;
}

.stat-icon.ammo {
    background: #e0f2f1;
    color: #00796b;
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

/* Badge Styles */
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

/* Asset Code */
.asset-code {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: var(--primary-color);
    background: var(--light-bg);
    padding: 4px 8px;
    border-radius: 4px;
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

.status-processed {
    background: #e8f5e9;
    color: #388e3c;
}

.status-verified {
    background: #d1ecf1;
    color: #0c5460;
}

.status-completed {
    background: #d4edda;
    color: #155724;
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

.btn-icon.danger:hover {
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
    const params = new URLSearchParams();
    const search = document.getElementById('searchReturns').value.trim();
    const status = document.getElementById('filterStatus').value;
    const type = document.getElementById('filterType').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    if (search !== '') params.set('search', search);
    if (status !== '') params.set('status', status);
    if (type !== '') params.set('type', type);
    if (dateFrom !== '') params.set('date_from', dateFrom);
    if (dateTo !== '') params.set('date_to', dateTo);

    window.location.href = '<?php echo BASE_URL; ?>/returns?' + params.toString();
}

function resetFilters() {
    window.location.href = '<?php echo BASE_URL; ?>/returns';
}

function processReturn(id) {
    if (confirm('Are you sure you want to process this return? This will update inventory balances.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/returns/process/' + id;
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

// Search with debounce (reloads the page with the search query applied server-side)
let searchTimeout;
document.getElementById('searchReturns').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Filter change events
document.getElementById('filterStatus').addEventListener('change', applyFilters);
document.getElementById('filterType').addEventListener('change', applyFilters);
document.getElementById('filterDateFrom').addEventListener('change', applyFilters);
document.getElementById('filterDateTo').addEventListener('change', applyFilters);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
