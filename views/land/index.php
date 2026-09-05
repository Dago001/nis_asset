<?php
$page = isset($page) ? (int)$page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$totalCount = isset($totalCount) ? (int)$totalCount : count($assets ?? []);

$title = 'Land Assets Management';
$active = 'land';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Ensure variables are defined - these should come from the controller
$assets = isset($assets) ? $assets : [];
$statistics = isset($statistics) ? $statistics : [
    'total' => 0,
    'by_status' => [],
    'total_area' => 0
];

// Zones should be passed from the controller
$zones = isset($zones) ? $zones : [];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-map-marked-alt"></i>
                Land Assets Management
            </h1>
            <p>Manage all landed assets and properties</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('land.create')): ?>
            <a href="<?php echo BASE_URL; ?>/land/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add New Land Asset
            </a>
            <?php endif; ?>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/land/export" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <div class="stat-details">
                <h4>Total Land Assets</h4>
                <p class="stat-number"><?php echo number_format($statistics['total'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon developed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Developed</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_status']['Developed'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon undeveloped">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Undeveloped</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_status']['Undeveloped'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon area">
                <i class="fas fa-ruler-combined"></i>
            </div>
            <div class="stat-details">
                <h4>Total Area</h4>
                <p class="stat-number"><?php echo number_format($statistics['total_area'] ?? 0, 2); ?> m²</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Land Assets</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchLand" class="form-control" placeholder="Search by code, title, address...">
                </div>
                <div class="filter-group">
                    <label>Ownership Type</label>
                    <select id="filterOwnership" class="form-control">
                        <option value="">All Types</option>
                        <option value="FGN">FGN</option>
                        <option value="State">State</option>
                        <option value="Private">Private</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        <option value="Developed">Developed</option>
                        <option value="Undeveloped">Undeveloped</option>
                        <option value="Fenced">Fenced</option>
                        <option value="Not Fenced">Not Fenced</option>
                        <option value="Under Litigation">Under Litigation</option>
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

    <!-- Land Assets Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Land Assets Register</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo number_format($totalCount); ?></span> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($assets)): ?>
                <div class="empty-state">
                    <i class="fas fa-map-marked-alt"></i>
                    <p>No land assets found</p>
                    <?php if (Auth::can('land.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/land/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Land Asset
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="landTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Asset Code</th>
                        <th data-sort="text">Ownership</th>
                        <th data-sort="text">Title Holder</th>
                        <th data-sort="text">Location</th>
                        <th data-sort="number">Size</th>
                        <th data-sort="text">Purpose</th>
                        <th data-sort="text">Status</th>
                        <th data-sort="text">Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $index => $asset): ?>
                    <tr data-ownership="<?php echo $asset['ownership_type'] ?? ''; ?>" 
                        data-status="<?php echo strtolower($asset['status'] ?? ''); ?>"
                        data-zone="<?php echo $asset['zone_id'] ?? ''; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo Security::escape($asset['asset_code'] ?? ''); ?></span>
                        </td>
                        <td>
                            <?php 
                            $ownership = $asset['ownership_type'] ?? '';
                            $badgeClass = 'badge-primary';
                            if ($ownership == 'FGN') $badgeClass = 'badge-success';
                            elseif ($ownership == 'State') $badgeClass = 'badge-info';
                            elseif ($ownership == 'Private') $badgeClass = 'badge-warning';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo Security::escape($ownership); ?>
                            </span>
                        </td>
                        <td><?php echo Security::escape($asset['title_holder'] ?? ''); ?></td>
                        <td>
                            <?php 
                            $location = [];
                            if (!empty($asset['lga_name'])) $location[] = $asset['lga_name'];
                            if (!empty($asset['state_name'])) $location[] = $asset['state_name'];
                            echo Security::escape(implode(', ', $location));
                            ?>
                        </td>
                        <td>
                            <?php 
                            if (!empty($asset['size'])) {
                                echo number_format($asset['size'], 2) . ' ' . Security::escape($asset['size_unit'] ?? '');
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?php echo Security::escape($asset['purpose_use'] ?? '-'); ?></td>
                        <td>
                            <?php 
                            $status = $asset['status'] ?? '';
                            $statusClass = '';
                            if ($status == 'Developed') $statusClass = 'status-active';
                            elseif ($status == 'Undeveloped') $statusClass = 'status-pending';
                            elseif ($status == 'Under Litigation') $statusClass = 'status-rejected';
                            else $statusClass = 'status-warning';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo Security::escape($status ?: 'Not Set'); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            // Document count should be passed from controller
                            $docCount = $asset['document_count'] ?? 0;
                            ?>
                            <span class="document-badge" title="<?php echo $docCount; ?> document(s)">
                                <i class="fas fa-paperclip"></i> <?php echo $docCount; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo BASE_URL; ?>/land/show/<?php echo $asset['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::can('land.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/land/edit/<?php echo $asset['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('land.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/land/delete/<?php echo $asset['id'] ?? ''; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this land asset?')">
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
    display: block;
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

/* Stats Grid */
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

.stat-icon.developed {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.undeveloped {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.area {
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

/* Badge Styles */
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-success {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-primary {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-info {
    background: #e0f2f1;
    color: #00695c;
}

.badge-warning {
    background: #fff3e0;
    color: #e65100;
}

.record-count {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

/* Document Badge */
.document-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: #e8f5e8;
    color: var(--success-color);
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
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

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
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
    const searchTerm = document.getElementById('searchLand').value.trim();
    const filterOwnership = document.getElementById('filterOwnership').value;
    const filterStatus = document.getElementById('filterStatus').value;
    const filterZone = document.getElementById('filterZone').value;
    
    let url = window.location.pathname + '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (filterOwnership) url += '&ownership=' + encodeURIComponent(filterOwnership);
    if (filterStatus) url += '&status=' + encodeURIComponent(filterStatus);
    if (filterZone) url += '&zone=' + encodeURIComponent(filterZone);
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) document.getElementById('searchLand').value = urlParams.get('search');
    if (urlParams.has('ownership')) document.getElementById('filterOwnership').value = urlParams.get('ownership');
    if (urlParams.has('status')) document.getElementById('filterStatus').value = urlParams.get('status');
    if (urlParams.has('zone')) document.getElementById('filterZone').value = urlParams.get('zone');
    
    // Auto submit on dropdown changes
    document.getElementById('filterOwnership').addEventListener('change', applyFilters);
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterZone').addEventListener('change', applyFilters);
    
    // Debounced search input
    let searchTimeout;
    document.getElementById('searchLand').addEventListener('input', function() {
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

