<?php
/**
 * Ammunition Management Controller
 */
class AmmunitionController extends Controller {
    
    /**
     * Constructor - Check permissions
     */
    public function __construct() {
        // Only call parent constructor if it exists
        if (method_exists(get_parent_class($this), '__construct')) {
            parent::__construct();
        }
        
        // You can add permission checks here if needed
    }
    
    /**
     * Display ammunition inventory
     */
    public function index() {
        // Check permission
        if (!Auth::can('ammunition.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view ammunition']);
            return;
        }

        // Server-side pagination (was previously fetching every row and
        // hiding all but 10 of them client-side, which — with 3,000+ rows —
        // made the page render huge on first paint and then visibly collapse
        // a moment later once the page-size JS ran).
        $params = [];
        $baseSql = "SELECT ai.*, at.ammo_type, ac.calibre
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             ORDER BY ai.created_at DESC";

        // Stock-level is a range, not an exact match, so paginateTable()'s
        // generic "column = value" GET filters can't express it — splice a
        // WHERE in ourselves before it adds its own (search/type/etc.) via AND.
        $stockFilter = $_GET['stock'] ?? '';
        if (in_array($stockFilter, ['low', 'adequate', 'overstock'], true)) {
            if ($stockFilter === 'low') {
                $stockCondition = 'ai.balance < 100';
            } elseif ($stockFilter === 'adequate') {
                $stockCondition = 'ai.balance >= 100 AND ai.balance < 500';
            } else {
                $stockCondition = 'ai.balance >= 500';
            }
            $baseSql = preg_replace('/\sORDER BY\s/i', " WHERE {$stockCondition} ORDER BY ", $baseSql, 1);
        }

        $pagination = paginateTable('ammunition_inventory', 'ai', ['ammo_id', 'batch_number', 'storage_location'], $baseSql, $params);
        $ammunition = Database::fetchAll($pagination['sql'], $params);

        if ($ammunition === false) $ammunition = [];

        // Compute real-time document count for each ammunition record
        if (!empty($ammunition)) {
            $ammoIds = array_column($ammunition, 'id');
            $placeholders = implode(',', array_fill(0, count($ammoIds), '?'));
            $docCounts = Database::fetchAll(
                "SELECT asset_id, COUNT(*) as count FROM documents WHERE asset_type = 'ammunition' AND asset_id IN ($placeholders) GROUP BY asset_id",
                $ammoIds
            ) ?: [];
            
            $docMap = [];
            foreach ($docCounts as $dc) {
                $docMap[$dc['asset_id']] = (int)$dc['count'];
            }
            
            foreach ($ammunition as &$item) {
                $item['document_count'] = $docMap[$item['id']] ?? 0;
            }
            unset($item);
        }

        // paginateTable()'s own COUNT query is built from scratch and never
        // sees the stock-level condition spliced in above, so its totalCount
        // would be wrong (e.g. "Page 1 of 60" for a filter matching 0 rows).
        // Recompute the count from the exact query that was actually run.
        if (!empty($stockFilter)) {
            $countSql = preg_replace('/^SELECT .*? FROM/is', 'SELECT COUNT(*) as count FROM', $pagination['sql'], 1);
            $countSql = preg_replace('/\s+ORDER BY\s.*$/is', '', $countSql, 1);
            $countParams = array_slice($params, 0, -2); // drop the LIMIT/OFFSET params
            $realTotal = (int) (Database::fetchOne($countSql, $countParams)['count'] ?? 0);

            $pagination['totalCount'] = $realTotal;
            $pagination['totalPages'] = max(1, (int) ceil($realTotal / $pagination['limit']));
        }

        // Command/Formation filter (Super Admin/admin/HQ Armorer/Armorer
        // only — a Command Armorer is already locked to their own command,
        // so an ad-hoc filter on top of that would be meaningless for them).
        $commandFilterId = (!Auth::isCommandRestricted() && !empty($_GET['command_id'])) ? (int) $_GET['command_id'] : null;

        // Statistics reflect the whole (command-scoped) inventory, not just
        // the current page, so compute them with their own aggregate query.
        $statsParams = [];
        $statsSql = Database::applyCommandFilter(
            "SELECT COUNT(*) as total_types,
                    COALESCE(SUM(balance), 0) as total_rounds,
                    SUM(CASE WHEN balance < 100 THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date > CURDATE()
                              AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) as expiring_soon
             FROM ammunition_inventory ai",
            'ai',
            $statsParams
        );
        $statsSql = Database::applyOptionalFilter($statsSql, 'ai', 'command_id', $commandFilterId, $statsParams);
        $statsRow = Database::fetchOne($statsSql, $statsParams);

