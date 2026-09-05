<?php
$title = 'Add New Ammunition';
$active = 'ammunition';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$old = Session::get('old', []);
$errors = Session::get('errors', []);
Session::remove('old');
Session::remove('errors');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Add New Ammunition
            </h1>
            <p>Enter ammunition details - All fields marked with * are required</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/ammunition" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Ammunition
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/ammunition/store" id="ammoForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Basic Identification -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-id-card"></i> Basic Identification</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Ammunition ID</label>
                        <input type="text" name="ammo_id" id="ammo_id" 
                               value="<?php echo Security::escape($old['ammo_id'] ?? 'AMMO-'); ?>" 
                               required maxlength="50" 
                               class="form-control <?php echo isset($errors['ammo_id']) ? 'error' : ''; ?>"
                               placeholder="AMMO-001">
                        <?php if (isset($errors['ammo_id'])): ?>
                            <small class="error-text"><?php echo $errors['ammo_id']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Unique ammunition identifier</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Batch Number</label>
                        <input type="text" name="batch_number" id="batch_number" 
                               value="<?php echo Security::escape($old['batch_number'] ?? ''); ?>" 
                               required maxlength="100" 
                               class="form-control <?php echo isset($errors['batch_number']) ? 'error' : ''; ?>"
                               placeholder="Manufacturing batch number">
                        <?php if (isset($errors['batch_number'])): ?>
                            <small class="error-text"><?php echo $errors['batch_number']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Type and Calibre -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-bullseye"></i> Type & Calibre</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Ammunition Type</label>
                        <select name="ammo_type_id" id="ammo_type_id" required
                                class="form-control <?php echo isset($errors['ammo_type_id']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <?php foreach ($ammoTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" 
                                    <?php echo ($old['ammo_type_id'] ?? '') == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($type['ammo_type']); ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="other">Other (Specify)</option>
                        </select>
                        <?php if (isset($errors['ammo_type_id'])): ?>
                            <small class="error-text"><?php echo $errors['ammo_type_id']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="typeOtherWrapper" style="display: none;">
                        <label>Specify Type</label>
                        <input type="text" name="ammo_type_other" id="ammo_type_other" 
                               class="form-control" value="<?php echo Security::escape($old['ammo_type_other'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Calibre</label>
                        <select name="calibre_id" id="calibre_id" required
                                class="form-control <?php echo isset($errors['calibre_id']) ? 'error' : ''; ?>">
                            <option value="">Select Calibre</option>
                            <?php foreach ($calibres as $calibre): ?>
                            <option value="<?php echo $calibre['id']; ?>" 
                                    <?php echo ($old['calibre_id'] ?? '') == $calibre['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($calibre['calibre']); ?>
                                <?php if (!empty($calibre['rounds_per_unit'])): ?>
                                    (<?php echo $calibre['rounds_per_unit']; ?> rounds/unit)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="other">Other (Specify)</option>
                        </select>
                        <?php if (isset($errors['calibre_id'])): ?>
                            <small class="error-text"><?php echo $errors['calibre_id']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group" id="calibreOtherWrapper" style="display: none;">
                    <label>Specify Calibre</label>
                    <input type="text" name="calibre_other" id="calibre_other" 
                           class="form-control" value="<?php echo Security::escape($old['calibre_other'] ?? ''); ?>">
                </div>
            </div>
            
            <!-- Quantity Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-calculator"></i> Quantity Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Quantity Received</label>
                        <input type="number" name="quantity_received" id="quantity_received" 
                               value="<?php echo Security::escape($old['quantity_received'] ?? ''); ?>" 
                               required min="1" 
                               class="form-control <?php echo isset($errors['quantity_received']) ? 'error' : ''; ?>"
                               placeholder="Number of units">
                        <?php if (isset($errors['quantity_received'])): ?>
                            <small class="error-text"><?php echo $errors['quantity_received']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Storage Form</label>
                        <select name="storage_form" id="storage_form" required
                                class="form-control <?php echo isset($errors['storage_form']) ? 'error' : ''; ?>">
                            <option value="">Select Form</option>
                            <option value="Rounds" <?php echo ($old['storage_form'] ?? '') == 'Rounds' ? 'selected' : ''; ?>>Rounds</option>
                            <option value="Boxes" <?php echo ($old['storage_form'] ?? '') == 'Boxes' ? 'selected' : ''; ?>>Boxes</option>
                            <option value="Crates" <?php echo ($old['storage_form'] ?? '') == 'Crates' ? 'selected' : ''; ?>>Crates</option>
                            <option value="Pallets" <?php echo ($old['storage_form'] ?? '') == 'Pallets' ? 'selected' : ''; ?>>Pallets</option>
                        </select>
                        <?php if (isset($errors['storage_form'])): ?>
                            <small class="error-text"><?php echo $errors['storage_form']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Storage Location -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-map-marker-alt"></i> Storage Location</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Storage Location</label>
                        <select name="storage_location" id="storage_location" required
                                class="form-control <?php echo isset($errors['storage_location']) ? 'error' : ''; ?>">
                            <option value="">Select Location</option>
                            <option value="HQ Armoury" <?php echo ($old['storage_location'] ?? '') == 'HQ Armoury' ? 'selected' : ''; ?>>HQ Armoury / Service Headquarters</option>
                            <option value="Zonal Armoury" <?php echo in_array($old['storage_location'] ?? '', ['Zonal Armoury', 'Zonal Armony']) ? 'selected' : ''; ?>>Zonal Armoury</option>
                            <option value="Command Armoury" <?php echo in_array($old['storage_location'] ?? '', ['Command Armoury', 'Command Armony']) ? 'selected' : ''; ?>>Command Armoury</option>
                            <option value="Other" <?php echo ($old['storage_location'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <?php if (isset($errors['storage_location'])): ?>
                            <small class="error-text"><?php echo $errors['storage_location']; ?></small>
                        <?php endif; ?>
                    </div>

                    <!-- Zonal Armoury Dropdown -->
                    <div class="form-group" id="zonalWrapper" style="<?php echo in_array($old['storage_location'] ?? '', ['Zonal Armoury', 'Zonal Armony']) ? '' : 'display: none;'; ?>">
                        <label class="required">Zonal Command</label>
                        <select name="zone_id" id="zone_id" class="form-control">
                            <option value="">Select Zonal Command (Zone A - H)</option>
                            <?php foreach ($zones as $zone): ?>
                            <option value="<?php echo $zone['id']; ?>" <?php echo ($old['zone_id'] ?? '') == $zone['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($zone['zone_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Command Armoury Dropdown -->
                    <div class="form-group" id="commandWrapper" style="<?php echo in_array($old['storage_location'] ?? '', ['Command Armoury', 'Command Armony']) ? '' : 'display: none;'; ?>">
                        <label class="required">Command / Formation</label>
                        <select name="command_id" id="command_id" class="form-control">
                            <option value="">Select State Command (All 36 States & Special Commands)</option>
                            <?php foreach ($commands as $command): ?>
                            <option value="<?php echo $command['id']; ?>" <?php echo ($old['command_id'] ?? '') == $command['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($command['command_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Other Location Text Input -->
                    <div class="form-group" id="locationOtherWrapper" style="<?php echo ($old['storage_location'] ?? '') == 'Other' ? '' : 'display: none;'; ?>">
                        <label class="required">Specify Storage Location </label>
                        <input type="text" name="storage_location_other" id="storage_location_other" 
                               class="form-control" value="<?php echo Security::escape($old['storage_location_other'] ?? ''); ?>"
                               placeholder="Enter custom location or manual details">
                    </div>
                </div>
            </div>
            
            <!-- Manufacturer & Expiry -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-industry"></i> Manufacturer & Expiry</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Manufacturer</label>
                        <input type="text" name="manufacturer" value="<?php echo Security::escape($old['manufacturer'] ?? ''); ?>" 
                               maxlength="100" class="form-control" placeholder="e.g., Nigerian Defence Industries">
                    </div>
                    
                    <div class="form-group">
                        <label>Date Manufactured</label>
                        <input type="date" name="date_manufactured" value="<?php echo Security::escape($old['date_manufactured'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date" id="expiry_date" 
                               value="<?php echo Security::escape($old['expiry_date'] ?? ''); ?>" 
                               class="form-control">
                        <small class="form-hint">Critical for live ammunition</small>
                    </div>
                </div>
            </div>
            
            <!-- Condition & Remarks -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-clipboard-check"></i> Condition & Remarks</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Condition</label>
                        <select name="condition" required
                                class="form-control <?php echo isset($errors['condition']) ? 'error' : ''; ?>">
                            <option value="">Select Condition</option>
                            <option value="Serviceable" <?php echo ($old['condition'] ?? '') == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                            <option value="Unserviceable" <?php echo ($old['condition'] ?? '') == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                            <option value="Condemned" <?php echo ($old['condition'] ?? '') == 'Condemned' ? 'selected' : ''; ?>>Condemned</option>
                        </select>
                        <?php if (isset($errors['condition'])): ?>
                            <small class="error-text"><?php echo $errors['condition']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="3" class="form-control" 
                              placeholder="Any additional remarks about the ammunition"><?php echo Security::escape($old['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-save"></i> Save Ammunition
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm('ammoForm')">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
                <a href="<?php echo BASE_URL; ?>/ammunition" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Form Section Styles */
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
    border-bottom: 1px solid #D7E3DC;
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
    color: #134617;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Form Grid */
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
    color: #134617;
    margin-bottom: 8px;
}

.required::after {
    content: " *";
    color: #B42318;
}

.form-control {
    padding: 10px 12px;
    border: 1px solid #D7E3DC;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #207027;
    box-shadow: 0 0 0 3px rgba(32, 112, 39, 0.2);
}

.form-control.error {
    border-color: #B42318;
    background-color: #fff5f5;
}

.error-text {
    color: #B42318;
    font-size: 0.85rem;
    margin-top: 5px;
}

.form-hint {
    font-size: 0.8rem;
    color: #53665E;
    margin-top: 5px;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #D7E3DC;
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
    background: #207027;
    color: white;
}

.btn-success:hover {
    background: #134617;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(32, 112, 39, 0.3);
}

.btn-secondary {
    background: #53665E;
    color: white;
}

.btn-secondary:hover {
    background: #6c757d;
}

.btn-outline {
    background: transparent;
    color: #53665E;
    border: 1px solid #D7E3DC;
}

.btn-outline:hover {
    background: var(--light-bg);
    color: #134617;
    border-color: #207027;
}

.submit-btn {
    min-width: 150px;
}

/* Responsive */
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
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ammunition type other field
    const typeSelect = document.getElementById('ammo_type_id');
    const typeWrapper = document.getElementById('typeOtherWrapper');
    const typeOther = document.getElementById('ammo_type_other');
    
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                typeWrapper.style.display = 'block';
                typeOther.required = true;
            } else {
                typeWrapper.style.display = 'none';
                typeOther.required = false;
                typeOther.value = '';
            }
        });
        
        <?php if (($old['ammo_type_id'] ?? '') === 'other'): ?>
        typeSelect.value = 'other';
        typeWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
    // Calibre other field
    const calibreSelect = document.getElementById('calibre_id');
    const calibreWrapper = document.getElementById('calibreOtherWrapper');
    const calibreOther = document.getElementById('calibre_other');
    
    if (calibreSelect) {
        calibreSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                calibreWrapper.style.display = 'block';
                calibreOther.required = true;
            } else {
                calibreWrapper.style.display = 'none';
                calibreOther.required = false;
                calibreOther.value = '';
            }
        });
        
        <?php if (($old['calibre_id'] ?? '') === 'other'): ?>
        calibreSelect.value = 'other';
        calibreWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
    // Storage location dependent fields
    const locationSelect = document.getElementById('storage_location');
    const zonalWrapper = document.getElementById('zonalWrapper');
    const commandWrapper = document.getElementById('commandWrapper');
    const locationWrapper = document.getElementById('locationOtherWrapper');
    const locationOther = document.getElementById('storage_location_other');
    const zoneInput = document.getElementById('zone_id');
    const commandInput = document.getElementById('command_id');
    
    function toggleStorageLocation(val) {
        const v = (val || '').toLowerCase().trim();
        if (v.includes('zonal')) {
            if (zonalWrapper) zonalWrapper.style.display = 'block';
            if (commandWrapper) commandWrapper.style.display = 'none';
            if (locationWrapper) locationWrapper.style.display = 'none';
            if (zoneInput) zoneInput.required = true;
            if (commandInput) commandInput.required = false;
            if (locationOther) locationOther.required = false;
        } else if (v.includes('command')) {
            if (zonalWrapper) zonalWrapper.style.display = 'none';
            if (commandWrapper) commandWrapper.style.display = 'block';
            if (locationWrapper) locationWrapper.style.display = 'none';
            if (zoneInput) zoneInput.required = false;
            if (commandInput) commandInput.required = true;
            if (locationOther) locationOther.required = false;
        } else if (v.includes('other')) {
            if (zonalWrapper) zonalWrapper.style.display = 'none';
            if (commandWrapper) commandWrapper.style.display = 'none';
            if (locationWrapper) locationWrapper.style.display = 'block';
            if (zoneInput) zoneInput.required = false;
            if (commandInput) commandInput.required = false;
            if (locationOther) locationOther.required = true;
        } else {
            if (zonalWrapper) zonalWrapper.style.display = 'none';
            if (commandWrapper) commandWrapper.style.display = 'none';
            if (locationWrapper) locationWrapper.style.display = 'none';
            if (zoneInput) zoneInput.required = false;
            if (commandInput) commandInput.required = false;
            if (locationOther) locationOther.required = false;
        }
    }
    
    window.toggleStorageLocation = toggleStorageLocation;

    if (locationSelect) {
        locationSelect.addEventListener('change', function() {
            toggleStorageLocation(this.value);
        });
        
        toggleStorageLocation(locationSelect.value);
    }

    // Ammunition ID Auto Prefix "AMMO-"
    const ammoIdInput = document.getElementById('ammo_id');
    let ammoIdTimeout;
    
    if (ammoIdInput) {
        if (!ammoIdInput.value) {
            ammoIdInput.value = 'AMMO-';
        }
        
        ammoIdInput.addEventListener('focus', function() {
            if (!this.value) {
                this.value = 'AMMO-';
            }
        });

        ammoIdInput.addEventListener('input', function() {
            let val = this.value;
            if (!val.startsWith('AMMO-')) {
                let clean = val.replace(/^(ammo-?|ammo)?/i, '');
                this.value = 'AMMO-' + clean;
            }

            clearTimeout(ammoIdTimeout);
            const ammoId = this.value;
            
            if (ammoId.length < 3) return;
            
            ammoIdTimeout = setTimeout(() => {
                fetch('<?php echo BASE_URL; ?>/api/validate_ammo_id?ammo_id=' + encodeURIComponent(ammoId))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            const errorDiv = document.createElement('small');
                            errorDiv.className = 'error-text';
                            errorDiv.textContent = 'Ammunition ID already exists';
                            ammoIdInput.parentNode.appendChild(errorDiv);
                            ammoIdInput.classList.add('error');
                        }
                    })
                    .catch(error => console.error('Validation error:', error));
            }, 500);
        });
    }
});

function resetForm(formId) {
    if (confirm('Are you sure you want to reset the form? All unsaved data will be lost.')) {
        document.getElementById(formId).reset();
        
        // Hide conditional fields
        if (document.getElementById('typeOtherWrapper')) document.getElementById('typeOtherWrapper').style.display = 'none';
        if (document.getElementById('calibreOtherWrapper')) document.getElementById('calibreOtherWrapper').style.display = 'none';
        if (document.getElementById('locationOtherWrapper')) document.getElementById('locationOtherWrapper').style.display = 'none';
        if (document.getElementById('zonalWrapper')) document.getElementById('zonalWrapper').style.display = 'none';
        if (document.getElementById('commandWrapper')) document.getElementById('commandWrapper').style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
