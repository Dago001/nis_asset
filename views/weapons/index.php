<?php
$page = isset($page) ? (int)$page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$totalCount = isset($totalCount) ? (int)$totalCount : count($weapons ?? []);

$title = 'Weapons Inventory';
$active = 'weapons';
$extra_css = [BASE_URL . '/assets/css/weapons.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// These variables should come from the controller
$weapons = isset($weapons) ? $weapons : [];
$statistics = isset($statistics) ? $statistics : [
    'total' => 0,
    'issued' => 0,
    'serviceable' => 0,
    'unserviceable' => 0
];

// Filter data should come from the controller
$weaponTypes = isset($weaponTypes) ? $weaponTypes : [];
$calibres = isset($calibres) ? $calibres : [];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-gun"></i>
                Weapons Inventory
            </h1>
            <p>Manage all weapons and firearms inventory</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('weapons.create')): ?>
            <a href="<?php echo BASE_URL; ?>/weapons/create" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add New Weapon
            </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/weapons/dashboard" class="btn btn-info">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/weapons/export" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-gun"></i>
            </div>
            <div class="stat-details">
                <h4>Total Weapons</h4>
                <p class="stat-number"><?php echo number_format($statistics['total'] ?? 0); ?></p>
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
            <div class="stat-icon serviceable">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Serviceable</h4>
                <p class="stat-number"><?php echo number_format($statistics['serviceable'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon unserviceable">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h4>Unserviceable</h4>
                <p class="stat-number"><?php echo number_format($statistics['unserviceable'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Weapons</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: block;">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchWeapons" class="form-control" placeholder="Search by ID, serial, model...">
                </div>
                <div class="filter-group">
                    <label>Weapon Type</label>
                    <select id="filterType" class="form-control">
                        <option value="">All Types</option>
                        <?php if (!empty($weaponTypes) && is_array($weaponTypes)): ?>
                            <?php foreach ($weaponTypes as $type): ?>
                                <option value="<?php echo $type['id'] ?? ''; ?>">
                                    <?php echo Security::escape($type['type_name'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Calibre</label>
                    <select id="filterCalibre" class="form-control">
                        <option value="">All Calibres</option>
                        <?php if (!empty($calibres) && is_array($calibres)): ?>
                            <?php foreach ($calibres as $calibre): ?>
                                <option value="<?php echo $calibre['id'] ?? ''; ?>">
                                    <?php echo Security::escape($calibre['calibre_name'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Location</label>
                    <select id="filterLocation" class="form-control">
                        <option value="">All Locations</option>
                        <option value="Armoury">Armoury</option>
                        <option value="Issued">Issued</option>
                        <option value="In Repair">In Repair</option>
                        <option value="Lost">Lost</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Condition</label>
                    <select id="filterCondition" class="form-control">
                        <option value="">All Conditions</option>
                        <option value="Serviceable">Serviceable</option>
                        <option value="Unserviceable">Unserviceable</option>
                        <option value="Under Repair">Under Repair</option>
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

    <!-- Weapons Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Weapons Register</h2>
            <div class="card-actions">
                <span class="record-count">Showing <span id="recordCount"><?php echo number_format($totalCount); ?></span> records</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($weapons)): ?>
                <div class="empty-state">
                    <i class="fas fa-gun"></i>
                    <p>No weapons found</p>
                    <?php if (Auth::can('weapons.create')): ?>
                    <a href="<?php echo BASE_URL; ?>/weapons/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Weapon
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="weaponsTable">
                <thead>
                    <tr>
                        <th data-sort="text">S/N</th>
                        <th data-sort="text">Weapon ID</th>
                        <th data-sort="text">Type</th>
                        <th data-sort="text">Make/Model</th>
                        <th data-sort="text">Serial Number</th>
                        <th data-sort="text">Calibre</th>
                        <th data-sort="text">Location</th>
                        <th data-sort="text">Custodian</th>
                        <th data-sort="text">Condition</th>
                        <th data-sort="text">Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weapons as $index => $weapon): ?>
                    <tr data-type="<?php echo $weapon['weapon_type_id'] ?? ''; ?>" 
                        data-calibre="<?php echo $weapon['calibre_id'] ?? ''; ?>"
                        data-location="<?php echo strtolower($weapon['current_location'] ?? ''); ?>"
                        data-condition="<?php echo strtolower($weapon['condition'] ?? ''); ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo Security::escape($weapon['weapon_id'] ?? ''); ?></span>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?php echo Security::escape($weapon['weapon_type_name'] ?? $weapon['weapon_type_other'] ?? 'Other'); ?></span>
                        </td>
                        <td><?php echo Security::escape($weapon['make_model'] ?? ''); ?></td>
                        <td>
                            <span class="serial-number"><?php echo Security::escape($weapon['serial_no'] ?? ''); ?></span>
                        </td>
                        <td><?php echo Security::escape($weapon['calibre_name'] ?? $weapon['calibre_other'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                            $location = $weapon['current_location'] ?? '';
                            $locationClass = '';
                            if ($location == 'Armoury') $locationClass = 'status-active';
                            elseif ($location == 'Issued') $locationClass = 'status-warning';
                            elseif ($location == 'In Repair') $locationClass = 'status-maintenance';
                            elseif ($location == 'Lost') $locationClass = 'status-rejected';
                            ?>
                            <span class="status-badge <?php echo $locationClass; ?>">
                                <?php echo Security::escape($location); ?>
                            </span>
                        </td>
                        <td><?php echo Security::escape($weapon['custodian'] ?? '-'); ?></td>
                        <td>
                            <?php 
                            $condition = $weapon['condition'] ?? '';
                            $conditionClass = '';
                            if ($condition == 'Serviceable') $conditionClass = 'status-active';
                            elseif ($condition == 'Unserviceable') $conditionClass = 'status-rejected';
                            elseif ($condition == 'Under Repair') $conditionClass = 'status-warning';
                            ?>
                            <span class="status-badge <?php echo $conditionClass; ?>">
                                <?php echo Security::escape($condition); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $docCount = (int)($weapon['document_count'] ?? 0);
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
                                <a href="<?php echo BASE_URL; ?>/weapons/show/<?php echo $weapon['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (Auth::can('weapons.edit')): ?>
                                <a href="<?php echo BASE_URL; ?>/weapons/edit/<?php echo $weapon['id'] ?? ''; ?>" 
                                   class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::can('weapons.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/weapons/delete/<?php echo $weapon['id'] ?? ''; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this weapon? This action cannot be undone.')">
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
    const searchTerm = document.getElementById('searchWeapons').value.trim();
    const filterType = document.getElementById('filterType').value;
    const filterCalibre = document.getElementById('filterCalibre').value;
    const filterLocation = document.getElementById('filterLocation').value;
    const filterCondition = document.getElementById('filterCondition').value;
    const filterCommandEl = document.getElementById('filterCommand');
    const filterCommand = filterCommandEl ? filterCommandEl.value : '';

    let url = window.location.pathname + '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (filterType) url += '&type=' + encodeURIComponent(filterType);
    if (filterCalibre) url += '&calibre=' + encodeURIComponent(filterCalibre);
    if (filterLocation) url += '&location=' + encodeURIComponent(filterLocation);
    if (filterCondition) url += '&condition=' + encodeURIComponent(filterCondition);
    if (filterCommand) url += '&command_id=' + encodeURIComponent(filterCommand);

    window.location.href = url;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) document.getElementById('searchWeapons').value = urlParams.get('search');
    if (urlParams.has('type')) document.getElementById('filterType').value = urlParams.get('type');
    if (urlParams.has('calibre')) document.getElementById('filterCalibre').value = urlParams.get('calibre');
    if (urlParams.has('location')) document.getElementById('filterLocation').value = urlParams.get('location');
    if (urlParams.has('condition')) document.getElementById('filterCondition').value = urlParams.get('condition');
    const filterCommandEl = document.getElementById('filterCommand');
    if (filterCommandEl && urlParams.has('command_id')) filterCommandEl.value = urlParams.get('command_id');

    // Auto submit on dropdown changes
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterCalibre').addEventListener('change', applyFilters);
    document.getElementById('filterLocation').addEventListener('change', applyFilters);
    document.getElementById('filterCondition').addEventListener('change', applyFilters);
    if (filterCommandEl) filterCommandEl.addEventListener('change', applyFilters);
    
    // Debounced search input
    let searchTimeout;
    document.getElementById('searchWeapons').addEventListener('input', function() {
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

