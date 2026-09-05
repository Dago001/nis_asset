<?php
$title = 'User Details';
$active = 'users';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Format initials
$initials = '';
$nameParts = explode(' ', trim($user['full_name'] ?? 'Officer'));
foreach (array_slice($nameParts, 0, 2) as $np) {
    $initials .= strtoupper(substr($np, 0, 1));
}
if (empty($initials)) $initials = 'NIS';
?>

<div class="container-fluid user-show-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/users"><i class="fas fa-users-gear"></i> User Management</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Officer Profile</span>
            </div>
            <h1 class="page-title">
                <i class="fas fa-id-card-clip"></i>
                <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                <span class="header-badge-code" title="Click to copy Service Number" onclick="copyToClipboard('<?php echo htmlspecialchars($user['username']); ?>', 'Service Number')">
                    <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($user['username']); ?>
                    <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
            <p>Comprehensive account profile, assigned roles, deployment formation, and security settings</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('users.manage')): ?>
            <a href="<?php echo BASE_URL; ?>/users/edit/<?php echo $user['id']; ?>" class="btn btn-success">
                <i class="fas fa-user-pen"></i> Edit User
            </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/users" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
            <button type="button" class="btn btn-outline" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- KPI Summary Metrics Grid -->
    <div class="user-kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon icon-identity">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="kpi-details">
                <span class="kpi-label">Service Number</span>
                <span class="kpi-value"><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon <?php echo !empty($user['is_active']) ? 'icon-active' : 'icon-inactive'; ?>">
                <i class="fas <?php echo !empty($user['is_active']) ? 'fa-user-check' : 'fa-user-slash'; ?>"></i>
            </div>
            <div class="kpi-details">
                <span class="kpi-label">Account Status</span>
                <span class="kpi-value">
                    <?php if (!empty($user['is_active'])): ?>
                        <span class="status-badge status-active"><span class="status-dot"></span> Active</span>
                    <?php else: ?>
                        <span class="status-badge status-inactive"><span class="status-dot"></span> Disabled</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-rank">
                <i class="fas fa-award"></i>
            </div>
            <div class="kpi-details">
                <span class="kpi-label">Rank </span>
                <span class="kpi-value"><?php echo htmlspecialchars($user['rank'] ?? 'Unranked'); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-2fa">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div class="kpi-details">
                <span class="kpi-label">Google 2FA</span>
                <span class="kpi-value">
                    <?php if (!empty($user['two_factor_enabled'])): ?>
                        <span class="status-badge status-active"><span class="status-dot"></span> Enrolled</span>
                    <?php else: ?>
                        <span class="status-badge status-inactive"><span class="status-dot"></span> Not Set</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content Layout (2 Columns) -->
    <div class="show-layout-grid">
        <!-- Main Column (Left) -->
        <div class="show-main-column">
            <!-- Profile Info Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <div class="header-title-box">
                        <i class="fas fa-id-card"></i>
                        <h3>Officer Profile & Contact Details</h3>
                    </div>
                </div>
                <div class="pro-card-body">
                    <div class="officer-hero-banner">
                        <?php 
                        $baseDir = defined('BASE_PATH') ? BASE_PATH : (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2));
                        $showAvatar = !empty($user['profile_image']) && file_exists($baseDir . '/' . $user['profile_image'])
                            ? BASE_URL . '/' . htmlspecialchars($user['profile_image'])
                            : null;
                        ?>
                        <?php if ($showAvatar): ?>
                            <img src="<?php echo $showAvatar; ?>" alt="Avatar" class="hero-avatar-circle" style="object-fit: cover; padding: 0;">
                        <?php else: ?>
                            <div class="hero-avatar-circle">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                        <?php endif; ?>
                        <div class="hero-officer-info">
                            <h2><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></h2>
                            <p><i class="fas fa-award"></i> <?php echo htmlspecialchars($user['rank'] ?? 'Unranked'); ?> &bull; NIS #<?php echo htmlspecialchars($user['nis_number'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Full Name</span>
                            <span class="item-value font-semibold"><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">NIS Service Number</span>
                            <span class="item-value font-mono text-primary" style="font-weight: 700;">
                                <?php echo htmlspecialchars($user['nis_number'] ?? 'N/A'); ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Official Email</span>
                            <span class="item-value">
                                <a href="mailto:<?php echo htmlspecialchars($user['email'] ?? ''); ?>" style="color: var(--primary-light, #207027); text-decoration: none;">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?>
                                </a>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Phone Number</span>
                            <span class="item-value font-mono">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deployment & Formation Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <div class="header-title-box">
                        <i class="fas fa-building-shield"></i>
                        <h3>Deployment & Formation</h3>
                    </div>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Assigned Command</span>
                            <span class="item-value font-semibold">
                                <i class="fas fa-building text-primary"></i> <?php echo htmlspecialchars($user['command_name'] ?? 'HQ / Unassigned'); ?>
                            </span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Zonal Command</span>
                            <span class="item-value">
                                <i class="fas fa-map-location-dot text-muted"></i> <?php echo htmlspecialchars($user['zone_name'] ?? 'Zonal N/A'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Restriction (Geofencing) Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <div class="header-title-box">
                        <i class="fas fa-location-crosshairs"></i>
                        <h3>Location Restriction (Geofencing)</h3>
                    </div>
                    <?php if (!empty($user['geofence_enabled'])): ?>
                        <span class="badge badge-enabled"><i class="fas fa-lock"></i> Geofence Active</span>
                    <?php else: ?>
                        <span class="badge badge-disabled"><i class="fas fa-lock-open"></i> Unrestricted</span>
                    <?php endif; ?>
                </div>
                <div class="pro-card-body">
                    <?php if (!empty($user['geofence_enabled'])): ?>
                        <div class="geofence-status-banner active">
                            <i class="fas fa-shield-halved"></i>
                            <div>
                                <strong>Geofence Enforcement Enabled</strong>
                                <p style="margin: 2px 0 0 0; font-size: 0.82rem;">Logins are restricted to a <strong><?php echo htmlspecialchars($user['geofence_radius_m'] ?? '500'); ?> meter</strong> radius around the designated coordinate point.</p>
                            </div>
                        </div>

                        <?php if (!empty($user['geofence_lat']) && !empty($user['geofence_lng'])): ?>
                            <div id="geofencePreviewMap" style="height: 220px; width: 100%; border-radius: 8px; border: 1px solid var(--border-color, #D7E3DC); margin: 14px 0 12px 0;"></div>
                        <?php endif; ?>

                        <div class="pro-detail-grid">
                            <div class="pro-detail-item">
                                <span class="item-label">Center Latitude</span>
                                <span class="item-value font-mono"><?php echo htmlspecialchars($user['geofence_lat'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="pro-detail-item">
                                <span class="item-label">Center Longitude</span>
                                <span class="item-value font-mono"><?php echo htmlspecialchars($user['geofence_lng'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="pro-detail-item">
                                <span class="item-label">Radius</span>
                                <span class="item-value"><?php echo htmlspecialchars($user['geofence_radius_m'] ?? '500'); ?> meters</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="geofence-status-banner inactive">
                            <i class="fas fa-globe"></i>
                            <div>
                                <strong>No Location Restriction Configured</strong>
                                <p style="margin: 2px 0 0 0; font-size: 0.82rem;">This officer can securely log into the system from any authorized device location.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Column (Right) -->
        <div class="show-sidebar-column">
            <!-- System Access Roles Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <div class="header-title-box">
                        <i class="fas fa-shield-halved"></i>
                        <h3>Assigned System Roles</h3>
                    </div>
                </div>
                <div class="pro-card-body">
                    <div class="roles-pills-wrap">
                        <?php 
                        $userRolesList = [];
                        if (!empty($user['roles'])) {
                            $userRolesList = is_array($user['roles']) ? $user['roles'] : explode(',', $user['roles']);
                        } elseif (!empty($user['role_names'])) {
                            $userRolesList = explode(',', $user['role_names']);
                        }
                        
                        if (!Auth::isSuperAdmin()) {
                            $userRolesList = array_values(array_filter($userRolesList, function($r) {
                                return stripos(trim($r), 'Super Admin') === false;
                            }));
                        }
                        
                        if (!empty($userRolesList)):
                            foreach ($userRolesList as $role): 
                                $trimmedRole = trim($role);
                                $badgeClass = 'role-badge-default';
                                if (stripos($trimmedRole, 'Super Admin') !== false) $badgeClass = 'role-badge-danger';
                                elseif (stripos($trimmedRole, 'Admin') !== false) $badgeClass = 'role-badge-primary';
                                elseif (stripos($trimmedRole, 'Sectional') !== false) $badgeClass = 'role-badge-warning';
                        ?>
                            <div class="role-pill-card <?php echo $badgeClass; ?>">
                                <i class="fas fa-shield-check"></i>
                                <span><?php echo htmlspecialchars($trimmedRole); ?></span>
                            </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <span class="text-muted" style="font-size: 0.85rem;">No active roles assigned</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Account Metadata Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <div class="header-title-box">
                        <i class="fas fa-clock-rotate-left"></i>
                        <h3>Account Activity</h3>
                    </div>
                </div>
                <div class="pro-card-body">
                    <div class="activity-timeline">
                        <div class="activity-item">
                            <span class="activity-label">Registered On</span>
                            <span class="activity-value font-mono">
                                <?php echo !empty($user['created_at']) ? date('d M Y, h:i A', strtotime($user['created_at'])) : 'N/A'; ?>
                            </span>
                        </div>
                        <div class="activity-item">
                            <span class="activity-label">Last Login</span>
                            <span class="activity-value font-mono">
                                <?php echo !empty($user['last_login']) ? date('d M Y, h:i A', strtotime($user['last_login'])) : 'Never'; ?>
                            </span>
                        </div>
                        <div class="activity-item">
                            <span class="activity-label">Last Known IP</span>
                            <span class="activity-value font-mono">
                                <?php echo htmlspecialchars($user['last_ip'] ?? 'N/A'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="copyToast" class="copy-toast"></div>

<style>
/* User Show View Styling */
.user-show-wrapper {
    padding-bottom: 40px;
}

/* Page Header - moderate title font-weight (no heavy bold) */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color, #D7E3DC);
}

.page-header .header-content h1,
.page-title {
    font-size: 1.5rem;
    font-weight: 600 !important; /* Disabled excessive boldness */
    color: var(--primary-color, #134617);
    margin: 4px 0 4px 0;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.page-header .header-content p {
    font-size: 0.9rem;
    color: var(--text-secondary, #53665E);
    margin: 0;
}

.header-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: var(--text-secondary, #53665E);
    margin-bottom: 4px;
}

.header-breadcrumb a {
    color: var(--primary-light, #207027);
    text-decoration: none;
    font-weight: 600;
}

.breadcrumb-separator {
    font-size: 0.7rem;
    color: #94A3B8;
}

.header-badge-code {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--light-bg, #F7FAF8);
    color: var(--primary-color, #134617);
    border: 1px solid var(--border-color, #D7E3DC);
    font-size: 0.88rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.header-badge-code:hover {
    background: #e8f5e9;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* KPI Metrics Grid */
.user-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.kpi-card {
    background: var(--surface, #ffffff);
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 8px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.icon-identity { background: #e8f5e9; color: #134617; }
.icon-active { background: #e8f5e9; color: #207027; }
.icon-inactive { background: #fee2e2; color: #b91c1c; }
.icon-rank { background: #e0f2fe; color: #0369a1; }
.icon-2fa { background: #fef3c7; color: #d97706; }

.kpi-details {
    display: flex;
    flex-direction: column;
}

.kpi-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-secondary, #53665E);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.kpi-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
}

/* 2-Column Layout */
.show-layout-grid {
    display: grid;
    grid-template-columns: 7fr 3fr;
    gap: 20px;
}

/* Cards */
.pro-card {
    background: var(--surface, #ffffff);
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    margin-bottom: 20px;
    overflow: hidden;
}

.pro-card-header {
    padding: 14px 18px;
    background: var(--light-bg, #F7FAF8);
    border-bottom: 1px solid var(--border-color, #D7E3DC);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title-box {
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-title-box i {
    color: var(--primary-light, #207027);
    font-size: 1rem;
}

.header-title-box h3 {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
}

.pro-card-body {
    padding: 18px;
}

/* Hero Banner in Officer Card */
.officer-hero-banner {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-bottom: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--border-color, #D7E3DC);
}

.hero-avatar-circle {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, #134617 0%, #207027 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(19, 70, 23, 0.25);
}

.hero-officer-info h2 {
    margin: 0 0 4px 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
}

.hero-officer-info p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--text-secondary, #53665E);
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Detail Grids */
.pro-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 20px;
}

.pro-detail-item {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.item-label {
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--text-secondary, #53665E);
}

.item-value {
    font-size: 0.92rem;
    color: var(--text-primary, #212529);
}

/* Geofence Status Banners */
.geofence-status-banner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 6px;
    margin-bottom: 12px;
    font-size: 0.88rem;
}

.geofence-status-banner.active {
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    color: #0369a1;
}

.geofence-status-banner.inactive {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
}

.geofence-status-banner i {
    font-size: 1.2rem;
    margin-top: 2px;
}

.badge-enabled {
    background: #e0f2fe;
    color: #0369a1;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
}

.badge-disabled {
    background: #f1f5f9;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
}

/* Roles Pills */
.roles-pills-wrap {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.role-pill-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}

.role-pill-card.role-badge-primary { background: #e8f5e9; color: #134617; border: 1px solid #c8e6c9; }
.role-pill-card.role-badge-danger { background: #ffebee; color: #b71c1c; border: 1px solid #ffcdd2; }
.role-pill-card.role-badge-warning { background: #fff8e1; color: #b45309; border: 1px solid #fef3c7; }
.role-pill-card.role-badge-default { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

/* Activity Timeline */
.activity-timeline {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-color, #D7E3DC);
}

.activity-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.activity-label {
    font-size: 0.82rem;
    color: var(--text-secondary, #53665E);
}

.activity-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.78rem;
    font-weight: 600;
}

.status-active { background: #e8f5e9; color: #1b5e20; }
.status-inactive { background: #fee2e2; color: #991b1b; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; }
.status-active .status-dot { background: #2e7d32; }
.status-inactive .status-dot { background: #dc2626; }

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
}

.btn-success { background: var(--primary-light, #207027); color: white; }
.btn-success:hover { background: var(--primary-color, #134617); color: white; }
.btn-secondary { background: #64748b; color: white; }
.btn-secondary:hover { background: #475569; color: white; }
.btn-outline { background: transparent; border: 1px solid var(--border-color, #D7E3DC); color: var(--text-secondary, #53665E); }
.btn-outline:hover { background: #e2e8f0; color: var(--text-primary, #212529); }

.copy-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #0F172A;
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 0.88rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
    pointer-events: none;
    z-index: 9999;
}
.copy-toast.show { opacity: 1; transform: translateY(0); }

@media (max-width: 992px) {
    .show-layout-grid { grid-template-columns: 1fr; }
    .user-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .header-actions { width: 100%; }
    .header-actions .btn { flex: 1; justify-content: center; }
    .user-kpi-grid { grid-template-columns: 1fr; }
    .pro-detail-grid { grid-template-columns: 1fr; }
}
</style>

<script>
function copyToClipboard(text, label) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.getElementById('copyToast');
        toast.innerHTML = `<i class="fas fa-check-circle" style="color:#4ADE80;"></i> Copied ${label}: <strong>${text}</strong>`;
        toast.classList.add('show');
        setTimeout(() => { toast.classList.remove('show'); }, 3000);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($user['geofence_enabled']) && !empty($user['geofence_lat']) && !empty($user['geofence_lng'])): ?>
    const mapEl = document.getElementById('geofencePreviewMap');
    if (mapEl && typeof L !== 'undefined') {
        const lat = <?php echo (float)$user['geofence_lat']; ?>;
        const lng = <?php echo (float)$user['geofence_lng']; ?>;
        const radius = <?php echo (int)($user['geofence_radius_m'] ?? 500); ?>;
        
        const previewMap = L.map('geofencePreviewMap', {
            zoomControl: false,
            attributionControl: false
        }).setView([lat, lng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(previewMap);
        
        L.marker([lat, lng]).addTo(previewMap);
        L.circle([lat, lng], {
            radius: radius,
            color: '#207027',
            fillColor: '#207027',
            fillOpacity: 0.15
        }).addTo(previewMap);
    }
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
