<?php
/**
 * Requisition Controller
 */
class RequisitionController extends Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
    }

    
    public function index() {
        // Check permission using can() instead of requirePermission()
        if (!Auth::can('requisition.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view requisitions']);
            return;
        }
        
        // Calculate statistics efficiently — scoped to the viewer's own
        // command when restricted, so a Command Armorer tracking their own
        // requisitions doesn't see every other command's counts mixed in.
        $statsParams = [];
        // requisitions' command column is requesting_command_id, not the
        // command_id every other scoped table uses — applyCommandFilter()
        // defaults to "command_id" and needs this spelled out, or it
        // silently references a column this table doesn't have (caught by
        // fetchAll()/fetchOne(), which just return empty on the error —
        // see Database::applyCommandFilter()'s docblock).
        $statsSql = Database::applyCommandFilter("SELECT status, COUNT(*) as count FROM requisitions r GROUP BY status", 'r', $statsParams, 'requesting_command_id');
        $statsData = Database::fetchAll($statsSql, $statsParams);
        $statistics = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'issued' => 0,
            'partially_issued' => 0,
            'completed' => 0
        ];
        foreach ($statsData as $row) {
            $status = $row['status'];
            $count = (int)$row['count'];
            $statistics['total'] += $count;
            switch ($status) {
                case 'Pending':
                    $statistics['pending'] = $count;
                    break;
                case 'Approved':
                    $statistics['approved'] = $count;
                    break;
                case 'Rejected':
                    $statistics['rejected'] = $count;
                    break;
                case 'Issued':
                    $statistics['issued'] = $count;
                    break;
                case 'Partially Issued':
                    $statistics['partially_issued'] = $count;
                    break;
                case 'Completed':
                    $statistics['completed'] = $count;
                    break;
            }
        }
        
        $params = [];
        $baseSql = "SELECT r.*, 
                    u.full_name as requester_name,
                    c.command_name,
                    COUNT(ri.id) as item_count
             FROM requisitions r
             LEFT JOIN users u ON r.requesting_officer_id = u.id
             LEFT JOIN commands c ON r.requesting_command_id = c.id
             LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
             GROUP BY r.id
             ORDER BY r.created_at DESC";
        
        // requisitions' command column is requesting_command_id, not
        // paginateTable()'s "command_id" default — see the comment on the
        // stats query above.
        $pagination = paginateTable('requisitions', 'r', ['requisition_number', 'requesting_officer_name'], $baseSql, $params, null, 'requesting_command_id');
        $requisitions = Database::fetchAll($pagination['sql'], $params);
        if ($requisitions === false) $requisitions = [];
        
        // Load the view
        $this->view('requisition/index', [
            'requisitions' => $requisitions,
            'statistics' => $statistics,
            'pagination' => $pagination
        ]);
    }
    
    /**
     * Show create requisition form
     */
    public function create() {
        // Check permission
        if (!Auth::can('requisition.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create requisitions']);
            return;
        }
        
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        $weaponTypes = Database::fetchAll("SELECT * FROM weapon_types WHERE is_active = 1 ORDER BY type_name");
        $ammoTypes = Database::fetchAll("SELECT * FROM ammunition_types WHERE is_active = 1 ORDER BY ammo_type");
        $weaponCalibres = Database::fetchAll("SELECT * FROM weapon_calibres ORDER BY calibre_name");
        $ammoCalibres = Database::fetchAll("SELECT * FROM ammunition_calibres WHERE is_active = 1 ORDER BY calibre");
        
        if ($commands === false) $commands = [];
        if ($weaponTypes === false) $weaponTypes = [];
        if ($ammoTypes === false) $ammoTypes = [];
        if ($weaponCalibres === false) $weaponCalibres = [];
        if ($ammoCalibres === false) $ammoCalibres = [];
        
        $this->view('requisition/create', [
            'commands' => $commands,
            'weaponTypes' => $weaponTypes,
            'ammoTypes' => $ammoTypes,
            'weaponCalibres' => $weaponCalibres,
            'ammoCalibres' => $ammoCalibres
        ]);
    }
    
    /**
     * Store new requisition
     */
    public function store() {
        // Check permission
        if (!Auth::can('requisition.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create requisitions']);
            return;
        }
        
        // Validate CSRF token
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('requisition/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Enforce command_id lock for non-SuperAdmins
        if (!Auth::isSuperAdmin()) {
            $_POST['requesting_command_id'] = Auth::commandId();
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['requisition_date'])) {
            $errors['requisition_date'] = 'Requisition date is required';
        }
        
        if (empty($_POST['priority_level'])) {
            $errors['priority_level'] = 'Priority level is required';
        }
        
        if (empty($_POST['requisition_type'])) {
            $errors['requisition_type'] = 'Requisition type is required';
        }
        
        if (empty($_POST['requesting_officer_name'])) {
            $errors['requesting_officer_name'] = 'Officer name is required';
        } elseif (!isValidName($_POST['requesting_officer_name'])) {
            $errors['requesting_officer_name'] = "Officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        
        if (empty($_POST['requesting_rank'])) {
            $errors['requesting_rank'] = 'Rank is required';
        }
        
        if (empty($_POST['requesting_nis'])) {
            $errors['requesting_nis'] = 'NIS number is required';
        } elseif (!isDigitsOnly($_POST['requesting_nis'])) {
            $errors['requesting_nis'] = 'NIS number must contain numbers only';
        } elseif (strlen(trim($_POST['requesting_nis'])) < 4 || strlen(trim($_POST['requesting_nis'])) > 5) {
            $errors['requesting_nis'] = 'NIS number must be 4 or 5 digits';
        }

        if (empty($_POST['requesting_phone'])) {
            $errors['requesting_phone'] = 'Phone number is required';
        } elseif (!isValidPhone($_POST['requesting_phone'])) {
            $errors['requesting_phone'] = 'Phone number must be exactly 11 digits';
        }
        
        if (empty($_POST['requesting_command_id'])) {
            $errors['requesting_command_id'] = 'Command is required';
        }
        
        if (empty($_POST['justification'])) {
            $errors['justification'] = 'Justification is required';
        }
        
        // Check if at least one item is provided
        if (!isset($_POST['item_type']) || empty(array_filter($_POST['item_type']))) {
            $errors['items'] = 'At least one requisition item is required';
        }
        
        // If there are validation errors, redirect back with errors and old input
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('requisition/create');
            return;
        }
        
        $requisitionNumber = $this->generateRequisitionNumber();
        
        Database::beginTransaction();
        
        try {
            // Determine status (Draft or Pending) and approval stage
            $status = isset($_POST['status']) && $_POST['status'] === 'Draft' ? 'Draft' : 'Pending';
            $approvalStage = ($status === 'Draft') ? 'Command_Entry' : 'Command_Approval';
            // Determine requisition_type if empty

            $requisitionType = $_POST['requisition_type'] ?? '';
            if (empty($requisitionType)) {
                $itemTypes = array_unique(array_filter($_POST['item_type'] ?? []));
                if (count($itemTypes) === 1) {
                    $requisitionType = reset($itemTypes);
                } elseif (count($itemTypes) > 1) {
                    $requisitionType = 'Both';
                } else {
                    $requisitionType = 'Both';
                }
            }

            $requisitionId = Database::insert('requisitions', [
                'requisition_number' => $requisitionNumber,
                'requisition_date' => $_POST['requisition_date'],
                'requisition_type' => $requisitionType,
                'priority_level' => $_POST['priority_level'],
                'requesting_officer_id' => Auth::id(),
                'requesting_officer_name' => $_POST['requesting_officer_name'],
                'requesting_rank' => $_POST['requesting_rank'],
                'requesting_nis' => $_POST['requesting_nis'],
                'requesting_phone' => $_POST['requesting_phone'],
                'requesting_command_id' => $_POST['requesting_command_id'],
                'justification' => $_POST['justification'],
                'expected_return_date' => !empty($_POST['expected_return_date']) ? $_POST['expected_return_date'] : null,
                'status' => $status,
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id(),
                'approval_stage' => $approvalStage,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($requisitionId && $status === 'Pending') {
                if (class_exists('Notification')) {
                    Notification::sendToRoleInCommand(
                        'Command Approval Officer', 
                        $_POST['requesting_command_id'], 
                        "A new requisition ({$requisitionNumber}) has been submitted for approval.",
                        "/requisition/show/{$requisitionId}"
                    );
                }
            }
            
            if (!$requisitionId) {
                throw new Exception("Failed to create requisition");
            }
            
            // Insert items
            foreach ($_POST['item_type'] as $index => $itemType) {
                if (empty($itemType)) continue;
                
                $weaponTypeId = null;
                $weaponTypeOther = null;
                $ammoTypeId = null;
                $ammoTypeOther = null;
                $calibreId = null;
                $calibreOther = null;
                
                if ($itemType === 'Weapon') {
                    $weaponTypeId = !empty($_POST['weapon_type_id'][$index]) && $_POST['weapon_type_id'][$index] !== 'other' ? $_POST['weapon_type_id'][$index] : null;
                    if (($_POST['weapon_type_id'][$index] ?? '') === 'other') {
                        $weaponTypeOther = $_POST['weapon_type_other'][$index] ?? null;
                    }
                } elseif ($itemType === 'Ammunition') {
                    $ammoTypeId = !empty($_POST['ammo_type_id'][$index]) && $_POST['ammo_type_id'][$index] !== 'other' ? $_POST['ammo_type_id'][$index] : null;
                    if (($_POST['ammo_type_id'][$index] ?? '') === 'other') {
                        $ammoTypeOther = $_POST['ammo_type_other'][$index] ?? null;
                    }
                }
                
                // Handle calibre
                $calibreId = !empty($_POST['calibre_id'][$index]) && $_POST['calibre_id'][$index] !== 'other' ? $_POST['calibre_id'][$index] : null;
                if (($_POST['calibre_id'][$index] ?? '') === 'other') {
                    $calibreOther = $_POST['calibre_other'][$index] ?? null;
                }
                
                // Handle purpose
                $purpose = $_POST['purpose'][$index] ?? '';
                if ($purpose === 'Other') {
                    $purpose = $_POST['purpose_other'][$index] ?? 'Other';
                }
                
                $itemId = Database::insert('requisition_items', [
                    'requisition_id' => $requisitionId,
                    'item_type' => $itemType,
                    'weapon_type_id' => $weaponTypeId,
                    'weapon_type_other' => $weaponTypeOther,
                    'ammo_type_id' => $ammoTypeId,
                    'ammo_type_other' => $ammoTypeOther,
                    'calibre_id' => $calibreId,
                    'calibre_other' => $calibreOther,
                    'quantity' => $_POST['quantity'][$index] ?? 1,
                    'purpose' => $purpose,
                    'remarks' => $_POST['item_remarks'][$index] ?? null,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if (!$itemId) {
                    throw new Exception("Failed to create requisition item");
                }
            }
            
            Database::commit();
            
            if (class_exists('AuditLogger')) {
                AuditLogger::logCreate('requisitions', $requisitionId, $_POST);
            }
            
            $successMessage = $status === 'Draft' ? 'Requisition saved as draft' : 'Requisition submitted successfully';
            $this->redirect('requisition', ['success' => $successMessage]);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Requisition creation error: " . $e->getMessage());
            Session::set('errors', ['general' => 'Failed to submit requisition: ' . $e->getMessage()]);
            Session::set('old', $_POST);
            $this->redirect('requisition/create');
        }
    }
    
    /**
     * Show requisition details
     */
    public function show($id) {
        // Check permission
        if (!Auth::can('requisition.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view requisitions']);
            return;
        }
         $requisition = Database::fetchOne(
            "SELECT r.*, 
                    creator.full_name as created_by_name,
                    approver.full_name as approved_by_name,
                    cmd_app.full_name as command_approved_by_name,
                    hq_vet.full_name as hq_vetted_by_name,
                    issuer.full_name as issued_by_name,
                    c.command_name,
                    z.zone_name
             FROM requisitions r
             LEFT JOIN users creator ON r.created_by = creator.id
             LEFT JOIN users approver ON r.approved_by = approver.id
             LEFT JOIN users cmd_app ON r.command_approved_by = cmd_app.id
             LEFT JOIN users hq_vet ON r.hq_vetted_by = hq_vet.id
             LEFT JOIN users issuer ON r.issued_by = issuer.id
             LEFT JOIN commands c ON r.requesting_command_id = c.id
             LEFT JOIN zones z ON c.zone_id = z.id
             WHERE r.id = ?",
            [$id]
        );
        
        if (!$requisition) {
            $this->redirect('requisition', ['error' => 'Requisition not found']);
            return;
        }
        
        $items = Database::fetchAll(
            "SELECT ri.*,
                    wt.type_name as weapon_type_name,
                    at.ammo_type as ammo_type_name,
                    COALESCE(wc.calibre_name, ac.calibre, ri.calibre_other, '-') as calibre_name,
                    CASE 
                        WHEN ri.item_type = 'Weapon' THEN COALESCE(wt.type_name, ri.weapon_type_other, 'Weapon')
                        WHEN ri.item_type = 'Ammunition' THEN COALESCE(at.ammo_type, ri.ammo_type_other, 'Ammunition')
                        ELSE COALESCE(ri.item_type, 'Asset')
                    END as item_display_name
             FROM requisition_items ri
             LEFT JOIN weapon_types wt ON ri.weapon_type_id = wt.id
             LEFT JOIN ammunition_types at ON ri.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON (ri.item_type = 'Ammunition' AND ri.calibre_id = ac.id)
             LEFT JOIN weapon_calibres wc ON (ri.item_type = 'Weapon' AND ri.calibre_id = wc.id)
             WHERE ri.requisition_id = ?
             ORDER BY ri.id",
            [$id]
        );
        
        if ($items === false) $items = [];
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logView('requisitions', $id);
        }
        
        $this->view('requisition/show', [
            'requisition' => $requisition,
            'items' => $items
        ]);
    }
    
    /**
     * Show edit requisition form
     */
    public function edit($id) {
        // Check permission
        if (!Auth::can('requisition.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit requisitions']);
            return;
        }
        
        $requisition = Database::fetchOne("SELECT * FROM requisitions WHERE id = ?", [$id]);
        
        if (!$requisition) {
            $this->redirect('requisition', ['error' => 'Requisition not found']);
            return;
        }
        
        if (!in_array($requisition['status'], ['Pending', 'Draft'])) {
            $this->redirect('requisition', ['error' => 'Cannot edit requisition that is not pending']);
            return;
        }
        
        $items = Database::fetchAll("SELECT * FROM requisition_items WHERE requisition_id = ?", [$id]);
        if ($items === false) $items = [];
        
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        $weaponTypes = Database::fetchAll("SELECT * FROM weapon_types WHERE is_active = 1 ORDER BY type_name");
        $ammoTypes = Database::fetchAll("SELECT * FROM ammunition_types WHERE is_active = 1 ORDER BY ammo_type");
        $weaponCalibres = Database::fetchAll("SELECT * FROM weapon_calibres ORDER BY calibre_name");
        $ammoCalibres = Database::fetchAll("SELECT * FROM ammunition_calibres WHERE is_active = 1 ORDER BY calibre");
        
        if ($commands === false) $commands = [];
        if ($weaponTypes === false) $weaponTypes = [];
        if ($ammoTypes === false) $ammoTypes = [];
        if ($weaponCalibres === false) $weaponCalibres = [];
        if ($ammoCalibres === false) $ammoCalibres = [];
        
        $this->view('requisition/edit', [
            'requisition' => $requisition,
            'items' => $items,
            'commands' => $commands,
            'weaponTypes' => $weaponTypes,
            'ammoTypes' => $ammoTypes,
            'weaponCalibres' => $weaponCalibres,
            'ammoCalibres' => $ammoCalibres
        ]);
    }
    
    /**
     * Update requisition
     */
    public function update($id) {
        // Check permission
        if (!Auth::can('requisition.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit requisitions']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("requisition/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        // Enforce command_id lock for non-SuperAdmins
        if (!Auth::isSuperAdmin()) {
            $_POST['requesting_command_id'] = Auth::commandId();
        }
        
        $requisition = Database::fetchOne("SELECT * FROM requisitions WHERE id = ?", [$id]);
        
        if (!$requisition) {
            $this->redirect('requisition', ['error' => 'Requisition not found']);
            return;
        }
        
        if (!in_array($requisition['status'], ['Pending', 'Draft'])) {
            $this->redirect('requisition', ['error' => 'Cannot edit requisition that is not pending']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['requisition_date'])) {
            $errors['requisition_date'] = 'Requisition date is required';
        }
        
        if (empty($_POST['priority_level'])) {
            $errors['priority_level'] = 'Priority level is required';
        }
        
        if (empty($_POST['requesting_officer_name'])) {
            $errors['requesting_officer_name'] = 'Officer name is required';
        } elseif (!isValidName($_POST['requesting_officer_name'])) {
            $errors['requesting_officer_name'] = "Officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        
        if (empty($_POST['requesting_rank'])) {
            $errors['requesting_rank'] = 'Rank is required';
        }
        
        if (empty($_POST['requesting_nis'])) {
            $errors['requesting_nis'] = 'NIS number is required';
        } elseif (!isDigitsOnly($_POST['requesting_nis'])) {
            $errors['requesting_nis'] = 'NIS number must contain numbers only';
        } elseif (strlen(trim($_POST['requesting_nis'])) < 4 || strlen(trim($_POST['requesting_nis'])) > 5) {
            $errors['requesting_nis'] = 'NIS number must be 4 or 5 digits';
        }

        if (empty($_POST['requesting_phone'])) {
            $errors['requesting_phone'] = 'Phone number is required';
        } elseif (!isValidPhone($_POST['requesting_phone'])) {
            $errors['requesting_phone'] = 'Phone number must be exactly 11 digits';
        }
        
        if (empty($_POST['requesting_command_id'])) {
            $errors['requesting_command_id'] = 'Command is required';
        }
        
        if (empty($_POST['justification'])) {
            $errors['justification'] = 'Justification is required';
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("requisition/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $status = isset($_POST['status']) && $_POST['status'] === 'Draft' ? 'Draft' : 'Pending';
            $approvalStage = ($status === 'Draft') ? 'Command_Entry' : 'Command_Approval';

            // Determine requisition_type if empty
            $requisitionType = $_POST['requisition_type'] ?? '';
            if (empty($requisitionType)) {
                $itemTypes = array_unique(array_filter($_POST['item_type'] ?? []));
                if (count($itemTypes) === 1) {
                    $requisitionType = reset($itemTypes);
                } elseif (count($itemTypes) > 1) {
                    $requisitionType = 'Both';
                } else {
                    $requisitionType = 'Both';
                }
            }

            Database::update('requisitions', [
                'requisition_date' => $_POST['requisition_date'],
                'requisition_type' => $requisitionType,
                'priority_level' => $_POST['priority_level'],
                'requesting_officer_name' => $_POST['requesting_officer_name'],
                'requesting_rank' => $_POST['requesting_rank'],
                'requesting_nis' => $_POST['requesting_nis'],
                'requesting_phone' => $_POST['requesting_phone'],
                'requesting_command_id' => $_POST['requesting_command_id'],
                'justification' => $_POST['justification'],
                'expected_return_date' => !empty($_POST['expected_return_date']) ? $_POST['expected_return_date'] : null,
                'status' => $status,
                'approval_stage' => $approvalStage,
                'remarks' => $_POST['remarks'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            
            if ($status === 'Pending' && $requisition['status'] === 'Draft') {
                if (class_exists('Notification')) {
                    Notification::sendToRoleInCommand(
                        'Command Approval Officer', 
                        $_POST['requesting_command_id'], 
                        "A new requisition ({$requisition['requisition_number']}) has been submitted for approval.",
                        "/requisition/show/{$id}"
                    );
                }
            }
            
            // Delete existing items
            Database::delete('requisition_items', 'requisition_id = ?', [$id]);
            
            // Insert updated items
            foreach ($_POST['item_type'] as $index => $itemType) {
                if (empty($itemType)) continue;
                
                $weaponTypeId = null;
                $weaponTypeOther = null;
                $ammoTypeId = null;
                $ammoTypeOther = null;
                $calibreId = null;
                $calibreOther = null;
                
                if ($itemType === 'Weapon') {
                    $weaponTypeId = !empty($_POST['weapon_type_id'][$index]) && $_POST['weapon_type_id'][$index] !== 'other' ? $_POST['weapon_type_id'][$index] : null;
                    if (($_POST['weapon_type_id'][$index] ?? '') === 'other') {
                        $weaponTypeOther = $_POST['weapon_type_other'][$index] ?? null;
                    }
                } elseif ($itemType === 'Ammunition') {
                    $ammoTypeId = !empty($_POST['ammo_type_id'][$index]) && $_POST['ammo_type_id'][$index] !== 'other' ? $_POST['ammo_type_id'][$index] : null;
                    if (($_POST['ammo_type_id'][$index] ?? '') === 'other') {
                        $ammoTypeOther = $_POST['ammo_type_other'][$index] ?? null;
                    }
                }
                
                // Handle calibre
                $calibreId = !empty($_POST['calibre_id'][$index]) && $_POST['calibre_id'][$index] !== 'other' ? $_POST['calibre_id'][$index] : null;
                if (($_POST['calibre_id'][$index] ?? '') === 'other') {
                    $calibreOther = $_POST['calibre_other'][$index] ?? null;
                }
                
                // Handle purpose
                $purpose = $_POST['purpose'][$index] ?? '';
                if ($purpose === 'Other') {
                    $purpose = $_POST['purpose_other'][$index] ?? 'Other';
                }
                
                Database::insert('requisition_items', [
                    'requisition_id' => $id,
                    'item_type' => $itemType,
                    'weapon_type_id' => $weaponTypeId,
                    'weapon_type_other' => $weaponTypeOther,
                    'ammo_type_id' => $ammoTypeId,
                    'ammo_type_other' => $ammoTypeOther,
                    'calibre_id' => $calibreId,
                    'calibre_other' => $calibreOther,
                    'quantity' => $_POST['quantity'][$index] ?? 1,
                    'purpose' => $purpose,
                    'remarks' => $_POST['item_remarks'][$index] ?? null,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            Database::commit();
            
            if (class_exists('AuditLogger')) {
                AuditLogger::logUpdate('requisitions', $id, $requisition, $_POST);
            }
            
            $this->redirect("requisition/show/$id", ['success' => 'Requisition updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Requisition update error: " . $e->getMessage());
            $this->redirect("requisition/edit/$id", ['error' => 'Failed to update requisition: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Delete requisition
     */
    public function delete($id) {
        // Check permission
        if (!Auth::can('requisition.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete requisitions']);
            return;
        }
        
        $requisition = Database::fetchOne("SELECT * FROM requisitions WHERE id = ?", [$id]);
        
        if (!$requisition) {
            $this->redirect('requisition', ['error' => 'Requisition not found']);
            return;
        }
        
        if (!in_array($requisition['status'], ['Pending', 'Draft'])) {
            $this->redirect('requisition', ['error' => 'Cannot delete requisition that is not pending']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::delete('requisition_items', 'requisition_id = ?', [$id]);
            Database::delete('requisitions', 'id = ?', [$id]);
            
            Database::commit();
            
            if (class_exists('AuditLogger')) {
                AuditLogger::logDelete('requisitions', $id, $requisition);
            }
            
            $this->redirect('requisition', ['success' => 'Requisition deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Requisition deletion error: " . $e->getMessage());
            $this->redirect('requisition', ['error' => 'Failed to delete requisition: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Approve requisition
     */
    public function approve($id) {
        // Check permission
        if (!Auth::can('requisition.approve')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to approve requisitions']);
            return;
        }
        
        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("requisition/show/$id", ['error' => 'Invalid request method']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("requisition/show/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $requisition = Database::fetchOne("SELECT * FROM requisitions WHERE id = ?", [$id]);
        
        if (!$requisition) {
            $this->redirect('requisition', ['error' => 'Requisition not found']);
            return;
        }
        
        if ($requisition['status'] !== 'Pending') {
            $this->redirect('requisition', ['error' => 'Only pending requisitions can be approved']);
            return;
        }
        
        $currentStage = $requisition['approval_stage'] ?? 'Command_Approval';
        $userRoles = $_SESSION['roles'] ?? [];
        
        Database::beginTransaction();
        
        try {
            $remarks = $_POST['approval_remarks'] ?? '';
            $updateData = ['updated_at' => date('Y-m-d H:i:s')];
            $nextStage = $currentStage;
            $msg = '';
            
            if ($currentStage === 'Command_Approval') {
                if (!in_array('Command Approval Officer', $userRoles, true) || Auth::commandId() != $requisition['requesting_command_id']) {
                    throw new Exception("Only the Command Approval Officer for this command can approve at this stage.");
                }
                
                $nextStage = 'HQ_Vetting';
                $updateData['command_approved_by'] = Auth::id();
                $updateData['command_approval_date'] = date('Y-m-d H:i:s');
                $updateData['command_approval_remarks'] = $remarks;
                $updateData['approval_stage'] = $nextStage;
                
                $msg = "Requisition approved by Command and forwarded to HQ Armorer for vetting.";
                
                if (class_exists('Notification')) {
                    Notification::sendToRole('HQ Armorer', "Requisition {$requisition['requisition_number']} is pending HQ vetting.", "/requisition/show/{$id}");
                    Notification::send($requisition['requesting_officer_id'], "Your requisition {$requisition['requisition_number']} was approved by Command and forwarded to HQ.", "/requisition/show/{$id}");
                }
                
            } elseif ($currentStage === 'HQ_Vetting') {
                if (!in_array('HQ Armorer', $userRoles, true)) {
                    throw new Exception("Only an HQ Armorer can approve at this stage.");
                }

                $nextStage = 'Armorer_Issue';
                $updateData['hq_vetted_by'] = Auth::id();
                $updateData['hq_vetting_date'] = date('Y-m-d H:i:s');
                $updateData['hq_vetting_remarks'] = $remarks;
                $updateData['approval_stage'] = $nextStage;
                $updateData['status'] = 'Approved';

                $updateData['approved_by'] = Auth::id();
                $updateData['approval_date'] = date('Y-m-d H:i:s');
                $updateData['approval_remarks'] = $remarks;

                $msg = "Requisition approved by HQ Armorer and moved to Armorer for issuance.";

                if (class_exists('Notification')) {
                    // Notify Armorer for fulfillment
                    Notification::sendToRole('Armorer', "Requisition {$requisition['requisition_number']} has been approved and is ready to issue.", "/weapon_issue?requisition_id={$id}");
                    Notification::sendToRole('HQ Armorer', "Requisition {$requisition['requisition_number']} has been approved and is ready to issue.", "/weapon_issue?requisition_id={$id}");
                    
                    // Notify Command Armorer (requester)
                    Notification::send($requisition['requesting_officer_id'], "Your requisition {$requisition['requisition_number']} has been approved by HQ and sent to Armorer for issuance.", "/requisition/show/{$id}");
                    
                    // Notify Command Approval Officer
                    if (!empty($requisition['command_approved_by'])) {
                        Notification::send($requisition['command_approved_by'], "Requisition {$requisition['requisition_number']}, which you approved, has been vetted and approved by HQ Armorer.", "/requisition/show/{$id}");
                    }
                }
            } else {
                throw new Exception("Invalid workflow stage.");
            }
            
            Database::update('requisitions', $updateData, 'id = ?', [$id]);
            Database::commit();
            
            if (class_exists('AuditLogger')) {
                AuditLogger::log('APPROVE', 'requisitions', $id, null, $msg);
            }
            
            $this->redirect("requisition/show/$id", ['success' => $msg]);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Requisition approval error: " . $e->getMessage());
            $this->redirect("requisition/show/$id", ['error' => 'Failed to approve requisition: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Reject requisition
     */
    public function reject($id) {
        // Check permission
        if (!Auth::can('requisition.approve')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to reject requisitions']);
            return;
        }
        
        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("requisition/show/$id", ['error' => 'Invalid request method']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("requisition/show/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $requisition = Database::fetchOne("SELECT * FROM requisitions WHERE id = ?", [$id]);
        
        if (!$requisition) {
            $this->redirect('requisition', ['error' => 'Requisition not found']);
            return;
        }
        
        if ($requisition['status'] !== 'Pending') {
            $this->redirect('requisition', ['error' => 'Only pending requisitions can be rejected']);
            return;
        }
        
        if (empty($_POST['rejection_reason'])) {
            $this->redirect("requisition/show/$id", ['error' => 'Rejection reason is required']);
            return;
        }

        $currentStage = $requisition['approval_stage'] ?? 'Command_Approval';
        $userRoles = $_SESSION['roles'] ?? [];

        if ($currentStage === 'Command_Approval') {
            if (!in_array('Command Approval Officer', $userRoles, true) || Auth::commandId() != $requisition['requesting_command_id']) {
                $this->redirect("requisition/show/$id", ['error' => 'Only the Command Approval Officer for this command can reject at this stage.']);
                return;
            }
        } elseif ($currentStage === 'HQ_Vetting') {
            if (!in_array('HQ Armorer', $userRoles, true)) {
                $this->redirect("requisition/show/$id", ['error' => 'Only an HQ Armorer can reject at this stage.']);
                return;
            }
        }

        Database::beginTransaction();

        try {
            Database::update('requisitions', [
                'status' => 'Rejected',
                'approved_by' => Auth::id(),
                'approval_date' => date('Y-m-d H:i:s'),
                'rejection_reason' => $_POST['rejection_reason'],
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            
            Database::commit();
            
            if (class_exists('Notification')) {
                Notification::send($requisition['requesting_officer_id'], "Your requisition {$requisition['requisition_number']} has been rejected. Reason: {$_POST['rejection_reason']}", "/requisition/show/{$id}");
                if (!empty($requisition['command_approved_by']) && $requisition['command_approved_by'] != $requisition['requesting_officer_id']) {
                    Notification::send($requisition['command_approved_by'], "Requisition {$requisition['requisition_number']} was rejected at HQ Vetting. Reason: {$_POST['rejection_reason']}", "/requisition/show/{$id}");
                }
            }
            
            if (class_exists('AuditLogger')) {
                AuditLogger::log('REJECT', 'requisitions', $id, null, 'Requisition rejected: ' . $_POST['rejection_reason']);
            }
            
            $this->redirect("requisition/show/$id", ['success' => 'Requisition rejected successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Requisition rejection error: " . $e->getMessage());
            $this->redirect("requisition/show/$id", ['error' => 'Failed to reject requisition: ' . $e->getMessage()]);
        }
    }
    
    public function my() {
        // Check if user is logged in
        if (!Auth::check()) {
            $this->redirect('auth/login', ['error' => 'Please login to continue']);
            return;
        }
        
        $params = [Auth::id()];
        $baseSql = "SELECT r.*, c.command_name, COUNT(ri.id) as item_count
             FROM requisitions r
             LEFT JOIN commands c ON r.requesting_command_id = c.id
             LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
             WHERE r.created_by = ?
             GROUP BY r.id
             ORDER BY r.created_at DESC";
             
        // requisitions' command column is requesting_command_id, not
        // paginateTable()'s "command_id" default — see the comment on the
        // stats query above.
        $pagination = paginateTable('requisitions', 'r', ['requisition_number', 'requesting_officer_name'], $baseSql, $params, null, 'requesting_command_id');
        $requisitions = Database::fetchAll($pagination['sql'], $params);
        if ($requisitions === false) $requisitions = [];
        
        $this->view('requisition/my', [
            'requisitions' => $requisitions,
            'pagination' => $pagination
        ]);
    }
    
    public function pending() {
        $userRoles = $_SESSION['roles'] ?? [];
        $isAdminOrSuper = in_array('admin', $userRoles, true) || in_array('Super Admin Officer', $userRoles, true) || Auth::isSuperAdmin();
        
        // Check permission (operational approvers, admin, or super admin)
        if (!Auth::can('requisition.approve') && !$isAdminOrSuper) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view pending requisitions']);
            return;
        }
        
        $params = [];
        $baseSql = "SELECT r.*, u.full_name as requester_name, c.command_name,
                    COUNT(ri.id) as item_count
             FROM requisitions r
             LEFT JOIN users u ON r.created_by = u.id
             LEFT JOIN commands c ON r.requesting_command_id = c.id
             LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
             WHERE r.status = 'Pending'
             GROUP BY r.id
             ORDER BY 
                CASE r.priority_level 
                    WHEN 'Urgent' THEN 1 
                    WHEN 'High' THEN 2 
                    WHEN 'Medium' THEN 3 
                    WHEN 'Low' THEN 4 
                    ELSE 5 
                END, 
                r.created_at ASC";
                
        // requisitions' command column is requesting_command_id, not
        // paginateTable()'s "command_id" default — see the comment on the
        // stats query above.
        $pagination = paginateTable('requisitions', 'r', ['requisition_number', 'requesting_officer_name'], $baseSql, $params, null, 'requesting_command_id');
        $requisitions = Database::fetchAll($pagination['sql'], $params);
        if ($requisitions === false) $requisitions = [];
        
        $this->view('requisition/pending', [
            'requisitions' => $requisitions,
            'pagination' => $pagination
        ]);
    }
    
    /**
     * Export requisitions to CSV
     */
    public function export() {
        // Check permission
        if (!Auth::can('reports.export')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to export data']);
            return;
        }
        
        // Unscoped before — a command-restricted user (e.g. Command Armorer)
        // could export every command's requisitions, not just their own.
        $exportParams = [];
        $exportSql = Database::applyCommandFilter(
            "SELECT r.*, c.command_name,
                    COUNT(ri.id) as item_count
             FROM requisitions r
             LEFT JOIN commands c ON r.requesting_command_id = c.id
             LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
             GROUP BY r.id
             ORDER BY r.created_at DESC",
            'r', $exportParams, 'requesting_command_id'
        );
        $requisitions = Database::fetchAll($exportSql, $exportParams);

        if ($requisitions === false) $requisitions = [];
        
        $filename = 'requisitions_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        Security::fputcsv($output, [
            'Requisition #', 'Date', 'Type', 'Priority', 'Officer Name',
            'Rank', 'NIS Number', 'Phone', 'Command', 'Justification',
            'Expected Return', 'Status', 'Item Count', 'Remarks', 'Created At'
        ]);
        
        // Data
        foreach ($requisitions as $req) {
            Security::fputcsv($output, [
                $req['requisition_number'] ?? '',
                $req['requisition_date'] ?? '',
                $req['requisition_type'] ?? '',
                $req['priority_level'] ?? '',
                $req['requesting_officer_name'] ?? '',
                $req['requesting_rank'] ?? '',
                $req['requesting_nis'] ?? '',
                $req['requesting_phone'] ?? '',
                $req['command_name'] ?? '',
                $req['justification'] ?? '',
                $req['expected_return_date'] ?? '',
                $req['status'] ?? '',
                $req['item_count'] ?? 0,
                $req['remarks'] ?? '',
                $req['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        if (class_exists('AuditLogger')) {
            AuditLogger::logExport('requisitions', 'csv');
        }
        exit;
    }
    
    /**
     * Generate unique requisition number
     */
    private function generateRequisitionNumber() {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT requisition_number FROM requisitions
             WHERE requisition_number LIKE 'REQ-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($last && isset($last['requisition_number'])) {
            $seq = intval(substr($last['requisition_number'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("REQ-%s%s-%04d", $year, $month, $seq);
    }
}