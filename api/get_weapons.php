<?php
/** API: weapons list (search), scoped to the caller's command. */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

Auth::requirePermission('weapons.view');

$search = trim($_GET['search'] ?? '');
$params = [];
$sql = "SELECT w.*, wt.type_name
        FROM weapons_inventory w
        LEFT JOIN weapon_types wt ON w.weapon_type_id = wt.id";

if ($search !== '') {
    $sql .= " WHERE (w.weapon_id LIKE ? OR w.serial_no LIKE ? OR w.make_model LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
}

$sql = Database::applyCommandFilter($sql, 'w', $params);
$sql .= " ORDER BY w.created_at DESC LIMIT 50";

echo json_encode(['success' => true, 'data' => Database::fetchAll($sql, $params) ?: []]);
