<?php
$title = 'Edit Land Asset';
$active = 'land';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$errors = Session::get('errors', []);
Session::remove('errors');

// Document types for the dropdown
$documentTypes = [
    'c_of_o' => 'Certificate of Occupancy (C of O)',
    'r_of_o' => 'Right of Occupancy (R of O)',
    'allocation_letter' => 'Allocation Letter',
    'survey_plan' => 'Survey Plan',
    'receipts' => 'Receipts (if any)',
    'pictures' => 'Upload Pictures'
];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-edit"></i>
                Edit Land Asset: <?php echo Security::escape($asset['asset_code']); ?>
            </h1>
            <p>Update land asset information</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/land/show/<?php echo $asset['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="<?php echo BASE_URL; ?>/land" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/land/update/<?php echo $asset['id']; ?>" 
              enctype="multipart/form-data" id="landForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Basic Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Asset Code</label>
                        <input type="text" value="<?php echo Security::escape($asset['asset_code']); ?>" 
                               class="form-control" readonly disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Ownership Type</label>
                        <select name="ownership_type" id="ownership_type" required 
                                class="form-control <?php echo isset($errors['ownership_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="FGN" <?php echo $asset['ownership_type'] == 'FGN' ? 'selected' : ''; ?>>FGN (NIS)</option>
                            <option value="State" <?php echo $asset['ownership_type'] == 'State' ? 'selected' : ''; ?>>State</option>
                            <option value="Private" <?php echo $asset['ownership_type'] == 'Private' ? 'selected' : ''; ?>>Private</option>
                        </select>
                        <?php if (isset($errors['ownership_type'])): ?>
                            <small class="error-text"><?php echo $errors['ownership_type']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Title Holder</label>
                        <input type="text" name="title_holder" value="<?php echo Security::escape($asset['title_holder']); ?>" 
                               required maxlength="255" class="form-control <?php echo isset($errors['title_holder']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['title_holder'])): ?>
                            <small class="error-text"><?php echo $errors['title_holder']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="required">Address / Location Description</label>
                    <textarea name="address" required rows="3" 
                              class="form-control <?php echo isset($errors['address']) ? 'error' : ''; ?>"><?php echo Security::escape($asset['address']); ?></textarea>
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
            
            <!-- Land Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-ruler-combined"></i> Land Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Size / Area</label>
                        <input type="number" step="0.01" name="size" value="<?php echo Security::escape($asset['size']); ?>" 
                               required class="form-control <?php echo isset($errors['size']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['size'])): ?>
                            <small class="error-text"><?php echo $errors['size']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Size Unit</label>
                        <select name="size_unit" required class="form-control <?php echo isset($errors['size_unit']) ? 'error' : ''; ?>">
                            <option value="">Select Unit</option>
                            <option value="m²" <?php echo $asset['size_unit'] == 'm²' ? 'selected' : ''; ?>>Square Meter (m²)</option>
                            <option value="hectares" <?php echo $asset['size_unit'] == 'hectares' ? 'selected' : ''; ?>>Hectares</option>
                            <option value="acres" <?php echo $asset['size_unit'] == 'acres' ? 'selected' : ''; ?>>Acres</option>
                            <option value="sq_ft" <?php echo $asset['size_unit'] == 'sq_ft' ? 'selected' : ''; ?>>Square Feet</option>
                        </select>
                        <?php if (isset($errors['size_unit'])): ?>
                            <small class="error-text"><?php echo $errors['size_unit']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Survey Plan Number</label>
                        <input type="text" name="survey_plan_no" value="<?php echo Security::escape($asset['survey_plan_no'] ?? ''); ?>" 
                               maxlength="100" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Certificate of Occupancy (C of O) Number</label>
                        <input type="text" name="certificate_of_occupancy_no" 
                               value="<?php echo Security::escape($asset['certificate_of_occupancy_no'] ?? ''); ?>" 
                               maxlength="100" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Purpose / Use</label>
                        <select name="purpose_use" id="purpose_use" class="form-control">
                            <option value="">Select Purpose</option>
                            <option value="Command / Formation" <?php echo ($asset['purpose_use'] ?? '') == 'Command / Formation' ? 'selected' : ''; ?>>Command / Formation</option>
                            <option value="Area Office" <?php echo ($asset['purpose_use'] ?? '') == 'Area Office' ? 'selected' : ''; ?>>Area Office</option>
                            <option value="Divisional Office" <?php echo ($asset['purpose_use'] ?? '') == 'Divisional Office' ? 'selected' : ''; ?>>Divisional Office</option>
                            <option value="PPT Office" <?php echo ($asset['purpose_use'] ?? '') == 'PPT Office' ? 'selected' : ''; ?>>PPT Office</option>
                            <option value="Barracks/Transit Camp" <?php echo ($asset['purpose_use'] ?? '') == 'Barracks/Transit Camp' ? 'selected' : ''; ?>>Barracks/Transit Camp</option>
                            <option value="Flag House" <?php echo ($asset['purpose_use'] ?? '') == 'Flag House' ? 'selected' : ''; ?>>Flag House</option>
                            <option value="Zonal Command" <?php echo ($asset['purpose_use'] ?? '') == 'Zonal Command' ? 'selected' : ''; ?>>Zonal Command</option>
                            <option value="Migrant Holding Center" <?php echo ($asset['purpose_use'] ?? '') == 'Migrant Holding Center' ? 'selected' : ''; ?>>Migrant Holding Center</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="purposeOtherWrapper" style="display: none;">
                        <label>Specify Other Purpose</label>
                        <input type="text" name="purpose_other" id="purpose_other" class="form-control" 
                               value="<?php echo !in_array($asset['purpose_use'] ?? '', ['Command / Formation', 'Area Office', 'Divisional Office', 'PPT Office', 'Barracks/Transit Camp', 'Flag House', 'Zonal Command', 'Migrant Holding Center']) ? Security::escape($asset['purpose_use'] ?? '') : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Date Acquired</label>
                        <input type="date" name="date_acquired" value="<?php echo Security::escape($asset['date_acquired'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
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
                        <label>Encumbrance</label>
                        <textarea name="encumbrance" rows="2" class="form-control"><?php echo Security::escape($asset['encumbrance'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">Select Status</option>
                            <option value="Developed" <?php echo ($asset['status'] ?? '') == 'Developed' ? 'selected' : ''; ?>>Developed</option>
                            <option value="Undeveloped" <?php echo ($asset['status'] ?? '') == 'Undeveloped' ? 'selected' : ''; ?>>Undeveloped</option>
                            <option value="Fenced" <?php echo ($asset['status'] ?? '') == 'Fenced' ? 'selected' : ''; ?>>Fenced</option>
                            <option value="Not Fenced" <?php echo ($asset['status'] ?? '') == 'Not Fenced' ? 'selected' : ''; ?>>Not Fenced</option>
                            <option value="Under Litigation" <?php echo ($asset['status'] ?? '') == 'Under Litigation' ? 'selected' : ''; ?>>Under Litigation</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($asset['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- GPS Coordinates -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-map-pin"></i> GPS Coordinates (Optional)</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Latitude</label>
                        <input type="number" step="0.00000001" name="latitude" 
                               value="<?php echo Security::escape($asset['latitude'] ?? ''); ?>" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Longitude</label>
                        <input type="number" step="0.00000001" name="longitude" 
                               value="<?php echo Security::escape($asset['longitude'] ?? ''); ?>" class="form-control">
                    </div>
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
                        <button type="button" class="btn-outline-primary" id="addDocumentBtn">
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
                    <i class="fas fa-save"></i> Update Land Asset
                </button>
                <a href="<?php echo BASE_URL; ?>/land/show/<?php echo $asset['id']; ?>" class="btn btn-outline">
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

.document-actions {
    display: flex;
    gap: 5px;
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

document.addEventListener('DOMContentLoaded', function() {
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
                        
                        <?php if (!empty($asset['lga_id'])): ?>
                        lgaSelect.value = '<?php echo $asset['lga_id']; ?>';
                        <?php endif; ?>
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>';
                });
        });
        
        <?php if (!empty($asset['state_id'])): ?>
        stateSelect.value = '<?php echo $asset['state_id']; ?>';
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
                        
                        <?php if (!empty($asset['command_id'])): ?>
                        commandSelect.value = '<?php echo $asset['command_id']; ?>';
                        <?php endif; ?>
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    commandSelect.innerHTML = '<option value="">Error loading commands</option>';
                });
        });
        
        <?php if (!empty($asset['zone_id'])): ?>
        zoneSelect.value = '<?php echo $asset['zone_id']; ?>';
        zoneSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
    }
    
    // Purpose other field
    const purposeSelect = document.getElementById('purpose_use');
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
        
        <?php if (!in_array($asset['purpose_use'] ?? '', ['Command / Formation', 'Area Office', 'Divisional Office', 'PPT Office', 'Barracks/Transit Camp', 'Flag House', 'Zonal Command', 'Migrant Holding Center'])): ?>
        purposeWrapper.style.display = 'block';
        <?php endif; ?>
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
        <select name="document_types[]" class="document-type-select" onchange="this.style.borderColor='#207027'">
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
        document.getElementById('landForm').appendChild(input);
        
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
