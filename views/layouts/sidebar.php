<?php
/**
 * Sidebar Layout with Logo
 * 
 * Variables expected:
 * $active - Active menu item
 * $user - Current user data
 */

// Ensure Auth class exists
if (!class_exists('Auth')) {
    class Auth {
        public static function check() { return isset($_SESSION['user_id']); }
        public static function can($perm) { return true; }
        public static function canAny($perms) { return true; }
    }
}

// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/nis_ams');
}

// Get user data
$userName = $_SESSION['full_name'] ?? 'User';
$userRole = $_SESSION['roles'][0] ?? 'Staff';
$isSuperAdmin = isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true;
$userSessionRoles = $_SESSION['roles'] ?? [];
$isArmorer = in_array('Armorer', $userSessionRoles)
          || in_array('Command Armorer', $userSessionRoles)
          || in_array('HQ Armorer', $userSessionRoles);
$isHQArmorer = in_array('HQ Armorer', $userSessionRoles)
             || in_array('Armorer', $userSessionRoles);

$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

// Which single sidebar section should be expanded.
//
// This used to be five independent checks, each falling back to
// "does this substring appear anywhere in the URL". That meant a page
// like /reports/weapons — which legitimately contains the word
// "weapons" — satisfied BOTH the Reports section's check and the
// Weapons Management section's check, so both dropdowns sprang open
// at once instead of just Reports. Resolve to exactly one section
// instead: prefer the page's own declared $active value (every view
// sets one), and only fall back to guessing from the URL when a page
// hasn't set $active at all.
$sidebarSections = [
    'asset'    => ['asset', 'land', 'buildings', 'rented', 'movable', 'ict', 'projects'],
    'fleet'    => ['fleet', 'fleet-dashboard', 'vehicles', 'aircraft', 'marine', 'motorcycles'],
    'weapons'  => ['weapons', 'ammunition', 'requisitions', 'returns', 'weapon_issue', 'audit', 'weapons-dashboard', 'ammunition-dashboard'],
    'reports'  => ['reports', 'asset-reports', 'weapon-reports', 'ammo-reports', 'audit-history'],
    'admin'    => ['admin', 'users', 'settings'],
];

$activeSection = null;
if (isset($active)) {
    foreach ($sidebarSections as $sectionKey => $values) {
        if (in_array($active, $values, true)) {
            $activeSection = $sectionKey;
            break;
        }
    }
}

if ($activeSection === null) {
    // Legacy fallback for any page that never set $active — first
    // matching section wins, checked most-specific first.
    if (strpos($currentUri, '/reports') !== false || strpos($currentUri, '/audit/history') !== false) {
        $activeSection = 'reports';
    } elseif (preg_match('#/(land|buildings|rented|movable|ict|projects)(/|$)#i', $currentUri)) {
        $activeSection = 'asset';
    } elseif (strpos($currentUri, '/fleet') !== false) {
        $activeSection = 'fleet';
    } elseif (preg_match('#/(weapons|ammunition|requisition|returns|weapon_issue|audit)(/|$)#i', $currentUri)) {
        $activeSection = 'weapons';
    } elseif (preg_match('#/(users|settings)(/|$)#i', $currentUri)) {
        $activeSection = 'admin';
    }
}

