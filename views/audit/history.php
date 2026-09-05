<?php
$title = 'Audit History';
$active = 'audit-history';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-history"></i>
                Audit History
            </h1>
            <p>Complete audit trail of all system activities</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/audit/export?<?php echo http_build_query($filters); ?>" class="btn btn-outline">
                <i class="fas fa-download"></i> Export
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilters()">
            <h3><i class="fas fa-filter"></i> Filter Audit Logs</h3>
            <i class="fas fa-chevron-down" id="filterToggleIcon"></i>
        </div>
        <div class="filter-body" id="filterBody" style="display: <?php echo !empty(array_filter($filters)) ? 'block' : 'none'; ?>;">
            <form method="GET" action="<?php echo BASE_URL; ?>/audit/history" id="filterForm">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>User</label>
                        <select name="user_id" class="form-control">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($filters['user_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($user['full_name'] ?: $user['username']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Action</label>
                        <select name="action" class="form-control">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                            <option value="<?php echo $action['action']; ?>" <?php echo ($filters['action'] ?? '') == $action['action'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($action['action']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Table</label>
                        <select name="table_name" class="form-control">
                            <option value="">All Tables</option>
                            <?php foreach ($tables as $table): ?>
                            <option value="<?php echo $table['table_name']; ?>" <?php echo ($filters['table_name'] ?? '') == $table['table_name'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($table['table_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Date From</label>
                        <input type="date" name="start_date" value="<?php echo Security::escape($filters['start_date'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="filter-group">
                        <label>Date To</label>
                        <input type="date" name="end_date" value="<?php echo Security::escape($filters['end_date'] ?? ''); ?>" class="form-control">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="<?php echo BASE_URL; ?>/audit/history" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Audit Trail</h2>
        </div>

        <div class="table-responsive">
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No audit logs found</p>
                </div>
            <?php else: ?>
            <table class="asset-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                        <td>
                            <?php if ($log['user_id']): ?>
                                <a href="<?php echo BASE_URL; ?>/users/show/<?php echo $log['user_id']; ?>">
                                    <?php echo Security::escape($log['full_name'] ?: $log['username']); ?>
                                </a>
                            <?php else: ?>
                                System
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $actionClass = '';
                            if (strpos($log['action'], 'CREATE') !== false) $actionClass = 'badge-success';
                            elseif (strpos($log['action'], 'UPDATE') !== false) $actionClass = 'badge-warning';
                            elseif (strpos($log['action'], 'DELETE') !== false) $actionClass = 'badge-danger';
                            elseif (strpos($log['action'], 'LOGIN') !== false) $actionClass = 'badge-info';
                            else $actionClass = 'badge-secondary';
                            ?>
                            <span class="badge <?php echo $actionClass; ?>">
                                <?php echo Security::escape($log['action']); ?>
                            </span>
                        </td>
                        <td><?php echo Security::escape($log['table_name'] ?? '-'); ?></td>
                        <td class="text-center"><?php echo $log['record_id'] ?? '-'; ?></td>
                        <td><?php echo Security::escape($log['description'] ?? '-'); ?></td>
                        <td><?php echo Security::escape($log['ip_address'] ?? '-'); ?></td>
                        <td>
                            <?php if (!empty($log['old_data']) || !empty($log['new_data'])): ?>
                            <button type="button" class="btn-icon" onclick="showAuditDetails(<?php echo $log['id']; ?>)">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="<?php echo BASE_URL; ?>/audit/history?page=<?php echo $page - 1; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>" class="page-link">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
            <?php endif; ?>
            
            <span class="page-info">
                Page <?php echo $page; ?> of <?php echo $totalPages; ?>
            </span>
            
            <?php if ($page < $totalPages): ?>
            <a href="<?php echo BASE_URL; ?>/audit/history?page=<?php echo $page + 1; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>" class="page-link">
                Next <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Audit Details Modal -->
<div class="modal" id="auditDetailsModal" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-search"></i> Audit Details</h3>
            <button type="button" class="close-modal" onclick="hideAuditDetails()">&times;</button>
        </div>
        <div class="modal-body" id="auditDetailsContent">
            <div class="loading">Loading...</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideAuditDetails()">Close</button>
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

.filter-body {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

.text-center {
    text-align: center;
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
}

.page-link:hover {
    background: var(--light-bg);
    border-color: var(--success-color);
    text-decoration: none;
    color: var(--text-primary);
}

.page-link.active {
    background: var(--success-color);
    color: white;
    border-color: var(--success-color);
}

.page-info {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1100;
}

.modal-content {
    background: var(--surface);
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-content.modal-lg {
    max-width: 800px;
}

.modal-header {
    padding: 15px 20px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.close-modal {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.loading {
    text-align: center;
    padding: 30px;
    color: var(--text-secondary);
}

.data-diff {
    margin-bottom: 20px;
}

.data-diff h4 {
    margin: 0 0 10px 0;
    color: var(--text-primary);
    font-size: 1rem;
}

.diff-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.diff-table th {
    background: var(--light-bg);
    padding: 8px;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
}

.diff-table td {
    padding: 8px;
    border: 1px solid var(--border-color);
}

.diff-table .old-value {
    background: #f8d7da;
}

.diff-table .new-value {
    background: #d4edda;
}

@media (max-width: 768px) {
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

function showAuditDetails(logId) {
    const modal = document.getElementById('auditDetailsModal');
    const content = document.getElementById('auditDetailsContent');
    
    modal.style.display = 'flex';
    content.innerHTML = '<div class="loading">Loading...</div>';
    
    fetch('<?php echo BASE_URL; ?>/api/get_audit_details?id=' + logId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                
                if (data.old_data) {
                    html += '<div class="data-diff">';
                    html += '<h4>Old Data</h4>';
                    html += '<table class="diff-table">';
                    html += '<tr><th>Field</th><th>Value</th></tr>';
                    Object.entries(data.old_data).forEach(([key, value]) => {
                        if (key !== 'id' && key !== 'created_at' && key !== 'updated_at') {
                            html += `<tr><td>${key}</td><td class="old-value">${value !== null ? value : 'null'}</td></tr>`;
                        }
                    });
                    html += '</table>';
                    html += '</div>';
                }
                
                if (data.new_data) {
                    html += '<div class="data-diff">';
                    html += '<h4>New Data</h4>';
                    html += '<table class="diff-table">';
                    html += '<tr><th>Field</th><th>Value</th></tr>';
                    Object.entries(data.new_data).forEach(([key, value]) => {
                        if (key !== 'id' && key !== 'created_at' && key !== 'updated_at') {
                            html += `<tr><td>${key}</td><td class="new-value">${value !== null ? value : 'null'}</td></tr>`;
                        }
                    });
                    html += '</table>';
                    html += '</div>';
                }
                
                if (!data.old_data && !data.new_data) {
                    html = '<p class="text-center text-muted">No additional data available</p>';
                }
                
                content.innerHTML = html;
            } else {
                content.innerHTML = '<p class="text-center text-danger">Failed to load audit details</p>';
            }
        })
        .catch(error => {
            content.innerHTML = '<p class="text-center text-danger">Error loading details</p>';
            console.error('Error:', error);
        });
}

function hideAuditDetails() {
    document.getElementById('auditDetailsModal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('auditDetailsModal');
    if (event.target === modal) {
        hideAuditDetails();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
