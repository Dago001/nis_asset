<?php
/**
 * Dashboard Controller
 */
class DashboardController extends Controller {
    
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?error=' . urlencode('Please login to access the dashboard'));
            exit;
        }
        
        $userRoles = $_SESSION['roles'] ?? [];
        // Route all Armorer variants to the Armorer workspace
        if (in_array('Armorer', $userRoles)
            || in_array('Command Armorer', $userRoles)
            || in_array('HQ Armorer', $userRoles)) {
            $this->armorerDashboard();
            return;
        }
        
        // Get user info from session
        $user = [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? 'User',
            'email' => $_SESSION['email'] ?? '',
            'rank' => $_SESSION['rank'] ?? '',
            'nis_number' => $_SESSION['nis_number'] ?? '',
            'roles' => $_SESSION['roles'] ?? ['Staff']
        ];
        
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true;
        $userPermissions = $_SESSION['permissions'] ?? [];
        
        // Set variables for the view
        $active = 'dashboard';
        $title = 'Dashboard';
        
        // Get database connection for statistics
        $pdo = null;
        if (function_exists('getDBConnection')) {
            $pdo = getDBConnection();
        }
        
        // Initialize stats array with default values
        $stats = [
            'total_weapons' => 0,
            'weapons_issued' => 0,
            'total_ammunition' => 0,
            'ammunition_balance' => 0,
            'total_land' => 0,
            'total_buildings' => 0,
            'total_rented' => 0,
            'total_projects' => 0,
            'total_movable' => 0,
            'total_ict' => 0,
            'total_vehicles' => 0,
            'total_aircraft' => 0,
            'total_marine' => 0,
            'total_motorcycles' => 0,
            'total_users' => 1, // At least the current user
            'pending_requisitions' => 0,
            'expiring_ammunition' => 0,
            'unserviceable_weapons' => 0
        ];
        
        $activities = [];
        
        // Try to get real data if database is connected
        if ($pdo) {
            try {
                // Command filter setup
                $cmdFilter = '';
                $cmdParams = [];
                if (Auth::isCommandRestricted()) {
                    $cmdFilter = " WHERE command_id = ?";
                    $cmdParams[] = Auth::commandId();
                }
                
                // Get weapons aggregate stats
                $wpnParams = Auth::isCommandRestricted() ? [Auth::commandId()] : [];
                $wpnFilter = Auth::isCommandRestricted() ? " WHERE command_id = ?" : "";
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN current_location = 'Issued' THEN 1 ELSE 0 END) as issued,
                        SUM(CASE WHEN `condition` = 'Serviceable' THEN 1 ELSE 0 END) as serviceable,
                        SUM(CASE WHEN `condition` = 'Unserviceable' THEN 1 ELSE 0 END) as unserviceable,
                        SUM(CASE WHEN current_location = 'In Repair' THEN 1 ELSE 0 END) as in_repair
                    FROM weapons_inventory" . $wpnFilter
                );
                $stmt->execute($wpnParams);
                $wpnRes = $stmt->fetch();
                $stats['total_weapons'] = $wpnRes['total'] ?? 0;
                $stats['weapons_issued'] = $wpnRes['issued'] ?? 0;
                $stats['serviceable_weapons'] = $wpnRes['serviceable'] ?? 0;
                $stats['unserviceable_weapons'] = $wpnRes['unserviceable'] ?? 0;
                $stats['in_repair_weapons'] = $wpnRes['in_repair'] ?? 0;
                
                // Get total ammunition & balance
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_types,
                        SUM(balance) as total_rounds
                    FROM ammunition_inventory" . $cmdFilter
                );
                $stmt->execute($cmdParams);
                $ammoRes = $stmt->fetch();
                $stats['total_ammunition'] = $ammoRes['total_types'] ?? 0;
                $stats['ammunition_balance'] = $ammoRes['total_rounds'] ?? 0;
                
                // Get land assets
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM land_assets" . $cmdFilter);
                $stmt->execute($cmdParams);
                $stats['total_land'] = $stmt->fetch()['count'] ?? 0;
                
                // Get buildings
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM building_assets" . $cmdFilter);
                $stmt->execute($cmdParams);
                $stats['total_buildings'] = $stmt->fetch()['count'] ?? 0;
                
                // Get rented properties
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM rented_properties" . $cmdFilter);
                $stmt->execute($cmdParams);
                $stats['total_rented'] = $stmt->fetch()['count'] ?? 0;
                
                // Get ongoing projects
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ongoing_projects" . $cmdFilter);
                $stmt->execute($cmdParams);
                $stats['total_projects'] = $stmt->fetch()['count'] ?? 0;
                
                // Get movable assets
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM movable_assets" . $cmdFilter);
                $stmt->execute($cmdParams);
                $stats['total_movable'] = $stmt->fetch()['count'] ?? 0;
                
                // Get ICT assets
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ict_assets" . $cmdFilter);
                $stmt->execute($cmdParams);
                $stats['total_ict'] = $stmt->fetch()['count'] ?? 0;
                
                // Get fleet assets aggregate
                $stmt = $pdo->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM vehicle_assets" . $cmdFilter . ") as total_vehicles,
                        (SELECT COUNT(*) FROM aircraft_assets" . $cmdFilter . ") as total_aircraft,
                        (SELECT COUNT(*) FROM marine_assets" . $cmdFilter . ") as total_marine,
                        (SELECT COUNT(*) FROM motorcycle_assets" . $cmdFilter . ") as total_motorcycles
                ");
                $fleetParams = [];
                if (Auth::isCommandRestricted()) {
                    $fleetParams = [Auth::commandId(), Auth::commandId(), Auth::commandId(), Auth::commandId()];
                }
                $stmt->execute($fleetParams);
                $fleetRes = $stmt->fetch();
                $stats['total_vehicles'] = $fleetRes['total_vehicles'] ?? 0;
                $stats['total_aircraft'] = $fleetRes['total_aircraft'] ?? 0;
                $stats['total_marine'] = $fleetRes['total_marine'] ?? 0;
                $stats['total_motorcycles'] = $fleetRes['total_motorcycles'] ?? 0;
                
                // Get users count
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = 1" . (Auth::isCommandRestricted() ? " AND command_id = ?" : ""));
                $stmt->execute(Auth::isCommandRestricted() ? [Auth::commandId()] : []);
                $stats['total_users'] = $stmt->fetch()['count'] ?? 1;
                
                // Get requisitions aggregate stats
                $reqParams = Auth::isCommandRestricted() ? [Auth::commandId()] : [];
                $reqFilter = Auth::isCommandRestricted() ? " WHERE requesting_command_id = ?" : "";
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                    FROM requisitions" . $reqFilter
                );
                $stmt->execute($reqParams);
                $reqRes = $stmt->fetch();
                $stats['pending_requisitions'] = $reqRes['pending'] ?? 0;
                $stats['approved_requisitions'] = $reqRes['approved'] ?? 0;
                $stats['rejected_requisitions'] = $reqRes['rejected'] ?? 0;
                
                // Get expiring ammunition (within 30 days)
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count FROM ammunition_inventory 
                    WHERE expiry_date IS NOT NULL 
                    AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                    AND expiry_date >= CURDATE()" . (Auth::isCommandRestricted() ? " AND command_id = ?" : "")
                );
                $stmt->execute(Auth::isCommandRestricted() ? [Auth::commandId()] : []);
                $stats['expiring_ammunition'] = $stmt->fetch()['count'] ?? 0;
                
                // Get requisitions by priority levels
                $cmdPriorityFilter = Auth::isCommandRestricted() ? " WHERE requesting_command_id = ?" : "";
                $cmdPriorityParams = Auth::isCommandRestricted() ? [Auth::commandId()] : [];
                $stats['requisitions_by_priority'] = Database::fetchAll("
                    SELECT priority_level, COUNT(*) as count 
                    FROM requisitions" . $cmdPriorityFilter . "
                    GROUP BY priority_level",
                    $cmdPriorityParams
                ) ?: [];
                
                // Get top 5 commands by weapon stock
                $cmdWpnFilter = Auth::isCommandRestricted() ? " WHERE wi.command_id = ?" : "";
                $cmdWpnParams = Auth::isCommandRestricted() ? [Auth::commandId()] : [];
                $stats['top_commands'] = Database::fetchAll("
                    SELECT c.command_name, COUNT(wi.id) as count 
                    FROM weapons_inventory wi
                    JOIN commands c ON wi.command_id = c.id" . $cmdWpnFilter . "
                    GROUP BY c.id, c.command_name
                    ORDER BY count DESC
                    LIMIT 5",
                    $cmdWpnParams
                ) ?: [];
                
                // Get recent activities
                $stmt = $pdo->query("
                    SELECT al.*, u.full_name 
                    FROM audit_logs al 
                    LEFT JOIN users u ON al.user_id = u.id 
                    ORDER BY al.created_at DESC 
                    LIMIT 10
                ");
                $activities = $stmt->fetchAll();
                
            } catch (Exception $e) {
                error_log("Dashboard stats error: " . $e->getMessage());
            }
        }
        
        // Requisitions awaiting THIS Command Approval Officer's own
        // decision, on THEIR command only — the workflow calls for these to
        // "appear at the dashboard", not just be reachable via the
        // Requisitions Queue page like every other stage's approver.
        $pendingMyApproval = [];
        if (in_array('Command Approval Officer', $userRoles, true) && !$isSuperAdmin) {
            $commandId = (int) Auth::commandId();
            if ($commandId > 0) {
                $pendingMyApproval = Database::fetchAll(
                    "SELECT r.*, u.full_name as requester_name, COUNT(ri.id) as item_count
                     FROM requisitions r
                     LEFT JOIN users u ON r.created_by = u.id
                     LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
                     WHERE r.status = 'Pending' AND r.approval_stage = 'Command_Approval'
                       AND r.requesting_command_id = ?
                     GROUP BY r.id
                     ORDER BY
                        CASE r.priority_level
                            WHEN 'Urgent' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4
                        END, r.created_at ASC",
                    [$commandId]
                ) ?: [];
            }
        }

        // Pass data to view
        $this->view('dashboard/index', [
            'stats' => $stats,
            'activities' => $activities,
            'user' => $user,
            'isSuperAdmin' => $isSuperAdmin,
            'userPermissions' => $userPermissions,
            'active' => $active,
            'title' => $title,
            'pendingMyApproval' => $pendingMyApproval
        ]);
    }
    
    /**
     * Armourer Dashboard View
     * Supports both Command Armorer (command-scoped) and HQ Armorer (service-wide)
     */
    private function armorerDashboard() {
        $isCommandArmorer = Auth::isCommandArmorer();
        $isHQArmorer      = Auth::isHQArmorer();
        $commandId        = (int) Auth::commandId();

        // A command armorer with no command assigned sees nothing (fail closed).
        if ($isCommandArmorer && $commandId <= 0) {
            $isCommandArmorer = false;
            $commandId = -1;
        }

        // ---- Build command filter (int-cast above → safe to inline) ----
        $wFilter  = $isCommandArmorer ? " WHERE wi.command_id = {$commandId}" : "";
        $aFilter  = $isCommandArmorer ? " WHERE command_id = {$commandId}" : "";
        $reqFilter = $isCommandArmorer ? " AND r.requesting_command_id = {$commandId}" : "";
        $issueFilter = $isCommandArmorer ? " WHERE wi.command_id = {$commandId}" : "";
        
        // ---- Weapons Stats ----
        $weaponsStats = [
            'total'       => Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory wi{$wFilter}")['count'] ?? 0,
            // 'In Storage' is the value the actual seed data uses for an
            // unissued weapon — see WeaponIssueController::getAvailableWeapons().
            'available'   => Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory wi{$wFilter}" . ($isCommandArmorer ? " AND" : " WHERE") . " wi.current_location IN ('Armoury', 'Available', 'In Storage')")['count'] ?? 0,
            'issued'      => Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory wi{$wFilter}" . ($isCommandArmorer ? " AND" : " WHERE") . " wi.current_location = 'Issued'")['count'] ?? 0,
            'serviceable' => Database::fetchOne("SELECT COUNT(*) as count FROM weapons_inventory wi{$wFilter}" . ($isCommandArmorer ? " AND" : " WHERE") . " wi.condition = 'Serviceable'")['count'] ?? 0
        ];
        
        // ---- Ammunition Stats ----
        $ammoStats = [
            'avail_rounds'  => Database::fetchOne("SELECT SUM(balance) as count FROM ammunition_inventory{$aFilter}")['count'] ?? 0,
            'issued_rounds' => Database::fetchOne("SELECT SUM(quantity_issued) as count FROM ammunition_inventory{$aFilter}")['count'] ?? 0
        ];
        
        // ---- Approved & Partially Issued Requisitions queue ready for Armorer Issuance ----
        $rawPendingIssues = Database::fetchAll("
            SELECT r.*, c.command_name,
                   COALESCE(r.requesting_officer_name, u.full_name, 'Officer') as requesting_officer_name,
                   COUNT(ri.id) as total_items
            FROM requisitions r
            LEFT JOIN commands c ON r.requesting_command_id = c.id
            LEFT JOIN users u ON (r.requesting_officer_id = u.id OR r.created_by = u.id)
            LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
            WHERE (r.status IN ('Approved', 'Partially Issued') OR r.approval_stage = 'Armorer_Issue')
              AND r.status NOT IN ('Completed', 'Issued', 'Rejected', 'Draft', 'Pending')
              AND r.approval_stage != 'Completed'
            {$reqFilter}
            GROUP BY r.id
            ORDER BY
                CASE r.priority_level
                    WHEN 'Urgent' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4
                END, r.updated_at DESC
            LIMIT 50
        ") ?: [];

        $pendingIssues = [];
        foreach ($rawPendingIssues as $issue) {
            $reqId = (int)$issue['id'];

            // Check if there are remaining weapons or ammo to be issued
            $weaponsRequested = (int)(Database::fetchOne(
                "SELECT COALESCE(SUM(quantity), 0) as total FROM requisition_items WHERE requisition_id = ? AND item_type = 'Weapon'",
                [$reqId]
            )['total'] ?? 0);

            $weaponsIssued = (int)(Database::fetchOne(
                "SELECT COUNT(*) as count FROM weapon_issue_log WHERE requisition_id = ?",
                [$reqId]
            )['count'] ?? 0);

            $ammoRequested = (int)(Database::fetchOne(
                "SELECT COALESCE(SUM(quantity), 0) as total FROM requisition_items WHERE requisition_id = ? AND item_type = 'Ammunition'",
                [$reqId]
            )['total'] ?? 0);

            $ammoIssued = (int)(Database::fetchOne(
                "SELECT COALESCE(SUM(units_issued), 0) as total FROM ammunition_issue_log WHERE requisition_id = ?",
                [$reqId]
            )['total'] ?? 0);

            $remainingWeapons = max(0, $weaponsRequested - $weaponsIssued);
            $remainingAmmo = max(0, $ammoRequested - $ammoIssued);

            // If all requested items have already been issued (or no items remain to be issued)
            if (($weaponsRequested > 0 || $ammoRequested > 0) && $remainingWeapons === 0 && $remainingAmmo === 0) {
                // Ensure DB status is marked Completed
                if ($issue['status'] !== 'Completed' || $issue['approval_stage'] !== 'Completed') {
                    Database::update('requisitions', [
                        'status' => 'Completed',
                        'approval_stage' => 'Completed',
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$reqId]);
                }
                // Do NOT include in Pending Issuance Queue
                continue;
            }

            $issue['remaining_weapons'] = $remainingWeapons;
            $issue['remaining_ammo'] = $remainingAmmo;
            $issue['weapons_requested'] = $weaponsRequested;
            $issue['ammo_requested'] = $ammoRequested;

            $pendingIssues[] = $issue;
            if (count($pendingIssues) >= 20) {
                break;
            }
        }



        // ---- Requisitions awaiting THIS HQ Armorer's own vetting decision
        // (service-wide — HQ Armorer isn't command-restricted) ----
        // Gated on the literal role, not $isHQArmorer (which also covers
        // the legacy "Armorer" role for inventory-visibility purposes, but
        // approve() only lets an actual "HQ Armorer" act at this stage).
        $pendingVetting = [];
        if (in_array('HQ Armorer', $_SESSION['roles'] ?? [], true)) {
            $pendingVetting = Database::fetchAll("
                SELECT r.*, c.command_name, u.full_name as requester_name,
                       COUNT(ri.id) as total_items
                FROM requisitions r
                LEFT JOIN commands c ON r.requesting_command_id = c.id
                LEFT JOIN users u ON r.created_by = u.id
                LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
                WHERE r.status = 'Pending' AND r.approval_stage = 'HQ_Vetting'
                GROUP BY r.id
                ORDER BY
                    CASE r.priority_level
                        WHEN 'Urgent' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4
                    END, r.created_at ASC
            ") ?: [];
        }
        
        // ---- Recent Issue Log ----
        $recentIssuesLog = Database::fetchAll("
            SELECT wil.*, wi.serial_no, wi.make_model, u.full_name as issuer_name
            FROM weapon_issue_log wil FORCE INDEX (idx_wil_date)
            JOIN weapons_inventory wi ON wil.weapon_id = wi.id
            LEFT JOIN users u ON wil.issued_by = u.id
            {$issueFilter}
            ORDER BY wil.issue_date DESC
            LIMIT 10
        ") ?: [];
        
        $this->view('dashboard/armorer', [
            'weaponsStats'     => $weaponsStats,
            'ammoStats'        => $ammoStats,
            'pendingIssues'    => $pendingIssues,
            'pendingVetting'   => $pendingVetting,
            'recentIssuesLog'  => $recentIssuesLog,
            'isCommandArmorer' => $isCommandArmorer,
            'isHQArmorer'      => $isHQArmorer
        ]);
    }
}