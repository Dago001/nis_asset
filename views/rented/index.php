<?php
$page = isset($page) ? (int)$page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$totalCount = isset($totalCount) ? (int)$totalCount : count($properties ?? []);

$title = 'Rented Properties Management';
$active = 'rented';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Ensure variables are defined with proper fallbacks
$properties = isset($properties) ? $properties : [];
$statistics = isset($statistics) ? $statistics : [
    'total' => 0,
    'total_annual_rent' => 0,
    'expiring_soon' => 0,
    'expired' => 0,
    'by_status' => []
];

// Get expiring and expired properties from the passed data
$expiringSoon = [];
$expired = [];

foreach ($properties as $property) {
    if (!empty($property['expiry_date'])) {
        $today = date('Y-m-d');
        $expiryDate = $property['expiry_date'];
        
        if ($expiryDate < $today) {
            $expired[] = $property;
        } elseif ($expiryDate <= date('Y-m-d', strtotime('+30 days'))) {
            $expiringSoon[] = $property;
        }
    }
}

// Limit to 5 each for display
$expiringSoon = array_slice($expiringSoon, 0, 5);
$expired = array_slice($expired, 0, 5);
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-house-user"></i>
                Rented Properties Management
            </h1>
            <p>Manage all leased and rented properties</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('rented.create')): ?>
            <a href="<?php echo BASE_URL; ?>/rented/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add New Property
            </a>
            <?php endif; ?>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/rented/export" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards - Matching Dashboard Style -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="card-icon" style="background: #1F6F8B;">
                <i class="fas fa-building"></i>
            </div>
            <div class="card-content">
                <div class="card-value"><?php echo number_format($statistics['total'] ?? 0); ?></div>
                <div class="card-label">Total Properties</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="card-icon" style="background: #C69214;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="card-content">
                <div class="card-value"><?php echo number_format($statistics['expiring_soon'] ?? 0); ?></div>
                <div class="card-label">Expiring Soon</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="card-icon" style="background: #B42318;">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="card-content">
                <div class="card-value"><?php echo number_format($statistics['expired'] ?? 0); ?></div>
                <div class="card-label">Expired</div>
            </div>
        </div>

