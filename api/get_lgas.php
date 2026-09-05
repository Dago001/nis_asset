<?php
/** API: LGAs for a given state (supports state ID or state name like 'FCT', 'Federal Capital Territory'). */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stateParam = trim($_GET['state_id'] ?? $_GET['state'] ?? '');

if (empty($stateParam)) {
    echo json_encode([]);
    exit;
}

$stateId = 0;
if (is_numeric($stateParam)) {
    $stateId = (int)$stateParam;
} else {
    // Support FCT / Federal Capital Territory / Abuja alias lookup
    $search = $stateParam;
    if (strcasecmp($search, 'FCT') === 0 || strcasecmp($search, 'Abuja') === 0) {
        $search = 'Federal Capital Territory';
    }
    $stateRow = Database::fetchOne(
        "SELECT id FROM states WHERE state_name LIKE ? LIMIT 1",
        ['%' . $search . '%']
    );
    if ($stateRow) {
        $stateId = (int)$stateRow['id'];
    }
}

if ($stateId <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $lgas = Database::fetchAll(
        "SELECT id, lga_name FROM lgas WHERE state_id = ? ORDER BY lga_name ASC",
        [$stateId]
    ) ?: [];
    echo json_encode($lgas);
} catch (Throwable $e) {
    error_log('get_lgas error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load LGAs']);
}
