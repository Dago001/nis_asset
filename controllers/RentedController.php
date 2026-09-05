<?php
/**
 * Rented Properties Controller
 */
class RentedController extends Controller {
    
    public function index() {
        // Check permission
        if (!Auth::can('rented.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view rented properties']);
            return;
        }
        
        // Fetch rented properties with joins
        $params = [];
        $baseSql = "SELECT rp.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM rented_properties rp
             LEFT JOIN states s ON rp.state_id = s.id
             LEFT JOIN lgas l ON rp.lga_id = l.id
             LEFT JOIN zones z ON rp.zone_id = z.id
             LEFT JOIN commands c ON rp.command_id = c.id
             ORDER BY rp.created_at DESC";
             
        $pagination = paginateTable('rented_properties', 'rp', ['asset_code', 'property_address', 'owner_lessor_name'], $baseSql, $params);
        $properties = Database::fetchAll($pagination['sql'], $params);
        
        // If no properties, pass empty array
        if ($properties === false) {
            $properties = [];
        }
        
        // Compute real-time document count for each rented property
        if (!empty($properties)) {
            $propIds = array_column($properties, 'id');
            $placeholders = implode(',', array_fill(0, count($propIds), '?'));
            $docCounts = Database::fetchAll(
                "SELECT asset_id, COUNT(*) as count FROM documents WHERE asset_type = 'rented' AND asset_id IN ($placeholders) GROUP BY asset_id",
                $propIds
            ) ?: [];
            
            $docMap = [];
            foreach ($docCounts as $dc) {
                $docMap[$dc['asset_id']] = (int)$dc['count'];
            }
            
            foreach ($properties as &$property) {
                $property['document_count'] = $docMap[$property['id']] ?? 0;
            }
            unset($property);
        }
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(annual_rent) as total_annual_rent FROM rented_properties rp", 'rp', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $today = date('Y-m-d');
        $thirtyDaysLater = date('Y-m-d', strtotime('+30 days'));
        
        // Expired count
        $expParams = [$today];
        $expSql = Database::applyCommandFilter("SELECT COUNT(*) as count FROM rented_properties rp WHERE expiry_date < ?", 'rp', $expParams);
        $expiredCount = Database::fetchOne($expSql, $expParams)['count'] ?? 0;
        
        // Expiring soon count
        $soonParams = [$today, $thirtyDaysLater];
        $soonSql = Database::applyCommandFilter("SELECT COUNT(*) as count FROM rented_properties rp WHERE expiry_date >= ? AND expiry_date <= ?", 'rp', $soonParams);
        $soonCount = Database::fetchOne($soonSql, $soonParams)['count'] ?? 0;
        
