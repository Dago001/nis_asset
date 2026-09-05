<?php
/** API: commands for a given zone (supports zone ID, zone code 'HQ', or zone name). */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$zoneParam = trim($_GET['zone_id'] ?? $_GET['zone'] ?? '');

if (empty($zoneParam)) {
    echo json_encode([]);
    exit;
}

$zoneId = 0;
if (is_numeric($zoneParam)) {
    $zoneId = (int)$zoneParam;
} else {
    $search = $zoneParam;
    if (strcasecmp($search, 'HQ') === 0 || strcasecmp($search, 'Headquarter') === 0) {
        $search = 'Headquarters';
    }
    $zoneRow = Database::fetchOne(
        "SELECT id FROM zones WHERE zone_name LIKE ? OR zone_code LIKE ? LIMIT 1",
        ['%' . $search . '%', '%' . $search . '%']
    );
    if ($zoneRow) {
        $zoneId = (int)$zoneRow['id'];
    }
}

if ($zoneId <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $commands = Database::fetchAll(
        "SELECT id, command_name, state_id, lga_id FROM commands WHERE zone_id = ? ORDER BY command_name ASC",
        [$zoneId]
    ) ?: [];
    echo json_encode($commands);
} catch (Throwable $e) {
    error_log('get_commands error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load commands']);
}
