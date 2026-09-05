<?php
/**
 * Audit Controller
 */
class AuditController extends Controller {
    
    public function quarterly() {
        // Check permission using can() method
        if (!Auth::can('audit.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view audits']);
            return;
        }
        
        $audits = Database::fetchAll(
            "SELECT qa.*, u.full_name as created_by_name, c.command_name
             FROM quarterly_audits qa
             LEFT JOIN users u ON qa.created_by = u.id
             LEFT JOIN commands c ON qa.command_id = c.id
             ORDER BY qa.created_at DESC
             LIMIT 50"
        );
        
        if ($audits === false) $audits = [];
        
        $this->view('audit/quarterly/index', ['audits' => $audits]);
    }
    
    public function createQuarterly() {
        // Check permission using can() method
        if (!Auth::can('audit.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create audits']);
            return;
        }
        
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        if ($commands === false) $commands = [];
        
        $weapons = Database::fetchAll(
            "SELECT wi.*, wt.type_name 
             FROM weapons_inventory wi
             LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             ORDER BY wi.created_at DESC"
        );
        if ($weapons === false) $weapons = [];
        
        $ammunition = Database::fetchAll(
            "SELECT ai.*, at.ammo_type, ac.calibre
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             ORDER BY ai.created_at DESC"
        );
        if ($ammunition === false) $ammunition = [];
        
        $this->view('audit/quarterly/create', [
            'commands' => $commands,
            'weapons' => $weapons,
            'ammunition' => $ammunition
        ]);
    }
    
    public function storeQuarterly() {
        // Check permission using can() method
        if (!Auth::can('audit.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create audits']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('audit/quarterly/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation instead of Security::validate()
        $errors = [];
        
        if (empty($_POST['audit_date'])) {
            $errors['audit_date'] = 'Audit date is required';
        }
        
        if (empty($_POST['quarter'])) {
            $errors['quarter'] = 'Quarter is required';
        }
        
        if (empty($_POST['year']) || !is_numeric($_POST['year'])) {
            $errors['year'] = 'Year is required and must be a number';
        }
        
        if (empty($_POST['audit_officer'])) {
            $errors['audit_officer'] = 'Audit officer name is required';
        } elseif (strlen($_POST['audit_officer']) > 100) {
            $errors['audit_officer'] = 'Audit officer name must not exceed 100 characters';
        } elseif (!isValidName($_POST['audit_officer'])) {
            $errors['audit_officer'] = "Audit officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        
        if (empty($_POST['auditor_rank'])) {
            $errors['auditor_rank'] = 'Auditor rank is required';
        } elseif (strlen($_POST['auditor_rank']) > 50) {
            $errors['auditor_rank'] = 'Auditor rank must not exceed 50 characters';
        }
        
        if (empty($_POST['auditor_nis'])) {
            $errors['auditor_nis'] = 'Auditor NIS number is required';
        } elseif (!isDigitsOnly($_POST['auditor_nis'])) {
            $errors['auditor_nis'] = 'NIS number must contain numbers only';
        } elseif (strlen($_POST['auditor_nis']) > 20) {
            $errors['auditor_nis'] = 'Auditor NIS number must not exceed 20 characters';
        }
        
        if (empty($_POST['unit'])) {
            $errors['unit'] = 'Unit is required';
        } elseif (strlen($_POST['unit']) > 100) {
            $errors['unit'] = 'Unit must not exceed 100 characters';
        }
        
        if (empty($_POST['audit_location'])) {
            $errors['audit_location'] = 'Audit location is required';
        } elseif (strlen($_POST['audit_location']) > 200) {
            $errors['audit_location'] = 'Audit location must not exceed 200 characters';
        }
        
        if (empty($_POST['command_id']) || !is_numeric($_POST['command_id'])) {
            $errors['command_id'] = 'Command is required';
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('audit/quarterly/create');
            return;
        }
        
        $auditNumber = $this->generateAuditNumber();
        
        Database::beginTransaction();
        
        try {
            $auditId = Database::insert('quarterly_audits', [
                'audit_number' => $auditNumber,
                'audit_date' => $_POST['audit_date'],
                'quarter' => $_POST['quarter'],
                'year' => $_POST['year'],
                'audit_officer' => $_POST['audit_officer'],
                'auditor_rank' => $_POST['auditor_rank'],
                'auditor_nis' => $_POST['auditor_nis'],
                'unit' => $_POST['unit'],
                'audit_location' => $_POST['audit_location'],
                'command_id' => $_POST['command_id'],
                'audit_remarks' => $_POST['audit_remarks'] ?? null,
                'total_weapons_audited' => $_POST['total_weapons_audited'] ?? 0,
                'total_ammunition_audited' => $_POST['total_ammunition_audited'] ?? 0,
                'weapons_with_variance' => $_POST['weapons_with_variance'] ?? 0,
                'ammunition_with_variance' => $_POST['ammunition_with_variance'] ?? 0,
                'total_missing_weapons' => $_POST['total_missing_weapons'] ?? 0,
                'audit_conclusion' => $_POST['audit_conclusion'] ?? null,
                'recommending_officer' => $_POST['recommending_officer'] ?? null,
                'approving_officer' => $_POST['approving_officer'] ?? null,
                'status' => 'Submitted',
                'created_by' => Auth::id()
            ]);
            
            // Insert weapons audited
            if (isset($_POST['weapon_id']) && is_array($_POST['weapon_id'])) {
                foreach ($_POST['weapon_id'] as $index => $weaponId) {
                    if (empty($weaponId)) continue;
                    
                    Database::insert('audit_weapons', [
                        'audit_id' => $auditId,
                        'weapon_id' => $weaponId,
                        'weapon_type' => $_POST['weapon_type'][$index] ?? null,
                        'make_model' => $_POST['make_model'][$index] ?? null,
                        'serial_number' => $_POST['serial_number'][$index] ?? null,
                        'system_status' => $_POST['system_status'][$index] ?? null,
                        'physical_status' => $_POST['physical_status'][$index] ?? null,
                        'variance' => $_POST['variance'][$index] ?? '0',
                        'variance_value' => $_POST['variance_value'][$index] ?? 0,
                        'condition' => $_POST['condition'][$index] ?? null,
                        'remarks' => $_POST['weapon_remarks'][$index] ?? null
                    ]);
                }
            }
            
            // Insert ammunition audited
            if (isset($_POST['ammo_id']) && is_array($_POST['ammo_id'])) {
                foreach ($_POST['ammo_id'] as $index => $ammoId) {
                    if (empty($ammoId)) continue;
                    
                    Database::insert('audit_ammunition', [
                        'audit_id' => $auditId,
                        'ammo_id' => $ammoId,
                        'ammo_type' => $_POST['ammo_type'][$index] ?? null,
                        'calibre' => $_POST['calibre'][$index] ?? null,
                        'system_units' => $_POST['system_units'][$index] ?? 0,
                        'physical_units' => $_POST['physical_units'][$index] ?? 0,
                        'variance' => $_POST['ammo_variance'][$index] ?? '0',
                        'variance_value' => $_POST['ammo_variance_value'][$index] ?? 0,
                        'condition' => $_POST['ammo_condition'][$index] ?? null,
                        'remarks' => $_POST['ammo_remarks'][$index] ?? null
                    ]);
                }
            }
            
            // Insert missing weapons
            if (isset($_POST['missing_arm_type']) && is_array($_POST['missing_arm_type'])) {
                foreach ($_POST['missing_arm_type'] as $index => $armType) {
                    if (empty($armType)) continue;
                    
                    Database::insert('audit_missing_weapons', [
                        'audit_id' => $auditId,
                        'arm_type' => $armType,
                        'serial_number' => $_POST['missing_serial'][$index] ?? '',
                        'last_known_location' => $_POST['missing_location'][$index] ?? null,
                        'date_missing' => $_POST['missing_date'][$index] ?? null,
                        'reported_by' => $_POST['missing_reported_by'][$index] ?? null,
                        'investigation_status' => $_POST['missing_investigation_status'][$index] ?? 'Reported'
                    ]);
                }
            }
            
            Database::commit();
            
            AuditLogger::logCreate('quarterly_audits', $auditId, $_POST);
            
            $this->redirect('audit/quarterly', ['success' => 'Quarterly audit submitted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Quarterly audit creation error: " . $e->getMessage());
            $this->redirect('audit/quarterly/create', ['error' => 'Failed to submit audit: ' . $e->getMessage()]);
        }
    }
    
    public function showQuarterly($id) {
        // Check permission using can() method
        if (!Auth::can('audit.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view audits']);
            return;
        }
        
        $audit = Database::fetchOne(
            "SELECT qa.*, u.full_name as created_by_name, c.command_name
             FROM quarterly_audits qa
             LEFT JOIN users u ON qa.created_by = u.id
             LEFT JOIN commands c ON qa.command_id = c.id
             WHERE qa.id = ?",
            [$id]
        );
        
        if (!$audit) {
            $this->redirect('audit/quarterly', ['error' => 'Audit not found']);
            return;
        }
        
        $weapons = Database::fetchAll(
            "SELECT aw.*, wi.make_model, wi.serial_no
             FROM audit_weapons aw
             JOIN weapons_inventory wi ON aw.weapon_id = wi.id
             WHERE aw.audit_id = ?",
            [$id]
        );
        if ($weapons === false) $weapons = [];
        
        $ammunition = Database::fetchAll(
            "SELECT aa.*, ai.ammo_id, at.ammo_type, ac.calibre
             FROM audit_ammunition aa
             JOIN ammunition_inventory ai ON aa.ammo_id = ai.id
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             WHERE aa.audit_id = ?",
            [$id]
        );
        if ($ammunition === false) $ammunition = [];
        
        $missing = Database::fetchAll(
            "SELECT * FROM audit_missing_weapons WHERE audit_id = ?",
            [$id]
        );
        if ($missing === false) $missing = [];
        
        AuditLogger::logView('quarterly_audits', $id);
        
        $this->view('audit/quarterly/show', [
            'audit' => $audit,
            'weapons' => $weapons,
            'ammunition' => $ammunition,
            'missing' => $missing
        ]);
    }
    
    public function review($id) {
        if (!Auth::can('audit.review') && !Auth::can('audit.approve') && !Auth::can('audit.edit') && !Auth::can('audit.create')) {
            $this->redirect('audit/quarterly', ['error' => 'You do not have permission to review audits']);
            return;
        }

        $audit = Database::fetchOne("SELECT * FROM quarterly_audits WHERE id = ?", [$id]);
        if (!$audit) {
            $this->redirect('audit/quarterly', ['error' => 'Audit not found']);
            return;
        }

        $recommendingOfficer = Auth::user()['full_name'] ?? 'Authorized Officer';
        Database::update('quarterly_audits', [
            'status' => 'Reviewed',
            'recommending_officer' => $recommendingOfficer,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);

        AuditLogger::logUpdate('quarterly_audits', $id, $audit, ['status' => 'Reviewed']);
        $this->redirect("audit/quarterly/show/$id", ['success' => 'Audit marked as Reviewed successfully']);
    }

    public function approve($id) {
        if (!Auth::can('audit.approve') && !Auth::can('audit.edit')) {
            $this->redirect('audit/quarterly', ['error' => 'You do not have permission to approve audits']);
            return;
        }

        $audit = Database::fetchOne("SELECT * FROM quarterly_audits WHERE id = ?", [$id]);
        if (!$audit) {
            $this->redirect('audit/quarterly', ['error' => 'Audit not found']);
            return;
        }

        $approvingOfficer = Auth::user()['full_name'] ?? 'Commanding Officer';
        Database::update('quarterly_audits', [
            'status' => 'Approved',
            'approving_officer' => $approvingOfficer,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);

        AuditLogger::logUpdate('quarterly_audits', $id, $audit, ['status' => 'Approved']);
        $this->redirect("audit/quarterly/show/$id", ['success' => 'Audit approved successfully']);
    }

    public function deleteQuarterly($id) {
        if (!Auth::can('audit.delete')) {
            $this->redirect('audit/quarterly', ['error' => 'You do not have permission to delete audits']);
            return;
        }

        $audit = Database::fetchOne("SELECT * FROM quarterly_audits WHERE id = ?", [$id]);
        if (!$audit) {
            $this->redirect('audit/quarterly', ['error' => 'Audit not found']);
            return;
        }

        Database::beginTransaction();
        try {
            Database::delete('audit_weapons', 'audit_id = ?', [$id]);
            Database::delete('audit_ammunition', 'audit_id = ?', [$id]);
            Database::delete('audit_missing_weapons', 'audit_id = ?', [$id]);
            Database::delete('quarterly_audits', 'id = ?', [$id]);
            Database::commit();

            AuditLogger::logDelete('quarterly_audits', $id, $audit);
            $this->redirect('audit/quarterly', ['success' => 'Audit deleted successfully']);
        } catch (Exception $e) {
            Database::rollback();
            $this->redirect('audit/quarterly', ['error' => 'Failed to delete audit: ' . $e->getMessage()]);
        }
    }

    public function editQuarterly($id) {
        if (!Auth::can('audit.edit') && !Auth::can('audit.create')) {
            $this->redirect('audit/quarterly', ['error' => 'You do not have permission to edit audits']);
            return;
        }

        $audit = Database::fetchOne("SELECT * FROM quarterly_audits WHERE id = ?", [$id]);
        if (!$audit) {
            $this->redirect('audit/quarterly', ['error' => 'Audit not found']);
            return;
        }

        $this->redirect("audit/quarterly/show/$id");
    }

    public function updateQuarterly($id) {
        if (!Auth::can('audit.edit') && !Auth::can('audit.create')) {
            $this->redirect('audit/quarterly', ['error' => 'You do not have permission to update audits']);
            return;
        }

        $audit = Database::fetchOne("SELECT * FROM quarterly_audits WHERE id = ?", [$id]);
        if (!$audit) {
            $this->redirect('audit/quarterly', ['error' => 'Audit not found']);
            return;
        }

        $updateData = [
            'audit_remarks' => $_POST['audit_remarks'] ?? $audit['audit_remarks'],
            'audit_conclusion' => $_POST['audit_conclusion'] ?? $audit['audit_conclusion'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        Database::update('quarterly_audits', $updateData, 'id = ?', [$id]);
        AuditLogger::logUpdate('quarterly_audits', $id, $audit, $updateData);
        $this->redirect("audit/quarterly/show/$id", ['success' => 'Audit updated successfully']);
    }
    
    public function history() {
        // Check permission using can() method
        if (!Auth::can('audit.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view audit history']);
            return;
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'table_name' => $_GET['table_name'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null
        ];
        
        $logs = AuditLogger::getLogs($filters, $limit, $offset);
        if ($logs === false) $logs = [];
        
        $total = Database::fetchOne("SELECT COUNT(*) as count FROM audit_logs")['count'] ?? 0;
        $totalPages = ceil($total / $limit);
        
        $users = Database::fetchAll("SELECT id, username, full_name FROM users ORDER BY full_name") ?: [];
        $actions = [
            ['action' => 'CREATE'], ['action' => 'UPDATE'], ['action' => 'DELETE'],
            ['action' => 'LOGIN'], ['action' => 'LOGIN_FAILED'], ['action' => 'LOGOUT'],
            ['action' => 'PROCESS'], ['action' => 'EXPORT'], ['action' => 'VIEW']
        ];
        $tables = [
            ['table_name' => 'users'], ['table_name' => 'land_assets'], ['table_name' => 'building_assets'],
            ['table_name' => 'rented_properties'], ['table_name' => 'movable_assets'], ['table_name' => 'ict_assets'],
            ['table_name' => 'vehicle_assets'], ['table_name' => 'weapons_inventory'], ['table_name' => 'ammunition_inventory'],
            ['table_name' => 'requisitions'], ['table_name' => 'returns'], ['table_name' => 'quarterly_audits']
        ];
        
        $this->view('audit/history', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'users' => $users,
            'actions' => $actions,
            'tables' => $tables
        ]);
    }
    
    public function exportHistory() {
        // Check permission using can() method
        if (!Auth::can('reports.export')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to export data']);
            return;
        }
        
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'table_name' => $_GET['table_name'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null
        ];
        
        $logs = AuditLogger::getLogs($filters, 10000, 0);
        if ($logs === false) $logs = [];
        
        $filename = 'audit_history_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        Security::fputcsv($output, ['ID', 'User', 'Action', 'Table', 'Record ID', 'Description', 'IP Address', 'Timestamp']);
        
        foreach ($logs as $log) {
            Security::fputcsv($output, [
                $log['id'] ?? '',
                $log['full_name'] ?? $log['username'] ?? 'System',
                $log['action'] ?? '',
                $log['table_name'] ?? '',
                $log['record_id'] ?? '',
                $log['description'] ?? '',
                $log['ip_address'] ?? '',
                $log['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('audit_history', 'csv');
        exit;
    }
    
    private function generateAuditNumber() {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT audit_number FROM quarterly_audits 
             WHERE audit_number LIKE 'AUD-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($last && isset($last['audit_number'])) {
            $seq = intval(substr($last['audit_number'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("AUD-%s%s-%04d", $year, $month, $seq);
    }
}