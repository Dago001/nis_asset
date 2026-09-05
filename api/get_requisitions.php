<?php
/** API: requisitions list, scoped to the caller's command. */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

Auth::requirePermission('requisition.view');

$status = $_GET['status'] ?? '';
$userId = $_GET['user_id'] ?? '';

$sql = "SELECT r.*, u.full_name as requester_name, c.command_name
        FROM requisitions r
        LEFT JOIN users u ON r.requesting_officer_id = u.id
        LEFT JOIN commands c ON r.requesting_command_id = c.id
        WHERE 1=1";
$params = [];

if (Auth::isCommandRestricted()) {
    $sql .= " AND r.requesting_command_id = ?";
    $params[] = Auth::commandId();
}
if ($status !== '') {
    $sql .= " AND r.status = ?";
    $params[] = $status;
}
if ($userId !== '' && ctype_digit((string) $userId)) {
    $sql .= " AND r.created_by = ?";
    $params[] = $userId;
}

$sql .= " ORDER BY r.created_at DESC LIMIT 100";

echo json_encode(['success' => true, 'data' => Database::fetchAll($sql, $params) ?: []]);
