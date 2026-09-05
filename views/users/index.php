<?php
$title = 'User Management';
$active = 'users';

// Define BASE_URL if not already defined
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $basePath = preg_replace('/\/views\/users$/', '', $scriptPath);
    define('BASE_URL', $protocol . $host . $basePath);
}

// If accessed directly without router, redirect to router URL
if (!isset($users)) {
    header('Location: ' . BASE_URL . '/users');
    exit;
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Check if user should see Add New User button
$showAddButton = false;
if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true) {
    $showAddButton = true;
} elseif (function_exists('Auth') && Auth::can('users.manage')) {
    $showAddButton = true;
} elseif (isset($_SESSION['roles']) && is_array($_SESSION['roles'])) {
    foreach ($_SESSION['roles'] as $role) {
        if (stripos($role, 'Admin') !== false || stripos($role, 'Super') !== false) {
            $showAddButton = true;
            break;
        }
    }
}

// Stats calculation
$totalUsers = count($users);
$activeUsers = 0;
$inactiveUsers = 0;
$twoFactorCount = 0;

foreach ($users as $u) {
    if (!empty($u['is_active'])) {
        $activeUsers++;
    } else {
        $inactiveUsers++;
    }
    if (!empty($u['two_factor_enabled'])) {
        $twoFactorCount++;
    }
}

// One-shot reveal of a just-generated password
$generatedPassword = Session::get('generated_password');
$generatedPasswordFor = Session::get('generated_password_for');
if ($generatedPassword) {
    Session::remove('generated_password');
    Session::remove('generated_password_for');
}
?>