$isAssetActive = $activeSection === 'asset';
$isFleetActive = $activeSection === 'fleet';
$isWeaponsActive = $activeSection === 'weapons';
$isReportsActive = $activeSection === 'reports';
$isAdminActive = $activeSection === 'admin';
?>
<!-- Sidebar -->
<aside class="sidebar">
    
    

    <!-- Navigation Menu -->
    <nav class="sidebar-menu">
        <?php
        // CGIS used to get a stripped-down 4-link menu here ("Analytical
        // Dashboards Only"). CGIS is now meant to see everything in the
        // system (read-only, enforced in Auth::can()) except User
        // Management/System Settings, so it goes through the same full menu
        // as everyone else below — each section already gates itself on
        // Auth::can(), which now correctly grants CGIS view access
        // everywhere those checks appear. Condition kept (rather than
        // deleting the if/else) so this block's structure — and the
        // matching endif count further down — doesn't have to be touched.
        ?>
        <?php if (false): ?>
        <?php else: ?>
        <!-- Dashboard -->
        <a href="<?php echo BASE_URL; ?>/dashboard" class="menu-item <?php echo ($active === 'dashboard') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span class="menu-text">Dashboard</span>
        </a>
        
        <?php if (!$isArmorer): ?>
        <!-- Asset Management Dropdown -->
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle <?php echo $isAssetActive ? 'open' : ''; ?>" onclick="toggleDropdown('assetDropdown')">
                <i class="fas fa-boxes"></i>
                <span class="menu-text">Asset Management</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="assetDropdown" style="display: <?php echo $isAssetActive ? 'block' : 'none'; ?>;">
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('land.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/land" class="sub-item <?php echo ($active === 'land' || strpos($currentUri, '/land') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-map-marked-alt"></i>
                    <span class="menu-text">Land Assets</span>
                </a>
                <?php endif; ?>
                
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('building.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/buildings" class="sub-item <?php echo ($active === 'buildings' || strpos($currentUri, '/buildings') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span class="menu-text">Building Assets</span>
                </a>
                <?php endif; ?>
                
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('rented.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/rented" class="sub-item <?php echo ($active === 'rented' || strpos($currentUri, '/rented') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-house-user"></i>
                    <span class="menu-text">Rented Properties</span>
                </a>
                <?php endif; ?>
                
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('movable.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/movable" class="sub-item <?php echo ($active === 'movable' || strpos($currentUri, '/movable') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i>
                    <span class="menu-text">Movable Assets</span>
                </a>
                <?php endif; ?>
                
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('ict.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/ict" class="sub-item <?php echo ($active === 'ict' || strpos($currentUri, '/ict') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-server"></i>
                    <span class="menu-text">ICT Assets</span>
                </a>
                <?php endif; ?>
                
                <a href="<?php echo BASE_URL; ?>/projects" class="sub-item <?php echo ($active === 'projects' || strpos($currentUri, '/projects') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-hard-hat"></i>
                    <span class="menu-text">Ongoing Projects</span>
                </a>
            </div>
        </div>
        
        <!-- Fleet Management Dropdown - FIXED VERSION -->
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle <?php echo $isFleetActive ? 'open' : ''; ?>" onclick="toggleDropdown('fleetDropdown')">
                <i class="fas fa-car"></i>
                <span class="menu-text">Fleet Management</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="fleetDropdown" style="display: <?php echo $isFleetActive ? 'block' : 'none'; ?>;">
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('fleet.view'))): ?>
                <!-- Fleet Dashboard -->
                <a href="<?php echo BASE_URL; ?>/fleet/dashboard" class="sub-item <?php echo ($active === 'fleet-dashboard' || strpos($currentUri, '/fleet/dashboard') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-text">Fleet Dashboard</span>
                </a>
                
                <!-- Vehicles -->
                <a href="<?php echo BASE_URL; ?>/fleet/vehicles" class="sub-item <?php echo ($active === 'vehicles' || strpos($currentUri, '/fleet/vehicles') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-car"></i>
                    <span class="menu-text">Vehicles</span>
                </a>
                
                <!-- Aircraft -->
                <a href="<?php echo BASE_URL; ?>/fleet/aircraft" class="sub-item <?php echo ($active === 'aircraft' || strpos($currentUri, '/fleet/aircraft') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-helicopter"></i>
                    <span class="menu-text">Aircraft</span>
                </a>
                
                <!-- Marine -->
                <a href="<?php echo BASE_URL; ?>/fleet/marine" class="sub-item <?php echo ($active === 'marine' || strpos($currentUri, '/fleet/marine') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-ship"></i>
                    <span class="menu-text">Marine</span>
                </a>
                
                <!-- Motorcycles -->
                <a href="<?php echo BASE_URL; ?>/fleet/motorcycles" class="sub-item <?php echo ($active === 'motorcycles' || strpos($currentUri, '/fleet/motorcycles') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-motorcycle"></i>
                    <span class="menu-text">Motorcycles</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
        // Weapons Management is restricted to exactly: Super Admin Officer,
        // admin, HQ Armorer, Armorer, Command Armorer — the same 5 roles
        // that hold weapons.*/ammunition.* permissions (see the
        // 2026_08_29_000003 migration). Gating the whole container on these
        // (rather than just each sub-item) means the dropdown itself
        // doesn't appear as an empty shell for every other role.
        //
        // Armorer-type roles never actually reach this block: they get
        // their own, more detailed "======== ARMORER MENU ========" section
        // just below (Weapons / Ammunition / Requisitions / Returns /
        // Reports, each pre-scoped to "My Command" vs "Service-Wide"). This
        // container is missing the !$isArmorer guard every other duplicated
        // section here has (see Asset Management above, Reports below), so
        // an Armorer saw BOTH: this generic "Weapons Management" dropdown
        // *and* their own dedicated menu, linking to overlapping pages.
        $canSeeWeaponsSection = !$isArmorer
            && ($isSuperAdmin
                || (class_exists('Auth') && (Auth::can('weapons.view') || Auth::can('ammunition.view'))));
        ?>
        <?php if ($canSeeWeaponsSection): ?>
        <!-- Weapons Management Dropdown -->
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle <?php echo $isWeaponsActive ? 'open' : ''; ?>" onclick="toggleDropdown('weaponDropdown')">
                <i class="fas fa-gun"></i>
                <span class="menu-text">Weapons Management</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="weaponDropdown" style="display: <?php echo $isWeaponsActive ? 'block' : 'none'; ?>;">
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('weapons.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/weapons" class="sub-item <?php echo ($active === 'weapons' || (strpos($currentUri, '/reports') === false && (preg_match('#/weapons(/|$)#i', $currentUri) && strpos($currentUri, '/weapons/dashboard') === false))) ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="menu-text">Weapons Inventory</span>
                </a>
                <?php endif; ?>

                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('ammunition.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/ammunition" class="sub-item <?php echo ($active === 'ammunition' || (strpos($currentUri, '/reports') === false && (preg_match('#/ammunition(/|$)#i', $currentUri) && strpos($currentUri, '/ammunition/dashboard') === false))) ? 'active' : ''; ?>">
                    <i class="fas fa-bullseye"></i>
                    <span class="menu-text">Ammunition</span>
                </a>
                <?php endif; ?>

                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('requisition.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/requisition" class="sub-item <?php echo ($active === 'requisitions' || strpos($currentUri, '/requisition') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="menu-text">Requisition</span>
                </a>
                <?php endif; ?>

                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('returns.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/returns" class="sub-item <?php echo ($active === 'returns' || strpos($currentUri, '/returns') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-undo-alt"></i>
                    <span class="menu-text">Returns</span>
                </a>
                <?php endif; ?>

                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('weapons.edit'))): ?>
                <a href="<?php echo BASE_URL; ?>/weapon_issue" class="sub-item <?php echo ($active === 'weapon_issue' || strpos($currentUri, '/weapon_issue') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="menu-text">Weapon Issue</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isArmorer): ?>
        <!-- ======== ARMORER MENU ======== -->
        <!-- Weapons Inventory -->
        <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('weapons.view'))): ?>
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle" onclick="toggleDropdown('armorerWeaponsDropdown')">
                <i class="fas fa-shield-alt"></i>
                <span class="menu-text">Weapons Dashboard</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="armorerWeaponsDropdown">
                <a href="<?php echo BASE_URL; ?>/weapons" class="sub-item <?php echo ($active === 'weapons') ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span class="menu-text"><?php echo $isHQArmorer ? 'All Weapons (Service-Wide)' : 'Command Weapons'; ?></span>
                </a>
                <!-- <a href="<?php echo BASE_URL; ?>/weapons/dashboard" class="sub-item <?php echo ($active === 'weapons-dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span class="menu-text">Weapons Dashboard</span>
                </a> -->
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('weapons.create'))): ?>
                <a href="<?php echo BASE_URL; ?>/weapons/create" class="sub-item <?php echo ($active === 'weapons-create') ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span class="menu-text">Register Weapon</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/weapon_issue" class="sub-item <?php echo ($active === 'weapon_issue') ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span class="menu-text">Issue / Return Log</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Ammunition -->
        <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('ammunition.view'))): ?>
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle" onclick="toggleDropdown('armorerAmmoDropdown')">
                <i class="fas fa-crosshairs"></i>
                <span class="menu-text">Ammunition</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="armorerAmmoDropdown">
                <a href="<?php echo BASE_URL; ?>/ammunition" class="sub-item <?php echo ($active === 'ammunition') ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span class="menu-text"><?php echo $isHQArmorer ? 'All Ammo (Service-Wide)' : 'Command Ammo'; ?></span>
                </a>
                <!-- <a href="<?php echo BASE_URL; ?>/ammunition/dashboard" class="sub-item <?php echo ($active === 'ammunition-dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span class="menu-text">Ammunition Dashboard</span>
                </a> -->
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('ammunition.create'))): ?>
                <a href="<?php echo BASE_URL; ?>/ammunition/create" class="sub-item <?php echo ($active === 'ammunition-create') ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span class="menu-text">Add Ammunition</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Requisitions Dropdown -->
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle" onclick="toggleDropdown('armorerReqDropdown')">
                <i class="fas fa-file-alt"></i>
                <span class="menu-text">Requisitions</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="armorerReqDropdown">
                <a href="<?php echo BASE_URL; ?>/requisition" class="sub-item <?php echo ($active === 'requisitions') ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span class="menu-text">Requisitions Queue</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/requisition/create" class="sub-item <?php echo ($active === 'requisition-create') ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span class="menu-text">New Requisition</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/requisition/my" class="sub-item <?php echo ($active === 'requisition-my') ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span class="menu-text">My Requisitions</span>
                </a>
            </div>
        </div>

        <!-- Returns Dropdown -->
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle" onclick="toggleDropdown('armorerReturnDropdown')">
                <i class="fas fa-undo-alt"></i>
                <span class="menu-text">Returns</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="armorerReturnDropdown">
                <a href="<?php echo BASE_URL; ?>/returns" class="sub-item <?php echo ($active === 'returns') ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span class="menu-text">Returns Log</span>
                </a>
                <!-- <a href="<?php echo BASE_URL; ?>/returns/create" class="sub-item <?php echo ($active === 'returns-create') ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span class="menu-text">Create Return</span>
                </a> -->
            </div>
        </div>

        <!-- Reports Dropdown -->
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle" onclick="toggleDropdown('armorerReportsDropdown')">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text">Reports</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="armorerReportsDropdown">
                <a href="<?php echo BASE_URL; ?>/reports/weapons" class="sub-item <?php echo ($active === 'weapon-reports') ? 'active' : ''; ?>">
                    <i class="fas fa-gun"></i>
                    <span class="menu-text">Weapons Report</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/reports/ammunition" class="sub-item <?php echo ($active === 'ammo-reports') ? 'active' : ''; ?>">
                    <i class="fas fa-bullseye"></i>
                    <span class="menu-text">Ammunition Report</span>
                </a>
            </div>
        </div>
        <!-- ======== END ARMORER MENU ======== -->
        <?php endif; ?>
        
        <?php if (!$isArmorer): ?>
        <!-- Reports Dropdown — also home to Quarterly/Audit History, so the
             container needs to open for an audit.view-only role too, not
             just reports.view holders. -->
        <?php if ($isSuperAdmin || (class_exists('Auth') && (Auth::can('reports.view') || Auth::can('audit.view')))): ?>
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle <?php echo $isReportsActive ? 'open' : ''; ?>" onclick="toggleDropdown('reportsDropdown')">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text">Reports</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="reportsDropdown" style="display: <?php echo $isReportsActive ? 'block' : 'none'; ?>;">
                <a href="<?php echo BASE_URL; ?>/reports" class="sub-item <?php echo ($active === 'asset-reports' || ($active === 'reports' && strpos($currentUri, '/reports/weapons') === false && strpos($currentUri, '/reports/ammunition') === false)) ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span class="menu-text">Asset Reports</span>
                </a>
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('weapons.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/reports/weapons" class="sub-item <?php echo ($active === 'weapon-reports' || strpos($currentUri, '/reports/weapons') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-gun"></i>
                    <span class="menu-text">Weapons Report</span>
                </a>
                <?php endif; ?>
                <?php if ($isSuperAdmin || (class_exists('Auth') && Auth::can('audit.view'))): ?>
                <a href="<?php echo BASE_URL; ?>/audit/quarterly" class="sub-item <?php echo ($active === 'audit' || $active === 'quarterly-audit' || (strpos($currentUri, '/audit') !== false && strpos($currentUri, '/audit/history') === false)) ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span class="menu-text">Quarterly Audit</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/audit/history" class="sub-item <?php echo ($active === 'audit-history' || strpos($currentUri, '/audit/history') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span class="menu-text">Audit History</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Admin Dropdown -->
        <?php
        // The 'admin' role now manages users but never settings — that
        // split lives in Auth::can()/Auth::isSuperAdmin() (which also
        // keeps the literal 'ADMIN' seed account locked out of both), so
        // this just reads those instead of re-deriving the rule here.
        $canManageUsers = $isSuperAdmin || (class_exists('Auth') && Auth::can('users.manage'));
        $canManageSettings = $isSuperAdmin || (class_exists('Auth') && Auth::isSuperAdmin());
        $canManageSessions = $isSuperAdmin || (class_exists('Auth') && Auth::can('sessions.manage'));
        ?>
        <?php if ($canManageUsers || $canManageSettings || $canManageSessions): ?>
        <div class="dropdown-group">
            <div class="menu-item dropdown-toggle <?php echo $isAdminActive ? 'open' : ''; ?>" onclick="toggleDropdown('adminDropdown')">
                <i class="fas fa-users-cog"></i>
                <span class="menu-text">Admin</span>
                <i class="fas fa-chevron-right arrow"></i>
            </div>
            <div class="dropdown-items" id="adminDropdown" style="display: <?php echo $isAdminActive ? 'block' : 'none'; ?>;">
                <?php if ($canManageUsers): ?>
                <a href="<?php echo BASE_URL; ?>/users" class="sub-item <?php echo ($active === 'users' || strpos($currentUri, '/users') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span class="menu-text">User Management</span>
                </a>
                <?php endif; ?>

                <?php if ($canManageSessions): ?>
                <a href="<?php echo BASE_URL; ?>/sessions" class="sub-item <?php echo ($active === 'sessions' || strpos($currentUri, '/sessions') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-user-clock"></i>
                    <span class="menu-text">Active Sessions</span>
                </a>
                <?php endif; ?>

                <?php if ($canManageSettings): ?>
                <a href="<?php echo BASE_URL; ?>/settings" class="sub-item <?php echo ($active === 'settings' || strpos($currentUri, '/settings') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i>
                    <span class="menu-text">System Settings</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
    </nav>

    <div class="theme-toggle-row">
        <span><i class="fas fa-moon" style="width:16px;text-align:center;margin-right:6px;"></i>Dark Mode</span>
        <button type="button" id="themeToggle" class="theme-toggle-switch" role="switch" aria-checked="false" aria-label="Toggle dark mode"></button>
    </div>
</aside>

<!-- Main Content Wrapper -->
<main class="main-content">

<script>
// Toggle dropdown function
function toggleDropdown(id) {
    var dropdown = document.getElementById(id);
    var toggle = event.currentTarget;
    
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
        toggle.classList.remove('open');
    } else {
        dropdown.style.display = 'block';
        toggle.classList.add('open');
    }
}

