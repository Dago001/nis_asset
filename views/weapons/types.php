<?php
$title = 'Weapon Types';
$active = 'weapons';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-tags"></i>
                Weapon Types
            </h1>
            <p>Manage weapon type classifications</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('weapons.create')): ?>
            <button class="btn btn-success" onclick="showAddTypeModal()">
                <i class="fas fa-plus-circle"></i> Add New Type
            </button>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/weapons" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Weapons
            </a>
        </div>
    </div>

    <!-- Types Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Weapon Types</h2>
        </div>

        <div class="table-responsive">
            <?php if (empty($types)): ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <p>No weapon types found</p>
                </div>
            <?php else: ?>
            <table class="asset-table">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Type Name</th>
                        <th>Description</th>
                        <th>Default Calibre</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($types as $index => $type): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo Security::escape($type['type_name']); ?></td>
                        <td><?php echo Security::escape($type['description'] ?? '-'); ?></td>
                        <td><?php echo Security::escape($type['default_calibre'] ?? '-'); ?></td>
                        <td>
                            <?php if ($type['is_active']): ?>
                                <span class="status-badge status-active">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="#" class="btn-icon" title="Edit" onclick="editType(<?php echo $type['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (Auth::can('weapons.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/weapons/types/delete/<?php echo $type['id']; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this weapon type?')">
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
    </div>
</div>

<!-- Add Type Modal -->
<div class="modal" id="addTypeModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Weapon Type</h3>
            <button type="button" class="close-modal" onclick="hideAddTypeModal()">&times;</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>/weapons/types/store">
            <?php echo Security::csrfField(); ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="required">Type Name</label>
                    <input type="text" name="type_name" required maxlength="100" class="form-control">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Default Calibre</label>
                    <input type="text" name="default_calibre" maxlength="50" class="form-control" placeholder="e.g., 7.62x39mm">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save Type</button>
                <button type="button" class="btn btn-secondary" onclick="hideAddTypeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
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

.modal-header {
    padding: 20px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.2rem;
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
    padding: 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<script>
function showAddTypeModal() {
    document.getElementById('addTypeModal').style.display = 'flex';
}

function hideAddTypeModal() {
    document.getElementById('addTypeModal').style.display = 'none';
}

function editType(id) {
    // Implement edit functionality
    alert('Edit functionality for type ' + id);
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('addTypeModal');
    if (event.target === modal) {
        hideAddTypeModal();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
