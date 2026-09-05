<?php
/**
 * Returns Controller
 */
class ReturnsController extends Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Only call parent constructor if it exists
        if (method_exists(get_parent_class($this), '__construct')) {
            parent::__construct();
        }
    }
    
    /**
     * Display all returns
     */
    public function index() {
        // Check permission
        if (!Auth::can('returns.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view returns']);
            return;
        }
        
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(r.return_number LIKE ? OR r.returning_officer_name LIKE ? OR r.returning_unit LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '') {
            $where[] = "r.status = ?";
            $params[] = $status;
        }
        if ($type !== '') {
            $where[] = "r.return_type = ?";
            $params[] = $type;
        }
        if ($dateFrom !== '') {
            $where[] = "r.return_date >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[] = "r.return_date <= ?";
            $params[] = $dateTo;
        }

        // Command isolation: `returns` has no command_id of its own — a
        // return's command is the command that requested the original
        // requisition it's tied to. A Command Armorer only sees their own
        // command's returns; Super Admin/admin/HQ Armorer/Armorer see all
        // (Auth::isCommandRestricted() already excludes those roles).
        if (Auth::isCommandRestricted()) {
            $where[] = "req.requesting_command_id = ?";
            $params[] = Auth::commandId();
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        // Both queries need the requisitions join whenever a WHERE clause
        // might reference req.* (search doesn't, but command scoping does) —
        // joining unconditionally keeps the two queries' WHERE clauses identical.
        $joinSql = "LEFT JOIN requisitions req ON r.requisition_id = req.id";

        $totalCount = (int) (Database::fetchOne(
            "SELECT COUNT(*) as total FROM returns r $joinSql $whereSql", $params
        )['total'] ?? 0);
        $totalPages = max(1, (int) ceil($totalCount / $limit));

        $returns = Database::fetchAll(
            "SELECT r.*, u.full_name as received_by_name, req.requisition_number
             FROM returns r
             LEFT JOIN users u ON r.received_by = u.id
             $joinSql
             $whereSql
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        if (!is_array($returns)) {
            $returns = [];
        }

        // Calculate statistics — scoped the same way as the list above, not
        // from the whole unfiltered table, so a Command Armorer's summary
        // cards match what they can actually see.
        $statsWhere = Auth::isCommandRestricted() ? 'WHERE req.requesting_command_id = ?' : '';
        $statsParams = Auth::isCommandRestricted() ? [Auth::commandId()] : [];
        $grandTotal = (int) (Database::fetchOne(
            "SELECT COUNT(*) as total FROM returns r $joinSql $statsWhere", $statsParams
        )['total'] ?? 0);
        $statusCounts = Database::fetchAll(
            "SELECT r.status, COUNT(*) as cnt FROM returns r $joinSql $statsWhere GROUP BY r.status", $statsParams
        ) ?: [];

        $statistics = [
            'total' => $grandTotal,
            'pending' => 0,
            'processed' => 0,
            'verified' => 0,
            'completed' => 0,
            'total_weapons_returned' => 0,
            'total_ammunition_returned' => 0
        ];

        foreach ($statusCounts as $row) {
            switch ($row['status'] ?? '') {
                case 'Pending':
                    $statistics['pending'] = (int) $row['cnt'];
                    break;
                case 'Processed':
                    $statistics['processed'] = (int) $row['cnt'];
                    break;
                case 'Verified':
                    $statistics['verified'] = (int) $row['cnt'];
                    break;
                case 'Completed':
                    $statistics['completed'] = (int) $row['cnt'];
                    break;
            }
        }

        // Get weapons and ammunition counts from related tables — same
        // command scoping via the returns -> requisitions chain.
        $rwJoin = "JOIN returns r ON rw.return_id = r.id $joinSql";
        $raJoin = "JOIN returns r ON ra.return_id = r.id $joinSql";
        $statistics['total_weapons_returned'] = (int) (Database::fetchOne(
            "SELECT COUNT(*) as total FROM return_weapons rw $rwJoin $statsWhere", $statsParams
        )['total'] ?? 0);
        $statistics['total_ammunition_returned'] = (int) (Database::fetchOne(
            "SELECT COALESCE(SUM(rounds_returned), 0) as total FROM return_ammunition ra $raJoin $statsWhere", $statsParams
        )['total'] ?? 0);

        $this->view('returns/index', [
            'returns' => $returns,
            'statistics' => $statistics,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount
        ]);
    }
    
    /**
 * Show create return form
 */
public function create() {
    // Check permission
    if (!Auth::can('returns.create')) {
        $this->redirect('dashboard', ['error' => 'You do not have permission to create returns']);
        return;
    }
    
    // Get available weapons (issued weapons that can be returned)
    $availableWeapons = Database::fetchAll(
        "SELECT wi.*, wt.type_name 
         FROM weapons_inventory wi
         LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
         WHERE wi.current_location = 'Issued'
         ORDER BY wi.weapon_id"
    ) ?: [];
    
    // Get issued ammunition
    $issuedAmmunition = Database::fetchAll(
        "SELECT ai.*, at.ammo_type, ac.calibre
         FROM ammunition_inventory ai
         LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
         LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
         WHERE ai.quantity_issued > 0
         ORDER BY ai.ammo_id"
    ) ?: [];
    
    // Get ALL requisitions for the dropdown (not just issued ones)
    $requisitions = Database::fetchAll(
        "SELECT r.*, 
                u.full_name as requesting_officer_name,
                COUNT(ri.id) as total_items
         FROM requisitions r
         LEFT JOIN users u ON r.requesting_officer_id = u.id
         LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
         GROUP BY r.id
         ORDER BY r.created_at DESC"
    ) ?: [];
    
    $this->view('returns/create', [
        'availableWeapons' => $availableWeapons,
        'issuedAmmunition' => $issuedAmmunition,
        'requisitions' => $requisitions  // This is the key change
    ]);
}
    
 /**
 * Store new return (simplified version)
 */
public function store() {
    // Check permission
    if (!Auth::can('returns.create')) {
        $this->redirect('dashboard', ['error' => 'You do not have permission to create returns']);
        return;
    }
    
    // Validate CSRF token
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $this->redirect('returns/create', ['error' => 'Invalid security token']);
        return;
    }
    
    // Manual validation
    $errors = [];
    
    if (empty($_POST['return_date'])) {
        $errors['return_date'] = 'Return date is required';
    }
    
    if (empty($_POST['return_type'])) {
        $errors['return_type'] = 'Return type is required';
    }
    
    if (empty($_POST['returning_officer_name'])) {
        $errors['returning_officer_name'] = 'Officer name is required';
    } elseif (!isValidName($_POST['returning_officer_name'])) {
        $errors['returning_officer_name'] = "Officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
    }
    
    if (empty($_POST['returning_rank'])) {
        $errors['returning_rank'] = 'Rank is required';
    }
    
    if (empty($_POST['returning_nis'])) {
        $errors['returning_nis'] = 'NIS number is required';
    } elseif (!isDigitsOnly($_POST['returning_nis'])) {
        $errors['returning_nis'] = 'NIS number must contain numbers only';
    }


    if (empty($_POST['returning_unit'])) {
        $errors['returning_unit'] = 'Unit is required';
    }
    
    // Check if at least one weapon or ammunition is provided based on return type
    $returnType = $_POST['return_type'] ?? '';
    $hasWeapon = isset($_POST['weapon_id']) && is_array($_POST['weapon_id']) && !empty(array_filter($_POST['weapon_id']));
    $hasAmmo = isset($_POST['ammo_id']) && is_array($_POST['ammo_id']) && !empty(array_filter($_POST['ammo_id']));
    
    if ($returnType === 'Weapon' && !$hasWeapon) {
        $errors['weapons'] = 'At least one weapon is required';
    }
    
    if ($returnType === 'Ammunition' && !$hasAmmo) {
        $errors['ammunition'] = 'At least one ammunition is required';
    }
    
    if ($returnType === 'Both' && (!$hasWeapon || !$hasAmmo)) {
        $errors['items'] = 'Both weapons and ammunition are required';
    }
    
    if (!empty($errors)) {
        Session::set('errors', $errors);
        Session::set('old', $_POST);
        $this->redirect('returns/create');
        return;
    }
    
    $returnNumber = $this->generateReturnNumber();
    
    Database::beginTransaction();
    
    try {
        // The `returns` table has no weapon_id/ammo_id/rounds_returned/etc.
        // columns of its own — those live on the return_weapons/
        // return_ammunition child rows (inserted below). An earlier version
        // of this method tried to also duplicate the first weapon/ammo's
        // details onto the parent row for "backward compatibility" with
        // columns that don't exist on this schema, which made every single
        // return submission fail with a SQL error before a row was ever
        // written — this table had zero rows regardless of return_type.
        $returnId = Database::insert('returns', [
            'return_number' => $returnNumber,
            'return_date' => $_POST['return_date'],
            'return_type' => $_POST['return_type'],
            'requisition_id' => !empty($_POST['requisition_id']) ? $_POST['requisition_id'] : null,
            'returning_officer_name' => $_POST['returning_officer_name'],
            'returning_rank' => $_POST['returning_rank'],
            'returning_nis' => $_POST['returning_nis'],
            'returning_unit' => $_POST['returning_unit'],
            'status' => 'Pending',
            'remarks' => $_POST['remarks'] ?? null,
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if (!$returnId) {
            throw new Exception("Failed to create return record");
        }
        
        // Insert all returned weapons and update inventory status
        if (isset($_POST['weapon_id']) && is_array($_POST['weapon_id'])) {
            foreach ($_POST['weapon_id'] as $index => $wId) {
                if (empty($wId)) continue;
                
                $cond = $_POST['condition'][$index] ?? 'Serviceable';
                $rem = $_POST['weapon_remarks'][$index] ?? null;
                
                Database::insert('return_weapons', [
                    'return_id' => $returnId,
                    'weapon_id' => $wId,
                    'condition' => $cond,
                    'remarks' => $rem
                ]);
                
                Database::update('weapons_inventory', [
                    'current_location' => 'Armoury',
                    'custodian' => null,
                    'custodian_rank' => null,
                    'custodian_nis' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$wId]);
            }
        }
        
        // Insert all returned ammunition and update inventory balance
        if (isset($_POST['ammo_id']) && is_array($_POST['ammo_id'])) {
            foreach ($_POST['ammo_id'] as $index => $aId) {
                if (empty($aId)) continue;
                
                $rounds = (int)($_POST['rounds_returned'][$index] ?? 0);
                $cond = $_POST['ammo_condition'][$index] ?? 'Serviceable';
                $rem = $_POST['ammo_remarks'][$index] ?? null;
                
                Database::insert('return_ammunition', [
                    'return_id' => $returnId,
                    'ammo_id' => $aId,
                    'rounds_returned' => $rounds,
                    'condition' => $cond,
                    'remarks' => $rem
                ]);
                
                if ($rounds > 0) {
                    $ammo = Database::fetchOne("SELECT * FROM ammunition_inventory WHERE id = ?", [$aId]);
                    if ($ammo) {
                        $newIssued = ($ammo['quantity_issued'] ?? 0) - $rounds;
                        if ($newIssued < 0) $newIssued = 0;
                        $newBalance = ($ammo['quantity_received'] ?? 0) - $newIssued;
                        
                        Database::update('ammunition_inventory', [
                            'quantity_issued' => $newIssued,
                            'balance' => $newBalance,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = ?', [$aId]);
                    }
                }
            }
        }
        
        Database::commit();
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logCreate('returns', $returnId, $_POST);
        }
        
        $this->redirect('returns', ['success' => 'Return created successfully']);
        
    } catch (Exception $e) {
        Database::rollBack();
        error_log("Return creation error: " . $e->getMessage());
        Session::set('errors', ['general' => 'Failed to create return: ' . $e->getMessage()]);
        Session::set('old', $_POST);
        $this->redirect('returns/create');
    }
}
    /**
     * Show return details
     */
    public function show($id) {
        // Check permission
        if (!Auth::can('returns.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view returns']);
            return;
        }
        
        $return = Database::fetchOne(
            "SELECT r.*, u.full_name as received_by_name, req.requisition_number,
                    creator.full_name as created_by_name
             FROM returns r
             LEFT JOIN users u ON r.received_by = u.id
             LEFT JOIN requisitions req ON r.requisition_id = req.id
             LEFT JOIN users creator ON r.created_by = creator.id
             WHERE r.id = ?",
            [$id]
        );
        
        if (!$return) {
            $this->redirect('returns', ['error' => 'Return not found']);
            return;
        }
        
        // Get returned weapons
        $weapons = Database::fetchAll(
            "SELECT rw.*, 
                    wi.weapon_id as inventory_code, 
                    wi.weapon_id, 
                    wi.make_model, 
                    wi.serial_no, 
                    wi.serial_no as serial_number, 
                    COALESCE(wt.type_name, rw.weapon_type, 'Weapon') as weapon_type
             FROM return_weapons rw
             JOIN weapons_inventory wi ON rw.weapon_id = wi.id
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             WHERE rw.return_id = ?",
            [$id]
        ) ?: [];
        
        // Get returned ammunition
        $ammunition = Database::fetchAll(
            "SELECT ra.*, 
                    ai.ammo_id as inventory_code, 
                    ai.ammo_id, 
                    ai.batch_number, 
                    COALESCE(at.ammo_type, 'Ammunition') as ammo_type, 
                    ac.calibre
             FROM return_ammunition ra
             JOIN ammunition_inventory ai ON ra.ammo_id = ai.id
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             WHERE ra.return_id = ?",
            [$id]
        ) ?: [];
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logView('returns', $id);
        }

        $this->view('returns/show', [
            'return' => $return,
            'weapons' => $weapons,
            'ammunition' => $ammunition
        ]);
    }

    /**
     * Return true if the current user is allowed to reach this return —
     * command-restricted roles only reach returns tied to a requisition
     * from their own command (mirrors index()'s scoping).
     */
    private function canAccessReturn(array $return): bool {
        if (!Auth::isCommandRestricted()) {
            return true;
        }
        if (empty($return['requisition_id'])) {
            return false;
        }
        $req = Database::fetchOne(
            "SELECT requesting_command_id FROM requisitions WHERE id = ?",
            [$return['requisition_id']]
        );
        return $req && (int) $req['requesting_command_id'] === (int) Auth::commandId();
    }

    /**
     * Show edit return form
     */
    public function edit($id) {
        if (!Auth::can('returns.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit returns']);
            return;
        }

        $return = Database::fetchOne("SELECT * FROM returns WHERE id = ?", [$id]);
        if (!$return) {
            $this->redirect('returns', ['error' => 'Return not found']);
            return;
        }

        if (!$this->canAccessReturn($return)) {
            $this->redirect('returns', ['error' => 'You do not have permission to edit this return']);
            return;
        }

        // Weapons/ammunition currently on this return, in the exact shape
        // views/returns/edit.php expects (it's shared with create.php's
        // markup, which is why the keys mirror what a fresh weapons_inventory/
        // ammunition_inventory row + join would look like).
        $weapons = Database::fetchAll(
            "SELECT rw.id as return_weapon_id, rw.weapon_id, rw.condition, rw.remarks,
                    wt.type_name as weapon_type, wi.serial_no as serial_number
             FROM return_weapons rw
             JOIN weapons_inventory wi ON rw.weapon_id = wi.id
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             WHERE rw.return_id = ?",
            [$id]
        ) ?: [];

        $ammunition = Database::fetchAll(
            "SELECT ra.id as return_ammo_id, ra.ammo_id, ra.rounds_returned, ra.rounds_used, ra.condition, ra.remarks,
                    ai.batch_number, ai.quantity_issued as rounds_issued
             FROM return_ammunition ra
             JOIN ammunition_inventory ai ON ra.ammo_id = ai.id
             WHERE ra.return_id = ?",
            [$id]
        ) ?: [];

        // The weapon/ammo picklists are normally "currently issued" (mirrors
        // create()'s query) — but a weapon/ammo already on THIS return is no
        // longer marked Issued (the return already moved it back to Armoury/
        // added its rounds back), so it has to be force-included or its
        // <option> — and the admin's existing selection — would silently
        // vanish from the dropdown.
        $returnedWeaponIds = array_map('intval', array_column($weapons, 'weapon_id'));
        $weaponIncludeSql = $returnedWeaponIds ? (' OR wi.id IN (' . implode(',', $returnedWeaponIds) . ')') : '';
        $availableWeapons = Database::fetchAll(
            "SELECT wi.*, wt.type_name
             FROM weapons_inventory wi
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             WHERE wi.current_location = 'Issued'{$weaponIncludeSql}
             ORDER BY wi.weapon_id"
        ) ?: [];

        $returnedAmmoIds = array_map('intval', array_column($ammunition, 'ammo_id'));
        $ammoIncludeSql = $returnedAmmoIds ? (' OR ai.id IN (' . implode(',', $returnedAmmoIds) . ')') : '';
        $issuedAmmunition = Database::fetchAll(
            "SELECT ai.*, at.ammo_type, ac.calibre
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             WHERE ai.quantity_issued > 0{$ammoIncludeSql}
             ORDER BY ai.ammo_id"
        ) ?: [];

        $issuedRequisitions = Database::fetchAll(
            "SELECT r.*, u.full_name as requesting_officer_name
             FROM requisitions r
             LEFT JOIN users u ON r.requesting_officer_id = u.id
             ORDER BY r.created_at DESC"
        ) ?: [];

        $this->view('returns/edit', [
            'return' => $return,
            'weapons' => $weapons,
            'ammunition' => $ammunition,
            'availableWeapons' => $availableWeapons,
            'issuedAmmunition' => $issuedAmmunition,
            'issuedRequisitions' => $issuedRequisitions,
        ]);
    }

    /**
     * Update an existing return.
     *
     * The weapon/ammo side-effects from store() were already applied the
     * moment this return was created (weapons moved to Armoury, ammo balances
     * adjusted) — this can't just re-run store()'s logic. Instead: fully
     * UNDO every existing line item's effect, then re-apply the submitted
     * line items from scratch. That's simpler and more robust than computing
     * per-row deltas (handles a weapon being swapped out, rounds_returned
     * being changed, a line being removed entirely — all as one mechanism),
     * at the cost of doing a little redundant work for lines that didn't
     * actually change.
     */
    public function update($id) {
        if (!Auth::can('returns.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit returns']);
            return;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("returns/edit/$id", ['error' => 'Invalid security token']);
            return;
        }

        $return = Database::fetchOne("SELECT * FROM returns WHERE id = ?", [$id]);
        if (!$return) {
            $this->redirect('returns', ['error' => 'Return not found']);
            return;
        }

        if (!$this->canAccessReturn($return)) {
            $this->redirect('returns', ['error' => 'You do not have permission to edit this return']);
            return;
        }

        if ($return['status'] !== 'Pending') {
            $this->redirect("returns/show/$id", ['error' => 'Only pending returns can be edited — this one has already been processed.']);
            return;
        }

        // Same validation as store()
        $errors = [];

        if (empty($_POST['return_date'])) {
            $errors['return_date'] = 'Return date is required';
        }
        if (empty($_POST['return_type'])) {
            $errors['return_type'] = 'Return type is required';
        }
        if (empty($_POST['returning_officer_name'])) {
            $errors['returning_officer_name'] = 'Officer name is required';
        } elseif (!isValidName($_POST['returning_officer_name'])) {
            $errors['returning_officer_name'] = "Officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        if (empty($_POST['returning_rank'])) {
            $errors['returning_rank'] = 'Rank is required';
        }
        if (empty($_POST['returning_nis'])) {
            $errors['returning_nis'] = 'NIS number is required';
        } elseif (!isDigitsOnly($_POST['returning_nis'])) {
            $errors['returning_nis'] = 'NIS number must contain numbers only';
        }
        if (empty($_POST['returning_unit'])) {
            $errors['returning_unit'] = 'Unit is required';
        }

        $returnType = $_POST['return_type'] ?? '';
        $hasWeapon = isset($_POST['weapon_id']) && is_array($_POST['weapon_id']) && !empty(array_filter($_POST['weapon_id']));
        $hasAmmo = isset($_POST['ammo_id']) && is_array($_POST['ammo_id']) && !empty(array_filter($_POST['ammo_id']));

        if ($returnType === 'Weapon' && !$hasWeapon) {
            $errors['weapons'] = 'At least one weapon is required';
        }
        if ($returnType === 'Ammunition' && !$hasAmmo) {
            $errors['ammunition'] = 'At least one ammunition is required';
        }
        if ($returnType === 'Both' && (!$hasWeapon || !$hasAmmo)) {
            $errors['items'] = 'Both weapons and ammunition are required';
        }

        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("returns/edit/$id");
            return;
        }

        Database::beginTransaction();

        try {
            // ---- Weapons: undo every existing line, then re-apply the submitted set ----
            $oldWeaponIds = array_map('intval', array_column(
                Database::fetchAll("SELECT weapon_id FROM return_weapons WHERE return_id = ?", [$id]) ?: [],
                'weapon_id'
            ));

            $newWeaponIds = [];
            if (isset($_POST['weapon_id']) && is_array($_POST['weapon_id'])) {
                foreach ($_POST['weapon_id'] as $wId) {
                    if (!empty($wId)) $newWeaponIds[] = (int) $wId;
                }
            }

            // Anything dropped from the list goes back to Issued — this
            // return no longer accounts for it having come back in.
            foreach (array_diff($oldWeaponIds, $newWeaponIds) as $droppedId) {
                Database::update('weapons_inventory', [
                    'current_location' => 'Issued',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$droppedId]);
            }

            Database::delete('return_weapons', 'return_id = ?', [$id]);

            if (isset($_POST['weapon_id']) && is_array($_POST['weapon_id'])) {
                foreach ($_POST['weapon_id'] as $index => $wId) {
                    if (empty($wId)) continue;
                    $wId = (int) $wId;
                    $cond = $_POST['condition'][$index] ?? 'Serviceable';
                    $rem = $_POST['weapon_remarks'][$index] ?? null;

                    Database::insert('return_weapons', [
                        'return_id' => $id,
                        'weapon_id' => $wId,
                        'condition' => $cond,
                        'remarks' => $rem
                    ]);

                    // Idempotent for weapons that were already on this return
                    // (already in Armoury) — only meaningfully changes state
                    // for weapons newly added to the list.
                    Database::update('weapons_inventory', [
                        'current_location' => 'Armoury',
                        'custodian' => null,
                        'custodian_rank' => null,
                        'custodian_nis' => null,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$wId]);
                }
            }

            // ---- Ammunition: undo every existing line's balance effect, then re-apply ----
            $oldAmmoRows = Database::fetchAll(
                "SELECT ammo_id, rounds_returned FROM return_ammunition WHERE return_id = ?", [$id]
            ) ?: [];

            foreach ($oldAmmoRows as $oa) {
                $rounds = (int) ($oa['rounds_returned'] ?? 0);
                if ($rounds <= 0) continue;
                $ammo = Database::fetchOne("SELECT * FROM ammunition_inventory WHERE id = ?", [$oa['ammo_id']]);
                if (!$ammo) continue;
                $restoredIssued = ($ammo['quantity_issued'] ?? 0) + $rounds;
                $restoredBalance = ($ammo['quantity_received'] ?? 0) - $restoredIssued;
                Database::update('ammunition_inventory', [
                    'quantity_issued' => $restoredIssued,
                    'balance' => $restoredBalance,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$oa['ammo_id']]);
            }

            Database::delete('return_ammunition', 'return_id = ?', [$id]);

            if (isset($_POST['ammo_id']) && is_array($_POST['ammo_id'])) {
                foreach ($_POST['ammo_id'] as $index => $aId) {
                    if (empty($aId)) continue;
                    $aId = (int) $aId;
                    $rounds = (int) ($_POST['rounds_returned'][$index] ?? 0);
                    $cond = $_POST['ammo_condition'][$index] ?? 'Serviceable';
                    $rem = $_POST['ammo_remarks'][$index] ?? null;

                    Database::insert('return_ammunition', [
                        'return_id' => $id,
                        'ammo_id' => $aId,
                        'rounds_returned' => $rounds,
                        'condition' => $cond,
                        'remarks' => $rem
                    ]);

                    if ($rounds > 0) {
                        $ammo = Database::fetchOne("SELECT * FROM ammunition_inventory WHERE id = ?", [$aId]);
                        if ($ammo) {
                            $newIssued = max(0, ($ammo['quantity_issued'] ?? 0) - $rounds);
                            $newBalance = ($ammo['quantity_received'] ?? 0) - $newIssued;
                            Database::update('ammunition_inventory', [
                                'quantity_issued' => $newIssued,
                                'balance' => $newBalance,
                                'updated_at' => date('Y-m-d H:i:s')
                            ], 'id = ?', [$aId]);
                        }
                    }
                }
            }

            Database::update('returns', [
                'return_date' => $_POST['return_date'],
                'return_type' => $_POST['return_type'],
                'requisition_id' => !empty($_POST['requisition_id']) ? $_POST['requisition_id'] : null,
                'returning_officer_name' => $_POST['returning_officer_name'],
                'returning_rank' => $_POST['returning_rank'],
                'returning_nis' => $_POST['returning_nis'],
                'returning_unit' => $_POST['returning_unit'],
                'remarks' => $_POST['remarks'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            Database::commit();

            if (class_exists('AuditLogger')) {
                AuditLogger::logUpdate('returns', $id, $return, $_POST);
            }

            $this->redirect("returns/show/$id", ['success' => 'Return updated successfully']);

        } catch (Exception $e) {
            Database::rollBack();
            error_log("Return update error: " . $e->getMessage());
            Session::set('errors', ['general' => 'Failed to update return: ' . $e->getMessage()]);
            $this->redirect("returns/edit/$id");
        }
    }

    /**
     * Delete a return. Only Pending returns can be deleted — once a return
     * is Processed it's part of the permanent inventory record. Reverses
     * every weapon/ammunition side-effect this return applied before
     * removing the rows, so deleting never leaves inventory out of sync.
     */
    public function delete($id) {
        if (!Auth::can('returns.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete returns']);
            return;
        }

        $return = Database::fetchOne("SELECT * FROM returns WHERE id = ?", [$id]);
        if (!$return) {
            $this->redirect('returns', ['error' => 'Return not found']);
            return;
        }

        if (!$this->canAccessReturn($return)) {
            $this->redirect('returns', ['error' => 'You do not have permission to delete this return']);
            return;
        }

        if ($return['status'] !== 'Pending') {
            $this->redirect('returns', ['error' => 'Only pending returns can be deleted — this one has already been processed.']);
            return;
        }

        Database::beginTransaction();

        try {
            $weapons = Database::fetchAll("SELECT weapon_id FROM return_weapons WHERE return_id = ?", [$id]) ?: [];
            foreach ($weapons as $w) {
                Database::update('weapons_inventory', [
                    'current_location' => 'Issued',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$w['weapon_id']]);
            }

            $ammoRows = Database::fetchAll(
                "SELECT ammo_id, rounds_returned FROM return_ammunition WHERE return_id = ?", [$id]
            ) ?: [];
            foreach ($ammoRows as $ar) {
                $rounds = (int) ($ar['rounds_returned'] ?? 0);
                if ($rounds <= 0) continue;
                $ammo = Database::fetchOne("SELECT * FROM ammunition_inventory WHERE id = ?", [$ar['ammo_id']]);
                if (!$ammo) continue;
                $restoredIssued = ($ammo['quantity_issued'] ?? 0) + $rounds;
                $restoredBalance = ($ammo['quantity_received'] ?? 0) - $restoredIssued;
                Database::update('ammunition_inventory', [
                    'quantity_issued' => $restoredIssued,
                    'balance' => $restoredBalance,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$ar['ammo_id']]);
            }

            Database::delete('return_weapons', 'return_id = ?', [$id]);
            Database::delete('return_ammunition', 'return_id = ?', [$id]);
            Database::delete('returns', 'id = ?', [$id]);

            Database::commit();

            if (class_exists('AuditLogger')) {
                AuditLogger::log('DELETE', 'returns', $id, null, "Return {$return['return_number']} deleted");
            }

            $this->redirect('returns', ['success' => 'Return deleted successfully']);

        } catch (Exception $e) {
            Database::rollBack();
            error_log("Return delete error: " . $e->getMessage());
            $this->redirect('returns', ['error' => 'Failed to delete return: ' . $e->getMessage()]);
        }
    }

    /**
     * Process return (mark as received)
     */
    public function process($id) {
        // Check permission
        if (!Auth::can('returns.process')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to process returns']);
            return;
        }
        
        $return = Database::fetchOne("SELECT * FROM returns WHERE id = ?", [$id]);
        
        if (!$return) {
            $this->redirect('returns', ['error' => 'Return not found']);
            return;
        }
        
        if ($return['status'] !== 'Pending') {
            $this->redirect('returns', ['error' => 'Only pending returns can be processed']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('returns', [
                'status' => 'Processed',
                'received_by' => Auth::id(),
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            
            Database::commit();
            
            if (class_exists('AuditLogger')) {
                AuditLogger::log('PROCESS', 'returns', $id, null, 'Return processed');
            }
            
            $this->redirect('returns', ['success' => 'Return processed successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Return process error: " . $e->getMessage());
            $this->redirect("returns/show/$id", ['error' => 'Failed to process return: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Generate unique return number
     */
    private function generateReturnNumber() {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT return_number FROM returns
             WHERE return_number LIKE 'RET-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($last && isset($last['return_number'])) {
            $seq = intval(substr($last['return_number'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("RET-%s%s-%04d", $year, $month, $seq);
    }
    
    /**
     * Export returns to CSV
     */
    public function export() {
        // Check permission
        if (!Auth::can('reports.export')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to export data']);
            return;
        }
        
        $returns = Database::fetchAll(
            "SELECT r.*, u.full_name as received_by_name, req.requisition_number
             FROM returns r
             LEFT JOIN users u ON r.received_by = u.id
             LEFT JOIN requisitions req ON r.requisition_id = req.id
             ORDER BY r.created_at DESC"
        );
        
        if ($returns === false) $returns = [];
        
        $filename = 'returns_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        Security::fputcsv($output, [
            'Return #', 'Date', 'Type', 'Requisition #', 'Returning Officer',
            'Rank', 'NIS Number', 'Unit', 'Status', 'Remarks', 'Created At'
        ]);
        
        // Data
        foreach ($returns as $return) {
            Security::fputcsv($output, [
                $return['return_number'] ?? '',
                $return['return_date'] ?? '',
                $return['return_type'] ?? '',
                $return['requisition_number'] ?? '',
                $return['returning_officer_name'] ?? '',
                $return['returning_rank'] ?? '',
                $return['returning_nis'] ?? '',
                $return['returning_unit'] ?? '',
                $return['status'] ?? '',
                $return['remarks'] ?? '',
                $return['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('returns', 'csv');
        }
        exit;
    }
}