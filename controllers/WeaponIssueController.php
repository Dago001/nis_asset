<?php
/**
 * NIS Asset Management System
 * 
 * Weapon Issue Controller
 * Handles weapons and ammunition issuance, tracking, and returns
 * 
 * @author NIS Web Team
 * @version 1.0
 */

class WeaponIssueController extends Controller {
    
    /**
     * Constructor - Check permissions
     */
    public function __construct() {
        // Check if user has permission to issue weapons
        if (!Auth::can('weapons.edit') && !Auth::can('weapons.issue')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to issue weapons']);
        }
    }
    
    /**
     * Display weapon issue dashboard with forms and recent issues
     */
    public function index() {
        // Get available weapons (in Armoury) — capped: a <select> with
        // thousands of options is unusable anyway, and unbounded queries
        // here contributed to the same oversized-page problem as below.
        $availableWeapons = $this->getAvailableWeapons();

        // Get available ammunition (balance > 0)
        $availableAmmunition = $this->getAvailableAmmunition();

        // Get approved requisitions for dropdown
        $requisitions = $this->getApprovedRequisitions();

        // Get recent weapon issue logs
        $recentWeaponIssues = $this->getRecentWeaponIssues(20);

        // Get recent ammunition issue logs
        $recentAmmoIssues = $this->getRecentAmmoIssues(20);

        // Currently issued weapons awaiting return — paginated (see
        // getIssuedWeapons() docblock for why this mattered).
        $issuedPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $issuedWeaponsResult = $this->getIssuedWeapons($issuedPage);
        $issuedWeapons = $issuedWeaponsResult['rows'];

        // Get issued ammunition (for returns section)
        $issuedAmmunition = $this->getIssuedAmmunition();

        // Calculate statistics
        $statistics = [
            'total_issued_weapons' => $issuedWeaponsResult['total'],
            'available_weapons' => count($availableWeapons),
            'pending_returns' => $this->getPendingReturnsCount(),
            'available_ammo' => count($availableAmmunition),
            'total_weapons' => $this->getTotalWeaponsCount(),
            'total_ammunition' => $this->getTotalAmmunitionCount(),
            'serviceable_weapons' => $this->getServiceableWeaponsCount(),
            'unserviceable_weapons' => $this->getUnserviceableWeaponsCount()
        ];

        // Load the view
        $this->view('weapon_issue/index', [
            'availableWeapons' => $availableWeapons,
            'availableAmmunition' => $availableAmmunition,
            'requisitions' => $requisitions,
            'recentWeaponIssues' => $recentWeaponIssues,
            'recentAmmoIssues' => $recentAmmoIssues,
            'issuedWeapons' => $issuedWeapons,
            'issuedAmmunition' => $issuedAmmunition,
            'statistics' => $statistics,
            'issuedPage' => $issuedWeaponsResult['page'],
            'issuedTotalPages' => $issuedWeaponsResult['totalPages'],
            'issuedTotalCount' => $issuedWeaponsResult['total'],
        ]);
    }
    
    /**
     * Show create issue form (alternative to index)
     */
    public function create() {
        $this->index();
    }
    
    /**
     * Process weapon issue form submission
     */
    public function store() {
        // Check permission
        if (!Auth::can('weapons.edit') && !Auth::can('weapons.issue')) {
            $this->jsonResponse(false, 'You do not have permission to issue weapons');
            return;
        }
        
        // Validate CSRF token
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(false, 'Invalid security token');
            return;
        }
        
        $type = $_POST['issue_type'] ?? '';
        
