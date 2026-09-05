<?php
$title = 'Edit Motorcycle';
$active = 'fleet';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

$errors = Session::get('errors', []);
Session::remove('errors');

// Document types for the dropdown
$documentTypes = [
    'purchase_receipt' => 'Purchase Receipt',
    'insurance' => 'Insurance Document',
    'registration' => 'Registration Papers',
    'maintenance' => 'Maintenance Record',
    'photo' => 'Motorcycle Photo',
    'other' => 'Other Document'
];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-edit"></i>
                Edit Motorcycle: <?php echo Security::escape($motorcycle['asset_code']); ?>
            </h1>
            <p>Update motorcycle information</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/fleet/motorcycles/show/<?php echo $motorcycle['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="<?php echo BASE_URL; ?>/fleet/motorcycles" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Motorcycles
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/fleet/motorcycles/update/<?php echo $motorcycle['id']; ?>" 
              enctype="multipart/form-data" id="motorcycleForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Basic Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Asset Code</label>
                        <input type="text" value="<?php echo Security::escape($motorcycle['asset_code']); ?>" 
                               class="form-control" readonly disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Use Purpose</label>
                        <select name="use_purpose" required class="form-control <?php echo isset($errors['use_purpose']) ? 'error' : ''; ?>">
                            <option value="">Select Purpose</option>
                            <option value="Official" <?php echo ($motorcycle['use_purpose'] ?? '') == 'Official' ? 'selected' : ''; ?>>Official</option>
                            <option value="Operational" <?php echo ($motorcycle['use_purpose'] ?? '') == 'Operational' ? 'selected' : ''; ?>>Operational</option>
                            <option value="Pool" <?php echo ($motorcycle['use_purpose'] ?? '') == 'Pool' ? 'selected' : ''; ?>>Pool</option>
                            <option value="Reserved" <?php echo ($motorcycle['use_purpose'] ?? '') == 'Reserved' ? 'selected' : ''; ?>>Reserved</option>
                        </select>
                        <?php if (isset($errors['use_purpose'])): ?>
                            <small class="error-text"><?php echo $errors['use_purpose']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Ownership Type</label>
                        <select name="ownership_type" required class="form-control <?php echo isset($errors['ownership_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="FGN-Owned" <?php echo ($motorcycle['ownership_type'] ?? '') == 'FGN-Owned' ? 'selected' : ''; ?>>FGN Owned</option>
                            <option value="Donor" <?php echo ($motorcycle['ownership_type'] ?? '') == 'Donor' ? 'selected' : ''; ?>>Donor</option>
                            <option value="Leased" <?php echo ($motorcycle['ownership_type'] ?? '') == 'Leased' ? 'selected' : ''; ?>>Leased</option>
                        </select>
                        <?php if (isset($errors['ownership_type'])): ?>
                            <small class="error-text"><?php echo $errors['ownership_type']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Motorcycle Type</label>
                        <select name="motorcycle_type" id="motorcycle_type" required class="form-control <?php echo isset($errors['motorcycle_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="Trail" <?php echo ($motorcycle['motorcycle_type'] ?? '') == 'Trail' ? 'selected' : ''; ?>>Trail</option>
                            <option value="Street" <?php echo ($motorcycle['motorcycle_type'] ?? '') == 'Street' ? 'selected' : ''; ?>>Street</option>
                            <option value="Sport" <?php echo ($motorcycle['motorcycle_type'] ?? '') == 'Sport' ? 'selected' : ''; ?>>Sport</option>
                            <option value="Cruiser" <?php echo ($motorcycle['motorcycle_type'] ?? '') == 'Cruiser' ? 'selected' : ''; ?>>Cruiser</option>
                            <option value="Scooter" <?php echo ($motorcycle['motorcycle_type'] ?? '') == 'Scooter' ? 'selected' : ''; ?>>Scooter</option>
                            <option value="Other">Other</option>
                        </select>
                        <?php if (isset($errors['motorcycle_type'])): ?>
                            <small class="error-text"><?php echo $errors['motorcycle_type']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group" id="typeOtherWrapper" style="display: none;">
                    <label>Specify Motorcycle Type</label>
                    <input type="text" name="motorcycle_type_other" id="motorcycle_type_other" class="form-control" 
                           value="<?php echo !in_array($motorcycle['motorcycle_type'] ?? '', ['Trail', 'Street', 'Sport', 'Cruiser', 'Scooter']) ? Security::escape($motorcycle['motorcycle_type'] ?? '') : ''; ?>"
                           placeholder="Enter motorcycle type">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Make/Model</label>
                        <input type="text" name="make_model" value="<?php echo Security::escape($motorcycle['make_model'] ?? ''); ?>" 
                               required maxlength="100" class="form-control <?php echo isset($errors['make_model']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['make_model'])): ?>
                            <small class="error-text"><?php echo $errors['make_model']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Engine Capacity (cc)</label>
                        <input type="text" name="engine_capacity" value="<?php echo Security::escape($motorcycle['engine_capacity'] ?? ''); ?>" 
                               required maxlength="50" class="form-control <?php echo isset($errors['engine_capacity']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['engine_capacity'])): ?>
                            <small class="error-text"><?php echo $errors['engine_capacity']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Identification Numbers -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-hashtag"></i> Identification Numbers</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Chassis Number</label>
                        <input type="text" name="chassis_number" value="<?php echo Security::escape($motorcycle['chassis_number'] ?? ''); ?>" 
                               required maxlength="100" class="form-control <?php echo isset($errors['chassis_number']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['chassis_number'])): ?>
                            <small class="error-text"><?php echo $errors['chassis_number']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Engine Number</label>
                        <input type="text" name="engine_number" value="<?php echo Security::escape($motorcycle['engine_number'] ?? ''); ?>" 
                               required maxlength="100" class="form-control <?php echo isset($errors['engine_number']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['engine_number'])): ?>
                            <small class="error-text"><?php echo $errors['engine_number']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Registration Number</label>
                        <input type="text" name="registration_number" value="<?php echo Security::escape($motorcycle['registration_number'] ?? ''); ?>" 
                               required maxlength="50" class="form-control <?php echo isset($errors['registration_number']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['registration_number'])): ?>
                            <small class="error-text"><?php echo $errors['registration_number']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Fuel Type</label>
                        <select name="fuel_type" required class="form-control <?php echo isset($errors['fuel_type']) ? 'error' : ''; ?>">
                            <option value="">Select Fuel Type</option>
                            <option value="Petrol" <?php echo ($motorcycle['fuel_type'] ?? '') == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                            <option value="Diesel" <?php echo ($motorcycle['fuel_type'] ?? '') == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                            <option value="Electric" <?php echo ($motorcycle['fuel_type'] ?? '') == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                        </select>
                        <?php if (isset($errors['fuel_type'])): ?>
                            <small class="error-text"><?php echo $errors['fuel_type']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Current Mileage (km)</label>
                        <input type="number" name="current_mileage" value="<?php echo Security::escape($motorcycle['current_mileage'] ?? ''); ?>" 
                               required class="form-control <?php echo isset($errors['current_mileage']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['current_mileage'])): ?>
                            <small class="error-text"><?php echo $errors['current_mileage']; ?></small>
                        <?php endif; ?>
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
                        <label class="required">State</label>
                        <select name="state_id" id="state_id" required 
                                class="form-control <?php echo isset($errors['state_id']) ? 'error' : ''; ?>">
                            <option value="">Select State</option>
                            <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>" 
                                    <?php echo $motorcycle['state_id'] == $state['id'] ? 'selected' : ''; ?>>
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
                                    <?php echo $motorcycle['zone_id'] == $zone['id'] ? 'selected' : ''; ?>>
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
                
                <div class="form-group">
                    <label>Current Location / Garage</label>
                    <input type="text" name="current_location" value="<?php echo Security::escape($motorcycle['current_location'] ?? ''); ?>" 
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Assigned Officer/Unit</label>
                    <input type="text" name="assigned_officer_unit" value="<?php echo Security::escape($motorcycle['assigned_officer_unit'] ?? ''); ?>" 
                           class="form-control">
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
                            <option value="Purchase" <?php echo ($motorcycle['acquisition_type'] ?? '') == 'Purchase' ? 'selected' : ''; ?>>Purchase</option>
                            <option value="Transfer" <?php echo ($motorcycle['acquisition_type'] ?? '') == 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
                            <option value="Donation" <?php echo ($motorcycle['acquisition_type'] ?? '') == 'Donation' ? 'selected' : ''; ?>>Donation</option>
                            <option value="Lease" <?php echo ($motorcycle['acquisition_type'] ?? '') == 'Lease' ? 'selected' : ''; ?>>Lease</option>
                        </select>
                        <?php if (isset($errors['acquisition_type'])): ?>
                            <small class="error-text"><?php echo $errors['acquisition_type']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Acquisition Date</label>
                        <input type="date" name="acquisition_date" value="<?php echo Security::escape($motorcycle['acquisition_date'] ?? ''); ?>" 
                               required class="form-control <?php echo isset($errors['acquisition_date']) ? 'error' : ''; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['acquisition_date'])): ?>
                            <small class="error-text"><?php echo $errors['acquisition_date']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Purchase Value (₦)</label>
                        <input type="number" step="0.01" name="purchase_value" value="<?php echo Security::escape($motorcycle['purchase_value'] ?? ''); ?>" 
                               required class="form-control <?php echo isset($errors['purchase_value']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['purchase_value'])): ?>
                            <small class="error-text"><?php echo $errors['purchase_value']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Value (₦)</label>
                        <input type="number" step="0.01" name="current_value" value="<?php echo Security::escape($motorcycle['current_value'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
            </div>
            
            <!-- Condition & Insurance -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-clipboard-check"></i> Condition & Insurance</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Condition</label>
                        <select name="condition" required class="form-control <?php echo isset($errors['condition']) ? 'error' : ''; ?>">
                            <option value="">Select Condition</option>
                            <option value="Excellent" <?php echo ($motorcycle['condition'] ?? '') == 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                            <option value="Good" <?php echo ($motorcycle['condition'] ?? '') == 'Good' ? 'selected' : ''; ?>>Good</option>
                            <option value="Fair" <?php echo ($motorcycle['condition'] ?? '') == 'Fair' ? 'selected' : ''; ?>>Fair</option>
                            <option value="Poor" <?php echo ($motorcycle['condition'] ?? '') == 'Poor' ? 'selected' : ''; ?>>Poor</option>
                        </select>
                        <?php if (isset($errors['condition'])): ?>
                            <small class="error-text"><?php echo $errors['condition']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Insurance Status</label>
                        <select name="insurance_status" id="insurance_status" required class="form-control <?php echo isset($errors['insurance_status']) ? 'error' : ''; ?>">
                            <option value="">Select Status</option>
                            <option value="Valid" <?php echo ($motorcycle['insurance_status'] ?? '') == 'Valid' ? 'selected' : ''; ?>>Valid</option>
                            <option value="Expired" <?php echo ($motorcycle['insurance_status'] ?? '') == 'Expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="Not Insured" <?php echo ($motorcycle['insurance_status'] ?? '') == 'Not Insured' ? 'selected' : ''; ?>>Not Insured</option>
                        </select>
                        <?php if (isset($errors['insurance_status'])): ?>
                            <small class="error-text"><?php echo $errors['insurance_status']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Insurance Expiry</label>
                        <input type="date" name="insurance_expiry" id="insurance_expiry" 
                               value="<?php echo Security::escape($motorcycle['insurance_expiry'] ?? ''); ?>" 
                               class="form-control">
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
                        <label>Last Serviced Date</label>
                        <input type="date" name="last_serviced_date" value="<?php echo Security::escape($motorcycle['last_serviced_date'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Next Service Due</label>
                        <input type="date" name="next_service_due" value="<?php echo Security::escape($motorcycle['next_service_due'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
            </div>
            
            <!-- Remarks -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-sticky-note"></i> Remarks</h3>
                </div>
                
                <div class="form-group">
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($motorcycle['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Existing Documents -->
            <?php if (!empty($documents)): ?>
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-alt"></i> Existing Documents</h3>
                </div>
                
                <div class="existing-documents-grid">
                    <?php foreach ($documents as $index => $doc): ?>
                    <div class="existing-document-card" id="existing_doc_<?php echo $doc['id']; ?>">
                        <input type="hidden" name="existing_docs[<?php echo $index; ?>][id]" value="<?php echo $doc['id']; ?>">
                        <div class="document-preview">
                            <?php
                            $fileExt = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                            $iconClass = 'fa-file';
                            if (in_array($fileExt, ['pdf'])) $iconClass = 'fa-file-pdf';
                            elseif (in_array($fileExt, ['jpg', 'jpeg', 'png'])) $iconClass = 'fa-file-image';
                            elseif (in_array($fileExt, ['doc', 'docx'])) $iconClass = 'fa-file-word';
                            ?>
                            <i class="fas <?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="document-details">
                            <div class="document-name"><?php echo Security::escape($doc['file_name']); ?></div>
                            <div class="document-meta">
                                <span class="document-size"><?php echo round($doc['file_size'] / 1024, 2); ?> KB</span>
                                <span class="document-type"><?php echo strtoupper($fileExt); ?></span>
                            </div>
                        </div>
                        <div class="document-actions">
                            <?php
                            $rawPath = str_replace('\\', '/', $doc['file_path']);
                            if (strpos($rawPath, 'htdocs/nis_ams/') !== false) {
                                $rel = substr($rawPath, strpos($rawPath, 'htdocs/nis_ams/') + strlen('htdocs/nis_ams/'));
                                $fileUrl = BASE_URL . '/' . ltrim($rel, '/');
                            } else {
                                $fileUrl = BASE_URL . '/' . ltrim($rawPath, '/');
                            }
                            ?>
                            <a href="<?php echo Security::escape($fileUrl); ?>" target="_blank" class="btn-icon" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button type="button" class="btn-icon delete" onclick="removeDocument(<?php echo $doc['id']; ?>)" title="Remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- New Document Upload Section -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-upload"></i> Add New Documents</h3>
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
                    <i class="fas fa-save"></i> Update Motorcycle
                </button>
                <a href="<?php echo BASE_URL; ?>/fleet/motorcycles/show/<?php echo $motorcycle['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Document Upload Styles */
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

/* Existing Documents Grid */
.existing-documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.existing-document-card {
    background: var(--surface);
    border: 1px solid #D7E3DC;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s ease;
}

.existing-document-card:hover {
    border-color: #207027;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.document-preview {
    width: 40px;
    height: 40px;
    border-radius: 6px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #207027;
    font-size: 1.5rem;
}

.document-details {
    flex: 1;
    min-width: 0;
}

.document-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.document-meta {
    display: flex;
    gap: 10px;
    font-size: 0.8rem;
}

.document-size {
    color: #64748b;
}

.document-type {
    background: #D7E3DC;
    padding: 2px 6px;
    border-radius: 4px;
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

@media (max-width: 768px) {
    .document-row {
        grid-template-columns: 1fr;
    }
    
    .existing-documents-grid {
        grid-template-columns: 1fr;
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
    
    // Motorcycle type other field
    const typeSelect = document.getElementById('motorcycle_type');
    const typeWrapper = document.getElementById('typeOtherWrapper');
    const typeOther = document.getElementById('motorcycle_type_other');
    
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
        
        <?php if (!in_array($motorcycle['motorcycle_type'] ?? '', ['Trail', 'Street', 'Sport', 'Cruiser', 'Scooter'])): ?>
        typeSelect.value = 'Other';
        typeWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
    // Insurance status toggle
    const insuranceStatus = document.getElementById('insurance_status');
    const insuranceExpiry = document.getElementById('insurance_expiry');
    
    if (insuranceStatus && insuranceExpiry) {
        insuranceStatus.addEventListener('change', function() {
            if (this.value === 'Valid') {
                insuranceExpiry.required = true;
            } else {
                insuranceExpiry.required = false;
            }
        });
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
                        
                        <?php if (!empty($motorcycle['lga_id'])): ?>
                        lgaSelect.value = '<?php echo $motorcycle['lga_id']; ?>';
                        <?php endif; ?>
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>';
                });
        });
        
        <?php if (!empty($motorcycle['state_id'])): ?>
        stateSelect.value = '<?php echo $motorcycle['state_id']; ?>';
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
                        
                        <?php if (!empty($motorcycle['command_id'])): ?>
                        commandSelect.value = '<?php echo $motorcycle['command_id']; ?>';
                        <?php endif; ?>
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    commandSelect.innerHTML = '<option value="">Error loading commands</option>';
                });
        });
        
        <?php if (!empty($motorcycle['zone_id'])): ?>
        zoneSelect.value = '<?php echo $motorcycle['zone_id']; ?>';
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
    if (!container) return;
    
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
            <input type="file" name="new_documents[]" class="file-input-element" 
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

function removeDocument(docId) {
    if (confirm('Are you sure you want to remove this document? This action cannot be undone.')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'remove_docs[]';
        input.value = docId;
        document.getElementById('motorcycleForm').appendChild(input);
        
        const docElement = document.getElementById('existing_doc_' + docId);
        if (docElement) {
            docElement.remove();
        }
        
        if (typeof showNotification === 'function') {
            showNotification('success', 'Document will be removed upon update');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
