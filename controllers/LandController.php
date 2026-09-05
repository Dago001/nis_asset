<?php
/**
 * Land Assets Controller
 */
class LandController extends Controller {
    
    public function index() {
        // Check permission
        if (!Auth::can('land.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view land assets']);
            return;
        }
        
        // Fetch assets
        $params = [];
        $baseSql = "SELECT la.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM land_assets la
             LEFT JOIN states s ON la.state_id = s.id
             LEFT JOIN lgas l ON la.lga_id = l.id
             LEFT JOIN zones z ON la.zone_id = z.id
             LEFT JOIN commands c ON la.command_id = c.id
             ORDER BY la.created_at DESC";
             
        $pagination = paginateTable('land_assets', 'la', ['asset_code', 'title_holder', 'address', 'survey_plan_no', 'certificate_of_occupancy_no'], $baseSql, $params);
        $assets = Database::fetchAll($pagination['sql'], $params);
        
        // If no assets, pass empty array
        if ($assets === false) {
            $assets = [];
        }
        
        // Compute document count for each land asset
        foreach ($assets as &$asset) {
            $docResult = Database::fetchOne(
                "SELECT COUNT(*) as count FROM documents WHERE asset_type = 'land' AND asset_id = ?",
                [$asset['id']]
            );
            $asset['document_count'] = $docResult['count'] ?? 0;
        }
        unset($asset);
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(size) as total_area FROM land_assets la", 'la', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $statusParams = [];
        $statusSql = Database::applyCommandFilter("SELECT status, COUNT(*) as count FROM land_assets la GROUP BY status", 'la', $statusParams);
        $statusResults = Database::fetchAll($statusSql, $statusParams) ?: [];
        $byStatus = [];
        foreach ($statusResults as $r) {
            $byStatus[$r['status'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'by_status' => $byStatus,
            'total_area' => $summary['total_area'] ?? 0
        ];
        
        // Pass data to view
        $this->view('land/index', [
            'assets' => $assets,
            'statistics' => $statistics,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function create() {
        // Check permission
        if (!Auth::can('land.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create land assets']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        // If no data, pass empty arrays
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $this->view('land/create', [
            'states' => $states,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    public function store() {
        // Check permission
        if (!Auth::can('land.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create land assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('land/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Enforce command isolation
        if (Auth::isCommandRestricted()) {
            $_POST['command_id'] = Auth::commandId();
        } else {
            $this->resolveCustomCommand();
        }
        
        // Manual validation instead of Security::validate()
        $errors = [];
        
        // Validate required fields
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['title_holder'])) {
            $errors['title_holder'] = 'Title holder is required';
        } elseif (strlen($_POST['title_holder']) > 255) {
            $errors['title_holder'] = 'Title holder must not exceed 255 characters';
        }
        
        if (empty($_POST['address'])) {
            $errors['address'] = 'Address is required';
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
        
        if (empty($_POST['size']) || !is_numeric($_POST['size'])) {
            $errors['size'] = 'Size is required and must be a number';
        }
        
        if (empty($_POST['size_unit'])) {
            $errors['size_unit'] = 'Size unit is required';
        }
        
        // If there are validation errors, redirect back
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('land/create');
            return;
        }
        
        // Generate asset code
        $assetCode = $this->generateAssetCode('LAND');
        
        Database::beginTransaction();
        
        try {
            $landId = Database::insert('land_assets', [
                'asset_code' => $assetCode,
                'ownership_type' => $_POST['ownership_type'],
                'title_holder' => $_POST['title_holder'],
                'address' => $_POST['address'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'size' => $_POST['size'],
                'size_unit' => $_POST['size_unit'],
                'survey_plan_no' => $_POST['survey_plan_no'] ?? null,
                'certificate_of_occupancy_no' => $_POST['certificate_occupancy_no'] ?? null,
                'purpose_use' => $_POST['purpose_use'] ?? null,
                'date_acquired' => $_POST['date_acquired'] ?? null,
                'encumbrance' => $_POST['encumbrance'] ?? null,
                'status' => $_POST['status'] ?? null,
                'latitude' => $_POST['latitude'] ?? null,
                'longitude' => $_POST['longitude'] ?? null,
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ]);
            
            // Handle document uploads
            if (!empty($_FILES['documents']['name'][0])) {
                $this->uploadDocuments($landId, 'land', $_FILES['documents']);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('land_assets', $landId, $_POST);
            
            $this->redirect('land', ['success' => 'Land asset created successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Land asset creation error: " . $e->getMessage());
            $this->redirect('land/create', ['error' => 'Failed to create land asset: ' . $e->getMessage()]);
        }
    }
    
    public function show($id) {
        // Check permission
        if (!Auth::can('land.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view land assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter(
            "SELECT la.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM land_assets la
             LEFT JOIN states s ON la.state_id = s.id
             LEFT JOIN lgas l ON la.lga_id = l.id
             LEFT JOIN zones z ON la.zone_id = z.id
             LEFT JOIN commands c ON la.command_id = c.id
             LEFT JOIN users u ON la.created_by = u.id
             WHERE la.id = ?",
            'la',
            $params
        );
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('land', ['error' => 'Land asset not found']);
            return;
        }
        
        // Get documents
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'land' AND asset_id = ?",
            [$id]
        );
        
        if ($documents === false) $documents = [];
        
        AuditLogger::logView('land_assets', $id);
        
        $this->view('land/show', [
            'asset' => $asset,
            'documents' => $documents
        ]);
    }
    
    public function edit($id) {
        // Check permission
        if (!Auth::can('land.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit land assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM land_assets WHERE id = ?", 'land_assets', $params);
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('land', ['error' => 'Land asset not found']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        // Get LGAs for the selected state
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$asset['state_id']]);
        if ($lgas === false) $lgas = [];
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'land' AND asset_id = ?",
            [$id]
        );
        if ($documents === false) $documents = [];
        
        $this->view('land/edit', [
            'asset' => $asset,
            'states' => $states,
            'lgas' => $lgas,
            'zones' => $zones,
            'commands' => $commands,
            'documents' => $documents
        ]);
    }
    
    public function update($id) {
        // Check permission
        if (!Auth::can('land.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit land assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("land/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        if (Auth::isCommandRestricted()) {
            $_POST['command_id'] = Auth::commandId();
        } else {
            $this->resolveCustomCommand();
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM land_assets WHERE id = ?", 'land_assets', $params);
        $oldData = Database::fetchOne($sql, $params);
        
        if (!$oldData) {
            $this->redirect('land', ['error' => 'Land asset not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['title_holder'])) {
            $errors['title_holder'] = 'Title holder is required';
        } elseif (strlen($_POST['title_holder']) > 255) {
            $errors['title_holder'] = 'Title holder must not exceed 255 characters';
        }
        
        if (empty($_POST['address'])) {
            $errors['address'] = 'Address is required';
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
        
        if (empty($_POST['size']) || !is_numeric($_POST['size'])) {
            $errors['size'] = 'Size is required and must be a number';
        }
        
        if (empty($_POST['size_unit'])) {
            $errors['size_unit'] = 'Size unit is required';
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("land/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('land_assets', [
                'ownership_type' => $_POST['ownership_type'],
                'title_holder' => $_POST['title_holder'],
                'address' => $_POST['address'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'size' => $_POST['size'],
                'size_unit' => $_POST['size_unit'],
                'survey_plan_no' => $_POST['survey_plan_no'] ?? null,
                'certificate_of_occupancy_no' => $_POST['certificate_occupancy_no'] ?? null,
                'purpose_use' => $_POST['purpose_use'] ?? null,
                'date_acquired' => $_POST['date_acquired'] ?? null,
                'encumbrance' => $_POST['encumbrance'] ?? null,
                'status' => $_POST['status'] ?? null,
                'latitude' => $_POST['latitude'] ?? null,
                'longitude' => $_POST['longitude'] ?? null,
                'remarks' => $_POST['remarks'] ?? null
            ], 'id = ?', [$id]);
            
            // Handle new document uploads (supports both 'new_documents' and 'documents' form input names)
            if (!empty($_FILES['new_documents']['name'][0])) {
                $this->uploadDocuments($id, 'land', $_FILES['new_documents']);
            }
            if (!empty($_FILES['documents']['name'][0])) {
                $this->uploadDocuments($id, 'land', $_FILES['documents']);
            }
            
            Database::commit();
            
            AuditLogger::logUpdate('land_assets', $id, $oldData, $_POST);
            
            $this->redirect("land/show/$id", ['success' => 'Land asset updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Land asset update error: " . $e->getMessage());
            $this->redirect("land/edit/$id", ['error' => 'Failed to update land asset: ' . $e->getMessage()]);
        }
    }
    
    public function delete($id) {
        // Check permission
        if (!Auth::can('land.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete land assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM land_assets WHERE id = ?", 'land_assets', $params);
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('land', ['error' => 'Land asset not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            // Delete associated documents
            $documents = Database::fetchAll(
                "SELECT * FROM documents WHERE asset_type = 'land' AND asset_id = ?",
                [$id]
            );
            
            if ($documents && is_array($documents)) {
                foreach ($documents as $doc) {
                    if (isset($doc['file_path']) && file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
            }
            
            Database::delete('documents', "asset_type = 'land' AND asset_id = ?", [$id]);
            Database::delete('land_assets', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('land_assets', $id, $asset);
            
            $this->redirect('land', ['success' => 'Land asset deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Land asset deletion error: " . $e->getMessage());
            $this->redirect('land', ['error' => 'Failed to delete land asset: ' . $e->getMessage()]);
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
            "SELECT la.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM land_assets la
             LEFT JOIN states s ON la.state_id = s.id
             LEFT JOIN lgas l ON la.lga_id = l.id
             LEFT JOIN zones z ON la.zone_id = z.id
             LEFT JOIN commands c ON la.command_id = c.id
             ORDER BY la.created_at DESC",
            'la',
            $params
        );
        $assets = Database::fetchAll($sql, $params);
        
        if ($assets === false) $assets = [];
        
        $filename = 'land_assets_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        Security::fputcsv($output, [
            'Asset Code', 'Ownership Type', 'Title Holder', 'Address',
            'State', 'LGA', 'Zone', 'Command', 'Size', 'Unit',
            'Survey Plan No', 'C of O No', 'Purpose', 'Date Acquired',
            'Status', 'Remarks', 'Created At'
        ]);
        
        // Data
        foreach ($assets as $asset) {
            Security::fputcsv($output, [
                $asset['asset_code'] ?? '',
                $asset['ownership_type'] ?? '',
                $asset['title_holder'] ?? '',
                $asset['address'] ?? '',
                $asset['state_name'] ?? '',
                $asset['lga_name'] ?? '',
                $asset['zone_name'] ?? '',
                $asset['command_name'] ?? '',
                $asset['size'] ?? '',
                $asset['size_unit'] ?? '',
                $asset['survey_plan_no'] ?? '',
                $asset['certificate_of_occupancy_no'] ?? '',
                $asset['purpose_use'] ?? '',
                $asset['date_acquired'] ?? '',
                $asset['status'] ?? '',
                $asset['remarks'] ?? '',
                $asset['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('land', 'csv');
        exit;
    }
    
    private function generateAssetCode($prefix) {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM land_assets 
             WHERE asset_code LIKE '{$prefix}-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($last && isset($last['asset_code'])) {
            $parts = explode('-', $last['asset_code']);
            $seq = intval(end($parts)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("%s-%s%s-%04d", $prefix, $year, $month, $seq);
    }
    
    private function uploadDocuments($assetId, $type, $files) {
        $uploadDir = Config::get('upload_path') . $type . '_documents/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = Config::get('allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
        $maxSize = Config::get('max_upload_size', 10485760);
        
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
                throw new Exception("File too large: $fileName");
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

    private function resolveCustomCommand() {
        if (!empty($_POST['command_id']) && $_POST['command_id'] === 'Other' && !empty($_POST['command_other'])) {
            $customCmdName = trim($_POST['command_other']);
            $zoneId = (int)($_POST['zone_id'] ?? 0);
            $existingCmd = Database::fetchOne(
                "SELECT id FROM commands WHERE command_name = ? AND zone_id = ?",
                [$customCmdName, $zoneId]
            );
            if ($existingCmd) {
                $_POST['command_id'] = $existingCmd['id'];
            } else {
                $newCmdId = Database::insert('commands', [
                    'command_name' => $customCmdName,
                    'zone_id' => $zoneId,
                    'command_type' => 'Formation',
                    'state_id' => (int)($_POST['state_id'] ?? 0),
                    'lga_id' => (int)($_POST['lga_id'] ?? 0),
                    'is_active' => 1
                ]);
                if ($newCmdId) {
                    $_POST['command_id'] = $newCmdId;
                }
            }
        }
    }
}