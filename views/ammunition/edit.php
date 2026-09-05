<?php
$title = 'Edit Ammunition';
$active = 'ammunition';
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
                Edit Ammunition: <?php echo Security::escape($ammo['ammo_id']); ?>
            </h1>
            <p>Update ammunition information</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/ammunition/show/<?php echo $ammo['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="<?php echo BASE_URL; ?>/ammunition" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Ammunition
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/ammunition/update/<?php echo $ammo['id']; ?>" id="ammoForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Basic Identification -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-id-card"></i> Basic Identification</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Ammunition ID</label>
                        <input type="text" name="ammo_id" value="<?php echo Security::escape($ammo['ammo_id']); ?>" 
                               class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Batch Number</label>
                        <input type="text" name="batch_number" id="batch_number" 
                               value="<?php echo Security::escape($ammo['batch_number']); ?>" 
                               required maxlength="100" 
                               class="form-control <?php echo isset($errors['batch_number']) ? 'error' : ''; ?>">
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
                                    <?php echo $ammo['ammo_type_id'] == $type['id'] ? 'selected' : ''; ?>>
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
                               class="form-control" value="<?php echo Security::escape($ammo['ammo_type_other'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Calibre</label>
                        <select name="calibre_id" id="calibre_id" required
                                class="form-control <?php echo isset($errors['calibre_id']) ? 'error' : ''; ?>">
                            <option value="">Select Calibre</option>
                            <?php foreach ($calibres as $calibre): ?>
                            <option value="<?php echo $calibre['id']; ?>" 
                                    <?php echo $ammo['calibre_id'] == $calibre['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($calibre['calibre']); ?>
                                (<?php echo $calibre['rounds_per_unit']; ?> rounds/unit)
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
                           class="form-control" value="<?php echo Security::escape($ammo['calibre_other'] ?? ''); ?>">
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
                               value="<?php echo Security::escape($ammo['quantity_received']); ?>" 
                               required min="1" 
                               class="form-control quantity-field <?php echo isset($errors['quantity_received']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['quantity_received'])): ?>
                            <small class="error-text"><?php echo $errors['quantity_received']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Quantity Issued</label>
                        <input type="number" name="quantity_issued" id="quantity_issued" 
                               value="<?php echo Security::escape($ammo['quantity_issued']); ?>" 
                               required min="0" 
                               class="form-control quantity-field <?php echo isset($errors['quantity_issued']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['quantity_issued'])): ?>
                            <small class="error-text"><?php echo $errors['quantity_issued']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Storage Form</label>
                        <select name="storage_form" id="storage_form"
                                class="form-control <?php echo isset($errors['storage_form']) ? 'error' : ''; ?>">
                            <option value="">Select Form</option>
                            <option value="Rounds" <?php echo ($ammo['storage_form'] ?? '') == 'Rounds' ? 'selected' : ''; ?>>Rounds</option>
                            <option value="Boxes" <?php echo ($ammo['storage_form'] ?? '') == 'Boxes' ? 'selected' : ''; ?>>Boxes</option>
                            <option value="Crates" <?php echo ($ammo['storage_form'] ?? '') == 'Crates' ? 'selected' : ''; ?>>Crates</option>
                            <option value="Pallets" <?php echo ($ammo['storage_form'] ?? '') == 'Pallets' ? 'selected' : ''; ?>>Pallets</option>
                        </select>
                        <?php if (isset($errors['storage_form'])): ?>
                            <small class="error-text"><?php echo $errors['storage_form']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="calculated-field">Current Balance</label>
                        <input type="text" id="current_balance" 
                               value="<?php echo number_format($ammo['quantity_received'] - $ammo['quantity_issued']); ?>" 
                               class="form-control" readonly disabled>
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
                            <option value="HQ Armoury" <?php echo ($ammo['storage_location'] ?? '') == 'HQ Armoury' ? 'selected' : ''; ?>>HQ Armoury / Service Headquarters</option>
                            <option value="Zonal Armoury" <?php echo in_array($ammo['storage_location'] ?? '', ['Zonal Armoury', 'Zonal Armony']) ? 'selected' : ''; ?>>Zonal Armoury</option>
                            <option value="Command Armoury" <?php echo in_array($ammo['storage_location'] ?? '', ['Command Armoury', 'Command Armony']) ? 'selected' : ''; ?>>Command Armoury</option>
                            <option value="Other" <?php echo ($ammo['storage_location'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <?php if (isset($errors['storage_location'])): ?>
                            <small class="error-text"><?php echo $errors['storage_location']; ?></small>
                        <?php endif; ?>
                    </div>

                    <!-- Zonal Armoury Dropdown -->
                    <div class="form-group" id="zonalWrapper" style="<?php echo in_array($ammo['storage_location'] ?? '', ['Zonal Armoury', 'Zonal Armony']) ? '' : 'display: none;'; ?>">
                        <label class="required">Zonal Command</label>
                        <select name="zone_id" id="zone_id" class="form-control">
                            <option value="">Select Zonal Command (Zone A - H)</option>
                            <?php foreach ($zones as $zone): ?>
                            <option value="<?php echo $zone['id']; ?>" <?php echo ($ammo['zone_id'] ?? '') == $zone['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($zone['zone_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Command Armoury Dropdown -->
                    <div class="form-group" id="commandWrapper" style="<?php echo in_array($ammo['storage_location'] ?? '', ['Command Armoury', 'Command Armony']) ? '' : 'display: none;'; ?>">
                        <label class="required">Command / Formation</label>
                        <select name="command_id" id="command_id" class="form-control">
                            <option value="">Select State Command (All 36 States & Special Commands)</option>
                            <?php foreach ($commands as $command): ?>
                            <option value="<?php echo $command['id']; ?>" <?php echo ($ammo['command_id'] ?? '') == $command['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($command['command_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Other Location Text Input -->
                    <div class="form-group" id="locationOtherWrapper" style="<?php echo ($ammo['storage_location'] ?? '') == 'Other' ? '' : 'display: none;'; ?>">
                        <label class="required">Specify Storage Location / Manual Input</label>
                        <input type="text" name="storage_location_other" id="storage_location_other" 
                               class="form-control" value="<?php echo Security::escape($ammo['storage_location_other'] ?? ''); ?>"
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
                        <input type="text" name="manufacturer" value="<?php echo Security::escape($ammo['manufacturer'] ?? ''); ?>" 
                               maxlength="100" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Date Manufactured</label>
                        <input type="date" name="date_manufactured" value="<?php echo Security::escape($ammo['date_manufactured'] ?? ''); ?>" 
                               class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date" id="expiry_date" 
                               value="<?php echo Security::escape($ammo['expiry_date'] ?? ''); ?>" 
                               class="form-control">
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
                            <option value="Serviceable" <?php echo ($ammo['condition'] ?? '') == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                            <option value="Unserviceable" <?php echo ($ammo['condition'] ?? '') == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                            <option value="Condemned" <?php echo ($ammo['condition'] ?? '') == 'Condemned' ? 'selected' : ''; ?>>Condemned</option>
                        </select>
                        <?php if (isset($errors['condition'])): ?>
                            <small class="error-text"><?php echo $errors['condition']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($ammo['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-save"></i> Update Ammunition
                </button>
                <a href="<?php echo BASE_URL; ?>/ammunition/show/<?php echo $ammo['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

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
            }
        });
        
        <?php if (!empty($ammo['ammo_type_other'])): ?>
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
        
        <?php if (!empty($ammo['calibre_other'])): ?>
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
    
    if (locationSelect) {
        function toggleStorageLocation(val) {
            if (!val) {
                if (zonalWrapper) zonalWrapper.style.display = 'none';
                if (commandWrapper) commandWrapper.style.display = 'none';
                if (locationWrapper) locationWrapper.style.display = 'none';
                return;
            }
            const v = val.toLowerCase().trim();
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

        locationSelect.addEventListener('change', function() {
            toggleStorageLocation(this.value);
        });
        
        toggleStorageLocation(locationSelect.value);
    }
    
    // Balance calculation
    const quantityReceived = document.getElementById('quantity_received');
    const quantityIssued = document.getElementById('quantity_issued');
    const currentBalance = document.getElementById('current_balance');
    
    function updateBalance() {
        if (quantityReceived && quantityIssued && currentBalance) {
            const received = parseInt(quantityReceived.value) || 0;
            const issued = parseInt(quantityIssued.value) || 0;
            const balance = Math.max(0, received - issued);
            currentBalance.value = balance.toLocaleString();
            
            // Warn if balance is negative
            if (issued > received) {
                quantityIssued.classList.add('error');
                showNotification('warning', 'Issued quantity cannot exceed received quantity');
            } else {
                quantityIssued.classList.remove('error');
            }
        }
    }
    
    if (quantityReceived && quantityIssued) {
        quantityReceived.addEventListener('input', updateBalance);
        quantityIssued.addEventListener('input', updateBalance);
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>