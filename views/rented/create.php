<?php
$title = 'Add Rented Property';
$active = 'rented';
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
    'tenancy_agreement' => 'Tenancy Agreement',
    'receipts' => 'Payment Receipts',
    'valuation_report' => 'Valuation Report',
    'photos' => 'Property Photos'
];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Add New Rented Property
            </h1>
            <p>Enter leased/rented property details</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/rented" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/rented/store" enctype="multipart/form-data" id="rentedForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Location Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-map-marker-alt"></i> Location Information</h3>
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
                
                <div class="form-group">
                    <label class="required">Property Address</label>
                    <textarea name="property_address" required rows="3" 
                              class="form-control <?php echo isset($errors['property_address']) ? 'error' : ''; ?>"
                              placeholder="Enter complete property address"><?php echo Security::escape($old['property_address'] ?? ''); ?></textarea>
                    <?php if (isset($errors['property_address'])): ?>
                        <small class="error-text"><?php echo $errors['property_address']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Lessor Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-user-tie"></i> Lessor Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Owner / Lessor Name</label>
                        <input type="text" name="owner_lessor_name" 
                               value="<?php echo Security::escape($old['owner_lessor_name'] ?? ''); ?>" 
                               required maxlength="255" pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                               class="form-control <?php echo isset($errors['owner_lessor_name']) ? 'error' : ''; ?>"
                               placeholder="Full name of property owner">
                        <?php if (isset($errors['owner_lessor_name'])): ?>
                            <small class="error-text"><?php echo $errors['owner_lessor_name']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Alphabets, spaces, hyphens (-), and apostrophes (') only</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Lessor Phone</label>
                        <input type="tel" name="owner_phone"
                               value="<?php echo Security::escape($old['owner_phone'] ?? ''); ?>"
                               minlength="11" maxlength="11" inputmode="numeric" pattern="\d{11}" title="Phone number must be exactly 11 digits"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                               class="form-control <?php echo isset($errors['owner_phone']) ? 'error' : ''; ?>" placeholder="Contact phone number (11 digits)">
                        <?php if (isset($errors['owner_phone'])): ?>
                            <small class="error-text"><?php echo $errors['owner_phone']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Must be exactly 11 digits (e.g. 08012345678)</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Lessor Email</label>
                        <input type="email" name="owner_email" 
                               value="<?php echo Security::escape($old['owner_email'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="Contact email">
                    </div>
                    
                    <div class="form-group">
                        <label>Lessor Address</label>
                        <input type="text" name="owner_address" 
                               value="<?php echo Security::escape($old['owner_address'] ?? ''); ?>" 
                               maxlength="255" class="form-control" placeholder="Lessor's address">
                    </div>
                </div>
            </div>
            
            <!-- Lease Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-contract"></i> Lease Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Purpose / Use</label>
                        <select name="purpose" id="purpose" required 
                                class="form-control <?php echo isset($errors['purpose']) ? 'error' : ''; ?>">
                            <option value="">Select Purpose</option>
                            <option value="Office" <?php echo ($old['purpose'] ?? '') == 'Office' ? 'selected' : ''; ?>>Office</option>
                            <option value="Residential" <?php echo ($old['purpose'] ?? '') == 'Residential' ? 'selected' : ''; ?>>Residential</option>
                            <option value="Warehouse" <?php echo ($old['purpose'] ?? '') == 'Warehouse' ? 'selected' : ''; ?>>Warehouse</option>
                            <option value="Staff Quarters" <?php echo ($old['purpose'] ?? '') == 'Staff Quarters' ? 'selected' : ''; ?>>Staff Quarters</option>
                            <option value="Training Facility" <?php echo ($old['purpose'] ?? '') == 'Training Facility' ? 'selected' : ''; ?>>Training Facility</option>
                            <option value="Other">Other</option>
                        </select>
                        <?php if (isset($errors['purpose'])): ?>
                            <small class="error-text"><?php echo $errors['purpose']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="purposeOtherWrapper" style="display: none;">
                        <label>Specify Other Purpose</label>
                        <input type="text" name="purpose_other" id="purpose_other" 
                               class="form-control" placeholder="Enter purpose" value="<?php echo Security::escape($old['purpose_other'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Lease Start Date</label>
                        <input type="date" name="start_date" 
                               value="<?php echo Security::escape($old['start_date'] ?? ''); ?>" 
                               required class="form-control <?php echo isset($errors['start_date']) ? 'error' : ''; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['start_date'])): ?>
                            <small class="error-text"><?php echo $errors['start_date']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Lease Expiry Date</label>
                        <input type="date" name="expiry_date" 
                               value="<?php echo Security::escape($old['expiry_date'] ?? ''); ?>" 
                               required class="form-control <?php echo isset($errors['expiry_date']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['expiry_date'])): ?>
                            <small class="error-text"><?php echo $errors['expiry_date']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Annual Rent (₦)</label>
                        <input type="number" step="0.01" name="annual_rent" 
                               value="<?php echo Security::escape($old['annual_rent'] ?? ''); ?>" 
                               required class="form-control <?php echo isset($errors['annual_rent']) ? 'error' : ''; ?>"
                               placeholder="Enter annual rent amount">
                        <?php if (isset($errors['annual_rent'])): ?>
                            <small class="error-text"><?php echo $errors['annual_rent']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Lease Agreement Reference</label>
                        <input type="text" name="lease_agreement_ref" 
                               value="<?php echo Security::escape($old['lease_agreement_ref'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="Contract/Agreement number">
                    </div>
                </div>
            </div>
            
            <!-- Financial Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-money-bill-wave"></i> Financial Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Source of Funding</label>
                        <select name="funding_source" id="funding_source" class="form-control">
                            <option value="">Select Source</option>
                            <option value="Capital" <?php echo ($old['funding_source'] ?? '') == 'Capital' ? 'selected' : ''; ?>>Capital Appropriation</option>
                            <option value="Overhead" <?php echo ($old['funding_source'] ?? '') == 'Overhead' ? 'selected' : ''; ?>>Overhead/Expenditure</option>
                            <option value="Special Intervention" <?php echo ($old['funding_source'] ?? '') == 'Special Intervention' ? 'selected' : ''; ?>>Special Intervention</option>
                            <option value="IGR" <?php echo ($old['funding_source'] ?? '') == 'IGR' ? 'selected' : ''; ?>>Internal Generated Revenue (IGR)</option>
                            <option value="Donation" <?php echo ($old['funding_source'] ?? '') == 'Donation' ? 'selected' : ''; ?>>Donation</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="fundingOtherWrapper" style="display: none;">
                        <label>Specify Other Funding Source</label>
                        <input type="text" name="funding_other" id="funding_other" 
                               class="form-control" placeholder="Enter funding source" value="<?php echo Security::escape($old['funding_other'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Status & Remarks -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-clipboard-check"></i> Status & Remarks</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Property Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">Select Status</option>
                            <option value="Active" <?php echo ($old['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Under Renewal" <?php echo ($old['status'] ?? '') == 'Under Renewal' ? 'selected' : ''; ?>>Under Renewal</option>
                            <option value="Terminated" <?php echo ($old['status'] ?? '') == 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
                            <option value="Expired" <?php echo ($old['status'] ?? '') == 'Expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="Others" <?php echo ($old['status'] ?? '') == 'Others' ? 'selected' : ''; ?>>Others</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="3" class="form-control" 
                              placeholder="Any additional remarks about the property"><?php echo Security::escape($old['remarks'] ?? ''); ?></textarea>
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
                    <i class="fas fa-save"></i> Save Property
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm('rentedForm')">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
                <a href="<?php echo BASE_URL; ?>/rented" class="btn btn-outline">
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
            
            // Construct the API URL correctly
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
            
            // Construct the API URL correctly
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
    
    // Purpose other field
    const purposeSelect = document.getElementById('purpose');
    const purposeWrapper = document.getElementById('purposeOtherWrapper');
    const purposeOther = document.getElementById('purpose_other');
    
    if (purposeSelect) {
        purposeSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                purposeWrapper.style.display = 'block';
                purposeOther.required = true;
            } else {
                purposeWrapper.style.display = 'none';
                purposeOther.required = false;
            }
        });
        
        <?php if (($old['purpose'] ?? '') === 'Other'): ?>
        purposeWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
    // Funding source other field
    const fundingSelect = document.getElementById('funding_source');
    const fundingWrapper = document.getElementById('fundingOtherWrapper');
    const fundingOther = document.getElementById('funding_other');
    
    if (fundingSelect) {
        fundingSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                fundingWrapper.style.display = 'block';
                fundingOther.required = true;
            } else {
                fundingWrapper.style.display = 'none';
                fundingOther.required = false;
            }
        });
        
        <?php if (($old['funding_source'] ?? '') === 'Other'): ?>
        fundingWrapper.style.display = 'block';
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
        
        // Reset document rows to just one empty row
        const container = document.getElementById('documentTypesContainer');
        container.innerHTML = '';
        documentCount = 0;
        addDocumentRow();
        
        // Reset dropdowns
        document.getElementById('lga_id').innerHTML = '<option value="">Select State First</option>';
        document.getElementById('command_id').innerHTML = '<option value="">Select Zone First</option>';
        
        // Hide other wrappers
        document.getElementById('purposeOtherWrapper').style.display = 'none';
        document.getElementById('fundingOtherWrapper').style.display = 'none';
        
        if (typeof showNotification === 'function') {
            showNotification('info', 'Form has been reset');
        } else {
            alert('Form has been reset');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
