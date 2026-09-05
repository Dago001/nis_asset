<?php
/**
 * Fleet Management Controller (Vehicles, Aircraft, Marine, Motorcycles)
 */
class FleetController extends Controller {
    
    // =============================================
    // VEHICLES MANAGEMENT
    // =============================================
    
    public function vehicles() {
        // Check permission
        if (!Auth::can('fleet.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view fleet assets']);
            return;
        }
        
        $params = [];
        $baseSql = "SELECT v.*, s.state_name, l.lga_name, z.zone_name, c.command_name
             FROM vehicle_assets v
             LEFT JOIN states s ON v.state_id = s.id
             LEFT JOIN lgas l ON v.lga_id = l.id
             LEFT JOIN zones z ON v.zone_id = z.id
             LEFT JOIN commands c ON v.command_id = c.id
             ORDER BY v.created_at DESC";
             
        $pagination = paginateTable('vehicle_assets', 'v', ['asset_code', 'vehicle_type', 'registration_number', 'vin_chassis_number'], $baseSql, $params);
        $vehicles = Database::fetchAll($pagination['sql'], $params);
        if ($vehicles === false) $vehicles = [];
        
        // Get document counts for each vehicle
        foreach ($vehicles as &$vehicle) {
            $docResult = Database::fetchOne(
                "SELECT COUNT(*) as count FROM documents WHERE asset_type = 'vehicle' AND asset_id = ?",
                [$vehicle['id']]
            );
            $vehicle['document_count'] = $docResult['count'] ?? 0;
        }
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(purchase_value) as total_value FROM vehicle_assets v", 'v', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $statusParams = [];
        $statsSql = Database::applyCommandFilter("SELECT operational_status, COUNT(*) as count FROM vehicle_assets v GROUP BY operational_status", 'v', $statusParams);
        $statusResults = Database::fetchAll($statsSql, $statusParams) ?: [];
        
        $activeCount = 0;
        $inRepairCount = 0;
        $byStatus = [];
        foreach ($statusResults as $r) {
            $status = $r['operational_status'] ?? 'Unknown';
            $count = (int)$r['count'];
            $byStatus[$status] = $count;
            if ($status === 'Active') {
                $activeCount = $count;
            } elseif ($status === 'In Repair') {
                $inRepairCount = $count;
            }
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'total_value' => $summary['total_value'] ?? 0,
            'active' => $activeCount,
            'in_repair' => $inRepairCount,
            'by_status' => $byStatus
        ];
        
        // Get zones for filter
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        if ($zones === false) $zones = [];
        