<?php if ($generatedPassword): ?>
<div class="password-reveal-overlay" id="passwordRevealOverlay">
    <div class="password-reveal-box">
        <div class="reveal-icon-circle">
            <i class="fas fa-key"></i>
        </div>
        <h3>New Password Generated</h3>
        <p>Temporary password for <strong><?php echo htmlspecialchars($generatedPasswordFor ?? ''); ?></strong>:</p>
        <div class="password-reveal-value">
            <code id="generatedPasswordText"><?php echo htmlspecialchars($generatedPassword); ?></code>
            <button type="button" onclick="copyGeneratedPassword()" id="copyGeneratedPasswordBtn">
                <i class="fas fa-copy"></i> Copy
            </button>
        </div>
        <p class="password-reveal-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            This will not be shown again — copy it now and hand it to the user securely. They will be required to change it and set up Google Authenticator upon login.
        </p>
        <button type="button" class="btn btn-success reveal-done-btn" onclick="document.getElementById('passwordRevealOverlay').remove()">
            <i class="fas fa-check"></i> Done
        </button>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid user-mgmt-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-users-gear"></i>
                User Management
            </h1>
            <p>Manage NIS system user accounts, command deployments, and security permissions</p>
        </div>
        <div class="header-actions">
            <?php if (function_exists('Auth') && Auth::can('reports.export')): ?>
            <button class="btn btn-outline" onclick="exportUsers()">
                <i class="fas fa-file-export"></i> Export CSV
            </button>
            <?php endif; ?>
            
            <?php if ($showAddButton): ?>
            <a href="<?php echo BASE_URL; ?>/users/create" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="user-stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-total">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label">Total Users</span>
                <span class="stat-number"><?php echo $totalUsers; ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-active">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label">Active Accounts</span>
                <span class="stat-number"><?php echo $activeUsers; ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-inactive">
                <i class="fas fa-user-slash"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label">Inactive Accounts</span>
                <span class="stat-number"><?php echo $inactiveUsers; ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-2fa">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div class="stat-details">
                <span class="stat-label">2FA Enrolled</span>
                <span class="stat-number"><?php echo $twoFactorCount; ?></span>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleUserFilters()">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                <span>Filter & Search Users</span>
            </div>
            <div class="filter-toggle-icon">
                <i class="fas fa-chevron-down" id="filterChevron"></i>
            </div>
        </div>
        <div class="filter-body" id="filterBody">
            <div class="filter-grid">
                <div class="filter-item">
                    <label for="searchUsers">Search Query</label>
                    <div class="search-input-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchUsers" oninput="filterUsersDirectory()" onkeyup="filterUsersDirectory()" placeholder="Search by service no, name, email, rank, command..." autocomplete="off">
                    </div>
                </div>
                <div class="filter-item">
                    <label for="filterStatus">Account Status</label>
                    <select id="filterStatus" onchange="filterUsersDirectory()">
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
            </div>
            <div class="filter-footer">
                <button type="button" onclick="clearUserFilters()" class="btn-clear-filter">
                    <i class="fas fa-rotate-left"></i> Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="content-card">
        <div class="section-title">
            <div class="title-left">
                <h2><i class="fas fa-list-check"></i> System Users Directory</h2>
            </div>
            <div class="title-right">
                <span class="badge-counter" id="userCountBadge">
                    <i class="fas fa-user"></i> Showing <?php echo count($users); ?> users
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>No Users Found</h3>
                    <p>There are no registered system users in the database yet.</p>
                    <?php if ($showAddButton): ?>
                    <a href="<?php echo BASE_URL; ?>/users/create" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add First User
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table class="asset-table" id="usersTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">S/N</th>
                        <th>User Identity</th>
                        <th>NIS No & Rank</th>
                        <th>Contact Details</th>
                        <th>Command & Zone</th>
                        <th>Assigned Roles</th>
                        <th>Last Login</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user): 
                        $initials = '';
                        $nameParts = explode(' ', trim($user['full_name'] ?? 'U'));
                        foreach (array_slice($nameParts, 0, 2) as $np) {
                            $initials .= strtoupper(substr($np, 0, 1));
                        }
                        $baseDir = defined('BASE_PATH') ? BASE_PATH : (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2));
                        $userAvatar = !empty($user['profile_image']) && file_exists($baseDir . '/' . $user['profile_image'])
                            ? BASE_URL . '/' . htmlspecialchars($user['profile_image'])
                            : null;
                    ?>
                    <tr>
                        <td>
                            <span class="sn-pill"><?php echo $index + 1; ?></span>
                        </td>
                        <td>
                            <div class="user-identity-cell">
                                <?php if ($userAvatar): ?>
                                    <img src="<?php echo $userAvatar; ?>" alt="Avatar" class="user-avatar-initials" style="object-fit: cover; padding: 0;">
                                <?php else: ?>
                                    <div class="user-avatar-initials">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="user-identity-info">
                                    <strong class="user-username"><?php echo htmlspecialchars($user['username'] ?? ''); ?></strong>
                                    <span class="user-fullname"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="rank-nis-cell">
                                <span class="nis-no-text"><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($user['nis_number'] ?? 'N/A'); ?></span>
                                <span class="badge-rank">
                                    <?php echo htmlspecialchars($user['rank'] ?? 'Unranked'); ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="contact-info-cell">
                                <span class="contact-line"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span>
                                <span class="contact-line"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="formation-cell">
                                <span class="command-text"><i class="fas fa-building"></i> <?php echo htmlspecialchars($user['command_name'] ?? 'HQ / Unassigned'); ?></span>
                                <span class="zone-text"><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($user['zone_name'] ?? 'Zonal N/A'); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="roles-cell">
                                <?php 
                                if (!empty($user['role_names'])) {
                                    $roleNames = explode(', ', $user['role_names']);
                                    foreach (array_slice($roleNames, 0, 2) as $role):
                                        $badgeClass = 'role-badge-default';
                                        if (stripos($role, 'Super Admin') !== false) $badgeClass = 'role-badge-danger';
                                        elseif (stripos($role, 'Admin') !== false) $badgeClass = 'role-badge-primary';
                                        elseif (stripos($role, 'Sectional') !== false) $badgeClass = 'role-badge-warning';
                                        elseif (stripos($role, 'Vetting') !== false) $badgeClass = 'role-badge-info';
                                ?>
                                    <span class="role-badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($role); ?>
                                    </span>
                                <?php 
                                    endforeach;
                                    if (count($roleNames) > 2) {
                                        echo '<span class="role-badge role-badge-more">+' . (count($roleNames) - 2) . '</span>';
                                    }
                                } else {
                                    echo '<span class="text-muted" style="font-size:0.8rem;">No roles</span>';
                                }
                                ?>
                            </div>
                        </td>
                        <td>
                            <div class="login-time-cell">
                                <?php if (!empty($user['last_login'])): ?>
                                    <span class="login-date"><i class="fas fa-clock"></i> <?php echo date('d M Y', strtotime($user['last_login'])); ?></span>
                                    <span class="login-hour"><?php echo date('h:i A', strtotime($user['last_login'])); ?></span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.8rem;"><i class="fas fa-circle-minus"></i> Never</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($user['is_active'] ?? false): ?>
                                <span class="status-badge status-active">
                                    <span class="status-dot"></span> Active
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">
                                    <span class="status-dot"></span> Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btn-group">
                                <a href="<?php echo BASE_URL; ?>/users/show/<?php echo $user['id']; ?>" 
                                   class="action-btn btn-view" title="View Officer Profile">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($showAddButton): ?>
                                <a href="<?php echo BASE_URL; ?>/users/edit/<?php echo $user['id']; ?>" 
                                   class="action-btn btn-edit" title="Edit User Account">
                                    <i class="fas fa-user-pen"></i>
                                </a>
                                <?php endif; ?>

                                <?php if ($showAddButton && ($user['id'] ?? 0) != ($_SESSION['user_id'] ?? 0)): ?>
                                    <a href="<?php echo BASE_URL; ?>/users/toggle-status/<?php echo $user['id']; ?>"
                                       class="action-btn <?php echo ($user['is_active'] ?? false) ? 'btn-status-toggle warning' : 'btn-status-toggle success'; ?>"
                                       title="<?php echo ($user['is_active'] ?? false) ? 'Deactivate User' : 'Activate User'; ?>"
                                       onclick="return confirm('Are you sure you want to <?php echo ($user['is_active'] ?? false) ? 'deactivate' : 'activate'; ?> this user?')">
                                        <i class="fas fa-<?php echo ($user['is_active'] ?? false) ? 'user-slash' : 'user-check'; ?>"></i>
                                    </a>
                                    
                                    <a href="<?php echo BASE_URL; ?>/users/reset-password/<?php echo $user['id']; ?>"
                                       class="action-btn btn-key reset-password" title="Reset User Password"
                                       onclick="return confirm('Generate a new temporary password for &quot;<?php echo htmlspecialchars(addslashes($user['username'] ?? '')); ?>&quot;? They will be required to change it on their next login.')">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    
                                    <a href="<?php echo BASE_URL; ?>/users/reset-2fa/<?php echo $user['id']; ?>"
                                       class="action-btn btn-totp reset-2fa" title="<?php echo !empty($user['two_factor_enabled']) ? 'Reset Google Authenticator' : 'Google Authenticator is not enabled for this user'; ?>"
                                       onclick="return confirm('Reset Google Authenticator for &quot;<?php echo htmlspecialchars(addslashes($user['username'] ?? '')); ?>&quot;? They will be able to log in with just their password and can re-enrol from their profile.')">
                                        <i class="fas fa-mobile-screen-button"></i>
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

