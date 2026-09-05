<?php
/**
 * Weapons Management Controller
 */
class WeaponsController extends Controller {
    
    public function index() {
        // Check permission
        if (!Auth::can('weapons.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view weapons']);
            return;
        }
        
        $params = [];
        $baseSql = "SELECT wi.*, wt.type_name as weapon_type_name, wc.calibre_name
             FROM weapons_inventory wi
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             LEFT JOIN weapon_calibres wc ON wi.calibre_id = wc.id
             ORDER BY wi.created_at DESC";
             
        $pagination = paginateTable('weapons_inventory', 'wi', ['serial_no', 'make_model', 'weapon_id'], $baseSql, $params);
        $weapons = Database::fetchAll($pagination['sql'], $params);
        if ($weapons === false) $weapons = [];
        
        // Compute real-time document count for each weapon
        if (!empty($weapons)) {
            $weaponIds = array_column($weapons, 'id');
            $placeholders = implode(',', array_fill(0, count($weaponIds), '?'));
            $docCounts = Database::fetchAll(
                "SELECT asset_id, COUNT(*) as count FROM documents WHERE asset_type = 'weapon' AND asset_id IN ($placeholders) GROUP BY asset_id",
                $weaponIds
            ) ?: [];
            
            $docMap = [];
            foreach ($docCounts as $dc) {
                $docMap[$dc['asset_id']] = (int)$dc['count'];
            }
            
            foreach ($weapons as &$weapon) {
                $weapon['document_count'] = $docMap[$weapon['id']] ?? 0;
            }
            unset($weapon);
        }
        
        // Command/Formation filter (Super Admin/admin/HQ Armorer/Armorer
        // only — a Command Armorer is already locked to their own command
        // via applyCommandFilter() below, so an ad-hoc filter on top of
        // that would be meaningless for them). Threaded into the same
        // three stats queries as the command restriction itself, so the
        // summary cards match whatever command/formation is selected —
        // paginateTable() already applies it to the table/count above
        // via its generic "any real column in $_GET" filter.
        $commandFilterId = (!Auth::isCommandRestricted() && !empty($_GET['command_id'])) ? (int) $_GET['command_id'] : null;

        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total FROM weapons_inventory wi", 'wi', $statsParams);
        $statsSql = Database::applyOptionalFilter($statsSql, 'wi', 'command_id', $commandFilterId, $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);

        $locationParams = [];
        $locationSql = Database::applyCommandFilter("SELECT current_location, COUNT(*) as count FROM weapons_inventory wi GROUP BY current_location", 'wi', $locationParams);
        $locationSql = Database::applyOptionalFilter($locationSql, 'wi', 'command_id', $commandFilterId, $locationParams);
        $locationResults = Database::fetchAll($locationSql, $locationParams) ?: [];
        $issuedCount = 0;
        foreach ($locationResults as $r) {
            if (($r['current_location'] ?? '') === 'Issued') {
                $issuedCount = (int)$r['count'];
            }
        }

        $condParams = [];
        $condSql = Database::applyCommandFilter("SELECT `condition`, COUNT(*) as count FROM weapons_inventory wi GROUP BY `condition`", 'wi', $condParams);
        $condSql = Database::applyOptionalFilter($condSql, 'wi', 'command_id', $commandFilterId, $condParams);
        $condResults = Database::fetchAll($condSql, $condParams) ?: [];
        $serviceableCount = 0;
        $unserviceableCount = 0;
        foreach ($condResults as $r) {
            if (($r['condition'] ?? '') === 'Serviceable') {
                $serviceableCount = (int)$r['count'];
            } elseif (($r['condition'] ?? '') === 'Unserviceable') {
                $unserviceableCount = (int)$r['count'];
            }
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'issued' => $issuedCount,
            'serviceable' => $serviceableCount,
            'unserviceable' => $unserviceableCount
        ];
        
        // Get filter options
        $weaponTypes = Database::fetchAll("SELECT id, type_name FROM weapon_types ORDER BY type_name");
        if ($weaponTypes === false) $weaponTypes = [];
        
        $calibres = Database::fetchAll("SELECT id, calibre_name FROM weapon_calibres ORDER BY calibre_name");
        if ($calibres === false) $calibres = [];

