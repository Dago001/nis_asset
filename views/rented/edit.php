<?php
$title = 'Edit Rented Property';
$active = 'rented';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$errors = Session::get('errors', []);
Session::remove('errors');

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
                <i class="fas fa-edit"></i>
                Edit Rented Property: <?php echo Security::escape($property['asset_code']); ?>
            </h1>
            <p>Update rented property information</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/rented/show/<?php echo $property['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="<?php echo BASE_URL; ?>/rented" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/rented/update/<?php echo $property['id']; ?>" 
              enctype="multipart/form-data" id="rentedForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Location Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-map-marker-alt"></i> Location Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Property Code</label>
                        <input type="text" value="<?php echo Security::escape($property['asset_code']); ?>" 
                               class="form-control" readonly disabled>
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
                                    <?php echo $property['state_id'] == $state['id'] ? 'selected' : ''; ?>>
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
                                    <?php echo $property['zone_id'] == $zone['id'] ? 'selected' : ''; ?>>
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
                              class="form-control <?php echo isset($errors['property_address']) ? 'error' : ''; ?>"><?php echo Security::escape($property['property_address']); ?></textarea>
                    <?php if (isset($errors['property_address'])): ?>
                        <small class="error-text"><?php echo $errors['property_address']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Property Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-info-circle"></i> Property Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Owner / Lessor Name</label>
                        <input type="text" name="owner_lessor_name" 
                               value="<?php echo Security::escape($property['owner_lessor_name']); ?>" 
                               required maxlength="255" pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                               class="form-control <?php echo isset($errors['owner_lessor_name']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['owner_lessor_name'])): ?>
                            <small class="error-text"><?php echo $errors['owner_lessor_name']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Alphabets, spaces, hyphens (-), and apostrophes (') only</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Owner Contact</label>
                        <input type="text" name="owner_contact" 
                               value="<?php echo Security::escape($property['owner_contact'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="Contact person">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Owner Phone</label>
                        <input type="tel" name="owner_phone"
                               value="<?php echo Security::escape($property['owner_phone'] ?? ''); ?>"
                               minlength="11" maxlength="11" inputmode="numeric" pattern="\d{11}" title="Phone number must be exactly 11 digits"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                               class="form-control <?php echo isset($errors['owner_phone']) ? 'error' : ''; ?>" placeholder="Phone number (11 digits)">
                        <?php if (isset($errors['owner_phone'])): ?>
                            <small class="error-text"><?php echo $errors['owner_phone']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Must be exactly 11 digits (e.g. 08012345678)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Owner Email</label>
                        <input type="email" name="owner_email" 
                               value="<?php echo Security::escape($property['owner_email'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="Email address">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Purpose / Use</label>
                        <select name="purpose" id="purpose" required 
                                class="form-control <?php echo isset($errors['purpose']) ? 'error' : ''; ?>">
                            <option value="">Select Purpose</option>
                            <option value="Office Space" <?php echo $property['purpose'] == 'Office Space' ? 'selected' : ''; ?>>Office Space</option>
                            <option value="Residential" <?php echo $property['purpose'] == 'Residential' ? 'selected' : ''; ?>>Residential</option>
                            <option value="Staff Quarters" <?php echo $property['purpose'] == 'Staff Quarters' ? 'selected' : ''; ?>>Staff Quarters</option>
                            <option value="Warehouse" <?php echo $property['purpose'] == 'Warehouse' ? 'selected' : ''; ?>>Warehouse</option>
                            <option value="Training Facility" <?php echo $property['purpose'] == 'Training Facility' ? 'selected' : ''; ?>>Training Facility</option>
                            <option value="Other">Others</option>
                        </select>
                        <?php if (isset($errors['purpose'])): ?>
                            <small class="error-text"><?php echo $errors['purpose']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="purposeOtherWrapper" style="display: none;">
                        <label>Specify Other Purpose</label>
                        <input type="text" name="purpose_other" id="purpose_other" class="form-control" 
                               value="<?php echo !in_array($property['purpose'] ?? '', ['Office Space', 'Residential', 'Staff Quarters', 'Warehouse', 'Training Facility']) ? Security::escape($property['purpose'] ?? '') : ''; ?>">
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
                        <label class="required">Start Date</label>
                        <input type="date" name="start_date" 
                               value="<?php echo Security::escape($property['start_date']); ?>" 
                               required class="form-control <?php echo isset($errors['start_date']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['start_date'])): ?>
                            <small class="error-text"><?php echo $errors['start_date']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Expiry Date</label>
                        <input type="date" name="expiry_date" 
                               value="<?php echo Security::escape($property['expiry_date']); ?>" 
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
                               value="<?php echo Security::escape($property['annual_rent']); ?>" 
                               required class="form-control <?php echo isset($errors['annual_rent']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['annual_rent'])): ?>
                            <small class="error-text"><?php echo $errors['annual_rent']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Lease Agreement Reference</label>
                        <input type="text" name="lease_agreement_ref" 
                               value="<?php echo Security::escape($property['lease_agreement_ref'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="Agreement number">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Funding Source</label>
                        <select name="funding_source" id="funding_source" class="form-control">
                            <option value="">Select Funding Source</option>
                            <option value="Capital Appropriation" <?php echo ($property['funding_source'] ?? '') == 'Capital Appropriation' ? 'selected' : ''; ?>>Capital Appropriation</option>
                            <option value="Overhead" <?php echo ($property['funding_source'] ?? '') == 'Overhead' ? 'selected' : ''; ?>>Overhead</option>
                            <option value="Special Intervention" <?php echo ($property['funding_source'] ?? '') == 'Special Intervention' ? 'selected' : ''; ?>>Special Intervention</option>
                            <option value="IGR" <?php echo ($property['funding_source'] ?? '') == 'IGR' ? 'selected' : ''; ?>>IGR</option>
                            <option value="Donor" <?php echo ($property['funding_source'] ?? '') == 'Donor' ? 'selected' : ''; ?>>Donor</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="fundingOtherWrapper" style="display: none;">
                        <label>Specify Other Funding Source</label>
                        <input type="text" name="funding_other" id="funding_other" class="form-control"
                               value="<?php echo !in_array($property['funding_source'] ?? '', ['Capital Appropriation', 'Overhead', 'Special Intervention', 'IGR', 'Donor']) ? Security::escape($property['funding_source'] ?? '') : ''; ?>">
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
                        <label class="required">Property Status</label>
                        <select name="status" id="status" required 
                                class="form-control <?php echo isset($errors['status']) ? 'error' : ''; ?>">
                            <option value="">Select Status</option>
                            <option value="Active" <?php echo ($property['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Under Renewal" <?php echo ($property['status'] ?? '') == 'Under Renewal' ? 'selected' : ''; ?>>Under Renewal</option>
                            <option value="Expired" <?php echo ($property['status'] ?? '') == 'Expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="Terminated" <?php echo ($property['status'] ?? '') == 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
                            <option value="Others" <?php echo ($property['status'] ?? '') == 'Others' ? 'selected' : ''; ?>>Others</option>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <small class="error-text"><?php echo $errors['status']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($property['remarks'] ?? ''); ?></textarea>
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
                    <i class="fas fa-save"></i> Update Property
                </button>
                <a href="<?php echo BASE_URL; ?>/rented/show/<?php echo $property['id']; ?>" class="btn btn-outline">
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
                        
                        <?php if (!empty($property['lga_id'])): ?>
                        lgaSelect.value = '<?php echo $property['lga_id']; ?>';
                        <?php endif; ?>
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>';
                });
        });
        
        <?php if (!empty($property['state_id'])): ?>
        stateSelect.value = '<?php echo $property['state_id']; ?>';
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
                        
                        <?php if (!empty($property['command_id'])): ?>
                        commandSelect.value = '<?php echo $property['command_id']; ?>';
                        <?php endif; ?>
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    commandSelect.innerHTML = '<option value="">Error loading commands</option>';
                });
        });
        
        <?php if (!empty($property['zone_id'])): ?>
        zoneSelect.value = '<?php echo $property['zone_id']; ?>';
        zoneSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
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
        
        <?php if (!in_array($property['purpose'] ?? '', ['Office Space', 'Residential', 'Staff Quarters', 'Warehouse', 'Training Facility'])): ?>
        purposeSelect.value = 'Other';
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
        
        <?php if (!in_array($property['funding_source'] ?? '', ['Capital Appropriation', 'Overhead', 'Special Intervention', 'IGR', 'Donor'])): ?>
        fundingSelect.value = 'Other';
        fundingWrapper.style.display = 'block';
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

function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) return 'fa-file-pdf';
    if (mimeType.includes('image')) return 'fa-file-image';
    if (mimeType.includes('word')) return 'fa-file-word';
    if (mimeType.includes('excel')) return 'fa-file-excel';
    return 'fa-file';
}

function removeDocument(docId) {
    if (confirm('Are you sure you want to remove this document? This action cannot be undone.')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'remove_docs[]';
        input.value = docId;
        document.getElementById('rentedForm').appendChild(input);
        
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
