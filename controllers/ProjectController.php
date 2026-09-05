<?php
/**
 * Ongoing Projects Controller
 */
class ProjectController extends Controller {
    
    public function index() {
        // Check permission
        if (!Auth::can('projects.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view projects']);
            return;
        }
        
        // Fetch projects with joins
        $params = [];
        $baseSql = "SELECT p.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM ongoing_projects p
             LEFT JOIN states s ON p.state_id = s.id
             LEFT JOIN lgas l ON p.lga_id = l.id
             LEFT JOIN zones z ON p.zone_id = z.id
             LEFT JOIN commands c ON p.command_id = c.id
             ORDER BY p.created_at DESC";
             
        $pagination = paginateTable('ongoing_projects', 'p', ['project_code', 'project_title', 'contractor'], $baseSql, $params);
        $projects = Database::fetchAll($pagination['sql'], $params);
        if ($projects === false) $projects = [];
        
        // Compute real-time document count for each project
        if (!empty($projects)) {
            $projectIds = array_column($projects, 'id');
            $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
            $docCounts = Database::fetchAll(
                "SELECT asset_id, COUNT(*) as count FROM documents WHERE asset_type = 'project' AND asset_id IN ($placeholders) GROUP BY asset_id",
                $projectIds
            ) ?: [];
            
            $docMap = [];
            foreach ($docCounts as $dc) {
                $docMap[$dc['asset_id']] = (int)$dc['count'];
            }
            
            foreach ($projects as &$project) {
                $project['document_count'] = $docMap[$project['id']] ?? 0;
            }
            unset($project);
        }
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(contract_sum) as total_value FROM ongoing_projects p", 'p', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $todayStr = date('Y-m-d');
        
        // Overdue count
        $overdueParams = [$todayStr];
        $overdueSql = Database::applyCommandFilter("SELECT COUNT(*) as count FROM ongoing_projects p WHERE expected_completion_date < ? AND status NOT IN ('Completed', 'Cancelled')", 'p', $overdueParams);
        $overdueCount = Database::fetchOne($overdueSql, $overdueParams)['count'] ?? 0;
        
        $statusParams = [];
        $statusSql = Database::applyCommandFilter("SELECT status, COUNT(*) as count, SUM(contract_sum) as status_value FROM ongoing_projects p GROUP BY status", 'p', $statusParams);
        $statusResults = Database::fetchAll($statusSql, $statusParams) ?: [];
        $byStatus = [];
        foreach ($statusResults as $r) {
            $status = $r['status'] ?? 'Unknown';
            $byStatus[$status] = [
                'count' => (int)$r['count'],
                'value' => floatval($r['status_value'] ?? 0)
            ];
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'by_status' => $byStatus,
            'total_value' => $summary['total_value'] ?? 0,
            'overdue' => $overdueCount
        ];
        
        // Get zones for filter
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        if ($zones === false) $zones = [];
        
