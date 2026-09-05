<?php
$title = 'Pending Requisitions';
$active = 'requisitions';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Ensure $requisitions is an array
$requisitions = isset($requisitions) && is_array($requisitions) ? $requisitions : [];

$page = $pagination['page'] ?? 1;
$totalPages = $pagination['totalPages'] ?? 1;
$totalCount = $pagination['totalCount'] ?? 0;

// Calculate priority counts safely from database
$priorityCounts = [
    'Urgent' => 0,
    'High' => 0,
    'Medium' => 0,
    'Low' => 0
];
$countsData = Database::fetchAll("SELECT priority_level, COUNT(*) as count FROM requisitions WHERE status = 'Pending' GROUP BY priority_level");
foreach ($countsData as $row) {
    if (isset($priorityCounts[$row['priority_level']])) {
        $priorityCounts[$row['priority_level']] = (int)$row['count'];
    }
}
$pendingTotal = array_sum($priorityCounts);

// Generate CSRF token using Security class
$csrfToken = Security::csrfToken();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-clock"></i>
                Pending Requisitions
            </h1>
            <p>Requisitions awaiting your approval</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/requisition" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> All Requisitions
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h4>Pending Requisitions</h4>
                <p class="stat-number"><?php echo number_format($totalCount); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon urgent">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h4>Urgent</h4>
                <p class="stat-number"><?php echo $priorityCounts['Urgent']; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon high">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-details">
                <h4>High Priority</h4>
                <p class="stat-number"><?php echo $priorityCounts['High']; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon medium">
                <i class="fas fa-minus"></i>
            </div>
            <div class="stat-details">
                <h4>Medium Priority</h4>
                <p class="stat-number"><?php echo $priorityCounts['Medium']; ?></p>
            </div>
        </div>
    </div>

    <!-- Pending Requisitions Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Requisitions Awaiting Approval</h2>
            <div class="card-actions">
                <span class="record-count">Showing page <?php echo $page; ?> of <?php echo $totalPages; ?> (Total: <span id="recordCount"><?php echo $totalCount; ?></span> records)</span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($requisitions)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No pending requisitions found</p>
                </div>
            <?php else: ?>
            <table class="asset-table" id="pendingTable">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Requisition #</th>
                        <th>Date</th>
                        <th>Requesting Officer</th>
                        <th>Rank</th>
                        <th>Command</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Items</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requisitions as $index => $req): ?>
                    <tr>
                        <td><?php echo ($page - 1) * 50 + $index + 1; ?></td>
                        <td>
                            <span class="asset-code"><?php echo htmlspecialchars($req['requisition_number'] ?? ''); ?></span>
                        </td>
                        <td><?php echo !empty($req['requisition_date']) ? date('d/m/Y', strtotime($req['requisition_date'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($req['requester_name'] ?? $req['requesting_officer_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($req['requesting_rank'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($req['command_name'] ?? ''); ?></td>
                        <td>
                            <?php 
                            $rType = !empty($req['requisition_type']) ? $req['requisition_type'] : 'Both';
                            $typeLabel = $rType;
                            if ($rType === 'Both') $typeLabel = 'Weapons & Ammo';
                            elseif ($rType === 'All') $typeLabel = 'All Types';
                            
                            $typeClass = 'badge-type-both';
                            if ($rType === 'Weapon') $typeClass = 'badge-type-weapon';
                            elseif ($rType === 'Ammunition') $typeClass = 'badge-type-ammo';
                            elseif ($rType === 'Non-Lethal') $typeClass = 'badge-type-nonlethal';
                            ?>
                            <span class="badge <?php echo $typeClass; ?>"><?php echo htmlspecialchars($typeLabel); ?></span>
                        </td>
                        <td>
                            <?php 
                            $priority = $req['priority_level'] ?? '';
                            $priorityClass = '';
                            if ($priority == 'Urgent') $priorityClass = 'priority-urgent';
                            elseif ($priority == 'High') $priorityClass = 'priority-high';
                            elseif ($priority == 'Medium') $priorityClass = 'priority-medium';
                            elseif ($priority == 'Low') $priorityClass = 'priority-low';
                            ?>
                            <span class="priority-badge <?php echo $priorityClass; ?>">
                                <?php echo htmlspecialchars($priority); ?>
                            </span>
                        </td>
                        <td class="text-center"><?php echo $req['item_count'] ?? 0; ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $req['id'] ?? ''; ?>" 
                                   class="btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php 
                                $userRoles = $_SESSION['roles'] ?? [];
                                $canActOnRow = false;
                                if (($req['approval_stage'] ?? 'Command_Approval') === 'Command_Approval') {
                                    if (in_array('Command Approval Officer', $userRoles, true) && Auth::commandId() == ($req['requesting_command_id'] ?? 0)) {
                                        $canActOnRow = true;
                                    }
                                } elseif (($req['approval_stage'] ?? '') === 'HQ_Vetting') {
                                    if (in_array('HQ Armorer', $userRoles, true)) {
                                        $canActOnRow = true;
                                    }
                                }
                                if ($canActOnRow):
                                ?>
                                <button class="btn-icon success" title="Approve" onclick="approveRequisition(<?php echo $req['id'] ?? 0; ?>)">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button class="btn-icon delete" title="Reject" onclick="rejectRequisition(<?php echo $req['id'] ?? 0; ?>)">
                                    <i class="fas fa-times-circle"></i>
                                </button>
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
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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

.stat-icon.urgent {
    background: #ffebee;
    color: #c62828;
}

.stat-icon.high {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.medium {
    background: #fff8e1;
    color: #fbc02d;
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

/* Content Card */
.content-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.section-title {
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

/* Priority Badges */
.priority-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.priority-urgent {
    background: #ffebee;
    color: #c62828;
}

.priority-high {
    background: #fff3e0;
    color: #ef6c00;
}

.priority-medium {
    background: #fff8e1;
    color: #fbc02d;
}

.priority-low {
    background: #e8f5e9;
    color: #2e7d32;
}

/* Badge */
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

.text-center {
    text-align: center;
}

.badge-type-weapon {
    background: #DCFCE7;
    color: #15803D;
    border: 1px solid #BBF7D0;
}

.badge-type-ammo {
    background: #FEF3C7;
    color: #B45309;
    border: 1px solid #FDE68A;
}

.badge-type-both {
    background: #E0E7FF;
    color: #4338CA;
    border: 1px solid #C7D2FE;
}

.badge-type-nonlethal {
    background: #F3E8FF;
    color: #7E22CE;
    border: 1px solid #E9D5FF;
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

.btn-icon.success {
    background: #e8f5e9;
    color: #2e7d32;
}

.btn-icon.success:hover {
    background: #2e7d32;
    color: white;
}

.btn-icon.delete {
    background: #ffebee;
    color: #c62828;
}

.btn-icon.delete:hover {
    background: #c62828;
    color: white;
}

.btn-icon:hover i {
    color: white !important;
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
    margin: 0;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-buttons {
        flex-wrap: wrap;
    }
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
</style>

<script>
function approveRequisition(id) {
    if (confirm('Are you sure you want to approve this requisition?')) {
        const remarks = prompt('Enter approval remarks (optional):');
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/requisition/approve/' + id;
        form.style.display = 'none';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = '<?php echo $csrfToken; ?>';
        form.appendChild(csrf);
        
        if (remarks && remarks.trim() !== '') {
            const remarksInput = document.createElement('input');
            remarksInput.type = 'hidden';
            remarksInput.name = 'approval_remarks';
            remarksInput.value = remarks.trim();
            form.appendChild(remarksInput);
        }
        
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectRequisition(id) {
    const reason = prompt('Please enter reason for rejection:');
    if (reason && reason.trim() !== '') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/requisition/reject/' + id;
        form.style.display = 'none';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = '<?php echo $csrfToken; ?>';
        form.appendChild(csrf);
        
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'rejection_reason';
        reasonInput.value = reason.trim();
        form.appendChild(reasonInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
