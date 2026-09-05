<?php
$page = isset($page) ? (int)$page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$totalCount = isset($totalCount) ? (int)$totalCount : count($vehicles ?? []);

$title = 'Vehicle Fleet Management';
$active = 'vehicles';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

// Ensure variables are defined with defaults - exactly like motorcycles code
$vehicles = isset($vehicles) ? $vehicles : [];
$statistics = isset($statistics) ? $statistics : [
    'total' => 0,
    'by_status' => [
        'Active' => 0,
        'In Repair' => 0,
        'Grounded' => 0,
        'Awaiting Disposal' => 0
    ],
    'service_due' => 0
];

// Get zones for filter dropdown (like motorcycles code)
$zones = isset($zones) ? $zones : [];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-car"></i>
                Vehicle Fleet Management
            </h1>
            <p>Manage all NIS vehicles</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('fleet.create')): ?>
            <a href="<?php echo BASE_URL; ?>/fleet/vehicles/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add New Vehicle
            </a>
            <?php endif; ?>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/fleet/export?type=vehicles" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-car"></i>
            </div>
            <div class="stat-details">
                <h4>Total Vehicles</h4>
                <p class="stat-number"><?php echo number_format($statistics['total'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon active">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Active</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_status']['Active'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon maintenance">
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-details">
                <h4>In Repair</h4>
                <p class="stat-number"><?php echo number_format($statistics['by_status']['In Repair'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon service-due">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-details">
                <h4>Service Due</h4>
                <p class="stat-number"><?php echo number_format($statistics['service_due'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Vehicles</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: block;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchVehicles" class="form-control" placeholder="Search by reg, make, chassis...">
                </div>
                <div class="filter-group">
                    <label>Vehicle Type</label>
                    <select id="filterType" class="form-control">
                        <option value="">All Types</option>
                        <option value="Saloon">Saloon</option>
                        <option value="SUV">SUV</option>
                        <option value="Bus">Bus</option>
                        <option value="Truck">Truck</option>
                        <option value="Ambulance">Ambulance</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="In Repair">In Repair</option>
                        <option value="Grounded">Grounded</option>
                        <option value="Awaiting Disposal">Awaiting Disposal</option>
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

    <!-- Vehicles Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Vehicle Register</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo number_format($totalCount); ?></span> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($vehicles)): ?>
                <div class="empty-state">
                    <i class="fas fa-car"></i>
                    <p>No vehicles found</p>
                    <?php if (Auth::can('fleet.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/fleet/vehicles/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Vehicle
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="vehiclesTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Asset Code</th>
                        <th data-sort="text">Registration</th>
                        <th data-sort="text">Make/Model</th>
                        <th data-sort="text">Year</th>
                        <th data-sort="text">Type</th>
                        <th data-sort="text">Status</th>
                        <th data-sort="text">Condition</th>
                        <th data-sort="text">Insurance</th>
                        <th data-sort="text">Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $index => $vehicle): ?>
                    <?php 
                    $vehicleType = isset($vehicle['vehicle_type']) ? $vehicle['vehicle_type'] : '';
                    $operationalStatus = isset($vehicle['operational_status']) ? $vehicle['operational_status'] : '';
                    $condition = isset($vehicle['condition']) ? $vehicle['condition'] : '';
                    ?>
                    <tr data-type="<?php echo strtolower($vehicleType ?? ''); ?>" 
                        data-status="<?php echo strtolower($operationalStatus ?? ''); ?>"
                        data-condition="<?php echo strtolower($condition ?? ''); ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo htmlspecialchars($vehicle['asset_code'] ?? ''); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($vehicle['registration_number'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['make_manufacturer'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['model_year'] ?? ''); ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo htmlspecialchars($vehicleType ?: 'N/A'); ?></span>
                        </td>
                        <td>
                            <?php 
                            $statusClass = '';
                            if ($operationalStatus == 'Active') $statusClass = 'status-active';
                            elseif ($operationalStatus == 'In Repair') $statusClass = 'status-warning';
                            elseif ($operationalStatus == 'Grounded') $statusClass = 'status-rejected';
                            else $statusClass = 'status-pending';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($operationalStatus ?: 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $conditionClass = '';
                            if ($condition == 'Excellent') $conditionClass = 'status-active';
                            elseif ($condition == 'Good') $conditionClass = 'status-good';
                            elseif ($condition == 'Fair') $conditionClass = 'status-warning';
                            elseif ($condition == 'Poor') $conditionClass = 'status-rejected';
                            else $conditionClass = 'status-pending';
                            ?>
                            <span class="status-badge <?php echo $conditionClass; ?>">
                                <?php echo htmlspecialchars($condition ?: 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $insuranceClass = 'status-active';
                            $insuranceText = 'Valid';
                            
                            if (!empty($vehicle['insurance_expiry'])) {
                                $daysToExpiry = round((strtotime($vehicle['insurance_expiry']) - time()) / (60 * 60 * 24));
                                if ($daysToExpiry < 0) {
                                    $insuranceClass = 'status-rejected';
                                    $insuranceText = 'Expired';
                                } elseif ($daysToExpiry <= 30) {
                                    $insuranceClass = 'status-warning';
                                    $insuranceText = 'Expiring Soon';
                                }
                            } else {
                                $insuranceClass = 'status-pending';
                                $insuranceText = 'Not Set';
                            }
                            ?>
                            <span class="status-badge <?php echo $insuranceClass; ?>">
                                <?php echo $insuranceText; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $docCount = (int)($vehicle['document_count'] ?? 0);
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
                                <a href="<?php echo BASE_URL; ?>/fleet/vehicles/show/<?php echo $vehicle['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::can('fleet.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/fleet/vehicles/edit/<?php echo $vehicle['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('fleet.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/fleet/vehicles/delete/<?php echo $vehicle['id'] ?? ''; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this vehicle?')">
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

.stat-icon.service-due {
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
    const searchTerm = document.getElementById('searchVehicles').value.trim();
    const filterType = document.getElementById('filterType').value;
    const filterStatus = document.getElementById('filterStatus').value;
    const filterCondition = document.getElementById('filterCondition').value;
    
    let url = window.location.pathname + '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (filterType) url += '&type=' + encodeURIComponent(filterType);
    if (filterStatus) url += '&status=' + encodeURIComponent(filterStatus);
    if (filterCondition) url += '&condition=' + encodeURIComponent(filterCondition);
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) document.getElementById('searchVehicles').value = urlParams.get('search');
    if (urlParams.has('type')) document.getElementById('filterType').value = urlParams.get('type');
    if (urlParams.has('status')) document.getElementById('filterStatus').value = urlParams.get('status');
    if (urlParams.has('condition')) document.getElementById('filterCondition').value = urlParams.get('condition');
    
    // Auto submit on dropdown changes
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterCondition').addEventListener('change', applyFilters);
    
    // Debounced search input
    let searchTimeout;
    document.getElementById('searchVehicles').addEventListener('input', function() {
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