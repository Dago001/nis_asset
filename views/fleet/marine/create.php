<?php
$title = 'Add New Marine Asset';
$active = 'marine';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

$old = Session::get('old', []);
$errors = Session::get('errors', []);
Session::remove('old');
Session::remove('errors');

// Get all states for dropdown
$states = Database::fetchAll("SELECT * FROM states ORDER BY state_name");
if ($states === false) $states = [];

// Get all zones for dropdown
$zones = Database::fetchAll("SELECT * FROM zones ORDER BY zone_name");
if ($zones === false) $zones = [];

// Document types for the dropdown
$documentTypes = [
    'registration' => 'Registration Certificate',
    'survey' => 'Survey Certificate',
    'insurance' => 'Insurance Document',
    'equipment' => 'Equipment List',
    'photo' => 'Vessel Photo',
    'other' => 'Other Document'
];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Add New Marine Asset
            </h1>
            <p>Enter marine vessel details</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/fleet/marine" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Marine Assets
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/fleet/marine/store" enctype="multipart/form-data" id="marineForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Basic Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Use/Purpose</label>
                        <select name="use_purpose" required class="form-control <?php echo isset($errors['use_purpose']) ? 'error' : ''; ?>">
                            <option value="">Select Purpose</option>
                            <option value="Patrol" <?php echo ($old['use_purpose'] ?? '') == 'Patrol' ? 'selected' : ''; ?>>Patrol</option>
                            <option value="Transport" <?php echo ($old['use_purpose'] ?? '') == 'Transport' ? 'selected' : ''; ?>>Transport</option>
                            <option value="Rescue" <?php echo ($old['use_purpose'] ?? '') == 'Rescue' ? 'selected' : ''; ?>>Rescue</option>
                            <option value="Surveillance" <?php echo ($old['use_purpose'] ?? '') == 'Surveillance' ? 'selected' : ''; ?>>Surveillance</option>
                        </select>
                        <?php if (isset($errors['use_purpose'])): ?>
                            <small class="error-text"><?php echo $errors['use_purpose']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Ownership Type</label>
                        <select name="ownership_type" required class="form-control <?php echo isset($errors['ownership_type']) ? 'error' : ''; ?>">
                            <option value="">Select Ownership</option>
                            <option value="FGN-Owned" <?php echo ($old['ownership_type'] ?? '') == 'FGN-Owned' ? 'selected' : ''; ?>>FGN-Owned</option>
                            <option value="Donor" <?php echo ($old['ownership_type'] ?? '') == 'Donor' ? 'selected' : ''; ?>>Donor</option>
                            <option value="Leased" <?php echo ($old['ownership_type'] ?? '') == 'Leased' ? 'selected' : ''; ?>>Leased</option>
                        </select>
                        <?php if (isset($errors['ownership_type'])): ?>
                            <small class="error-text"><?php echo $errors['ownership_type']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Vessel Name</label>
                        <input type="text" name="vessel_name" value="<?php echo Security::escape($old['vessel_name'] ?? ''); ?>" 
                               required maxlength="100" class="form-control <?php echo isset($errors['vessel_name']) ? 'error' : ''; ?>"
                               placeholder="e.g., NIS PATROL 1">
                        <?php if (isset($errors['vessel_name'])): ?>
                            <small class="error-text"><?php echo $errors['vessel_name']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Boat Type</label>
                        <select name="boat_type" id="boat_type" required class="form-control <?php echo isset($errors['boat_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="Patrol Boat" <?php echo ($old['boat_type'] ?? '') == 'Patrol Boat' ? 'selected' : ''; ?>>Patrol Boat</option>
                            <option value="Speed Boat" <?php echo ($old['boat_type'] ?? '') == 'Speed Boat' ? 'selected' : ''; ?>>Speed Boat</option>
                            <option value="Ferry" <?php echo ($old['boat_type'] ?? '') == 'Ferry' ? 'selected' : ''; ?>>Ferry</option>
                            <option value="Rigid Hull" <?php echo ($old['boat_type'] ?? '') == 'Rigid Hull' ? 'selected' : ''; ?>>Rigid Hull</option>
                            <option value="Rubber Boat" <?php echo ($old['boat_type'] ?? '') == 'Rubber Boat' ? 'selected' : ''; ?>>Rubber Boat</option>
                            <option value="Other">Other</option>
                        </select>
                        <?php if (isset($errors['boat_type'])): ?>
                            <small class="error-text"><?php echo $errors['boat_type']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="typeOtherWrapper" style="display: none;">
                        <label>Specify Boat Type</label>
                        <input type="text" name="boat_type_other" id="boat_type_other" class="form-control" 
                               value="<?php echo Security::escape($old['boat_type_other'] ?? ''); ?>" placeholder="Enter boat type">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Hull Identification</label>
                        <input type="text" name="hull_identification" value="<?php echo Security::escape($old['hull_identification'] ?? ''); ?>" 
                               required maxlength="100" class="form-control <?php echo isset($errors['hull_identification']) ? 'error' : ''; ?>"
                               placeholder="Hull serial number">
                        <?php if (isset($errors['hull_identification'])): ?>
                            <small class="error-text"><?php echo $errors['hull_identification']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Registration Number</label>
                        <input type="text" name="registration_number" value="<?php echo Security::escape($old['registration_number'] ?? ''); ?>" 
                               required maxlength="50" class="form-control <?php echo isset($errors['registration_number']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['registration_number'])): ?>
                            <small class="error-text"><?php echo $errors['registration_number']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Engine Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-engine"></i> Engine Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Engine Type</label>
                        <input type="text" name="engine_type" value="<?php echo Security::escape($old['engine_type'] ?? ''); ?>" 
                               required maxlength="100" class="form-control <?php echo isset($errors['engine_type']) ? 'error' : ''; ?>"
                               placeholder="e.g., Outboard, Inboard">
                        <?php if (isset($errors['engine_type'])): ?>
                            <small class="error-text"><?php echo $errors['engine_type']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Engine Capacity</label>
                        <input type="text" name="engine_capacity" value="<?php echo Security::escape($old['engine_capacity'] ?? ''); ?>" 
                               required maxlength="50" class="form-control <?php echo isset($errors['engine_capacity']) ? 'error' : ''; ?>"
                               placeholder="e.g., 200HP, 500HP">
                        <?php if (isset($errors['engine_capacity'])): ?>
                            <small class="error-text"><?php echo $errors['engine_capacity']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Number of Engines</label>
                        <input type="number" name="number_engines" value="<?php echo Security::escape($old['number_engines'] ?? '1'); ?>" 
                               required min="1" class="form-control <?php echo isset($errors['number_engines']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['number_engines'])): ?>
                            <small class="error-text"><?php echo $errors['number_engines']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Fuel Type</label>
                        <select name="fuel_type" required class="form-control <?php echo isset($errors['fuel_type']) ? 'error' : ''; ?>">
                            <option value="">Select Fuel Type</option>
                            <option value="Petrol" <?php echo ($old['fuel_type'] ?? '') == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                            <option value="Diesel" <?php echo ($old['fuel_type'] ?? '') == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                        </select>
                        <?php if (isset($errors['fuel_type'])): ?>
                            <small class="error-text"><?php echo $errors['fuel_type']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Acquisition Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Acquisition Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Acquisition Type</label>
                        <select name="acquisition_type" required class="form-control <?php echo isset($errors['acquisition_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="Purchase" <?php echo ($old['acquisition_type'] ?? '') == 'Purchase' ? 'selected' : ''; ?>>Purchase</option>
                            <option value="Transfer" <?php echo ($old['acquisition_type'] ?? '') == 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
                            <option value="Donation" <?php echo ($old['acquisition_type'] ?? '') == 'Donation' ? 'selected' : ''; ?>>Donation</option>
                            <option value="Lease" <?php echo ($old['acquisition_type'] ?? '') == 'Lease' ? 'selected' : ''; ?>>Lease</option>
                        </select>
                        <?php if (isset($errors['acquisition_type'])): ?>
                            <small class="error-text"><?php echo $errors['acquisition_type']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Acquisition Date</label>
                        <input type="date" name="acquisition_date" value="<?php echo Security::escape($old['acquisition_date'] ?? ''); ?>" 
                               required class="form-control <?php echo isset($errors['acquisition_date']) ? 'error' : ''; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['acquisition_date'])): ?>
                            <small class="error-text"><?php echo $errors['acquisition_date']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Capital Value (₦)</label>
                        <input type="number" step="0.01" name="capital_value" value="<?php echo Security::escape($old['capital_value'] ?? ''); ?>" 
                               required class="form-control <?php echo isset($errors['capital_value']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['capital_value'])): ?>
                            <small class="error-text"><?php echo $errors['capital_value']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Location Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-map-marker-alt"></i> Location & Assignment</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">State</label>
                        <select name="state_id" id="state_id" required 
                                class="form-control <?php echo isset($errors['state_id']) ? 'error' : ''; ?>">
                            <option value="">Select State</option>
                            <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>" 
                                    <?php echo ($old['state_id'] ?? '') == $state['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($state['state_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['state_id'])): ?>
                            <small class="error-text"><?php echo $errors['state_id']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">LGA</label>
                        <select name="lga_id" id="lga_id" required 
                                class="form-control <?php echo isset($errors['lga_id']) ? 'error' : ''; ?>">
                            <option value="">Select State First</option>
                        </select>
                        <?php if (isset($errors['lga_id'])): ?>
                            <small class="error-text"><?php echo $errors['lga_id']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Zone</label>
                        <select name="zone_id" id="zone_id" required 
                                class="form-control <?php echo isset($errors['zone_id']) ? 'error' : ''; ?>">
                            <option value="">Select Zone</option>
                            <?php foreach ($zones as $zone): ?>
                            <option value="<?php echo $zone['id']; ?>" 
                                    <?php echo ($old['zone_id'] ?? '') == $zone['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($zone['zone_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['zone_id'])): ?>
                            <small class="error-text"><?php echo $errors['zone_id']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Command</label>
                        <select name="command_id" id="command_id" required 
                                class="form-control <?php echo isset($errors['command_id']) ? 'error' : ''; ?>">
                            <option value="">Select Zone First</option>
                        </select>
                        <?php if (isset($errors['command_id'])): ?>
                            <small class="error-text"><?php echo $errors['command_id']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Docking Location</label>
                        <input type="text" name="docking_location" value="<?php echo Security::escape($old['docking_location'] ?? ''); ?>" 
                               required class="form-control" placeholder="Port, jetty, or base">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Assigned Crew/Unit</label>
                        <input type="text" name="assigned_crew_unit" value="<?php echo Security::escape($old['assigned_crew_unit'] ?? ''); ?>" 
                               required class="form-control">
                    </div>
                </div>
            </div>
            
            <!-- Operational Status -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-clipboard-check"></i> Operational Status</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Operational Status</label>
                        <select name="operational_status" id="operational_status" required class="form-control <?php echo isset($errors['operational_status']) ? 'error' : ''; ?>">
                            <option value="">Select Status</option>
                            <option value="Operational" <?php echo ($old['operational_status'] ?? '') == 'Operational' ? 'selected' : ''; ?>>Operational</option>
                            <option value="Maintenance" <?php echo ($old['operational_status'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="Docked" <?php echo ($old['operational_status'] ?? '') == 'Docked' ? 'selected' : ''; ?>>Docked</option>
                            <option value="Decommissioned" <?php echo ($old['operational_status'] ?? '') == 'Decommissioned' ? 'selected' : ''; ?>>Decommissioned</option>
                        </select>
                        <?php if (isset($errors['operational_status'])): ?>
                            <small class="error-text"><?php echo $errors['operational_status']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Condition</label>
                        <select name="condition" required class="form-control <?php echo isset($errors['condition']) ? 'error' : ''; ?>">
                            <option value="">Select Condition</option>
                            <option value="Excellent" <?php echo ($old['condition'] ?? '') == 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                            <option value="Good" <?php echo ($old['condition'] ?? '') == 'Good' ? 'selected' : ''; ?>>Good</option>
                            <option value="Fair" <?php echo ($old['condition'] ?? '') == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                            <option value="Poor" <?php echo ($old['condition'] ?? '') == 'Poor' ? 'selected' : ''; ?>>Poor</option>
                        </select>
                        <?php if (isset($errors['condition'])): ?>
                            <small class="error-text"><?php echo $errors['condition']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Maintenance -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-tools"></i> Maintenance</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Dry Docking Date</label>
                        <input type="date" name="last_dry_docking" value="<?php echo Security::escape($old['last_dry_docking'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Next Dry Docking Date</label>
                        <input type="date" name="next_dry_docking" value="<?php echo Security::escape($old['next_dry_docking'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Onboard Equipment</label>
                    <textarea name="onboard_equipment" rows="3" class="form-control" 
                              placeholder="Radar, sonar, communication equipment, etc."><?php echo Security::escape($old['onboard_equipment'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Remarks -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-sticky-note"></i> Remarks</h3>
                </div>
                
                <div class="form-group">
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($old['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Document Upload Section -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-upload"></i> Supporting Documents</h3>
                    <span class="badge badge-info">Optional - Max 5MB per file</span>
                </div>
                
                <div class="document-upload-container">
                    <div class="document-types-grid" id="documentTypesContainer">
                        <!-- Document type rows will be added here dynamically -->
                    </div>
                    
                    <div class="document-actions">
                        <button type="button" class="btn btn-outline-primary" id="addDocumentBtn">
                            <i class="fas fa-plus-circle"></i> Add Another Document
                        </button>
                    </div>
                    
                    <div class="upload-info">
                        <div class="info-item">
                            <i class="fas fa-info-circle text-info"></i>
                            <span>Allowed file types: PDF, JPG, PNG, DOC, DOCX</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-database text-warning"></i>
                            <span>Maximum file size: 5MB per file</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-save"></i> Save Marine Asset
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm('marineForm')">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
                <a href="<?php echo BASE_URL; ?>/fleet/marine" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
:root {
    --primary-color: #134617;
    --primary-light: #207027;
    --secondary-color: #207027;
    --secondary-dark: #134617;
    --success-color: #207027;
    --danger-color: #B42318;
    --warning-color: #C69214;
    --info-color: #1F6F8B;
    --light-bg: #F7FAF8;
    --border-color: #D7E3DC;
    --text-primary: #212529;
    --text-secondary: #53665E;
}
[data-theme="dark"] {
    --primary-color: #299631;
    --primary-light: #37bf43;
    --secondary-color: #37bf43;
    --secondary-dark: #299631;
    --success-color: #37bf43;
    --danger-color: #e7564b;
    --warning-color: #eec052;
    --info-color: #3cacd4;
    --light-bg: #1a231d;
    --border-color: #2f3832;
    --text-primary: #d8e9d9;
    --text-secondary: #dfe2e1;
}


/* Form Styles */
.form-section {
    background: var(--surface);
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.form-section-inner {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.form-section-inner:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-title h3 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.required::after {
    content: " *";
    color: var(--danger-color);
}

.form-control, .form-group select, .form-group input {
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.95rem;
    transition: all 0.3s;
    width: 100%;
}

.form-control:focus, .form-group select:focus, .form-group input:focus {
    outline: none;
    border-color: var(--success-color);
    box-shadow: 0 0 0 3px rgba(32, 112, 39, 0.2);
}

.form-control.error, .form-group select.error, .form-group input.error {
    border-color: var(--danger-color);
    background-color: #fff5f5;
}

.error-text {
    color: var(--danger-color);
    font-size: 0.85rem;
    margin-top: 5px;
}

/* Professional Document Upload Styles */
.document-upload-container {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #D7E3DC;
    margin-top: 15px;
}

.document-types-grid {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.document-row {
    display: grid;
    grid-template-columns: 250px 1fr 40px;
    gap: 15px;
    align-items: center;
    background: var(--surface);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #D7E3DC;
    transition: all 0.3s ease;
    position: relative;
}

.document-row:hover {
    border-color: #207027;
    box-shadow: 0 2px 8px rgba(32, 112, 39, 0.1);
}

.document-type-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #D7E3DC;
    border-radius: 6px;
    font-size: 0.95rem;
    background: var(--surface);
    cursor: pointer;
    transition: all 0.3s ease;
}

.document-type-select:hover,
.document-type-select:focus {
    border-color: #207027;
    outline: none;
    box-shadow: 0 0 0 3px rgba(32, 112, 39, 0.1);
}

.file-input-wrapper {
    position: relative;
    width: 100%;
}

.file-input-custom {
    width: 100%;
    padding: 10px 12px;
    border: 1px dashed #D7E3DC;
    border-radius: 6px;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #64748b;
}

.file-input-custom:hover {
    border-color: #207027;
    background: #f0f9f4;
    color: #207027;
}

.file-input-custom i {
    font-size: 1.2rem;
}

.file-input-custom span {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.file-input-element {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.remove-document-btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 1px solid #fee2e2;
    background: #fef2f2;
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.remove-document-btn:hover {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

.remove-document-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #f1f5f9;
    color: #94a3b8;
    border-color: #D7E3DC;
}

.document-actions {
    display: flex;
    justify-content: center;
    margin: 15px 0;
}

.btn-outline-primary {
    background: var(--surface);
    border: 1px solid #207027;
    color: #207027;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-outline-primary:hover {
    background: #207027;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(32, 112, 39, 0.2);
}

.upload-info {
    margin-top: 20px;
    padding: 15px;
    background: #f1f5f9;
    border-radius: 8px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: #475569;
}

/* Badge styles */
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-info {
    background: #e0f2f1;
    color: #00695c;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-success {
    background: var(--success-color);
    color: white;
}

.btn-success:hover {
    background: var(--secondary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(32, 112, 39, 0.3);
}

.btn-secondary {
    background: var(--text-secondary);
    color: white;
}

.btn-secondary:hover {
    background: #6c757d;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.btn-outline:hover {
    background: var(--light-bg);
    border-color: var(--success-color);
    color: var(--success-color);
}

.submit-btn {
    min-width: 150px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
    
    .document-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Define base URL for API calls
const baseUrl = '<?php echo BASE_URL; ?>';
const documentTypes = <?php echo json_encode($documentTypes); ?>;

let documentCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    // Add first document row by default
    addDocumentRow();
    
    // Boat type other field
    const typeSelect = document.getElementById('boat_type');
    const typeWrapper = document.getElementById('typeOtherWrapper');
    const typeOther = document.getElementById('boat_type_other');
    
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                typeWrapper.style.display = 'block';
                typeOther.required = true;
            } else {
                typeWrapper.style.display = 'none';
                typeOther.required = false;
            }
        });
        
        <?php if (($old['boat_type'] ?? '') === 'Other'): ?>
        typeSelect.value = 'Other';
        typeWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
    // State to LGA dropdown
    const stateSelect = document.getElementById('state_id');
    const lgaSelect = document.getElementById('lga_id');
    
    if (stateSelect) {
        stateSelect.addEventListener('change', function() {
            const stateId = this.value;
            
            if (!stateId) {
                lgaSelect.innerHTML = '<option value="">Select State First</option>';
                return;
            }
            
            const apiUrl = baseUrl.replace(/\/$/, '') + '/api/get_lgas.php?state_id=' + stateId;
            
            fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>';
                        return;
                    }
                    
                    lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                    if (data && data.length > 0) {
                        data.forEach(lga => {
                            const option = document.createElement('option');
                            option.value = lga.id;
                            option.textContent = lga.lga_name;
                            lgaSelect.appendChild(option);
                        });
                        
                        <?php if (!empty($old['lga_id'])): ?>
                        lgaSelect.value = '<?php echo $old['lga_id']; ?>';
                        <?php endif; ?>
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>';
                });
        });
        
        <?php if (!empty($old['state_id'])): ?>
        stateSelect.value = '<?php echo $old['state_id']; ?>';
        stateSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
    }
    
    // Zone to Command dropdown
    const zoneSelect = document.getElementById('zone_id');
    const commandSelect = document.getElementById('command_id');
    
    if (zoneSelect) {
        zoneSelect.addEventListener('change', function() {
            const zoneId = this.value;
            
            if (!zoneId) {
                commandSelect.innerHTML = '<option value="">Select Zone First</option>';
                return;
            }
            
            const apiUrl = baseUrl.replace(/\/$/, '') + '/api/get_commands.php?zone_id=' + zoneId;
            
            fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        commandSelect.innerHTML = '<option value="">Error loading commands</option>';
                        return;
                    }
                    
                    commandSelect.innerHTML = '<option value="">Select Command</option>';
                    if (data && data.length > 0) {
                        data.forEach(cmd => {
                            const option = document.createElement('option');
                            option.value = cmd.id;
                            option.textContent = cmd.command_name;
                            commandSelect.appendChild(option);
                        });
                        
                        <?php if (!empty($old['command_id'])): ?>
                        commandSelect.value = '<?php echo $old['command_id']; ?>';
                        <?php endif; ?>
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    commandSelect.innerHTML = '<option value="">Error loading commands</option>';
                });
        });
        
        <?php if (!empty($old['zone_id'])): ?>
        zoneSelect.value = '<?php echo $old['zone_id']; ?>';
        zoneSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
    }
    
    // Add Document button click handler
    document.getElementById('addDocumentBtn').addEventListener('click', function() {
        addDocumentRow();
    });
});

function addDocumentRow() {
    const container = document.getElementById('documentTypesContainer');
    const rowId = 'doc_row_' + documentCount;
    
    const row = document.createElement('div');
    row.className = 'document-row';
    row.id = rowId;
    
    let options = '<option value="">Select Document Type</option>';
    for (const [value, label] of Object.entries(documentTypes)) {
        options += `<option value="${value}">${label}</option>`;
    }
    
    row.innerHTML = `
        <select name="document_types[]" class="document-type-select">
            ${options}
        </select>
        <div class="file-input-wrapper">
            <div class="file-input-custom" id="custom_${rowId}">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Choose file...</span>
            </div>
            <input type="file" name="documents[]" class="file-input-element" 
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" 
                   data-custom="custom_${rowId}"
                   onchange="updateFileLabel(this, '${rowId}')">
        </div>
        <button type="button" class="remove-document-btn" onclick="removeDocumentRow('${rowId}')" ${documentCount === 0 ? 'disabled' : ''}>
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(row);
    documentCount++;
}

function updateFileLabel(input, rowId) {
    const customDiv = document.getElementById('custom_' + rowId);
    if (customDiv) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
            
            if (file.size > 5 * 1024 * 1024) {
                alert('File ' + file.name + ' exceeds 5MB limit. Please choose a smaller file.');
                input.value = '';
                customDiv.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Choose file...</span>';
                return;
            }
            
            const allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            const fileExt = file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(fileExt)) {
                alert('File type not allowed. Allowed types: PDF, JPG, PNG, DOC, DOCX');
                input.value = '';
                customDiv.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Choose file...</span>';
                return;
            }
            
            customDiv.innerHTML = `<i class="fas fa-check-circle text-success"></i><span>${file.name} (${fileSizeMB} MB)</span>`;
            customDiv.style.borderColor = '#207027';
            customDiv.style.background = '#f0f9f4';
        } else {
            customDiv.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Choose file...</span>';
        }
    }
}

function removeDocumentRow(rowId) {
    const row = document.getElementById(rowId);
    if (row && documentCount > 1) {
        row.remove();
        documentCount--;
    } else if (documentCount === 1) {
        alert('At least one document row must remain. You can leave it empty if you don\'t want to upload any documents.');
    }
}

function resetForm(formId) {
    if (confirm('Are you sure you want to reset the form? All unsaved data will be lost.')) {
        document.getElementById(formId).reset();
        
        const container = document.getElementById('documentTypesContainer');
        container.innerHTML = '';
        documentCount = 0;
        addDocumentRow();
        
        document.getElementById('lga_id').innerHTML = '<option value="">Select State First</option>';
        document.getElementById('command_id').innerHTML = '<option value="">Select Zone First</option>';
        
        const typeWrapper = document.getElementById('typeOtherWrapper');
        if (typeWrapper) {
            typeWrapper.style.display = 'none';
        }
        
        if (typeof showNotification === 'function') {
            showNotification('info', 'Form has been reset');
        } else {
            alert('Form has been reset');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>