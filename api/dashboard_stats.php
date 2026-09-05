<?php
require_once __DIR__ . '/../config/init.php';

header('Content-Type: application/json');

Auth::requireAuth();

$isRestricted = Auth::isCommandRestricted();
$commandId = Auth::commandId();
$commandWhere = $isRestricted ? ' WHERE command_id = ?' : '';
$commandParams = $isRestricted ? [$commandId] : [];

function countValue(string $sql, array $params = []): int {
    $row = Database::fetchOne($sql, $params);
    return (int)($row['count'] ?? 0);
}

function sumValue(string $sql, array $params = []): int {
    $row = Database::fetchOne($sql, $params);
    return (int)($row['total'] ?? 0);
}

$stats = [];

// 1. Weapons Aggregate Stats
$wpnSql = $isRestricted 
    ? "SELECT COUNT(*) as total,
              SUM(CASE WHEN current_location = 'Issued' THEN 1 ELSE 0 END) as issued,
              SUM(CASE WHEN `condition` = 'Serviceable' THEN 1 ELSE 0 END) as serviceable,
              SUM(CASE WHEN `condition` = 'Unserviceable' THEN 1 ELSE 0 END) as unserviceable,
              SUM(CASE WHEN current_location = 'In Repair' THEN 1 ELSE 0 END) as in_repair
       FROM weapons_inventory WHERE command_id = ?"
    : "SELECT COUNT(*) as total,
              SUM(CASE WHEN current_location = 'Issued' THEN 1 ELSE 0 END) as issued,
              SUM(CASE WHEN `condition` = 'Serviceable' THEN 1 ELSE 0 END) as serviceable,
              SUM(CASE WHEN `condition` = 'Unserviceable' THEN 1 ELSE 0 END) as unserviceable,
              SUM(CASE WHEN current_location = 'In Repair' THEN 1 ELSE 0 END) as in_repair
       FROM weapons_inventory";
$wpnRes = Database::fetchOne($wpnSql, $commandParams);

$stats['weapons'] = [
    'total' => (int)($wpnRes['total'] ?? 0),
    'issued' => (int)($wpnRes['issued'] ?? 0),
    'serviceable' => (int)($wpnRes['serviceable'] ?? 0),
    'unserviceable' => (int)($wpnRes['unserviceable'] ?? 0),
    'in_repair' => (int)($wpnRes['in_repair'] ?? 0)
];

// 2. Ammunition Aggregate Stats
$ammoSql = $isRestricted
    ? "SELECT COUNT(*) as total_types,
              SUM(balance) as total_rounds,
              SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() THEN 1 ELSE 0 END) as expiring_soon,
              SUM(CASE WHEN balance < 100 THEN 1 ELSE 0 END) as low_stock
       FROM ammunition_inventory WHERE command_id = ?"
    : "SELECT COUNT(*) as total_types,
              SUM(balance) as total_rounds,
              SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() THEN 1 ELSE 0 END) as expiring_soon,
              SUM(CASE WHEN balance < 100 THEN 1 ELSE 0 END) as low_stock
       FROM ammunition_inventory";
$ammoRes = Database::fetchOne($ammoSql, $commandParams);

$stats['ammunition'] = [
    'total_types' => (int)($ammoRes['total_types'] ?? 0),
    'total_rounds' => (int)($ammoRes['total_rounds'] ?? 0),
    'expiring_soon' => (int)($ammoRes['expiring_soon'] ?? 0),
    'low_stock' => (int)($ammoRes['low_stock'] ?? 0)
];

// 3. Assets Stats
$stats['assets'] = [
    'land' => countValue('SELECT COUNT(*) as count FROM land_assets' . $commandWhere, $commandParams),
    'buildings' => countValue('SELECT COUNT(*) as count FROM building_assets' . $commandWhere, $commandParams),
    'rented' => countValue('SELECT COUNT(*) as count FROM rented_properties' . $commandWhere, $commandParams),
    'projects' => countValue('SELECT COUNT(*) as count FROM ongoing_projects' . $commandWhere, $commandParams),
    'movable' => countValue('SELECT COUNT(*) as count FROM movable_assets' . $commandWhere, $commandParams),
    'ict' => countValue('SELECT COUNT(*) as count FROM ict_assets' . $commandWhere, $commandParams),
];

// 4. Fleet Stats
$fleetSql = "SELECT 
    (SELECT COUNT(*) FROM vehicle_assets" . $commandWhere . ") as vehicles,
    (SELECT COUNT(*) FROM aircraft_assets" . $commandWhere . ") as aircraft,
    (SELECT COUNT(*) FROM marine_assets" . $commandWhere . ") as marine,
    (SELECT COUNT(*) FROM motorcycle_assets" . $commandWhere . ") as motorcycles";
$fleetParams = $isRestricted ? [$commandId, $commandId, $commandId, $commandId] : [];
$fleetRes = Database::fetchOne($fleetSql, $fleetParams);

$stats['fleet'] = [
    'vehicles' => (int)($fleetRes['vehicles'] ?? 0),
    'aircraft' => (int)($fleetRes['aircraft'] ?? 0),
    'marine' => (int)($fleetRes['marine'] ?? 0),
    'motorcycles' => (int)($fleetRes['motorcycles'] ?? 0)
];

// 5. Requisitions Stats
$reqSql = $isRestricted
    ? "SELECT 
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
       FROM requisitions WHERE requesting_command_id = ?"
    : "SELECT 
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
       FROM requisitions";
$reqRes = Database::fetchOne($reqSql, $isRestricted ? [$commandId] : []);

$stats['requisitions'] = [
    'pending' => (int)($reqRes['pending'] ?? 0),
    'approved' => (int)($reqRes['approved'] ?? 0),
    'rejected' => (int)($reqRes['rejected'] ?? 0)
];

// 6. Users Stats
$userWhere = $isRestricted ? ' AND command_id = ?' : '';
$stats['users'] = [
    'total' => countValue('SELECT COUNT(*) as count FROM users WHERE is_active = 1' . $userWhere, $commandParams),
];

// 7. Requisitions by Priority
$cmdPriorityFilter = $isRestricted ? " WHERE requesting_command_id = ?" : "";
$cmdPriorityParams = $isRestricted ? [$commandId] : [];
$stats['requisitions_by_priority'] = Database::fetchAll("
    SELECT priority_level, COUNT(*) as count 
    FROM requisitions" . $cmdPriorityFilter . "
    GROUP BY priority_level",
    $cmdPriorityParams
) ?: [];

// 8. Top 5 Commands
$cmdWpnFilter = $isRestricted ? " WHERE wi.command_id = ?" : "";
$cmdWpnParams = $isRestricted ? [$commandId] : [];
$stats['top_commands'] = Database::fetchAll("
    SELECT c.command_name, COUNT(wi.id) as count 
    FROM weapons_inventory wi
    JOIN commands c ON wi.command_id = c.id" . $cmdWpnFilter . "
    GROUP BY c.id, c.command_name
    ORDER BY count DESC
    LIMIT 5",
    $cmdWpnParams
) ?: [];

$stats['recent_activity'] = Database::fetchAll(
    "SELECT al.*, u.full_name
     FROM audit_logs al
     LEFT JOIN users u ON al.user_id = u.id
     ORDER BY al.created_at DESC
     LIMIT 10"
) ?: [];

echo json_encode(['success' => true, 'stats' => $stats]);