        $this->view('projects/index', [
            'projects' => $projects,
            'statistics' => $statistics,
            'zones' => $zones,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function create() {
        // Check permission
        if (!Auth::can('projects.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create projects']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $this->view('projects/create', [
            'states' => $states,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    public function store() {
        if (Auth::isCommandRestricted()) {
            $_POST['command_id'] = Auth::commandId();
        }
        
        // Check permission
        if (!Auth::can('projects.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create projects']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('projects/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['project_title'])) {
            $errors['project_title'] = 'Project title is required';
        } elseif (strlen($_POST['project_title']) > 255) {
            $errors['project_title'] = 'Project title must not exceed 255 characters';
        }
        
        if (empty($_POST['project_type'])) {
            $errors['project_type'] = 'Project type is required';
        }
        
        if (empty($_POST['state_id']) || !is_numeric($_POST['state_id'])) {
            $errors['state_id'] = 'State is required';
        }
        
        if (empty($_POST['lga_id']) || !is_numeric($_POST['lga_id'])) {
            $errors['lga_id'] = 'LGA is required';
        }
        
        if (empty($_POST['zone_id']) || !is_numeric($_POST['zone_id'])) {
            $errors['zone_id'] = 'Zone is required';
        }
        
        if (empty($_POST['command_id']) || !is_numeric($_POST['command_id'])) {
            $errors['command_id'] = 'Command is required';
        }
        
        if (empty($_POST['contract_sum']) || !is_numeric($_POST['contract_sum'])) {
            $errors['contract_sum'] = 'Contract sum is required and must be a number';
        }
        
        if (empty($_POST['source_funding'])) {
            $errors['source_funding'] = 'Funding source is required';
        }
        
        if (empty($_POST['status'])) {
            $errors['status'] = 'Project status is required';
        }
        
        // Validate dates if provided
        if (!empty($_POST['date_awarded']) && !empty($_POST['expected_completion_date'])) {
            if (strtotime($_POST['expected_completion_date']) < strtotime($_POST['date_awarded'])) {
                $errors['expected_completion_date'] = 'Completion date must be after award date';
            }
        }

        if (!empty($_POST['supervising_officer']) && !isValidName($_POST['supervising_officer'])) {
            $errors['supervising_officer'] = "Supervising officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('projects/create');
            return;
        }
        
        // Generate project code
        $projectCode = $this->generateProjectCode();
        
        Database::beginTransaction();
        
        try {
            $contractSum = (float)($_POST['contract_sum'] ?? 0);
            $amountPaid = (float)($_POST['amount_paid'] ?? 0);
            $balance = max(0, $contractSum - $amountPaid);

            // Prepare data for insertion
            $data = [
                'project_code' => $projectCode,
                'project_title' => $_POST['project_title'],
                'project_type' => $_POST['project_type'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'contractor' => $_POST['contractor'] ?? null,
                'contract_sum' => $contractSum,
                'amount_paid' => $amountPaid,
                'balance' => $balance,
                'date_awarded' => !empty($_POST['date_awarded']) ? $_POST['date_awarded'] : null,
                'expected_completion_date' => !empty($_POST['expected_completion_date']) ? $_POST['expected_completion_date'] : null,
                'physical_progress' => !empty($_POST['physical_progress']) ? $_POST['physical_progress'] : 0,
                'financial_progress' => !empty($_POST['financial_progress']) ? $_POST['financial_progress'] : ($contractSum > 0 ? min(100, round(($amountPaid / $contractSum) * 100, 2)) : 0),
                'source_funding' => $_POST['source_funding'],
                'supervising_officer' => $_POST['supervising_officer'] ?? null,
                'status' => $_POST['status'],
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ];
            
            $projectId = Database::insert('ongoing_projects', $data);
            
            if (!$projectId) {
                throw new Exception("Failed to insert project record");
            }
            
            // Handle document uploads
            if (!empty($_FILES['documents']['name'][0])) {
                $this->uploadDocuments($projectId, 'project', $_FILES['documents']);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('ongoing_projects', $projectId, $_POST);
            
            $this->redirect('projects', ['success' => 'Project created successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Project creation error: " . $e->getMessage());
            $this->redirect('projects/create', ['error' => 'Failed to create project: ' . $e->getMessage()]);
        }
    }
    
    public function show($id) {
        // Check permission
        if (!Auth::can('projects.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view projects']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter(
            "SELECT p.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM ongoing_projects p
             LEFT JOIN states s ON p.state_id = s.id
             LEFT JOIN lgas l ON p.lga_id = l.id
             LEFT JOIN zones z ON p.zone_id = z.id
             LEFT JOIN commands c ON p.command_id = c.id
             LEFT JOIN users u ON p.created_by = u.id
             WHERE p.id = ?",
            'p',
            $params
        );
        $project = Database::fetchOne($sql, $params);
        
        if (!$project) {
            $this->redirect('projects', ['error' => 'Project not found']);
            return;
        }
        
        // Get documents
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'project' AND asset_id = ?",
            [$id]
        );
        
        if ($documents === false) $documents = [];
        
        AuditLogger::logView('ongoing_projects', $id);
        
        $this->view('projects/show', [
            'project' => $project,
            'documents' => $documents
        ]);
    }
    
    public function edit($id) {
        // Check permission
        if (!Auth::can('projects.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit projects']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM ongoing_projects WHERE id = ?", 'ongoing_projects', $params);
        $project = Database::fetchOne($sql, $params);
        
        if (!$project) {
            $this->redirect('projects', ['error' => 'Project not found']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        // Get LGAs for the selected state
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$project['state_id']]);
        if ($lgas === false) $lgas = [];
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'project' AND asset_id = ?",
            [$id]
        );
        if ($documents === false) $documents = [];
        
        $this->view('projects/edit', [
            'project' => $project,
            'states' => $states,
            'lgas' => $lgas,
            'zones' => $zones,
            'commands' => $commands,
            'documents' => $documents
        ]);
    }
    
    public function update($id) {
        // Check permission
        if (!Auth::can('projects.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit projects']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("projects/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        if (Auth::isCommandRestricted()) {
            $_POST['command_id'] = Auth::commandId();
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM ongoing_projects WHERE id = ?", 'ongoing_projects', $params);
        $oldData = Database::fetchOne($sql, $params);
        
        if (!$oldData) {
            $this->redirect('projects', ['error' => 'Project not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['project_title'])) {
            $errors['project_title'] = 'Project title is required';
        } elseif (strlen($_POST['project_title']) > 255) {
            $errors['project_title'] = 'Project title must not exceed 255 characters';
        }
        
        if (empty($_POST['project_type'])) {
            $errors['project_type'] = 'Project type is required';
        }
        
        if (empty($_POST['state_id']) || !is_numeric($_POST['state_id'])) {
            $errors['state_id'] = 'State is required';
        }
        
        if (empty($_POST['lga_id']) || !is_numeric($_POST['lga_id'])) {
            $errors['lga_id'] = 'LGA is required';
        }
        
        if (empty($_POST['zone_id']) || !is_numeric($_POST['zone_id'])) {
            $errors['zone_id'] = 'Zone is required';
        }
        
        if (empty($_POST['command_id']) || !is_numeric($_POST['command_id'])) {
            $errors['command_id'] = 'Command is required';
        }
        
        if (empty($_POST['contract_sum']) || !is_numeric($_POST['contract_sum'])) {
            $errors['contract_sum'] = 'Contract sum is required and must be a number';
        }
        
        if (empty($_POST['source_funding'])) {
            $errors['source_funding'] = 'Funding source is required';
        }
        
        if (empty($_POST['status'])) {
            $errors['status'] = 'Project status is required';
        }
        
        // Validate dates if provided
        if (!empty($_POST['date_awarded']) && !empty($_POST['expected_completion_date'])) {
            if (strtotime($_POST['expected_completion_date']) < strtotime($_POST['date_awarded'])) {
                $errors['expected_completion_date'] = 'Completion date must be after award date';
            }
        }

        if (!empty($_POST['supervising_officer']) && !isValidName($_POST['supervising_officer'])) {
            $errors['supervising_officer'] = "Supervising officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("projects/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $contractSum = (float)($_POST['contract_sum'] ?? 0);
            $amountPaid = (float)($_POST['amount_paid'] ?? 0);
            $balance = max(0, $contractSum - $amountPaid);

            // Prepare update data
            $data = [
                'project_title' => $_POST['project_title'],
                'project_type' => $_POST['project_type'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'contractor' => $_POST['contractor'] ?? null,
                'contract_sum' => $contractSum,
                'amount_paid' => $amountPaid,
                'balance' => $balance,
                'date_awarded' => !empty($_POST['date_awarded']) ? $_POST['date_awarded'] : null,
                'expected_completion_date' => !empty($_POST['expected_completion_date']) ? $_POST['expected_completion_date'] : null,
                'physical_progress' => !empty($_POST['physical_progress']) ? $_POST['physical_progress'] : 0,
                'financial_progress' => !empty($_POST['financial_progress']) ? $_POST['financial_progress'] : ($contractSum > 0 ? min(100, round(($amountPaid / $contractSum) * 100, 2)) : 0),
                'source_funding' => $_POST['source_funding'],
                'supervising_officer' => $_POST['supervising_officer'] ?? null,
                'status' => $_POST['status'],
                'remarks' => $_POST['remarks'] ?? null
            ];
            
            Database::update('ongoing_projects', $data, 'id = ?', [$id]);
            
            // Handle document deletions
            if (!empty($_POST['remove_docs'])) {
                foreach ($_POST['remove_docs'] as $docId) {
                    $doc = Database::fetchOne("SELECT * FROM documents WHERE id = ?", [$docId]);
                    if ($doc && file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                    Database::delete('documents', 'id = ?', [$docId]);
                }
            }
            
            // Handle new document uploads
            if (!empty($_FILES['new_documents']['name'][0])) {
                $this->uploadDocuments($id, 'project', $_FILES['new_documents']);
            }
            if (!empty($_FILES['documents']['name'][0])) {
                $this->uploadDocuments($id, 'project', $_FILES['documents']);
            }
            
            Database::commit();
            
            AuditLogger::logUpdate('ongoing_projects', $id, $oldData, $_POST);
            
            $this->redirect("projects/show/$id", ['success' => 'Project updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Project update error: " . $e->getMessage());
            $this->redirect("projects/edit/$id", ['error' => 'Failed to update project: ' . $e->getMessage()]);
        }
    }
    
    public function delete($id) {
        // Check permission
        if (!Auth::can('projects.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete projects']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM ongoing_projects WHERE id = ?", 'ongoing_projects', $params);
        $project = Database::fetchOne($sql, $params);
        
        if (!$project) {
            $this->redirect('projects', ['error' => 'Project not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            // Delete associated documents
            $documents = Database::fetchAll(
                "SELECT * FROM documents WHERE asset_type = 'project' AND asset_id = ?",
                [$id]
            );
            
            if ($documents && is_array($documents)) {
                foreach ($documents as $doc) {
                    if (isset($doc['file_path']) && file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
            }
            
            Database::delete('documents', "asset_type = 'project' AND asset_id = ?", [$id]);
            Database::delete('ongoing_projects', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('ongoing_projects', $id, $project);
            
            $this->redirect('projects', ['success' => 'Project deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Project deletion error: " . $e->getMessage());
            $this->redirect('projects', ['error' => 'Failed to delete project: ' . $e->getMessage()]);
        }
    }
    
    public function export() {
        // Check permission
        if (!Auth::can('reports.export')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to export data']);
            return;
        }
        
        $params = [];
        $sql = Database::applyCommandFilter(
            "SELECT p.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM ongoing_projects p
             LEFT JOIN states s ON p.state_id = s.id
             LEFT JOIN lgas l ON p.lga_id = l.id
             LEFT JOIN zones z ON p.zone_id = z.id
             LEFT JOIN commands c ON p.command_id = c.id
             ORDER BY p.created_at DESC",
            'p',
            $params
        );
        $projects = Database::fetchAll($sql, $params);
        
        if ($projects === false) $projects = [];
        
        $filename = 'projects_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        Security::fputcsv($output, [
            'Project Code', 'Project Title', 'Project Type', 'State', 'LGA', 'Zone', 'Command',
            'Contractor', 'Contract Sum', 'Date Awarded', 'Expected Completion',
            'Physical Progress %', 'Financial Progress %', 'Funding Source',
            'Supervising Officer', 'Status', 'Remarks', 'Created At'
        ]);
        
        // Data
        foreach ($projects as $project) {
            Security::fputcsv($output, [
                $project['project_code'] ?? '',
                $project['project_title'] ?? '',
                $project['project_type'] ?? '',
                $project['state_name'] ?? '',
                $project['lga_name'] ?? '',
                $project['zone_name'] ?? '',
                $project['command_name'] ?? '',
                $project['contractor'] ?? '',
                $project['contract_sum'] ?? '',
                $project['date_awarded'] ?? '',
                $project['expected_completion_date'] ?? '',
                $project['physical_progress'] ?? '',
                $project['financial_progress'] ?? '',
                $project['source_funding'] ?? '',
                $project['supervising_officer'] ?? '',
                $project['status'] ?? '',
                $project['remarks'] ?? '',
                $project['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('projects', 'csv');
        exit;
    }
    
    private function generateProjectCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT project_code FROM ongoing_projects 
             WHERE project_code LIKE 'PRJ-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($last && isset($last['project_code'])) {
            $parts = explode('-', $last['project_code']);
            $seq = intval(end($parts)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("PRJ-%s%s-%04d", $year, $month, $seq);
    }
    
    private function uploadDocuments($assetId, $type, $files) {
        $uploadDir = Config::get('upload_path') . $type . '_documents/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = Config::get('allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
        $maxSize = Config::get('max_upload_size', 10485760); // 10MB
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $fileName = $files['name'][$i];
            $fileTmp = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception("File type not allowed: $fileName");
            }
            
            if ($fileSize > $maxSize) {
                throw new Exception("File too large: $fileName (Max: 10MB)");
            }
            
            $newFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $fileName);
            $destination = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $destination)) {
                // Store relative path in database
                $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $destination);
                
                Database::insert('documents', [
                    'asset_type' => $type,
                    'asset_id' => $assetId,
                    'file_name' => $fileName,
                    'file_path' => $relativePath,
                    'file_size' => $fileSize,
                    'file_mime' => $files['type'][$i],
                    'uploaded_by' => Auth::id()
                ]);
            }
        }
    }
}