<?php
/** API: ammunition list (search), scoped to the caller's command. */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

Auth::requirePermission('ammunition.view');

$search = trim($_GET['search'] ?? '');
$params = [];
$sql = "SELECT a.*, at.ammo_type, ac.calibre
        FROM ammunition_inventory a
        LEFT JOIN ammunition_types at ON a.ammo_type_id = at.id
        LEFT JOIN ammunition_calibres ac ON a.calibre_id = ac.id";

if ($search !== '') {
    $sql .= " WHERE (a.ammo_id LIKE ? OR a.batch_number LIKE ? OR at.ammo_type LIKE ? OR ac.calibre LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $like];
}

$sql = Database::applyCommandFilter($sql, 'a', $params);
$sql .= " ORDER BY a.created_at DESC LIMIT 50";

echo json_encode(['success' => true, 'data' => Database::fetchAll($sql, $params) ?: []]);