// Auto-expand dropdown if any child is active
document.addEventListener('DOMContentLoaded', function() {
    // Auto-expand dropdown if any child is active
    var activeItems = document.querySelectorAll('.sub-item.active');
    activeItems.forEach(function(item) {
        var dropdown = item.closest('.dropdown-items');
        if (dropdown) {
            dropdown.style.display = 'block';
            var toggle = document.querySelector('[onclick*="' + dropdown.id + '"]');
            if (toggle) toggle.classList.add('open');
        }
    });

    // User Profile Dropdown
    const adminProfileBtn = document.getElementById('adminProfileBtn');
    const adminProfileMenu = document.getElementById('adminProfileMenu');
    
    if (adminProfileBtn && adminProfileMenu) {
        adminProfileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            adminProfileBtn.classList.toggle('active');
            adminProfileMenu.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!adminProfileBtn.contains(e.target) && !adminProfileMenu.contains(e.target)) {
                adminProfileBtn.classList.remove('active');
                adminProfileMenu.classList.remove('active');
            }
        });
    }

    // Mobile menu toggle: handled solely by assets/js/app.js (toggleSidebar()),
    // which also loads on every page. A second, independent handler used to
    // live here — both fired on every click, each toggling the same classes
    // the other had just toggled, so the two net canceled out and the
    // overlay/sidebar stopped visibly responding to taps.
});
</script>