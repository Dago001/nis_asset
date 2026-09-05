<?php
$title = 'Edit ICT Asset';
$active = 'ict';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$errors = Session::get('errors', []);
Session::remove('errors');

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
                <i class="fas fa-edit"></i>
                Edit ICT Asset: <?php echo Security::escape($asset['asset_code'] ?? $asset['asset_description']); ?>
            </h1>
            <p>Update ICT asset information</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/ict/show/<?php echo $asset['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="<?php echo BASE_URL; ?>/ict" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to ICT Assets
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/ict/update/<?php echo $asset['id']; ?>" 
              enctype="multipart/form-data" id="ictForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Basic Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Asset Code</label>
                        <input type="text" value="<?php echo Security::escape($asset['asset_code'] ?? ''); ?>" 
                               class="form-control" readonly disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Asset Description</label>
                        <input type="text" name="asset_description" 
                               value="<?php echo Security::escape($asset['asset_description']); ?>" 
                               required maxlength="255" 
                               class="form-control <?php echo isset($errors['asset_description']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['asset_description'])): ?>
                            <small class="error-text"><?php echo $errors['asset_description']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Asset Category</label>
                        <select name="asset_category" id="asset_category" required 
                                class="form-control <?php echo isset($errors['asset_category']) ? 'error' : ''; ?>">
                            <option value="">Select Category</option>
                            <option value="Hardware" <?php echo $asset['asset_category'] == 'Hardware' ? 'selected' : ''; ?>>Hardware</option>
                            <option value="Software" <?php echo $asset['asset_category'] == 'Software' ? 'selected' : ''; ?>>Software</option>
                            <option value="Network" <?php echo $asset['asset_category'] == 'Network' ? 'selected' : ''; ?>>Network</option>
                            <option value="Server" <?php echo $asset['asset_category'] == 'Server' ? 'selected' : ''; ?>>Server</option>
                            <option value="Peripheral" <?php echo $asset['asset_category'] == 'Peripheral' ? 'selected' : ''; ?>>Peripheral</option>
                            <option value="Other">Other</option>
                        </select>
                        <?php if (isset($errors['asset_category'])): ?>
                            <small class="error-text"><?php echo $errors['asset_category']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="categoryOtherWrapper" style="display: none;">
                        <label>Specify Category</label>
                        <input type="text" name="asset_category_other" id="asset_category_other" 
                               class="form-control" value="<?php echo !in_array($asset['asset_category'] ?? '', ['Hardware', 'Software', 'Network', 'Server', 'Peripheral']) ? Security::escape($asset['asset_category'] ?? '') : ''; ?>"
                               placeholder="Enter category">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Manufacturer</label>
                        <input type="text" name="manufacturer" 
                               value="<?php echo Security::escape($asset['manufacturer'] ?? ''); ?>" 
                               maxlength="255" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Model/Version</label>
                        <input type="text" name="model_version" 
                               value="<?php echo Security::escape($asset['model_version'] ?? ''); ?>" 
                               maxlength="100" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Serial Number</label>
                        <input type="text" name="serial_number" id="serial_number" 
                               value="<?php echo Security::escape($asset['serial_number'] ?? ''); ?>" 
                               maxlength="100" class="form-control">
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
                               value="<?php echo Security::escape($asset['ip_address'] ?? ''); ?>" 
                               maxlength="45" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>MAC Address</label>
                        <input type="text" name="mac_address" 
                               value="<?php echo Security::escape($asset['mac_address'] ?? ''); ?>" 
                               maxlength="17" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Operating System</label>
                        <input type="text" name="operating_system" 
                               value="<?php echo Security::escape($asset['operating_system'] ?? ''); ?>" 
                               maxlength="100" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Software License</label>
                        <input type="text" name="software_license" 
                               value="<?php echo Security::escape($asset['software_license'] ?? ''); ?>" 
                               maxlength="100" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>License Expiry Date</label>
                        <input type="date" name="license_expiry" 
                               value="<?php echo Security::escape($asset['license_expiry'] ?? ''); ?>" 
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
                        <label class="required">State</label>
                        <select name="state_id" id="state_id" required 
                                class="form-control <?php echo isset($errors['state_id']) ? 'error' : ''; ?>">
                            <option value="">Select State</option>
                            <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>" 
                                    <?php echo $asset['state_id'] == $state['id'] ? 'selected' : ''; ?>>
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
                                    <?php echo $asset['zone_id'] == $zone['id'] ? 'selected' : ''; ?>>
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
                    <label>Specific Location / Room</label>
                    <input type="text" name="location" 
                           value="<?php echo Security::escape($asset['location'] ?? ''); ?>" 
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Responsible Officer</label>
                    <input type="text" name="responsible_officer" 
                           value="<?php echo Security::escape($asset['responsible_officer'] ?? ''); ?>" 
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
                               value="<?php echo Security::escape($asset['purchase_date'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Purchase Value (₦)</label>
                        <input type="number" step="0.01" name="purchase_value" 
                               value="<?php echo Security::escape($asset['purchase_value'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Current Value (₦)</label>
                        <input type="number" step="0.01" name="current_value" 
                               value="<?php echo Security::escape($asset['current_value'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Ownership Type</label>
                        <select name="ownership_type" class="form-control">
                            <option value="">Select Ownership</option>
                            <option value="FGN" <?php echo ($asset['ownership_type'] ?? '') == 'FGN' ? 'selected' : ''; ?>>FGN</option>
                            <option value="Donor" <?php echo ($asset['ownership_type'] ?? '') == 'Donor' ? 'selected' : ''; ?>>Donor</option>
                            <option value="Leased" <?php echo ($asset['ownership_type'] ?? '') == 'Leased' ? 'selected' : ''; ?>>Leased</option>
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
                               value="<?php echo Security::escape($asset['warranty_period'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Warranty Expiry</label>
                        <input type="date" name="warranty_expiry" 
                               value="<?php echo Security::escape($asset['warranty_expiry'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Maintenance Provider</label>
                        <input type="text" name="maintenance_provider" 
                               value="<?php echo Security::escape($asset['maintenance_provider'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Service Date</label>
                        <input type="date" name="last_service_date" 
                               value="<?php echo Security::escape($asset['last_service_date'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Next Service Date</label>
                        <input type="date" name="next_service_date" 
                               value="<?php echo Security::escape($asset['next_service_date'] ?? ''); ?>" 
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
                            <option value="In Use" <?php echo ($asset['current_status'] ?? '') == 'In Use' ? 'selected' : ''; ?>>In Use</option>
                            <option value="Available" <?php echo ($asset['current_status'] ?? '') == 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="Under Repair" <?php echo ($asset['current_status'] ?? '') == 'Under Repair' ? 'selected' : ''; ?>>Under Repair</option>
                            <option value="Decommissioned" <?php echo ($asset['current_status'] ?? '') == 'Decommissioned' ? 'selected' : ''; ?>>Decommissioned</option>
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
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($asset['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Existing Documents Section - Professional Redesign -->
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
                            <a href="<?php echo BASE_URL . '/' . $doc['file_path']; ?>" target="_blank" class="btn-icon" title="View">
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
            
            <!-- New Document Upload Section - Professional Redesign -->
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
                    <i class="fas fa-save"></i> Update ICT Asset
                </button>
                <a href="<?php echo BASE_URL; ?>/ict/show/<?php echo $asset['id']; ?>" class="btn btn-outline">
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
    
    .existing-documents-grid {
        grid-template-columns: 1fr;
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
        
        <?php if (!in_array($asset['asset_category'] ?? '', ['Hardware', 'Software', 'Network', 'Server', 'Peripheral'])): ?>
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
                        
                        <?php if (!empty($asset['lga_id'])): ?>
                        lgaSelect.value = '<?php echo $asset['lga_id']; ?>';
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
        
        <?php if (!empty($asset['state_id'])): ?>
        stateSelect.value = '<?php echo $asset['state_id']; ?>';
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
                        
                        <?php if (!empty($asset['command_id'])): ?>
                        commandSelect.value = '<?php echo $asset['command_id']; ?>';
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
        
        <?php if (!empty($asset['zone_id'])): ?>
        zoneSelect.value = '<?php echo $asset['zone_id']; ?>';
        zoneSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
    } else {
        debug('Zone select NOT found');
    }
    
    // Initialize document upload
    addDocumentRow();
    
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

function removeDocument(docId) {
    if (confirm('Are you sure you want to remove this document? This action cannot be undone.')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'remove_docs[]';
        input.value = docId;
        document.getElementById('ictForm').appendChild(input);
        
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