        $this->view('fleet/vehicles/index', [
            'vehicles' => $vehicles,
            'statistics' => $statistics,
            'zones' => $zones,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function createVehicle() {
        // Check permission
        if (!Auth::can('fleet.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create fleet assets']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $this->view('fleet/vehicles/create', [
            'states' => $states,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    public function storeVehicle() {
        if (Auth::isCommandRestricted()) { $_POST['command_id'] = Auth::commandId(); }
        // Check permission
        if (!Auth::can('fleet.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create fleet assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('fleet/vehicles/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['use_purpose'])) {
            $errors['use_purpose'] = 'Use purpose is required';
        }
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['vehicle_type'])) {
            $errors['vehicle_type'] = 'Vehicle type is required';
        }
        
        if (empty($_POST['make_manufacturer'])) {
            $errors['make_manufacturer'] = 'Make/Manufacturer is required';
        }
        
        if (empty($_POST['model_year'])) {
            $errors['model_year'] = 'Model year is required';
        }
        
        if (empty($_POST['color'])) {
            $errors['color'] = 'Color is required';
        }
        
        if (empty($_POST['vin_chassis_number'])) {
            $errors['vin_chassis_number'] = 'VIN/Chassis number is required';
        }
        
        if (empty($_POST['engine_number'])) {
            $errors['engine_number'] = 'Engine number is required';
        }
        
        if (empty($_POST['registration_number'])) {
            $errors['registration_number'] = 'Registration number is required';
        }
        
        if (empty($_POST['engine_capacity'])) {
            $errors['engine_capacity'] = 'Engine capacity is required';
        }
        
        if (empty($_POST['fuel_type'])) {
            $errors['fuel_type'] = 'Fuel type is required';
        }
        
        if (empty($_POST['mileage']) || !is_numeric($_POST['mileage'])) {
            $errors['mileage'] = 'Mileage is required and must be a number';
        }
        
        if (empty($_POST['acquisition_type'])) {
            $errors['acquisition_type'] = 'Acquisition type is required';
        }
        
        if (empty($_POST['acquisition_date'])) {
            $errors['acquisition_date'] = 'Acquisition date is required';
        }
        
        if (empty($_POST['purchase_value']) || !is_numeric($_POST['purchase_value'])) {
            $errors['purchase_value'] = 'Purchase value is required and must be a number';
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
        
        if (empty($_POST['operational_status'])) {
            $errors['operational_status'] = 'Operational status is required';
        }
        
        if (empty($_POST['condition'])) {
            $errors['condition'] = 'Condition is required';
        }
        
        if (empty($_POST['insurance_status'])) {
            $errors['insurance_status'] = 'Insurance status is required';
        }

        if (!empty($_POST['assigned_officer']) && !isValidName($_POST['assigned_officer'])) {
            $errors['assigned_officer'] = "Assigned officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('fleet/vehicles/create');
            return;
        }
        
        $assetCode = $this->generateVehicleCode();
        
        Database::beginTransaction();
        
        try {
            $vehicleId = Database::insert('vehicle_assets', [
                'asset_code' => $assetCode,
                'use_purpose' => $_POST['use_purpose'],
                'ownership_type' => $_POST['ownership_type'],
                'vehicle_type' => $_POST['vehicle_type'],
                'make_manufacturer' => $_POST['make_manufacturer'],
                'model_year' => $_POST['model_year'],
                'color' => $_POST['color'],
                'vin_chassis_number' => $_POST['vin_chassis_number'],
                'engine_number' => $_POST['engine_number'],
                'registration_number' => $_POST['registration_number'],
                'engine_capacity' => $_POST['engine_capacity'],
                'fuel_type' => $_POST['fuel_type'],
                'mileage' => $_POST['mileage'],
                'acquisition_type' => $_POST['acquisition_type'],
                'acquisition_date' => $_POST['acquisition_date'],
                'purchase_value' => $_POST['purchase_value'],
                'current_value' => $_POST['purchase_value'], // Initially same as purchase
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'current_location' => $_POST['current_location'] ?? null,
                'assigned_officer' => $_POST['assigned_officer'] ?? null,
                'assigned_rank' => $_POST['assigned_rank'] ?? null,
                'assigned_nis' => $_POST['assigned_nis'] ?? null,
                'operational_status' => $_POST['operational_status'],
                'condition' => $_POST['condition'],
                'insurance_status' => $_POST['insurance_status'],
                'insurance_expiry' => $_POST['insurance_expiry'] ?? null,
                'last_service_date' => $_POST['last_service_date'] ?? null,
                'next_service_date' => $_POST['next_service_date'] ?? null,
                'last_maintenance_cost' => $_POST['last_maintenance_cost'] ?? null,
                'maintenance_vendor' => $_POST['maintenance_vendor'] ?? null,
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ]);
            
            if (!$vehicleId) {
                throw new Exception("Failed to insert vehicle record");
            }
            
            // Handle document uploads
            $vFiles = !empty($_FILES['documents']['name'][0]) ? $_FILES['documents'] : (!empty($_FILES['new_documents']['name'][0]) ? $_FILES['new_documents'] : null);
            $docTypes = !empty($_POST['document_types']) ? $_POST['document_types'] : (!empty($_POST['new_document_types']) ? $_POST['new_document_types'] : []);
            if ($vFiles && !empty($vFiles['name'][0])) {
                $this->uploadDocumentsWithTypes($vehicleId, 'vehicle', $vFiles, $docTypes);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('vehicle_assets', $vehicleId, $_POST);
            
            $this->redirect('fleet/vehicles', ['success' => 'Vehicle added successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Vehicle creation error: " . $e->getMessage());
            $this->redirect('fleet/vehicles/create', ['error' => 'Failed to add vehicle: ' . $e->getMessage()]);
        }
    }
    
    public function showVehicle($id) {
        // Check permission
        if (!Auth::can('fleet.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view fleet assets']);
            return;
        }
        
        $vehicle = Database::fetchOne(
            "SELECT v.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM vehicle_assets v
             LEFT JOIN states s ON v.state_id = s.id
             LEFT JOIN lgas l ON v.lga_id = l.id
             LEFT JOIN zones z ON v.zone_id = z.id
             LEFT JOIN commands c ON v.command_id = c.id
             LEFT JOIN users u ON v.created_by = u.id
             WHERE v.id = ?",
            [$id]
        );
        
        if (!$vehicle) {
            $this->redirect('fleet/vehicles', ['error' => 'Vehicle not found']);
            return;
        }
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'vehicle' AND asset_id = ?",
            [$id]
        );
        
        if ($documents === false) $documents = [];
        
        AuditLogger::logView('vehicle_assets', $id);
        
        $this->view('fleet/vehicles/show', [
            'vehicle' => $vehicle,
            'documents' => $documents
        ]);
    }
    
    public function editVehicle($id) {
        // Check permission
        if (!Auth::can('fleet.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit fleet assets']);
            return;
        }
        
        $vehicle = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM vehicle_assets WHERE id = ?", 'vehicle_assets', $params), $params = [$id]);
        
        if (!$vehicle) {
            $this->redirect('fleet/vehicles', ['error' => 'Vehicle not found']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$vehicle['state_id']]);
        if ($lgas === false) $lgas = [];
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'vehicle' AND asset_id = ?",
            [$id]
        );
        if ($documents === false) $documents = [];
        
        $this->view('fleet/vehicles/edit', [
            'vehicle' => $vehicle,
            'states' => $states,
            'lgas' => $lgas,
            'zones' => $zones,
            'commands' => $commands,
            'documents' => $documents
        ]);
    }
    
    public function updateVehicle($id) {
        if (Auth::isCommandRestricted()) { $_POST['command_id'] = Auth::commandId(); }
        // Check permission
        if (!Auth::can('fleet.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit fleet assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("fleet/vehicles/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $oldData = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM vehicle_assets WHERE id = ?", 'vehicle_assets', $params), $params = [$id]);
        
        if (!$oldData) {
            $this->redirect('fleet/vehicles', ['error' => 'Vehicle not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['use_purpose'])) {
            $errors['use_purpose'] = 'Use purpose is required';
        }
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['vehicle_type'])) {
            $errors['vehicle_type'] = 'Vehicle type is required';
        }
        
        if (empty($_POST['make_manufacturer'])) {
            $errors['make_manufacturer'] = 'Make/Manufacturer is required';
        }
        
        if (empty($_POST['model_year'])) {
            $errors['model_year'] = 'Model year is required';
        }
        
        if (empty($_POST['color'])) {
            $errors['color'] = 'Color is required';
        }
        
        if (empty($_POST['vin_chassis_number'])) {
            $errors['vin_chassis_number'] = 'VIN/Chassis number is required';
        }
        
        if (empty($_POST['engine_number'])) {
            $errors['engine_number'] = 'Engine number is required';
        }
        
        if (empty($_POST['registration_number'])) {
            $errors['registration_number'] = 'Registration number is required';
        }
        
        if (empty($_POST['engine_capacity'])) {
            $errors['engine_capacity'] = 'Engine capacity is required';
        }
        
        if (empty($_POST['fuel_type'])) {
            $errors['fuel_type'] = 'Fuel type is required';
        }
        
        if (empty($_POST['mileage']) || !is_numeric($_POST['mileage'])) {
            $errors['mileage'] = 'Mileage is required and must be a number';
        }
        
        if (empty($_POST['acquisition_type'])) {
            $errors['acquisition_type'] = 'Acquisition type is required';
        }
        
        if (empty($_POST['acquisition_date'])) {
            $errors['acquisition_date'] = 'Acquisition date is required';
        }
        
        if (empty($_POST['purchase_value']) || !is_numeric($_POST['purchase_value'])) {
            $errors['purchase_value'] = 'Purchase value is required and must be a number';
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
        
        if (empty($_POST['operational_status'])) {
            $errors['operational_status'] = 'Operational status is required';
        }
        
        if (empty($_POST['condition'])) {
            $errors['condition'] = 'Condition is required';
        }
        
        if (empty($_POST['insurance_status'])) {
            $errors['insurance_status'] = 'Insurance status is required';
        }

        if (!empty($_POST['assigned_officer']) && !isValidName($_POST['assigned_officer'])) {
            $errors['assigned_officer'] = "Assigned officer name must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("fleet/vehicles/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('vehicle_assets', [
                'use_purpose' => $_POST['use_purpose'],
                'ownership_type' => $_POST['ownership_type'],
                'vehicle_type' => $_POST['vehicle_type'],
                'make_manufacturer' => $_POST['make_manufacturer'],
                'model_year' => $_POST['model_year'],
                'color' => $_POST['color'],
                'vin_chassis_number' => $_POST['vin_chassis_number'],
                'engine_number' => $_POST['engine_number'],
                'registration_number' => $_POST['registration_number'],
                'engine_capacity' => $_POST['engine_capacity'],
                'fuel_type' => $_POST['fuel_type'],
                'mileage' => $_POST['mileage'],
                'acquisition_type' => $_POST['acquisition_type'],
                'acquisition_date' => $_POST['acquisition_date'],
                'purchase_value' => $_POST['purchase_value'],
                'current_value' => $_POST['current_value'] ?? $_POST['purchase_value'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'current_location' => $_POST['current_location'] ?? null,
                'assigned_officer' => $_POST['assigned_officer'] ?? null,
                'assigned_rank' => $_POST['assigned_rank'] ?? null,
                'assigned_nis' => $_POST['assigned_nis'] ?? null,
                'operational_status' => $_POST['operational_status'],
                'condition' => $_POST['condition'],
                'insurance_status' => $_POST['insurance_status'],
                'insurance_expiry' => $_POST['insurance_expiry'] ?? null,
                'last_service_date' => $_POST['last_service_date'] ?? null,
                'next_service_date' => $_POST['next_service_date'] ?? null,
                'last_maintenance_cost' => $_POST['last_maintenance_cost'] ?? null,
                'maintenance_vendor' => $_POST['maintenance_vendor'] ?? null,
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
            $vFiles = !empty($_FILES['new_documents']['name'][0]) ? $_FILES['new_documents'] : (!empty($_FILES['documents']['name'][0]) ? $_FILES['documents'] : null);
            $docTypes = !empty($_POST['new_document_types']) ? $_POST['new_document_types'] : (!empty($_POST['document_types']) ? $_POST['document_types'] : []);
            if ($vFiles && !empty($vFiles['name'][0])) {
                $this->uploadDocumentsWithTypes($id, 'vehicle', $vFiles, $docTypes);
            }
            
            Database::commit();
            
            AuditLogger::logUpdate('vehicle_assets', $id, $oldData, $_POST);
            
            $this->redirect("fleet/vehicles/show/$id", ['success' => 'Vehicle updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Vehicle update error: " . $e->getMessage());
            $this->redirect("fleet/vehicles/edit/$id", ['error' => 'Failed to update vehicle: ' . $e->getMessage()]);
        }
    }
    
    public function deleteVehicle($id) {
        // Check permission
        if (!Auth::can('fleet.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete fleet assets']);
            return;
        }
        
        $params = [];
        $vehicle = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM vehicle_assets WHERE id = ?", 'vehicle_assets', $params), $params = [$id]);
        
        if (!$vehicle) {
            $this->redirect('fleet/vehicles', ['error' => 'Vehicle not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $documents = Database::fetchAll(
                "SELECT * FROM documents WHERE asset_type = 'vehicle' AND asset_id = ?",
                [$id]
            );
            
            if ($documents && is_array($documents)) {
                foreach ($documents as $doc) {
                    if (file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
            }
            
            Database::delete('documents', "asset_type = 'vehicle' AND asset_id = ?", [$id]);
            Database::delete('vehicle_assets', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('vehicle_assets', $id, $vehicle);
            
            $this->redirect('fleet/vehicles', ['success' => 'Vehicle deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Vehicle deletion error: " . $e->getMessage());
            $this->redirect('fleet/vehicles', ['error' => 'Failed to delete vehicle: ' . $e->getMessage()]);
        }
    }
    public function aircraft() {
        // Check permission
        if (!Auth::can('fleet.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view fleet assets']);
            return;
        }
        
        $params = [];
        $baseSql = "SELECT a.*, s.state_name, l.lga_name, z.zone_name, c.command_name
             FROM aircraft_assets a
             LEFT JOIN states s ON a.state_id = s.id
             LEFT JOIN lgas l ON a.lga_id = l.id
             LEFT JOIN zones z ON a.zone_id = z.id
             LEFT JOIN commands c ON a.command_id = c.id
             ORDER BY a.created_at DESC";
             
        $pagination = paginateTable('aircraft_assets', 'a', ['asset_code', 'aircraft_type', 'tail_number'], $baseSql, $params);
        $aircraft = Database::fetchAll($pagination['sql'], $params);
        if ($aircraft === false) $aircraft = [];
        
        // Get document counts for each aircraft
        foreach ($aircraft as &$a) {
            $docResult = Database::fetchOne(
                "SELECT COUNT(*) as count FROM documents WHERE asset_type = 'aircraft' AND asset_id = ?",
                [$a['id']]
            );
            $a['document_count'] = $docResult['count'] ?? 0;
        }
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(capital_value) as total_value, SUM(flight_hours) as total_flight_hours FROM aircraft_assets a", 'a', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $statusParams = [];
        $statusSql = Database::applyCommandFilter("SELECT operational_status, COUNT(*) as count FROM aircraft_assets a GROUP BY operational_status", 'a', $statusParams);
        $statusResults = Database::fetchAll($statusSql, $statusParams) ?: [];
        $byStatus = [
            'Operational' => 0,
            'Maintenance' => 0,
            'Grounded' => 0
        ];
        foreach ($statusResults as $r) {
            $byStatus[$r['operational_status'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'total_value' => $summary['total_value'] ?? 0,
            'total_flight_hours' => $summary['total_flight_hours'] ?? 0,
            'by_status' => $byStatus
        ];
        
        // Get zones for filter
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        if ($zones === false) $zones = [];
        
        $this->view('fleet/aircraft/index', [
            'aircraft' => $aircraft,
            'statistics' => $statistics,
            'zones' => $zones,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function createAircraft() {
        // Check permission
        if (!Auth::can('fleet.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create fleet assets']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $this->view('fleet/aircraft/create', [
            'states' => $states,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    public function storeAircraft() {
        if (!Auth::can('fleet.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create fleet assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('fleet/aircraft/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['use_purpose'])) {
            $errors['use_purpose'] = 'Use purpose is required';
        }
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['aircraft_type'])) {
            $errors['aircraft_type'] = 'Aircraft type is required';
        }
        
        if (empty($_POST['model_manufacturer'])) {
            $errors['model_manufacturer'] = 'Model/Manufacturer is required';
        }
        
        if (empty($_POST['year_manufacture']) || !is_numeric($_POST['year_manufacture'])) {
            $errors['year_manufacture'] = 'Year of manufacture is required and must be a number';
        }
        
        if (empty($_POST['tail_number'])) {
            $errors['tail_number'] = 'Tail number is required';
        }
        
        if (empty($_POST['chassis_serial'])) {
            $errors['chassis_serial'] = 'Chassis serial number is required';
        }
        
        if (empty($_POST['engine_type'])) {
            $errors['engine_type'] = 'Engine type is required';
        }
        
        if (empty($_POST['operational_status'])) {
            $errors['operational_status'] = 'Operational status is required';
        }
        
        if (empty($_POST['acquisition_type'])) {
            $errors['acquisition_type'] = 'Acquisition type is required';
        }
        
        if (empty($_POST['acquisition_date'])) {
            $errors['acquisition_date'] = 'Acquisition date is required';
        }
        
        if (empty($_POST['capital_value']) || !is_numeric($_POST['capital_value'])) {
            $errors['capital_value'] = 'Capital value is required and must be a number';
        }
        
        if (empty($_POST['storage_location'])) {
            $errors['storage_location'] = 'Storage location is required';
        }
        
        if (empty($_POST['assigned_unit_pilot'])) {
            $errors['assigned_unit_pilot'] = 'Assigned unit/pilot is required';
        }
        
        if (empty($_POST['insurance_type'])) {
            $errors['insurance_type'] = 'Insurance type is required';
        }
        
        if (empty($_POST['insurance_status'])) {
            $errors['insurance_status'] = 'Insurance status is required';
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
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('fleet/aircraft/create');
            return;
        }
        
        $assetCode = $this->generateAircraftCode();
        
        Database::beginTransaction();
        
        try {
            $aircraftId = Database::insert('aircraft_assets', [
                'asset_code' => $assetCode,
                'use_purpose' => $_POST['use_purpose'],
                'ownership_type' => $_POST['ownership_type'],
                'aircraft_type' => $_POST['aircraft_type'],
                'model_manufacturer' => $_POST['model_manufacturer'],
                'year_manufacture' => $_POST['year_manufacture'],
                'tail_number' => $_POST['tail_number'],
                'chassis_serial' => $_POST['chassis_serial'],
                'engine_type' => $_POST['engine_type'],
                'flight_hours' => $_POST['flight_hours'] ?? 0,
                'operational_status' => $_POST['operational_status'],
                'acquisition_type' => $_POST['acquisition_type'],
                'acquisition_date' => $_POST['acquisition_date'],
                'capital_value' => $_POST['capital_value'],
                'storage_location' => $_POST['storage_location'],
                'assigned_unit_pilot' => $_POST['assigned_unit_pilot'],
                'insurance_type' => $_POST['insurance_type'],
                'insurance_status' => $_POST['insurance_status'],
                'insurance_expiry' => $_POST['insurance_expiry'] ?? null,
                'last_maintenance' => $_POST['last_maintenance'] ?? null,
                'next_overhaul' => $_POST['next_overhaul'] ?? null,
                'installed_equipment' => $_POST['installed_equipment'] ?? null,
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ]);
            
            if (!$aircraftId) {
                throw new Exception("Failed to insert aircraft record");
            }
            
            // Handle document uploads
            if (!empty($_FILES['documents']['name'][0]) && !empty($_POST['document_types'])) {
                $this->uploadDocumentsWithTypes($aircraftId, 'aircraft', $_FILES['documents'], $_POST['document_types']);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('aircraft_assets', $aircraftId, $_POST);
            
            $this->redirect('fleet/aircraft', ['success' => 'Aircraft added successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Aircraft creation error: " . $e->getMessage());
            $this->redirect('fleet/aircraft/create', ['error' => 'Failed to add aircraft: ' . $e->getMessage()]);
        }
    }
    
    public function showAircraft($id) {
        // Check permission
        if (!Auth::can('fleet.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view fleet assets']);
            return;
        }
        
        $aircraft = Database::fetchOne(
            "SELECT a.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM aircraft_assets a
             LEFT JOIN states s ON a.state_id = s.id
             LEFT JOIN lgas l ON a.lga_id = l.id
             LEFT JOIN zones z ON a.zone_id = z.id
             LEFT JOIN commands c ON a.command_id = c.id
             LEFT JOIN users u ON a.created_by = u.id
             WHERE a.id = ?",
            [$id]
        );
        
        if (!$aircraft) {
            $this->redirect('fleet/aircraft', ['error' => 'Aircraft not found']);
            return;
        }
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'aircraft' AND asset_id = ?",
            [$id]
        );
        
        if ($documents === false) $documents = [];
        
        AuditLogger::logView('aircraft_assets', $id);
        
        $this->view('fleet/aircraft/show', [
            'aircraft' => $aircraft,
            'documents' => $documents
        ]);
    }
    
    public function editAircraft($id) {
        // Check permission
        if (!Auth::can('fleet.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit fleet assets']);
            return;
        }
        
        $aircraft = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM aircraft_assets WHERE id = ?", 'aircraft_assets', $params), $params = [$id]);
        
        if (!$aircraft) {
            $this->redirect('fleet/aircraft', ['error' => 'Aircraft not found']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$aircraft['state_id']]);
        if ($lgas === false) $lgas = [];
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'aircraft' AND asset_id = ?",
            [$id]
        );
        if ($documents === false) $documents = [];
        
        $this->view('fleet/aircraft/edit', [
            'aircraft' => $aircraft,
            'states' => $states,
            'lgas' => $lgas,
            'zones' => $zones,
            'commands' => $commands,
            'documents' => $documents
        ]);
    }
    
    public function updateAircraft($id) {
        if (Auth::isCommandRestricted()) { $_POST['command_id'] = Auth::commandId(); }
        // Check permission
        if (!Auth::can('fleet.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit fleet assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("fleet/aircraft/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $oldData = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM aircraft_assets WHERE id = ?", 'aircraft_assets', $params), $params = [$id]);
        
        if (!$oldData) {
            $this->redirect('fleet/aircraft', ['error' => 'Aircraft not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['use_purpose'])) {
            $errors['use_purpose'] = 'Use purpose is required';
        }
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['aircraft_type'])) {
            $errors['aircraft_type'] = 'Aircraft type is required';
        }
        
        if (empty($_POST['model_manufacturer'])) {
            $errors['model_manufacturer'] = 'Model/Manufacturer is required';
        }
        
        if (empty($_POST['year_manufacture']) || !is_numeric($_POST['year_manufacture'])) {
            $errors['year_manufacture'] = 'Year of manufacture is required and must be a number';
        }
        
        if (empty($_POST['tail_number'])) {
            $errors['tail_number'] = 'Tail number is required';
        }
        
        if (empty($_POST['chassis_serial'])) {
            $errors['chassis_serial'] = 'Chassis serial number is required';
        }
        
        if (empty($_POST['engine_type'])) {
            $errors['engine_type'] = 'Engine type is required';
        }
        
        if (empty($_POST['operational_status'])) {
            $errors['operational_status'] = 'Operational status is required';
        }
        
        if (empty($_POST['acquisition_type'])) {
            $errors['acquisition_type'] = 'Acquisition type is required';
        }
        
        if (empty($_POST['acquisition_date'])) {
            $errors['acquisition_date'] = 'Acquisition date is required';
        }
        
        if (empty($_POST['capital_value']) || !is_numeric($_POST['capital_value'])) {
            $errors['capital_value'] = 'Capital value is required and must be a number';
        }
        
        if (empty($_POST['storage_location'])) {
            $errors['storage_location'] = 'Storage location is required';
        }
        
        if (empty($_POST['assigned_unit_pilot'])) {
            $errors['assigned_unit_pilot'] = 'Assigned unit/pilot is required';
        }
        
        if (empty($_POST['insurance_type'])) {
            $errors['insurance_type'] = 'Insurance type is required';
        }
        
        if (empty($_POST['insurance_status'])) {
            $errors['insurance_status'] = 'Insurance status is required';
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
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("fleet/aircraft/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('aircraft_assets', [
                'use_purpose' => $_POST['use_purpose'],
                'ownership_type' => $_POST['ownership_type'],
                'aircraft_type' => $_POST['aircraft_type'],
                'model_manufacturer' => $_POST['model_manufacturer'],
                'year_manufacture' => $_POST['year_manufacture'],
                'tail_number' => $_POST['tail_number'],
                'chassis_serial' => $_POST['chassis_serial'],
                'engine_type' => $_POST['engine_type'],
                'flight_hours' => $_POST['flight_hours'] ?? 0,
                'operational_status' => $_POST['operational_status'],
                'acquisition_type' => $_POST['acquisition_type'],
                'acquisition_date' => $_POST['acquisition_date'],
                'capital_value' => $_POST['capital_value'],
                'storage_location' => $_POST['storage_location'],
                'assigned_unit_pilot' => $_POST['assigned_unit_pilot'],
                'insurance_type' => $_POST['insurance_type'],
                'insurance_status' => $_POST['insurance_status'],
                'insurance_expiry' => $_POST['insurance_expiry'] ?? null,
                'last_maintenance' => $_POST['last_maintenance'] ?? null,
                'next_overhaul' => $_POST['next_overhaul'] ?? null,
                'installed_equipment' => $_POST['installed_equipment'] ?? null,
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
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
            $aFiles = !empty($_FILES['new_documents']['name'][0]) ? $_FILES['new_documents'] : (!empty($_FILES['documents']['name'][0]) ? $_FILES['documents'] : null);
            $docTypes = !empty($_POST['new_document_types']) ? $_POST['new_document_types'] : (!empty($_POST['document_types']) ? $_POST['document_types'] : []);
            if ($aFiles && !empty($aFiles['name'][0])) {
                $this->uploadDocumentsWithTypes($id, 'aircraft', $aFiles, $docTypes);
            }
            
            Database::commit();
            
            AuditLogger::logUpdate('aircraft_assets', $id, $oldData, $_POST);
            
            $this->redirect("fleet/aircraft/show/$id", ['success' => 'Aircraft updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Aircraft update error: " . $e->getMessage());
            $this->redirect("fleet/aircraft/edit/$id", ['error' => 'Failed to update aircraft: ' . $e->getMessage()]);
        }
    }
    
    public function deleteAircraft($id) {
        // Check permission
        if (!Auth::can('fleet.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete fleet assets']);
            return;
        }
        
        $aircraft = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM aircraft_assets WHERE id = ?", 'aircraft_assets', $params), $params = [$id]);
        
        if (!$aircraft) {
            $this->redirect('fleet/aircraft', ['error' => 'Aircraft not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $documents = Database::fetchAll(
                "SELECT * FROM documents WHERE asset_type = 'aircraft' AND asset_id = ?",
                [$id]
            );
            
            if ($documents && is_array($documents)) {
                foreach ($documents as $doc) {
                    if (file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
            }
            
            Database::delete('documents', "asset_type = 'aircraft' AND asset_id = ?", [$id]);
            Database::delete('aircraft_assets', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('aircraft_assets', $id, $aircraft);
            
            $this->redirect('fleet/aircraft', ['success' => 'Aircraft deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Aircraft deletion error: " . $e->getMessage());
            $this->redirect('fleet/aircraft', ['error' => 'Failed to delete aircraft: ' . $e->getMessage()]);
        }
    }
    
    // =============================================
    // MARINE ASSETS MANAGEMENT
    // =============================================
    
    public function marine() {
        // Check permission
        if (!Auth::can('fleet.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view fleet assets']);
            return;
        }
        
        $params = [];
        $baseSql = "SELECT m.*, s.state_name, l.lga_name, z.zone_name, c.command_name
             FROM marine_assets m
             LEFT JOIN states s ON m.state_id = s.id
             LEFT JOIN lgas l ON m.lga_id = l.id
             LEFT JOIN zones z ON m.zone_id = z.id
             LEFT JOIN commands c ON m.command_id = c.id
             ORDER BY m.created_at DESC";
             
        $pagination = paginateTable('marine_assets', 'm', ['asset_code', 'boat_type', 'registration_number'], $baseSql, $params);
        $marine = Database::fetchAll($pagination['sql'], $params);
        if ($marine === false) $marine = [];
        
        // Get document counts for each marine asset
        foreach ($marine as &$m) {
            $docResult = Database::fetchOne(
                "SELECT COUNT(*) as count FROM documents WHERE asset_type = 'marine' AND asset_id = ?",
                [$m['id']]
            );
            $m['document_count'] = $docResult['count'] ?? 0;
        }
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(capital_value) as total_value FROM marine_assets m", 'm', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $typeParams = [];
        $typeSql = Database::applyCommandFilter("SELECT boat_type, COUNT(*) as count FROM marine_assets m GROUP BY boat_type", 'm', $typeParams);
        $typeResults = Database::fetchAll($typeSql, $typeParams) ?: [];
        $byType = [];
        foreach ($typeResults as $r) {
            $byType[$r['boat_type'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statusParams = [];
        $statusSql = Database::applyCommandFilter("SELECT operational_status, COUNT(*) as count FROM marine_assets m GROUP BY operational_status", 'm', $statusParams);
        $statusResults = Database::fetchAll($statusSql, $statusParams) ?: [];
        $byStatus = [];
        foreach ($statusResults as $r) {
            $byStatus[$r['operational_status'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'total_value' => $summary['total_value'] ?? 0,
            'by_type' => $byType,
            'by_status' => $byStatus
        ];
        
        // Get zones for filter
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        if ($zones === false) $zones = [];
        
        $this->view('fleet/marine/index', [
            'marine' => $marine,
            'statistics' => $statistics,
            'zones' => $zones,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function createMarine() {
        // Check permission
        if (!Auth::can('fleet.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create fleet assets']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $this->view('fleet/marine/create', [
            'states' => $states,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    public function storeMarine() {
        if (Auth::isCommandRestricted()) { $_POST['command_id'] = Auth::commandId(); }
        // Check permission
        if (!Auth::can('fleet.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create fleet assets']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('fleet/marine/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['use_purpose'])) {
            $errors['use_purpose'] = 'Use purpose is required';
        }
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['boat_type'])) {
            $errors['boat_type'] = 'Boat type is required';
        }
        
        if (empty($_POST['hull_identification'])) {
            $errors['hull_identification'] = 'Hull identification is required';
        }
        
        if (empty($_POST['engine_type'])) {
            $errors['engine_type'] = 'Engine type is required';
        }
        
        if (empty($_POST['engine_capacity'])) {
            $errors['engine_capacity'] = 'Engine capacity is required';
        }
        
        if (empty($_POST['number_engines']) || !is_numeric($_POST['number_engines'])) {
            $errors['number_engines'] = 'Number of engines is required and must be a number';
        }
        
        if (empty($_POST['registration_number'])) {
            $errors['registration_number'] = 'Registration number is required';
        }
        
        if (empty($_POST['fuel_type'])) {
            $errors['fuel_type'] = 'Fuel type is required';
        }
        
        if (empty($_POST['acquisition_type'])) {
            $errors['acquisition_type'] = 'Acquisition type is required';
        }
        
        if (empty($_POST['acquisition_date'])) {
            $errors['acquisition_date'] = 'Acquisition date is required';
        }
        
        if (empty($_POST['capital_value']) || !is_numeric($_POST['capital_value'])) {
            $errors['capital_value'] = 'Capital value is required and must be a number';
        }
        
        if (empty($_POST['docking_location'])) {
            $errors['docking_location'] = 'Docking location is required';
        }
        
        if (empty($_POST['assigned_crew_unit'])) {
            $errors['assigned_crew_unit'] = 'Assigned crew/unit is required';
        }
        
        if (empty($_POST['operational_status'])) {
            $errors['operational_status'] = 'Operational status is required';
        }
        
        if (empty($_POST['condition'])) {
            $errors['condition'] = 'Condition is required';
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
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('fleet/marine/create');
            return;
        }
        
        $assetCode = $this->generateMarineCode();
        
        Database::beginTransaction();
        
        try {
            $marineId = Database::insert('marine_assets', [
                'asset_code' => $assetCode,
                'use_purpose' => $_POST['use_purpose'],
                'ownership_type' => $_POST['ownership_type'],
                'vessel_name' => $_POST['vessel_name'] ?? null,
                'boat_type' => $_POST['boat_type'],
                'hull_identification' => $_POST['hull_identification'],
                'engine_type' => $_POST['engine_type'],
                'engine_capacity' => $_POST['engine_capacity'],
                'number_engines' => $_POST['number_engines'],
                'registration_number' => $_POST['registration_number'],
                'fuel_type' => $_POST['fuel_type'],
                'acquisition_type' => $_POST['acquisition_type'],
                'acquisition_date' => $_POST['acquisition_date'],
                'capital_value' => $_POST['capital_value'],
                'docking_location' => $_POST['docking_location'],
                'assigned_crew_unit' => $_POST['assigned_crew_unit'],
                'operational_status' => $_POST['operational_status'],
                'condition' => $_POST['condition'],
                'last_dry_docking' => $_POST['last_dry_docking'] ?? null,
                'next_dry_docking' => $_POST['next_dry_docking'] ?? null,
                'onboard_equipment' => $_POST['onboard_equipment'] ?? null,
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ]);
            
            if (!$marineId) {
                throw new Exception("Failed to insert marine asset record");
            }
            
            // Handle document uploads
            if (!empty($_FILES['documents']['name'][0]) && !empty($_POST['document_types'])) {
                $this->uploadDocumentsWithTypes($marineId, 'marine', $_FILES['documents'], $_POST['document_types']);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('marine_assets', $marineId, $_POST);
            
            $this->redirect('fleet/marine', ['success' => 'Marine asset added successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Marine asset creation error: " . $e->getMessage());
            $this->redirect('fleet/marine/create', ['error' => 'Failed to add marine asset: ' . $e->getMessage()]);
        }
    }

    public function showMarine($id) {
        if (!Auth::can('fleet.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view fleet assets']);
            return;
        }

        $marine = Database::fetchOne(
            "SELECT m.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM marine_assets m
             LEFT JOIN states s ON m.state_id = s.id
             LEFT JOIN lgas l ON m.lga_id = l.id
             LEFT JOIN zones z ON m.zone_id = z.id
             LEFT JOIN commands c ON m.command_id = c.id
             LEFT JOIN users u ON m.created_by = u.id
             WHERE m.id = ?",
            [$id]
        );

        if (!$marine) {
            $this->redirect('fleet/marine', ['error' => 'Marine asset not found']);
            return;
        }

        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'marine' AND asset_id = ?",
            [$id]
        );

        if ($documents === false) $documents = [];

        AuditLogger::logView('marine_assets', $id);

        $this->view('fleet/marine/show', [
            'marine' => $marine,
            'documents' => $documents
        ]);
    }

    public function editMarine($id) {
        if (!Auth::can('fleet.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit fleet assets']);
            return;
        }

        $params = [$id];
        $marine = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM marine_assets WHERE id = ?", 'marine_assets', $params), $params);

        if (!$marine) {
            $this->redirect('fleet/marine', ['error' => 'Marine asset not found']);
            return;
        }

        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name") ?: [];
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name") ?: [];
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name") ?: [];
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$marine['state_id']]) ?: [];

        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'marine' AND asset_id = ?",
            [$id]
        ) ?: [];

        $this->view('fleet/marine/edit', [
            'marine' => $marine,
            'states' => $states,
            'lgas' => $lgas,
            'zones' => $zones,
            'commands' => $commands,
            'documents' => $documents
        ]);
    }

    public function updateMarine($id) {
        if (Auth::isCommandRestricted()) { $_POST['command_id'] = Auth::commandId(); }
        if (!Auth::can('fleet.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit fleet assets']);
            return;
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("fleet/marine/edit/$id", ['error' => 'Invalid security token']);
            return;
        }

        $params = [$id];
        $oldData = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM marine_assets WHERE id = ?", 'marine_assets', $params), $params);

        if (!$oldData) {
            $this->redirect('fleet/marine', ['error' => 'Marine asset not found']);
            return;
        }

        Database::beginTransaction();

        try {
            Database::update('marine_assets', [
                'use_purpose' => $_POST['use_purpose'] ?? $oldData['use_purpose'],
                'ownership_type' => $_POST['ownership_type'] ?? $oldData['ownership_type'],
                'boat_type' => $_POST['boat_type'] ?? $oldData['boat_type'],
                'vessel_name' => $_POST['vessel_name'] ?? ($oldData['vessel_name'] ?? null),
                'hull_identification' => $_POST['hull_identification'] ?? ($oldData['hull_identification'] ?? null),
                'engine_type' => $_POST['engine_type'] ?? ($oldData['engine_type'] ?? null),
                'engine_capacity' => $_POST['engine_capacity'] ?? ($oldData['engine_capacity'] ?? null),
                'number_engines' => $_POST['number_engines'] ?? ($oldData['number_engines'] ?? null),
                'registration_number' => $_POST['registration_number'] ?? ($oldData['registration_number'] ?? null),
                'fuel_type' => $_POST['fuel_type'] ?? ($oldData['fuel_type'] ?? null),
                'acquisition_type' => $_POST['acquisition_type'] ?? ($oldData['acquisition_type'] ?? null),
                'acquisition_date' => $_POST['acquisition_date'] ?? ($oldData['acquisition_date'] ?? null),
                'capital_value' => $_POST['capital_value'] ?? ($oldData['capital_value'] ?? null),
                'docking_location' => $_POST['docking_location'] ?? ($oldData['docking_location'] ?? null),
                'assigned_crew_unit' => $_POST['assigned_crew_unit'] ?? ($oldData['assigned_crew_unit'] ?? null),
                'operational_status' => $_POST['operational_status'] ?? ($oldData['operational_status'] ?? null),
                'condition' => $_POST['condition'] ?? ($oldData['condition'] ?? null),
                'last_dry_docking' => $_POST['last_dry_docking'] ?? null,
                'next_dry_docking' => $_POST['next_dry_docking'] ?? null,
                'onboard_equipment' => $_POST['onboard_equipment'] ?? null,
                'state_id' => $_POST['state_id'] ?? $oldData['state_id'],
                'lga_id' => $_POST['lga_id'] ?? $oldData['lga_id'],
                'zone_id' => $_POST['zone_id'] ?? $oldData['zone_id'],
                'command_id' => $_POST['command_id'] ?? $oldData['command_id'],
                'remarks' => $_POST['remarks'] ?? null
            ], 'id = ?', [$id]);

            if (!empty($_POST['remove_docs'])) {
                foreach ($_POST['remove_docs'] as $docId) {
                    $doc = Database::fetchOne("SELECT * FROM documents WHERE id = ?", [$docId]);
                    if ($doc && file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                    Database::delete('documents', 'id = ?', [$docId]);
                }
            }

            $mFiles = !empty($_FILES['new_documents']['name'][0]) ? $_FILES['new_documents'] : (!empty($_FILES['documents']['name'][0]) ? $_FILES['documents'] : null);
            $docTypes = !empty($_POST['new_document_types']) ? $_POST['new_document_types'] : (!empty($_POST['document_types']) ? $_POST['document_types'] : []);
            if ($mFiles && !empty($mFiles['name'][0])) {
                $this->uploadDocumentsWithTypes($id, 'marine', $mFiles, $docTypes);
            }

            Database::commit();

            AuditLogger::logUpdate('marine_assets', $id, $oldData, $_POST);

            $this->redirect("fleet/marine/show/$id", ['success' => 'Marine asset updated successfully']);

        } catch (Exception $e) {
            Database::rollBack();
            error_log("Marine asset update error: " . $e->getMessage());
            $this->redirect("fleet/marine/edit/$id", ['error' => 'Failed to update marine asset: ' . $e->getMessage()]);
        }
    }

    public function deleteMarine($id) {
        if (!Auth::can('fleet.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete marine assets']);
            return;
        }

        $params = [$id];
        $marine = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM marine_assets WHERE id = ?", 'marine_assets', $params), $params);

        if (!$marine) {
            $this->redirect('fleet/marine', ['error' => 'Marine asset not found']);
            return;
        }

        Database::beginTransaction();

        try {
            $docs = Database::fetchAll("SELECT * FROM documents WHERE asset_type = 'marine' AND asset_id = ?", [$id]);
            foreach ($docs as $doc) {
                if (file_exists($doc['file_path'])) {
                    unlink($doc['file_path']);
                }
            }
            Database::delete('documents', "asset_type = 'marine' AND asset_id = ?", [$id]);
            Database::delete('marine_assets', 'id = ?', [$id]);

            Database::commit();

            AuditLogger::logDelete('marine_assets', $id, $marine);

            $this->redirect('fleet/marine', ['success' => 'Marine asset deleted successfully']);

        } catch (Exception $e) {
            Database::rollBack();
            error_log("Marine asset deletion error: " . $e->getMessage());
            $this->redirect('fleet/marine', ['error' => 'Failed to delete marine asset']);
        }
    }
    
    // =============================================
    // MOTORCYCLES MANAGEMENT
    // =============================================
    
    public function motorcycles() {
        // Check permission
        if (!Auth::can('fleet.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view motorcycles']);
            return;
        }
        
        $params = [];
        $baseSql = "SELECT m.*, s.state_name, l.lga_name, z.zone_name, c.command_name
             FROM motorcycle_assets m
             LEFT JOIN states s ON m.state_id = s.id
             LEFT JOIN lgas l ON m.lga_id = l.id
             LEFT JOIN zones z ON m.zone_id = z.id
             LEFT JOIN commands c ON m.command_id = c.id
             ORDER BY m.created_at DESC";
             
        $pagination = paginateTable('motorcycle_assets', 'm', ['asset_code', 'motorcycle_type', 'registration_number'], $baseSql, $params);
        $motorcycles = Database::fetchAll($pagination['sql'], $params);
        if ($motorcycles === false) $motorcycles = [];
        
        // Get document counts for each motorcycle
        foreach ($motorcycles as &$m) {
            $docResult = Database::fetchOne(
                "SELECT COUNT(*) as count FROM documents WHERE asset_type = 'motorcycle' AND asset_id = ?",
                [$m['id']]
            );
            $m['document_count'] = $docResult['count'] ?? 0;
        }
        
        // Calculate statistics using optimized database queries
        $statsParams = [];
        $statsSql = Database::applyCommandFilter("SELECT COUNT(*) as total, SUM(purchase_value) as total_value FROM motorcycle_assets m", 'm', $statsParams);
        $summary = Database::fetchOne($statsSql, $statsParams);
        
        $conditionParams = [];
        $conditionSql = Database::applyCommandFilter("SELECT `condition`, COUNT(*) as count FROM motorcycle_assets m GROUP BY `condition`", 'm', $conditionParams);
        $conditionResults = Database::fetchAll($conditionSql, $conditionParams) ?: [];
        $byCondition = [];
        foreach ($conditionResults as $r) {
            $byCondition[$r['condition'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statusParams = [];
        $statusSql = Database::applyCommandFilter("SELECT insurance_status, COUNT(*) as count FROM motorcycle_assets m GROUP BY insurance_status", 'm', $statusParams);
        $statusResults = Database::fetchAll($statusSql, $statusParams) ?: [];
        $byStatus = [];
        foreach ($statusResults as $r) {
            $byStatus[$r['insurance_status'] ?? 'Unknown'] = (int)$r['count'];
        }
        
        $statistics = [
            'total' => $summary['total'] ?? 0,
            'total_value' => $summary['total_value'] ?? 0,
            'by_condition' => $byCondition,
            'by_status' => $byStatus
        ];
        
        // Get zones for filter
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        if ($zones === false) $zones = [];
        
        $this->view('fleet/motorcycles/index', [
            'motorcycles' => $motorcycles,
            'statistics' => $statistics,
            'zones' => $zones,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $pagination['totalCount']
        ]);
    }
    
    public function createMotorcycle() {
        // Check permission
        if (!Auth::can('fleet.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create motorcycles']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $this->view('fleet/motorcycles/create', [
            'states' => $states,
            'zones' => $zones,
            'commands' => $commands
        ]);
    }
    
    public function storeMotorcycle() {
        if (Auth::isCommandRestricted()) { $_POST['command_id'] = Auth::commandId(); }
        // Check permission
        if (!Auth::can('fleet.create')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to create motorcycles']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('fleet/motorcycles/create', ['error' => 'Invalid security token']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['use_purpose'])) {
            $errors['use_purpose'] = 'Use purpose is required';
        }
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['motorcycle_type'])) {
            $errors['motorcycle_type'] = 'Motorcycle type is required';
        }
        
        if (empty($_POST['make_model'])) {
            $errors['make_model'] = 'Make/Model is required';
        }
        
        if (empty($_POST['engine_capacity'])) {
            $errors['engine_capacity'] = 'Engine capacity is required';
        }
        
        if (empty($_POST['chassis_number'])) {
            $errors['chassis_number'] = 'Chassis number is required';
        }
        
        if (empty($_POST['engine_number'])) {
            $errors['engine_number'] = 'Engine number is required';
        }
        
        if (empty($_POST['registration_number'])) {
            $errors['registration_number'] = 'Registration number is required';
        }
        
        if (empty($_POST['fuel_type'])) {
            $errors['fuel_type'] = 'Fuel type is required';
        }
        
        if (empty($_POST['current_mileage']) || !is_numeric($_POST['current_mileage'])) {
            $errors['current_mileage'] = 'Current mileage is required and must be a number';
        }
        
        if (empty($_POST['acquisition_type'])) {
            $errors['acquisition_type'] = 'Acquisition type is required';
        }
        
        if (empty($_POST['acquisition_date'])) {
            $errors['acquisition_date'] = 'Acquisition date is required';
        }
        
        if (empty($_POST['purchase_value']) || !is_numeric($_POST['purchase_value'])) {
            $errors['purchase_value'] = 'Purchase value is required and must be a number';
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
        
        if (empty($_POST['condition'])) {
            $errors['condition'] = 'Condition is required';
        }
        
        if (empty($_POST['insurance_status'])) {
            $errors['insurance_status'] = 'Insurance status is required';
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old', $_POST);
            $this->redirect('fleet/motorcycles/create');
            return;
        }
        
        $assetCode = $this->generateMotorcycleCode();
        
        Database::beginTransaction();
        
        try {
            $motorcycleId = Database::insert('motorcycle_assets', [
                'asset_code' => $assetCode,
                'use_purpose' => $_POST['use_purpose'],
                'ownership_type' => $_POST['ownership_type'],
                'motorcycle_type' => $_POST['motorcycle_type'],
                'make_model' => $_POST['make_model'],
                'engine_capacity' => $_POST['engine_capacity'],
                'chassis_number' => $_POST['chassis_number'],
                'engine_number' => $_POST['engine_number'],
                'registration_number' => $_POST['registration_number'],
                'fuel_type' => $_POST['fuel_type'],
                'current_mileage' => $_POST['current_mileage'],
                'acquisition_type' => $_POST['acquisition_type'],
                'acquisition_date' => $_POST['acquisition_date'],
                'purchase_value' => $_POST['purchase_value'],
                'current_value' => !empty($_POST['current_value']) ? $_POST['current_value'] : $_POST['purchase_value'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'current_location' => $_POST['current_location'] ?? null,
                'assigned_officer_unit' => $_POST['assigned_officer_unit'] ?? null,
                'condition' => $_POST['condition'],
                'insurance_status' => $_POST['insurance_status'],
                'insurance_expiry' => $_POST['insurance_expiry'] ?? null,
                'last_serviced_date' => $_POST['last_serviced_date'] ?? null,
                'next_service_due' => $_POST['next_service_due'] ?? null,
                'remarks' => $_POST['remarks'] ?? null,
                'created_by' => Auth::id()
            ]);
            
            if (!$motorcycleId) {
                throw new Exception("Failed to insert motorcycle record");
            }
            
            // Handle document uploads with types
            if (!empty($_FILES['documents']['name'][0]) && !empty($_POST['document_types'])) {
                $this->uploadDocumentsWithTypes($motorcycleId, 'motorcycle', $_FILES['documents'], $_POST['document_types']);
            }
            
            Database::commit();
            
            AuditLogger::logCreate('motorcycle_assets', $motorcycleId, $_POST);
            
            $this->redirect('fleet/motorcycles', ['success' => 'Motorcycle added successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Motorcycle creation error: " . $e->getMessage());
            $this->redirect('fleet/motorcycles/create', ['error' => 'Failed to add motorcycle: ' . $e->getMessage()]);
        }
    }
    
    public function showMotorcycle($id) {
        // Check permission
        if (!Auth::can('fleet.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view motorcycles']);
            return;
        }
        
        $motorcycle = Database::fetchOne(
            "SELECT m.*, s.state_name, l.lga_name, z.zone_name, c.command_name,
                    u.full_name as created_by_name
             FROM motorcycle_assets m
             LEFT JOIN states s ON m.state_id = s.id
             LEFT JOIN lgas l ON m.lga_id = l.id
             LEFT JOIN zones z ON m.zone_id = z.id
             LEFT JOIN commands c ON m.command_id = c.id
             LEFT JOIN users u ON m.created_by = u.id
             WHERE m.id = ?",
            [$id]
        );
        
        if (!$motorcycle) {
            $this->redirect('fleet/motorcycles', ['error' => 'Motorcycle not found']);
            return;
        }
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'motorcycle' AND asset_id = ?",
            [$id]
        );
        
        if ($documents === false) $documents = [];
        
        AuditLogger::logView('motorcycle_assets', $id);
        
        $this->view('fleet/motorcycles/show', [
            'motorcycle' => $motorcycle,
            'documents' => $documents
        ]);
    }
    
    public function editMotorcycle($id) {
        // Check permission
        if (!Auth::can('fleet.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit motorcycles']);
            return;
        }
        
        $motorcycle = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM motorcycle_assets WHERE id = ?", 'motorcycle_assets', $params), $params = [$id]);
        
        if (!$motorcycle) {
            $this->redirect('fleet/motorcycles', ['error' => 'Motorcycle not found']);
            return;
        }
        
        $states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
        $zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
        $commands = Database::fetchAll("SELECT * FROM commands ORDER BY command_name");
        
        if ($states === false) $states = [];
        if ($zones === false) $zones = [];
        if ($commands === false) $commands = [];
        
        $lgas = Database::fetchAll("SELECT * FROM lgas WHERE state_id = ? ORDER BY lga_name", [$motorcycle['state_id']]);
        if ($lgas === false) $lgas = [];
        
        $documents = Database::fetchAll(
            "SELECT * FROM documents WHERE asset_type = 'motorcycle' AND asset_id = ?",
            [$id]
        );
        if ($documents === false) $documents = [];
        
        $this->view('fleet/motorcycles/edit', [
            'motorcycle' => $motorcycle,
            'states' => $states,
            'lgas' => $lgas,
            'zones' => $zones,
            'commands' => $commands,
            'documents' => $documents
        ]);
    }
    
    public function updateMotorcycle($id) {
        if (Auth::isCommandRestricted()) { $_POST['command_id'] = Auth::commandId(); }
        // Check permission
        if (!Auth::can('fleet.edit')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit motorcycles']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("fleet/motorcycles/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $oldData = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM motorcycle_assets WHERE id = ?", 'motorcycle_assets', $params), $params = [$id]);
        
        if (!$oldData) {
            $this->redirect('fleet/motorcycles', ['error' => 'Motorcycle not found']);
            return;
        }
        
        // Manual validation
        $errors = [];
        
        if (empty($_POST['use_purpose'])) {
            $errors['use_purpose'] = 'Use purpose is required';
        }
        
        if (empty($_POST['ownership_type'])) {
            $errors['ownership_type'] = 'Ownership type is required';
        }
        
        if (empty($_POST['motorcycle_type'])) {
            $errors['motorcycle_type'] = 'Motorcycle type is required';
        }
        
        if (empty($_POST['make_model'])) {
            $errors['make_model'] = 'Make/Model is required';
        }
        
        if (empty($_POST['engine_capacity'])) {
            $errors['engine_capacity'] = 'Engine capacity is required';
        }
        
        if (empty($_POST['chassis_number'])) {
            $errors['chassis_number'] = 'Chassis number is required';
        }
        
        if (empty($_POST['engine_number'])) {
            $errors['engine_number'] = 'Engine number is required';
        }
        
        if (empty($_POST['registration_number'])) {
            $errors['registration_number'] = 'Registration number is required';
        }
        
        if (empty($_POST['fuel_type'])) {
            $errors['fuel_type'] = 'Fuel type is required';
        }
        
        if (empty($_POST['current_mileage']) || !is_numeric($_POST['current_mileage'])) {
            $errors['current_mileage'] = 'Current mileage is required and must be a number';
        }
        
        if (empty($_POST['acquisition_type'])) {
            $errors['acquisition_type'] = 'Acquisition type is required';
        }
        
        if (empty($_POST['acquisition_date'])) {
            $errors['acquisition_date'] = 'Acquisition date is required';
        }
        
        if (empty($_POST['purchase_value']) || !is_numeric($_POST['purchase_value'])) {
            $errors['purchase_value'] = 'Purchase value is required and must be a number';
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
        
        if (empty($_POST['condition'])) {
            $errors['condition'] = 'Condition is required';
        }
        
        if (empty($_POST['insurance_status'])) {
            $errors['insurance_status'] = 'Insurance status is required';
        }
        
        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect("fleet/motorcycles/edit/$id");
            return;
        }
        
        Database::beginTransaction();
        
        try {
            Database::update('motorcycle_assets', [
                'use_purpose' => $_POST['use_purpose'],
                'ownership_type' => $_POST['ownership_type'],
                'motorcycle_type' => $_POST['motorcycle_type'],
                'make_model' => $_POST['make_model'],
                'engine_capacity' => $_POST['engine_capacity'],
                'chassis_number' => $_POST['chassis_number'],
                'engine_number' => $_POST['engine_number'],
                'registration_number' => $_POST['registration_number'],
                'fuel_type' => $_POST['fuel_type'],
                'current_mileage' => $_POST['current_mileage'],
                'acquisition_type' => $_POST['acquisition_type'],
                'acquisition_date' => $_POST['acquisition_date'],
                'purchase_value' => $_POST['purchase_value'],
                'current_value' => !empty($_POST['current_value']) ? $_POST['current_value'] : $_POST['purchase_value'],
                'state_id' => $_POST['state_id'],
                'lga_id' => $_POST['lga_id'],
                'zone_id' => $_POST['zone_id'],
                'command_id' => $_POST['command_id'],
                'current_location' => $_POST['current_location'] ?? null,
                'assigned_officer_unit' => $_POST['assigned_officer_unit'] ?? null,
                'condition' => $_POST['condition'],
                'insurance_status' => $_POST['insurance_status'],
                'insurance_expiry' => $_POST['insurance_expiry'] ?? null,
                'last_serviced_date' => $_POST['last_serviced_date'] ?? null,
                'next_service_due' => $_POST['next_service_due'] ?? null,
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
            $mcFiles = !empty($_FILES['new_documents']['name'][0]) ? $_FILES['new_documents'] : (!empty($_FILES['documents']['name'][0]) ? $_FILES['documents'] : null);
            $docTypes = !empty($_POST['new_document_types']) ? $_POST['new_document_types'] : (!empty($_POST['document_types']) ? $_POST['document_types'] : []);
            if ($mcFiles && !empty($mcFiles['name'][0])) {
                $this->uploadDocumentsWithTypes($id, 'motorcycle', $mcFiles, $docTypes);
            }
            
            Database::commit();
            
            AuditLogger::logUpdate('motorcycle_assets', $id, $oldData, $_POST);
            
            $this->redirect("fleet/motorcycles/show/$id", ['success' => 'Motorcycle updated successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Motorcycle update error: " . $e->getMessage());
            $this->redirect("fleet/motorcycles/edit/$id", ['error' => 'Failed to update motorcycle: ' . $e->getMessage()]);
        }
    }
    
    public function deleteMotorcycle($id) {
        // Check permission
        if (!Auth::can('fleet.delete')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to delete motorcycles']);
            return;
        }
        
        $motorcycle = Database::fetchOne(Database::applyCommandFilter("SELECT * FROM motorcycle_assets WHERE id = ?", 'motorcycle_assets', $params), $params = [$id]);
        
        if (!$motorcycle) {
            $this->redirect('fleet/motorcycles', ['error' => 'Motorcycle not found']);
            return;
        }
        
        Database::beginTransaction();
        
        try {
            $documents = Database::fetchAll(
                "SELECT * FROM documents WHERE asset_type = 'motorcycle' AND asset_id = ?",
                [$id]
            );
            
            if ($documents && is_array($documents)) {
                foreach ($documents as $doc) {
                    if (file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
            }
            
            Database::delete('documents', "asset_type = 'motorcycle' AND asset_id = ?", [$id]);
            Database::delete('motorcycle_assets', 'id = ?', [$id]);
            
            Database::commit();
            
            AuditLogger::logDelete('motorcycle_assets', $id, $motorcycle);
            
            $this->redirect('fleet/motorcycles', ['success' => 'Motorcycle deleted successfully']);
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Motorcycle deletion error: " . $e->getMessage());
            $this->redirect('fleet/motorcycles', ['error' => 'Failed to delete motorcycle: ' . $e->getMessage()]);
        }
    }
    
    // =============================================
    // FLEET DASHBOARD & REPORTS
    // =============================================
    
    public function dashboard() {
        // Check permission
        if (!Auth::can('fleet.view')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view fleet dashboard']);
            return;
        }
        
        // Get user data from session
        $user = [
            'full_name' => $_SESSION['full_name'] ?? 'User',
            'roles' => $_SESSION['roles'] ?? ['Staff']
        ];
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true;
        
        // Initialize stats
        $stats = [
            'total_vehicles' => 0,
            'total_aircraft' => 0,
            'total_marine' => 0,
            'total_motorcycles' => 0,
            'active_vehicles' => 0,
            'vehicles_in_repair' => 0,
            'expiring_insurance' => 0,
            'total_fleet_value' => 0
        ];
        
        $recent_vehicles = [];
        
        try {
            // Get vehicle stats
            $stats['total_vehicles'] = Database::fetchOne("SELECT COUNT(*) as count FROM vehicle_assets")['count'] ?? 0;
            $stats['active_vehicles'] = Database::fetchOne("SELECT COUNT(*) as count FROM vehicle_assets WHERE operational_status = 'Active'")['count'] ?? 0;
            $stats['vehicles_in_repair'] = Database::fetchOne("SELECT COUNT(*) as count FROM vehicle_assets WHERE operational_status = 'In Repair'")['count'] ?? 0;
            
            // Get expiring insurance
            $stats['expiring_insurance'] = Database::fetchOne("
                SELECT COUNT(*) as count FROM vehicle_assets 
                WHERE insurance_expiry IS NOT NULL 
                AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND insurance_expiry >= CURDATE()
            ")['count'] ?? 0;
            
            // Get aircraft count
            $stats['total_aircraft'] = Database::fetchOne("SELECT COUNT(*) as count FROM aircraft_assets")['count'] ?? 0;
            
            // Get marine count
            $stats['total_marine'] = Database::fetchOne("SELECT COUNT(*) as count FROM marine_assets")['count'] ?? 0;
            
            // Get motorcycles count
            $stats['total_motorcycles'] = Database::fetchOne("SELECT COUNT(*) as count FROM motorcycle_assets")['count'] ?? 0;
            
            // Get total fleet value
            $vehicleValue = Database::fetchOne("SELECT SUM(purchase_value) as total FROM vehicle_assets")['total'] ?? 0;
            $aircraftValue = Database::fetchOne("SELECT SUM(capital_value) as total FROM aircraft_assets")['total'] ?? 0;
            $marineValue = Database::fetchOne("SELECT SUM(capital_value) as total FROM marine_assets")['total'] ?? 0;
            $motorcycleValue = Database::fetchOne("SELECT SUM(purchase_value) as total FROM motorcycle_assets")['total'] ?? 0;
            
            $stats['total_fleet_value'] = $vehicleValue + $aircraftValue + $marineValue + $motorcycleValue;
            
            // Get recent vehicles
            $recent_vehicles = Database::fetchAll("
                SELECT id, asset_code, make_manufacturer, model_year, registration_number, operational_status 
                FROM vehicle_assets 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            if ($recent_vehicles === false) $recent_vehicles = [];
            
        } catch (Exception $e) {
            error_log("Fleet dashboard error: " . $e->getMessage());
        }
        
        // Set variables for the view
        $title = 'Fleet Dashboard';
        $active = 'fleet-dashboard';
        $init_charts = true;
        
        $this->view('fleet/dashboard', [
            'stats' => $stats,
            'recent_vehicles' => $recent_vehicles,
            'user' => $user,
            'isSuperAdmin' => $isSuperAdmin,
            'title' => $title,
            'active' => $active,
            'init_charts' => $init_charts
        ]);
    }
    
    public function exportVehicles() {
        // Check permission
        if (!Auth::can('reports.export')) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to export data']);
            return;
        }
        
        $vehicles = Database::fetchAll(
            "SELECT v.*, s.state_name, l.lga_name, z.zone_name, c.command_name
             FROM vehicle_assets v
             LEFT JOIN states s ON v.state_id = s.id
             LEFT JOIN lgas l ON v.lga_id = l.id
             LEFT JOIN zones z ON v.zone_id = z.id
             LEFT JOIN commands c ON v.command_id = c.id
             ORDER BY v.created_at DESC"
        );
        
        if ($vehicles === false) $vehicles = [];
        
        $filename = 'vehicle_assets_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        Security::fputcsv($output, [
            'Asset Code', 'Use Purpose', 'Ownership Type', 'Vehicle Type',
            'Make/Manufacturer', 'Model Year', 'Color', 'VIN/Chassis',
            'Engine Number', 'Registration', 'Engine Capacity', 'Fuel Type',
            'Mileage', 'Acquisition Type', 'Acquisition Date', 'Purchase Value',
            'State', 'LGA', 'Zone', 'Command', 'Current Location',
            'Assigned Officer', 'Operational Status', 'Condition',
            'Insurance Status', 'Insurance Expiry', 'Last Service', 'Next Service',
            'Remarks', 'Created At'
        ]);
        
        foreach ($vehicles as $v) {
            Security::fputcsv($output, [
                $v['asset_code'] ?? '',
                $v['use_purpose'] ?? '',
                $v['ownership_type'] ?? '',
                $v['vehicle_type'] ?? '',
                $v['make_manufacturer'] ?? '',
                $v['model_year'] ?? '',
                $v['color'] ?? '',
                $v['vin_chassis_number'] ?? '',
                $v['engine_number'] ?? '',
                $v['registration_number'] ?? '',
                $v['engine_capacity'] ?? '',
                $v['fuel_type'] ?? '',
                $v['mileage'] ?? '',
                $v['acquisition_type'] ?? '',
                $v['acquisition_date'] ?? '',
                $v['purchase_value'] ?? '',
                $v['state_name'] ?? '',
                $v['lga_name'] ?? '',
                $v['zone_name'] ?? '',
                $v['command_name'] ?? '',
                $v['current_location'] ?? '',
                $v['assigned_officer'] ?? '',
                $v['operational_status'] ?? '',
                $v['condition'] ?? '',
                $v['insurance_status'] ?? '',
                $v['insurance_expiry'] ?? '',
                $v['last_service_date'] ?? '',
                $v['next_service_date'] ?? '',
                $v['remarks'] ?? '',
                $v['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
        
        AuditLogger::logExport('vehicles', 'csv');
        exit;
    }
    
    // =============================================
    // HELPER METHODS
    // =============================================
    
    private function generateVehicleCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM vehicle_assets 
             WHERE asset_code LIKE 'VHL-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($last && isset($last['asset_code'])) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("VHL-%s%s-%04d", $year, $month, $seq);
    }
    
    private function generateAircraftCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM aircraft_assets 
             WHERE asset_code LIKE 'AC-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($last && isset($last['asset_code'])) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("AC-%s%s-%04d", $year, $month, $seq);
    }
    
    private function generateMarineCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM marine_assets 
             WHERE asset_code LIKE 'MRN-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($last && isset($last['asset_code'])) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("MRN-%s%s-%04d", $year, $month, $seq);
    }
    
    private function generateMotorcycleCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM motorcycle_assets 
             WHERE asset_code LIKE 'MTR-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        );
        
        if ($last && isset($last['asset_code'])) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("MTR-%s%s-%04d", $year, $month, $seq);
    }
    
    private function uploadDocumentsWithTypes($assetId, $type, $files, $documentTypes = []) {
        $uploadDir = Config::get('upload_path') . $type . '_documents/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = Config::get('allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
        $maxSize = 5 * 1024 * 1024; // 5MB max per file
        
        if (!is_array($files) || empty($files['name']) || !is_array($files['name'])) {
            return;
        }
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if (empty($files['name'][$i]) || $files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $fileName = $files['name'][$i];
            $fileTmp = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $documentType = (!empty($documentTypes) && is_array($documentTypes) && !empty($documentTypes[$i])) ? $documentTypes[$i] : 'other';
            
            if (!in_array($fileExt, $allowedTypes)) {
                continue;
            }
            
            if ($fileSize > $maxSize) {
                continue;
            }
            
            $newFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $fileName);
            $destination = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $destination)) {
                $rawPath = str_replace('\\', '/', $destination);
                if (strpos($rawPath, 'assets/uploads/') !== false) {
                    $relativePath = substr($rawPath, strpos($rawPath, 'assets/uploads/'));
                } else {
                    $relativePath = 'assets/uploads/' . $type . '_documents/' . $newFileName;
                }
                
                Database::insert('documents', [
                    'asset_type' => $type,
                    'asset_id' => $assetId,
                    'file_name' => $fileName,
                    'file_path' => $relativePath,
                    'file_size' => $fileSize,
                    'file_mime' => $files['type'][$i] ?? 'application/octet-stream',
                    'document_type' => $documentType,
                    'uploaded_by' => Auth::id()
                ]);
            }
        }
    }
    
    /**
     * General export alias for fleet routes
     */
    public function export() {
        return $this->exportVehicles();
    }
}