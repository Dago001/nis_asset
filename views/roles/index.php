<?php
/**
 * Roles & Permissions List View
 */
include BASE_PATH . '/views/layouts/header.php';
include BASE_PATH . '/views/layouts/sidebar.php';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-shield-alt"></i>
                Roles & Permissions
            </h1>
            <p>Manage system access levels and permissions matrices</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="padding: 12px 15px; margin-bottom: 20px; border-radius: 6px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="padding: 12px 15px; margin-bottom: 20px; border-radius: 6px;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Roles Table -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Defined System Roles</h2>
            <span style="color: var(--text-secondary); font-size: 0.9rem;">Total: <?php echo count($roles); ?> roles</span>
        </div>

        <div class="table-responsive">
            <table class="asset-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #F3F7F4; border-bottom: 2px solid #207027;">
                        <th style="padding: 12px; text-align: left; width: 60px;">S/N</th>
                        <th style="padding: 12px; text-align: left; width: 250px;">Role Name</th>
                        <th style="padding: 12px; text-align: left;">Description</th>
                        <th style="padding: 12px; text-align: center; width: 120px;">Users Assigned</th>
                        <th style="padding: 12px; text-align: center; width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roles)): ?>
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: #777;">No roles found in system database.</td>
                        </tr>
                    <?php else: ?>
                        <?php $sn = 1; foreach ($roles as $role): ?>
                            <tr style="border-bottom: 1px solid #EAEAEA;">
                                <td style="padding: 12px;"><?php echo $sn++; ?></td>
                                <td style="padding: 12px;">
                                    <strong><?php echo htmlspecialchars($role['role_name']); ?></strong>
                                    <?php if ($role['is_system_role']): ?>
                                        <span class="badge" style="background: #EAEAEA; color: #444; font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">System</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; color: #555;"><?php echo htmlspecialchars($role['description'] ?? 'No description provided'); ?></td>
                                <td style="padding: 12px; text-align: center;">
                                    <span class="badge" style="background: #E8F5E9; color: #2E7D32; font-weight: 600; padding: 4px 8px; border-radius: 12px;">
                                        <?php echo (int)$role['user_count']; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <a href="<?php echo BASE_URL; ?>/roles/edit/<?php echo $role['id']; ?>" class="btn btn-primary btn-sm" style="background: #207027; color: white; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-size: 0.85rem; font-weight: 600;">
                                        <i class="fas fa-user-shield"></i> Permissions
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
