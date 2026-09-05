<?php
$page = isset($page) ? (int)$page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$totalCount = isset($totalCount) ? (int)$totalCount : count($assets ?? []);

$title = 'Movable Assets Management';
$active = 'movable';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// These variables should come from the controller
$assets = isset($assets) ? $assets : [];
$statistics = isset($statistics) ? $statistics : [
    'total' => 0,
    'by_condition' => [],
    'total_value' => 0,
    'by_type' => []
];

// Zones should be passed from the controller
$zones = isset($zones) ? $zones : [];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-tools"></i>
                Movable Assets Management
            </h1>
            <p>Manage all movable assets including equipment, furniture, and machinery</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('movable.create')): ?>
            <a href="<?php echo BASE_URL; ?>/movable/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add New Movable Asset
            </a>
            <?php endif; ?>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/movable/export" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-details">
                <h4>Total Assets</h4>
                <p class="stat-number"><?php echo number_format($statistics['total'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon in-use">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Excellent</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_condition']['Excellent'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stored">
                <i class="fas fa-warehouse"></i>
            </div>
            <div class="stat-details">
                <h4>Good</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_condition']['Good'] ?? 0); ?></p>
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
            <h3><i class="fas fa-filter"></i> Filter Movable Assets</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: block;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchAssets" class="form-control" placeholder="Search by name, code, serial...">
                </div>
                <div class="filter-group">
                    <label>Asset Type</label>
                    <select id="filterType" class="form-control">
                        <option value="">All Types</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Office Equipment">Office Equipment</option>
                        <option value="Machinery">Machinery</option>
                        <option value="Tools">Tools</option>
                        <option value="Appliances">Appliances</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Condition</label>
                    <select id="filterCondition" class="form-control">
                        <option value="">All Conditions</option>
                        <option value="Excellent">Excellent</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Poor">Poor</option>
                        <option value="Under Maintenance">Under Maintenance</option>
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

    <!-- Assets Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Movable Assets Register</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo number_format($totalCount); ?></span> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($assets)): ?>
                <div class="empty-state">
                    <i class="fas fa-tools"></i>
                    <p>No movable assets found</p>
                    <?php if (Auth::can('movable.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/movable/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Asset
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="movableTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Asset Code</th>
                        <th data-sort="text">Asset Type</th>
                        <th data-sort="text">Make/Model</th>
                        <th data-sort="text">Serial Number</th>
                        <th data-sort="text">Location</th>
                        <th data-sort="text">Custodian</th>
                        <th data-sort="text">Condition</th>
                        <th data-sort="number">Value (₦)</th>
                        <th data-sort="text">Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $index => $asset): ?>
                    <tr data-type="<?php echo strtolower($asset['asset_type'] ?? ''); ?>" 
                        data-condition="<?php echo strtolower($asset['condition_status'] ?? ''); ?>"
                        data-zone="<?php echo $asset['zone_id'] ?? ''; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo Security::escape($asset['asset_code'] ?? ''); ?></span>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo Security::escape($asset['asset_type'] ?? ''); ?></span>
                        </td>
                        <td><?php echo Security::escape($asset['make_model'] ?? ''); ?></td>
                        <td>
                            <?php if (!empty($asset['serial_number'])): ?>
                                <span class="serial-number"><?php echo Security::escape($asset['serial_number']); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $location = [];
                            if (!empty($asset['lga_name'])) $location[] = $asset['lga_name'];
                            if (!empty($asset['state_name'])) $location[] = $asset['state_name'];
                            echo Security::escape(implode(', ', $location));
                            ?>
                        </td>
                        <td><?php echo Security::escape($asset['custodian_name'] ?? '-'); ?></td>
                        <td>
                            <?php 
                            $condition = $asset['condition_status'] ?? '';
                            $conditionClass = '';
                            if ($condition == 'Excellent') $conditionClass = 'status-active';
                            elseif ($condition == 'Good') $conditionClass = 'status-good';
                            elseif ($condition == 'Fair') $conditionClass = 'status-warning';
                            elseif ($condition == 'Poor') $conditionClass = 'status-rejected';
                            elseif ($condition == 'Under Maintenance') $conditionClass = 'status-maintenance';
                            ?>
                            <span class="status-badge <?php echo $conditionClass; ?>">
                                <?php echo Security::escape($condition ?: 'Not Set'); ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <?php 
                            if (!empty($asset['purchase_value'])) {
                                echo '₦' . number_format($asset['purchase_value'], 2);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $docCount = $asset['document_count'] ?? 0;
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
                                <a href="<?php echo BASE_URL; ?>/movable/show/<?php echo $asset['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::can('movable.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/movable/edit/<?php echo $asset['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('movable.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/movable/delete/<?php echo $asset['id'] ?? ''; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this asset?')">
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
}

.stat-icon.total {
    background: #e3f2fd;
    color: #1976d2;
}

.stat-icon.in-use {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.stored {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.value {
    background: #e0f2f1;
    color: #00796b;
}

.stat-details h4 {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
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

.serial-number {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    background: var(--light-bg);
    padding: 2px 6px;
    border-radius: 4px;
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

.status-good {
    background: #d1ecf1;
    color: #0c5460;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.status-maintenance {
    background: #fff3cd;
    color: #856404;
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

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
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
    
    .filter-actions .btn {
        width: 100%;
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
    const searchTerm = document.getElementById('searchAssets').value.trim();
    const filterType = document.getElementById('filterType').value;
    const filterCondition = document.getElementById('filterCondition').value;
    const filterZone = document.getElementById('filterZone').value;
    
    let url = window.location.pathname + '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (filterType) url += '&type=' + encodeURIComponent(filterType);
    if (filterCondition) url += '&condition=' + encodeURIComponent(filterCondition);
    if (filterZone) url += '&zone=' + encodeURIComponent(filterZone);
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) document.getElementById('searchAssets').value = urlParams.get('search');
    if (urlParams.has('type')) document.getElementById('filterType').value = urlParams.get('type');
    if (urlParams.has('condition')) document.getElementById('filterCondition').value = urlParams.get('condition');
    if (urlParams.has('zone')) document.getElementById('filterZone').value = urlParams.get('zone');
    
    // Auto submit on dropdown changes
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterCondition').addEventListener('change', applyFilters);
    document.getElementById('filterZone').addEventListener('change', applyFilters);
    
    // Debounced search input
    let searchTimeout;
    document.getElementById('searchAssets').addEventListener('input', function() {
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

