<?php
/** API: single-field inline update of weapons / ammunition inventory. */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

Auth::requirePermission('weapons.edit');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$type  = $_POST['type'] ?? '';
$id    = (int) ($_POST['id'] ?? 0);
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

if (!$type || !$id || !$field) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$allowedFields = [
    'weapons'    => ['condition', 'current_location', 'custodian', 'remarks'],
    'ammunition' => ['condition', 'storage_location', 'remarks'],
];
if (!isset($allowedFields[$type]) || !in_array($field, $allowedFields[$type], true)) {
    echo json_encode(['success' => false, 'message' => 'Field not allowed']);
    exit;
}

$table = $type === 'weapons' ? 'weapons_inventory' : 'ammunition_inventory';
$oldData = Database::fetchOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
if (!$oldData) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit;
}

if (Auth::isCommandRestricted()
    && (string) ($oldData['command_id'] ?? '') !== (string) Auth::commandId()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not permitted for this record']);
    exit;
}

Database::beginTransaction();
try {
    Database::update($table, [$field => $value], 'id = ?', [$id]);
    AuditLogger::logUpdate($table, $id, $oldData, [$field => $value]);
    Database::commit();
    echo json_encode(['success' => true, 'message' => 'Updated successfully']);
} catch (Throwable $e) {
    Database::rollBack();
    error_log('update_inventory error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
