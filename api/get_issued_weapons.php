<?php
/**
 * API: currently-issued weapons (for return processing),
 * scoped to the caller's command.
 */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

Auth::requireAuth();
if (!Auth::can('weapons.edit') && !Auth::can('weapons.return') && !Auth::isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$search = trim($_GET['search'] ?? '');
$params = [];
$sql = "SELECT wil.id as issue_log_id, wil.officer_name, wil.issue_date, wil.expected_return_date,
               wi.id as weapon_id, wi.weapon_id as weapon_code, wi.make_model, wi.serial_no, wi.current_location
        FROM weapon_issue_log wil
        JOIN weapons_inventory wi ON wil.weapon_id = wi.id
        WHERE wil.status = 'Issued'";

if ($search !== '') {
    $sql .= " AND (wi.weapon_id LIKE ? OR wi.serial_no LIKE ? OR wi.make_model LIKE ? OR wil.officer_name LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

if (Auth::isCommandRestricted()) {
    $sql .= " AND wi.command_id = ?";
    $params[] = Auth::commandId();
}

$sql .= " ORDER BY wil.issue_date DESC LIMIT 50";

$weapons = Database::fetchAll($sql, $params) ?: [];

$today = new DateTime();
foreach ($weapons as &$weapon) {
    try {
        $weapon['days_out'] = (new DateTime($weapon['issue_date']))->diff($today)->days;
    } catch (Throwable $e) {
        $weapon['days_out'] = 0;
    }
    $weapon['is_overdue'] = false;
    $weapon['days_overdue'] = 0;
    if (!empty($weapon['expected_return_date'])) {
        try {
            $expected = new DateTime($weapon['expected_return_date']);
            $weapon['is_overdue'] = $expected < $today;
            $weapon['days_overdue'] = $weapon['is_overdue'] ? $expected->diff($today)->days : 0;
        } catch (Throwable $e) { /* leave defaults */ }
    }
}
unset($weapon);

echo json_encode(['success' => true, 'data' => $weapons, 'count' => count($weapons)]);
