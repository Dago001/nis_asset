<?php
$page = isset($page) ? (int) $page : (isset($_GET['page']) ? (int) $_GET['page'] : 1);
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$totalCount = isset($totalCount) ? (int) $totalCount : count($ammunition ?? []);

$title = 'Ammunition Inventory';
$active = 'ammunition';
$extra_css = [BASE_URL . '/assets/css/ammunition.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// These variables MUST come from the controller
$ammunition = isset($ammunition) ? $ammunition : [];
$statistics = isset($statistics) ? $statistics : [
    'total_types' => 0,
    'total_rounds' => 0,
    'expiring_soon' => 0,
    'low_stock' => 0
];

// Filter options should come from the controller
$ammoTypes = isset($ammoTypes) ? $ammoTypes : [];
$calibres = isset($calibres) ? $calibres : [];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-bullseye"></i>
                Ammunition Inventory
            </h1>
            <p>Manage all ammunition stock and inventory</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('ammunition.create')): ?>
            <a href="<?php echo BASE_URL; ?>/ammunition/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add New Ammunition
            </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/ammunition/dashboard" class="btn btn-info">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/ammunition/export" class="btn btn-outline">
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
                <h4>Total Types</h4>
                <p class="stat-number"><?php echo number_format($statistics['total_types'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rounds">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stat-details">
                <h4>Total Rounds</h4>
                <p class="stat-number"><?php echo number_format($statistics['total_rounds'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon expiring">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h4>Expiring Soon</h4>
                <p class="stat-number"><?php echo number_format($statistics['expiring_soon'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon low">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h4>Low Stock</h4>
                <p class="stat-number"><?php echo number_format($statistics['low_stock'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Ammunition</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: block;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchAmmo" class="form-control" placeholder="Search by ID or batch number...">
                </div>
                <div class="filter-group">
                    <label>Ammunition Type</label>
                    <select id="filterType" class="form-control">
                        <option value="">All Types</option>
                        <?php if (!empty($ammoTypes)): ?>
                            <?php foreach ($ammoTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['ammo_type'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Calibre</label>
                    <select id="filterCalibre" class="form-control">
                        <option value="">All Calibres</option>
                        <?php if (!empty($calibres)): ?>
                            <?php foreach ($calibres as $calibre): ?>
                            <option value="<?php echo $calibre['id']; ?>"><?php echo htmlspecialchars($calibre['calibre'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Storage Location</label>
                    <select id="filterLocation" class="form-control">
                        <option value="">All Locations</option>
                        <option value="Main Armoury">Main Armoury</option>
                        <option value="HQ Armoury">HQ Armoury</option>
                        <option value="Zonal Armoury">Zonal Armoury</option>
                        <option value="Command Armoury">Command Armoury</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Condition</label>
                    <select id="filterCondition" class="form-control">
                        <option value="">All Conditions</option>
                        <option value="Serviceable">Serviceable</option>
                        <option value="Unserviceable">Unserviceable</option>
                        <option value="Condemned">Condemned</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Stock Level</label>
                    <select id="filterStock" class="form-control">
                        <option value="">All</option>
                        <option value="low">Low Stock (&lt; 100)</option>
                        <option value="adequate">Adequate (100-499)</option>
                        <option value="overstock">Overstock (500+)</option>
                    </select>
                </div>
                <?php if (!empty($commands)): ?>
                <div class="filter-group">
                    <label>Command / Formation</label>
                    <select id="filterCommand" class="form-control">
                        <option value="">All Commands / Formations</option>
                        <?php foreach ($commands as $cmd): ?>
                            <option value="<?php echo $cmd['id']; ?>" <?php echo ((int) ($selectedCommandId ?? 0) === (int) $cmd['id']) ? 'selected' : ''; ?>>
                                <?php echo Security::escape($cmd['command_name']); ?><?php echo $cmd['command_type'] ? ' (' . Security::escape($cmd['command_type']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
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

    <!-- Ammunition Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Ammunition Register</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo number_format($totalCount); ?></span> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($ammunition)): ?>
                <div class="empty-state">
                    <i class="fas fa-bullseye"></i>
                    <p>No ammunition found</p>
                    <?php if (Auth::can('ammunition.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/ammunition/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Ammunition
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="ammoTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Ammo ID</th>
                        <th data-sort="text">Type</th>
                        <th data-sort="text">Calibre</th>
                        <th data-sort="text">Batch Number</th>
                        <th data-sort="number">Received</th>
                        <th data-sort="number">Issued</th>
                        <th data-sort="number">Balance</th>
                        <th data-sort="text">Storage</th>
                        <th data-sort="date">Expiry</th>
                        <th data-sort="text">Condition</th>
                        <th data-sort="text">Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ammunition as $index => $ammo): 
                        // Calculate balance
                        $received = isset($ammo['quantity_received']) ? (int)$ammo['quantity_received'] : 0;
                        $issued = isset($ammo['quantity_issued']) ? (int)$ammo['quantity_issued'] : 0;
                        $balance = $received - $issued;
                        
                        // Calculate expiry
                        $expiryDate = !empty($ammo['expiry_date']) ? strtotime($ammo['expiry_date']) : null;
                        $today = time();
                        $daysToExpiry = $expiryDate ? round(($expiryDate - $today) / (60 * 60 * 24)) : null;
                        
                        // Row classes for styling
                        $rowClass = '';
                        if ($balance < 100) {
                            $rowClass = 'low-stock-row';
                        } elseif ($daysToExpiry && $daysToExpiry <= 90 && $daysToExpiry > 0) {
                            $rowClass = 'expiring-row';
                        }
                    ?>
                    <tr class="<?php echo $rowClass; ?>" 
                        data-type="<?php echo $ammo['ammo_type_id'] ?? ''; ?>"
                        data-calibre="<?php echo $ammo['calibre_id'] ?? ''; ?>"
                        data-location="<?php echo strtolower($ammo['storage_location'] ?? ''); ?>"
                        data-condition="<?php echo strtolower($ammo['condition'] ?? ''); ?>"
                        data-balance="<?php echo $balance; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo htmlspecialchars($ammo['ammo_id'] ?? ''); ?></span>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($ammo['ammo_type'] ?? 'Other'); ?></span>
                            <?php if (!empty($ammo['ammo_type_other'])): ?>
                                <small>(<?php echo htmlspecialchars($ammo['ammo_type_other']); ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($ammo['calibre'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($ammo['batch_number'] ?? '-'); ?></td>
                        <td class="text-right"><?php echo number_format($received); ?></td>
                        <td class="text-right"><?php echo number_format($issued); ?></td>
                        <td class="text-right <?php echo $balance < 100 ? 'text-danger font-weight-bold' : ''; ?>">
                            <?php echo number_format($balance); ?>
                        </td>
                        <td><?php echo htmlspecialchars($ammo['storage_location'] ?? ''); ?></td>
                        <td>
                            <?php if (!empty($ammo['expiry_date'])): ?>
                                <span class="<?php echo $daysToExpiry && $daysToExpiry <= 90 && $daysToExpiry > 0 ? 'expiry-warning' : ''; ?>">
                                    <?php echo date('d/m/Y', strtotime($ammo['expiry_date'])); ?>
                                </span>
                                <?php if ($daysToExpiry && $daysToExpiry <= 90): ?>
                                    <br><small class="days-remaining <?php echo $daysToExpiry < 0 ? 'expired' : 'warning'; ?>">
                                        <?php echo $daysToExpiry < 0 ? 'Expired' : $daysToExpiry . ' days'; ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $condition = $ammo['condition'] ?? '';
                            $conditionClass = '';
                            if ($condition == 'Serviceable') $conditionClass = 'status-active';
                            elseif ($condition == 'Unserviceable') $conditionClass = 'status-warning';
                            elseif ($condition == 'Condemned') $conditionClass = 'status-rejected';
                            ?>
                            <span class="status-badge <?php echo $conditionClass; ?>">
                                <?php echo htmlspecialchars($condition); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $docCount = (int)($ammo['document_count'] ?? 0);
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
                                <a href="<?php echo BASE_URL; ?>/ammunition/show/<?php echo $ammo['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::can('ammunition.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/ammunition/edit/<?php echo $ammo['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('ammunition.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/ammunition/delete/<?php echo $ammo['id'] ?? ''; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this ammunition record?')">
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
        <div class="pagination" id="pagination">
            <?php if ($totalPages > 1): ?>
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

<script>
// Filtering/pagination happens server-side (see AmmunitionController::index()).
// This just builds the query string and reloads the page — mirrors the
// pattern used on /land, /weapons, etc.

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
    const searchTerm = document.getElementById('searchAmmo').value.trim();
    const type = document.getElementById('filterType').value;
    const calibre = document.getElementById('filterCalibre').value;
    const location = document.getElementById('filterLocation').value;
    const condition = document.getElementById('filterCondition').value;
    const stock = document.getElementById('filterStock').value;
    const filterCommandEl = document.getElementById('filterCommand');
    const command = filterCommandEl ? filterCommandEl.value : '';

    let url = window.location.pathname + '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (type) url += '&ammo_type_id=' + encodeURIComponent(type);
    if (calibre) url += '&calibre=' + encodeURIComponent(calibre);
    if (location) url += '&storage_location=' + encodeURIComponent(location);
    if (condition) url += '&condition=' + encodeURIComponent(condition);
    if (stock) url += '&stock=' + encodeURIComponent(stock);
    if (command) url += '&command_id=' + encodeURIComponent(command);

    window.location.href = url;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

document.addEventListener('DOMContentLoaded', function() {
    // Restore filter values from the URL so they stay visible after a reload.
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) document.getElementById('searchAmmo').value = urlParams.get('search');
    if (urlParams.has('ammo_type_id')) document.getElementById('filterType').value = urlParams.get('ammo_type_id');
    if (urlParams.has('calibre')) document.getElementById('filterCalibre').value = urlParams.get('calibre');
    if (urlParams.has('storage_location')) document.getElementById('filterLocation').value = urlParams.get('storage_location');
    if (urlParams.has('condition')) document.getElementById('filterCondition').value = urlParams.get('condition');
    if (urlParams.has('stock')) document.getElementById('filterStock').value = urlParams.get('stock');
    const filterCommandEl = document.getElementById('filterCommand');
    if (filterCommandEl && urlParams.has('command_id')) filterCommandEl.value = urlParams.get('command_id');

    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterCalibre').addEventListener('change', applyFilters);
    document.getElementById('filterLocation').addEventListener('change', applyFilters);
    document.getElementById('filterCondition').addEventListener('change', applyFilters);
    document.getElementById('filterStock').addEventListener('change', applyFilters);
    if (filterCommandEl) filterCommandEl.addEventListener('change', applyFilters);

    let searchTimeout;
    document.getElementById('searchAmmo').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 600);
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
