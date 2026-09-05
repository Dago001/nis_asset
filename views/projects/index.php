<?php
$page = isset($page) ? (int)$page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$totalCount = isset($totalCount) ? (int)$totalCount : count($projects ?? []);

$title = 'Ongoing Projects Management';
$active = 'projects';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// These variables should come from the controller
$projects = isset($projects) ? $projects : [];
$statistics = isset($statistics) ? $statistics : [
    'total' => 0,
    'by_status' => [],
    'total_value' => 0,
    'overdue' => 0
];

// Zones should be passed from the controller
$zones = isset($zones) ? $zones : [];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-hard-hat"></i>
                Ongoing Projects Management
            </h1>
            <p>Manage all construction and development projects</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('projects.create')): ?>
            <a href="<?php echo BASE_URL; ?>/projects/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add New Project
            </a>
            <?php endif; ?>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/projects/export" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-hard-hat"></i>
            </div>
            <div class="stat-details">
                <h4>Total Projects</h4>
                <p class="stat-number"><?php echo number_format($statistics['total'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon planning">
                <i class="fas fa-pencil-ruler"></i>
            </div>
            <div class="stat-details">
                <h4>Planning</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_status']['Planning']['count'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon progress">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="stat-details">
                <h4>In Progress</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_status']['In Progress']['count'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon completed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Completed</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_status']['Completed']['count'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon overdue">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h4>Overdue</h4>
                <p class="stat-number"><?php echo $statistics['overdue'] ?? 0; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon value">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-details">
                <h4>Total Value</h4>
                <p class="stat-number">₦<?php echo number_format($statistics['total_value'] ?? 0, 2); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Projects</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: block;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchProjects" class="form-control" placeholder="Search by title, code, contractor...">
                </div>
                <div class="filter-group">
                    <label>Project Type</label>
                    <select id="filterType" class="form-control">
                        <option value="">All Types</option>
                        <option value="Construction">Construction</option>
                        <option value="Renovation">Renovation</option>
                        <option value="Rehabilitation">Rehabilitation</option>
                        <option value="Expansion">Expansion</option>
                        <option value="Infrastructure">Infrastructure</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        <option value="Planning">Planning</option>
                        <option value="In Progress">In Progress</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Zone</label>
                    <select id="filterZone" class="form-control">
                        <option value="">All Zones</option>
                        <?php if (!empty($zones) && is_array($zones)): ?>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?php echo $zone['id'] ?? ''; ?>">
                                    <?php echo Security::escape($zone['zone_name'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Funding Source</label>
                    <select id="filterFunding" class="form-control">
                        <option value="">All Sources</option>
                        <option value="Capital Appropriation">Capital Appropriation</option>
                        <option value="Special Intervention">Special Intervention</option>
                        <option value="Donor">Donor</option>
                        <option value="IGR">IGR</option>
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

    <!-- Projects Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Projects Register</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo number_format($totalCount); ?></span> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($projects)): ?>
                <div class="empty-state">
                    <i class="fas fa-hard-hat"></i>
                    <p>No projects found</p>
                    <?php if (Auth::can('projects.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/projects/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Project
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="projectsTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Project Code</th>
                        <th data-sort="text">Project Title</th>
                        <th data-sort="text">Type</th>
                        <th data-sort="text">Contractor</th>
                        <th data-sort="text">Location</th>
                        <th data-sort="number">Contract Sum (₦)</th>
                        <th data-sort="text">Progress</th>
                        <th data-sort="text">Status</th>
                        <th data-sort="date">Completion</th>
                        <th data-sort="text">Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $index => $project): ?>
                    <?php 
                    $overdue = false;
                    if (!empty($project['expected_completion_date']) && strtotime($project['expected_completion_date']) < time() && !in_array($project['status'], ['Completed', 'Cancelled'])) {
                        $overdue = true;
                    }
                    ?>
                    <tr data-type="<?php echo strtolower($project['project_type'] ?? ''); ?>" 
                        data-status="<?php echo strtolower($project['status'] ?? ''); ?>"
                        data-zone="<?php echo $project['zone_id'] ?? ''; ?>"
                        data-funding="<?php echo strtolower($project['source_funding'] ?? ''); ?>"
                        class="<?php echo $overdue ? 'overdue-row' : ''; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo Security::escape($project['project_code'] ?? ''); ?></span>
                        </td>
                        <td><?php echo Security::escape($project['project_title'] ?? ''); ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo Security::escape($project['project_type'] ?? ''); ?></span>
                        </td>
                        <td><?php echo Security::escape($project['contractor'] ?? '-'); ?></td>
                        <td>
                            <?php 
                            $location = [];
                            if (!empty($project['lga_name'])) $location[] = $project['lga_name'];
                            if (!empty($project['state_name'])) $location[] = $project['state_name'];
                            echo Security::escape(implode(', ', $location));
                            ?>
                        </td>
                        <td class="text-right">₦<?php echo number_format($project['contract_sum'] ?? 0, 2); ?></td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?php echo $project['physical_progress'] ?? 0; ?>%;"></div>
                                <span class="progress-text"><?php echo $project['physical_progress'] ?? 0; ?>%</span>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $status = $project['status'] ?? '';
                            $statusClass = '';
                            if ($status == 'Planning') $statusClass = 'status-info';
                            elseif ($status == 'In Progress') $statusClass = 'status-warning';
                            elseif ($status == 'On Hold') $statusClass = 'status-secondary';
                            elseif ($status == 'Completed') $statusClass = 'status-success';
                            elseif ($status == 'Cancelled') $statusClass = 'status-danger';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo Security::escape($status); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($project['expected_completion_date'])): ?>
                                <?php echo date('d/m/Y', strtotime($project['expected_completion_date'])); ?>
                                <?php if ($overdue): ?>
                                    <br><small class="days-badge overdue">Overdue</small>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $docCount = (int)($project['document_count'] ?? 0);
                            ?>
                            <?php if ($docCount > 0): ?>
                                <span class="document-badge" title="<?php echo $docCount; ?> document(s)">
                                    <i class="fas fa-paperclip"></i> <?php echo $docCount; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo BASE_URL; ?>/projects/show/<?php echo $project['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::can('projects.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/projects/edit/<?php echo $project['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('projects.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/projects/delete/<?php echo $project['id'] ?? ''; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this project?')">
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

.stat-icon.planning {
    background: #e0f2f1;
    color: #00796b;
}

.stat-icon.progress {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.completed {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.overdue {
    background: #ffebee;
    color: #c62828;
}

.stat-icon.value {
    background: #d1c4e9;
    color: #512da8;
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

.filter-header i {
    transition: transform 0.3s ease;
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
    width: 100%;
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

/* Progress Bar */
.progress-container {
    width: 100px;
    height: 20px;
    background: var(--light-bg);
    border-radius: 10px;
    position: relative;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: var(--success-color);
    border-radius: 10px;
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-primary);
    text-shadow: 0 0 2px white;
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

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-info {
    background: #d1ecf1;
    color: #0c5460;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
}

.status-secondary {
    background: #e2e3e5;
    color: #383d41;
}

.status-success {
    background: #d4edda;
    color: #155724;
}

.status-danger {
    background: #f8d7da;
    color: #721c24;
}

/* Days badge */
.days-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
}

.days-badge.overdue {
    background: #f8d7da;
    color: #721c24;
}

.overdue-row {
    background-color: #fff3e0;
}

.text-right {
    text-align: right;
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
}

.page-link:hover {
    background: var(--light-bg);
    border-color: var(--success-color);
}

.page-link.active {
    background: var(--success-color);
    color: white;
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

.record-count {
    font-size: 0.9rem;
    color: var(--text-secondary);
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
    const searchTerm = document.getElementById('searchProjects').value.trim();
    const filterType = document.getElementById('filterType').value;
    const filterStatus = document.getElementById('filterStatus').value;
    const filterZone = document.getElementById('filterZone').value;
    const filterFunding = document.getElementById('filterFunding').value;
    
    let url = window.location.pathname + '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (filterType) url += '&type=' + encodeURIComponent(filterType);
    if (filterStatus) url += '&status=' + encodeURIComponent(filterStatus);
    if (filterZone) url += '&zone=' + encodeURIComponent(filterZone);
    if (filterFunding) url += '&funding=' + encodeURIComponent(filterFunding);
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) document.getElementById('searchProjects').value = urlParams.get('search');
    if (urlParams.has('type')) document.getElementById('filterType').value = urlParams.get('type');
    if (urlParams.has('status')) document.getElementById('filterStatus').value = urlParams.get('status');
    if (urlParams.has('zone')) document.getElementById('filterZone').value = urlParams.get('zone');
    if (urlParams.has('funding')) document.getElementById('filterFunding').value = urlParams.get('funding');
    
    // Auto submit on dropdown changes
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterZone').addEventListener('change', applyFilters);
    document.getElementById('filterFunding').addEventListener('change', applyFilters);
    
    // Debounced search input
    let searchTimeout;
    document.getElementById('searchProjects').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 600);
    });
    
    // Initialize filter body state
    const filterBody = document.getElementById('filterBody');
    if (filterBody) {
        filterBody.style.display = 'block';
    }
    const toggleIcon = document.getElementById('filterToggleIcon');
    if (toggleIcon) {
        toggleIcon.className = 'fas fa-chevron-up';
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