        $statistics = [
            'total_types' => (int) ($statsRow['total_types'] ?? 0),
            'total_rounds' => (int) ($statsRow['total_rounds'] ?? 0),
            'expiring_soon' => (int) ($statsRow['expiring_soon'] ?? 0),
            'low_stock' => (int) ($statsRow['low_stock'] ?? 0),
        ];

        // Get filter options
        $ammoTypes = Database::fetchAll("SELECT * FROM ammunition_types ORDER BY ammo_type");
        $calibres = Database::fetchAll("SELECT * FROM ammunition_calibres ORDER BY calibre");

        if ($ammoTypes === false) $ammoTypes = [];
        if ($calibres === false) $calibres = [];

        $commands = [];
        if (!Auth::isCommandRestricted()) {
            $commands = Database::fetchAll("SELECT id, command_name, command_type FROM commands WHERE is_active = 1 ORDER BY command_name") ?: [];
        }

        $this->view('ammunition/index', [
            'ammunition' => $ammunition,
            'statistics' => $statistics,
            'ammoTypes' => $ammoTypes,
            'calibres' => $calibres,
            'commands' => $commands,
            'selectedCommandId' => $commandFilterId,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount'],
        ]);
    }
    
    /**
     * Show create form
     */
    public function create() {
        // Check permission
        if (!Auth::can('ammunition.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create ammunition']);
            return;
        }
        
        $ammoTypes = Database::fetchAll("SELECT * FROM ammunition_types ORDER BY ammo_type") ?: [];
        $calibres = Database::fetchAll("SELECT * FROM ammunition_calibres ORDER BY calibre") ?: [];
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name") ?: [];
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name") ?: [];
        
        $this->view('ammunition/create', [
            'ammoTypes' => $ammoTypes,
            'calibres' => $calibres,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    /**
     * Store new ammunition
     */
    public function store() {
        // Check permission
        if (!Auth::can('ammunition.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create ammunition']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('ammunition/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['ammo_id'])) {
            $errors['ammo_id'] = 'Ammunition ID is required';
        }
        
        if (empty($_POST['batch_number'])) {
            $errors['batch_number'] = 'Batch number is required';
        }
        
        if (empty($_POST['quantity_received'])) {
            $errors['quantity_received'] = 'Quantity received is required';
        } elseif (!is_numeric($_POST['quantity_received']) || $_POST['quantity_received'] <= 0) {
            $errors['quantity_received'] = 'Quantity must be a positive number';
        }
        
        if (empty($_POST['storage_form'])) {
            $errors['storage_form'] = 'Storage form is required';
        }
        
        if (empty($_POST['storage_location'])) {
            $errors['storage_location'] = 'Storage location is required';
        }
        
        if (empty($_POST['condition'])) {
            $errors['condition'] = 'Condition is required';
        }
        
        // Check for duplicate ammo_id
        if (!empty($_POST['ammo_id'])) {
            $existing = Database::fetchOne(
                "SELECT id FROM ammunition_inventory WHERE ammo_id = ?",
                [$_POST['ammo_id']]
            );
            if ($existing) {
                $errors['ammo_id'] = 'Ammunition ID already exists';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('ammunition/create');
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $quantityReceived = (int)$_POST['quantity_received'];
            
            $ammoId = Database::insert('ammunition_inventory', [
                'ammo_id' => $_POST['ammo_id'],
                'ammo_type_id' => !empty($_POST['ammo_type_id']) && $_POST['ammo_type_id'] !== 'other' ? $_POST['ammo_type_id'] : null,
                'ammo_type_other' => ($_POST['ammo_type_id'] ?? '') === 'other' ? ($_POST['ammo_type_other'] ?? null) : null,
                'calibre_id' => !empty($_POST['calibre_id']) && $_POST['calibre_id'] !== 'other' ? $_POST['calibre_id'] : null,
                'calibre_other' => ($_POST['calibre_id'] ?? '') === 'other' ? ($_POST['calibre_other'] ?? null) : null,
                'storage_form' => $_POST['storage_form'],
                'storage_location' => $_POST['storage_location'] === 'Other' ? ($_POST['storage_location_other'] ?? 'Other') : $_POST['storage_location'],
                'storage_location_other' => $_POST['storage_location'] === 'Other' ? ($_POST['storage_location_other'] ?? null) : null,
                'zone_id' => ($_POST['storage_location'] ?? '') === 'Zonal Armoury' ? (!empty($_POST['zone_id']) ? $_POST['zone_id'] : null) : null,
                'command_id' => ($_POST['storage_location'] ?? '') === 'Command Armoury' ? (!empty($_POST['command_id']) ? $_POST['command_id'] : null) : null,
                'quantity_received' => $quantityReceived,
                'quantity_issued' => 0,
                'balance' => $quantityReceived,
                'condition' => $_POST['condition'],
                'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                'batch_number' => $_POST['batch_number'],
                'manufacturer' => $_POST['manufacturer'] ?? null,
                'date_manufactured' => !empty($_POST['date_manufactured']) ? $_POST['date_manufactured'] : null,
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ]);
            
            if (!$ammoId) {
                throw new Exception("Failed to insert ammunition record");
            }
            
            Database::commit();
            
            AuditLogger::logCreate('ammunition_inventory', $ammoId, $_POST);
            
            $this->redirect('ammunition', ['success' => 'Ammunition added successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Ammunition creation error: " . $e->getMessage());
            $this->redirect('ammunition/create', ['error' => 'Failed to add ammunition: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Show ammunition details
     */
    public function show($id) {
        // Check permission
        if (!Auth::can('ammunition.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view ammunition']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter(
            "SELECT ai.*, at.ammo_type, ac.calibre,
                    z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             LEFT JOIN zones z ON ai.zone_id = z.id
             LEFT JOIN commands c ON ai.command_id = c.id
             LEFT JOIN users u ON ai.created_by = u.id
             WHERE ai.id = ?",
            'ai',
            $params
        );
        $ammo = Database::fetchOne($sql, $params);
        
        if (!$ammo) {
            $this->redirect('ammunition', ['error' => 'Ammunition not found']);
            return;
        }
        
        // Get issue history
        $issueHistory = Database::fetchAll(
            "SELECT ail.*, 
                    CONCAT(ail.officer_name, ' (', ail.officer_rank, ')') as officer,
                    req.requisition_number
             FROM ammunition_issue_log ail
             LEFT JOIN requisitions req ON ail.requisition_id = req.id
             WHERE ail.ammo_id = ? 
             ORDER BY ail.issue_date DESC",
            [$id]
        );
        if ($issueHistory === false) $issueHistory = [];
        
        AuditLogger::logView('ammunition_inventory', $id);
        
        $this->view('ammunition/show', [
            'ammo' => $ammo,
            'issueHistory' => $issueHistory
        ]);
    }
    
    /**
     * Show edit form
     */
    public function edit($id) {
        // Check permission
        if (!Auth::can('ammunition.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit ammunition']);
            return;
        }
        
        $params = [$id];
        $ammo = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM ammunition_inventory WHERE id = ?", 'ammunition_inventory', $params), $params);
        
        if (!$ammo) {
            $this->redirect('ammunition', ['error' => 'Ammunition not found']);
            return;
        }
        
        $ammoTypes = Database::fetchAll("SELECT * FROM ammunition_types ORDER BY ammo_type") ?: [];
        $calibres = Database::fetchAll("SELECT * FROM ammunition_calibres ORDER BY calibre") ?: [];
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name") ?: [];
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name") ?: [];
        
        $this->view('ammunition/edit', [
            'ammo' => $ammo,
            'ammoTypes' => $ammoTypes,
            'calibres' => $calibres,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    /**
     * Update ammunition
     */
    public function update($id) {
        // Check permission
        if (!Auth::can('ammunition.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit ammunition']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("ammunition/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $oldData = Database::fetchOne("SELECT * FROM ammunition_inventory WHERE id = ?", [$id]);
        
        if (!$oldData) {
            $this->redirect('ammunition', ['error' => 'Ammunition not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        $ammoId = !empty($_POST['ammo_id']) ? $_POST['ammo_id'] : $oldData['ammo_id'];
        
        if (empty($ammoId)) {
            $errors['ammo_id'] = 'Ammunition ID is required';
        }
        
        if (empty($_POST['batch_number'])) {
            $errors['batch_number'] = 'Batch number is required';
        }
        
        if (empty($_POST['quantity_received'])) {
            $errors['quantity_received'] = 'Quantity received is required';
        } elseif (!is_numeric($_POST['quantity_received']) || $_POST['quantity_received'] <= 0) {
            $errors['quantity_received'] = 'Quantity must be a positive number';
        }
        
        if (empty($_POST['storage_form'])) {
            $errors['storage_form'] = 'Storage form is required';
        }
        
        if (empty($_POST['storage_location'])) {
            $errors['storage_location'] = 'Storage location is required';
        }
        
        if (empty($_POST['condition'])) {
            $errors['condition'] = 'Condition is required';
        }
        
        // Check for duplicate ammo_id (excluding current)
        if (!empty($ammoId) && $ammoId !== $oldData['ammo_id']) {
            $existing = Database::fetchOne(
                "SELECT id FROM ammunition_inventory WHERE ammo_id = ? AND id != ?",
                [$ammoId, $id]
            );
            if ($existing) {
                $errors['ammo_id'] = 'Ammunition ID already exists';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("ammunition/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $quantityReceived = (int)$_POST['quantity_received'];
            $quantityIssued = $oldData['quantity_issued'] ?? 0;
            $newBalance = $quantityReceived - $quantityIssued;
            
            $storageLoc = $_POST['storage_location'] ?? '';
            
            Database::update('ammunition_inventory', [
                'ammo_id' => $ammoId,
                'ammo_type_id' => !empty($_POST['ammo_type_id']) && $_POST['ammo_type_id'] !== 'other' ? $_POST['ammo_type_id'] : null,
                'ammo_type_other' => ($_POST['ammo_type_id'] ?? '') === 'other' ? ($_POST['ammo_type_other'] ?? null) : null,
                'calibre_id' => !empty($_POST['calibre_id']) && $_POST['calibre_id'] !== 'other' ? $_POST['calibre_id'] : null,
                'calibre_other' => ($_POST['calibre_id'] ?? '') === 'other' ? ($_POST['calibre_other'] ?? null) : null,
                'storage_form' => $_POST['storage_form'],
                'storage_location' => $storageLoc === 'Other' ? ($_POST['storage_location_other'] ?? 'Other') : $storageLoc,
                'storage_location_other' => $storageLoc === 'Other' ? ($_POST['storage_location_other'] ?? null) : null,
                'zone_id' => in_array($storageLoc, ['Zonal Armoury', 'Zonal Armony']) ? (!empty($_POST['zone_id']) ? $_POST['zone_id'] : null) : null,
                'command_id' => in_array($storageLoc, ['Command Armoury', 'Command Armony']) ? (!empty($_POST['command_id']) ? $_POST['command_id'] : null) : null,
                'quantity_received' => $quantityReceived,
                'balance' => $newBalance,
                'condition' => $_POST['condition'],
                'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                'batch_number' => $_POST['batch_number'],
                'manufacturer' => $_POST['manufacturer'] ?? null,
                'date_manufactured' => !empty($_POST['date_manufactured']) ? $_POST['date_manufactured'] : null,
                'remarks' => $_POST['remarks'] ?? null
            ], 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logUpdate('ammunition_inventory', $id, $oldData, $_POST);
            
            $this->redirect("ammunition/show/$id", ['success' => 'Ammunition updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Ammunition update error: " . $e->getMessage());
            $this->redirect("ammunition/edit/$id", ['error' => 'Failed to update ammunition: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Delete ammunition
     */
    public function delete($id) {
        // Check permission
        if (!Auth::can('ammunition.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete ammunition']);
            return;
        }
        
        $ammo = Database::fetchOne("SELECT * FROM ammunition_inventory WHERE id = ?", [$id]);
        
        if (!$ammo) {
            $this->redirect('ammunition', ['error' => 'Ammunition not found']);
            return;
        }
        
        // Check if ammunition has been issued
        if (($ammo['quantity_issued'] ?? 0) > 0) {
            $this->redirect('ammunition', ['error' => 'Cannot delete ammunition that has been issued']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::delete('ammunition_inventory', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('ammunition_inventory', $id, $ammo);
            
            $this->redirect('ammunition', ['success' => 'Ammunition deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Ammunition deletion error: " . $e->getMessage());
            $this->redirect('ammunition', ['error' => 'Failed to delete ammunition: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Dashboard view
     */
    public function dashboard() {
        // Check permission
        if (!Auth::can('ammunition.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view ammunition dashboard']);
            return;
        }
        
        $paramsStats = [];
        $sqlStats = Database::applyCommandFilter(
            "SELECT 
                COUNT(*) as total_types,
                SUM(ai.balance) as total_rounds,
                SUM(CASE WHEN ai.expiry_date IS NOT NULL AND ai.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND ai.expiry_date >= CURDATE() THEN 1 ELSE 0 END) as expiring_soon,
                SUM(CASE WHEN ai.balance < 100 THEN 1 ELSE 0 END) as low_stock
             FROM ammunition_inventory ai",
            "ai",
            $paramsStats
        );
        $resStats = Database::fetchOne($sqlStats, $paramsStats);
        
        $stats = [
            'total_types' => $resStats['total_types'] ?? 0,
            'total_rounds' => $resStats['total_rounds'] ?? 0,
            'expiring_soon' => $resStats['expiring_soon'] ?? 0,
            'low_stock' => $resStats['low_stock'] ?? 0
        ];
        
        $paramsByCalibre = [];
        $sqlByCalibre = Database::applyCommandFilter(
            "SELECT COALESCE(ac.calibre, 'Other') as calibre, 
                    COUNT(*) as type_count, 
                    SUM(balance) as total_rounds
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             GROUP BY COALESCE(ac.calibre, 'Other')
             ORDER BY total_rounds DESC",
            "ai",
            $paramsByCalibre
        );
        $byCalibre = Database::fetchAll($sqlByCalibre, $paramsByCalibre);
        if ($byCalibre === false) $byCalibre = [];
        
        $paramsExpiringSoon = [];
        $sqlExpiringSoon = Database::applyCommandFilter(
            "SELECT ai.*, at.ammo_type, ac.calibre,
                    DATEDIFF(expiry_date, CURDATE()) as days_remaining
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             WHERE ai.expiry_date IS NOT NULL
             AND ai.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
             AND ai.expiry_date >= CURDATE()
             ORDER BY ai.expiry_date ASC
             LIMIT 10",
            "ai",
            $paramsExpiringSoon
        );
        $expiringSoon = Database::fetchAll($sqlExpiringSoon, $paramsExpiringSoon);
        if ($expiringSoon === false) $expiringSoon = [];
        
        $this->view('ammunition/dashboard', [
            'stats' => $stats,
            'byCalibre' => $byCalibre,
            'expiringSoon' => $expiringSoon
        ]);
    }
    
    /**
     * Types management
     */
    public function types() {
        // Check permission
        if (!Auth::can('ammunition.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage ammunition types']);
            return;
        }
        
        $types = Database::fetchAll("SELECT * FROM ammunition_types ORDER BY ammo_type");
        if ($types === false) $types = [];
        
        $this->view('ammunition/types', ['types' => $types]);
    }
    
    /**
     * Store ammunition type
     */
    public function storeType() {
        // Check permission
        if (!Auth::can('ammunition.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage ammunition types']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('ammunition/types', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['ammo_type'])) {
            $errors['ammo_type'] = 'Ammunition type is required';
        }
        
        // Check for duplicate
        if (!empty($_POST['ammo_type'])) {
            $existing = Database::fetchOne(
                "SELECT id FROM ammunition_types WHERE ammo_type = ?",
                [$_POST['ammo_type']]
            );
            if ($existing) {
                $errors['ammo_type'] = 'Ammunition type already exists';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect('ammunition/types');
            return;
        }
        
        try {
            Database::insert('ammunition_types', [
                'ammo_type' => $_POST['ammo_type'],
                'description' => $_POST['description'] ?? null
            ]);
            
            $this->redirect('ammunition/types', ['success' => 'Ammunition type added successfully']);
            
        } catch (Exception $e) {
            error_log("Ammunition type creation error: " . $e->getMessage());
            $this->redirect('ammunition/types', ['error' => 'Failed to add ammunition type']);
        }
    }
    
    /**
     * Delete ammunition type
     */
    public function deleteType($id) {
        // Check permission
        if (!Auth::can('ammunition.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete ammunition types']);
            return;
        }
        
        $type = Database::fetchOne("SELECT * FROM ammunition_types WHERE id = ?", [$id]);
        
        if (!$type) {
            $this->redirect('ammunition/types', ['error' => 'Ammunition type not found']);
            return;
        }
        
        // Check if type is in use
        $inUse = Database::fetchOne(
            "SELECT COUNT(*) as count FROM ammunition_inventory WHERE ammo_type_id = ?",
            [$id]
        )['count'] ?? 0;
        
        if ($inUse > 0) {
            $this->redirect('ammunition/types', ['error' => 'Cannot delete ammunition type that is in use']);
            return;
        }
        
        try {
            Database::delete('ammunition_types', 'id = ?', [$id]);
            $this->redirect('ammunition/types', ['success' => 'Ammunition type deleted successfully']);
        } catch (Exception $e) {
            error_log("Ammunition type deletion error: " . $e->getMessage());
            $this->redirect('ammunition/types', ['error' => 'Failed to delete ammunition type']);
        }
    }
    
    /**
     * Calibres management
     */
    public function calibres() {
        // Check permission
        if (!Auth::can('ammunition.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage calibres']);
            return;
        }
        
        $calibres = Database::fetchAll("SELECT * FROM ammunition_calibres ORDER BY calibre");
        if ($calibres === false) $calibres = [];
        
        $this->view('ammunition/calibres', ['calibres' => $calibres]);
    }
    
    /**
     * Store calibre
     */
    public function storeCalibre() {
        // Check permission
        if (!Auth::can('ammunition.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage calibres']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('ammunition/calibres', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['calibre'])) {
            $errors['calibre'] = 'Calibre is required';
        }
        
        // Check for duplicate
        if (!empty($_POST['calibre'])) {
            $existing = Database::fetchOne(
                "SELECT id FROM ammunition_calibres WHERE calibre = ?",
                [$_POST['calibre']]
            );
            if ($existing) {
                $errors['calibre'] = 'Calibre already exists';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect('ammunition/calibres');
            return;
        }
        
        try {
            Database::insert('ammunition_calibres', [
                'calibre' => $_POST['calibre'],
                'description' => $_POST['description'] ?? null,
                'rounds_per_unit' => !empty($_POST['rounds_per_unit']) ? (int)$_POST['rounds_per_unit'] : 30
            ]);
            
            $this->redirect('ammunition/calibres', ['success' => 'Calibre added successfully']);
            
        } catch (Exception $e) {
            error_log("Calibre creation error: " . $e->getMessage());
            $this->redirect('ammunition/calibres', ['error' => 'Failed to add calibre']);
        }
    }
    
    /**
     * Delete calibre
     */
    public function deleteCalibre($id) {
        // Check permission
        if (!Auth::can('ammunition.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete calibres']);
            return;
        }
        
        $calibre = Database::fetchOne("SELECT * FROM ammunition_calibres WHERE id = ?", [$id]);
        
        if (!$calibre) {
            $this->redirect('ammunition/calibres', ['error' => 'Calibre not found']);
            return;
        }
        
        // Check if calibre is in use
        $inUse = Database::fetchOne(
            "SELECT COUNT(*) as count FROM ammunition_inventory WHERE calibre_id = ?",
            [$id]
        )['count'] ?? 0;
        
        if ($inUse > 0) {
            $this->redirect('ammunition/calibres', ['error' => 'Cannot delete calibre that is in use']);
            return;
        }
        
        try {
            Database::delete('ammunition_calibres', 'id = ?', [$id]);
            $this->redirect('ammunition/calibres', ['success' => 'Calibre deleted successfully']);
        } catch (Exception $e) {
            error_log("Calibre deletion error: " . $e->getMessage());
            $this->redirect('ammunition/calibres', ['error' => 'Failed to delete calibre']);
        }
    }
    
    /**
     * Export ammunition data
     */
    public function export() {
        // Check permission
        if (!Auth::can('reports.export')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to export data']);
            return;
        }
        
        $ammunition = Database::fetchAll(
            "SELECT ai.*, at.ammo_type, ac.calibre
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             ORDER BY ai.created_at DESC"
        );
        
        if ($ammunition === false) $ammunition = [];
        
        $filename = 'ammunition_inventory_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        Security::fputcsv($output, [
            'Ammo ID', 'Type', 'Calibre', 'Batch Number', 'Storage Form',
            'Storage Location', 'Quantity Received', 'Quantity Issued',
            'Balance', 'Condition', 'Expiry Date', 'Manufacturer',
            'Date Manufactured', 'Remarks', 'Created At'
        ]);
        
        // Data
        foreach ($ammunition as $a) {
            Security::fputcsv($output, [
                $a['ammo_id'] ?? '',
                $a['ammo_type'] ?? $a['ammo_type_other'] ?? '',
                $a['calibre'] ?? $a['calibre_other'] ?? '',
                $a['batch_number'] ?? '',
                $a['storage_form'] ?? '',
                $a['storage_location'] ?? '',
                $a['quantity_received'] ?? 0,
                $a['quantity_issued'] ?? 0,
                $a['balance'] ?? 0,
                $a['condition'] ?? '',
                $a['expiry_date'] ?? '',
                $a['manufacturer'] ?? '',
                $a['date_manufactured'] ?? '',
                $a['remarks'] ?? '',
                $a['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('ammunition', 'csv');
        exit;
    }
}