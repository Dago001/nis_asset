<?php
$title = 'Add ICT Asset';
$active = 'ict';
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

// Document types for the dropdown
$documentTypes = [
    'invoice' => 'Purchase Documents/Invoice',
    'warranty' => 'Warranty Certificate',
    'manual' => 'Installation/Commissioning Certificate',
    'license' => 'Software License',
    'maintenance ' => 'Maintenance Agreement',
    'technical ' => 'Technical Specifications',
    'photo' => 'Asset Photo',
    'other' => 'Other Document'
];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Add New ICT Asset
            </h1>
            <p>Enter ICT asset details</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/ict" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to ICT Assets
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/ict/store" enctype="multipart/form-data" id="ictForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Basic Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Asset Description</label>
                        <input type="text" name="asset_description" 
                               value="<?php echo Security::escape($old['asset_description'] ?? ''); ?>" 
                               required maxlength="255" 
                               class="form-control <?php echo isset($errors['asset_description']) ? 'error' : ''; ?>"
                               placeholder="e.g., Dell Latitude 5420 Laptop">
                        <?php if (isset($errors['asset_description'])): ?>
                            <small class="error-text"><?php echo $errors['asset_description']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Asset Category</label>
                        <select name="asset_category" id="asset_category" required 
                                class="form-control <?php echo isset($errors['asset_category']) ? 'error' : ''; ?>">
                            <option value="">Select Category</option>
                            <option value="Hardware" <?php echo ($old['asset_category'] ?? '') == 'Hardware' ? 'selected' : ''; ?>>Hardware</option>
                            <option value="Software" <?php echo ($old['asset_category'] ?? '') == 'Software' ? 'selected' : ''; ?>>Software</option>
                            <option value="Network" <?php echo ($old['asset_category'] ?? '') == 'Network' ? 'selected' : ''; ?>>Network</option>
                            <option value="Server" <?php echo ($old['asset_category'] ?? '') == 'Server' ? 'selected' : ''; ?>>Server</option>
                            <option value="Peripheral" <?php echo ($old['asset_category'] ?? '') == 'Peripheral' ? 'selected' : ''; ?>>Peripheral</option>
                            <option value="Other">Other</option>
                        </select>
                        <?php if (isset($errors['asset_category'])): ?>
                            <small class="error-text"><?php echo $errors['asset_category']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group" id="categoryOtherWrapper" style="display: none;">
                    <label>Specify Category</label>
                    <input type="text" name="asset_category_other" id="asset_category_other" 
                           class="form-control" value="<?php echo Security::escape($old['asset_category_other'] ?? ''); ?>" placeholder="Enter category">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Manufacturer</label>
                        <input type="text" name="manufacturer" 
                               value="<?php echo Security::escape($old['manufacturer'] ?? ''); ?>" 
                               maxlength="255" class="form-control" placeholder="e.g., Dell, HP, Cisco">
                    </div>
                    
                    <div class="form-group">
                        <label>Model/Version</label>
                        <input type="text" name="model_version" 
                               value="<?php echo Security::escape($old['model_version'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="e.g., Latitude 5420, v2.1">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Serial Number</label>
                        <input type="text" name="serial_number" id="serial_number" 
                               value="<?php echo Security::escape($old['serial_number'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="Manufacturer serial number">
                        <div id="serialValidation" class="form-hint"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Asset Tag/Code</label>
                        <input type="text" name="asset_code" 
                               value="<?php echo Security::escape($old['asset_code'] ?? ''); ?>" 
                               maxlength="50" class="form-control" placeholder="Internal asset tag">
                    </div>
                </div>
            </div>
            
            <!-- Technical Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-microchip"></i> Technical Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>IP Address</label>
                        <input type="text" name="ip_address" 
                               value="<?php echo Security::escape($old['ip_address'] ?? ''); ?>" 
                               maxlength="45" class="form-control" placeholder="e.g., 192.168.1.100">
                    </div>
                    
                    <div class="form-group">
                        <label>MAC Address</label>
                        <input type="text" name="mac_address" 
                               value="<?php echo Security::escape($old['mac_address'] ?? ''); ?>" 
                               maxlength="17" class="form-control" placeholder="e.g., 00:1B:44:11:3A:B7">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Operating System</label>
                        <input type="text" name="operating_system" 
                               value="<?php echo Security::escape($old['operating_system'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="e.g., Windows 11, Ubuntu 22.04">
                    </div>
                    
                    <div class="form-group">
                        <label>Software License</label>
                        <input type="text" name="software_license" 
                               value="<?php echo Security::escape($old['software_license'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="License key or reference">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>License Expiry Date</label>
                        <input type="date" name="license_expiry" 
                               value="<?php echo Security::escape($old['license_expiry'] ?? ''); ?>" 
                               class="form-control">
                    </div>
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
                
                <div class="form-group">
                    <label>Specific Location / Room</label>
                    <input type="text" name="location" 
                           value="<?php echo Security::escape($old['location'] ?? ''); ?>" 
                           class="form-control" placeholder="e.g., Server Room, Office 205">
                </div>
                
                <div class="form-group">
                    <label>Responsible Officer</label>
                    <input type="text" name="responsible_officer" 
                           value="<?php echo Security::escape($old['responsible_officer'] ?? ''); ?>" 
                           pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                           class="form-control <?php echo isset($errors['responsible_officer']) ? 'error' : ''; ?>" placeholder="Officer in charge">
                    <?php if (isset($errors['responsible_officer'])): ?>
                        <small class="error-text"><?php echo $errors['responsible_officer']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Acquisition Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Acquisition Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Purchase Date</label>
                        <input type="date" name="purchase_date" 
                               value="<?php echo Security::escape($old['purchase_date'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Purchase Value (₦)</label>
                        <input type="number" step="0.01" name="purchase_value" 
                               value="<?php echo Security::escape($old['purchase_value'] ?? ''); ?>" 
                               class="form-control" placeholder="Purchase amount">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Current Value (₦)</label>
                        <input type="number" step="0.01" name="current_value" 
                               value="<?php echo Security::escape($old['current_value'] ?? ''); ?>" 
                               class="form-control" placeholder="Current depreciated value">
                    </div>
                    
                    <div class="form-group">
                        <label>Ownership Type</label>
                        <select name="ownership_type" class="form-control">
                            <option value="">Select Ownership</option>
                            <option value="FGN" <?php echo ($old['ownership_type'] ?? '') == 'FGN' ? 'selected' : ''; ?>>FGN</option>
                            <option value="Donor" <?php echo ($old['ownership_type'] ?? '') == 'Donor' ? 'selected' : ''; ?>>Donor</option>
                            <option value="Leased" <?php echo ($old['ownership_type'] ?? '') == 'Leased' ? 'selected' : ''; ?>>Leased</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Warranty & Maintenance -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-shield-alt"></i> Warranty & Maintenance</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Warranty Period</label>
                        <input type="text" name="warranty_period" 
                               value="<?php echo Security::escape($old['warranty_period'] ?? ''); ?>" 
                               class="form-control" placeholder="e.g., 3 years">
                    </div>
                    
                    <div class="form-group">
                        <label>Warranty Expiry</label>
                        <input type="date" name="warranty_expiry" 
                               value="<?php echo Security::escape($old['warranty_expiry'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Maintenance Provider</label>
                        <input type="text" name="maintenance_provider" 
                               value="<?php echo Security::escape($old['maintenance_provider'] ?? ''); ?>" 
                               class="form-control" placeholder="Service company">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Service Date</label>
                        <input type="date" name="last_service_date" 
                               value="<?php echo Security::escape($old['last_service_date'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Next Service Date</label>
                        <input type="date" name="next_service_date" 
                               value="<?php echo Security::escape($old['next_service_date'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
            </div>
            
            <!-- Status -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-clipboard-check"></i> Status</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Current Status</label>
                        <select name="current_status" required 
                                class="form-control <?php echo isset($errors['current_status']) ? 'error' : ''; ?>">
                            <option value="">Select Status</option>
                            <option value="Operational" <?php echo ($old['current_status'] ?? '') == 'Operational' ? 'selected' : ''; ?>>Operational</option>
                            <option value="Faulty" <?php echo ($old['current_status'] ?? '') == 'Faulty' ? 'selected' : ''; ?>>Faulty</option>
                            <option value="Under Maintenance" <?php echo ($old['current_status'] ?? '') == 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                            <option value="Decommissioned" <?php echo ($old['current_status'] ?? '') == 'Decommissioned' ? 'selected' : ''; ?>>Decommissioned</option>
                            <option value="Others" <?php echo ($old['current_status'] ?? '') == 'Others' ? 'selected' : ''; ?>>Others</option>
                        </select>
                        <?php if (isset($errors['current_status'])): ?>
                            <small class="error-text"><?php echo $errors['current_status']; ?></small>
                        <?php endif; ?>
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
                              placeholder="Any additional remarks about the asset"><?php echo Security::escape($old['remarks'] ?? ''); ?></textarea>
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
                    <i class="fas fa-save"></i> Save ICT Asset
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm('ictForm')">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
                <a href="<?php echo BASE_URL; ?>/ict" class="btn btn-outline">
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
// Define base URL for API calls
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
    
    // Category other field
    const categorySelect = document.getElementById('asset_category');
    const categoryWrapper = document.getElementById('categoryOtherWrapper');
    const categoryOther = document.getElementById('asset_category_other');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                categoryWrapper.style.display = 'block';
                categoryOther.required = true;
            } else {
                categoryWrapper.style.display = 'none';
                categoryOther.required = false;
            }
        });
        
        <?php if (($old['asset_category'] ?? '') === 'Other'): ?>
        categorySelect.value = 'Other';
        categoryWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
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
                        
                        <?php if (!empty($old['lga_id'])): ?>
                        lgaSelect.value = '<?php echo $old['lga_id']; ?>';
                        <?php endif; ?>
                    } else {
                        lgaSelect.innerHTML = '<option value="">No LGAs found</option>';
                        debug('No LGAs found');
                    }
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
                        
                        <?php if (!empty($old['command_id'])): ?>
                        commandSelect.value = '<?php echo $old['command_id']; ?>';
                        <?php endif; ?>
                    } else {
                        commandSelect.innerHTML = '<option value="">No commands found</option>';
                        debug('No commands found');
                    }
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
    
    // Add Document button click handler
    document.getElementById('addDocumentBtn').addEventListener('click', function() {
        addDocumentRow();
    });
    
    // Serial number validation (optional)
    const serialInput = document.getElementById('serial_number');
    const serialValidation = document.getElementById('serialValidation');
    let serialTimeout;
    
    if (serialInput) {
        serialInput.addEventListener('input', function() {
            clearTimeout(serialTimeout);
            const serial = this.value;
            
            if (serial.length < 3) {
                serialValidation.innerHTML = '';
                return;
            }
            
            serialTimeout = setTimeout(() => {
                fetch(baseUrl + '/api/validate_serial.php?type=ict&serial=' + encodeURIComponent(serial))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            serialValidation.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Serial number may already exist</span>';
                        } else {
                            serialValidation.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Serial number available</span>';
                        }
                    })
                    .catch(() => {});
            }, 500);
        });
    }
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
            
            // Check file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('File ' + file.name + ' exceeds 5MB limit. Please choose a smaller file.');
                input.value = '';
                customDiv.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Choose file...</span>';
                customDiv.style.borderColor = '#D7E3DC';
                customDiv.style.background = '#f8fafc';
                return;
            }
            
            // Check file type
            const allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            const fileExt = file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(fileExt)) {
                alert('File type not allowed. Allowed types: PDF, JPG, PNG, DOC, DOCX');
                input.value = '';
                customDiv.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Choose file...</span>';
                customDiv.style.borderColor = '#D7E3DC';
                customDiv.style.background = '#f8fafc';
                return;
            }
            
            customDiv.innerHTML = `<i class="fas fa-check-circle text-success"></i><span>${file.name} (${fileSizeMB} MB)</span>`;
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
        
        // Clear file list
        document.getElementById('fileList').innerHTML = '';
        
        // Reset document rows to just one empty row
        const container = document.getElementById('documentTypesContainer');
        container.innerHTML = '';
        documentCount = 0;
        addDocumentRow();
        
        // Reset dropdowns
        document.getElementById('lga_id').innerHTML = '<option value="">Select State First</option>';
        document.getElementById('command_id').innerHTML = '<option value="">Select Zone First</option>';
        
        // Hide other wrapper
        document.getElementById('categoryOtherWrapper').style.display = 'none';
        
        if (typeof showNotification === 'function') {
            showNotification('info', 'Form has been reset');
        } else {
            alert('Form has been reset');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
