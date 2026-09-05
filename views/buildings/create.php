<?php
$title = 'Add Building Asset';
$active = 'buildings';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

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

// Get land assets for linking (limited to 50 initially)
$landAssets = Database::fetchAll("SELECT id, asset_code, address FROM land_assets ORDER BY asset_code ASC LIMIT 50");
if ($landAssets === false) $landAssets = [];

// Document types for the dropdown
$documentTypes = [
    'award_letter' => 'Award Letter',
    'completion_letter' => 'Completion Letter',
    'floor_plan' => 'Floor Plan/Design',
    'inspection_report' => 'Verification/Inspector Report',
    'pictures' => 'Upload Pictures'
];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Add New Building Asset
            </h1>
            <p>Enter building asset details</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/buildings" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/buildings/store" enctype="multipart/form-data" id="buildingForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Basic Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                </div>
                
                <!-- Ownership Type (NEW) -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Ownership Type</label>
                        <select name="ownership_type" id="ownership_type" required 
                                class="form-control <?php echo isset($errors['ownership_type']) ? 'error' : ''; ?>">
                            <option value="">Select Ownership Type</option>
                            <option value="FGN" <?php echo ($old['ownership_type'] ?? '') == 'FGN' ? 'selected' : ''; ?>>FGN (NIS)</option>
                            <option value="State" <?php echo ($old['ownership_type'] ?? '') == 'State' ? 'selected' : ''; ?>>State</option>
                            <option value="Private" <?php echo ($old['ownership_type'] ?? '') == 'Private' ? 'selected' : ''; ?>>Private</option>
                        </select>
                        <?php if (isset($errors['ownership_type'])): ?>
                            <small class="error-text"><?php echo $errors['ownership_type']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Purpose/Function (NEW) -->
                    <div class="form-group">
                        <label class="required">Purpose / Function</label>
                        <select name="purpose_function" id="purpose_function" required 
                                class="form-control <?php echo isset($errors['purpose_function']) ? 'error' : ''; ?>">
                            <option value="">Select Purpose/Function</option>
                            <option value="Command / Formation" <?php echo ($old['purpose_function'] ?? '') == 'Command / Formation' ? 'selected' : ''; ?>>Command / Formation</option>
                            <option value="Area Office" <?php echo ($old['purpose_function'] ?? '') == 'Area Office' ? 'selected' : ''; ?>>Area Office</option>
                            <option value="Divisional Office" <?php echo ($old['purpose_function'] ?? '') == 'Divisional Office' ? 'selected' : ''; ?>>Divisional Office</option>
                            <option value="PPT Office" <?php echo ($old['purpose_function'] ?? '') == 'PPT Office' ? 'selected' : ''; ?>>PPT Office</option>
                            <option value="Control Post" <?php echo ($old['purpose_function'] ?? '') == 'Control Post' ? 'selected' : ''; ?>>Control Post</option>
                            <option value="Barracks/Transit Camp" <?php echo ($old['purpose_function'] ?? '') == 'Barracks/Transit Camp' ? 'selected' : ''; ?>>Barracks/Transit Camp</option>
                            <option value="FOB/MOB" <?php echo ($old['purpose_function'] ?? '') == 'FOB/MOB' ? 'selected' : ''; ?>>FOB/MOB</option>
                            <option value="E-Border" <?php echo ($old['purpose_function'] ?? '') == 'E-Border' ? 'selected' : ''; ?>>E-Border</option>
                            <option value="Flag House" <?php echo ($old['purpose_function'] ?? '') == 'Flag House' ? 'selected' : ''; ?>>Flag House</option>
                            <option value="Zonal Command" <?php echo ($old['purpose_function'] ?? '') == 'Zonal Command' ? 'selected' : ''; ?>>Zonal Command</option>
                            <option value="Migrant Holding Centers" <?php echo ($old['purpose_function'] ?? '') == 'Migrant Holding Centers' ? 'selected' : ''; ?>>Migrant Holding Centers</option>
                            <option value="Others">Others</option>
                        </select>
                        <?php if (isset($errors['purpose_function'])): ?>
                            <small class="error-text"><?php echo $errors['purpose_function']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Purpose/Function Other field (for "Others" option) -->
                <div class="form-group" id="purposeOtherWrapper" style="display: none;">
                    <label>Specify Other Purpose/Function</label>
                    <input type="text" name="purpose_other" id="purpose_other" class="form-control" 
                           placeholder="Enter purpose/function" value="<?php echo Security::escape($old['purpose_other'] ?? ''); ?>">
                    <small class="form-hint">Please specify the purpose/function</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Building Name</label>
                        <input type="text" name="building_name" id="building_name" 
                               value="<?php echo Security::escape($old['building_name'] ?? ''); ?>" 
                               required maxlength="255" 
                               class="form-control <?php echo isset($errors['building_name']) ? 'error' : ''; ?>"
                               placeholder="e.g., Headquarters Building">
                        <?php if (isset($errors['building_name'])): ?>
                            <small class="error-text"><?php echo $errors['building_name']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Building Type</label>
                        <select name="building_type" id="building_type" required 
                                class="form-control <?php echo isset($errors['building_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="Office" <?php echo ($old['building_type'] ?? '') == 'Office' ? 'selected' : ''; ?>>Office</option>
                            <option value="Residential" <?php echo ($old['building_type'] ?? '') == 'Residential' ? 'selected' : ''; ?>>Residential</option>
                            <option value="Warehouse" <?php echo ($old['building_type'] ?? '') == 'Warehouse' ? 'selected' : ''; ?>>Warehouse</option>
                            <option value="Barracks" <?php echo ($old['building_type'] ?? '') == 'Barracks' ? 'selected' : ''; ?>>Barracks</option>
                            <option value="Training Facility" <?php echo ($old['building_type'] ?? '') == 'Training Facility' ? 'selected' : ''; ?>>Training Facility</option>
                            <option value="Medical Center" <?php echo ($old['building_type'] ?? '') == 'Medical Center' ? 'selected' : ''; ?>>Medical Center</option>
                            <option value="Other">Other</option>
                        </select>
                        <?php if (isset($errors['building_type'])): ?>
                            <small class="error-text"><?php echo $errors['building_type']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="required">Address / Location Description</label>
                    <textarea name="address" id="address" required rows="3" 
                              class="form-control <?php echo isset($errors['address']) ? 'error' : ''; ?>"
                              placeholder="Enter full address with landmarks"><?php echo Security::escape($old['address'] ?? ''); ?></textarea>
                    <?php if (isset($errors['address'])): ?>
                        <small class="error-text"><?php echo $errors['address']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Location Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-map-marker-alt"></i> Location Information</h3>
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
                        <label class="required">Command / Formation</label>
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
                        <label class="required">Local Government Area (LGA)</label>
                        <select name="lga_id" id="lga_id" required 
                                class="form-control <?php echo isset($errors['lga_id']) ? 'error' : ''; ?>">
                            <option value="">Select State First</option>
                        </select>
                        <?php if (isset($errors['lga_id'])): ?>
                            <small class="error-text"><?php echo $errors['lga_id']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Building Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-building"></i> Building Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Floor Count</label>
                        <input type="number" name="floor_count" value="<?php echo Security::escape($old['floor_count'] ?? ''); ?>" 
                               min="1" class="form-control" placeholder="Number of floors">
                    </div>
                    
                    <div class="form-group">
                        <label>Total Area (m²)</label>
                        <input type="number" step="0.01" name="total_area" value="<?php echo Security::escape($old['total_area'] ?? ''); ?>" 
                               class="form-control" placeholder="Total floor area">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Linked Land Asset</label>
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <input type="text" id="land_search" class="form-control" placeholder="Search land assets by code or address..." autocomplete="off">
                            <button type="button" id="btn_search_land" class="btn btn-primary" style="padding: 0 15px; background-color: var(--success-color); border-color: var(--success-color);"><i class="fas fa-search"></i> Search</button>
                        </div>
                        <select name="land_id" id="land_id" class="form-control">
                            <option value="">None (Standalone Building)</option>
                            <?php foreach ($landAssets as $land): ?>
                            <option value="<?php echo $land['id']; ?>" 
                                    <?php echo ($old['land_id'] ?? '') == $land['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($land['asset_code'] . ' - ' . $land['address']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Type keywords above and search to filter options</small>
                    </div>
                </div>
            </div>
            
            <!-- Construction Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-hard-hat"></i> Construction Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Contractor</label>
                        <input type="text" name="construction_contractor" 
                               value="<?php echo Security::escape($old['construction_contractor'] ?? ''); ?>" 
                               class="form-control" maxlength="255" placeholder="Construction company name">
                    </div>
                    
                    <div class="form-group">
                        <label>Contract Sum (₦)</label>
                        <input type="number" step="0.01" name="contract_sum" 
                               value="<?php echo Security::escape($old['contract_sum'] ?? ''); ?>" 
                               class="form-control" placeholder="Contract amount">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date Awarded</label>
                        <input type="date" name="date_awarded" value="<?php echo Security::escape($old['date_awarded'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Completion Date</label>
                        <input type="date" name="completion_date" value="<?php echo Security::escape($old['completion_date'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date Occupied</label>
                        <input type="date" name="date_occupied" value="<?php echo Security::escape($old['date_occupied'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Status & Maintenance -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-clipboard-check"></i> Status & Maintenance</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Condition Status</label>
                        <select name="condition_status" id="condition_status" required 
                                class="form-control <?php echo isset($errors['condition_status']) ? 'error' : ''; ?>">
                            <option value="">Select Condition</option>
                            <option value="Excellent" <?php echo ($old['condition_status'] ?? '') == 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                            <option value="Good" <?php echo ($old['condition_status'] ?? '') == 'Good' ? 'selected' : ''; ?>>Good</option>
                            <option value="Fair" <?php echo ($old['condition_status'] ?? '') == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                            <option value="Poor" <?php echo ($old['condition_status'] ?? '') == 'Poor' ? 'selected' : ''; ?>>Poor</option>
                            <option value="Under Maintenance" <?php echo ($old['condition_status'] ?? '') == 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                        </select>
                        <?php if (isset($errors['condition_status'])): ?>
                            <small class="error-text"><?php echo $errors['condition_status']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Maintenance Date</label>
                        <input type="date" name="last_maintenance_date" 
                               value="<?php echo Security::escape($old['last_maintenance_date'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Remarks -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-sticky-note"></i> Remarks</h3>
                </div>
                
                <div class="form-group">
                    <textarea name="remarks" rows="3" class="form-control" 
                              placeholder="Any additional remarks about the building"><?php echo Security::escape($old['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Document Upload Section - Professional Redesign -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-upload"></i> Document Upload</h3>
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
                    <i class="fas fa-save"></i> Save Building Asset
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm('buildingForm')">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
                <a href="<?php echo BASE_URL; ?>/buildings" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Professional Document Upload Styles */
.document-upload-container {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #D7E3DC;
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

.document-row:first-child {
    margin-top: 0;
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

.document-type-select:hover {
    border-color: #207027;
}

.document-type-select:focus {
    outline: none;
    border-color: #207027;
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

.btn-outline-primary i {
    font-size: 1rem;
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

.info-item i {
    font-size: 1rem;
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

/* Responsive */
@media (max-width: 768px) {
    .document-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .remove-document-btn {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    
    .upload-info {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
// Define base URL for API calls - use BASE_URL directly
const baseUrl = '<?php echo BASE_URL; ?>';
const documentTypes = <?php echo json_encode($documentTypes); ?>;

let documentCount = 0;

function debug(message, data = null) {
    console.log(message, data);
}

document.addEventListener('DOMContentLoaded', function() {
    debug('Page loaded');
    debug('Base URL:', baseUrl);
    
    // Add first document row by default
    addDocumentRow();
    
    // State to LGA dropdown
    const stateSelect = document.getElementById('state_id');
    const lgaSelect = document.getElementById('lga_id');
    
    if (stateSelect) {
        debug('State select found');
        
        stateSelect.addEventListener('change', function() {
            const stateId = this.value;
            debug('State selected:', stateId);
            
            if (!stateId) {
                lgaSelect.innerHTML = '<option value="">Select State First</option>';
                return;
            }
            
            // Construct the API URL correctly - ensure no double slashes
            const apiUrl = baseUrl.replace(/\/$/, '') + '/api/get_lgas.php?state_id=' + stateId;
            debug('Fetching LGAs from:', apiUrl);
            
            fetch(apiUrl)
                .then(response => {
                    debug('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    debug('Received data:', data);
                    
                    // Check if there's an error in the response
                    if (data.error) {
                        debug('API Error:', data.error);
                        lgaSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
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
                        debug('Added ' + data.length + ' LGAs');
                    } else {
                        lgaSelect.innerHTML = '<option value="">No LGAs found</option>';
                        debug('No LGAs found');
                    }
                    
                    <?php if (!empty($old['lga_id'])): ?>
                    lgaSelect.value = '<?php echo $old['lga_id']; ?>';
                    <?php endif; ?>
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    debug('Fetch Error:', error.message);
                    lgaSelect.innerHTML = '<option value="">Error loading LGAs: ' + error.message + '</option>';
                });
        });
        
        <?php if (!empty($old['state_id'])): ?>
        stateSelect.value = '<?php echo $old['state_id']; ?>';
        stateSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
    } else {
        debug('State select NOT found');
    }
    
    // Zone to Command dropdown
    const zoneSelect = document.getElementById('zone_id');
    const commandSelect = document.getElementById('command_id');
    
    if (zoneSelect) {
        debug('Zone select found');
        
        zoneSelect.addEventListener('change', function() {
            const zoneId = this.value;
            debug('Zone selected:', zoneId);
            
            if (!zoneId) {
                commandSelect.innerHTML = '<option value="">Select Zone First</option>';
                return;
            }
            
            // Construct the API URL correctly - ensure no double slashes
            const apiUrl = baseUrl.replace(/\/$/, '') + '/api/get_commands.php?zone_id=' + zoneId;
            debug('Fetching commands from:', apiUrl);
            
            fetch(apiUrl)
                .then(response => {
                    debug('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    debug('Received data:', data);
                    
                    // Check if there's an error in the response
                    if (data.error) {
                        debug('API Error:', data.error);
                        commandSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
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
                        debug('Added ' + data.length + ' commands');
                    } else {
                        commandSelect.innerHTML = '<option value="">No commands found</option>';
                        debug('No commands found');
                    }
                    
                    <?php if (!empty($old['command_id'])): ?>
                    commandSelect.value = '<?php echo $old['command_id']; ?>';
                    <?php endif; ?>
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    debug('Fetch Error:', error.message);
                    commandSelect.innerHTML = '<option value="">Error loading commands: ' + error.message + '</option>';
                });
        });
        
        <?php if (!empty($old['zone_id'])): ?>
        zoneSelect.value = '<?php echo $old['zone_id']; ?>';
        zoneSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
    } else {
        debug('Zone select NOT found');
    }
    
    // Purpose/Function other field
    const purposeSelect = document.getElementById('purpose_function');
    const purposeWrapper = document.getElementById('purposeOtherWrapper');
    const purposeOther = document.getElementById('purpose_other');
    
    if (purposeSelect) {
        purposeSelect.addEventListener('change', function() {
            if (this.value === 'Others') {
                purposeWrapper.style.display = 'block';
                purposeOther.required = true;
            } else {
                purposeWrapper.style.display = 'none';
                purposeOther.required = false;
                purposeOther.value = '';
            }
        });
        
        <?php if (($old['purpose_function'] ?? '') === 'Others'): ?>
        purposeWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
    // Building type other field
    const typeSelect = document.getElementById('building_type');
    const typeOtherWrapper = document.getElementById('typeOtherWrapper');
    const typeOther = document.getElementById('building_type_other');
    
    if (typeSelect && !typeOtherWrapper) {
        // Create other wrapper if it doesn't exist
        const wrapper = document.createElement('div');
        wrapper.id = 'typeOtherWrapper';
        wrapper.className = 'form-group';
        wrapper.style.display = 'none';
        wrapper.innerHTML = `
            <label>Specify Building Type</label>
            <input type="text" name="building_type_other" id="building_type_other" class="form-control" placeholder="Enter building type">
        `;
        typeSelect.parentNode.appendChild(wrapper);
        
        typeSelect.addEventListener('change', function() {
            const wrapper = document.getElementById('typeOtherWrapper');
            const otherInput = document.getElementById('building_type_other');
            if (this.value === 'Other') {
                wrapper.style.display = 'block';
                otherInput.required = true;
            } else {
                wrapper.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        });
        
        <?php if (($old['building_type'] ?? '') === 'Other'): ?>
        typeSelect.value = 'Other';
        typeSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
    }
    
    // AJAX search for Land Assets
    const landSearchInput = document.getElementById('land_search');
    const landSelect = document.getElementById('land_id');
    const searchLandBtn = document.getElementById('btn_search_land');
    
    if (landSearchInput && landSelect && searchLandBtn) {
        const performLandSearch = () => {
            const query = landSearchInput.value.trim();
            const apiUrl = baseUrl.replace(/\/$/, '') + '/api/get_land_assets?search=' + encodeURIComponent(query);
            
            searchLandBtn.disabled = true;
            searchLandBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
            
            fetch(apiUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP error');
                return res.json();
            })
            .then(data => {
                const currentValue = landSelect.value;
                landSelect.innerHTML = '<option value="">None (Standalone Building)</option>';
                
                if (data && data.length > 0) {
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = `${item.asset_code} - ${item.address}`;
                        landSelect.appendChild(option);
                    });
                    landSelect.value = currentValue;
                }
                
                landSelect.focus();
            })
            .catch(err => {
                console.error(err);
            })
            .finally(() => {
                searchLandBtn.disabled = false;
                searchLandBtn.innerHTML = '<i class="fas fa-search"></i> Search';
            });
        };
        
        searchLandBtn.addEventListener('click', performLandSearch);
        landSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performLandSearch();
            }
        });
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
    
    // Create dropdown options
    let options = '<option value="">Select Document Type</option>';
    for (const [value, label] of Object.entries(documentTypes)) {
        options += `<option value="${value}">${label}</option>`;
    }
    
    row.innerHTML = `
        <select name="document_types[]" class="document-type-select" onchange="updateDocumentLabel(this, '${rowId}')">
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

function updateDocumentLabel(select, rowId) {
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption.value) {
        select.style.borderColor = '#207027';
    }
}

function updateFileLabel(input, rowId) {
    const customDiv = document.getElementById('custom_' + rowId);
    if (customDiv) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const fileSize = (file.size / 1024 / 1024).toFixed(2); // Size in MB
            
            // Check file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('File ' + file.name + ' exceeds 5MB limit. Please choose a smaller file.');
                input.value = '';
                customDiv.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Choose file...</span>';
                return;
            }
            
            // Check file type
            const allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            const fileExt = file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(fileExt)) {
                alert('File type not allowed. Allowed types: PDF, JPG, PNG, DOC, DOCX');
                input.value = '';
                customDiv.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Choose file...</span>';
                return;
            }
            
            customDiv.innerHTML = `<i class="fas fa-check-circle text-success"></i><span>${file.name} (${fileSize} MB)</span>`;
            customDiv.style.borderColor = '#207027';
            customDiv.style.background = '#f0f9f4';
        } else {
            customDiv.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Choose file...</span>';
            customDiv.style.borderColor = '#D7E3DC';
            customDiv.style.background = '#f8fafc';
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

function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) return 'fa-file-pdf';
    if (mimeType.includes('image')) return 'fa-file-image';
    if (mimeType.includes('word')) return 'fa-file-word';
    if (mimeType.includes('excel')) return 'fa-file-excel';
    return 'fa-file';
}

function resetForm(formId) {
    if (confirm('Are you sure you want to reset the form? All unsaved data will be lost.')) {
        document.getElementById(formId).reset();
        
        // Reset document rows to just one empty row
        const container = document.getElementById('documentTypesContainer');
        container.innerHTML = '';
        documentCount = 0;
        addDocumentRow();
        
        // Reset dropdowns
        document.getElementById('lga_id').innerHTML = '<option value="">Select State First</option>';
        document.getElementById('command_id').innerHTML = '<option value="">Select Zone First</option>';
        
        // Hide other wrappers
        const purposeWrapper = document.getElementById('purposeOtherWrapper');
        if (purposeWrapper) {
            purposeWrapper.style.display = 'none';
        }
        
        const typeOtherWrapper = document.getElementById('typeOtherWrapper');
        if (typeOtherWrapper) {
            typeOtherWrapper.style.display = 'none';
        }
        
        if (typeof showNotification === 'function') {
            showNotification('info', 'Form has been reset');
        } else {
            alert('Form has been reset');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