        $statusParams = [];
        $statusSql = Database::applyCommandFilter("SELECT status, COUNT(*) as count FROM rented_properties rp GROUP BY status", 'rp', $statusParams);
        $statusResults = Database::fetchAll($statusSql, $statusParams) ?: [];
        $byStatus = [];
        foreach ($statusResults as $r) {
            $byStatus[$r['status'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'total_annual_rent' => $summary['total_annual_rent'] ?? 0,
            'expiring_soon' => $soonCount,
            'expired' => $expiredCount,
            'by_status' => $byStatus
        ];
        
        // Pass data to view
        $this->view('rented/index', [
            'properties' => $properties,
            'statistics' => $statistics,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function create() {
        // Check permission
        if (!Auth::can('rented.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create rented properties']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        // If no data, pass empty arrays
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $this->view('rented/create', [
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
        if (!Auth::can('rented.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create rented properties']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('rented/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['property_address'])) {
            $errors['property_address'] = 'Property address is required';
        }
        
        if (empty($_POST['owner_lessor_name'])) {
            $errors['owner_lessor_name'] = 'Owner/Lessor name is required';
        } elseif (strlen($_POST['owner_lessor_name']) > 255) {
            $errors['owner_lessor_name'] = 'Owner/Lessor name must not exceed 255 characters';
        }
        
        if (empty($_POST['purpose'])) {
            $errors['purpose'] = 'Purpose is required';
        }
        
        if (empty($_POST['start_date'])) {
            $errors['start_date'] = 'Start date is required';
        }
        
        if (empty($_POST['expiry_date'])) {
            $errors['expiry_date'] = 'Expiry date is required';
        }
        
        if (empty($_POST['annual_rent']) || !is_numeric($_POST['annual_rent'])) {
            $errors['annual_rent'] = 'Annual rent is required and must be a number';
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

        if (!empty($_POST['owner_lessor_name']) && !isValidName($_POST['owner_lessor_name'])) {
            $errors['owner_lessor_name'] = "Owner / Lessor name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }

        if (!empty($_POST['owner_phone']) && !isValidPhone($_POST['owner_phone'])) {
            $errors['owner_phone'] = 'Phone number must be exactly 11 digits';
        }

        // Check if expiry date is after start date
        if (!empty($_POST['start_date']) && !empty($_POST['expiry_date'])) {
            if ($_POST['expiry_date'] <= $_POST['start_date']) {
                $errors['expiry_date'] = 'Expiry date must be after start date';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('rented/create');
            return;
        }
        
        // Generate asset code
        $assetCode = $this->generateAssetCode('RNT');
        
        Database::beginTransaction();
        
        try {
            $propertyId = Database::insert('rented_properties', [
                'asset_code' => $assetCode,
                'property_address' => $_POST['property_address'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'owner_lessor_name' => $_POST['owner_lessor_name'],
                'owner_contact' => $_POST['owner_contact'] ?? null,
                'owner_phone' => $_POST['owner_phone'] ?? null,
                'owner_email' => $_POST['owner_email'] ?? null,
                'purpose' => $_POST['purpose'],
                'start_date' => $_POST['start_date'],
                'expiry_date' => $_POST['expiry_date'],
                'annual_rent' => $_POST['annual_rent'],
                'funding_source' => $_POST['funding_source'] ?? null,
                'lease_agreement_ref' => $_POST['lease_agreement_ref'] ?? null,
                'status' => $_POST['status'] ?? 'Active',
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ]);
            
            // Handle document uploads with types
            if (!empty($_FILES['documents']['name'][0]) && !empty($_POST['document_types'])) {
                $this->uploadDocumentsWithTypes($propertyId, 'rented', $_FILES['documents'], $_POST['document_types']);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('rented_properties', $propertyId, $_POST);
            
            $this->redirect('rented', ['success' => 'Rented property created successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Rented property creation error: " . $e->getMessage());
            $this->redirect('rented/create', ['error' => 'Failed to create rented property: ' . $e->getMessage()]);
        }
    }
    
    public function show($id) {
        // Check permission
        if (!Auth::can('rented.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view rented properties']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter(
            "SELECT rp.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM rented_properties rp
             LEFT JOIN states s ON rp.state_id = s.id
             LEFT JOIN lgas l ON rp.lga_id = l.id
             LEFT JOIN zones z ON rp.zone_id = z.id
             LEFT JOIN commands c ON rp.command_id = c.id
             LEFT JOIN users u ON rp.created_by = u.id
             WHERE rp.id = ?",
            'rp',
            $params
        );
        $property = Database::fetchOne($sql, $params);
        
        if (!$property) {
            $this->redirect('rented', ['error' => 'Rented property not found']);
            return;
        }
        
        // Get documents
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'rented' AND asset_id = ?",
            [$id]
        );
        
        if ($documents === false) $documents = [];
        
        AuditLogger::logView('rented_properties', $id);
        
        $this->view('rented/show', [
            'property' => $property,
            'documents' => $documents
        ]);
    }
    
    public function edit($id) {
        // Check permission
        if (!Auth::can('rented.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit rented properties']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM rented_properties WHERE id = ?", 'rented_properties', $params);
        $property = Database::fetchOne($sql, $params);
        
        if (!$property) {
            $this->redirect('rented', ['error' => 'Rented property not found']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        // Get LGAs for the selected state
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$property['state_id']]);
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'rented' AND asset_id = ?",
            [$id]
        );
        
        if ($lgas === false) $lgas = [];
        if ($documents === false) $documents = [];
        
        $this->view('rented/edit', [
            'property' => $property,
            'states' => $states,
            'lgas' => $lgas,
            'zones' => $zones,
            'commands' => $commands,
            'documents' => $documents
        ]);
    }
    
    public function update($id) {
        // Check permission
        if (!Auth::can('rented.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit rented properties']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("rented/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        if (Auth::isCommandRestricted()) {
            $_POST['command_id'] = Auth::commandId();
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM rented_properties WHERE id = ?", 'rented_properties', $params);
        $oldData = Database::fetchOne($sql, $params);
        
        if (!$oldData) {
            $this->redirect('rented', ['error' => 'Rented property not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['property_address'])) {
            $errors['property_address'] = 'Property address is required';
        }
        
        if (empty($_POST['owner_lessor_name'])) {
            $errors['owner_lessor_name'] = 'Owner/Lessor name is required';
        } elseif (strlen($_POST['owner_lessor_name']) > 255) {
            $errors['owner_lessor_name'] = 'Owner/Lessor name must not exceed 255 characters';
        }
        
        if (empty($_POST['purpose'])) {
            $errors['purpose'] = 'Purpose is required';
        }
        
        if (empty($_POST['start_date'])) {
            $errors['start_date'] = 'Start date is required';
        }
        
        if (empty($_POST['expiry_date'])) {
            $errors['expiry_date'] = 'Expiry date is required';
        }
        
        if (empty($_POST['annual_rent']) || !is_numeric($_POST['annual_rent'])) {
            $errors['annual_rent'] = 'Annual rent is required and must be a number';
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

        if (!empty($_POST['owner_lessor_name']) && !isValidName($_POST['owner_lessor_name'])) {
            $errors['owner_lessor_name'] = "Owner / Lessor name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }

        if (!empty($_POST['owner_phone']) && !isValidPhone($_POST['owner_phone'])) {
            $errors['owner_phone'] = 'Phone number must be exactly 11 digits';
        }

        // Check if expiry date is after start date
        if (!empty($_POST['start_date']) && !empty($_POST['expiry_date'])) {
            if ($_POST['expiry_date'] <= $_POST['start_date']) {
                $errors['expiry_date'] = 'Expiry date must be after start date';
            }
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("rented/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('rented_properties', [
                'property_address' => $_POST['property_address'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'owner_lessor_name' => $_POST['owner_lessor_name'],
                'owner_contact' => $_POST['owner_contact'] ?? null,
                'owner_phone' => $_POST['owner_phone'] ?? null,
                'owner_email' => $_POST['owner_email'] ?? null,
                'purpose' => $_POST['purpose'],
                'start_date' => $_POST['start_date'],
                'expiry_date' => $_POST['expiry_date'],
                'annual_rent' => $_POST['annual_rent'],
                'funding_source' => $_POST['funding_source'] ?? null,
                'lease_agreement_ref' => $_POST['lease_agreement_ref'] ?? null,
                'status' => $_POST['status'] ?? 'Active',
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
            
            // Handle new document uploads with types
            $rentedFiles = !empty($_FILES['new_documents']['name'][0]) ? $_FILES['new_documents'] : (!empty($_FILES['documents']['name'][0]) ? $_FILES['documents'] : null);
            if ($rentedFiles) {
                if (!empty($_POST['document_types'])) {
                    $this->uploadDocumentsWithTypes($id, 'rented', $rentedFiles, $_POST['document_types']);
                } else {
                    $this->uploadDocuments($id, 'rented', $rentedFiles);
                }
            }
            
            Database::commit();
            
            AuditLogger::logUpdate('rented_properties', $id, $oldData, $_POST);
            
            $this->redirect("rented/show/$id", ['success' => 'Rented property updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Rented property update error: " . $e->getMessage());
            $this->redirect("rented/edit/$id", ['error' => 'Failed to update rented property: ' . $e->getMessage()]);
        }
    }
    
    public function delete($id) {
        // Check permission
        if (!Auth::can('rented.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete rented properties']);
            return;
        }
        
        $params = [$id];
        $sql = Database::applyCommandFilter("SELECT * FROM rented_properties WHERE id = ?", 'rented_properties', $params);
        $property = Database::fetchOne($sql, $params);
        
        if (!$property) {
            $this->redirect('rented', ['error' => 'Rented property not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            // Delete associated documents
            $documents = Database::fetchAll(
                "SELECT * FROM documents WHERE asset_type = 'rented' AND asset_id = ?",
                [$id]
            );
            
            if ($documents && is_array($documents)) {
                foreach ($documents as $doc) {
                    if (file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
            }
            
            Database::delete('documents', "asset_type = 'rented' AND asset_id = ?", [$id]);
            Database::delete('rented_properties', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('rented_properties', $id, $property);
            
            $this->redirect('rented', ['success' => 'Rented property deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Rented property deletion error: " . $e->getMessage());
            $this->redirect('rented', ['error' => 'Failed to delete rented property']);
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
            "SELECT rp.*, s.state_name, l.lga_name, z.zone_name, c.command_name 
             FROM rented_properties rp
             LEFT JOIN states s ON rp.state_id = s.id
             LEFT JOIN lgas l ON rp.lga_id = l.id
             LEFT JOIN zones z ON rp.zone_id = z.id
             LEFT JOIN commands c ON rp.command_id = c.id
             ORDER BY rp.created_at DESC",
            'rp',
            $params
        );
        $properties = Database::fetchAll($sql, $params);
        
        if ($properties === false) $properties = [];
        
        $filename = 'rented_properties_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        Security::fputcsv($output, [
            'Asset Code', 'Property Address', 'State', 'LGA', 'Zone', 'Command',
            'Owner/Lessor', 'Contact', 'Phone', 'Email', 'Purpose',
            'Start Date', 'Expiry Date', 'Annual Rent', 'Funding Source',
            'Lease Reference', 'Status', 'Remarks', 'Created At'
        ]);
        
        // Data
        foreach ($properties as $property) {
            Security::fputcsv($output, [
                $property['asset_code'] ?? '',
                $property['property_address'] ?? '',
                $property['state_name'] ?? '',
                $property['lga_name'] ?? '',
                $property['zone_name'] ?? '',
                $property['command_name'] ?? '',
                $property['owner_lessor_name'] ?? '',
                $property['owner_contact'] ?? '',
                $property['owner_phone'] ?? '',
                $property['owner_email'] ?? '',
                $property['purpose'] ?? '',
                $property['start_date'] ?? '',
                $property['expiry_date'] ?? '',
                $property['annual_rent'] ?? '',
                $property['funding_source'] ?? '',
                $property['lease_agreement_ref'] ?? '',
                $property['status'] ?? '',
                $property['remarks'] ?? '',
                $property['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('rented', 'csv');
        exit;
    }
    
    private function generateAssetCode($prefix) {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM rented_properties 
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