        if ($type === 'weapon') {
            $this->processWeaponIssue();
        } elseif ($type === 'ammunition') {
            $this->processAmmunitionIssue();
        } else {
            $this->jsonResponse(false, 'Invalid issue type');
        }
    }
    
    /**
     * Process weapon issue
     */
    private function processWeaponIssue() {
        // Validate required fields
        $required = ['weapon_id', 'officer_name', 'officer_rank', 'unit', 'purpose', 'approved_by'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
                return;
            }
        }

        if (!empty($_POST['officer_nis']) && !isDigitsOnly($_POST['officer_nis'])) {
            $this->jsonResponse(false, 'NIS number must contain numbers only');
            return;
        }

        $weaponId = (int)$_POST['weapon_id'];
        $requisitionId = !empty($_POST['requisition_id']) ? $_POST['requisition_id'] : null;
        $issueDate = $_POST['issue_date'] ?? date('Y-m-d');
        $expectedReturnDate = !empty($_POST['expected_return_date']) ? $_POST['expected_return_date'] : null;
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        
        // Check if weapon exists and is available
        $weapon = Database::fetchOne(
            "SELECT * FROM weapons_inventory WHERE id = ?",
            [$weaponId]
        );
        
        if (!$weapon) {
            $this->jsonResponse(false, 'Weapon not found');
            return;
        }
        
        // Seed/live data records in-store weapons as 'In Storage' rather than 'Armoury'/'Available';
        // keep this check in sync with the availability list built in getAvailableWeapons().
        if (!in_array($weapon['current_location'], ['Armoury', 'Available', 'In Storage'], true)) {
            $this->jsonResponse(false, 'Weapon is not available for issue');
            return;
        }

        // Collect weapons to issue (starting with selected weapon)
        $weaponIdsToIssue = [$weaponId];
        if ($quantity > 1) {
            $neededExtra = $quantity - 1;
            $typeClause = !empty($weapon['weapon_type_id']) ? "ORDER BY (weapon_type_id = " . (int)$weapon['weapon_type_id'] . ") DESC, id ASC" : "ORDER BY id ASC";
            $extraWeapons = Database::fetchAll(
                "SELECT id FROM weapons_inventory 
                 WHERE id != ? 
                   AND current_location IN ('Armoury', 'Available', 'In Storage') 
                   AND `condition` = 'Serviceable'
                 {$typeClause}
                 LIMIT {$neededExtra}",
                [$weaponId]
            ) ?: [];

            foreach ($extraWeapons as $ew) {
                $weaponIdsToIssue[] = (int)$ew['id'];
            }
        }
        
        Database::beginTransaction();
        
        try {
            $firstIssueId = null;
            foreach ($weaponIdsToIssue as $wId) {
                // Insert into weapon issue log
                $issueId = Database::insert('weapon_issue_log', [
                    'requisition_id' => $requisitionId,
                    'weapon_id' => $wId,
                    'issue_date' => $issueDate,
                    'officer_name' => $_POST['officer_name'],
                    'officer_rank' => $_POST['officer_rank'],
                    'officer_nis' => $_POST['officer_nis'] ?? null,
                    'unit' => $_POST['unit'],
                    'purpose' => $_POST['purpose'] === 'Other' ? ($_POST['purpose_other'] ?? 'Other') : $_POST['purpose'],
                    'approved_by' => $_POST['approved_by'],
                    'issued_by' => Auth::id(),
                    'expected_return_date' => $expectedReturnDate,
                    'status' => 'Issued',
                    'remarks' => $_POST['remarks'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                if (!$issueId) {
                    throw new Exception("Failed to create issue record");
                }
                if (!$firstIssueId) $firstIssueId = $issueId;
            }
            
            // Determine command ID and send notifications
            $reqCommandId = null;
            if ($requisitionId && class_exists('Notification')) {
                $req = Database::fetchOne("SELECT requesting_command_id, requesting_officer_id, requisition_number, command_approved_by, created_by FROM requisitions WHERE id = ?", [$requisitionId]);
                if ($req) {
                    $reqCommandId = $req['requesting_command_id'];
                    Notification::send($req['requesting_officer_id'], "Weapons issued for your requisition {$req['requisition_number']}.", "/requisition/show/{$requisitionId}");
                    if (!empty($req['command_approved_by']) && $req['command_approved_by'] != $req['requesting_officer_id']) {
                        Notification::send($req['command_approved_by'], "Weapons issued for requisition {$req['requisition_number']}, which you approved.", "/requisition/show/{$requisitionId}");
                    }
                }
            }

            // Update weapon statuses
            foreach ($weaponIdsToIssue as $wId) {
                $weaponUpdate = [
                    'current_location' => 'Issued',
                    'custodian' => $_POST['officer_name'],
                    'custodian_rank' => $_POST['officer_rank'],
                    'custodian_nis' => $_POST['officer_nis'] ?? null,
                    'command_id' => $requisitionId ? $reqCommandId : ($weapon['command_id'] ?? Auth::commandId()),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($requisitionId) {
                    // For requisition-linked issues, add directly to requesting command's Armoury inventory as available stock
                    $weaponUpdate['current_location'] = 'Armoury';
                    $weaponUpdate['custodian'] = null;
                    $weaponUpdate['custodian_rank'] = null;
                    $weaponUpdate['custodian_nis'] = null;
                }

                Database::update('weapons_inventory', $weaponUpdate, 'id = ?', [$wId]);
            }
            
            // Update requisition status if linked
            if ($requisitionId) {
                // Automatically fulfill any remaining ammunition line items on this requisition
                $ammoItems = Database::fetchAll(
                    "SELECT * FROM requisition_items WHERE requisition_id = ? AND item_type = 'Ammunition'",
                    [$requisitionId]
                ) ?: [];

                foreach ($ammoItems as $ai) {
                    $aiQty = (int)($ai['quantity'] ?? 0);
                    $aiIssued = (int)(Database::fetchOne(
                        "SELECT COALESCE(SUM(units_issued), 0) as total FROM ammunition_issue_log WHERE requisition_id = ?",
                        [$requisitionId]
                    )['total'] ?? 0);
                    $aiRemaining = max(0, $aiQty - $aiIssued);
                    if ($aiRemaining > 0) {
                        // Find matching ammunition stock
                        $stockAmmo = Database::fetchOne(
                            "SELECT * FROM ammunition_inventory WHERE balance >= ? ORDER BY (calibre_id = ?) DESC, balance DESC LIMIT 1",
                            [$aiRemaining, (int)($ai['calibre_id'] ?? 0)]
                        ) ?: Database::fetchOne(
                            "SELECT * FROM ammunition_inventory WHERE balance > 0 ORDER BY balance DESC LIMIT 1"
                        );

                        if ($stockAmmo) {
                            $unitsToDeduct = min((int)$stockAmmo['balance'], $aiRemaining);
                            Database::insert('ammunition_issue_log', [
                                'requisition_id' => $requisitionId,
                                'ammo_id' => $stockAmmo['id'],
                                'issue_date' => $issueDate,
                                'units_issued' => $unitsToDeduct,
                                'rounds_issued' => $unitsToDeduct * 30,
                                'officer_name' => $_POST['officer_name'],
                                'officer_rank' => $_POST['officer_rank'] ?? '',
                                'officer_nis' => $_POST['officer_nis'] ?? null,
                                'unit' => $_POST['unit'] ?? '',
                                'purpose' => $_POST['purpose'] === 'Other' ? ($_POST['purpose_other'] ?? 'Other') : $_POST['purpose'],
                                'approved_by' => $_POST['approved_by'],
                                'issued_by' => Auth::id(),
                                'remarks' => $_POST['remarks'] ?? 'Issued with Requisition',
                                'created_at' => date('Y-m-d H:i:s')
                            ]);

                            Database::update('ammunition_inventory', [
                                'quantity_issued' => ($stockAmmo['quantity_issued'] ?? 0) + $unitsToDeduct,
                                'balance' => ($stockAmmo['balance'] ?? 0) - $unitsToDeduct,
                                'updated_at' => date('Y-m-d H:i:s')
                            ], 'id = ?', [$stockAmmo['id']]);
                        }
                    }
                }

                $this->updateRequisitionStatus($requisitionId, 'weapon');
            }

            
            // Log audit
            if (class_exists('AuditLogger')) {
                AuditLogger::log('WEAPON_ISSUE', 'weapons_inventory', $weaponId, null, 
                    count($weaponIdsToIssue) . " weapon(s) issued to {$_POST['officer_name']}" . ($requisitionId ? " (Requisition: $requisitionId)" : ""));
            }
            
            Database::commit();
            
            $successMsg = count($weaponIdsToIssue) > 1 
                ? count($weaponIdsToIssue) . ' weapons issued successfully' 
                : 'Weapon issued successfully';

            // Return success response
            if ($this->isAjax()) {
                $this->jsonResponse(true, $successMsg, ['issue_id' => $firstIssueId, 'issued_count' => count($weaponIdsToIssue)]);
            } else {
                $this->redirect('weapon_issue', ['success' => $successMsg]);
            }
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Weapon issue error: " . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->jsonResponse(false, 'Failed to issue weapon: ' . $e->getMessage());
            } else {
                $this->redirect('weapon_issue/create', ['error' => 'Failed to issue weapon']);
            }
        }
    }
    
    /**
     * Process ammunition issue - FIXED VERSION
     */
    private function processAmmunitionIssue() {
        // Log all POST data for debugging
        error_log("=== AMMUNITION ISSUE DEBUG ===");
        error_log("POST data: " . json_encode($_POST));
        
        // Validate required fields based on your form
        $required = ['ammo_id', 'units_issued', 'issued_to', 'purpose', 'approved_by'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                error_log("Missing required field: $field");
                $this->jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
                return;
            }
        }
        
        $ammoId = (int)$_POST['ammo_id'];
        $unitsIssued = (int)$_POST['units_issued'];
        
        // Validate units issued
        if ($unitsIssued <= 0) {
            error_log("Invalid units issued: $unitsIssued");
            $this->jsonResponse(false, 'Units issued must be greater than zero');
            return;
        }
        
        // Calculate rounds (30 rounds per unit as standard)
        $roundsIssued = !empty($_POST['total_rounds']) ? (int)$_POST['total_rounds'] : ($unitsIssued * 30);
        $requisitionId = !empty($_POST['requisition_id']) ? $_POST['requisition_id'] : null;
        $issueDate = $_POST['issue_date'] ?? date('Y-m-d');
        
        error_log("Ammo ID: $ammoId, Units: $unitsIssued, Rounds: $roundsIssued");
        error_log("Requisition ID: " . ($requisitionId ?? 'none'));
        
        // Check if ammunition exists and has sufficient balance
        $ammo = Database::fetchOne(
            "SELECT * FROM ammunition_inventory WHERE id = ?",
            [$ammoId]
        );
        
        if (!$ammo) {
            error_log("Ammunition not found with ID: $ammoId");
            $this->jsonResponse(false, 'Ammunition not found');
            return;
        }
        
        error_log("Ammunition found: ID={$ammo['ammo_id']}, Balance={$ammo['balance']}, Received={$ammo['quantity_received']}, Issued={$ammo['quantity_issued']}");
        
        // Check balance
        if ($ammo['balance'] < $unitsIssued) {
            error_log("Insufficient balance. Available: {$ammo['balance']}, Requested: $unitsIssued");
            $this->jsonResponse(false, "Insufficient balance. Available: {$ammo['balance']} units");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            // Insert into ammunition issue log - using CORRECT column names from your table structure
            $insertData = [
                'requisition_id' => $requisitionId,
                'ammo_id' => $ammoId,
                'issue_date' => $issueDate,
                'units_issued' => $unitsIssued,
                'rounds_issued' => $roundsIssued, // Using rounds_issued (not total_rounds)
                'officer_name' => $_POST['issued_to'], // Map issued_to to officer_name
                'purpose' => $_POST['purpose'] === 'Other' ? ($_POST['purpose_other'] ?? 'Other') : $_POST['purpose'],
                'approved_by' => $_POST['approved_by'],
                'issued_by' => Auth::id(),
                'remarks' => $_POST['remarks'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Add optional fields if they exist in POST
            if (!empty($_POST['officer_rank'])) {
                $insertData['officer_rank'] = $_POST['officer_rank'];
            }
            
            if (!empty($_POST['officer_nis'])) {
                $insertData['officer_nis'] = $_POST['officer_nis'];
            }
            
            if (!empty($_POST['unit'])) {
                $insertData['unit'] = $_POST['unit'];
            }
            
            error_log("Insert data: " . json_encode($insertData));
            
            // Insert into ammunition issue log
            $issueId = Database::insert('ammunition_issue_log', $insertData);
            
            if (!$issueId) {
                throw new Exception("Failed to create ammunition issue record - insert returned false/0");
            }
            
            error_log("Issue record created with ID: $issueId");
            
            // Update ammunition balance
            $newIssued = ($ammo['quantity_issued'] ?? 0) + $unitsIssued;
            $newBalance = ($ammo['quantity_received'] ?? 0) - $newIssued;
            
            error_log("Updating ammunition - New issued: $newIssued, New balance: $newBalance");
            
            $updateResult = Database::update('ammunition_inventory', [
                'quantity_issued' => $newIssued,
                'balance' => $newBalance,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$ammoId]);
            
            error_log("Update result: " . ($updateResult ? 'success' : 'failed'));
            
            // Assign to command if linked to a requisition
            if ($requisitionId) {
                $req = Database::fetchOne("SELECT requesting_command_id, requesting_officer_id, requisition_number, command_approved_by FROM requisitions WHERE id = ?", [$requisitionId]);
                if ($req) {
                    $reqCommandId = $req['requesting_command_id'];
                    $existingCommandAmmo = Database::fetchOne(
                        "SELECT * FROM ammunition_inventory 
                         WHERE ammo_type_id = ? AND calibre_id = ? AND command_id = ?",
                        [$ammo['ammo_type_id'], $ammo['calibre_id'], $reqCommandId]
                    );
                    
                    if ($existingCommandAmmo) {
                        Database::update('ammunition_inventory', [
                            'quantity_received' => $existingCommandAmmo['quantity_received'] + $unitsIssued,
                            'balance' => $existingCommandAmmo['balance'] + $unitsIssued,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = ?', [$existingCommandAmmo['id']]);
                    } else {
                        $newAmmoCode = 'AMM-' . date('Ym') . '-' . rand(1000, 9999);
                        while (Database::fetchOne("SELECT id FROM ammunition_inventory WHERE ammo_id = ?", [$newAmmoCode])) {
                            $newAmmoCode = 'AMM-' . date('Ym') . '-' . rand(1000, 9999);
                        }
                        Database::insert('ammunition_inventory', [
                            'ammo_id' => $newAmmoCode,
                            'ammo_type_id' => $ammo['ammo_type_id'],
                            'calibre_id' => $ammo['calibre_id'],
                            'command_id' => $reqCommandId,
                            'quantity_received' => $unitsIssued,
                            'quantity_issued' => 0,
                            'balance' => $unitsIssued,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                    
                    if (class_exists('Notification')) {
                        Notification::send($req['requesting_officer_id'], "Ammunition issued for your requisition {$req['requisition_number']}.", "/requisition/show/{$requisitionId}");
                        if (!empty($req['command_approved_by']) && $req['command_approved_by'] != $req['requesting_officer_id']) {
                            Notification::send($req['command_approved_by'], "Ammunition issued for requisition {$req['requisition_number']}, which you approved.", "/requisition/show/{$requisitionId}");
                        }
                    }
                }
            }
            
            // Update requisition status if linked
            if ($requisitionId) {
                // Automatically fulfill any remaining weapon line items on this requisition
                $wpnItems = Database::fetchAll(
                    "SELECT * FROM requisition_items WHERE requisition_id = ? AND item_type = 'Weapon'",
                    [$requisitionId]
                ) ?: [];

                foreach ($wpnItems as $wi) {
                    $wiQty = (int)($wi['quantity'] ?? 0);
                    $wiIssued = (int)(Database::fetchOne(
                        "SELECT COUNT(*) as count FROM weapon_issue_log WHERE requisition_id = ?",
                        [$requisitionId]
                    )['count'] ?? 0);
                    $wiRemaining = max(0, $wiQty - $wiIssued);
                    if ($wiRemaining > 0) {
                        $extraWeapons = Database::fetchAll(
                            "SELECT id FROM weapons_inventory 
                             WHERE current_location IN ('Armoury', 'Available', 'In Storage') 
                               AND `condition` = 'Serviceable'
                             ORDER BY (weapon_type_id = ?) DESC, id ASC
                             LIMIT {$wiRemaining}",
                            [(int)($wi['weapon_type_id'] ?? 0)]
                        ) ?: [];

                        foreach ($extraWeapons as $ew) {
                            $ewId = (int)$ew['id'];
                            Database::insert('weapon_issue_log', [
                                'requisition_id' => $requisitionId,
                                'weapon_id' => $ewId,
                                'issue_date' => $issueDate,
                                'officer_name' => $_POST['issued_to'] ?? $_POST['officer_name'] ?? 'Officer',
                                'officer_rank' => $_POST['officer_rank'] ?? '',
                                'officer_nis' => $_POST['officer_nis'] ?? null,
                                'unit' => $_POST['unit'] ?? '',
                                'purpose' => $_POST['purpose'] === 'Other' ? ($_POST['purpose_other'] ?? 'Other') : ($_POST['purpose'] ?? 'General Duty'),
                                'approved_by' => $_POST['approved_by'] ?? 'HQ Armorer',
                                'issued_by' => Auth::id(),
                                'expected_return_date' => $_POST['expected_return_date'] ?? null,
                                'status' => 'Issued',
                                'remarks' => $_POST['remarks'] ?? 'Issued with Requisition',
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);

                            Database::update('weapons_inventory', [
                                'current_location' => 'Armoury',
                                'custodian' => null,
                                'custodian_rank' => null,
                                'custodian_nis' => null,
                                'command_id' => $reqCommandId ?? null,
                                'updated_at' => date('Y-m-d H:i:s')
                            ], 'id = ?', [$ewId]);
                        }
                    }
                }

                error_log("Updating requisition status for ID: $requisitionId");
                $this->updateRequisitionStatus($requisitionId, 'ammunition');
            }

            
            // Log audit
            if (class_exists('AuditLogger')) {
                AuditLogger::log('AMMUNITION_ISSUE', 'ammunition_inventory', $ammoId, null, 
                    "Ammunition issued: $unitsIssued units to {$_POST['issued_to']}" . ($requisitionId ? " (Requisition: $requisitionId)" : ""));
            }
            
            Database::commit();
            error_log("Transaction committed successfully");
            
            // Return success response
            if ($this->isAjax()) {
                $this->jsonResponse(true, 'Ammunition issued successfully', ['issue_id' => $issueId]);
            } else {
                $this->redirect('weapon_issue', ['success' => 'Ammunition issued successfully']);
            }
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Ammunition issue error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if ($this->isAjax()) {
                $this->jsonResponse(false, 'Failed to issue ammunition: ' . $e->getMessage());
            } else {
                $this->redirect('weapon_issue/create', ['error' => 'Failed to issue ammunition: ' . $e->getMessage()]);
            }
        }
    }
    
    /**
     * Show return form for a specific issue
     */
    public function return($id) {
        // Check if it's weapon or ammunition issue
        $type = $_GET['type'] ?? 'weapon';
        
        if ($type === 'weapon') {
            $issue = Database::fetchOne(
                "SELECT wil.*, wi.weapon_id, wi.make_model, wi.serial_no, wi.current_location
                 FROM weapon_issue_log wil
                 JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                 WHERE wil.id = ?",
                [$id]
            );
            
            if (!$issue) {
                $this->redirect('weapon_issue', ['error' => 'Weapon issue record not found']);
                return;
            }
            
            if ($issue['status'] != 'Issued') {
                $this->redirect('weapon_issue', ['error' => 'This weapon has already been returned']);
                return;
            }
            
            $this->view('weapon_issue/return', [
                'issue' => $issue,
                'type' => 'weapon'
            ]);
            
        } else {
            $tableName = $this->getAmmoIssueTableName();
            $issue = Database::fetchOne(
                "SELECT ail.*, ai.ammo_id, at.ammo_type, ac.calibre
                 FROM $tableName ail
                 JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                 LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                 LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                 WHERE ail.id = ?",
                [$id]
            );

            if (!$issue) {
                $this->redirect('weapon_issue', ['error' => 'Ammunition issue record not found']);
                return;
            }
            
            $this->view('weapon_issue/return', [
                'issue' => $issue,
                'type' => 'ammunition'
            ]);
        }
    }
    
    /**
     * Process weapon return
     */
    public function processReturn($id) {
        // Check permission
        if (!Auth::can('weapons.edit') && !Auth::can('weapons.return')) {
            $this->jsonResponse(false, 'You do not have permission to process returns');
            return;
        }
        
        // Validate CSRF token
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(false, 'Invalid security token');
            return;
        }
        
        $type = $_POST['return_type'] ?? 'weapon';
        
        if ($type === 'weapon') {
            $this->processWeaponReturn($id);
        } else {
            $this->processAmmunitionReturn($id);
        }
    }
    
    /**
     * Process weapon return
     */
    private function processWeaponReturn($id) {
        // Validate required fields
        if (empty($_POST['return_condition'])) {
            $this->jsonResponse(false, 'Return condition is required');
            return;
        }
        
        $issue = Database::fetchOne("SELECT * FROM weapon_issue_log WHERE id = ?", [$id]);
        
        if (!$issue) {
            $this->jsonResponse(false, 'Issue record not found');
            return;
        }
        
        if ($issue['status'] != 'Issued') {
            $this->jsonResponse(false, 'This weapon has already been returned');
            return;
        }
        
        Database::beginTransaction();
        
        try {
            // Update issue log with return information
            Database::update('weapon_issue_log', [
                'actual_return_date' => $_POST['return_date'] ?? date('Y-m-d'),
                'return_condition' => $_POST['return_condition'],
                'status' => 'Returned',
                'remarks' => isset($_POST['remarks']) ? $issue['remarks'] . "\nReturn remarks: " . $_POST['remarks'] : $issue['remarks'],
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            
            // Update weapon status back to Armoury
            Database::update('weapons_inventory', [
                'current_location' => 'Armoury',
                'custodian' => null,
                'custodian_rank' => null,
                'custodian_nis' => null,
                'command_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$issue['weapon_id']]);
            
            // Log audit
            if (class_exists('AuditLogger')) {
                AuditLogger::log('WEAPON_RETURN', 'weapons_inventory', $issue['weapon_id'], null, 
                    "Weapon returned by {$issue['officer_name']} - Condition: {$_POST['return_condition']}");
            }
            
            Database::commit();
            
            if ($this->isAjax()) {
                $this->jsonResponse(true, 'Weapon returned successfully');
            } else {
                $this->redirect('weapon_issue', ['success' => 'Weapon returned successfully']);
            }
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Weapon return error: " . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->jsonResponse(false, 'Failed to process return: ' . $e->getMessage());
            } else {
                $this->redirect("weapon_issue/return/$id", ['error' => 'Failed to process return']);
            }
        }
    }
    
    /**
     * Process ammunition return
     */
    private function processAmmunitionReturn($id) {
        // Validate required fields
        if (empty($_POST['rounds_returned'])) {
            $this->jsonResponse(false, 'Rounds returned is required');
            return;
        }
        
        $tableName = $this->getAmmoIssueTableName();
        $issue = Database::fetchOne("SELECT * FROM $tableName WHERE id = ?", [$id]);
        
        if (!$issue) {
            $this->jsonResponse(false, 'Issue record not found');
            return;
        }
        
        $roundsReturned = (int)$_POST['rounds_returned'];
        $roundsUsed = (int)$_POST['rounds_used'];
        
        Database::beginTransaction();
        
        try {
            // Update issue log with return information
            Database::update($tableName, [
                'return_date' => $_POST['return_date'] ?? date('Y-m-d'),
                'rounds_returned' => $roundsReturned,
                'rounds_used' => $roundsUsed,
                'return_condition' => $_POST['return_condition'] ?? null,
                'status' => 'Returned',
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            
            // Update ammunition balance (add back returned rounds)
            $ammo = Database::fetchOne("SELECT * FROM ammunition_inventory WHERE id = ?", [$issue['ammo_id']]);
            
            if ($ammo) {
                $newIssued = ($ammo['quantity_issued'] ?? 0) - $roundsReturned;
                $newBalance = ($ammo['quantity_received'] ?? 0) - $newIssued;
                
                Database::update('ammunition_inventory', [
                    'quantity_issued' => $newIssued,
                    'balance' => $newBalance,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$issue['ammo_id']]);
                
                // If it is linked to a requisition, update command inventory
                if ($issue['requisition_id']) {
                    $req = Database::fetchOne("SELECT requesting_command_id FROM requisitions WHERE id = ?", [$issue['requisition_id']]);
                    if ($req) {
                        $reqCommandId = $req['requesting_command_id'];
                        $existingCommandAmmo = Database::fetchOne(
                            "SELECT * FROM ammunition_inventory 
                             WHERE ammo_type_id = ? AND calibre_id = ? AND command_id = ?",
                            [$ammo['ammo_type_id'], $ammo['calibre_id'], $reqCommandId]
                        );
                        if ($existingCommandAmmo) {
                            $newCommandBalance = max(0, $existingCommandAmmo['balance'] - $roundsReturned);
                            $newCommandReceived = max(0, $existingCommandAmmo['quantity_received'] - $roundsReturned);
                            Database::update('ammunition_inventory', [
                                'quantity_received' => $newCommandReceived,
                                'balance' => $newCommandBalance,
                                'updated_at' => date('Y-m-d H:i:s')
                            ], 'id = ?', [$existingCommandAmmo['id']]);
                        }
                    }
                }
            }
            
            // Log audit
            if (class_exists('AuditLogger')) {
                AuditLogger::log('AMMUNITION_RETURN', 'ammunition_inventory', $issue['ammo_id'], null, 
                    "Ammunition returned: $roundsReturned rounds");
            }
            
            Database::commit();
            
            if ($this->isAjax()) {
                $this->jsonResponse(true, 'Ammunition returned successfully');
            } else {
                $this->redirect('weapon_issue', ['success' => 'Ammunition returned successfully']);
            }
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Ammunition return error: " . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->jsonResponse(false, 'Failed to process return: ' . $e->getMessage());
            } else {
                $this->redirect("weapon_issue/return/$id?type=ammunition", ['error' => 'Failed to process return']);
            }
        }
    }
    
    /**
     * Show full issue history
     */
    public function history() {
        $type = $_GET['type'] ?? 'all';
        $page = (int)($_GET['page'] ?? 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $weaponIssues = [];
        $ammoIssues = [];
        $totalWeaponIssues = 0;
        $totalAmmoIssues = 0;
        
        if ($type === 'all' || $type === 'weapons') {
            $weaponIssues = Database::fetchAll(
                "SELECT wil.*, wi.weapon_id, wi.make_model, wi.serial_no,
                        u.full_name as issued_by_name
                 FROM weapon_issue_log wil
                 JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                 LEFT JOIN users u ON wil.issued_by = u.id
                 ORDER BY wil.issue_date DESC, wil.id DESC
                 LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
            
            $totalWeaponIssues = Database::fetchOne(
                "SELECT COUNT(*) as count FROM weapon_issue_log"
            )['count'] ?? 0;
        }
        
        if ($type === 'all' || $type === 'ammunition') {
            $tableName = $this->getAmmoIssueTableName();
            $ammoIssues = Database::fetchAll(
                "SELECT ail.*, ai.ammo_id, at.ammo_type, ac.calibre,
                        u.full_name as issued_by_name
                 FROM $tableName ail
                 JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                 LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                 LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                 LEFT JOIN users u ON ail.issued_by = u.id
                 ORDER BY ail.issue_date DESC, ail.id DESC
                 LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
            
            $totalAmmoIssues = Database::fetchOne(
                "SELECT COUNT(*) as count FROM $tableName"
            )['count'] ?? 0;
        }
        
        $this->view('weapon_issue/history', [
            'type' => $type,
            'weaponIssues' => $weaponIssues,
            'ammoIssues' => $ammoIssues,
            'page' => $page,
            'totalWeaponIssues' => $totalWeaponIssues,
            'totalAmmoIssues' => $totalAmmoIssues,
            'limit' => $limit
        ]);
    }
    
    /**
     * Show a specific issue details
     */
    public function show($id) {
        $type = $_GET['type'] ?? 'weapon';
        
        if ($type === 'weapon') {
            $issue = Database::fetchOne(
                "SELECT wil.*, wi.weapon_id, wi.make_model, wi.serial_no, wi.calibre_id,
                        wc.calibre_name, wt.type_name,
                        issuer.full_name as issued_by_name,
                        req.requisition_number
                 FROM weapon_issue_log wil
                 JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                 LEFT JOIN weapon_calibres wc ON wi.calibre_id = wc.id
                 LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
                 LEFT JOIN users issuer ON wil.issued_by = issuer.id
                 LEFT JOIN requisitions req ON wil.requisition_id = req.id
                 WHERE wil.id = ?",
                [$id]
            );
            
            if (!$issue) {
                $this->redirect('weapon_issue/history', ['error' => 'Issue record not found']);
                return;
            }
            
            $this->view('weapon_issue/show', [
                'issue' => $issue,
                'type' => 'weapon'
            ]);
            
        } else {
            $tableName = $this->getAmmoIssueTableName();
            $issue = Database::fetchOne(
                "SELECT ail.*, ai.ammo_id, at.ammo_type, ac.calibre,
                        issuer.full_name as issued_by_name,
                        req.requisition_number
                 FROM $tableName ail
                 JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                 LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                 LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                 LEFT JOIN users issuer ON ail.issued_by = issuer.id
                 LEFT JOIN requisitions req ON ail.requisition_id = req.id
                 WHERE ail.id = ?",
                [$id]
            );
            
            if (!$issue) {
                $this->redirect('weapon_issue/history', ['error' => 'Issue record not found']);
                return;
            }
            
            $this->view('weapon_issue/show', [
                'issue' => $issue,
                'type' => 'ammunition'
            ]);
        }
    }
    
    /**
     * API endpoint to get issued weapons (for return dropdown)
     */
    public function apiGetIssuedWeapons() {
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        $search = $_GET['search'] ?? '';
        
        if ($search) {
            $weapons = Database::fetchAll(
                "SELECT wil.id as issue_log_id, wil.officer_name, wil.issue_date,
                        wi.id as weapon_id, wi.weapon_id as weapon_code, wi.make_model, wi.serial_no
                 FROM weapon_issue_log wil
                 JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                 WHERE wil.status = 'Issued' 
                   AND (wi.weapon_id LIKE ? OR wi.serial_no LIKE ? OR wi.make_model LIKE ? OR wil.officer_name LIKE ?)
                 ORDER BY wil.issue_date DESC
                 LIMIT 50",
                ["%$search%", "%$search%", "%$search%", "%$search%"]
            );
        } else {
            $weapons = Database::fetchAll(
                "SELECT wil.id as issue_log_id, wil.officer_name, wil.issue_date,
                        wi.id as weapon_id, wi.weapon_id as weapon_code, wi.make_model, wi.serial_no
                 FROM weapon_issue_log wil
                 JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                 WHERE wil.status = 'Issued'
                 ORDER BY wil.issue_date DESC
                 LIMIT 50"
            );
        }
        
        $this->jsonResponse(true, 'Success', ['weapons' => $weapons]);
    }
    
    /**
     * API endpoint to get issued ammunition (for return dropdown)
     */
    public function apiGetIssuedAmmunition() {
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        $search = $_GET['search'] ?? '';
        $tableName = $this->getAmmoIssueTableName();
        
        // ammunition_issue_log has no status column (ammunition is consumed,
        // not returned like weapons — see getIssuedAmmunition()'s docblock
        // above for the same issue already fixed there); the old `ail.status`
        // filter referenced a column that doesn't exist and silently failed
        // on every call, so it's dropped here rather than replaced.
        if ($search) {
            $ammo = Database::fetchAll(
                "SELECT ail.id as issue_log_id, ail.officer_name, ail.issue_date, ail.units_issued, ail.rounds_issued,
                        ai.id as ammo_id, ai.ammo_id as ammo_code, at.ammo_type, ac.calibre, ai.balance
                 FROM $tableName ail
                 JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                 LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                 LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                 WHERE (ai.ammo_id LIKE ? OR at.ammo_type LIKE ? OR ac.calibre LIKE ? OR ail.officer_name LIKE ?)
                 ORDER BY ail.issue_date DESC
                 LIMIT 50",
                ["%$search%", "%$search%", "%$search%", "%$search%"]
            );
        } else {
            $ammo = Database::fetchAll(
                "SELECT ail.id as issue_log_id, ail.officer_name, ail.issue_date, ail.units_issued, ail.rounds_issued,
                        ai.id as ammo_id, ai.ammo_id as ammo_code, at.ammo_type, ac.calibre, ai.balance
                 FROM $tableName ail
                 JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                 LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
                 LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
                 ORDER BY ail.issue_date DESC
                 LIMIT 50"
            );
        }
        
        $this->jsonResponse(true, 'Success', ['ammunition' => $ammo]);
    }
    
    /**
     * Helper: Get available weapons
     */
    private function getAvailableWeapons() {
        // 'Armoury'/'Available' are the only two values the create/edit form
        // actually offers, but the real data is overwhelmingly 'In Storage'
        // (11,925 of ~15,000 rows) — a value that never shows up in the form
        // at all, so it must have come from however the inventory was bulk-
        // seeded. Whatever the label, it means the same thing here: sitting
        // in the armoury, not yet issued, not lost, not in repair.
        $where = "wi.current_location IN ('Armoury', 'Available', 'In Storage')";
        $params = [];
        if (Auth::isCommandRestricted()) {
            $where .= " AND wi.command_id = ?";
            $params[] = Auth::commandId();
        }
        $params[] = 300;

        return Database::fetchAll(
            "SELECT wi.*, wt.type_name
             FROM weapons_inventory wi
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             WHERE {$where}
             ORDER BY wi.weapon_id
             LIMIT ?",
            $params
        ) ?: [];
    }
    
    /**
     * Helper: Get available ammunition
     */
    private function getAvailableAmmunition() {
        $where = "ai.balance > 0";
        $params = [];
        if (Auth::isCommandRestricted()) {
            $where .= " AND ai.command_id = ?";
            $params[] = Auth::commandId();
        }
        $params[] = 300;

        return Database::fetchAll(
            "SELECT ai.*, at.ammo_type, ac.calibre
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             WHERE {$where}
             ORDER BY ai.ammo_id
             LIMIT ?",
            $params
        ) ?: [];
    }

    /**
     * Helper: Get approved requisitions
     */
    private function getApprovedRequisitions() {
        $where = "r.status IN ('Approved', 'Partially Issued') AND r.status NOT IN ('Issued', 'Completed', 'Rejected', 'Draft', 'Pending')";
        $params = [];
        if (Auth::isCommandRestricted()) {
            $where .= " AND r.requesting_command_id = ?";
            $params[] = Auth::commandId();
        }
        $params[] = 100;

        $requisitions = Database::fetchAll(
            "SELECT r.*,
                    c.command_name as requesting_command_name,
                    approver.full_name as approved_by_name,
                    cmd_app.full_name as command_approved_by_name,
                    hq_vet.full_name as hq_vetted_by_name,
                    COUNT(DISTINCT ri.id) as total_items,
                    COALESCE(SUM(CASE WHEN ri.item_type = 'Weapon' THEN ri.quantity ELSE 0 END), 0) as total_weapons,
                    COALESCE(SUM(CASE WHEN ri.item_type = 'Ammunition' THEN ri.quantity ELSE 0 END), 0) as total_ammunition,
                    COALESCE((SELECT COUNT(*) FROM weapon_issue_log wil WHERE wil.requisition_id = r.id), 0) as issued_weapons,
                    COALESCE((SELECT SUM(units_issued) FROM ammunition_issue_log ail WHERE ail.requisition_id = r.id), 0) as issued_ammunition
             FROM requisitions r
             LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
             LEFT JOIN commands c ON r.requesting_command_id = c.id
             LEFT JOIN users approver ON r.approved_by = approver.id
             LEFT JOIN users cmd_app ON r.command_approved_by = cmd_app.id
             LEFT JOIN users hq_vet ON r.hq_vetted_by = hq_vet.id
             WHERE {$where}
             GROUP BY r.id
             ORDER BY r.created_at DESC
             LIMIT ?",
            $params
        ) ?: [];

        // If a specific requisition_id is passed in query string (e.g. ?requisition_id=123)
        // ensure it is present in the list ONLY IF it is approved / partially issued and not fully completed / issued
        if (!empty($_GET['requisition_id'])) {
            $reqId = (int)$_GET['requisition_id'];
            $found = false;
            foreach ($requisitions as $req) {
                if ((int)$req['id'] === $reqId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $specificReq = Database::fetchOne(
                    "SELECT r.*,
                            c.command_name as requesting_command_name,
                            approver.full_name as approved_by_name,
                            cmd_app.full_name as command_approved_by_name,
                            hq_vet.full_name as hq_vetted_by_name,
                            COUNT(DISTINCT ri.id) as total_items,
                            COALESCE(SUM(CASE WHEN ri.item_type = 'Weapon' THEN ri.quantity ELSE 0 END), 0) as total_weapons,
                            COALESCE(SUM(CASE WHEN ri.item_type = 'Ammunition' THEN ri.quantity ELSE 0 END), 0) as total_ammunition,
                            COALESCE((SELECT COUNT(*) FROM weapon_issue_log wil WHERE wil.requisition_id = r.id), 0) as issued_weapons,
                            COALESCE((SELECT SUM(units_issued) FROM ammunition_issue_log ail WHERE ail.requisition_id = r.id), 0) as issued_ammunition
                     FROM requisitions r
                     LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
                     LEFT JOIN commands c ON r.requesting_command_id = c.id
                     LEFT JOIN users approver ON r.approved_by = approver.id
                     LEFT JOIN users cmd_app ON r.command_approved_by = cmd_app.id
                     LEFT JOIN users hq_vet ON r.hq_vetted_by = hq_vet.id
                     WHERE r.id = ? AND r.status IN ('Approved', 'Partially Issued') AND r.status NOT IN ('Issued', 'Completed', 'Rejected', 'Draft', 'Pending')
                     GROUP BY r.id",
                    [$reqId]
                );
                if ($specificReq) {
                    array_unshift($requisitions, $specificReq);
                }
            }
        }

        // Filter out any requisitions where all items are fully issued out
        $filteredRequisitions = [];
        foreach ($requisitions as $r) {
            $totWpn = (int)($r['total_weapons'] ?? 0);
            $totAmmo = (int)($r['total_ammunition'] ?? 0);
            $issWpn = (int)($r['issued_weapons'] ?? 0);
            $issAmmo = (int)($r['issued_ammunition'] ?? 0);
            $totItems = (int)($r['total_items'] ?? 0);

            $r['remaining_weapons'] = max(0, $totWpn - $issWpn);
            $r['remaining_ammunition'] = max(0, $totAmmo - $issAmmo);

            // If requisition has item entries and everything has already been issued, exclude it completely
            if ($totItems > 0 && $r['remaining_weapons'] <= 0 && $r['remaining_ammunition'] <= 0) {
                continue;
            }

            $filteredRequisitions[] = $r;
        }
        $requisitions = $filteredRequisitions;

        // Attach weapon and ammunition item specific purposes to requisitions
        if (!empty($requisitions)) {
            $reqIds = array_column($requisitions, 'id');
            $placeholders = implode(',', array_fill(0, count($reqIds), '?'));
            $items = Database::fetchAll(
                "SELECT requisition_id, item_type, purpose, purpose_other
                 FROM requisition_items
                 WHERE requisition_id IN ($placeholders)
                 ORDER BY id ASC",
                $reqIds
            ) ?: [];

            $itemMap = [];
            foreach ($items as $item) {
                $rid = $item['requisition_id'];
                $itype = $item['item_type'];
                if (!isset($itemMap[$rid][$itype])) {
                    $itemMap[$rid][$itype] = [
                        'purpose' => $item['purpose'] ?? '',
                        'purpose_other' => $item['purpose_other'] ?? ''
                    ];
                }
            }

            foreach ($requisitions as &$r) {
                $rid = $r['id'];
                $r['weapon_purpose'] = !empty($itemMap[$rid]['Weapon']['purpose']) ? $itemMap[$rid]['Weapon']['purpose'] : ($r['justification'] ?? '');
                $r['weapon_purpose_other'] = $itemMap[$rid]['Weapon']['purpose_other'] ?? '';
                $r['ammo_purpose'] = !empty($itemMap[$rid]['Ammunition']['purpose']) ? $itemMap[$rid]['Ammunition']['purpose'] : ($r['justification'] ?? '');
                $r['ammo_purpose_other'] = $itemMap[$rid]['Ammunition']['purpose_other'] ?? '';
                
                // Determine the best approved_by name
                $r['final_approved_by_name'] = !empty($r['approved_by_name']) ? $r['approved_by_name'] : (!empty($r['hq_vetted_by_name']) ? $r['hq_vetted_by_name'] : (!empty($r['command_approved_by_name']) ? $r['command_approved_by_name'] : ''));
            }
            unset($r);
        }

        return $requisitions;
    }
    
    /**
     * Helper: Get recent weapon issues
     */
    private function getRecentWeaponIssues($limit = 20) {
        $limit = max(1, (int)$limit);
        return Database::fetchAll(
            "SELECT wil.*, wi.weapon_id, wi.make_model, u.full_name as issued_by_name
             FROM (
                 SELECT * FROM weapon_issue_log ORDER BY id DESC LIMIT {$limit}
             ) wil
             JOIN weapons_inventory wi ON wil.weapon_id = wi.id
             LEFT JOIN users u ON wil.issued_by = u.id
             ORDER BY wil.id DESC"
        ) ?: [];
    }
    
    /**
     * Helper: Get recent ammunition issues
     */
    private function getRecentAmmoIssues($limit = 20) {
        $limit = max(1, (int)$limit);
        $tableName = $this->getAmmoIssueTableName();
        
        return Database::fetchAll(
            "SELECT ail.*, ai.ammo_id, at.ammo_type, ac.calibre, u.full_name as issued_by_name
             FROM (
                 SELECT * FROM {$tableName} ORDER BY id DESC LIMIT {$limit}
             ) ail
             JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             LEFT JOIN users u ON ail.issued_by = u.id
             ORDER BY ail.id DESC"
        ) ?: [];
    }
    
    /**
     * Helper: Get currently issued weapons awaiting return — paginated.
     *
     * This previously fetched every "Issued" row (49,000+ in this deployment)
     * on every page load with no LIMIT at all, which made the page render an
     * enormous table on first paint that then had to reflow/repaint once the
     * browser finished — the "zoom in then out" flash reported on this page,
     * same root cause as /ammunition.
     *
     * Returns ['rows' => [...], 'total' => int, 'page' => int, 'totalPages' => int, 'limit' => int].
     */
    private function getIssuedWeapons($page = 1, $limit = 50) {
        $page = max(1, (int) $page);
        $offset = ($page - 1) * $limit;

        $where = "wil.status = 'Issued'";
        $params = [];

        // Command isolation: weapon_issue_log itself has no command_id, so
        // scope through the joined weapons_inventory row instead.
        if (Auth::isCommandRestricted()) {
            $where .= " AND wi.command_id = ?";
            $params[] = Auth::commandId();
        }

        $search = trim($_GET['search'] ?? '');
        if ($search !== '') {
            $where .= " AND (wi.weapon_id LIKE ? OR wi.serial_no LIKE ? OR wil.officer_name LIKE ? OR wil.unit LIKE ?)";
            $like = "%{$search}%";
            array_push($params, $like, $like, $like, $like);
        }

        $total = (int) (Database::fetchOne(
            "SELECT COUNT(*) as count
             FROM weapon_issue_log wil
             JOIN weapons_inventory wi ON wil.weapon_id = wi.id
             WHERE {$where}",
            $params
        )['count'] ?? 0);

        $rows = Database::fetchAll(
            "SELECT wil.*, wi.weapon_id, wi.make_model, wi.serial_no
             FROM weapon_issue_log wil
             JOIN weapons_inventory wi ON wil.weapon_id = wi.id
             WHERE {$where}
             ORDER BY wil.issue_date DESC, wil.id DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        ) ?: [];

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'limit' => $limit,
        ];
    }

    /**
     * Helper: Get issued ammunition awaiting return.
     *
     * ammunition_issue_log has no status column (ammunition is consumed, not
     * returned like weapons — the old query filtered on `ail.status`, which
     * doesn't exist, so this silently failed and errored on every page load).
     * Capped defensively regardless.
     */
    private function getIssuedAmmunition($limit = 100) {
        $tableName = $this->getAmmoIssueTableName();

        $where = '1=1';
        $params = [];
        if (Auth::isCommandRestricted()) {
            $where .= " AND ai.command_id = ?";
            $params[] = Auth::commandId();
        }
        $params[] = $limit;

        return Database::fetchAll(
            "SELECT ail.*, ai.ammo_id, at.ammo_type, ac.calibre, ai.balance
             FROM $tableName ail
             JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             WHERE {$where}
             ORDER BY ail.issue_date DESC, ail.id DESC
             LIMIT ?",
            $params
        ) ?: [];
    }
    
    /**
     * Helper: Get pending (overdue) returns count
     */
    private function getPendingReturnsCount() {
        // weapon_issue_log has no command_id of its own — go through the
        // weapon it's for. Was completely unscoped before, so a Command
        // Armorer with e.g. 224 weapons total in their own command was
        // being shown the whole system's overdue count (tens of thousands).
        $joinSql = "JOIN weapons_inventory wi ON weapon_issue_log.weapon_id = wi.id";
        $sql = "SELECT COUNT(*) as count FROM weapon_issue_log $joinSql WHERE weapon_issue_log.status = 'Issued' AND expected_return_date < CURDATE()";
        $params = [];
        $sql = Database::applyOptionalFilter($sql, 'wi', 'command_id', Auth::isCommandRestricted() ? Auth::commandId() : null, $params);
        $weaponPending = Database::fetchOne(
            $sql, $params
        )['count'] ?? 0;
        
        return $weaponPending;
    }
    
    /**
     * Helper: Get total weapons count
     */
    private function getTotalWeaponsCount() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory");
        return $result['count'] ?? 0;
    }
    
    /**
     * Helper: Get total ammunition count
     */
    private function getTotalAmmunitionCount() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM ammunition_inventory");
        return $result['count'] ?? 0;
    }
    
    /**
     * Helper: Get serviceable weapons count
     */
    private function getServiceableWeaponsCount() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory WHERE `condition` = 'Serviceable'");
        return $result['count'] ?? 0;
    }
    
    /**
     * Helper: Get unserviceable weapons count
     */
    private function getUnserviceableWeaponsCount() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory WHERE `condition` = 'Unserviceable'");
        return $result['count'] ?? 0;
    }
    
    /**
     * Helper: Update requisition status after issue
     */
    private function updateRequisitionStatus($requisitionId, $itemType = 'Weapon') {
        // Check if all items in requisition are issued.
        //
        // Neither weapon_issue_log nor ammunition_issue_log has a
        // requisition_item_id column (they only have requisition_id, tying
        // an issuance to the requisition as a whole, not to one specific
        // line item) — a join on requisition_item_id here always threw,
        // was silently swallowed by fetchOne(), and defaulted to "0
        // remaining", so every requisition was marked fully Issued the
        // moment a single item on it was issued. Fixed by comparing
        // quantities instead of joining on a column that doesn't exist:
        // total quantity requested per item type vs. what's actually been
        // logged as issued against this requisition.
        $weaponsRequested = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as total
             FROM requisition_items
             WHERE requisition_id = ? AND item_type = 'Weapon'",
            [$requisitionId]
        )['total'] ?? 0;

        $weaponsIssued = Database::fetchOne(
            "SELECT COUNT(*) as count FROM weapon_issue_log WHERE requisition_id = ?",
            [$requisitionId]
        )['count'] ?? 0;

        $remainingWeapons = max(0, (int)$weaponsRequested - (int)$weaponsIssued);

        $ammoRequested = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as total
             FROM requisition_items
             WHERE requisition_id = ? AND item_type = 'Ammunition'",
            [$requisitionId]
        )['total'] ?? 0;

        $ammoTableName = $this->getAmmoIssueTableName();
        $ammoIssued = Database::fetchOne(
            "SELECT COALESCE(SUM(units_issued), 0) as total FROM {$ammoTableName} WHERE requisition_id = ?",
            [$requisitionId]
        )['total'] ?? 0;

        $remainingAmmo = max(0, (int)$ammoRequested - (int)$ammoIssued);
        
        if ($remainingWeapons == 0 && $remainingAmmo == 0) {
            Database::update('requisitions', [
                'status' => 'Completed',
                'approval_stage' => 'Completed',
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$requisitionId]);

            if (class_exists('Notification')) {
                $req = Database::fetchOne("SELECT requesting_officer_id, command_approved_by, requisition_number FROM requisitions WHERE id = ?", [$requisitionId]);
                if ($req) {
                    Notification::send($req['requesting_officer_id'], "Requisition {$req['requisition_number']} has been fully fulfilled and marked Completed.", "/requisition/show/{$requisitionId}");
                    if (!empty($req['command_approved_by']) && $req['command_approved_by'] != $req['requesting_officer_id']) {
                        Notification::send($req['command_approved_by'], "Requisition {$req['requisition_number']} has been fully fulfilled and marked Completed.", "/requisition/show/{$requisitionId}");
                    }
                }
            }
        } else {
            Database::update('requisitions', [
                'status' => 'Partially Issued',
                'approval_stage' => 'Armorer_Issue',
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$requisitionId]);
        }
    }
    
    /**
     * Helper: Get ammunition issue table name (handles different naming conventions)
     */
    private function getAmmoIssueTableName() {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $cached = 'ammunition_issue_log';
        return $cached;
    }
    
    /**
     * Helper: Check if request is AJAX
     */
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Helper: Send JSON response
     */
    protected function jsonResponse($success, $message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}