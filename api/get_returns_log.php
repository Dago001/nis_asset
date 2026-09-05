<?php
/** API: returns log, scoped to the caller's command (via linked requisition). */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

Auth::requirePermission('returns.view');

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "SELECT r.*, u.full_name as received_by_name, req.requisition_number
        FROM returns r
        LEFT JOIN users u ON r.received_by = u.id
        LEFT JOIN requisitions req ON r.requisition_id = req.id
        WHERE 1=1";
$params = [];

if (Auth::isCommandRestricted()) {
    $sql .= " AND req.requesting_command_id = ?";
    $params[] = Auth::commandId();
}
if ($search !== '') {
    $sql .= " AND (r.return_number LIKE ? OR r.returning_officer_name LIKE ? OR r.returning_unit LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
}
if ($status !== '') {
    $sql .= " AND r.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY r.created_at DESC LIMIT 100";

$returns = Database::fetchAll($sql, $params) ?: [];

foreach ($returns as &$return) {
    $w = Database::fetchOne(
        "SELECT COUNT(*) as count, SUM(arm_total) as total FROM return_weapons WHERE return_id = ?",
        [$return['id']]
    );
    $return['weapons_count'] = $w['count'] ?? 0;
    $return['weapons_total'] = $w['total'] ?? 0;

    $a = Database::fetchOne(
        "SELECT COUNT(*) as count, SUM(rounds_returned) as total FROM return_ammunition WHERE return_id = ?",
        [$return['id']]
    );
    $return['ammo_count'] = $a['count'] ?? 0;
    $return['ammo_total'] = $a['total'] ?? 0;
}
unset($return);

echo json_encode(['success' => true, 'data' => $returns]);
