<?php
$title = 'Edit Project';
$active = 'projects';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$errors = Session::get('errors', []);
Session::remove('errors');

// Document types for the dropdown
$documentTypes = [
    'contract' => 'Contract Document',
    'drawing' => 'Architectural Drawing',
    'specification' => 'Specifications',
    'progress_report' => 'Progress Report',
    'payment' => 'Payment Certificate',
    'photo' => 'Project Photo',
    'other' => 'Other Document'
];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-edit"></i>
                Edit Project: <?php echo Security::escape($project['project_code']); ?>
            </h1>
            <p>Update project information</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/projects/show/<?php echo $project['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="<?php echo BASE_URL; ?>/projects" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/projects/update/<?php echo $project['id']; ?>" 
              enctype="multipart/form-data" id="projectForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Basic Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Project Code</label>
                        <input type="text" value="<?php echo Security::escape($project['project_code']); ?>" 
                               class="form-control" readonly disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Project Title</label>
                        <input type="text" name="project_title" 
                               value="<?php echo Security::escape($project['project_title']); ?>" 
                               required maxlength="255" 
                               class="form-control <?php echo isset($errors['project_title']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['project_title'])): ?>
                            <small class="error-text"><?php echo $errors['project_title']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Project Type</label>
                        <select name="project_type" id="project_type" required 
                                class="form-control <?php echo isset($errors['project_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="Construction" <?php echo $project['project_type'] == 'Construction' ? 'selected' : ''; ?>>Construction</option>
                            <option value="Renovation" <?php echo $project['project_type'] == 'Renovation' ? 'selected' : ''; ?>>Renovation</option>
                            <option value="Rehabilitation" <?php echo $project['project_type'] == 'Rehabilitation' ? 'selected' : ''; ?>>Rehabilitation</option>
                            <option value="Expansion" <?php echo $project['project_type'] == 'Expansion' ? 'selected' : ''; ?>>Expansion</option>
                            <option value="Infrastructure" <?php echo $project['project_type'] == 'Infrastructure' ? 'selected' : ''; ?>>Infrastructure</option>
                            <option value="Other">Other</option>
                        </select>
                        <?php if (isset($errors['project_type'])): ?>
                            <small class="error-text"><?php echo $errors['project_type']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="typeOtherWrapper" style="display: none;">
                        <label>Specify Project Type</label>
                        <input type="text" name="project_type_other" id="project_type_other" 
                               class="form-control" value="<?php echo !in_array($project['project_type'] ?? '', ['Construction', 'Renovation', 'Rehabilitation', 'Expansion', 'Infrastructure']) ? Security::escape($project['project_type'] ?? '') : ''; ?>"
                               placeholder="Enter project type">
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
                                    <?php echo $project['state_id'] == $state['id'] ? 'selected' : ''; ?>>
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
                                    <?php echo $project['zone_id'] == $zone['id'] ? 'selected' : ''; ?>>
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
            </div>
            
            <!-- Contract Details -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-contract"></i> Contract Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Contractor</label>
                        <input type="text" name="contractor" 
                               value="<?php echo Security::escape($project['contractor'] ?? ''); ?>" 
                               maxlength="255" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Contract Sum (₦)</label>
                        <input type="number" step="0.01" name="contract_sum" id="contract_sum"
                               value="<?php echo Security::escape($project['contract_sum'] ?? ''); ?>" 
                               required class="form-control <?php echo isset($errors['contract_sum']) ? 'error' : ''; ?>"
                               oninput="calculateProjectBalance()">
                        <?php if (isset($errors['contract_sum'])): ?>
                            <small class="error-text"><?php echo $errors['contract_sum']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Amount Paid (₦)</label>
                        <input type="number" step="0.01" name="amount_paid" id="amount_paid"
                               value="<?php echo Security::escape($project['amount_paid'] ?? '0.00'); ?>" 
                               class="form-control" placeholder="Amount paid so far" oninput="calculateProjectBalance()">
                    </div>
                    
                    <div class="form-group">
                        <label>Balance (₦)</label>
                        <input type="number" step="0.01" name="balance" id="balance" readonly
                               value="<?php echo Security::escape($project['balance'] ?? '0.00'); ?>" 
                               class="form-control bg-light" placeholder="Auto-calculated balance">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date Awarded</label>
                        <input type="date" name="date_awarded" 
                               value="<?php echo Security::escape($project['date_awarded'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Expected Completion Date</label>
                        <input type="date" name="expected_completion_date" id="expected_completion_date"
                               value="<?php echo Security::escape($project['expected_completion_date'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Funding Source</label>
                        <select name="source_funding" id="source_funding" required 
                                class="form-control <?php echo isset($errors['source_funding']) ? 'error' : ''; ?>">
                            <option value="">Select Funding Source</option>
                            <option value="Capital Appropriation" <?php echo ($project['source_funding'] ?? '') == 'Capital Appropriation' ? 'selected' : ''; ?>>Capital Appropriation</option>
                            <option value="Special Intervention" <?php echo ($project['source_funding'] ?? '') == 'Special Intervention' ? 'selected' : ''; ?>>Special Intervention</option>
                            <option value="Donor" <?php echo ($project['source_funding'] ?? '') == 'Donor' ? 'selected' : ''; ?>>Donor</option>
                            <option value="IGR" <?php echo ($project['source_funding'] ?? '') == 'IGR' ? 'selected' : ''; ?>>IGR</option>
                            <option value="Other">Other</option>
                        </select>
                        <?php if (isset($errors['source_funding'])): ?>
                            <small class="error-text"><?php echo $errors['source_funding']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="fundingOtherWrapper" style="display: none;">
                        <label>Specify Funding Source</label>
                        <input type="text" name="funding_other" id="funding_other" 
                               class="form-control" value="<?php echo !in_array($project['source_funding'] ?? '', ['Capital Appropriation', 'Special Intervention', 'Donor', 'IGR']) ? Security::escape($project['source_funding'] ?? '') : ''; ?>"
                               placeholder="Enter funding source">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Supervising Officer</label>
                        <input type="text" name="supervising_officer" 
                               value="<?php echo Security::escape($project['supervising_officer'] ?? ''); ?>" 
                               maxlength="255" pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                               class="form-control <?php echo isset($errors['supervising_officer']) ? 'error' : ''; ?>" placeholder="Officer in charge">
                        <?php if (isset($errors['supervising_officer'])): ?>
                            <small class="error-text"><?php echo $errors['supervising_officer']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Progress Tracking -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-chart-line"></i> Progress Tracking</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Physical Progress (%)</label>
                        <input type="number" name="physical_progress" id="physical_progress"
                               value="<?php echo Security::escape($project['physical_progress'] ?? '0'); ?>" 
                               min="0" max="100" step="0.01" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Financial Progress (%)</label>
                        <input type="number" name="financial_progress" id="financial_progress"
                               value="<?php echo Security::escape($project['financial_progress'] ?? '0'); ?>" 
                               min="0" max="100" step="0.01" class="form-control">
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
                        <label class="required">Project Status</label>
                        <select name="status" id="status" required 
                                class="form-control <?php echo isset($errors['status']) ? 'error' : ''; ?>">
                            <option value="">Select Status</option>
                            <option value="Planning" <?php echo ($project['status'] ?? '') == 'Planning' ? 'selected' : ''; ?>>Planning</option>
                            <option value="In Progress" <?php echo ($project['status'] ?? '') == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="On Hold" <?php echo ($project['status'] ?? '') == 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                            <option value="Completed" <?php echo ($project['status'] ?? '') == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo ($project['status'] ?? '') == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <small class="error-text"><?php echo $errors['status']; ?></small>
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
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($project['remarks'] ?? ''); ?></textarea>
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
                    <i class="fas fa-save"></i> Update Project
                </button>
                <a href="<?php echo BASE_URL; ?>/projects/show/<?php echo $project['id']; ?>" class="btn btn-outline">
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
    box-shadow: 0 2px 8px rgba(32, 112, 39, 0.1);
}

