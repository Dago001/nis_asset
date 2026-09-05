<?php
/**
 * Building Assets Controller
 */
class BuildingController extends Controller {
    
    public function index() {
        // Check permission using can() method instead of requirePermission()
        if (!Auth::can('building.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view building assets']);
            return;
        }
        
        $params = [];
        $baseSql = "SELECT ba.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM building_assets ba
             LEFT JOIN states s ON ba.state_id = s.id
             LEFT JOIN lgas l ON ba.lga_id = l.id
             LEFT JOIN zones z ON ba.zone_id = z.id
             LEFT JOIN commands c ON ba.command_id = c.id
             ORDER BY ba.created_at DESC";
             
        $pagination = paginateTable('building_assets', 'ba', ['asset_code', 'building_name', 'building_type', 'address'], $baseSql, $params);
        $assets = Database::fetchAll($pagination['sql'], $params);
        if ($assets === false) $assets = [];
        
        // Get document counts for all assets in a single query
        if (!empty($assets)) {
            $assetIds = array_column($assets, 'id');
            $placeholders = implode(',', array_fill(0, count($assetIds), '?'));
            $docCounts = Database::fetchAll(
                "SELECT asset_id, COUNT(*) as count FROM documents WHERE asset_type = 'building' AND asset_id IN ($placeholders) GROUP BY asset_id",
                $assetIds
            ) ?: [];
            
            $docMap = [];
            foreach ($docCounts as $dc) {
                $docMap[$dc['asset_id']] = (int)$dc['count'];
            }
            
            foreach ($assets as &$asset) {
                $asset['document_count'] = $docMap[$asset['id']] ?? 0;
            }
        }
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(contract_sum) as total_value FROM building_assets ba", 'ba', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $condParams = [];
        $condSql = Database::applyCommandFilter("SELECT condition_status, COUNT(*) as count FROM building_assets ba GROUP BY condition_status", 'ba', $condParams);
        $condResults = Database::fetchAll($condSql, $condParams) ?: [];
        $byCondition = [];
        foreach ($condResults as $r) {
            $byCondition[$r['condition_status'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'by_condition' => $byCondition,
            'total_value' => $summary['total_value'] ?? 0
        ];
        
        // Get zones for filter dropdown
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        if ($zones === false) $zones = [];
        
        $this->view('buildings/index', [
            'assets' => $assets,
            'statistics' => $statistics,
            'zones' => $zones,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function create() {
        // Check permission using can() method
        if (!Auth::can('building.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create building assets']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        // Get land assets for linking (limited to 50 initially)
        $landAssets = Database::fetchAll("SELECT id, asset_code, address FROM land_assets ORDER BY asset_code ASC LIMIT 50");
        if ($landAssets === false) $landAssets = [];
        
        $this->view('buildings/create', [
            'states' => $states,
            'zones' => $zones,
            'commands' => $commands,
            'landAssets' => $landAssets
        ]);
    }
    
    public function store() {
        if (Auth::isCommandRestricted()) {
            $_POST['command_id'] = Auth::commandId();
        }
        
        // Check permission
        if (!Auth::can('building.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create building assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('buildings/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['building_name'])) {
            $errors['building_name'] = 'Building name is required';
        } elseif (strlen($_POST['building_name']) > 255) {
            $errors['building_name'] = 'Building name must not exceed 255 characters';
        }
        
        if (empty($_POST['building_type'])) {
            $errors['building_type'] = 'Building type is required';
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
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['purpose_function'])) {
            $errors['purpose_function'] = 'Purpose/Function is required';
        }
        
        // ADD THIS - Validate condition_status
        if (empty($_POST['condition_status'])) {
            $errors['condition_status'] = 'Condition status is required';
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('buildings/create');
            return;
        }
        
        $assetCode = $this->generateAssetCode('BLDG');
        
        Database::beginTransaction();
        
        try {
            $buildingId = Database::insert('building_assets', [
                'asset_code' => $assetCode,
                'building_name' => $_POST['building_name'],
                'building_type' => $_POST['building_type'],
                'address' => $_POST['address'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'ownership_type' => $_POST['ownership_type'],
                'purpose_function' => $_POST['purpose_function'],
                'land_id' => !empty($_POST['land_id']) ? $_POST['land_id'] : null,
                'construction_contractor' => !empty($_POST['construction_contractor']) ? $_POST['construction_contractor'] : null,
                'contract_sum' => !empty($_POST['contract_sum']) ? $_POST['contract_sum'] : null,
                'date_awarded' => !empty($_POST['date_awarded']) ? $_POST['date_awarded'] : null,
                'completion_date' => !empty($_POST['completion_date']) ? $_POST['completion_date'] : null,
                'date_occupied' => !empty($_POST['date_occupied']) ? $_POST['date_occupied'] : null,
                'condition_status' => $_POST['condition_status'], // Now this will never be empty
                'last_maintenance_date' => !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null,
                'floor_count' => !empty($_POST['floor_count']) ? $_POST['floor_count'] : null,
                'total_area' => !empty($_POST['total_area']) ? $_POST['total_area'] : null,
                'remarks' => !empty($_POST['remarks']) ? $_POST['remarks'] : null,
                'created_by' => Auth::id()
            ]);
            
            if (!$buildingId) {
                throw new Exception("Failed to insert building record");
            }
            
            // Handle document uploads with types
            if (!empty($_FILES['documents']['name'][0]) && !empty($_POST['document_types'])) {
                $this->uploadDocumentsWithTypes($buildingId, 'building', $_FILES['documents'], $_POST['document_types']);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('building_assets', $buildingId, $_POST);
            
            $this->redirect('buildings', ['success' => 'Building asset created successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Building asset creation error: " . $e->getMessage());
            $this->redirect('buildings/create', ['error' => 'Failed to create building asset: ' . $e->getMessage()]);
        }
    }
    
    public function show($id) {
        // Check permission
        if (!Auth::can('building.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view building assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter(
            "SELECT ba.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    la.asset_code as land_asset_code, u.full_name as created_by_name
             FROM building_assets ba
             LEFT JOIN states s ON ba.state_id = s.id
             LEFT JOIN lgas l ON ba.lga_id = l.id
             LEFT JOIN zones z ON ba.zone_id = z.id
             LEFT JOIN commands c ON ba.command_id = c.id
             LEFT JOIN land_assets la ON ba.land_id = la.id
             LEFT JOIN users u ON ba.created_by = u.id
             WHERE ba.id = ?",
            'ba',
            $params
        );
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('buildings', ['error' => 'Building asset not found']);
            return;
        }
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'building' AND asset_id = ? ORDER BY uploaded_at DESC",
            [$id]
        );
        
        if ($documents === false) $documents = [];
        
        AuditLogger::logView('building_assets', $id);
        
        $this->view('buildings/show', [
            'asset' => $asset,
            'documents' => $documents
        ]);
    }
    
    public function edit($id) {
        // Check permission
        if (!Auth::can('building.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit building assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM building_assets WHERE id = ?", 'building_assets', $params);
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('buildings', ['error' => 'Building asset not found']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        $landAssets = Database::fetchAll("SELECT id, asset_code, address FROM land_assets ORDER BY asset_code ASC LIMIT 50") ?: [];
        
        // Ensure the currently linked land asset is included in the list so it displays properly
        if (!empty($asset['land_id'])) {
            $linkedFound = false;
            foreach ($landAssets as $la) {
                if ($la['id'] == $asset['land_id']) {
                    $linkedFound = true;
                    break;
                }
            }
            if (!$linkedFound) {
                $linkedLand = Database::fetchOne("SELECT id, asset_code, address FROM land_assets WHERE id = ?", [$asset['land_id']]);
                if ($linkedLand) {
                    array_unshift($landAssets, $linkedLand);
                }
            }
        }
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$asset['state_id']]);
        if ($lgas === false) $lgas = [];
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'building' AND asset_id = ? ORDER BY uploaded_at DESC",
            [$id]
        );
        if ($documents === false) $documents = [];
        
        $this->view('buildings/edit', [
            'asset' => $asset,
            'states' => $states,
            'lgas' => $lgas,
            'zones' => $zones,
            'commands' => $commands,
            'landAssets' => $landAssets,
            'documents' => $documents
        ]);
    }
    
    public function update($id) {
        // Check permission
        if (!Auth::can('building.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit building assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("buildings/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        if (Auth::isCommandRestricted()) {
            $_POST['command_id'] = Auth::commandId();
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM building_assets WHERE id = ?", 'building_assets', $params);
        $oldData = Database::fetchOne($sql, $params);
        
        if (!$oldData) {
            $this->redirect('buildings', ['error' => 'Building asset not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['building_name'])) {
            $errors['building_name'] = 'Building name is required';
        } elseif (strlen($_POST['building_name']) > 255) {
            $errors['building_name'] = 'Building name must not exceed 255 characters';
        }
        
        if (empty($_POST['building_type'])) {
            $errors['building_type'] = 'Building type is required';
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
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['purpose_function'])) {
            $errors['purpose_function'] = 'Purpose/Function is required';
        }
        
        if (empty($_POST['condition_status'])) {
            $errors['condition_status'] = 'Condition status is required';
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("buildings/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('building_assets', [
                'building_name' => $_POST['building_name'],
                'building_type' => $_POST['building_type'],
                'address' => $_POST['address'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'ownership_type' => $_POST['ownership_type'],
                'purpose_function' => $_POST['purpose_function'],
                'land_id' => !empty($_POST['land_id']) ? $_POST['land_id'] : null,
                'construction_contractor' => !empty($_POST['construction_contractor']) ? $_POST['construction_contractor'] : null,
                'contract_sum' => !empty($_POST['contract_sum']) ? $_POST['contract_sum'] : null,
                'date_awarded' => !empty($_POST['date_awarded']) ? $_POST['date_awarded'] : null,
                'completion_date' => !empty($_POST['completion_date']) ? $_POST['completion_date'] : null,
                'date_occupied' => !empty($_POST['date_occupied']) ? $_POST['date_occupied'] : null,
                'condition_status' => $_POST['condition_status'],
                'last_maintenance_date' => !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null,
                'floor_count' => !empty($_POST['floor_count']) ? $_POST['floor_count'] : null,
                'total_area' => !empty($_POST['total_area']) ? $_POST['total_area'] : null,
                'remarks' => !empty($_POST['remarks']) ? $_POST['remarks'] : null
            ], 'id = ?', [$id]);
            
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
            $bldFiles = !empty($_FILES['new_documents']['name'][0]) ? $_FILES['new_documents'] : (!empty($_FILES['documents']['name'][0]) ? $_FILES['documents'] : null);
            if ($bldFiles) {
                if (!empty($_POST['document_types'])) {
                    $this->uploadDocumentsWithTypes($id, 'building', $bldFiles, $_POST['document_types']);
                } else {
                    $this->uploadDocuments($id, 'building', $bldFiles);
                }
            }
            
            Database::commit();
            
            AuditLogger::logUpdate('building_assets', $id, $oldData, $_POST);
            
            $this->redirect("buildings/show/$id", ['success' => 'Building asset updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Building asset update error: " . $e->getMessage());
            $this->redirect("buildings/edit/$id", ['error' => 'Failed to update building asset: ' . $e->getMessage()]);
        }
    }
    
    public function delete($id) {
        // Check permission
        if (!Auth::can('building.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete building assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM building_assets WHERE id = ?", 'building_assets', $params);
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('buildings', ['error' => 'Building asset not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $documents = Database::fetchAll(
                "SELECT * FROM documents WHERE asset_type = 'building' AND asset_id = ?",
                [$id]
            );
            
            if ($documents && is_array($documents)) {
                foreach ($documents as $doc) {
                    if (isset($doc['file_path']) && file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
            }
            
            Database::delete('documents', "asset_type = 'building' AND asset_id = ?", [$id]);
            Database::delete('building_assets', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('building_assets', $id, $asset);
            
            $this->redirect('buildings', ['success' => 'Building asset deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Building asset deletion error: " . $e->getMessage());
            $this->redirect('buildings', ['error' => 'Failed to delete building asset: ' . $e->getMessage()]);
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
            "SELECT ba.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM building_assets ba
             LEFT JOIN states s ON ba.state_id = s.id
             LEFT JOIN lgas l ON ba.lga_id = l.id
             LEFT JOIN zones z ON ba.zone_id = z.id
             LEFT JOIN commands c ON ba.command_id = c.id
             ORDER BY ba.created_at DESC",
            'ba',
            $params
        );
        $assets = Database::fetchAll($sql, $params);
        
        if ($assets === false) $assets = [];
        
        $filename = 'building_assets_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        Security::fputcsv($output, [
            'Asset Code', 'Building Name', 'Building Type', 'Address',
            'State', 'LGA', 'Zone', 'Command', 'Ownership Type',
            'Purpose', 'Contractor', 'Contract Sum', 'Date Awarded',
            'Completion Date', 'Date Occupied', 'Condition', 'Floor Count',
            'Total Area', 'Remarks', 'Created At'
        ]);
        
        foreach ($assets as $asset) {
            Security::fputcsv($output, [
                $asset['asset_code'] ?? '',
                $asset['building_name'] ?? '',
                $asset['building_type'] ?? '',
                $asset['address'] ?? '',
                $asset['state_name'] ?? '',
                $asset['lga_name'] ?? '',
                $asset['zone_name'] ?? '',
                $asset['command_name'] ?? '',
                $asset['ownership_type'] ?? '',
                $asset['purpose_function'] ?? '',
                $asset['construction_contractor'] ?? '',
                $asset['contract_sum'] ?? '',
                $asset['date_awarded'] ?? '',
                $asset['completion_date'] ?? '',
                $asset['date_occupied'] ?? '',
                $asset['condition_status'] ?? '',
                $asset['floor_count'] ?? '',
                $asset['total_area'] ?? '',
                $asset['remarks'] ?? '',
                $asset['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('building', 'csv');
        exit;
    }
    
    private function generateAssetCode($prefix) {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM building_assets 
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
    
    private function uploadDocumentsWithTypes($assetId, $type, $files, $documentTypes) {
        $uploadDir = Config::get('upload_path') . $type . '_documents/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = Config::get('allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
        $maxSize = 5 * 1024 * 1024; // 5MB max per file
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) {
                continue;
            }
            
            $fileName = $files['name'][$i];
            $fileTmp = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $documentType = $documentTypes[$i] ?? 'other';
            
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception("File type not allowed: $fileName");
            }
            
            if ($fileSize > $maxSize) {
                throw new Exception("File too large: $fileName (Max: 5MB)");
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
                    'document_type' => $documentType,
                    'uploaded_by' => Auth::id()
                ]);
            }
        }
    }
}