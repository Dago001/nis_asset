<?php
$title = 'Ammunition Calibres';
$active = 'ammunition';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-bullseye"></i>
                Ammunition Calibres
            </h1>
            <p>Manage ammunition calibre classifications</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('ammunition.create')): ?>
            <button class="btn btn-success" onclick="showAddCalibreModal()">
                <i class="fas fa-plus-circle"></i> Add New Calibre
            </button>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/ammunition" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Ammunition
            </a>
        </div>
    </div>

    <!-- Calibres Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Ammunition Calibres</h2>
        </div>

        <div class="table-responsive">
            <?php if (empty($calibres)): ?>
                <div class="empty-state">
                    <i class="fas fa-bullseye"></i>
                    <p>No calibres found</p>
                </div>
            <?php else: ?>
            <table class="asset-table">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Calibre</th>
                        <th>Rounds per Unit</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calibres as $index => $calibre): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo Security::escape($calibre['calibre']); ?></td>
                        <td><?php echo number_format($calibre['rounds_per_unit']); ?></td>
                        <td><?php echo Security::escape($calibre['description'] ?? '-'); ?></td>
                        <td>
                            <?php if ($calibre['is_active']): ?>
                                <span class="status-badge status-active">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="#" class="btn-icon" title="Edit" onclick="editCalibre(<?php echo $calibre['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (Auth::can('ammunition.delete')): ?>
                                <a href="<?php echo BASE_URL; ?>/ammunition/calibres/delete/<?php echo $calibre['id']; ?>" 
                                   class="btn-icon delete" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this calibre?')">
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

<!-- Add Calibre Modal -->
<div class="modal" id="addCalibreModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Calibre</h3>
            <button type="button" class="close-modal" onclick="hideAddCalibreModal()">&times;</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>/ammunition/calibres/store">
            <?php echo Security::csrfField(); ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="required">Calibre</label>
                    <input type="text" name="calibre" required maxlength="50" class="form-control" placeholder="e.g., 7.62x39mm">
                </div>
                <div class="form-group">
                    <label class="required">Rounds per Unit</label>
                    <input type="number" name="rounds_per_unit" required min="1" value="30" class="form-control">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save Calibre</button>
                <button type="button" class="btn btn-secondary" onclick="hideAddCalibreModal()">Cancel</button>
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
function showAddCalibreModal() {
    document.getElementById('addCalibreModal').style.display = 'flex';
}

function hideAddCalibreModal() {
    document.getElementById('addCalibreModal').style.display = 'none';
}

function editCalibre(id) {
    // Implement edit functionality
    alert('Edit functionality for calibre ' + id);
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('addCalibreModal');
    if (event.target === modal) {
        hideAddCalibreModal();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>