        // Command/Formation filter options — only meaningful (and only
        // shown in the view) for a viewer who isn't already locked to one
        // command. "Formation" isn't a separate table: commands.command_type
        // already has a 'Formation' value alongside 'State Command' etc., so
        // one dropdown over the whole commands table covers both.
        $commands = [];
        if (!Auth::isCommandRestricted()) {
            $commands = Database::fetchAll("SELECT id, command_name, command_type FROM commands WHERE is_active = 1 ORDER BY command_name") ?: [];
        }

        $this->view('weapons/index', [
            'weapons' => $weapons,
            'statistics' => $statistics,
            'weaponTypes' => $weaponTypes,
            'calibres' => $calibres,
            'commands' => $commands,
            'selectedCommandId' => $commandFilterId,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function create() {
        // Check permission
        if (!Auth::can('weapons.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create weapons']);
            return;
        }
        
        $weaponTypes = Database::fetchAll("SELECT * FROM weapon_types ORDER BY type_name") ?: [];
        $calibres = Database::fetchAll("SELECT * FROM weapon_calibres ORDER BY calibre_name") ?: [];
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name") ?: [];
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name") ?: [];
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name") ?: [];
        
        $this->view('weapons/create', [
            'weaponTypes' => $weaponTypes,
            'calibres' => $calibres,
            'states' => $states,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    public function store() {
        // Check permission
        if (!Auth::can('weapons.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create weapons']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('weapons/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['weapon_id'])) {
            $errors['weapon_id'] = 'Weapon ID is required';
        }
        
        if (empty($_POST['make_model'])) {
            $errors['make_model'] = 'Make/Model is required';
        } elseif (strlen($_POST['make_model']) > 255) {
            $errors['make_model'] = 'Make/Model must not exceed 255 characters';
        }
        
        if (empty($_POST['serial_no'])) {
            $errors['serial_no'] = 'Serial number is required';
        }
        
        if (empty($_POST['source'])) {
            $errors['source'] = 'Source is required';
        }
        
        if (empty($_POST['condition'])) {
            $errors['condition'] = 'Condition is required';
        }
        
        if (empty($_POST['current_location'])) {
            $errors['current_location'] = 'Current location is required';
        }

        if (!empty($_POST['custodian_nis']) && !isDigitsOnly($_POST['custodian_nis'])) {
            $errors['custodian_nis'] = 'NIS number must contain numbers only';
        }

        // Check for duplicate weapon_id
        if (!empty($_POST['weapon_id'])) {
            $existing = Database::fetchOne(
                "SELECT id FROM weapons_inventory WHERE weapon_id = ?",
                [$_POST['weapon_id']]
            );
            if ($existing) {
                $errors['weapon_id'] = 'Weapon ID already exists';
            }
        }
        
        // Check for duplicate serial_no
        if (!empty($_POST['serial_no'])) {
            $existing = Database::fetchOne(
                "SELECT id FROM weapons_inventory WHERE serial_no = ?",
                [$_POST['serial_no']]
            );
            if ($existing) {
                $errors['serial_no'] = 'Serial number already exists';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('weapons/create');
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $weaponId = Database::insert('weapons_inventory', [
                'weapon_id' => $_POST['weapon_id'],
                'weapon_type_id' => !empty($_POST['weapon_type_id']) && $_POST['weapon_type_id'] !== 'other' ? $_POST['weapon_type_id'] : null,
                'weapon_type_other' => ($_POST['weapon_type_id'] ?? '') === 'other' ? ($_POST['weapon_type_other'] ?? null) : null,
                'make_model' => $_POST['make_model'],
                'serial_no' => $_POST['serial_no'],
                'calibre_id' => !empty($_POST['calibre_id']) && $_POST['calibre_id'] !== 'other' ? $_POST['calibre_id'] : null,
                'calibre_other' => ($_POST['calibre_id'] ?? '') === 'other' ? ($_POST['calibre_other'] ?? null) : null,
                'source' => $_POST['source'] === 'Other' ? ($_POST['source_other'] ?? 'Other') : $_POST['source'],
                'condition' => $_POST['condition'],
                'current_location' => $_POST['current_location'] === 'Other' ? ($_POST['current_location_other'] ?? 'Other') : $_POST['current_location'],
                'current_location_other' => $_POST['current_location'] === 'Other' ? ($_POST['current_location_other'] ?? null) : null,
                'custodian' => ($_POST['current_location'] ?? '') === 'Issued' ? ($_POST['custodian'] ?? null) : null,
                'custodian_rank' => ($_POST['current_location'] ?? '') === 'Issued' ? ($_POST['custodian_rank'] ?? null) : null,
                'custodian_nis' => ($_POST['current_location'] ?? '') === 'Issued' ? ($_POST['custodian_nis'] ?? null) : null,
                'zone_id' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['zone_id']) ? $_POST['zone_id'] : null) : null,
                'command_id' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['command_id']) ? $_POST['command_id'] : null) : null,
                'state_id' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['state_id']) ? $_POST['state_id'] : null) : null,
                'lga_id' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['lga_id']) ? $_POST['lga_id'] : null) : null,
                'armoury_name' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['armoury_name']) ? $_POST['armoury_name'] : null) : null,
                'date_acquired' => !empty($_POST['date_acquired']) ? $_POST['date_acquired'] : null,
                'last_inspection_date' => !empty($_POST['last_inspection_date']) ? $_POST['last_inspection_date'] : null,
                'next_inspection_date' => !empty($_POST['next_inspection_date']) ? $_POST['next_inspection_date'] : null,
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ]);
            
            if (!$weaponId) {
                throw new Exception("Failed to insert weapon record");
            }
            
            Database::commit();
            
            AuditLogger::logCreate('weapons_inventory', $weaponId, $_POST);
            
            $this->redirect('weapons', ['success' => 'Weapon added successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Weapon creation error: " . $e->getMessage());
            $this->redirect('weapons/create', ['error' => 'Failed to add weapon: ' . $e->getMessage()]);
        }
    }
    
    public function show($id) {
        // Check permission
        if (!Auth::can('weapons.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view weapons']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter(
            "SELECT wi.*, wt.type_name as weapon_type_name, wc.calibre_name,
                    c.command_name,
                    COALESCE(z.zone_name, z_cmd.zone_name) as zone_name,
                    COALESCE(s.state_name, s_cmd.state_name) as state_name,
                    COALESCE(l.lga_name, l_cmd.lga_name) as lga_name,
                    u.full_name as created_by_name
             FROM weapons_inventory wi
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             LEFT JOIN weapon_calibres wc ON wi.calibre_id = wc.id
             LEFT JOIN commands c ON wi.command_id = c.id
             LEFT JOIN states s ON wi.state_id = s.id
             LEFT JOIN lgas l ON wi.lga_id = l.id
             LEFT JOIN zones z ON wi.zone_id = z.id
             LEFT JOIN states s_cmd ON c.state_id = s_cmd.id
             LEFT JOIN lgas l_cmd ON c.lga_id = l_cmd.id
             LEFT JOIN zones z_cmd ON c.zone_id = z_cmd.id
             LEFT JOIN users u ON wi.created_by = u.id
             WHERE wi.id = ?",
            'wi',
            $params
        );
        $weapon = Database::fetchOne($sql, $params);
        
        if (!$weapon) {
            $this->redirect('weapons', ['error' => 'Weapon not found']);
            return;
        }
        
        // Get issue history
        $issueHistory = Database::fetchAll(
            "SELECT wil.*, 
                    CONCAT(wil.officer_name, ' (', wil.officer_rank, ')') as officer,
                    req.requisition_number
             FROM weapon_issue_log wil
             LEFT JOIN requisitions req ON wil.requisition_id = req.id
             WHERE wil.weapon_id = ? 
             ORDER BY wil.issue_date DESC",
            [$id]
        );
        if ($issueHistory === false) $issueHistory = [];
        
        AuditLogger::logView('weapons_inventory', $id);
        
        $this->view('weapons/show', [
            'weapon' => $weapon,
            'issueHistory' => $issueHistory
        ]);
    }
    
    public function edit($id) {
        // Check permission
        if (!Auth::can('weapons.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit weapons']);
            return;
        }
        
        $params = [$id];
        $weapon = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM weapons_inventory WHERE id = ?", 'weapons_inventory', $params), $params);
        
        if (!$weapon) {
            $this->redirect('weapons', ['error' => 'Weapon not found']);
            return;
        }
        
        $weaponTypes = Database::fetchAll("SELECT * FROM weapon_types ORDER BY type_name") ?: [];
        $calibres = Database::fetchAll("SELECT * FROM weapon_calibres ORDER BY calibre_name") ?: [];
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name") ?: [];
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name") ?: [];
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name") ?: [];
        $lgas = !empty($weapon['state_id']) ? (Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$weapon['state_id']]) ?: []) : [];
        
        $this->view('weapons/edit', [
            'weapon' => $weapon,
            'weaponTypes' => $weaponTypes,
            'calibres' => $calibres,
            'states' => $states,
            'lgas' => $lgas,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    public function update($id) {
        if (Auth::isCommandRestricted()) { $_POST['command_id'] = Auth::commandId(); }
        // Check permission
        if (!Auth::can('weapons.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit weapons']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("weapons/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $params = [$id];
        $oldData = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM weapons_inventory WHERE id = ?", 'weapons_inventory', $params), $params);
        
        if (!$oldData) {
            $this->redirect('weapons', ['error' => 'Weapon not found']);
            return;
        }
        
        if (empty($_POST['weapon_id'])) {
            $_POST['weapon_id'] = $oldData['weapon_id'];
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['weapon_id'])) {
            $errors['weapon_id'] = 'Weapon ID is required';
        }
        
        if (empty($_POST['make_model'])) {
            $errors['make_model'] = 'Make/Model is required';
        } elseif (strlen($_POST['make_model']) > 255) {
            $errors['make_model'] = 'Make/Model must not exceed 255 characters';
        }
        
        if (empty($_POST['serial_no'])) {
            $errors['serial_no'] = 'Serial number is required';
        }
        
        if (empty($_POST['source'])) {
            $errors['source'] = 'Source is required';
        }
        
        if (empty($_POST['condition'])) {
            $errors['condition'] = 'Condition is required';
        }
        
        if (empty($_POST['current_location'])) {
            $errors['current_location'] = 'Current location is required';
        }

        if (!empty($_POST['custodian_nis']) && !isDigitsOnly($_POST['custodian_nis'])) {
            $errors['custodian_nis'] = 'NIS number must contain numbers only';
        }

        // Check for duplicate weapon_id (excluding current)
        if (!empty($_POST['weapon_id']) && $_POST['weapon_id'] !== $oldData['weapon_id']) {
            $existing = Database::fetchOne(
                "SELECT id FROM weapons_inventory WHERE weapon_id = ? AND id != ?",
                [$_POST['weapon_id'], $id]
            );
            if ($existing) {
                $errors['weapon_id'] = 'Weapon ID already exists';
            }
        }
        
        // Check for duplicate serial_no (excluding current)
        if (!empty($_POST['serial_no']) && $_POST['serial_no'] !== $oldData['serial_no']) {
            $existing = Database::fetchOne(
                "SELECT id FROM weapons_inventory WHERE serial_no = ? AND id != ?",
                [$_POST['serial_no'], $id]
            );
            if ($existing) {
                $errors['serial_no'] = 'Serial number already exists';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("weapons/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('weapons_inventory', [
                'weapon_id' => $_POST['weapon_id'],
                'weapon_type_id' => !empty($_POST['weapon_type_id']) && $_POST['weapon_type_id'] !== 'other' ? $_POST['weapon_type_id'] : null,
                'weapon_type_other' => ($_POST['weapon_type_id'] ?? '') === 'other' ? ($_POST['weapon_type_other'] ?? null) : null,
                'make_model' => $_POST['make_model'],
                'serial_no' => $_POST['serial_no'],
                'calibre_id' => !empty($_POST['calibre_id']) && $_POST['calibre_id'] !== 'other' ? $_POST['calibre_id'] : null,
                'calibre_other' => ($_POST['calibre_id'] ?? '') === 'other' ? ($_POST['calibre_other'] ?? null) : null,
                'source' => $_POST['source'] === 'Other' ? ($_POST['source_other'] ?? 'Other') : $_POST['source'],
                'condition' => $_POST['condition'],
                'current_location' => $_POST['current_location'] === 'Other' ? ($_POST['current_location_other'] ?? 'Other') : $_POST['current_location'],
                'current_location_other' => $_POST['current_location'] === 'Other' ? ($_POST['current_location_other'] ?? null) : null,
                'custodian' => ($_POST['current_location'] ?? '') === 'Issued' ? ($_POST['custodian'] ?? null) : null,
                'custodian_rank' => ($_POST['current_location'] ?? '') === 'Issued' ? ($_POST['custodian_rank'] ?? null) : null,
                'custodian_nis' => ($_POST['current_location'] ?? '') === 'Issued' ? ($_POST['custodian_nis'] ?? null) : null,
                'zone_id' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['zone_id']) ? $_POST['zone_id'] : null) : null,
                'command_id' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['command_id']) ? $_POST['command_id'] : null) : null,
                'state_id' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['state_id']) ? $_POST['state_id'] : null) : null,
                'lga_id' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['lga_id']) ? $_POST['lga_id'] : null) : null,
                'armoury_name' => ($_POST['current_location'] ?? '') === 'Armoury' ? (!empty($_POST['armoury_name']) ? $_POST['armoury_name'] : null) : null,
                'date_acquired' => !empty($_POST['date_acquired']) ? $_POST['date_acquired'] : null,
                'last_inspection_date' => !empty($_POST['last_inspection_date']) ? $_POST['last_inspection_date'] : null,
                'next_inspection_date' => !empty($_POST['next_inspection_date']) ? $_POST['next_inspection_date'] : null,
                'remarks' => $_POST['remarks'] ?? null
            ], 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logUpdate('weapons_inventory', $id, $oldData, $_POST);
            
            $this->redirect("weapons/show/$id", ['success' => 'Weapon updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Weapon update error: " . $e->getMessage());
            $this->redirect("weapons/edit/$id", ['error' => 'Failed to update weapon: ' . $e->getMessage()]);
        }
    }
    
    public function delete($id) {
        // Check permission
        if (!Auth::can('weapons.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete weapons']);
            return;
        }
        
        $params = [$id];
        $weapon = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM weapons_inventory WHERE id = ?", 'weapons_inventory', $params), $params);
        
        if (!$weapon) {
            $this->redirect('weapons', ['error' => 'Weapon not found']);
            return;
        }
        
        // Check if weapon has issue history
        $issueCount = Database::fetchOne(
            "SELECT COUNT(*) as count FROM weapon_issue_log WHERE weapon_id = ?",
            [$id]
        )['count'] ?? 0;
        
        if ($issueCount > 0) {
            $this->redirect('weapons', ['error' => 'Cannot delete weapon with issue history. Consider marking as decommissioned instead.']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::delete('weapons_inventory', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('weapons_inventory', $id, $weapon);
            
            $this->redirect('weapons', ['success' => 'Weapon deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Weapon deletion error: " . $e->getMessage());
            $this->redirect('weapons', ['error' => 'Failed to delete weapon: ' . $e->getMessage()]);
        }
    }
    
    public function dashboard() {
        // Check permission
        if (!Auth::can('weapons.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view weapons dashboard']);
            return;
        }
        
        $paramsStats = [];
        $sqlStats = Database::applyCommandFilter(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN wi.current_location = 'Issued' THEN 1 ELSE 0 END) as issued,
                SUM(CASE WHEN wi.condition = 'Serviceable' THEN 1 ELSE 0 END) as serviceable,
                SUM(CASE WHEN wi.condition = 'Unserviceable' THEN 1 ELSE 0 END) as unserviceable,
                SUM(CASE WHEN wi.current_location = 'In Repair' THEN 1 ELSE 0 END) as in_repair
             FROM weapons_inventory wi",
            "wi",
            $paramsStats
        );
        $resStats = Database::fetchOne($sqlStats, $paramsStats);
        
        $stats = [
            'total' => $resStats['total'] ?? 0,
            'issued' => $resStats['issued'] ?? 0,
            'serviceable' => $resStats['serviceable'] ?? 0,
            'unserviceable' => $resStats['unserviceable'] ?? 0,
            'in_repair' => $resStats['in_repair'] ?? 0
        ];
        
        $paramsByType = [];
        $sqlByType = Database::applyCommandFilter(
            "SELECT COALESCE(wt.type_name, wi.weapon_type_other, 'Other') as type, COUNT(*) as count 
             FROM weapons_inventory wi
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             GROUP BY COALESCE(wt.type_name, wi.weapon_type_other, 'Other')
             ORDER BY count DESC",
            "wi",
            $paramsByType
        );
        $byTypeData = Database::fetchAll($sqlByType, $paramsByType);
        
        $byType = [];
        if ($byTypeData !== false) {
            foreach ($byTypeData as $row) {
                $byType[$row['type']] = (int)$row['count'];
            }
        }
        
        $paramsByCalibre = [];
        $sqlByCalibre = Database::applyCommandFilter(
            "SELECT COALESCE(wc.calibre_name, wi.calibre_other, 'Other') as calibre, COUNT(*) as count 
             FROM weapons_inventory wi
             LEFT JOIN weapon_calibres wc ON wi.calibre_id = wc.id
             GROUP BY COALESCE(wc.calibre_name, wi.calibre_other, 'Other')
             ORDER BY count DESC",
            "wi",
            $paramsByCalibre
        );
        $byCalibreData = Database::fetchAll($sqlByCalibre, $paramsByCalibre);
        
        $byCalibre = [];
        if ($byCalibreData !== false) {
            foreach ($byCalibreData as $row) {
                $byCalibre[$row['calibre']] = (int)$row['count'];
            }
        }
        
        $stats['by_type'] = $byType;
        $stats['by_calibre'] = $byCalibre;
        
        $paramsRecent = [];
        $sqlRecent = Database::applyCommandFilter(
            "SELECT wil.*, wi.make_model, wi.weapon_id
             FROM weapon_issue_log wil FORCE INDEX (idx_wil_date)
             JOIN weapons_inventory wi ON wil.weapon_id = wi.id
             ORDER BY wil.issue_date DESC
             LIMIT 10",
            "wi",
            $paramsRecent
        );
        $recentIssues = Database::fetchAll($sqlRecent, $paramsRecent);
        if ($recentIssues === false) $recentIssues = [];
        
        $this->view('weapons/dashboard', [
            'stats' => $stats,
            'byType' => $byType,
            'recentIssues' => $recentIssues
        ]);
    }
    
    public function types() {
        // Check permission
        if (!Auth::can('weapons.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage weapon types']);
            return;
        }
        
        $types = Database::fetchAll("SELECT * FROM weapon_types ORDER BY type_name");
        if ($types === false) $types = [];
        
        $this->view('weapons/types', ['types' => $types]);
    }
    
    public function storeType() {
        // Check permission
        if (!Auth::can('weapons.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage weapon types']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('weapons/types', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['type_name'])) {
            $errors['type_name'] = 'Type name is required';
        }
        
        // Check for duplicate
        if (!empty($_POST['type_name'])) {
            $existing = Database::fetchOne(
                "SELECT id FROM weapon_types WHERE type_name = ?",
                [$_POST['type_name']]
            );
            if ($existing) {
                $errors['type_name'] = 'Weapon type already exists';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect('weapons/types');
            return;
        }
        
        try {
            Database::insert('weapon_types', [
                'type_name' => $_POST['type_name'],
                'description' => $_POST['description'] ?? null,
                'default_calibre' => $_POST['default_calibre'] ?? null
            ]);
            
            $this->redirect('weapons/types', ['success' => 'Weapon type added successfully']);
            
        } catch (Exception $e) {
            error_log("Weapon type creation error: " . $e->getMessage());
            $this->redirect('weapons/types', ['error' => 'Failed to add weapon type']);
        }
    }
    
    public function deleteType($id) {
        // Check permission
        if (!Auth::can('weapons.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete weapon types']);
            return;
        }
        
        $type = Database::fetchOne("SELECT * FROM weapon_types WHERE id = ?", [$id]);
        
        if (!$type) {
            $this->redirect('weapons/types', ['error' => 'Weapon type not found']);
            return;
        }
        
        // Check if type is in use
        $inUse = Database::fetchOne(
            "SELECT COUNT(*) as count FROM weapons_inventory WHERE weapon_type_id = ?",
            [$id]
        )['count'] ?? 0;
        
        if ($inUse > 0) {
            $this->redirect('weapons/types', ['error' => 'Cannot delete weapon type that is in use']);
            return;
        }
        
        try {
            Database::delete('weapon_types', 'id = ?', [$id]);
            $this->redirect('weapons/types', ['success' => 'Weapon type deleted successfully']);
        } catch (Exception $e) {
            error_log("Weapon type deletion error: " . $e->getMessage());
            $this->redirect('weapons/types', ['error' => 'Failed to delete weapon type']);
        }
    }
    
    public function calibres() {
        // Check permission
        if (!Auth::can('weapons.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage calibres']);
            return;
        }
        
        $calibres = Database::fetchAll("SELECT * FROM weapon_calibres ORDER BY calibre_name");
        if ($calibres === false) $calibres = [];
        
        $this->view('weapons/calibres', ['calibres' => $calibres]);
    }
    
    public function storeCalibre() {
        // Check permission
        if (!Auth::can('weapons.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to manage calibres']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('weapons/calibres', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['calibre_name'])) {
            $errors['calibre_name'] = 'Calibre name is required';
        }
        
        // Check for duplicate
        if (!empty($_POST['calibre_name'])) {
            $existing = Database::fetchOne(
                "SELECT id FROM weapon_calibres WHERE calibre_name = ?",
                [$_POST['calibre_name']]
            );
            if ($existing) {
                $errors['calibre_name'] = 'Calibre already exists';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect('weapons/calibres');
            return;
        }
        
        try {
            Database::insert('weapon_calibres', [
                'calibre_name' => $_POST['calibre_name'],
                'description' => $_POST['description'] ?? null
            ]);
            
            $this->redirect('weapons/calibres', ['success' => 'Calibre added successfully']);
            
        } catch (Exception $e) {
            error_log("Calibre creation error: " . $e->getMessage());
            $this->redirect('weapons/calibres', ['error' => 'Failed to add calibre']);
        }
    }
    
    public function deleteCalibre($id) {
        // Check permission
        if (!Auth::can('weapons.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete calibres']);
            return;
        }
        
        $calibre = Database::fetchOne("SELECT * FROM weapon_calibres WHERE id = ?", [$id]);
        
        if (!$calibre) {
            $this->redirect('weapons/calibres', ['error' => 'Calibre not found']);
            return;
        }
        
        // Check if calibre is in use
        $inUse = Database::fetchOne(
            "SELECT COUNT(*) as count FROM weapons_inventory WHERE calibre_id = ?",
            [$id]
        )['count'] ?? 0;
        
        if ($inUse > 0) {
            $this->redirect('weapons/calibres', ['error' => 'Cannot delete calibre that is in use']);
            return;
        }
        
        try {
            Database::delete('weapon_calibres', 'id = ?', [$id]);
            $this->redirect('weapons/calibres', ['success' => 'Calibre deleted successfully']);
        } catch (Exception $e) {
            error_log("Calibre deletion error: " . $e->getMessage());
            $this->redirect('weapons/calibres', ['error' => 'Failed to delete calibre']);
        }
    }
    
    public function issueLog() {
        // Check permission
        if (!Auth::can('weapons.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view issue logs']);
            return;
        }
        
        $issues = Database::fetchAll(
            "SELECT wil.*, wi.weapon_id, wi.make_model, req.requisition_number
             FROM weapon_issue_log wil
             JOIN weapons_inventory wi ON wil.weapon_id = wi.id
             LEFT JOIN requisitions req ON wil.requisition_id = req.id
             ORDER BY wil.issue_date DESC
             LIMIT 100"
        );
        
        if ($issues === false) $issues = [];
        
        $this->view('weapons/issue_log', ['issues' => $issues]);
    }
    
    public function createIssue() {
        // Check permission
        if (!Auth::can('weapons.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to issue weapons']);
            return;
        }
        
        // Get available weapons
        $weapons = Database::fetchAll(
            "SELECT wi.*, wt.type_name 
             FROM weapons_inventory wi
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             WHERE wi.current_location != 'Issued' AND wi.current_location != 'Lost'
             ORDER BY wi.weapon_id"
        );
        
        if ($weapons === false) $weapons = [];
        
        $this->view('weapons/issue_create', ['weapons' => $weapons]);
    }
    
    public function storeIssue() {
        // Check permission
        if (!Auth::can('weapons.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to issue weapons']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('weapons/issue', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['weapon_id'])) {
            $errors['weapon_id'] = 'Weapon is required';
        }
        
        if (empty($_POST['officer_name'])) {
            $errors['officer_name'] = 'Officer name is required';
        }
        
        if (empty($_POST['officer_rank'])) {
            $errors['officer_rank'] = 'Officer rank is required';
        }
        
        if (empty($_POST['unit'])) {
            $errors['unit'] = 'Unit is required';
        }
        
        if (empty($_POST['purpose'])) {
            $errors['purpose'] = 'Purpose is required';
        }

        if (!empty($_POST['officer_nis']) && !isDigitsOnly($_POST['officer_nis'])) {
            $errors['officer_nis'] = 'NIS number must contain numbers only';
        }

        if (empty($_POST['approved_by'])) {
            $errors['approved_by'] = 'Approving officer is required';
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('weapons/issue');
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $issueId = Database::insert('weapon_issue_log', [
                'weapon_id' => $_POST['weapon_id'],
                'requisition_id' => $_POST['requisition_id'] ?? null,
                'issue_date' => $_POST['issue_date'] ?? date('Y-m-d'),
                'officer_name' => $_POST['officer_name'],
                'officer_rank' => $_POST['officer_rank'],
                'officer_nis' => $_POST['officer_nis'] ?? null,
                'unit' => $_POST['unit'],
                'purpose' => $_POST['purpose'],
                'approved_by' => $_POST['approved_by'],
                'issued_by' => Auth::id(),
                'expected_return_date' => $_POST['expected_return_date'] ?? null,
                'status' => 'Issued',
                'remarks' => $_POST['remarks'] ?? null
            ]);
            
            if (!$issueId) {
                throw new Exception("Failed to create issue record");
            }
            
            // Update weapon location
            Database::update('weapons_inventory', [
                'current_location' => 'Issued',
                'custodian' => $_POST['officer_name'],
                'custodian_rank' => $_POST['officer_rank'],
                'custodian_nis' => $_POST['officer_nis'] ?? null
            ], 'id = ?', [$_POST['weapon_id']]);
            
            Database::commit();
            
            AuditLogger::logCreate('weapon_issue_log', $issueId, $_POST);
            
            $this->redirect('weapons/issue-log', ['success' => 'Weapon issued successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Weapon issue error: " . $e->getMessage());
            $this->redirect('weapons/issue', ['error' => 'Failed to issue weapon: ' . $e->getMessage()]);
        }
    }
    
    public function returnWeapon($id) {
        // Check permission
        if (!Auth::can('weapons.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to return weapons']);
            return;
        }
        
        $issue = Database::fetchOne(
            "SELECT wil.*, wi.id as weapon_id, wi.weapon_id as weapon_code
             FROM weapon_issue_log wil
             JOIN weapons_inventory wi ON wil.weapon_id = wi.id
             WHERE wil.id = ?",
            [$id]
        );
        
        if (!$issue) {
            $this->redirect('weapons/issue-log', ['error' => 'Issue record not found']);
            return;
        }
        
        $this->view('weapons/return', ['issue' => $issue]);
    }
    
    public function processReturn($id) {
        // Check permission
        if (!Auth::can('weapons.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to return weapons']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("weapons/return/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $issue = Database::fetchOne("SELECT * FROM weapon_issue_log WHERE id = ?", [$id]);
        
        if (!$issue) {
            $this->redirect('weapons/issue-log', ['error' => 'Issue record not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('weapon_issue_log', [
                'actual_return_date' => $_POST['return_date'] ?? date('Y-m-d'),
                'return_condition' => $_POST['return_condition'],
                'status' => 'Returned',
                'remarks' => $_POST['remarks'] ?? null
            ], 'id = ?', [$id]);
            
            // Update weapon location back to Armoury
            Database::update('weapons_inventory', [
                'current_location' => 'Armoury',
                'custodian' => null,
                'custodian_rank' => null,
                'custodian_nis' => null
            ], 'id = ?', [$issue['weapon_id']]);
            
            Database::commit();
            
            AuditLogger::logUpdate('weapon_issue_log', $id, $issue, $_POST);
            
            $this->redirect('weapons/issue-log', ['success' => 'Weapon returned successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Weapon return error: " . $e->getMessage());
            $this->redirect("weapons/return/$id", ['error' => 'Failed to process return: ' . $e->getMessage()]);
        }
    }
    
    public function export() {
        // Check permission
        if (!Auth::can('reports.export')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to export data']);
            return;
        }
        
        $weapons = Database::fetchAll(
            "SELECT wi.*, wt.type_name as weapon_type_name, wc.calibre_name
             FROM weapons_inventory wi
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             LEFT JOIN weapon_calibres wc ON wi.calibre_id = wc.id
             ORDER BY wi.created_at DESC"
        );
        
        if ($weapons === false) $weapons = [];
        
        $filename = 'weapons_inventory_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        Security::fputcsv($output, [
            'Weapon ID', 'Type', 'Make/Model', 'Serial Number', 'Calibre',
            'Source', 'Condition', 'Location', 'Custodian', 'Custodian Rank',
            'Custodian NIS', 'Date Acquired', 'Last Inspection', 'Next Inspection',
            'Remarks', 'Created At'
        ]);
        
        // Data
        foreach ($weapons as $w) {
            Security::fputcsv($output, [
                $w['weapon_id'] ?? '',
                $w['weapon_type_name'] ?? $w['weapon_type_other'] ?? '',
                $w['make_model'] ?? '',
                $w['serial_no'] ?? '',
                $w['calibre_name'] ?? $w['calibre_other'] ?? '',
                $w['source'] ?? '',
                $w['condition'] ?? '',
                $w['current_location'] ?? '',
                $w['custodian'] ?? '',
                $w['custodian_rank'] ?? '',
                $w['custodian_nis'] ?? '',
                $w['date_acquired'] ?? '',
                $w['last_inspection_date'] ?? '',
                $w['next_inspection_date'] ?? '',
                $w['remarks'] ?? '',
                $w['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('weapons', 'csv');
        exit;
    }
}