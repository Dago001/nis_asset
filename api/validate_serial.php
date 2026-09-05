<?php
/** API: check whether a weapon serial / ammunition batch number is already taken. */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

Auth::requireAuth();

$type      = $_GET['type'] ?? '';
$serial    = trim($_GET['serial'] ?? '');
$excludeId = (int) ($_GET['exclude_id'] ?? 0);

if ($serial === '' || !in_array($type, ['weapon', 'ammunition', 'ict'], true)) {
    echo json_encode(['valid' => false, 'message' => 'Missing or invalid parameters']);
    exit;
}

$map = [
    'weapon'     => ['weapons_inventory', 'serial_no'],
    'ammunition' => ['ammunition_inventory', 'batch_number'],
    'ict'        => ['ict_assets', 'serial_number'],
];
[$table, $column] = $map[$type];

try {
    $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
    $params = [$serial];
    if ($excludeId > 0) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    $exists = ((int) (Database::fetchOne($sql, $params)['count'] ?? 0)) > 0;
} catch (Throwable $e) {
    error_log('validate_serial error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['valid' => false, 'message' => 'Validation unavailable']);
    exit;
}

echo json_encode([
    'valid'   => !$exists,
    'exists'  => $exists,
    'message' => $exists ? 'Serial/batch number already exists' : 'Available',
]);
