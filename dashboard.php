<?php
/**
 * Legacy Dashboard Page - Redirects to MVC routed /dashboard
 */
require_once __DIR__ . '/config/init.php';
header('Location: ' . BASE_URL . '/dashboard');
exit;


// Get current user data
$user = getCurrentUser();

// Set variables for the view
$active = 'dashboard';
$title = 'Dashboard';
$init_charts = true;

// Get user role for customizing dashboard
$isSuperAdmin = isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true;
$userRoles = $_SESSION['roles'] ?? [];
$userPermissions = $_SESSION['permissions'] ?? [];

// Get database connection for statistics
$pdo = getDBConnection();

// Initialize stats array with default values
$stats = [
    'total_weapons' => 0,
    'weapons_issued' => 0,
    'total_ammunition' => 0,
    'ammunition_balance' => 0,
    'total_land' => 0,
    'total_buildings' => 0,
    'total_rented' => 0,
    'total_projects' => 0,
    'total_movable' => 0,
    'total_ict' => 0,
    'total_vehicles' => 0,
    'total_aircraft' => 0,
    'total_marine' => 0,
    'total_motorcycles' => 0,
    'total_users' => 1, // At least the current user
    'pending_requisitions' => 0,
    'expiring_ammunition' => 0,
    'unserviceable_weapons' => 0
];

$activities = [];

if ($pdo) {
    try {
        // Get total weapons
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM weapons_inventory");
        $stats['total_weapons'] = $stmt->fetch()['count'] ?? 0;
        
        // Get issued weapons
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM weapons_inventory WHERE current_location = 'Issued'");
        $stats['weapons_issued'] = $stmt->fetch()['count'] ?? 0;
        
        // Get total ammunition
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ammunition_inventory");
        $stats['total_ammunition'] = $stmt->fetch()['count'] ?? 0;
        
        // Get ammunition balance
        $stmt = $pdo->query("SELECT SUM(balance) as total FROM ammunition_inventory");
        $stats['ammunition_balance'] = $stmt->fetch()['total'] ?? 0;
        
        // Get land assets
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM land_assets");
        $stats['total_land'] = $stmt->fetch()['count'] ?? 0;
        
        // Get buildings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM building_assets");
        $stats['total_buildings'] = $stmt->fetch()['count'] ?? 0;
        
        // Get rented properties
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM rented_properties");
        $stats['total_rented'] = $stmt->fetch()['count'] ?? 0;
        
        // Get ongoing projects
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ongoing_projects");
        $stats['total_projects'] = $stmt->fetch()['count'] ?? 0;
        
        // Get movable assets
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM movable_assets");
        $stats['total_movable'] = $stmt->fetch()['count'] ?? 0;
        
        // Get ICT assets
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ict_assets");
        $stats['total_ict'] = $stmt->fetch()['count'] ?? 0;
        
        // Get vehicles
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM vehicle_assets");
        $stats['total_vehicles'] = $stmt->fetch()['count'] ?? 0;
        
        // Get aircraft
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM aircraft_assets");
        $stats['total_aircraft'] = $stmt->fetch()['count'] ?? 0;
        
        // Get marine
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM marine_assets");
        $stats['total_marine'] = $stmt->fetch()['count'] ?? 0;
        
        // Get motorcycles
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM motorcycle_assets");
        $stats['total_motorcycles'] = $stmt->fetch()['count'] ?? 0;
        
        // Get users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $stats['total_users'] = $stmt->fetch()['count'] ?? 1;
        
        // Get pending requisitions
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM requisitions WHERE status = 'Pending'");
        $stats['pending_requisitions'] = $stmt->fetch()['count'] ?? 0;
        
        // Get unserviceable weapons
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM weapons_inventory WHERE `condition` = 'Unserviceable'");
        $stats['unserviceable_weapons'] = $stmt->fetch()['count'] ?? 0;
        
        // Get expiring ammunition (next 90 days)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ammunition_inventory WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(NOW(), INTERVAL 90 DAY)");
        $stats['expiring_ammunition'] = $stmt->fetch()['count'] ?? 0;
        
        // Get recent activities
        $stmt = $pdo->query("
            SELECT al.*, u.full_name 
            FROM audit_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT 10
        ");
        $activities = $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
    }
}

// Include the header layout - use absolute path with __DIR__
require_once __DIR__ . '/views/layouts/header.php';

// Include the sidebar layout
require_once __DIR__ . '/views/layouts/sidebar.php';

// Display any alerts
require_once __DIR__ . '/views/layouts/alerts.php';

// Include the main dashboard content
require_once __DIR__ . '/views/dashboard/index.php';

// Include the footer layout
require_once __DIR__ . '/views/layouts/footer.php';
?>