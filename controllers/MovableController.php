<?php
/**
 * Movable Assets Controller
 */
class MovableController extends Controller {
    
    public function index() {
        // Check permission
        if (!Auth::can('movable.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view movable assets']);
            return;
        }
        
        // Fetch assets with joins
        $params = [];
        $baseSql = "SELECT ma.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM movable_assets ma
             LEFT JOIN states s ON ma.state_id = s.id
             LEFT JOIN lgas l ON ma.lga_id = l.id
             LEFT JOIN zones z ON ma.zone_id = z.id
             LEFT JOIN commands c ON ma.command_id = c.id
             ORDER BY ma.created_at DESC";
             
        $pagination = paginateTable('movable_assets', 'ma', ['asset_code', 'asset_type', 'make_model', 'serial_number'], $baseSql, $params);
        $assets = Database::fetchAll($pagination['sql'], $params);
        if ($assets === false) $assets = [];
        
        // Get document counts for each asset
        foreach ($assets as &$asset) {
            $docResult = Database::fetchOne(
                "SELECT COUNT(*) as count FROM documents WHERE asset_type = 'movable' AND asset_id = ?",
                [$asset['id']]
            );
            $asset['document_count'] = $docResult['count'] ?? 0;
        }
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(purchase_value) as total_value FROM movable_assets ma", 'ma', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $condParams = [];
        $condSql = Database::applyCommandFilter("SELECT condition_status, COUNT(*) as count FROM movable_assets ma GROUP BY condition_status", 'ma', $condParams);
        $condResults = Database::fetchAll($condSql, $condParams) ?: [];
        $byCondition = [];
        foreach ($condResults as $r) {
            $byCondition[$r['condition_status'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $typeParams = [];
        $typeSql = Database::applyCommandFilter("SELECT asset_type, COUNT(*) as count FROM movable_assets ma GROUP BY asset_type", 'ma', $typeParams);
        $typeResults = Database::fetchAll($typeSql, $typeParams) ?: [];
        $byType = [];
        foreach ($typeResults as $r) {
            $byType[$r['asset_type'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'total_value' => $summary['total_value'] ?? 0,
            'by_condition' => $byCondition,
            'by_type' => $byType
        ];
        
        // Get zones for filter
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        if ($zones === false) $zones = [];
        
        $this->view('movable/index', [
            'assets' => $assets,
            'statistics' => $statistics,
            'zones' => $zones,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function create() {
        // Check permission
        if (!Auth::can('movable.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create movable assets']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $this->view('movable/create', [
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
        if (!Auth::can('movable.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create movable assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('movable/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['asset_type'])) {
            $errors['asset_type'] = 'Asset type is required';
        }
        
        if (empty($_POST['make_model'])) {
            $errors['make_model'] = 'Make/Model is required';
        } elseif (strlen($_POST['make_model']) > 255) {
            $errors['make_model'] = 'Make/Model must not exceed 255 characters';
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
        
        if (empty($_POST['condition_status'])) {
            $errors['condition_status'] = 'Condition status is required';
        }

        if (!empty($_POST['custodian_nis']) && !isDigitsOnly($_POST['custodian_nis'])) {
            $errors['custodian_nis'] = 'NIS number must contain numbers only';
        }

        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('movable/create');
            return;
        }
        
        $assetCode = $this->generateAssetCode('MOV');
        
        Database::beginTransaction();
        
        try {
            // Prepare data based on your actual table columns
            $data = [
                'asset_code' => $assetCode,
                'asset_type' => $_POST['asset_type'],
                'make_model' => $_POST['make_model'],
                'capacity_specification' => $_POST['capacity_specification'] ?? null,
                'serial_number' => $_POST['serial_number'] ?? null,
                'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
                'purchase_value' => !empty($_POST['purchase_value']) ? $_POST['purchase_value'] : null,
                'current_value' => !empty($_POST['current_value']) ? $_POST['current_value'] : (!empty($_POST['purchase_value']) ? $_POST['purchase_value'] : null),
                'condition_status' => $_POST['condition_status'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'current_location' => $_POST['current_location'] ?? null,
                'custodian_name' => $_POST['custodian_name'] ?? null,
                'custodian_rank' => $_POST['custodian_rank'] ?? null,
                'custodian_nis' => $_POST['custodian_nis'] ?? null,
                'warranty_info' => $_POST['warranty_info'] ?? null,
                'maintenance_schedule' => $_POST['maintenance_schedule'] ?? null,
                'last_maintenance_date' => !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null,
                'next_maintenance_date' => !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null,
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ];
            
            $assetId = Database::insert('movable_assets', $data);
            
            if (!$assetId) {
                throw new Exception("Failed to insert movable asset record");
            }
            
            // Handle document uploads - using the same method as LandController
            if (!empty($_FILES['documents']['name'][0])) {
                $this->uploadDocuments($assetId, 'movable', $_FILES['documents']);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('movable_assets', $assetId, $_POST);
            
            $this->redirect('movable', ['success' => 'Movable asset created successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Movable asset creation error: " . $e->getMessage());
            $this->redirect('movable/create', ['error' => 'Failed to create movable asset: ' . $e->getMessage()]);
        }
    }
    
    public function show($id) {
        // Check permission
        if (!Auth::can('movable.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view movable assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter(
            "SELECT ma.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM movable_assets ma
             LEFT JOIN states s ON ma.state_id = s.id
             LEFT JOIN lgas l ON ma.lga_id = l.id
             LEFT JOIN zones z ON ma.zone_id = z.id
             LEFT JOIN commands c ON ma.command_id = c.id
             LEFT JOIN users u ON ma.created_by = u.id
             WHERE ma.id = ?",
            'ma',
            $params
        );
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('movable', ['error' => 'Movable asset not found']);
            return;
        }
        
        // Get documents
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'movable' AND asset_id = ?",
            [$id]
        );
        
        if ($documents === false) $documents = [];
        
        AuditLogger::logView('movable_assets', $id);
        
        $this->view('movable/show', [
            'asset' => $asset,
            'documents' => $documents
        ]);
    }
    
    public function edit($id) {
        // Check permission
        if (!Auth::can('movable.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit movable assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM movable_assets WHERE id = ?", 'movable_assets', $params);
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('movable', ['error' => 'Movable asset not found']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        // Get LGAs for the selected state
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$asset['state_id']]);
        if ($lgas === false) $lgas = [];
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'movable' AND asset_id = ?",
            [$id]
        );
        if ($documents === false) $documents = [];
        
        $this->view('movable/edit', [
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
        if (!Auth::can('movable.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit movable assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("movable/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        if (Auth::isCommandRestricted()) {
            $_POST['command_id'] = Auth::commandId();
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM movable_assets WHERE id = ?", 'movable_assets', $params);
        $oldData = Database::fetchOne($sql, $params);
        
        if (!$oldData) {
            $this->redirect('movable', ['error' => 'Movable asset not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['asset_type'])) {
            $errors['asset_type'] = 'Asset type is required';
        }
        
        if (empty($_POST['make_model'])) {
            $errors['make_model'] = 'Make/Model is required';
        } elseif (strlen($_POST['make_model']) > 255) {
            $errors['make_model'] = 'Make/Model must not exceed 255 characters';
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
        
        if (empty($_POST['condition_status'])) {
            $errors['condition_status'] = 'Condition status is required';
        }

        if (!empty($_POST['custodian_nis']) && !isDigitsOnly($_POST['custodian_nis'])) {
            $errors['custodian_nis'] = 'NIS number must contain numbers only';
        }

        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("movable/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            // Update data
            $data = [
                'asset_type' => $_POST['asset_type'],
                'make_model' => $_POST['make_model'],
                'capacity_specification' => $_POST['capacity_specification'] ?? null,
                'serial_number' => $_POST['serial_number'] ?? null,
                'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
                'purchase_value' => !empty($_POST['purchase_value']) ? $_POST['purchase_value'] : null,
                'current_value' => !empty($_POST['current_value']) ? $_POST['current_value'] : (!empty($_POST['purchase_value']) ? $_POST['purchase_value'] : null),
                'condition_status' => $_POST['condition_status'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'current_location' => $_POST['current_location'] ?? null,
                'custodian_name' => $_POST['custodian_name'] ?? null,
                'custodian_rank' => $_POST['custodian_rank'] ?? null,
                'custodian_nis' => $_POST['custodian_nis'] ?? null,
                'warranty_info' => $_POST['warranty_info'] ?? null,
                'maintenance_schedule' => $_POST['maintenance_schedule'] ?? null,
                'last_maintenance_date' => !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null,
                'next_maintenance_date' => !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null,
                'remarks' => $_POST['remarks'] ?? null
            ];
            
            Database::update('movable_assets', $data, 'id = ?', [$id]);
            
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
            $filesToUpload = null;
            if (!empty($_FILES['new_documents']['name'][0])) {
                $filesToUpload = $_FILES['new_documents'];
            } elseif (!empty($_FILES['documents']['name'][0])) {
                $filesToUpload = $_FILES['documents'];
            }
            
            if ($filesToUpload) {
                $this->uploadDocuments($id, 'movable', $filesToUpload);
            }
            
            Database::commit();
            
            AuditLogger::logUpdate('movable_assets', $id, $oldData, $_POST);
            
            $this->redirect("movable/show/$id", ['success' => 'Movable asset updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Movable asset update error: " . $e->getMessage());
            $this->redirect("movable/edit/$id", ['error' => 'Failed to update movable asset: ' . $e->getMessage()]);
        }
    }
    
    public function delete($id) {
        // Check permission
        if (!Auth::can('movable.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete movable assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM movable_assets WHERE id = ?", 'movable_assets', $params);
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('movable', ['error' => 'Movable asset not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            // Delete associated documents
            $documents = Database::fetchAll(
                "SELECT * FROM documents WHERE asset_type = 'movable' AND asset_id = ?",
                [$id]
            );
            
            if ($documents && is_array($documents)) {
                foreach ($documents as $doc) {
                    if (isset($doc['file_path']) && file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
            }
            
            Database::delete('documents', "asset_type = 'movable' AND asset_id = ?", [$id]);
            Database::delete('movable_assets', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('movable_assets', $id, $asset);
            
            $this->redirect('movable', ['success' => 'Movable asset deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Movable asset deletion error: " . $e->getMessage());
            $this->redirect('movable', ['error' => 'Failed to delete movable asset: ' . $e->getMessage()]);
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
            "SELECT ma.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM movable_assets ma
             LEFT JOIN states s ON ma.state_id = s.id
             LEFT JOIN lgas l ON ma.lga_id = l.id
             LEFT JOIN zones z ON ma.zone_id = z.id
             LEFT JOIN commands c ON ma.command_id = c.id
             ORDER BY ma.created_at DESC",
            'ma',
            $params
        );
        $assets = Database::fetchAll($sql, $params);
        
        if ($assets === false) $assets = [];
        
        $filename = 'movable_assets_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        Security::fputcsv($output, [
            'Asset Code', 'Asset Type', 'Make/Model', 'Serial Number',
            'Capacity/Spec', 'State', 'LGA', 'Zone', 'Command',
            'Location', 'Custodian', 'Rank', 'NIS Number',
            'Purchase Date', 'Purchase Value', 'Current Value',
            'Warranty Info', 'Maintenance Schedule', 'Last Maintenance',
            'Next Maintenance', 'Condition', 'Remarks', 'Created At'
        ]);
        
        // Data
        foreach ($assets as $asset) {
            Security::fputcsv($output, [
                $asset['asset_code'] ?? '',
                $asset['asset_type'] ?? '',
                $asset['make_model'] ?? '',
                $asset['serial_number'] ?? '',
                $asset['capacity_specification'] ?? '',
                $asset['state_name'] ?? '',
                $asset['lga_name'] ?? '',
                $asset['zone_name'] ?? '',
                $asset['command_name'] ?? '',
                $asset['current_location'] ?? '',
                $asset['custodian_name'] ?? '',
                $asset['custodian_rank'] ?? '',
                $asset['custodian_nis'] ?? '',
                $asset['purchase_date'] ?? '',
                $asset['purchase_value'] ?? '',
                $asset['current_value'] ?? '',
                $asset['warranty_info'] ?? '',
                $asset['maintenance_schedule'] ?? '',
                $asset['last_maintenance_date'] ?? '',
                $asset['next_maintenance_date'] ?? '',
                $asset['condition_status'] ?? '',
                $asset['remarks'] ?? '',
                $asset['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('movable', 'csv');
        exit;
    }
    
    private function generateAssetCode($prefix) {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM movable_assets 
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