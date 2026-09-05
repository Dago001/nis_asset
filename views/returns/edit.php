<?php
$title = 'Edit Return';
$active = 'returns';
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
                Edit Return: <?php echo Security::escape($return['return_number']); ?>
            </h1>
            <p>Update return information</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/returns/show/<?php echo $return['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="<?php echo BASE_URL; ?>/returns" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Returns
            </a>
        </div>
    </div>

    <!-- Status Warning for Non-Pending Returns -->
    <?php if ($return['status'] != 'Pending'): ?>
    <div class="status-banner status-danger">
        <div class="status-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="status-content">
            <h3>Cannot Edit</h3>
            <p>This return has been <?php echo strtolower($return['status']); ?> and cannot be edited.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/returns/update/<?php echo $return['id']; ?>" id="returnForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Return Header -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-signature"></i> Return Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Return Number</label>
                        <input type="text" value="<?php echo Security::escape($return['return_number']); ?>" 
                               class="form-control" readonly disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Return Date</label>
                        <input type="date" name="return_date" id="return_date" 
                               value="<?php echo Security::escape($return['return_date']); ?>" 
                               required class="form-control <?php echo isset($errors['return_date']) ? 'error' : ''; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['return_date'])): ?>
                            <small class="error-text"><?php echo $errors['return_date']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Return Type</label>
                        <select name="return_type" id="return_type" required 
                                class="form-control <?php echo isset($errors['return_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="Weapon" <?php echo $return['return_type'] == 'Weapon' ? 'selected' : ''; ?>>Weapon Only</option>
                            <option value="Ammunition" <?php echo $return['return_type'] == 'Ammunition' ? 'selected' : ''; ?>>Ammunition Only</option>
                            <option value="Both" <?php echo $return['return_type'] == 'Both' ? 'selected' : ''; ?>>Both Weapons & Ammunition</option>
                        </select>
                        <?php if (isset($errors['return_type'])): ?>
                            <small class="error-text"><?php echo $errors['return_type']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Related Requisition</label>
                        <select name="requisition_id" id="requisition_id" 
                                class="form-control <?php echo isset($errors['requisition_id']) ? 'error' : ''; ?>">
                            <option value="">None (Direct Return)</option>
                            <?php foreach ($issuedRequisitions as $req): ?>
                            <option value="<?php echo $req['id']; ?>" 
                                    <?php echo $return['requisition_id'] == $req['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($req['requisition_number'] . ' - ' . $req['requesting_officer_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['requisition_id'])): ?>
                            <small class="error-text"><?php echo $errors['requisition_id']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Returning Officer Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-user-shield"></i> Returning Officer</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Officer Name</label>
                        <input type="text" name="returning_officer_name" 
                               value="<?php echo Security::escape($return['returning_officer_name']); ?>" 
                               required maxlength="100" pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                               class="form-control <?php echo isset($errors['returning_officer_name']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['returning_officer_name'])): ?>
                            <small class="error-text"><?php echo $errors['returning_officer_name']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Alphabets, spaces, hyphens (-), and apostrophes (') only</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Rank</label>
                        <select name="returning_rank" required
                                class="form-control <?php echo isset($errors['returning_rank']) ? 'error' : ''; ?>">
                            <option value="">Select Rank</option>
                            <?php foreach (getNisRanks() as $rank): ?>
                                <option value="<?php echo htmlspecialchars($rank); ?>" <?php echo ($return['returning_rank'] ?? '') === $rank ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rank); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['returning_rank'])): ?>
                            <small class="error-text"><?php echo $errors['returning_rank']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">NIS Number</label>
                        <input type="text" name="returning_nis"
                               value="<?php echo Security::escape($return['returning_nis']); ?>"
                               required maxlength="20" inputmode="numeric" pattern="[0-9]+" title="Numbers only"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               class="form-control <?php echo isset($errors['returning_nis']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['returning_nis'])): ?>
                            <small class="error-text"><?php echo $errors['returning_nis']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Unit/Department</label>
                        <input type="text" name="returning_unit" 
                               value="<?php echo Security::escape($return['returning_unit']); ?>" 
                               required maxlength="100" 
                               class="form-control <?php echo isset($errors['returning_unit']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['returning_unit'])): ?>
                            <small class="error-text"><?php echo $errors['returning_unit']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Return Items - Weapons -->
            <div class="form-section-inner" id="weaponsSection" style="<?php echo in_array($return['return_type'], ['Weapon', 'Both']) ? '' : 'display: none;'; ?>">
                <div class="section-title">
                    <h3><i class="fas fa-gun"></i> Weapons Returned</h3>
                    <button type="button" class="btn btn-sm btn-success" onclick="addWeaponRow()">
                        <i class="fas fa-plus"></i> Add Weapon
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="items-table" id="weaponsTable">
                        <thead>
                            <tr>
                                <th>Weapon ID</th>
                                <th>Weapon Type</th>
                                <th>Serial Number</th>
                                <th>Condition</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="weaponsBody">
                            <?php if (!empty($weapons)): ?>
                                <?php foreach ($weapons as $weapon): ?>
                                <tr class="weapon-row">
                                    <td>
                                        <select name="weapon_id[]" class="form-control weapon-select" required onchange="updateWeaponDetails(this)">
                                            <option value="">Select Weapon</option>
                                            <?php foreach ($availableWeapons as $w): ?>
                                            <option value="<?php echo $w['id']; ?>" 
                                                    <?php echo $weapon['weapon_id'] == $w['id'] ? 'selected' : ''; ?>
                                                    data-type="<?php echo Security::escape($w['type_name']); ?>"
                                                    data-serial="<?php echo Security::escape($w['serial_no']); ?>">
                                                <?php echo Security::escape($w['weapon_id'] . ' - ' . $w['make_model'] . ' (' . $w['serial_no'] . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="weapon-type-display"><?php echo Security::escape($weapon['weapon_type']); ?></td>
                                    <td class="serial-display"><?php echo Security::escape($weapon['serial_number']); ?></td>
                                    <td>
                                        <select name="condition[]" class="form-control" required>
                                            <option value="">Select Condition</option>
                                            <option value="Serviceable" <?php echo $weapon['condition'] == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                            <option value="Unserviceable" <?php echo $weapon['condition'] == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                            <option value="Damaged" <?php echo $weapon['condition'] == 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                                            <option value="Missing Parts" <?php echo $weapon['condition'] == 'Missing Parts' ? 'selected' : ''; ?>>Missing Parts</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="weapon_remarks[]" class="form-control" 
                                               value="<?php echo Security::escape($weapon['remarks'] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-icon delete" onclick="removeWeaponRow(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr class="weapon-row">
                                <td>
                                    <select name="weapon_id[]" class="form-control weapon-select" required onchange="updateWeaponDetails(this)">
                                        <option value="">Select Weapon</option>
                                        <?php foreach ($availableWeapons as $weapon): ?>
                                        <option value="<?php echo $weapon['id']; ?>" 
                                                data-type="<?php echo Security::escape($weapon['type_name']); ?>"
                                                data-serial="<?php echo Security::escape($weapon['serial_no']); ?>">
                                            <?php echo Security::escape($weapon['weapon_id'] . ' - ' . $weapon['make_model'] . ' (' . $weapon['serial_no'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="weapon-type-display"></td>
                                <td class="serial-display"></td>
                                <td>
                                    <select name="condition[]" class="form-control" required>
                                        <option value="">Select Condition</option>
                                        <option value="Serviceable">Serviceable</option>
                                        <option value="Unserviceable">Unserviceable</option>
                                        <option value="Damaged">Damaged</option>
                                        <option value="Missing Parts">Missing Parts</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="weapon_remarks[]" class="form-control">
                                </td>
                                <td>
                                    <button type="button" class="btn-icon delete" onclick="removeWeaponRow(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Return Items - Ammunition -->
            <div class="form-section-inner" id="ammunitionSection" style="<?php echo in_array($return['return_type'], ['Ammunition', 'Both']) ? '' : 'display: none;'; ?>">
                <div class="section-title">
                    <h3><i class="fas fa-bullseye"></i> Ammunition Returned</h3>
                    <button type="button" class="btn btn-sm btn-success" onclick="addAmmoRow()">
                        <i class="fas fa-plus"></i> Add Ammunition
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="items-table" id="ammoTable">
                        <thead>
                            <tr>
                                <th>Ammunition</th>
                                <th>Batch Number</th>
                                <th>Rounds Issued</th>
                                <th>Rounds Returned</th>
                                <th>Rounds Used</th>
                                <th>Condition</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="ammoBody">
                            <?php if (!empty($ammunition)): ?>
                                <?php foreach ($ammunition as $ammo): ?>
                                <tr class="ammo-row">
                                    <td>
                                        <select name="ammo_id[]" class="form-control ammo-select" required onchange="updateAmmoDetails(this)">
                                            <option value="">Select Ammunition</option>
                                            <?php foreach ($issuedAmmunition as $a): ?>
                                            <option value="<?php echo $a['id']; ?>" 
                                                    <?php echo $ammo['ammo_id'] == $a['id'] ? 'selected' : ''; ?>
                                                    data-issued="<?php echo $a['rounds_issued']; ?>"
                                                    data-batch="<?php echo $a['batch_number']; ?>">
                                                <?php echo Security::escape($a['ammo_id'] . ' - ' . ($a['ammo_type'] ?? '') . ' (' . $a['calibre'] . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="batch-display"><?php echo Security::escape($ammo['batch_number']); ?></td>
                                    <td class="issued-display text-right"><?php echo $ammo['rounds_issued']; ?></td>
                                    <td>
                                        <input type="number" name="rounds_returned[]" class="form-control rounds-returned" 
                                               required min="0" value="<?php echo $ammo['rounds_returned']; ?>" 
                                               onchange="calculateRoundsUsed(this)">
                                    </td>
                                    <td>
                                        <input type="number" name="rounds_used[]" class="form-control rounds-used" 
                                               value="<?php echo $ammo['rounds_used']; ?>" readonly disabled>
                                        <input type="hidden" name="rounds_used_hidden[]" class="rounds-used-hidden" 
                                               value="<?php echo $ammo['rounds_used']; ?>">
                                    </td>
                                    <td>
                                        <select name="ammo_condition[]" class="form-control" required>
                                            <option value="">Select Condition</option>
                                            <option value="Serviceable" <?php echo $ammo['condition'] == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                            <option value="Unserviceable" <?php echo $ammo['condition'] == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                            <option value="Damaged" <?php echo $ammo['condition'] == 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="ammo_remarks[]" class="form-control" 
                                               value="<?php echo Security::escape($ammo['remarks'] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-icon delete" onclick="removeAmmoRow(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr class="ammo-row">
                                <td>
                                    <select name="ammo_id[]" class="form-control ammo-select" required onchange="updateAmmoDetails(this)">
                                        <option value="">Select Ammunition</option>
                                        <?php foreach ($issuedAmmunition as $ammo): ?>
                                        <option value="<?php echo $ammo['id']; ?>" 
                                                data-issued="<?php echo $ammo['rounds_issued']; ?>"
                                                data-batch="<?php echo $ammo['batch_number']; ?>">
                                            <?php echo Security::escape($ammo['ammo_id'] . ' - ' . ($ammo['ammo_type'] ?? '') . ' (' . $ammo['calibre'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="batch-display"></td>
                                <td class="issued-display text-right"></td>
                                <td>
                                    <input type="number" name="rounds_returned[]" class="form-control rounds-returned" 
                                           required min="0" onchange="calculateRoundsUsed(this)">
                                </td>
                                <td>
                                    <input type="number" name="rounds_used[]" class="form-control rounds-used" 
                                           readonly disabled>
                                    <input type="hidden" name="rounds_used_hidden[]" class="rounds-used-hidden">
                                </td>
                                <td>
                                    <select name="ammo_condition[]" class="form-control" required>
                                        <option value="">Select Condition</option>
                                        <option value="Serviceable">Serviceable</option>
                                        <option value="Unserviceable">Unserviceable</option>
                                        <option value="Damaged">Damaged</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="ammo_remarks[]" class="form-control">
                                </td>
                                <td>
                                    <button type="button" class="btn-icon delete" onclick="removeAmmoRow(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Remarks -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-sticky-note"></i> Remarks</h3>
                </div>
                
                <div class="form-group">
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($return['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-save"></i> Update Return
                </button>
                <a href="<?php echo BASE_URL; ?>/returns/show/<?php echo $return['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<style>
.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.items-table th {
    background: var(--light-bg);
    padding: 12px 8px;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    border-bottom: 2px solid var(--border-color);
}

.items-table td {
    padding: 8px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: top;
}

.items-table select,
.items-table input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 0.85rem;
}

.items-table .btn-icon {
    width: 30px;
    height: 30px;
}

.text-right {
    text-align: right;
}

/* Form Sections */
.form-section-inner {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-title h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--text-primary);
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.85rem;
}

/* Status Banner */
.status-banner {
    background: var(--surface);
    border-radius: 10px;
    padding: 20px 25px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 6px solid;
}

.status-banner.status-danger {
    border-left-color: var(--danger-color);
}

.status-icon i {
    font-size: 2.5rem;
    color: var(--danger-color);
}

.status-content h3 {
    margin: 0 0 5px 0;
    color: var(--text-primary);
}

.status-content p {
    margin: 0;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .items-table {
        font-size: 0.8rem;
    }
    
    .items-table td {
        padding: 5px;
    }
    
    .items-table select,
    .items-table input {
        padding: 4px;
    }
    
    .status-banner {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// Toggle section visibility & disabled/required attributes
function updateSectionVisibility() {
    const returnTypeSelect = document.getElementById('return_type');
    const returnType = returnTypeSelect ? returnTypeSelect.value : '';
    const weaponsSection = document.getElementById('weaponsSection');
    const ammoSection = document.getElementById('ammunitionSection');

    if (weaponsSection) {
        const isWeaponVisible = (returnType === 'Weapon' || returnType === 'Both');
        weaponsSection.style.display = isWeaponVisible ? 'block' : 'none';
        
        weaponsSection.querySelectorAll('input, select, textarea').forEach(el => {
            el.disabled = !isWeaponVisible;
            if (el.name === 'weapon_id[]' || el.name === 'condition[]') {
                el.required = isWeaponVisible;
            }
        });
    }

    if (ammoSection) {
        const isAmmoVisible = (returnType === 'Ammunition' || returnType === 'Both');
        ammoSection.style.display = isAmmoVisible ? 'block' : 'none';
        
        ammoSection.querySelectorAll('input, select, textarea').forEach(el => {
            el.disabled = !isAmmoVisible;
            if (el.name === 'ammo_id[]' || el.name === 'rounds_returned[]' || el.name === 'ammo_condition[]') {
                el.required = isAmmoVisible;
            }
        });
    }
}

const returnTypeSelect = document.getElementById('return_type');
if (returnTypeSelect) {
    returnTypeSelect.addEventListener('change', updateSectionVisibility);
}
updateSectionVisibility();

// Weapon row management
function addWeaponRow() {
    const tbody = document.getElementById('weaponsBody');
    const newRow = document.createElement('tr');
    newRow.className = 'weapon-row';
    newRow.innerHTML = `
        <td>
            <select name="weapon_id[]" class="form-control weapon-select" required onchange="updateWeaponDetails(this)">
                <option value="">Select Weapon</option>
                <?php foreach ($availableWeapons as $weapon): ?>
                <option value="<?php echo $weapon['id']; ?>" 
                        data-type="<?php echo Security::escape($weapon['type_name']); ?>"
                        data-serial="<?php echo Security::escape($weapon['serial_no']); ?>">
                    <?php echo Security::escape($weapon['weapon_id'] . ' - ' . $weapon['make_model'] . ' (' . $weapon['serial_no'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="weapon-type-display"></td>
        <td class="serial-display"></td>
        <td>
            <select name="condition[]" class="form-control" required>
                <option value="">Select Condition</option>
                <option value="Serviceable">Serviceable</option>
                <option value="Unserviceable">Unserviceable</option>
                <option value="Damaged">Damaged</option>
                <option value="Missing Parts">Missing Parts</option>
            </select>
        </td>
        <td>
            <input type="text" name="weapon_remarks[]" class="form-control">
        </td>
        <td>
            <button type="button" class="btn-icon delete" onclick="removeWeaponRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateWeaponRemoveButtons();
}

function removeWeaponRow(button) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    if (tbody.children.length > 1) {
        row.remove();
    } else {
        showNotification('warning', 'At least one weapon is required if type is Weapon or Both');
    }
    updateWeaponRemoveButtons();
}

function updateWeaponRemoveButtons() {
    const rows = document.querySelectorAll('.weapon-row');
    const removeButtons = document.querySelectorAll('.weapon-row .delete');
    
    if (rows.length <= 1) {
        removeButtons.forEach(btn => btn.disabled = true);
    } else {
        removeButtons.forEach(btn => btn.disabled = false);
    }
}

function updateWeaponDetails(select) {
    const row = select.closest('tr');
    const typeDisplay = row.querySelector('.weapon-type-display');
    const serialDisplay = row.querySelector('.serial-display');
    
    const selectedOption = select.options[select.selectedIndex];
    const weaponType = selectedOption.getAttribute('data-type');
    const serial = selectedOption.getAttribute('data-serial');
    
    typeDisplay.textContent = weaponType || '';
    serialDisplay.textContent = serial || '';
}

// Ammunition row management
function addAmmoRow() {
    const tbody = document.getElementById('ammoBody');
    const newRow = document.createElement('tr');
    newRow.className = 'ammo-row';
    newRow.innerHTML = `
        <td>
            <select name="ammo_id[]" class="form-control ammo-select" required onchange="updateAmmoDetails(this)">
                <option value="">Select Ammunition</option>
                <?php foreach ($issuedAmmunition as $ammo): ?>
                <option value="<?php echo $ammo['id']; ?>" 
                        data-issued="<?php echo $ammo['rounds_issued']; ?>"
                        data-batch="<?php echo $ammo['batch_number']; ?>">
                    <?php echo Security::escape($ammo['ammo_id'] . ' - ' . ($ammo['ammo_type'] ?? '') . ' (' . $ammo['calibre'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="batch-display"></td>
        <td class="issued-display text-right"></td>
        <td>
            <input type="number" name="rounds_returned[]" class="form-control rounds-returned" 
                   required min="0" onchange="calculateRoundsUsed(this)">
        </td>
        <td>
            <input type="number" name="rounds_used[]" class="form-control rounds-used" 
                   readonly disabled>
            <input type="hidden" name="rounds_used_hidden[]" class="rounds-used-hidden">
        </td>
        <td>
            <select name="ammo_condition[]" class="form-control" required>
                <option value="">Select Condition</option>
                <option value="Serviceable">Serviceable</option>
                <option value="Unserviceable">Unserviceable</option>
                <option value="Damaged">Damaged</option>
            </select>
        </td>
        <td>
            <input type="text" name="ammo_remarks[]" class="form-control">
        </td>
        <td>
            <button type="button" class="btn-icon delete" onclick="removeAmmoRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateAmmoRemoveButtons();
}

function removeAmmoRow(button) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    if (tbody.children.length > 1) {
        row.remove();
    } else {
        showNotification('warning', 'At least one ammunition is required if type is Ammunition or Both');
    }
    updateAmmoRemoveButtons();
}

function updateAmmoRemoveButtons() {
    const rows = document.querySelectorAll('.ammo-row');
    const removeButtons = document.querySelectorAll('.ammo-row .delete');
    
    if (rows.length <= 1) {
        removeButtons.forEach(btn => btn.disabled = true);
    } else {
        removeButtons.forEach(btn => btn.disabled = false);
    }
}

function updateAmmoDetails(select) {
    const row = select.closest('tr');
    const batchDisplay = row.querySelector('.batch-display');
    const issuedDisplay = row.querySelector('.issued-display');
    const roundsReturned = row.querySelector('.rounds-returned');
    
    const selectedOption = select.options[select.selectedIndex];
    const issued = selectedOption.getAttribute('data-issued');
    const batch = selectedOption.getAttribute('data-batch');
    
    batchDisplay.textContent = batch || '';
    issuedDisplay.textContent = issued || '0';
    
    if (roundsReturned) {
        roundsReturned.max = issued || 0;
        calculateRoundsUsed(roundsReturned);
    }
}

function calculateRoundsUsed(input) {
    const row = input.closest('tr');
    const issuedDisplay = row.querySelector('.issued-display');
    const roundsReturned = parseInt(input.value) || 0;
    const roundsUsedInput = row.querySelector('.rounds-used');
    const roundsUsedHidden = row.querySelector('.rounds-used-hidden');
    
    const issued = parseInt(issuedDisplay.textContent) || 0;
    const roundsUsed = issued - roundsReturned;
    
    if (roundsUsed >= 0) {
        roundsUsedInput.value = roundsUsed;
        roundsUsedHidden.value = roundsUsed;
    } else {
        roundsUsedInput.value = 0;
        roundsUsedHidden.value = 0;
        alert('Rounds returned cannot exceed rounds issued');
        input.value = issued;
    }
}

// Initialize existing weapon selects
document.querySelectorAll('.weapon-select').forEach(select => {
    if (select.value) {
        updateWeaponDetails(select);
    }
});

// Initialize existing ammo selects
document.querySelectorAll('.ammo-select').forEach(select => {
    if (select.value) {
        updateAmmoDetails(select);
    }
});

// Form validation
document.getElementById('returnForm').addEventListener('submit', function(e) {
    const returnType = document.getElementById('return_type').value;
    
    if (!returnType) {
        e.preventDefault();
        alert('Please select return type');
        return false;
    }
    
    if (returnType === 'Weapon' || returnType === 'Both') {
        const weaponRows = document.querySelectorAll('.weapon-row');
        let hasWeapon = false;
        weaponRows.forEach(row => {
            const select = row.querySelector('select[name="weapon_id[]"]');
            if (select && select.value) hasWeapon = true;
        });
        
        if (!hasWeapon) {
            e.preventDefault();
            alert('Please add at least one weapon');
            return false;
        }
    }
    
    if (returnType === 'Ammunition' || returnType === 'Both') {
        const ammoRows = document.querySelectorAll('.ammo-row');
        let hasAmmo = false;
        ammoRows.forEach(row => {
            const select = row.querySelector('select[name="ammo_id[]"]');
            if (select && select.value) hasAmmo = true;
        });
        
        if (!hasAmmo) {
            e.preventDefault();
            alert('Please add at least one ammunition');
            return false;
        }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>