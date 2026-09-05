<?php
/**
 * ICT Assets Controller
 */
class IctController extends Controller {
    
    public function index() {
        // Check permission
        if (!Auth::can('ict.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view ICT assets']);
            return;
        }
        
        // Fetch assets with joins
        $params = [];
        $baseSql = "SELECT i.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM ict_assets i
             LEFT JOIN states s ON i.state_id = s.id
             LEFT JOIN lgas l ON i.lga_id = l.id
             LEFT JOIN zones z ON i.zone_id = z.id
             LEFT JOIN commands c ON i.command_id = c.id
             ORDER BY i.created_at DESC";
             
        $pagination = paginateTable('ict_assets', 'i', ['asset_code', 'asset_description', 'serial_number'], $baseSql, $params);
        $assets = Database::fetchAll($pagination['sql'], $params);
        if ($assets === false) $assets = [];
        
        // Get document counts for each asset
        foreach ($assets as &$asset) {
            $docResult = Database::fetchOne(
                "SELECT COUNT(*) as count FROM documents WHERE asset_type = 'ict' AND asset_id = ?",
                [$asset['id']]
            );
            $asset['document_count'] = $docResult['count'] ?? 0;
        }
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(purchase_value) as total_value FROM ict_assets i", 'i', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $catParams = [];
        $catSql = Database::applyCommandFilter("SELECT asset_category, COUNT(*) as count FROM ict_assets i GROUP BY asset_category", 'i', $catParams);
        $catResults = Database::fetchAll($catSql, $catParams) ?: [];
        $byCategory = [];
        foreach ($catResults as $r) {
            $byCategory[$r['asset_category'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statusParams = [];
        $statusSql = Database::applyCommandFilter("SELECT current_status, COUNT(*) as count FROM ict_assets i GROUP BY current_status", 'i', $statusParams);
        $statusResults = Database::fetchAll($statusSql, $statusParams) ?: [];
        $byStatus = [];
        foreach ($statusResults as $r) {
            $byStatus[$r['current_status'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'total_value' => $summary['total_value'] ?? 0,
            'by_category' => $byCategory,
            'by_status' => $byStatus
        ];
        
        // Get zones for filter dropdown
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        if ($zones === false) $zones = [];
        
        $this->view('ict/index', [
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
        if (!Auth::can('ict.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create ICT assets']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $this->view('ict/create', [
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
        if (!Auth::can('ict.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create ICT assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('ict/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['asset_description'])) {
            $errors['asset_description'] = 'Asset description is required';
        } elseif (strlen($_POST['asset_description']) > 255) {
            $errors['asset_description'] = 'Asset description must not exceed 255 characters';
        }
        
        if (empty($_POST['asset_category'])) {
            $errors['asset_category'] = 'Asset category is required';
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
        
        if (empty($_POST['current_status'])) {
            $errors['current_status'] = 'Current status is required';
        }

        if (!empty($_POST['responsible_officer']) && !isValidName($_POST['responsible_officer'])) {
            $errors['responsible_officer'] = "Responsible officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('ict/create');
            return;
        }
        
        // Generate asset code if not provided
        $assetCode = !empty($_POST['asset_code']) ? $_POST['asset_code'] : $this->generateAssetCode('ICT');
        
        Database::beginTransaction();
        
        try {
            $assetId = Database::insert('ict_assets', [
                'asset_code' => $assetCode,
                'asset_description' => $_POST['asset_description'],
                'asset_category' => $_POST['asset_category'],
                'manufacturer' => $_POST['manufacturer'] ?? null,
                'model_version' => $_POST['model_version'] ?? null,
                'serial_number' => $_POST['serial_number'] ?? null,
                'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
                'purchase_value' => !empty($_POST['purchase_value']) ? $_POST['purchase_value'] : null,
                'current_value' => !empty($_POST['current_value']) ? $_POST['current_value'] : null,
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'location' => $_POST['location'] ?? null,
                'ownership_type' => $_POST['ownership_type'] ?? null,
                'current_status' => $_POST['current_status'],
                'warranty_period' => $_POST['warranty_period'] ?? null,
                'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
                'maintenance_provider' => $_POST['maintenance_provider'] ?? null,
                'last_service_date' => !empty($_POST['last_service_date']) ? $_POST['last_service_date'] : null,
                'next_service_date' => !empty($_POST['next_service_date']) ? $_POST['next_service_date'] : null,
                'responsible_officer' => $_POST['responsible_officer'] ?? null,
                'ip_address' => $_POST['ip_address'] ?? null,
                'mac_address' => $_POST['mac_address'] ?? null,
                'operating_system' => $_POST['operating_system'] ?? null,
                'software_license' => $_POST['software_license'] ?? null,
                'license_expiry' => !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null,
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ]);
            
            if (!$assetId) {
                throw new Exception("Failed to insert ICT asset record");
            }
            
            // Handle document uploads
            if (!empty($_FILES['documents']['name'][0])) {
                $this->uploadDocuments($assetId, 'ict', $_FILES['documents']);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('ict_assets', $assetId, $_POST);
            
            $this->redirect('ict', ['success' => 'ICT asset created successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("ICT asset creation error: " . $e->getMessage());
            $this->redirect('ict/create', ['error' => 'Failed to create ICT asset: ' . $e->getMessage()]);
        }
    }
    
    public function show($id) {
        // Check permission
        if (!Auth::can('ict.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view ICT assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter(
            "SELECT i.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM ict_assets i
             LEFT JOIN states s ON i.state_id = s.id
             LEFT JOIN lgas l ON i.lga_id = l.id
             LEFT JOIN zones z ON i.zone_id = z.id
             LEFT JOIN commands c ON i.command_id = c.id
             LEFT JOIN users u ON i.created_by = u.id
             WHERE i.id = ?",
            'i',
            $params
        );
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('ict', ['error' => 'ICT asset not found']);
            return;
        }
        
        // Get documents
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'ict' AND asset_id = ?",
            [$id]
        );
        
        if ($documents === false) $documents = [];
        
        AuditLogger::logView('ict_assets', $id);
        
        $this->view('ict/show', [
            'asset' => $asset,
            'documents' => $documents
        ]);
    }
    
    public function edit($id) {
        // Check permission
        if (!Auth::can('ict.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit ICT assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM ict_assets WHERE id = ?", 'ict_assets', $params);
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('ict', ['error' => 'ICT asset not found']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        // Get LGAs for the selected state
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$asset['state_id']]);
        if ($lgas === false) $lgas = [];
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'ict' AND asset_id = ?",
            [$id]
        );
        if ($documents === false) $documents = [];
        
        $this->view('ict/edit', [
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
        if (!Auth::can('ict.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit ICT assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("ict/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        if (Auth::isCommandRestricted()) {
            $_POST['command_id'] = Auth::commandId();
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM ict_assets WHERE id = ?", 'ict_assets', $params);
        $oldData = Database::fetchOne($sql, $params);
        
        if (!$oldData) {
            $this->redirect('ict', ['error' => 'ICT asset not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['asset_description'])) {
            $errors['asset_description'] = 'Asset description is required';
        } elseif (strlen($_POST['asset_description']) > 255) {
            $errors['asset_description'] = 'Asset description must not exceed 255 characters';
        }
        
        if (empty($_POST['asset_category'])) {
            $errors['asset_category'] = 'Asset category is required';
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
        
        if (empty($_POST['current_status'])) {
            $errors['current_status'] = 'Current status is required';
        }

        if (!empty($_POST['responsible_officer']) && !isValidName($_POST['responsible_officer'])) {
            $errors['responsible_officer'] = "Responsible officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("ict/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('ict_assets', [
                'asset_description' => $_POST['asset_description'],
                'asset_category' => $_POST['asset_category'],
                'manufacturer' => $_POST['manufacturer'] ?? null,
                'model_version' => $_POST['model_version'] ?? null,
                'serial_number' => $_POST['serial_number'] ?? null,
                'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
                'purchase_value' => !empty($_POST['purchase_value']) ? $_POST['purchase_value'] : null,
                'current_value' => !empty($_POST['current_value']) ? $_POST['current_value'] : null,
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'location' => $_POST['location'] ?? null,
                'ownership_type' => $_POST['ownership_type'] ?? null,
                'current_status' => $_POST['current_status'],
                'warranty_period' => $_POST['warranty_period'] ?? null,
                'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
                'maintenance_provider' => $_POST['maintenance_provider'] ?? null,
                'last_service_date' => !empty($_POST['last_service_date']) ? $_POST['last_service_date'] : null,
                'next_service_date' => !empty($_POST['next_service_date']) ? $_POST['next_service_date'] : null,
                'responsible_officer' => $_POST['responsible_officer'] ?? null,
                'ip_address' => $_POST['ip_address'] ?? null,
                'mac_address' => $_POST['mac_address'] ?? null,
                'operating_system' => $_POST['operating_system'] ?? null,
                'software_license' => $_POST['software_license'] ?? null,
                'license_expiry' => !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null,
                'remarks' => $_POST['remarks'] ?? null
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
            if (!empty($_FILES['new_documents']['name'][0])) {
                $this->uploadDocuments($id, 'ict', $_FILES['new_documents']);
            }
            if (!empty($_FILES['documents']['name'][0])) {
                $this->uploadDocuments($id, 'ict', $_FILES['documents']);
            }
            
            Database::commit();
            
            AuditLogger::logUpdate('ict_assets', $id, $oldData, $_POST);
            
            $this->redirect("ict/show/$id", ['success' => 'ICT asset updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("ICT asset update error: " . $e->getMessage());
            $this->redirect("ict/edit/$id", ['error' => 'Failed to update ICT asset: ' . $e->getMessage()]);
        }
    }
    
    public function delete($id) {
        // Check permission
        if (!Auth::can('ict.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete ICT assets']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM ict_assets WHERE id = ?", 'ict_assets', $params);
        $asset = Database::fetchOne($sql, $params);
        
        if (!$asset) {
            $this->redirect('ict', ['error' => 'ICT asset not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            // Delete associated documents
            $documents = Database::fetchAll(
                "SELECT * FROM documents WHERE asset_type = 'ict' AND asset_id = ?",
                [$id]
            );
            
            if ($documents && is_array($documents)) {
                foreach ($documents as $doc) {
                    if (isset($doc['file_path']) && file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
            }
            
            Database::delete('documents', "asset_type = 'ict' AND asset_id = ?", [$id]);
            Database::delete('ict_assets', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('ict_assets', $id, $asset);
            
            $this->redirect('ict', ['success' => 'ICT asset deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("ICT asset deletion error: " . $e->getMessage());
            $this->redirect('ict', ['error' => 'Failed to delete ICT asset: ' . $e->getMessage()]);
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
            "SELECT i.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM ict_assets i
             LEFT JOIN states s ON i.state_id = s.id
             LEFT JOIN lgas l ON i.lga_id = l.id
             LEFT JOIN zones z ON i.zone_id = z.id
             LEFT JOIN commands c ON i.command_id = c.id
             ORDER BY i.created_at DESC",
            'i',
            $params
        );
        $assets = Database::fetchAll($sql, $params);
        
        if ($assets === false) $assets = [];
        
        $filename = 'ict_assets_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        Security::fputcsv($output, [
            'Asset Code', 'Description', 'Category', 'Manufacturer', 'Model',
            'Serial Number', 'State', 'LGA', 'Zone', 'Command', 'Location',
            'IP Address', 'MAC Address', 'OS', 'Software License', 'License Expiry',
            'Status', 'Responsible Officer', 'Purchase Date', 'Purchase Value',
            'Current Value', 'Ownership', 'Warranty Period', 'Warranty Expiry',
            'Maintenance Provider', 'Last Service', 'Next Service', 'Remarks',
            'Created At'
        ]);
        
        // Data
        foreach ($assets as $asset) {
            Security::fputcsv($output, [
                $asset['asset_code'] ?? '',
                $asset['asset_description'] ?? '',
                $asset['asset_category'] ?? '',
                $asset['manufacturer'] ?? '',
                $asset['model_version'] ?? '',
                $asset['serial_number'] ?? '',
                $asset['state_name'] ?? '',
                $asset['lga_name'] ?? '',
                $asset['zone_name'] ?? '',
                $asset['command_name'] ?? '',
                $asset['location'] ?? '',
                $asset['ip_address'] ?? '',
                $asset['mac_address'] ?? '',
                $asset['operating_system'] ?? '',
                $asset['software_license'] ?? '',
                $asset['license_expiry'] ?? '',
                $asset['current_status'] ?? '',
                $asset['responsible_officer'] ?? '',
                $asset['purchase_date'] ?? '',
                $asset['purchase_value'] ?? '',
                $asset['current_value'] ?? '',
                $asset['ownership_type'] ?? '',
                $asset['warranty_period'] ?? '',
                $asset['warranty_expiry'] ?? '',
                $asset['maintenance_provider'] ?? '',
                $asset['last_service_date'] ?? '',
                $asset['next_service_date'] ?? '',
                $asset['remarks'] ?? '',
                $asset['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('ict', 'csv');
        exit;
    }
    
    private function generateAssetCode($prefix) {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM ict_assets 
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