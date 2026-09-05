<?php
$title = 'Edit Weapon';
$active = 'weapons';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$errors = Session::get('errors', []);
Session::remove('errors');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-edit"></i>
                Edit Weapon: <?php echo Security::escape($weapon['weapon_id']); ?>
            </h1>
            <p>Update weapon information</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/weapons/show/<?php echo $weapon['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="<?php echo BASE_URL; ?>/weapons" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Weapons
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/weapons/update/<?php echo $weapon['id']; ?>" id="weaponForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Weapon Identification -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-id-card"></i> Weapon Identification</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Weapon ID</label>
                        <input type="text" value="<?php echo Security::escape($weapon['weapon_id']); ?>" 
                               class="form-control" readonly disabled>
                        <input type="hidden" name="weapon_id" value="<?php echo Security::escape($weapon['weapon_id']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Serial Number</label>
                        <input type="text" name="serial_no" id="serial_no" 
                               value="<?php echo Security::escape($weapon['serial_no']); ?>" 
                               required maxlength="100" 
                               class="form-control <?php echo isset($errors['serial_no']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['serial_no'])): ?>
                            <small class="error-text"><?php echo $errors['serial_no']; ?></small>
                        <?php endif; ?>
                        <div id="serialValidation" class="form-hint"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Weapon Type</label>
                        <select name="weapon_type_id" id="weapon_type_id" 
                                class="form-control <?php echo isset($errors['weapon_type_id']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <?php foreach ($weaponTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" 
                                    <?php echo $weapon['weapon_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($type['type_name']); ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="other">Other (Specify)</option>
                        </select>
                        <?php if (isset($errors['weapon_type_id'])): ?>
                            <small class="error-text"><?php echo $errors['weapon_type_id']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="typeOtherWrapper" style="display: none;">
                        <label>Specify Weapon Type</label>
                        <input type="text" name="weapon_type_other" id="weapon_type_other" 
                               class="form-control" value="<?php echo Security::escape($weapon['weapon_type_other'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Make/Model</label>
                        <input type="text" name="make_model" value="<?php echo Security::escape($weapon['make_model']); ?>" 
                               required maxlength="255" 
                               class="form-control <?php echo isset($errors['make_model']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['make_model'])): ?>
                            <small class="error-text"><?php echo $errors['make_model']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Calibre Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-bullseye"></i> Calibre Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Calibre</label>
                        <select name="calibre_id" id="calibre_id" 
                                class="form-control <?php echo isset($errors['calibre_id']) ? 'error' : ''; ?>">
                            <option value="">Select Calibre</option>
                            <?php foreach ($calibres as $calibre): ?>
                            <option value="<?php echo $calibre['id']; ?>" 
                                    <?php echo $weapon['calibre_id'] == $calibre['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($calibre['calibre_name']); ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="other">Other (Specify)</option>
                        </select>
                        <?php if (isset($errors['calibre_id'])): ?>
                            <small class="error-text"><?php echo $errors['calibre_id']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="calibreOtherWrapper" style="display: none;">
                        <label>Specify Calibre</label>
                        <input type="text" name="calibre_other" id="calibre_other" 
                               class="form-control" value="<?php echo Security::escape($weapon['calibre_other'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Source & Acquisition -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-truck"></i> Source & Acquisition</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Source</label>
                        <select name="source" id="source" required 
                                class="form-control <?php echo isset($errors['source']) ? 'error' : ''; ?>">
                            <option value="">Select Source</option>
                            <option value="FGN Purchase" <?php echo $weapon['source'] == 'FGN Purchase' ? 'selected' : ''; ?>>FGN Purchase</option>
                            <option value="Donor" <?php echo $weapon['source'] == 'Donor' ? 'selected' : ''; ?>>Donor</option>
                            <option value="Transfer" <?php echo $weapon['source'] == 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
                            <option value="Recovered" <?php echo $weapon['source'] == 'Recovered' ? 'selected' : ''; ?>>Recovered</option>
                            <option value="Other">Other</option>
                        </select>
                        <?php if (isset($errors['source'])): ?>
                            <small class="error-text"><?php echo $errors['source']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" id="sourceOtherWrapper" style="display: none;">
                        <label>Specify Source</label>
                        <input type="text" name="source_other" id="source_other" 
                               class="form-control" value="<?php echo Security::escape($weapon['source_other'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Date Acquired</label>
                        <input type="date" name="date_acquired" value="<?php echo Security::escape($weapon['date_acquired'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Current Status -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-clipboard-check"></i> Current Status</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Condition</label>
                        <select name="condition" required 
                                class="form-control <?php echo isset($errors['condition']) ? 'error' : ''; ?>">
                            <option value="">Select Condition</option>
                            <option value="Serviceable" <?php echo ($weapon['condition'] ?? '') == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                            <option value="Unserviceable" <?php echo ($weapon['condition'] ?? '') == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                            <option value="Under Repair" <?php echo ($weapon['condition'] ?? '') == 'Under Repair' ? 'selected' : ''; ?>>Under Repair</option>
                        </select>
                        <?php if (isset($errors['condition'])): ?>
                            <small class="error-text"><?php echo $errors['condition']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Current Location</label>
                        <select name="current_location" id="current_location" required 
                                class="form-control <?php echo isset($errors['current_location']) ? 'error' : ''; ?>">
                            <option value="">Select Location</option>
                            <option value="Armoury" <?php echo ($weapon['current_location'] ?? '') == 'Armoury' ? 'selected' : ''; ?>>Armoury</option>
                            <option value="Issued" <?php echo ($weapon['current_location'] ?? '') == 'Issued' ? 'selected' : ''; ?>>Issued</option>
                            <option value="In Repair" <?php echo ($weapon['current_location'] ?? '') == 'In Repair' ? 'selected' : ''; ?>>In Repair</option>
                            <option value="Lost" <?php echo ($weapon['current_location'] ?? '') == 'Lost' ? 'selected' : ''; ?>>Lost</option>
                        </select>
                        <?php if (isset($errors['current_location'])): ?>
                            <small class="error-text"><?php echo $errors['current_location']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Armoury Location Fields -->
                <div id="armouryLocationFields" style="<?php echo ($weapon['current_location'] ?? '') == 'Armoury' ? '' : 'display: none;'; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Zone</label>
                            <select name="zone_id" id="zone_id" class="form-control">
                                <option value="">Select Zone</option>
                                <?php foreach ($zones as $zone): ?>
                                <option value="<?php echo $zone['id']; ?>" <?php echo ($weapon['zone_id'] ?? '') == $zone['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($zone['zone_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Command / Formation</label>
                            <select name="command_id" id="command_id" class="form-control">
                                <option value="">Select Command</option>
                                <?php foreach ($commands as $command): ?>
                                <option value="<?php echo $command['id']; ?>" <?php echo ($weapon['command_id'] ?? '') == $command['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($command['command_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>State</label>
                            <select name="state_id" id="state_id" class="form-control">
                                <option value="">Select State</option>
                                <?php foreach ($states as $state): ?>
                                <option value="<?php echo $state['id']; ?>" <?php echo ($weapon['state_id'] ?? '') == $state['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($state['state_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Local Government Area (LGA)</label>
                            <select name="lga_id" id="lga_id" class="form-control">
                                <option value="">Select State First</option>
                                <?php foreach ($lgas as $lga): ?>
                                <option value="<?php echo $lga['id']; ?>" <?php echo ($weapon['lga_id'] ?? '') == $lga['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($lga['lga_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Armoury Name / Specific Location Details</label>
                        <input type="text" name="armoury_name" value="<?php echo Security::escape($weapon['armoury_name'] ?? ''); ?>" 
                               class="form-control" placeholder="e.g., Central Armoury Vault 1, Command Main Station">
                    </div>
                </div>
                
                <div class="form-row" id="custodianFields" style="<?php echo ($weapon['current_location'] ?? '') == 'Issued' ? '' : 'display: none;'; ?>">
                    <div class="form-group">
                        <label>Custodian Name</label>
                        <input type="text" name="custodian" value="<?php echo Security::escape($weapon['custodian'] ?? ''); ?>" 
                               maxlength="255" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Custodian Rank</label>
                        <select name="custodian_rank" class="form-control">
                            <option value="">Select Rank</option>
                            <?php foreach (getNisRanks() as $rank): ?>
                                <option value="<?php echo htmlspecialchars($rank); ?>" <?php echo ($weapon['custodian_rank'] ?? '') === $rank ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rank); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row" id="custodianNisField" style="<?php echo ($weapon['current_location'] ?? '') == 'Issued' ? '' : 'display: none;'; ?>">
                    <div class="form-group">
                        <label>Custodian NIS Number</label>
                        <input type="text" name="custodian_nis" value="<?php echo Security::escape($weapon['custodian_nis'] ?? ''); ?>"
                               maxlength="20" inputmode="numeric" pattern="[0-9]*" title="Numbers only"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               class="form-control">
                    </div>
                </div>
                
                <div class="form-group" id="locationOtherWrapper" style="<?php echo ($weapon['current_location'] ?? '') == 'Other' ? '' : 'display: none;'; ?>">
                    <label>Specify Location</label>
                    <input type="text" name="current_location_other" id="current_location_other" 
                           class="form-control" value="<?php echo Security::escape($weapon['current_location_other'] ?? ''); ?>">
                </div>
            </div>
            
            <!-- Inspection -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-clipboard-list"></i> Inspection</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Inspection Date</label>
                        <input type="date" name="last_inspection_date" value="<?php echo Security::escape($weapon['last_inspection_date'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Next Inspection Date</label>
                        <input type="date" name="next_inspection_date" value="<?php echo Security::escape($weapon['next_inspection_date'] ?? ''); ?>" 
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
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($weapon['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-save"></i> Update Weapon
                </button>
                <a href="<?php echo BASE_URL; ?>/weapons/show/<?php echo $weapon['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Weapon type other field
    const typeSelect = document.getElementById('weapon_type_id');
    const typeWrapper = document.getElementById('typeOtherWrapper');
    const typeOther = document.getElementById('weapon_type_other');
    
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                typeWrapper.style.display = 'block';
                typeOther.required = true;
            } else {
                typeWrapper.style.display = 'none';
                typeOther.required = false;
            }
        });
        
        <?php if (!empty($weapon['weapon_type_other'])): ?>
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
            }
        });
        
        <?php if (!empty($weapon['calibre_other'])): ?>
        calibreSelect.value = 'other';
        calibreWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
    // Source other field
    const sourceSelect = document.getElementById('source');
    const sourceWrapper = document.getElementById('sourceOtherWrapper');
    const sourceOther = document.getElementById('source_other');
    
    if (sourceSelect) {
        sourceSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                sourceWrapper.style.display = 'block';
                sourceOther.required = true;
            } else {
                sourceWrapper.style.display = 'none';
                sourceOther.required = false;
            }
        });
        
        <?php if (!empty($weapon['source_other'])): ?>
        sourceSelect.value = 'Other';
        sourceWrapper.style.display = 'block';
        <?php endif; ?>
    }
    
    // Location dependent fields
    const locationSelect = document.getElementById('current_location');
    const armouryFields = document.getElementById('armouryLocationFields');
    const custodianFields = document.getElementById('custodianFields');
    const custodianNisField = document.getElementById('custodianNisField');
    const locationWrapper = document.getElementById('locationOtherWrapper');
    const locationOther = document.getElementById('current_location_other');
    
    if (locationSelect) {
        function toggleLocationFields(val) {
            if (val === 'Armoury') {
                if (armouryFields) armouryFields.style.display = 'block';
                if (custodianFields) custodianFields.style.display = 'none';
                if (custodianNisField) custodianNisField.style.display = 'none';
                if (locationWrapper) locationWrapper.style.display = 'none';
                if (locationOther) locationOther.required = false;
            } else if (val === 'Issued') {
                if (armouryFields) armouryFields.style.display = 'none';
                if (custodianFields) custodianFields.style.display = 'flex';
                if (custodianNisField) custodianNisField.style.display = 'flex';
                if (locationWrapper) locationWrapper.style.display = 'none';
                if (locationOther) locationOther.required = false;
            } else if (val === 'Other') {
                if (armouryFields) armouryFields.style.display = 'none';
                if (custodianFields) custodianFields.style.display = 'none';
                if (custodianNisField) custodianNisField.style.display = 'none';
                if (locationWrapper) locationWrapper.style.display = 'block';
                if (locationOther) locationOther.required = true;
            } else {
                if (armouryFields) armouryFields.style.display = 'none';
                if (custodianFields) custodianFields.style.display = 'none';
                if (custodianNisField) custodianNisField.style.display = 'none';
                if (locationWrapper) locationWrapper.style.display = 'none';
                if (locationOther) locationOther.required = false;
            }
        }

        locationSelect.addEventListener('change', function() {
            toggleLocationFields(this.value);
        });
        
        toggleLocationFields(locationSelect.value);
    }

    // Cascading State -> LGA
    const stateSelect = document.getElementById('state_id');
    const lgaSelect = document.getElementById('lga_id');
    if (stateSelect && lgaSelect) {
        stateSelect.addEventListener('change', function() {
            const stateId = this.value;
            lgaSelect.innerHTML = '<option value="">Loading LGAs...</option>';
            if (!stateId) {
                lgaSelect.innerHTML = '<option value="">Select State First</option>';
                return;
            }
            fetch('<?php echo BASE_URL; ?>/api/get_lgas?state_id=' + stateId)
                .then(res => res.json())
                .then(data => {
                    lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                    const lgas = data.lgas || (Array.isArray(data) ? data : []);
                    lgas.forEach(lga => {
                        const opt = document.createElement('option');
                        opt.value = lga.id;
                        opt.textContent = lga.lga_name;
                        lgaSelect.appendChild(opt);
                    });
                })
                .catch(() => { lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>'; });
        });
    }

    // Cascading Zone -> Command
    const zoneSelect = document.getElementById('zone_id');
    const commandSelect = document.getElementById('command_id');
    if (zoneSelect && commandSelect) {
        zoneSelect.addEventListener('change', function() {
            const zoneId = this.value;
            commandSelect.innerHTML = '<option value="">Loading Commands...</option>';
            if (!zoneId) {
                commandSelect.innerHTML = '<option value="">Select Zone First</option>';
                return;
            }
            fetch('<?php echo BASE_URL; ?>/api/get_commands?zone_id=' + zoneId)
                .then(res => res.json())
                .then(data => {
                    commandSelect.innerHTML = '<option value="">Select Command</option>';
                    const cmds = data.commands || (Array.isArray(data) ? data : []);
                    cmds.forEach(cmd => {
                        const opt = document.createElement('option');
                        opt.value = cmd.id;
                        opt.textContent = cmd.command_name;
                        commandSelect.appendChild(opt);
                    });
                })
                .catch(() => { commandSelect.innerHTML = '<option value="">Error loading Commands</option>'; });
        });
    }
    
    // Serial number validation
    const serialInput = document.getElementById('serial_no');
    const serialValidation = document.getElementById('serialValidation');
    const currentSerial = serialInput.value;
    let serialTimeout;
    
    if (serialInput) {
        serialInput.addEventListener('input', function() {
            clearTimeout(serialTimeout);
            const serial = this.value;
            
            if (serial === currentSerial || serial.length < 3) {
                serialValidation.innerHTML = '';
                return;
            }
            
            serialTimeout = setTimeout(() => {
                fetch('<?php echo BASE_URL; ?>/api/validate_serial?type=weapon&serial=' + encodeURIComponent(serial) + '&exclude_id=<?php echo $weapon['id']; ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            serialValidation.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Serial number already exists</span>';
                            serialInput.classList.add('error');
                        } else {
                            serialValidation.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Serial number available</span>';
                            serialInput.classList.remove('error');
                        }
                    });
            }, 500);
        });
    }
});
</script>

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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