.existing-document-card .document-preview {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    background: #eef7f0;
    color: #207027;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.existing-document-card .document-details {
    flex: 1;
    min-width: 0;
}

.existing-document-card .document-name {
    font-weight: 500;
    color: #1e293b;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;

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
    
    // Project type other field
    const typeSelect = document.getElementById('project_type');
    const typeWrapper = document.getElementById('typeOtherWrapper');
    const typeOther = document.getElementById('project_type_other');
    
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
        
        <?php if (!in_array($project['project_type'] ?? '', ['Construction', 'Renovation', 'Rehabilitation', 'Expansion', 'Infrastructure'])): ?>
        typeSelect.value = 'Other';
        typeWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
    // Funding source other field
    const fundingSelect = document.getElementById('source_funding');
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
        
        <?php if (!in_array($project['source_funding'] ?? '', ['Capital Appropriation', 'Special Intervention', 'Donor', 'IGR'])): ?>
        fundingSelect.value = 'Other';
        fundingWrapper.style.display = 'block';
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
                        
                        <?php if (!empty($project['lga_id'])): ?>
                        lgaSelect.value = '<?php echo $project['lga_id']; ?>';
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
        
        <?php if (!empty($project['state_id'])): ?>
        stateSelect.value = '<?php echo $project['state_id']; ?>';
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
                        
                        <?php if (!empty($project['command_id'])): ?>
                        commandSelect.value = '<?php echo $project['command_id']; ?>';
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
        
        <?php if (!empty($project['zone_id'])): ?>
        zoneSelect.value = '<?php echo $project['zone_id']; ?>';
        zoneSelect.dispatchEvent(new Event('change'));
        <?php endif; ?>
    } else {
        debug('Zone select NOT found');
    }
    
    // Validate dates
    const dateAwarded = document.querySelector('input[name="date_awarded"]');
    const expectedCompletion = document.getElementById('expected_completion_date');
    
    if (dateAwarded && expectedCompletion) {
        dateAwarded.addEventListener('change', function() {
            if (this.value) {
                expectedCompletion.min = this.value;
            }
        });
        
        expectedCompletion.addEventListener('change', function() {
            if (dateAwarded.value && this.value < dateAwarded.value) {
                alert('Completion date cannot be before award date');
                this.value = dateAwarded.value;
            }
        });
    }
    
    // Add Document button click handler
    const addDocBtn = document.getElementById('addDocumentBtn');
    if (addDocBtn) {
        addDocBtn.addEventListener('click', function() {
            addDocumentRow();
        });
    }

    // Auto-calculate Balance and Financial Progress
    const csInput = document.getElementById('contract_sum');
    const apInput = document.getElementById('amount_paid');
    if (csInput) {
        csInput.addEventListener('input', calculateProjectBalance);
        csInput.addEventListener('keyup', calculateProjectBalance);
        csInput.addEventListener('change', calculateProjectBalance);
    }
    if (apInput) {
        apInput.addEventListener('input', calculateProjectBalance);
        apInput.addEventListener('keyup', calculateProjectBalance);
        apInput.addEventListener('change', calculateProjectBalance);
    }
    calculateProjectBalance();
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
    if (typeof documentTypes === 'object' && documentTypes !== null) {
        for (const [value, label] of Object.entries(documentTypes)) {
            options += `<option value="${value}">${label}</option>`;
        }
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
        document.getElementById('projectForm').appendChild(input);
        
        const docElement = document.getElementById('existing_doc_' + docId);
        if (docElement) {
            docElement.remove();
        }
        
        if (typeof showNotification === 'function') {
            showNotification('success', 'Document will be removed upon update');
        }
    }
}

function calculateProjectBalance() {
    const contractSumInput = document.getElementById('contract_sum');
    const amountPaidInput = document.getElementById('amount_paid');
    const balanceInput = document.getElementById('balance');
    const financialProgressInput = document.getElementById('financial_progress');

    if (!contractSumInput || !balanceInput) return;

    const contractSum = parseFloat(contractSumInput.value) || 0;
    const amountPaid = parseFloat(amountPaidInput ? amountPaidInput.value : 0) || 0;
    const balance = Math.max(0, contractSum - amountPaid);

    if (contractSum > 0 || amountPaid > 0) {
        balanceInput.value = balance.toFixed(2);
    } else {
        balanceInput.value = '';
    }

    if (financialProgressInput && contractSum > 0) {
        const finPercent = Math.min(100, Math.max(0, (amountPaid / contractSum) * 100));
        financialProgressInput.value = finPercent.toFixed(2);
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