<style>
/* User Management Master Styling */
.user-mgmt-wrapper {
    padding-bottom: 30px;
}

/* Page Header Enhancements */
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
    font-weight: 600;
    color: var(--primary-color, #134617);
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-header .header-content p {
    font-size: 0.9rem;
    color: var(--text-secondary, #53665E);
    margin: 0;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Quick Stats Bar */
.user-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.stat-card {
    background: var(--surface, #ffffff);
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 8px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.icon-total { background: #e8f5e9; color: #134617; }
.icon-active { background: #e8f5e9; color: #207027; }
.icon-inactive { background: #fee2e2; color: #b91c1c; }
.icon-2fa { background: #e0f2fe; color: #0369a1; }

.stat-details {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-secondary, #53665E);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-number {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--text-primary, #212529);
    line-height: 1.2;
}

/* Filter Card */
.filter-section {
    background: var(--surface, #ffffff);
    border-radius: 8px;
    border: 1px solid var(--border-color, #D7E3DC);
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    overflow: hidden;
}

.filter-header {
    padding: 12px 18px;
    background: var(--light-bg, #F7FAF8);
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    border-bottom: 1px solid var(--border-color, #D7E3DC);
    transition: background 0.2s;
}

.filter-header:hover {
    background: #edf3ee;
}

.filter-title {
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--text-primary, #212529);
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-title i {
    color: var(--primary-light, #207027);
}

.filter-toggle-icon i {
    color: var(--text-secondary, #53665E);
    transition: transform 0.3s;
}

.filter-body {
    padding: 16px 18px;
}

.filter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 12px;
}

.filter-item label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-secondary, #53665E);
    margin-bottom: 6px;
}

.search-input-box {
    position: relative;
    width: 100%;
}

.search-input-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #8fa097;
    font-size: 0.85rem;
}

.search-input-box input {
    width: 100%;
    padding: 9px 12px 9px 34px;
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 6px;
    font-size: 0.9rem;
    background: var(--surface, #ffffff);
    color: var(--text-primary, #212529);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input-box input:focus, .filter-item select:focus {
    border-color: var(--primary-light, #207027);
    box-shadow: 0 0 0 3px rgba(32, 112, 39, 0.15);
}

.filter-item select {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 6px;
    font-size: 0.9rem;
    background: var(--surface, #ffffff);
    color: var(--text-primary, #212529);
    outline: none;
}

.filter-footer {
    display: flex;
    justify-content: flex-end;
}

.btn-clear-filter {
    padding: 6px 14px;
    font-size: 0.82rem;
    font-weight: 600;
    border: 1px solid var(--border-color, #D7E3DC);
    background: var(--surface, #ffffff);
    color: var(--text-secondary, #53665E);
    border-radius: 5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-clear-filter:hover {
    background: #eef3ef;
    color: var(--primary-color, #134617);
}

/* Content Card & Table Styles */
.content-card {
    background: var(--surface, #ffffff);
    border-radius: 8px;
    border: 1px solid var(--border-color, #D7E3DC);
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    overflow: hidden;
}

.content-card .section-title {
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color, #D7E3DC);
    background: var(--surface, #ffffff);
}

.content-card .section-title h2 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary, #212529);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.badge-counter {
    background: #e8f5e9;
    color: #134617;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.table-responsive {
    overflow-x: auto;
}

.asset-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}

.asset-table thead tr {
    background: #f4f8f5;
    border-bottom: 2px solid var(--border-color, #D7E3DC);
}

.asset-table thead th {
    padding: 12px 14px;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-secondary, #53665E);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: left;
    white-space: nowrap;
}

.asset-table tbody tr {
    border-bottom: 1px solid var(--border-color, #D7E3DC);
    transition: background 0.15s;
}

.asset-table tbody tr:hover {
    background: #f9fbf9;
}

.asset-table td {
    padding: 12px 14px;
    vertical-align: middle;
    color: var(--text-primary, #212529);
}

/* User Identity Cell */
.sn-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #f0f4f1;
    color: var(--text-secondary, #53665E);
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 600;
}

.user-identity-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar-initials {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: linear-gradient(135deg, #134617 0%, #207027 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 2px 5px rgba(19, 70, 23, 0.2);
}

.user-identity-info {
    display: flex;
    flex-direction: column;
}

.user-username {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--primary-color, #134617);
}

.user-fullname {
    font-size: 0.8rem;
    color: var(--text-secondary, #53665E);
}

/* NIS & Rank Cell */
.rank-nis-cell {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.nis-no-text {
    font-size: 0.8rem;
    color: var(--text-secondary, #53665E);
    display: flex;
    align-items: center;
    gap: 5px;
}

.badge-rank {
    display: inline-block;
    padding: 2px 8px;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    width: fit-content;
}

/* Contact Info Cell */
.contact-info-cell {
    display: flex;
    flex-direction: column;
    gap: 3px;
    font-size: 0.8rem;
    color: var(--text-secondary, #53665E);
}

.contact-line {
    display: flex;
    align-items: center;
    gap: 6px;
}

.contact-line i {
    color: #8fa097;
    font-size: 0.75rem;
    width: 12px;
}

/* Formation Cell */
.formation-cell {
    display: flex;
    flex-direction: column;
    gap: 3px;
    font-size: 0.8rem;
}

.command-text {
    font-weight: 600;
    color: var(--text-primary, #212529);
    display: flex;
    align-items: center;
    gap: 5px;
}

.command-text i {
    color: var(--primary-light, #207027);
    font-size: 0.75rem;
}

.zone-text {
    color: var(--text-secondary, #53665E);
    display: flex;
    align-items: center;
    gap: 5px;
}

.zone-text i {
    color: #a0aec0;
    font-size: 0.75rem;
}

/* Roles Cell */
.roles-cell {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 0.72rem;
    font-weight: 600;
}

.role-badge-primary { background: #e8f5e9; color: #134617; border: 1px solid #c8e6c9; }
.role-badge-danger { background: #ffebee; color: #b71c1c; border: 1px solid #ffcdd2; font-weight: 700; }
.role-badge-warning { background: #fff8e1; color: #b45309; border: 1px solid #fef3c7; }
.role-badge-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.role-badge-default { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.role-badge-more { background: #e2e8f0; color: #334155; font-size: 0.7rem; }

/* Login Time Cell */
.login-time-cell {
    display: flex;
    flex-direction: column;
    font-size: 0.8rem;
}

.login-date {
    font-weight: 600;
    color: var(--text-primary, #212529);
    display: flex;
    align-items: center;
    gap: 5px;
}

.login-date i {
    color: #8fa097;
    font-size: 0.75rem;
}

.login-hour {
    font-size: 0.75rem;
    color: var(--text-secondary, #53665E);
    margin-left: 17px;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active {
    background: #e8f5e9;
    color: #1b5e20;
}

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
}

.status-active .status-dot {
    background: #2e7d32;
    box-shadow: 0 0 0 2px rgba(46, 125, 50, 0.25);
}

.status-inactive .status-dot {
    background: #dc2626;
}

/* Action Icon Buttons */
.action-btn-group {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    color: var(--text-secondary, #53665E);
    background: var(--light-bg, #F7FAF8);
    border: 1px solid var(--border-color, #D7E3DC);
    text-decoration: none;
    font-size: 0.82rem;
    transition: all 0.2s;
    cursor: pointer;
}

.action-btn:hover {
    transform: translateY(-1px);
}

.action-btn.btn-view:hover { background: #e0f2fe; color: #0284c7; border-color: #bae6fd; }
.action-btn.btn-edit:hover { background: #e8f5e9; color: #16a34a; border-color: #bbf7d0; }
.action-btn.btn-key:hover { background: #fef3c7; color: #d97706; border-color: #fde68a; }
.action-btn.btn-totp:hover { background: #ccfbf1; color: #0d9488; border-color: #99f6e4; }
.action-btn.btn-status-toggle.warning:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
.action-btn.btn-status-toggle.success:hover { background: #e8f5e9; color: #16a34a; border-color: #bbf7d0; }

/* Buttons General */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
}

.btn-success {
    background: var(--primary-light, #207027);
    color: white;
}

.btn-success:hover {
    background: var(--primary-color, #134617);
    box-shadow: 0 3px 8px rgba(19, 70, 23, 0.25);
    transform: translateY(-1px);
    color: white;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--primary-light, #207027);
    color: var(--primary-light, #207027);
}

.btn-outline:hover {
    background: #e8f5e9;
    color: var(--primary-color, #134617);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 45px 20px;
}

.empty-icon {
    font-size: 40px;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.empty-state h3 {
    font-size: 1.1rem;
    color: var(--text-primary, #212529);
    margin: 0 0 6px 0;
}

.empty-state p {
    font-size: 0.88rem;
    color: var(--text-secondary, #53665E);
    margin: 0 0 16px 0;
}

/* Password Reveal Modal */
.password-reveal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 15px;
}

.password-reveal-box {
    background: var(--surface, #ffffff);
    border-radius: 12px;
    padding: 26px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 45px rgba(0,0,0,0.3);
    text-align: center;
    border: 1px solid rgba(255,255,255,0.2);
    animation: revealPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes revealPop {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.reveal-icon-circle {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #e8f5e9;
    color: var(--primary-light, #207027);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin: 0 auto 14px;
}

.password-reveal-box h3 {
    margin: 0 0 8px 0;
    color: var(--primary-color, #134617);
    font-size: 1.2rem;
    font-weight: 700;
}

.password-reveal-box p {
    margin: 0 0 14px 0;
    color: var(--text-primary, #212529);
    font-size: 0.9rem;
}

.password-reveal-value {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--light-bg, #F7FAF8);
    border: 1px solid var(--border-color, #D7E3DC);
    border-radius: 6px;
    padding: 10px 14px;
    margin-bottom: 14px;
}

.password-reveal-value code {
    flex: 1;
    text-align: left;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: var(--primary-color, #134617);
    word-break: break-all;
}

.password-reveal-value button {
    background: var(--primary-light, #207027);
    color: white;
    border: none;
    border-radius: 5px;
    padding: 6px 12px;
    cursor: pointer;
    font-size: 0.82rem;
    font-weight: 600;
    white-space: nowrap;
    transition: background 0.2s;
}

.password-reveal-value button:hover {
    background: var(--primary-color, #134617);
}

.password-reveal-warning {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 6px;
    padding: 10px;
    color: #b45309 !important;
    font-size: 0.8rem !important;
    text-align: left;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    line-height: 1.35;
    margin-bottom: 18px !important;
}

.reveal-done-btn {
    width: 100%;
    justify-content: center;
    padding: 10px;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .user-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .header-actions {
        width: 100%;
    }
    .header-actions .btn {
        flex: 1;
        justify-content: center;
    }
    .user-stats-grid {
        grid-template-columns: 1fr;
    }
    .filter-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function copyGeneratedPassword() {
    const text = document.getElementById('generatedPasswordText')?.textContent || '';
    const btn = document.getElementById('copyGeneratedPasswordBtn');
    const done = () => {
        if (!btn) return;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied';
        setTimeout(() => { btn.innerHTML = original; }, 1500);
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done).catch(() => {});
    } else {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        document.body.removeChild(ta);
    }
}

function exportUsers() {
    window.location.href = '<?php echo BASE_URL; ?>/users/export';
}

function toggleUserFilters() {
    const body = document.getElementById('filterBody');
    const header = document.querySelector('.filter-header');
    const chevron = document.getElementById('filterChevron');
    
    if (!body) return;
    if (body.style.display === 'none' || body.style.display === '') {
        body.style.display = 'block';
        if (header) header.classList.add('active');
        if (chevron) chevron.style.transform = 'rotate(180deg)';
    } else {
        body.style.display = 'none';
        if (header) header.classList.remove('active');
        if (chevron) chevron.style.transform = '';
    }
}

function clearUserFilters() {
    const s = document.getElementById('searchUsers');
    const f = document.getElementById('filterStatus');
    if (s) s.value = '';
    if (f) f.value = '';
    filterUsersDirectory();
}

// Search and filter functionality
function filterUsersDirectory() {
    const searchInput = document.getElementById('searchUsers');
    const filterSelect = document.getElementById('filterStatus');
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    const rawSearch = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const searchWords = rawSearch ? rawSearch.split(/\s+/).filter(Boolean) : [];
    const status = filterSelect ? filterSelect.value : '';
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const rows = tbody.querySelectorAll('tr:not(.no-filter-match)');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const text = (row.innerText || row.textContent || '').toLowerCase();
        const statusBadge = row.querySelector('.status-badge');
        const isActive = statusBadge ? statusBadge.classList.contains('status-active') : false;
        
        let show = true;
        
        // Multi-term search match
        if (searchWords.length > 0) {
            const matchesAll = searchWords.every(word => text.includes(word));
            if (!matchesAll) {
                show = false;
            }
        }
        
        // Status filter
        if (status && show) {
            if (status === 'active' && !isActive) show = false;
            if (status === 'inactive' && isActive) show = false;
        }
        
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    let noMatchRow = tbody.querySelector('.no-filter-match');
    if (visibleCount === 0) {
        if (!noMatchRow) {
            noMatchRow = document.createElement('tr');
            noMatchRow.className = 'no-filter-match';
            noMatchRow.innerHTML = '<td colspan="9" style="text-align:center; padding: 36px 16px; color: #64748b;"><i class="fas fa-search" style="font-size: 1.6rem; margin-bottom: 8px; display: block; color: #94a3b8;"></i><strong style="font-size: 1rem; color: #334155;">No matching users found</strong><br><span style="font-size: 0.85rem; color: #64748b;">Try adjusting your search query or reset filters.</span></td>';
            tbody.appendChild(noMatchRow);
        } else {
            noMatchRow.style.display = '';
        }
    } else if (noMatchRow) {
        noMatchRow.style.display = 'none';
    }

    const countBadge = document.getElementById('userCountBadge');
    if (countBadge) {
        countBadge.innerHTML = '<i class="fas fa-user"></i> Showing ' + visibleCount + ' users';
    }
}

// Export to window
window.filterUsersDirectory = filterUsersDirectory;
window.clearUserFilters = clearUserFilters;
window.toggleUserFilters = toggleUserFilters;

function initUserFilters() {
    const searchInput = document.getElementById('searchUsers');
    const filterSelect = document.getElementById('filterStatus');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterUsersDirectory);
        searchInput.addEventListener('keyup', filterUsersDirectory);
        searchInput.addEventListener('search', filterUsersDirectory);
    }
    
    if (filterSelect) {
        filterSelect.addEventListener('change', filterUsersDirectory);
    }
    
    const filterBody = document.getElementById('filterBody');
    if (filterBody) {
        if (window.innerWidth <= 768) {
            filterBody.style.display = 'none';
        } else {
            filterBody.style.display = 'block';
            const filterHeader = document.querySelector('.filter-header');
            if (filterHeader) filterHeader.classList.add('active');
            const chevron = document.getElementById('filterChevron');
            if (chevron) chevron.style.transform = 'rotate(180deg)';
        }
    }

    // Run once on init
    filterUsersDirectory();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUserFilters);
} else {
    initUserFilters();
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>