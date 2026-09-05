<?php
/**
 * API Controller for handling AJAX requests
 */
class ApiController extends Controller {

    public function __construct() {
        // "Api → Enable API access" / "API Rate Limit" settings.
        if (!Config::get('api_enabled', true)) {
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'API access is currently disabled.']);
            exit;
        }
        $this->enforceApiRateLimit();
    }

    /**
     * Per-minute request cap for this controller's endpoints, keyed by
     * client IP, driven by the "Api → API Rate Limit" setting. Separate
     * from Middleware::checkRateLimit(), which is a site-wide throttle for
     * unauthenticated requests only and reads a different (env-only) key.
     */
    private function enforceApiRateLimit() {
        $limit = (int) Config::get('api_rate_limit', 60);
        if ($limit <= 0) return; // 0/negative = unlimited

        $ip = Security::getClientIp();
        try {
            Database::insert('request_log', [
                'ip_address' => $ip,
                'url'        => substr($_SERVER['REQUEST_URI'] ?? '', 0, 255),
                'method'     => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            ]);
            $count = Database::fetchOne(
                "SELECT COUNT(*) as c FROM request_log
                  WHERE ip_address = ? AND url LIKE '%/api/%'
                    AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)",
                [$ip]
            );
            if ($count && (int) $count['c'] > $limit) {
                http_response_code(429);
                header('Retry-After: 60');
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'API rate limit exceeded. Please slow down.']);
                exit;
            }
        } catch (Throwable $e) {
            // request_log missing / DB hiccup — don't block traffic over this.
        }
    }

    /**
     * Get dashboard statistics
     */
    public function dashboardStats() {
        Auth::requireAuth();

        // Check if AJAX request
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }

        // Get statistics
        $stats = [
            'total_weapons' => $this->getTotalWeapons(),
            'issued_weapons' => $this->getIssuedWeaponsCount(),
            'available_weapons' => $this->getAvailableWeaponsCount(),
            'serviceable_weapons' => $this->getServiceableWeapons(),
            'unserviceable_weapons' => $this->getUnserviceableWeapons(),
            'total_ammunition' => $this->getTotalAmmunition(),
            'available_ammo' => $this->getAvailableAmmoCount(),
            'pending_returns' => $this->getPendingReturns()
        ];
        
        $this->jsonResponse(true, 'Success', $stats);
    }
    
    /**
     * Get LGAs by state
     */
    public function getLgas() {
        Auth::requireAuth();

        $stateParam = trim($_GET['state_id'] ?? $_GET['state'] ?? '');
        if (empty($stateParam)) {
            echo json_encode([]);
            exit;
        }

        $stateId = 0;
        if (is_numeric($stateParam)) {
            $stateId = (int)$stateParam;
        } else {
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

        if (!$stateId) {
            echo json_encode([]);
            exit;
        }
        
        $lgas = Database::fetchAll(
            "SELECT id, lga_name FROM lgas WHERE state_id = ? ORDER BY lga_name ASC",
            [$stateId]
        );
        if ($lgas === false) $lgas = [];
        
        header('Content-Type: application/json');
        echo json_encode($lgas);
        exit;
    }
    
    /**
     * Get commands by zone
     */
    public function getCommands() {
        Auth::requireAuth();

        $zoneId = (int) ($_GET['zone_id'] ?? 0);
        
        if (!$zoneId) {
            echo json_encode([]);
            exit;
        }
        
        $commands = Database::fetchAll(
            "SELECT id, command_name, state_id, lga_id FROM commands WHERE zone_id = ? ORDER BY command_name ASC",
            [$zoneId]
        );
        if ($commands === false) $commands = [];
        
        header('Content-Type: application/json');
        echo json_encode($commands);
        exit;
    }
    
    /**
     * Get land assets list for autocomplete
     */
    public function getLandAssets() {
        if (!Auth::check()) {
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }
        
        $search = $_GET['search'] ?? '';
        
        if ($search !== '') {
            $landAssets = Database::fetchAll(
                "SELECT id, asset_code, address FROM land_assets 
                 WHERE asset_code LIKE ? OR address LIKE ? 
                 ORDER BY asset_code ASC 
                 LIMIT 50",
                ["%$search%", "%$search%"]
            );
        } else {
            $landAssets = Database::fetchAll(
                "SELECT id, asset_code, address FROM land_assets 
                 ORDER BY asset_code ASC 
                 LIMIT 50"
            );
        }
        
        header('Content-Type: application/json');
        echo json_encode($landAssets ?: []);
        exit;
    }

    /**
     * Get land assets map locations with real-time coordinates and summary metrics
     */
    public function getLandMapLocations() {
        if (!Auth::check()) {
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $stateCoords = [
            'Abia' => [5.5247, 7.4925],
            'Adamawa' => [9.2094, 12.4818],
            'Akwa Ibom' => [5.0377, 7.9128],
            'Anambra' => [6.2209, 7.0722],
            'Bauchi' => [10.3158, 9.8442],
            'Bayelsa' => [4.9267, 6.2676],
            'Benue' => [7.7322, 8.5391],
            'Borno' => [11.8333, 13.1500],
            'Cross River' => [4.9757, 8.3417],
            'Delta' => [6.1983, 6.7275],
            'Ebonyi' => [6.3249, 8.1137],
            'Edo' => [6.3350, 5.6037],
            'Ekiti' => [7.6211, 5.2215],
            'Enugu' => [6.4584, 7.5464],
            'FCT' => [9.0765, 7.3986],
            'Federal Capital Territory' => [9.0765, 7.3986],
            'Abuja' => [9.0765, 7.3986],
            'Gombe' => [10.2897, 11.1673],
            'Imo' => [5.4850, 7.0355],
            'Jigawa' => [11.7584, 9.3496],
            'Kaduna' => [10.5105, 7.4165],
            'Kano' => [12.0022, 8.5920],
            'Katsina' => [12.9887, 7.6009],
            'Kebbi' => [12.4539, 4.1975],
            'Kogi' => [7.8023, 6.7333],
            'Kwara' => [8.4799, 4.5418],
            'Lagos' => [6.5244, 3.3792],
            'Nasarawa' => [8.4900, 8.5200],
            'Niger' => [9.6139, 6.5569],
            'Ogun' => [7.1557, 3.3458],
            'Ondo' => [7.2571, 5.2058],
            'Osun' => [7.7827, 4.5418],
            'Oyo' => [7.3775, 3.9470],
            'Plateau' => [9.8965, 8.8583],
            'Rivers' => [4.8156, 7.0498],
            'Sokoto' => [13.0059, 5.2476],
            'Taraba' => [8.8937, 11.3596],
            'Yobe' => [11.7470, 11.9660],
            'Zamfara' => [12.1628, 6.6614]
        ];

        // Fast SQL Summary
        $statusTotals = Database::fetchOne("
            SELECT 
                COUNT(*) as total, 
                SUM(CASE WHEN status = 'Developed' THEN 1 ELSE 0 END) as developed,
                SUM(CASE WHEN status = 'Undeveloped' THEN 1 ELSE 0 END) as undeveloped,
                SUM(CASE WHEN status = 'Fenced' THEN 1 ELSE 0 END) as fenced,
                SUM(CASE WHEN status LIKE '%Litigation%' THEN 1 ELSE 0 END) as litigation
            FROM land_assets
        ") ?: ['total' => 0, 'developed' => 0, 'undeveloped' => 0, 'fenced' => 0, 'litigation' => 0];

        // Zone Aggregate Breakdown
        $zoneRows = Database::fetchAll("
            SELECT z.zone_name, COUNT(la.id) as count 
            FROM land_assets la 
            LEFT JOIN commands c ON la.command_id = c.id 
            LEFT JOIN zones z ON c.zone_id = z.id 
            GROUP BY z.id
        ") ?: [];

        $regionStats = [
            'North Central' => 0,
            'North East' => 0,
            'North West' => 0,
            'South East' => 0,
            'South South' => 0,
            'South West' => 0,
            'HQ' => 0
        ];

        foreach ($zoneRows as $zr) {
            $zName = $zr['zone_name'] ?? '';
            $cnt = (int)$zr['count'];

            if (stripos($zName, 'Headquarters') !== false || stripos($zName, 'HQ') !== false) {
                $regionStats['HQ'] += $cnt;
            } elseif (stripos($zName, 'Lagos') !== false || stripos($zName, 'Ibadan') !== false) {
                $regionStats['South West'] += $cnt;
            } elseif (stripos($zName, 'Kaduna') !== false) {
                $regionStats['North West'] += $cnt;
            } elseif (stripos($zName, 'Bauchi') !== false) {
                $regionStats['North East'] += $cnt;
            } elseif (stripos($zName, 'Minna') !== false || stripos($zName, 'Makurdi') !== false) {
                $regionStats['North Central'] += $cnt;
            } elseif (stripos($zName, 'Owerri') !== false) {
                $regionStats['South East'] += $cnt;
            } elseif (stripos($zName, 'Benin') !== false) {
                $regionStats['South South'] += $cnt;
            } else {
                $regionStats['HQ'] += $cnt;
            }
        }

        // Fetch representative land asset sample for map pins
        $params = [];
        $sql = "SELECT la.id, la.asset_code, la.title_holder, la.address, la.status, 
                       la.size, la.size_unit, la.latitude, la.longitude, la.purpose_use,
                       la.ownership_type, la.date_acquired,
                       s.state_name, l.lga_name, z.zone_name, z.zone_code, c.command_name
                FROM land_assets la
                LEFT JOIN states s ON la.state_id = s.id
                LEFT JOIN lgas l ON la.lga_id = l.id
                LEFT JOIN commands c ON la.command_id = c.id
                LEFT JOIN zones z ON c.zone_id = z.id";
        
        $sql = Database::applyCommandFilter($sql, 'la', $params);
        $sql .= " ORDER BY la.id DESC LIMIT 400";

        $landAssets = Database::fetchAll($sql, $params) ?: [];

        $items = [];
        $countsByState = [];

        foreach ($landAssets as $asset) {
            $status = $asset['status'] ?: 'Developed';
            
            // Resolve State Name from State, Command, or Zone
            $stateName = $asset['state_name'] ?? '';
            $cmdName = $asset['command_name'] ?? '';
            $zName = $asset['zone_name'] ?? '';

            if (!$stateName || $stateName === '0') {
                if (stripos($cmdName, 'Lagos') !== false) $stateName = 'Lagos';
                elseif (stripos($cmdName, 'Delta') !== false) $stateName = 'Delta';
                elseif (stripos($cmdName, 'Ogun') !== false) $stateName = 'Ogun';
                elseif (stripos($cmdName, 'Kano') !== false) $stateName = 'Kano';
                elseif (stripos($cmdName, 'Kaduna') !== false) $stateName = 'Kaduna';
                elseif (stripos($cmdName, 'Rivers') !== false) $stateName = 'Rivers';
                elseif (stripos($cmdName, 'Edo') !== false) $stateName = 'Edo';
                elseif (stripos($cmdName, 'Oyo') !== false) $stateName = 'Oyo';
                elseif (stripos($cmdName, 'Enugu') !== false) $stateName = 'Enugu';
                elseif (stripos($cmdName, 'Borno') !== false) $stateName = 'Borno';
                elseif (stripos($cmdName, 'Benue') !== false) $stateName = 'Benue';
                elseif (stripos($cmdName, 'FCT') !== false || stripos($cmdName, 'Abuja') !== false) $stateName = 'FCT';
                elseif (stripos($zName, 'Lagos') !== false) $stateName = 'Lagos';
                elseif (stripos($zName, 'Benin') !== false) $stateName = 'Edo';
                elseif (stripos($zName, 'Owerri') !== false) $stateName = 'Imo';
                elseif (stripos($zName, 'Ibadan') !== false) $stateName = 'Oyo';
                elseif (stripos($zName, 'Bauchi') !== false) $stateName = 'Bauchi';
                elseif (stripos($zName, 'Minna') !== false) $stateName = 'Niger';
                elseif (stripos($zName, 'Makurdi') !== false) $stateName = 'Benue';
                elseif (stripos($zName, 'Kaduna') !== false) $stateName = 'Kaduna';
                else $stateName = 'FCT';
            }

            // Determine coordinates
            $lat = floatval($asset['latitude'] ?? 0);
            $lng = floatval($asset['longitude'] ?? 0);

            if ($lat == 0 || $lng == 0) {
                if (isset($stateCoords[$stateName])) {
                    $offsetCount = $countsByState[$stateName] ?? 0;
                    $countsByState[$stateName] = $offsetCount + 1;

                    $angle = ($offsetCount * 137.5) * (M_PI / 180);
                    $radius = 0.04 * sqrt($offsetCount + 1);
                    $lat = $stateCoords[$stateName][0] + ($radius * cos($angle));
                    $lng = $stateCoords[$stateName][1] + ($radius * sin($angle));
                } else {
                    $lat = 9.0765;
                    $lng = 7.3986;
                }
            }

            $items[] = [
                'id' => (int)$asset['id'],
                'asset_code' => $asset['asset_code'],
                'title_holder' => $asset['title_holder'] ?: 'Nigeria Immigration Service',
                'address' => $asset['address'] ?: 'Command Sector Area',
                'state_name' => $stateName,
                'lga_name' => $asset['lga_name'] ?: 'State Sector',
                'zone_name' => $zName ?: 'HQ',
                'command_name' => $cmdName ?: 'State Command',
                'status' => $status,
                'ownership_type' => $asset['ownership_type'] ?: 'FGN',
                'size' => $asset['size'] ? number_format($asset['size'], 2) : 'N/A',
                'size_unit' => $asset['size_unit'] ?: 'sqm',
                'purpose_use' => $asset['purpose_use'] ?: 'Operational Use',
                'date_acquired' => $asset['date_acquired'] ?: 'N/A',
                'lat' => round($lat, 6),
                'lng' => round($lng, 6)
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'summary' => [
                'total' => (int)$statusTotals['total'],
                'developed' => (int)$statusTotals['developed'],
                'undeveloped' => (int)$statusTotals['undeveloped'],
                'fenced' => (int)$statusTotals['fenced'],
                'litigation' => (int)$statusTotals['litigation'],
                'by_region' => $regionStats
            ],
            'locations' => $items
        ]);
        exit;
    }
    
    /**
     * Get weapons list
     */
    public function getWeapons() {
        Auth::requirePermission('weapons.view');
        
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        $search = trim($_GET['search'] ?? '');
        $params = [];
        $sql = "SELECT w.*, wt.type_name
                FROM weapons_inventory w
                LEFT JOIN weapon_types wt ON w.weapon_type_id = wt.id";
        if ($search !== '') {
            $sql .= " WHERE (w.weapon_id LIKE ? OR w.serial_no LIKE ? OR w.make_model LIKE ?)";
            $like = "%{$search}%";
            $params = [$like, $like, $like];
        }
        $sql = Database::applyCommandFilter($sql, 'w', $params);
        $sql .= " ORDER BY w.created_at DESC LIMIT 50";

        echo json_encode(['success' => true, 'data' => Database::fetchAll($sql, $params) ?: []]);
        exit;
    }
    
    /**
     * Get ammunition list
     */
    public function getAmmunition() {
        Auth::requirePermission('ammunition.view');
        
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        $search = trim($_GET['search'] ?? '');
        $params = [];
        $sql = "SELECT a.*, at.ammo_type, ac.calibre
                FROM ammunition_inventory a
                LEFT JOIN ammunition_types at ON a.ammo_type_id = at.id
                LEFT JOIN ammunition_calibres ac ON a.calibre_id = ac.id";
        if ($search !== '') {
            $sql .= " WHERE (a.ammo_id LIKE ? OR a.batch_number LIKE ? OR at.ammo_type LIKE ? OR ac.calibre LIKE ?)";
            $like = "%{$search}%";
            $params = [$like, $like, $like, $like];
        }
        $sql = Database::applyCommandFilter($sql, 'a', $params);
        $sql .= " ORDER BY a.created_at DESC LIMIT 50";

        echo json_encode(['success' => true, 'data' => Database::fetchAll($sql, $params) ?: []]);
        exit;
    }
    
    /**
     * Get requisitions list
     */
    public function getRequisitions() {
        Auth::requirePermission('requisition.view');
        
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        $status = $_GET['status'] ?? '';
        $userId = $_GET['user_id'] ?? '';

        $sql = "SELECT r.*, u.full_name as requester_name, c.command_name
                FROM requisitions r
                LEFT JOIN users u ON r.requesting_officer_id = u.id
                LEFT JOIN commands c ON r.requesting_command_id = c.id
                WHERE 1=1";
        $params = [];

        // Command isolation.
        if (Auth::isCommandRestricted()) {
            $sql .= " AND r.requesting_command_id = ?";
            $params[] = Auth::commandId();
        }

        if ($status) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }
        if ($userId !== '' && ctype_digit((string) $userId)) {
            $sql .= " AND r.created_by = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY r.created_at DESC LIMIT 100";

        echo json_encode(['success' => true, 'data' => Database::fetchAll($sql, $params) ?: []]);
        exit;
    }
    
    /**
     * Get returns log
     */
    public function getReturnsLog() {
        Auth::requirePermission('returns.view');
        
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $sql = "SELECT r.*, u.full_name as received_by_name, req.requisition_number
                FROM returns r
                LEFT JOIN users u ON r.received_by = u.id
                LEFT JOIN requisitions req ON r.requisition_id = req.id
                WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (r.return_number LIKE ? OR r.returning_officer_name LIKE ? OR r.returning_unit LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($status) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT 100";
        
        $returns = Database::fetchAll($sql, $params);
        
        // Get counts for each return
        foreach ($returns as &$return) {
            $weapons = Database::fetchOne(
                "SELECT COUNT(*) as count, SUM(arm_total) as total FROM return_weapons WHERE return_id = ?",
                [$return['id']]
            );
            $return['weapons_count'] = $weapons['count'] ?? 0;
            $return['weapons_total'] = $weapons['total'] ?? 0;
            
            $ammo = Database::fetchOne(
                "SELECT COUNT(*) as count, SUM(rounds_returned) as total FROM return_ammunition WHERE return_id = ?",
                [$return['id']]
            );
            $return['ammo_count'] = $ammo['count'] ?? 0;
            $return['ammo_total'] = $ammo['total'] ?? 0;
        }
        
        echo json_encode(['success' => true, 'data' => $returns]);
        exit;
    }
    
    /**
     * Update inventory
     */
    public function updateInventory() {
        Auth::requirePermission('weapons.edit');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
        
        $type = $_POST['type'] ?? '';
        $id = $_POST['id'] ?? 0;
        $field = $_POST['field'] ?? '';
        $value = $_POST['value'] ?? '';
        
        if (!$type || !$id || !$field) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        $allowedFields = [
            'weapons' => ['condition', 'current_location', 'custodian', 'remarks'],
            'ammunition' => ['condition', 'storage_location', 'remarks']
        ];
        
        if (!in_array($field, $allowedFields[$type] ?? [])) {
            echo json_encode(['success' => false, 'message' => 'Field not allowed']);
            exit;
        }
        
        $table = $type === 'weapons' ? 'weapons_inventory' : 'ammunition_inventory';
        $oldData = Database::fetchOne("SELECT * FROM $table WHERE id = ?", [(int) $id]);

        if (!$oldData) {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
            exit;
        }

        // Command isolation: a command-restricted user may only edit their own
        // command's inventory.
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
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Update inventory error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        exit;
    }
    
    /**
     * Validate serial number
     */
    public function validateSerial() {
        Auth::requireAuth();
        
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        $type = $_GET['type'] ?? '';
        $serial = $_GET['serial'] ?? '';
        $excludeId = $_GET['exclude_id'] ?? null;
        
        if (!$type || !$serial) {
            echo json_encode(['valid' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        $exists = false;
        
        if ($type === 'weapon') {
            $sql = "SELECT COUNT(*) as count FROM weapons_inventory WHERE serial_no = ?";
            $params = [$serial];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $result = Database::fetchOne($sql, $params);
            $exists = ($result['count'] ?? 0) > 0;
        } elseif ($type === 'ammunition') {
            // Check ammunition batch number uniqueness if needed
            $sql = "SELECT COUNT(*) as count FROM ammunition_inventory WHERE batch_number = ?";
            $params = [$serial];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $result = Database::fetchOne($sql, $params);
            $exists = ($result['count'] ?? 0) > 0;
        }
        
        echo json_encode([
            'valid' => !$exists,
            'exists' => $exists,
            'message' => $exists ? 'Serial number already exists' : 'Serial number is available'
        ]);
        exit;
    }
    
    /**
     * Get audit details
     */
    public function getAuditDetails() {
        Auth::requirePermission('audit.view');
        
        if (!$this->isAjax()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        $auditId = $_GET['audit_id'] ?? 0;
        
        if (!$auditId) {
            echo json_encode(['success' => false, 'message' => 'Audit ID required']);
            exit;
        }
        
        $weapons = Database::fetchAll(
            "SELECT aw.*, wi.weapon_id, wi.make_model, wi.serial_no
             FROM audit_weapons aw
             JOIN weapons_inventory wi ON aw.weapon_id = wi.id
             WHERE aw.audit_id = ?",
            [$auditId]
        );
        
        $ammunition = Database::fetchAll(
            "SELECT aa.*, ai.ammo_id, at.ammo_type, ac.calibre
             FROM audit_ammunition aa
             JOIN ammunition_inventory ai ON aa.ammo_id = ai.id
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             WHERE aa.audit_id = ?",
            [$auditId]
        );
        
        echo json_encode([
            'success' => true,
            'weapons' => $weapons,
            'ammunition' => $ammunition
        ]);
        exit;
    }
    
    // =============================================
    // PRIVATE HELPER METHODS
    // =============================================
    
    /**
     * Get total weapons count
     */
    private function getTotalWeapons() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get issued weapons count
     */
    private function getIssuedWeaponsCount() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory WHERE current_location = 'Issued'");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get available weapons count
     */
    private function getAvailableWeaponsCount() {
        // See WeaponIssueController::getAvailableWeapons() — 'In Storage' is
        // the value the actual seed data uses; 'Armoury'/'Available' are the
        // only ones the create/edit form offers, but almost nothing is
        // literally tagged that way.
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory WHERE current_location IN ('Armoury', 'Available', 'In Storage')");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get serviceable weapons count
     */
    private function getServiceableWeapons() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory WHERE `condition` = 'Serviceable'");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get unserviceable weapons count
     */
    private function getUnserviceableWeapons() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory WHERE `condition` = 'Unserviceable'");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get total ammunition count
     */
    private function getTotalAmmunition() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM ammunition_inventory");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get available ammo types count
     */
    private function getAvailableAmmoCount() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM ammunition_inventory WHERE balance > 0");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get pending returns count
     */
    private function getPendingReturns() {
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM weapon_issue_log WHERE status = 'Issued' AND expected_return_date < CURDATE()");
        return $result['count'] ?? 0;
    }
    
    /**
     * Check if AJAX request
     */
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Send JSON response
     */
    protected function jsonResponse($success, $message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'stats' => $data
        ]);
        exit;
    }
}