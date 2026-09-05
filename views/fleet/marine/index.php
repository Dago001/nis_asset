<?php
$page = isset($page) ? (int)$page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$totalCount = isset($totalCount) ? (int)$totalCount : count($marine ?? []);

$title = 'Marine Assets Management';
$active = 'marine';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

// Ensure variables are defined with defaults - exactly like vehicles code
$marine = isset($marine) ? $marine : [];
$statistics = isset($statistics) ? $statistics : [
    'total' => 0,
    'by_status' => [
        'Operational' => 0,
        'Maintenance' => 0,
        'Docked' => 0,
        'Decommissioned' => 0
    ],
    'docking_due' => 0
];

// Get zones for filter dropdown (like vehicles code)
$zones = isset($zones) ? $zones : [];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-ship"></i>
                Marine Assets Management
            </h1>
            <p>Manage all NIS marine vessels and boats</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('fleet.create')): ?>
            <a href="<?php echo BASE_URL; ?>/fleet/marine/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add New Marine Asset
            </a>
            <?php endif; ?>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/fleet/export?type=marine" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-ship"></i>
            </div>
            <div class="stat-details">
                <h4>Total Vessels</h4>
                <p class="stat-number"><?php echo number_format($statistics['total'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon active">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Operational</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_status']['Operational'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon maintenance">
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-details">
                <h4>In Maintenance</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_status']['Maintenance'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon docking">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-details">
                <h4>Dry Docking Due</h4>
                <p class="stat-number"><?php echo number_format($statistics['docking_due'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Marine Assets</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: block;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchMarine" class="form-control" placeholder="Search by vessel name, registration...">
                </div>
                <div class="filter-group">
                    <label>Boat Type</label>
                    <select id="filterType" class="form-control">
                        <option value="">All Types</option>
                        <option value="Patrol Boat">Patrol Boat</option>
                        <option value="Speed Boat">Speed Boat</option>
                        <option value="Ferry">Ferry</option>
                        <option value="Rigid Hull">Rigid Hull</option>
                        <option value="Rubber Boat">Rubber Boat</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        <option value="Operational">Operational</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Docked">Docked</option>
                        <option value="Decommissioned">Decommissioned</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Zone</label>
                    <select id="filterZone" class="form-control">
                        <option value="">All Zones</option>
                        <?php if (!empty($zones)): ?>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?php echo $zone['id']; ?>"><?php echo htmlspecialchars($zone['zone_name']); ?></option>
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

    <!-- Marine Assets Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Marine Assets Register</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo number_format($totalCount); ?></span> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($marine)): ?>
                <div class="empty-state">
                    <i class="fas fa-ship"></i>
                    <p>No marine assets found</p>
                    <?php if (Auth::can('fleet.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/fleet/marine/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Marine Asset
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="marineTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Asset Code</th>
                        <th data-sort="text">Vessel Name</th>
                        <th data-sort="text">Type</th>
                        <th data-sort="text">Registration</th>
                        <th data-sort="text">Hull ID</th>
                        <th data-sort="text">Engine Type</th>
                        <th data-sort="text">Status</th>
                        <th data-sort="text">Docking Location</th>
                        <th data-sort="text">Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($marine as $index => $m): ?>
                    <?php 
                    $vesselName = isset($m['vessel_name']) ? $m['vessel_name'] : '';
                    $boatType = isset($m['boat_type']) ? $m['boat_type'] : '';
                    $operationalStatus = isset($m['operational_status']) ? $m['operational_status'] : '';
                    $zoneId = isset($m['zone_id']) ? $m['zone_id'] : '';
                    ?>
                    <tr data-type="<?php echo strtolower($boatType ?? ''); ?>" 
                        data-status="<?php echo strtolower($operationalStatus ?? ''); ?>"
                        data-zone="<?php echo $zoneId ?? ''; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo htmlspecialchars($m['asset_code'] ?? ''); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($vesselName ?: 'N/A'); ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo htmlspecialchars($boatType ?: 'N/A'); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($m['registration_number'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($m['hull_identification'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($m['engine_type'] ?? ''); ?></td>
                        <td>
                            <?php 
                            $statusClass = '';
                            if ($operationalStatus == 'Operational') $statusClass = 'status-active';
                            elseif ($operationalStatus == 'Maintenance') $statusClass = 'status-warning';
                            elseif ($operationalStatus == 'Docked') $statusClass = 'status-info';
                            elseif ($operationalStatus == 'Decommissioned') $statusClass = 'status-rejected';
                            else $statusClass = 'status-pending';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($operationalStatus ?: 'N/A'); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($m['docking_location'] ?? $m['command_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                            $docCount = (int)($m['document_count'] ?? 0);
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
                                <a href="<?php echo BASE_URL; ?>/fleet/marine/show/<?php echo $m['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::can('fleet.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/fleet/marine/edit/<?php echo $m['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('fleet.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/fleet/marine/delete/<?php echo $m['id'] ?? ''; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this marine asset?')">
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
:root {
    --primary-color: #134617;
    --primary-light: #207027;
    --success-color: #207027;
    --text-secondary: #53665E;
    --text-primary: #212529;
    --border-color: #D7E3DC;
    --light-bg: #F7FAF8;
}
[data-theme="dark"] {
    --primary-color: #299631;
    --primary-light: #37bf43;
    --success-color: #37bf43;
    --text-secondary: #dfe2e1;
    --text-primary: #d8e9d9;
    --border-color: #2f3832;
    --light-bg: #1a231d;
}


.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
}

.header-content h1 {
    margin: 0;
    font-size: 1.8rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-content p {
    margin: 5px 0 0;
    color: var(--text-secondary);
}

.header-actions {
    display: flex;
    gap: 10px;
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
    min-width: 0;
    overflow: hidden;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-icon.total {
    background: #e3f2fd;
    color: #1976d2;
}

.stat-icon.active {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.maintenance {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.docking {
    background: #e0f2f1;
    color: #00796b;
}

.stat-details {
    flex: 1 1 0%;
    min-width: 0;
    overflow: hidden;
}

.stat-details h4 {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 0.85rem;
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

/* Badge Styles */
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-info {
    background: #e0f2f1;
    color: #00695c;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
}

.status-info {
    background: #d1ecf1;
    color: #0c5460;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
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

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.3s;
}

.btn-icon:hover {
    background: #f0f0f0;
    color: var(--success-color);
}

.btn-icon.delete:hover {
    background: #ffebee;
    color: #B42318;
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
        grid-template-columns: 1fr;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .action-buttons {
        justify-content: center;
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
    const searchTerm = document.getElementById('searchMarine').value.trim();
    const filterType = document.getElementById('filterType').value;
    const filterStatus = document.getElementById('filterStatus').value;
    const filterZone = document.getElementById('filterZone').value;
    
    let url = window.location.pathname + '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (filterType) url += '&type=' + encodeURIComponent(filterType);
    if (filterStatus) url += '&status=' + encodeURIComponent(filterStatus);
    if (filterZone) url += '&zone=' + encodeURIComponent(filterZone);
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) document.getElementById('searchMarine').value = urlParams.get('search');
    if (urlParams.has('type')) document.getElementById('filterType').value = urlParams.get('type');
    if (urlParams.has('status')) document.getElementById('filterStatus').value = urlParams.get('status');
    if (urlParams.has('zone')) document.getElementById('filterZone').value = urlParams.get('zone');
    
    // Auto submit on dropdown changes
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterZone').addEventListener('change', applyFilters);
    
    // Debounced search input
    let searchTimeout;
    document.getElementById('searchMarine').addEventListener('input', function() {
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

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>