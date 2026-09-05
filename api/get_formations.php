<?php
/** API: formations for a given zone. */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

Auth::requireAuth();

$zoneId = (int) ($_GET['zone_id'] ?? 0);
if ($zoneId <= 0) { echo json_encode([]); exit; }

try {
    $formations = Database::fetchAll(
        "SELECT id, formation_name, formation_code FROM formations WHERE zone_id = ? ORDER BY formation_name",
        [$zoneId]
    ) ?: [];
    echo json_encode($formations);
} catch (Throwable $e) {
    error_log('get_formations error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load formations']);
}