<div class="stat-card">
            <div class="card-icon" style="background: #207027;">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="card-content">
                <div class="card-value">₦<?php echo number_format($statistics['total_annual_rent'] ?? 0, 2); ?></div>
                <div class="card-label">Annual Rent</div>
            </div>
        </div>

    </div>

    

    <!-- Alerts for Expiring/Expired Leases -->
    <?php if (!empty($expiringSoon) || !empty($expired)): ?>
    <div class="alerts-container">
        <?php if (!empty($expiringSoon)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-clock"></i>
                <div class="alert-content">
                    <strong>Leases Expiring Soon:</strong>
                    <ul>
                        <?php foreach ($expiringSoon as $prop): ?>
                        <li>
                            <?php echo Security::escape($prop['property_address'] ?? ''); ?> - 
                            Expires <?php echo appDate($prop['expiry_date']); ?>
                            (<?php echo (int)((strtotime($prop['expiry_date']) - time()) / 86400); ?> days)
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($expired)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div class="alert-content">
                    <strong>Expired Leases:</strong>
                    <ul>
                        <?php foreach ($expired as $prop): ?>
                        <li>
                            <?php echo Security::escape($prop['property_address'] ?? ''); ?> - 
                            Expired <?php echo appDate($prop['expiry_date']); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Properties</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: none;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchProperties" placeholder="Search by address, lessor...">
                </div>
                <div class="filter-group">
                    <label>Purpose</label>
                    <select id="filterPurpose">
                        <option value="">All Purposes</option>
                        <option value="Office">Office</option>
                        <option value="Residential">Residential</option>
                        <option value="Warehouse">Warehouse</option>
                        <option value="Staff Quarters">Staff Quarters</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Expiring Soon">Expiring Soon</option>
                        <option value="Expired">Expired</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Zone</label>
                    <select id="filterZone">
                        <option value="">All Zones</option>
                        <?php
                        // This should come from controller, but for now we'll keep it
                        // In a real MVC app, this should also be passed from controller
                        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
                        if ($zones && is_array($zones)):
                            foreach ($zones as $zone):
                        ?>
                        <option value="<?php echo $zone['id']; ?>"><?php echo Security::escape($zone['zone_name']); ?></option>
                        <?php 
                            endforeach;
                        endif;
                        ?>
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

    <!-- Properties Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Rented Properties Register</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo number_format($totalCount); ?></span> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($properties)): ?>
                <div class="empty-state">
                    <i class="fas fa-house-user"></i>
                    <p>No rented properties found</p>
                    <?php if (Auth::can('rented.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/rented/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Property
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="propertiesTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Asset Code</th>
                        <th data-sort="text">Address</th>
                        <th data-sort="text">Lessor</th>
                        <th data-sort="text">Purpose</th>
                        <th data-sort="date">Start Date</th>
                        <th data-sort="date">Expiry Date</th>
                        <th data-sort="number">Annual Rent (₦)</th>
                        <th data-sort="text">Status</th>
                        <th data-sort="text">Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($properties as $index => $property): 
                        $today = time();
                        $expiry = !empty($property['expiry_date']) ? strtotime($property['expiry_date']) : null;
                        $daysRemaining = $expiry ? floor(($expiry - $today) / 86400) : null;
                        
                        $statusClass = 'status-active';
                        $statusText = 'Active';
                        
                        if ($expiry && $expiry < $today) {
                            $statusClass = 'status-rejected';
                            $statusText = 'Expired';
                        } elseif ($expiry && $daysRemaining <= 30 && $daysRemaining > 0) {
                            $statusClass = 'status-warning';
                            $statusText = 'Expiring Soon';
                        }
                    ?>
                    <tr data-purpose="<?php echo $property['purpose'] ?? ''; ?>" 
                        data-status="<?php echo $statusText; ?>"
                        data-zone="<?php echo $property['zone_id'] ?? ''; ?>"
                        data-expiry="<?php echo $property['expiry_date'] ?? ''; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo Security::escape($property['asset_code'] ?? ''); ?></span>
                        </td>
                        <td><?php echo Security::escape(substr($property['property_address'] ?? '', 0, 50)) . (strlen($property['property_address'] ?? '') > 50 ? '...' : ''); ?></td>
                        <td><?php echo Security::escape($property['owner_lessor_name'] ?? ''); ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo Security::escape($property['purpose'] ?? ''); ?></span>
                        </td>
                        <td><?php echo !empty($property['start_date']) ? appDate($property['start_date']) : '-'; ?></td>
                        <td>
                            <?php if (!empty($property['expiry_date'])): ?>
                                <span class="<?php echo $statusClass === 'status-warning' ? 'expiry-warning' : ($statusClass === 'status-rejected' ? 'expiry-danger' : ''); ?>">
                                    <?php echo appDate($property['expiry_date']); ?>
                                    <?php if ($daysRemaining && $daysRemaining <= 30 && $daysRemaining > 0): ?>
                                        <br><small class="expiry-warning"><?php echo $daysRemaining; ?> days left</small>
                                    <?php elseif ($expiry && $expiry < $today): ?>
                                        <br><small class="expiry-danger">Expired</small>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td class="text-right">₦<?php echo number_format($property['annual_rent'] ?? 0, 2); ?></td>
                        <td>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $docCount = (int)($property['document_count'] ?? 0);
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
                                <a href="<?php echo BASE_URL; ?>/rented/show/<?php echo $property['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::can('rented.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/rented/edit/<?php echo $property['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('rented.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/rented/delete/<?php echo $property['id'] ?? ''; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this property?')">
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
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: var(--white);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;

    min-width: 0;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.card-icon {
    width: 55px;
    height: 55px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.card-content {
    flex: 1;
}

.card-value {
    font-size: 1.8rem;
    font-weight: 400;
    color: var(--text-primary);
    line-height: 1.2;
    margin-bottom: 4px;
}

.card-label {
    color: var(--text-secondary);
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Alerts Container */
.alerts-container {
    margin-bottom: 25px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 10px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.alert-warning {
    background: #fff3cd;
    
    color: #856404;
}

.alert-danger {
    background: #f8d7da;
    
    color: #721c24;
}

.alert i {
    font-size: 1.5rem;
}

.alert ul {
    margin: 5px 0 0 0;
    padding-left: 20px;
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
    padding: 12px 15px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    color: white;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filter-header h3 {
    margin: 0;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-body {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 3px;
    font-weight: 600;
}

.filter-group input,
.filter-group select {
    padding: 6px 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 0.8rem;
}

.filter-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

/* Badge Styles */
.badge {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 500;
}

.badge-info {
    background: #e0f2f1;
    color: #00695c;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
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

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

/* Expiry Styles */
.expiry-warning {
    color: #C69214;
    font-weight: 600;
}

.expiry-danger {
    color: #B42318;
    font-weight: 600;
}

/* Document Badge */
.document-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 6px;
    background: #e8f5e8;
    color: var(--success-color);
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 500;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid var(--border-color);
}

.page-link {
    padding: 6px 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    color: var(--text-primary);
    text-decoration: none;
    transition: all 0.3s;
    font-size: 0.8rem;
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

.page-info {
    color: var(--text-secondary);
    font-size: 0.8rem;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
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
    const searchTerm = document.getElementById('searchProperties').value.trim();
    const filterPurpose = document.getElementById('filterPurpose').value;
    const filterStatus = document.getElementById('filterStatus').value;
    const filterZone = document.getElementById('filterZone').value;
    
    let url = window.location.pathname + '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (filterPurpose) url += '&purpose=' + encodeURIComponent(filterPurpose);
    if (filterStatus) url += '&status=' + encodeURIComponent(filterStatus);
    if (filterZone) url += '&zone=' + encodeURIComponent(filterZone);
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) document.getElementById('searchProperties').value = urlParams.get('search');
    if (urlParams.has('purpose')) document.getElementById('filterPurpose').value = urlParams.get('purpose');
    if (urlParams.has('status')) document.getElementById('filterStatus').value = urlParams.get('status');
    if (urlParams.has('zone')) document.getElementById('filterZone').value = urlParams.get('zone');
    
    // Auto submit on dropdown changes
    document.getElementById('filterPurpose').addEventListener('change', applyFilters);
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterZone').addEventListener('change', applyFilters);
    
    // Debounced search input
    let searchTimeout;
    document.getElementById('searchProperties').addEventListener('input', function() {
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
