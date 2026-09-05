<?php
/** API: items issued under a requisition (for building a return). */
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

Auth::requireAuth();

$requisitionId = (int) ($_GET['requisition_id'] ?? 0);
if ($requisitionId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Requisition ID is required']);
    exit;
}

// Command isolation.
if (Auth::isCommandRestricted()) {
    $req = Database::fetchOne(
        "SELECT requesting_command_id FROM requisitions WHERE id = ?",
        [$requisitionId]
    );
    if (!$req || (string) $req['requesting_command_id'] !== (string) Auth::commandId()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Not permitted for this requisition']);
        exit;
    }
}

try {
    $weapons = Database::fetchAll(
        "SELECT wi.id, wi.weapon_id, wi.make_model, wi.serial_no, wt.type_name
         FROM weapon_issue_log wil
         JOIN weapons_inventory wi ON wil.weapon_id = wi.id
         LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
         WHERE wil.requisition_id = ? AND wi.current_location = 'Issued'
         GROUP BY wi.id",
        [$requisitionId]
    ) ?: [];

    $ammo = Database::fetchAll(
        "SELECT ai.id, ai.ammo_id, at.ammo_type, ac.calibre, SUM(ail.rounds_issued) as rounds_issued, ai.batch_number
         FROM ammunition_issue_log ail
         JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
         LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
         LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
         WHERE ail.requisition_id = ? AND ai.quantity_issued > 0
         GROUP BY ai.id",
        [$requisitionId]
    ) ?: [];

    echo json_encode(['success' => true, 'weapons' => $weapons, 'ammunition' => $ammo]);
} catch (Throwable $e) {
    error_log('get_requisition_issued_items error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load issued items']);
}
