<?php
$title = 'Issue History';
$active = 'weapon_issue';
$extra_css = [BASE_URL . '/assets/css/weapon_issue.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$type = isset($type) ? $type : 'all';
$weaponIssues = isset($weaponIssues) ? $weaponIssues : [];
$ammoIssues = isset($ammoIssues) ? $ammoIssues : [];
$page = isset($page) ? $page : 1;
$totalWeaponIssues = isset($totalWeaponIssues) ? $totalWeaponIssues : 0;
$totalAmmoIssues = isset($totalAmmoIssues) ? $totalAmmoIssues : 0;
$limit = isset($limit) ? $limit : 50;
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-history"></i>
                Issue History
            </h1>
            <p>Complete history of weapons and ammunition issues</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/weapon_issue" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> New Issue
            </a>
            <a href="<?php echo BASE_URL; ?>/weapons" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Weapons
            </a>
        </div>
    </div>

    <!-- Type Filter -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter History</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Type</label>
                    <select id="filterType" onchange="changeType(this.value)">
                        <option value="all" <?php echo $type == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="weapons" <?php echo $type == 'weapons' ? 'selected' : ''; ?>>Weapons Only</option>
                        <option value="ammunition" <?php echo $type == 'ammunition' ? 'selected' : ''; ?>>Ammunition Only</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus">
                        <option value="">All Status</option>
                        <option value="Issued">Issued</option>
                        <option value="Returned">Returned</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" id="filterDateFrom">
                </div>
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" id="filterDateTo">
                </div>
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchHistory" placeholder="Search...">
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

    <?php if ($type === 'all' || $type === 'weapons'): ?>
    <!-- Weapons History -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-gun"></i> Weapons Issue History</h2>
            <span class="record-count">Total: <?php echo number_format($totalWeaponIssues); ?></span>
        </div>

        <div class="table-responsive">
            <?php if (empty($weaponIssues)): ?>
                <div class="empty-state">
                    <i class="fas fa-gun"></i>
                    <p>No weapon issues found</p>
                </div>
            <?php else: ?>
            <table class="asset-table" id="weaponHistoryTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Weapon ID</th>
                        <th>Make/Model</th>
                        <th>Officer</th>
                        <th>Rank</th>
                        <th>Unit</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Return Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weaponIssues as $issue): ?>
                    <tr data-status="<?php echo $issue['status']; ?>" data-date="<?php echo $issue['issue_date']; ?>">
                        <td><?php echo date('d/m/Y', strtotime($issue['issue_date'])); ?></td>
                        <td><?php echo Security::escape($issue['weapon_id']); ?></td>
                        <td><?php echo Security::escape($issue['make_model']); ?></td>
                        <td><?php echo Security::escape($issue['officer_name']); ?></td>
                        <td><?php echo Security::escape($issue['officer_rank']); ?></td>
                        <td><?php echo Security::escape($issue['unit']); ?></td>
                        <td><?php echo Security::escape($issue['purpose']); ?></td>
                        <td>
                            <?php 
                            $statusClass = '';
                            if ($issue['status'] == 'Returned') $statusClass = 'status-active';
                            elseif ($issue['status'] == 'Issued') $statusClass = 'status-warning';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo Security::escape($issue['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $issue['actual_return_date'] ? date('d/m/Y', strtotime($issue['actual_return_date'])) : '-'; ?>
                        </td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/weapon_issue/show/<?php echo $issue['id']; ?>?type=weapon" 
                               class="btn-icon" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($type === 'all' || $type === 'ammunition'): ?>
    <!-- Ammunition History -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-bullseye"></i> Ammunition Issue History</h2>
            <span class="record-count">Total: <?php echo number_format($totalAmmoIssues); ?></span>
        </div>

        <div class="table-responsive">
            <?php if (empty($ammoIssues)): ?>
                <div class="empty-state">
                    <i class="fas fa-bullseye"></i>
                    <p>No ammunition issues found</p>
                </div>
            <?php else: ?>
            <table class="asset-table" id="ammoHistoryTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Ammunition</th>
                        <th>Type/Calibre</th>
                        <th>Units</th>
                        <th>Rounds</th>
                        <th>Issued To</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Return Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ammoIssues as $issue): ?>
                    <tr data-status="<?php echo $issue['status'] ?? 'Issued'; ?>" data-date="<?php echo $issue['issue_date']; ?>">
                        <td><?php echo date('d/m/Y', strtotime($issue['issue_date'])); ?></td>
                        <td><?php echo Security::escape($issue['ammo_id']); ?></td>
                        <td><?php echo Security::escape(($issue['ammo_type'] ?? '') . ' (' . ($issue['calibre'] ?? '') . ')'); ?></td>
                        <td class="text-right"><?php echo $issue['units_issued']; ?></td>
                        <td class="text-right"><?php echo $issue['total_rounds']; ?></td>
                        <td><?php echo Security::escape($issue['issued_to']); ?></td>
                        <td><?php echo Security::escape($issue['purpose']); ?></td>
                        <td>
                            <?php 
                            $statusClass = '';
                            if (($issue['status'] ?? '') == 'Returned') $statusClass = 'status-active';
                            else $statusClass = 'status-warning';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo Security::escape($issue['status'] ?? 'Issued'); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo !empty($issue['return_date']) ? date('d/m/Y', strtotime($issue['return_date'])) : '-'; ?>
                        </td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/weapon_issue/show/<?php echo $issue['id']; ?>?type=ammunition" 
                               class="btn-icon" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php
    $totalRecords = $type == 'weapons' ? $totalWeaponIssues : ($type == 'ammunition' ? $totalAmmoIssues : $totalWeaponIssues + $totalAmmoIssues);
    $totalPages = ceil($totalRecords / $limit);
    ?>
    
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <a href="?type=<?php echo $type; ?>&page=<?php echo max(1, $page - 1); ?>" 
           class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <i class="fas fa-chevron-left"></i> Previous
        </a>
        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        <a href="?type=<?php echo $type; ?>&page=<?php echo min($totalPages, $page + 1); ?>" 
           class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            Next <i class="fas fa-chevron-right"></i>
        </a>
    </div>
    <?php endif; ?>
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

function changeType(value) {
    window.location.href = '?type=' + value;
}

function applyFilters() {
    const status = document.getElementById('filterStatus').value.toLowerCase();
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    const searchTerm = document.getElementById('searchHistory').value.toLowerCase();
    
    // Filter weapons table
    const weaponRows = document.querySelectorAll('#weaponHistoryTable tbody tr');
    weaponRows.forEach(row => {
        let show = true;
        
        if (status) {
            const rowStatus = row.dataset.status?.toLowerCase() || '';
            show = show && rowStatus === status;
        }
        
        if (dateFrom) {
            const rowDate = row.dataset.date;
            show = show && rowDate >= dateFrom;
        }
        
        if (dateTo) {
            const rowDate = row.dataset.date;
            show = show && rowDate <= dateTo;
        }
        
        if (searchTerm) {
            const text = row.textContent.toLowerCase();
            show = show && text.includes(searchTerm);
        }
        
        row.style.display = show ? '' : 'none';
    });
    
    // Filter ammo table
    const ammoRows = document.querySelectorAll('#ammoHistoryTable tbody tr');
    ammoRows.forEach(row => {
        let show = true;
        
        if (status) {
            const rowStatus = row.dataset.status?.toLowerCase() || '';
            show = show && rowStatus === status;
        }
        
        if (dateFrom) {
            const rowDate = row.dataset.date;
            show = show && rowDate >= dateFrom;
        }
        
        if (dateTo) {
            const rowDate = row.dataset.date;
            show = show && rowDate <= dateTo;
        }
        
        if (searchTerm) {
            const text = row.textContent.toLowerCase();
            show = show && text.includes(searchTerm);
        }
        
        row.style.display = show ? '' : 'none';
    });
}

function resetFilters() {
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('searchHistory').value = '';
    
    document.querySelectorAll('#weaponHistoryTable tbody tr, #ammoHistoryTable tbody tr').forEach(row => {
        row.style.display = '';
    });
}

// Live search
document.getElementById('searchHistory')?.addEventListener('input', function() {
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(applyFilters, 300);
});

document.getElementById('filterStatus')?.addEventListener('change', applyFilters);
document.getElementById('filterDateFrom')?.addEventListener('change', applyFilters);
document.getElementById('filterDateTo')?.addEventListener('change', applyFilters);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
