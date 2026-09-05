<?php
/**
 * Role Permissions Matrix Edit View
 */
include BASE_PATH . '/views/layouts/header.php';
include BASE_PATH . '/views/layouts/sidebar.php';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="header-content">
            <h1>
                <i class="fas fa-user-shield"></i>
                Permissions Matrix: <?php echo htmlspecialchars($role['role_name']); ?>
            </h1>
            <p>Customize the granular feature permissions for this role</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/roles" class="btn btn-outline" style="border: 1px solid #666; color: var(--text-primary); padding: 8px 16px; border-radius: 4px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                <i class="fas fa-arrow-left"></i> Back to Roles
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="padding: 12px 15px; margin-bottom: 20px; border-radius: 6px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Matrix Editor Form -->
    <form action="<?php echo BASE_URL; ?>/roles/update/<?php echo $role['id']; ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <?php foreach ($groupedPermissions as $module => $perms): ?>
            <div class="content-card" style="margin-bottom: 25px; border-radius: 8px; border: 1px solid #D7E3DC; overflow: hidden; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div class="filter-header" style="padding: 12px 20px; background: var(--light-bg); border-bottom: 1px solid #D7E3DC; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 1.1rem; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-cubes"></i> <?php echo htmlspecialchars($module); ?> Module
                    </h3>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-sm btn-outline" onclick="toggleModuleChecked(this, true)" style="font-size: 0.75rem; padding: 4px 8px; background: #E8F5E9; border: 1px solid #2E7D32; color: #2E7D32; border-radius: 4px; cursor: pointer; font-weight: 600;">
                            Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline" onclick="toggleModuleChecked(this, false)" style="font-size: 0.75rem; padding: 4px 8px; background: #FFEBEE; border: 1px solid #C62828; color: #C62828; border-radius: 4px; cursor: pointer; font-weight: 600;">
                            Deselect All
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive" style="padding: 0;">
                    <table class="asset-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #FAFAFA; border-bottom: 1px solid #EAEAEA;">
                                <th style="padding: 12px 20px; text-align: left;">Permission Name & Key</th>
                                <th style="padding: 12px; text-align: center; width: 100px;">View</th>
                                <th style="padding: 12px; text-align: center; width: 100px;">Create</th>
                                <th style="padding: 12px; text-align: center; width: 100px;">Edit</th>
                                <th style="padding: 12px; text-align: center; width: 100px;">Delete</th>
                                <th style="padding: 12px; text-align: center; width: 100px;">Approve</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perms as $perm): ?>
                                <tr class="perm-row" style="border-bottom: 1px solid #F0F0F0;">
                                    <td style="padding: 12px 20px;">
                                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($perm['description'] ?: ucwords(str_replace('.', ' ', $perm['permission_key']))); ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #777; font-family: monospace;">
                                            <?php echo htmlspecialchars($perm['permission_key']); ?>
                                        </div>
                                    </td>
                                    <!-- Checkboxes -->
                                    <td style="padding: 12px; text-align: center;">
                                        <input type="checkbox" name="perm_<?php echo $perm['id']; ?>_view" value="1" <?php echo ($perm['can_view'] ?? 0) ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer; accent-color: #207027;">
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <input type="checkbox" name="perm_<?php echo $perm['id']; ?>_create" value="1" <?php echo ($perm['can_create'] ?? 0) ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer; accent-color: #207027;">
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <input type="checkbox" name="perm_<?php echo $perm['id']; ?>_edit" value="1" <?php echo ($perm['can_edit'] ?? 0) ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer; accent-color: #207027;">
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <input type="checkbox" name="perm_<?php echo $perm['id']; ?>_delete" value="1" <?php echo ($perm['can_delete'] ?? 0) ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer; accent-color: #207027;">
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <input type="checkbox" name="perm_<?php echo $perm['id']; ?>_approve" value="1" <?php echo ($perm['can_approve'] ?? 0) ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer; accent-color: #207027;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Save Footer -->
        <div class="content-card" style="padding: 15px 20px; display: flex; justify-content: flex-end; gap: 15px; border-radius: 8px; border: 1px solid #D7E3DC; background: white;">
            <a href="<?php echo BASE_URL; ?>/roles" class="btn btn-outline" style="border: 1px solid #666; color: var(--text-primary); padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; cursor: pointer;">
                Cancel
            </a>
            <button type="submit" class="btn btn-success" style="background: #207027; color: white; border: none; padding: 10px 25px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-save"></i> Save Permissions Matrix
            </button>
        </div>
    </form>
</div>

<script>
    /**
     * Check/uncheck all checkboxes in a module card
     */
    function toggleModuleChecked(button, checkedState) {
        const card = button.closest('.content-card');
        const checkboxes = card.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => {
            cb.checked = checkedState;
        });
    }
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
