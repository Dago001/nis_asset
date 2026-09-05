<?php
$title = 'Edit Requisition';
$active = 'requisition';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$errors = Session::get('errors', []);
Session::remove('errors');

$weaponCalibres = isset($weaponCalibres) ? $weaponCalibres : [];
$ammoCalibres = isset($ammoCalibres) ? $ammoCalibres : [];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-edit"></i>
                Edit Requisition: <?php echo Security::escape($requisition['requisition_number']); ?>
            </h1>
            <p>Update requisition details</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $requisition['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="<?php echo BASE_URL; ?>/requisition" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Requisitions
            </a>
        </div>
    </div>

    <!-- Status Warning for Non-Pending Requisitions -->
    <?php if ($requisition['status'] != 'Pending' && $requisition['status'] != 'Draft'): ?>
    <div class="status-banner status-danger">
        <div class="status-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="status-content">
            <h3>Cannot Edit</h3>
            <p>This requisition is <?php echo strtolower($requisition['status']); ?> and cannot be edited.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/requisitions/update/<?php echo $requisition['id']; ?>" id="requisitionForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Requisition Header -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-signature"></i> Requisition Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Requisition Number</label>
                        <input type="text" value="<?php echo Security::escape($requisition['requisition_number']); ?>" 
                               class="form-control" readonly disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Requisition Date</label>
                        <input type="date" name="requisition_date" id="requisition_date" 
                               value="<?php echo Security::escape($requisition['requisition_date']); ?>" 
                               required class="form-control <?php echo isset($errors['requisition_date']) ? 'error' : ''; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['requisition_date'])): ?>
                            <small class="error-text"><?php echo $errors['requisition_date']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Priority Level</label>
                        <select name="priority_level" id="priority_level" required 
                                class="form-control <?php echo isset($errors['priority_level']) ? 'error' : ''; ?>">
                            <option value="">Select Priority</option>
                            <option value="Low" <?php echo $requisition['priority_level'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo $requisition['priority_level'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo $requisition['priority_level'] == 'High' ? 'selected' : ''; ?>>High</option>
                            <option value="Urgent" <?php echo $requisition['priority_level'] == 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                        <?php if (isset($errors['priority_level'])): ?>
                            <small class="error-text"><?php echo $errors['priority_level']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Requisition Type</label>
                        <select name="requisition_type" id="requisition_type" required 
                                class="form-control <?php echo isset($errors['requisition_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="Weapon" <?php echo $requisition['requisition_type'] == 'Weapon' ? 'selected' : ''; ?>>Weapon Only</option>
                            <option value="Ammunition" <?php echo $requisition['requisition_type'] == 'Ammunition' ? 'selected' : ''; ?>>Ammunition Only</option>
                            <option value="Non-Lethal" <?php echo $requisition['requisition_type'] == 'Non-Lethal' ? 'selected' : ''; ?>>Non-Lethal Only</option>
                            <option value="Both" <?php echo $requisition['requisition_type'] == 'Both' ? 'selected' : ''; ?>>Weapons & Ammunition</option>
                            <option value="All" <?php echo $requisition['requisition_type'] == 'All' ? 'selected' : ''; ?>>All Types</option>
                        </select>
                        <?php if (isset($errors['requisition_type'])): ?>
                            <small class="error-text"><?php echo $errors['requisition_type']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Expected Return Date</label>
                        <input type="date" name="expected_return_date" id="expected_return_date" 
                               value="<?php echo Security::escape($requisition['expected_return_date'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
            </div>
            
            <!-- Requesting Officer Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-user-shield"></i> Requesting Officer</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Officer Name</label>
                        <input type="text" name="requesting_officer_name" 
                               value="<?php echo Security::escape($requisition['requesting_officer_name']); ?>" 
                               required maxlength="255" pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                               class="form-control <?php echo isset($errors['requesting_officer_name']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['requesting_officer_name'])): ?>
                            <small class="error-text"><?php echo $errors['requesting_officer_name']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Alphabets, spaces, hyphens (-), and apostrophes (') only</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Rank</label>
                        <?php $nisRanks = getNisRanks(); ?>
                        <select name="requesting_rank" required 
                                class="form-control <?php echo isset($errors['requesting_rank']) ? 'error' : ''; ?>">
                            <option value="">Select Rank</option>
                            <?php foreach ($nisRanks as $rank): ?>
                                <option value="<?php echo htmlspecialchars($rank); ?>"
                                        <?php echo $requisition['requesting_rank'] === $rank ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rank); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['requesting_rank'])): ?>
                            <small class="error-text"><?php echo $errors['requesting_rank']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">NIS Number</label>
                        <input type="text" name="requesting_nis"
                               value="<?php echo Security::escape($requisition['requesting_nis']); ?>"
                               required minlength="4" maxlength="5" inputmode="numeric" pattern="[0-9]{4,5}" title="NIS Number must be 4 or 5 digits"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 5)"
                               class="form-control <?php echo isset($errors['requesting_nis']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['requesting_nis'])): ?>
                            <small class="error-text"><?php echo $errors['requesting_nis']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Must be 4 or 5 digits (e.g. 1234 or 12345)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Phone Number</label>
                        <input type="tel" name="requesting_phone"
                               value="<?php echo Security::escape($requisition['requesting_phone']); ?>"
                               required minlength="11" maxlength="11" inputmode="numeric" pattern="\d{11}" title="Phone number must be exactly 11 digits"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                               class="form-control <?php echo isset($errors['requesting_phone']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['requesting_phone'])): ?>
                            <small class="error-text"><?php echo $errors['requesting_phone']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Must be exactly 11 digits (e.g. 08012345678)</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Command/Formation</label>
                        <?php
                        // Same reasoning as requisition/create.php: lock this
                        // to the editor's own command only when they're
                        // actually command-restricted, not just "isn't Super
                        // Admin" — HQ Armorer isn't Super Admin either but
                        // also isn't tied to one command.
                        $isCommandLocked = Auth::isCommandRestricted();
                        $userCommandId = Auth::commandId();
                        $selectedCommandId = $requisition['requesting_command_id'] ?? ($isCommandLocked ? $userCommandId : '');
                        $lockedCommandName = null;
                        if ($isCommandLocked) {
                            foreach ($commands as $cmd) {
                                if ((int) $cmd['id'] === (int) $selectedCommandId) {
                                    $lockedCommandName = $cmd['command_name'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <?php if ($isCommandLocked): ?>
                            <div class="form-control-static">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo $lockedCommandName
                                    ? Security::escape($lockedCommandName)
                                    : 'No command is assigned to your account — contact an administrator.'; ?>
                            </div>
                            <input type="hidden" name="requesting_command_id" value="<?php echo htmlspecialchars((string) $selectedCommandId); ?>">
                        <?php else: ?>
                            <select name="requesting_command_id" id="requesting_command_id" required
                                    class="form-control <?php echo isset($errors['requesting_command_id']) ? 'error' : ''; ?>">
                                <option value="">Select Command</option>
                                <?php foreach ($commands as $cmd): ?>
                                <option value="<?php echo $cmd['id']; ?>"
                                        <?php echo ($selectedCommandId == $cmd['id']) ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($cmd['command_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <?php if (isset($errors['requesting_command_id'])): ?>
                            <small class="error-text"><?php echo $errors['requesting_command_id']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Requisition Items -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-clipboard-list"></i> Requisition Items</h3>
                    <button type="button" class="btn btn-sm btn-success" onclick="addItemRow()">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="items-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Item Type</th>
                                <th>Weapon/Ammo Type</th>
                                <th>Calibre</th>
                                <th>Quantity</th>
                                <th>Purpose</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php foreach ($items as $index => $item): ?>
                            <tr class="item-row">
                                <td>
                                    <select name="item_type[]" class="form-control item-type" required onchange="handleItemTypeChange(this)">
                                        <option value="">Select</option>
                                        <option value="Weapon" <?php echo $item['item_type'] == 'Weapon' ? 'selected' : ''; ?>>Weapon</option>
                                        <option value="Ammunition" <?php echo $item['item_type'] == 'Ammunition' ? 'selected' : ''; ?>>Ammunition</option>
                                    </select>
                                </td>
                                <td class="type-field">
                                    <?php if ($item['item_type'] == 'Weapon'): ?>
                                        <select name="weapon_type_id[]" class="form-control weapon-type" onchange="handleWeaponTypeChange(this)">
                                            <option value="">Select Weapon Type</option>
                                            <?php foreach ($weaponTypes as $wt): ?>
                                            <option value="<?php echo $wt['id']; ?>" <?php echo $item['weapon_type_id'] == $wt['id'] ? 'selected' : ''; ?>>
                                                <?php echo Security::escape($wt['type_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                            <option value="other" <?php echo !empty($item['weapon_type_other']) ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <input type="text" name="weapon_type_other[]" class="form-control weapon-other" 
                                               value="<?php echo Security::escape($item['weapon_type_other'] ?? ''); ?>" 
                                               placeholder="Specify type" style="<?php echo !empty($item['weapon_type_other']) ? 'display: block;' : 'display: none;'; ?>">
                                    <?php elseif ($item['item_type'] == 'Ammunition'): ?>
                                        <select name="ammo_type_id[]" class="form-control ammo-type" onchange="handleAmmoTypeChange(this)">
                                            <option value="">Select Ammo Type</option>
                                            <?php foreach ($ammoTypes as $at): ?>
                                            <option value="<?php echo $at['id']; ?>" <?php echo $item['ammo_type_id'] == $at['id'] ? 'selected' : ''; ?>>
                                                <?php echo Security::escape($at['ammo_type']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                            <option value="other" <?php echo !empty($item['ammo_type_other']) ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <input type="text" name="ammo_type_other[]" class="form-control ammo-other" 
                                               value="<?php echo Security::escape($item['ammo_type_other'] ?? ''); ?>" 
                                               placeholder="Specify type" style="<?php echo !empty($item['ammo_type_other']) ? 'display: block;' : 'display: none;'; ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select name="calibre_id[]" class="form-control calibre-type" onchange="handleCalibreChange(this)">
                                        <option value="">Select Calibre</option>
                                        <?php 
                                        $activeCalibres = ($item['item_type'] == 'Weapon') ? $weaponCalibres : $ammoCalibres;
                                        foreach ($activeCalibres as $cal): 
                                            $calVal = ($item['item_type'] == 'Weapon') ? ($cal['calibre_name'] ?? '') : ($cal['calibre'] ?? '');
                                        ?>
                                        <option value="<?php echo $cal['id']; ?>" <?php echo $item['calibre_id'] == $cal['id'] ? 'selected' : ''; ?>>
                                            <?php echo Security::escape($calVal); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <option value="other" <?php echo !empty($item['calibre_other']) ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <input type="text" name="calibre_other[]" class="form-control calibre-other" 
                                           value="<?php echo Security::escape($item['calibre_other'] ?? ''); ?>" 
                                           placeholder="Specify calibre" style="<?php echo !empty($item['calibre_other']) ? 'display: block;' : 'display: none;'; ?>">
                                </td>
                                <td>
                                    <input type="number" name="quantity[]" class="form-control" required min="1"
                                           value="<?php echo $item['quantity']; ?>">
                                </td>
                                <td>
                                    <select name="purpose[]" class="form-control purpose-select" onchange="handlePurposeChange(this)">
                                        <option value="">Select Purpose</option>
                                        <option value="Training" <?php echo ($item['purpose'] ?? '') == 'Training' ? 'selected' : ''; ?>>Training</option>
                                        <option value="Operation" <?php echo ($item['purpose'] ?? '') == 'Operation' ? 'selected' : ''; ?>>Operation</option>
                                        <option value="Patrol" <?php echo ($item['purpose'] ?? '') == 'Patrol' ? 'selected' : ''; ?>>Patrol</option>
                                        <option value="Maintenance" <?php echo ($item['purpose'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="Other" <?php echo !empty($item['purpose_other']) ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <input type="text" name="purpose_other[]" class="form-control purpose-other" 
                                           value="<?php echo Security::escape($item['purpose_other'] ?? ''); ?>" 
                                           placeholder="Specify purpose" style="<?php echo !empty($item['purpose_other']) ? 'display: block;' : 'display: none;'; ?>">
                                </td>
                                <td>
                                    <input type="text" name="item_remarks[]" class="form-control" 
                                           value="<?php echo Security::escape($item['remarks'] ?? ''); ?>">
                                </td>
                                <td>
                                    <button type="button" class="btn-icon delete" onclick="removeItemRow(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (isset($errors['items'])): ?>
                    <small class="error-text"><?php echo $errors['items']; ?></small>
                <?php endif; ?>
            </div>
            
            <!-- Justification -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-pen"></i> Justification</h3>
                </div>
                
                <div class="form-group">
                    <label class="required">Justification</label>
                    <textarea name="justification" rows="4" required 
                              class="form-control <?php echo isset($errors['justification']) ? 'error' : ''; ?>"><?php echo Security::escape($requisition['justification']); ?></textarea>
                    <?php if (isset($errors['justification'])): ?>
                        <small class="error-text"><?php echo $errors['justification']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Remarks -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-sticky-note"></i> Additional Remarks</h3>
                </div>
                
                <div class="form-group">
                    <textarea name="remarks" rows="3" class="form-control"><?php echo Security::escape($requisition['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <?php if (($requisition['status'] ?? '') === 'Draft'): ?>
                    <button type="button" class="btn btn-outline" onclick="saveAsDraft()" style="border: 1px solid #207027; color: #207027; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-save"></i> Save as Draft
                    </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Requisition
                </button>
                <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $requisition['id']; ?>" class="btn btn-outline">
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
// Item data from server
const weaponTypes = <?php echo json_encode($weaponTypes); ?>;
const ammunitionTypes = <?php echo json_encode($ammoTypes); ?>; // ammoTypes in edit view
const nonLethalTypes = [
    'Taser', 'Pepper Spray', 'Batons', 'Rubber Bullets', 'Bean Bag Rounds',
    'Stun Grenade', 'Water Cannon', 'Tear Gas', 'Riot Shield', 'Riot Helmet',
    'Riot Gear', 'Long Range Acoustic Device', 'Net Gun'
];
const weaponCalibres = <?php echo json_encode($weaponCalibres); ?>;
const ammunitionCalibres = <?php echo json_encode($ammoCalibres); ?>;

function addItemRow() {
    const tbody = document.getElementById('itemsBody');
    const newRow = document.createElement('tr');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <td>
            <select name="item_type[]" class="form-control item-type" required onchange="handleItemTypeChange(this)">
                <option value="">Select</option>
                <option value="Weapon">Weapon</option>
                <option value="Ammunition">Ammunition</option>
                <option value="Non-Lethal">Non-Lethal</option>
            </select>
        </td>
        <td class="type-field"></td>
        <td>
            <select name="calibre_id[]" class="form-control calibre-type" onchange="handleCalibreChange(this)">
                <option value="">Select Calibre</option>
            </select>
            <input type="text" name="calibre_other[]" class="form-control calibre-other" 
                   placeholder="Specify calibre" style="display: none;">
        </td>
        <td>
            <input type="number" name="quantity[]" class="form-control" required min="1" value="1">
        </td>
        <td>
            <select name="purpose[]" class="form-control purpose-select" onchange="handlePurposeChange(this)">
                <option value="">Select Purpose</option>
                <option value="Training">Training</option>
                <option value="Operation">Operation</option>
                <option value="Patrol">Patrol</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Other">Other</option>
            </select>
            <input type="text" name="purpose_other[]" class="form-control purpose-other" 
                   placeholder="Specify purpose" style="display: none;">
        </td>
        <td>
            <input type="text" name="item_remarks[]" class="form-control">
        </td>
        <td>
            <button type="button" class="btn-icon delete" onclick="removeItemRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    filterItemTypes();
    updateRemoveButtons();
}

function removeItemRow(button) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    if (tbody.children.length > 1) {
        row.remove();
    } else {
        showNotification('warning', 'At least one item is required');
    }
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.item-row');
    const removeButtons = document.querySelectorAll('.item-row .delete');
    
    if (rows.length <= 1) {
        removeButtons.forEach(btn => btn.disabled = true);
    } else {
        removeButtons.forEach(btn => btn.disabled = false);
    }
}

function filterItemTypes() {
    const requisitionType = document.getElementById('requisition_type').value;
    const itemSelects = document.querySelectorAll('.item-type');
    
    itemSelects.forEach(select => {
        const currentValue = select.value;
        select.innerHTML = '<option value="">Select</option>';
        
        if (requisitionType === 'Weapon') {
            select.innerHTML += '<option value="Weapon">Weapon</option>';
            if (currentValue === 'Weapon') select.value = 'Weapon';
        } else if (requisitionType === 'Ammunition') {
            select.innerHTML += '<option value="Ammunition">Ammunition</option>';
            if (currentValue === 'Ammunition') select.value = 'Ammunition';
        } else if (requisitionType === 'Non-Lethal') {
            select.innerHTML += '<option value="Non-Lethal">Non-Lethal</option>';
            if (currentValue === 'Non-Lethal') select.value = 'Non-Lethal';
        } else if (requisitionType === 'Both') {
            select.innerHTML += '<option value="Weapon">Weapon</option>';
            select.innerHTML += '<option value="Ammunition">Ammunition</option>';
            if (currentValue === 'Weapon' || currentValue === 'Ammunition') select.value = currentValue;
        } else {
            select.innerHTML += '<option value="Weapon">Weapon</option>';
            select.innerHTML += '<option value="Ammunition">Ammunition</option>';
            select.innerHTML += '<option value="Non-Lethal">Non-Lethal</option>';
            if (currentValue) select.value = currentValue;
        }
        
        if (currentValue && !select.value) {
            handleItemTypeChange(select);
        }
    });
}

function handleItemTypeChange(select) {
    const row = select.closest('tr');
    const typeField = row.querySelector('.type-field');
    const calibreCell = row.querySelector('td:nth-child(3)');
    const selectedType = select.value;
    
    // Save current values if they exist to restore them
    const prevWeaponType = row.querySelector('.weapon-type') ? row.querySelector('.weapon-type').value : '';
    const prevWeaponOther = row.querySelector('.weapon-other') ? row.querySelector('.weapon-other').value : '';
    const prevAmmoType = row.querySelector('.ammo-type') ? row.querySelector('.ammo-type').value : '';
    const prevAmmoOther = row.querySelector('.ammo-other') ? row.querySelector('.ammo-other').value : '';
    const prevNLType = row.querySelector('.non-lethal-type') ? row.querySelector('.non-lethal-type').value : '';
    const prevNLOther = row.querySelector('.non-lethal-other') ? row.querySelector('.non-lethal-other').value : '';
    const prevCalibre = row.querySelector('.calibre-type') ? row.querySelector('.calibre-type').value : '';
    const prevCalibreOther = row.querySelector('.calibre-other') ? row.querySelector('.calibre-other').value : '';
    const prevSpec = row.querySelector('.specification') ? row.querySelector('.specification').value : '';
    
    if (selectedType === 'Weapon') {
        let options = '<option value="">Select Weapon Type</option>';
        weaponTypes.forEach(wt => {
            options += `<option value="${wt.id}">${escapeHtml(wt.type_name)}</option>`;
        });
        options += '<option value="other">Other</option>';
        
        typeField.innerHTML = `
            <select name="weapon_type_id[]" class="form-control weapon-type" onchange="handleWeaponTypeChange(this)">
                ${options}
            </select>
            <input type="text" name="weapon_type_other[]" class="form-control weapon-other" 
                   placeholder="Specify type" style="display: none;">
        `;
        
        // Restore calibre dropdown for weapons
        let calibreOptions = '<option value="">Select Calibre</option>';
        weaponCalibres.forEach(cal => {
            calibreOptions += `<option value="${cal.id}">${escapeHtml(cal.calibre_name)}</option>`;
        });
        calibreOptions += '<option value="other">Other</option>';
        
        calibreCell.innerHTML = `
            <select name="calibre_id[]" class="form-control calibre-type" onchange="handleCalibreChange(this)">
                ${calibreOptions}
            </select>
            <input type="text" name="calibre_other[]" class="form-control calibre-other" 
                   placeholder="Specify calibre" style="display: none;">
        `;
        
        // Restore values
        if (prevWeaponType) {
            row.querySelector('.weapon-type').value = prevWeaponType;
            handleWeaponTypeChange(row.querySelector('.weapon-type'));
        }
        if (prevWeaponOther) {
            row.querySelector('.weapon-other').value = prevWeaponOther;
        }
        if (prevCalibre) {
            row.querySelector('.calibre-type').value = prevCalibre;
            handleCalibreChange(row.querySelector('.calibre-type'));
        }
        if (prevCalibreOther) {
            row.querySelector('.calibre-other').value = prevCalibreOther;
        }
    } else if (selectedType === 'Ammunition') {
        let options = '<option value="">Select Ammo Type</option>';
        ammunitionTypes.forEach(at => {
            options += `<option value="${at.id}">${escapeHtml(at.ammo_type)}</option>`;
        });
        options += '<option value="other">Other</option>';
        
        typeField.innerHTML = `
            <select name="ammo_type_id[]" class="form-control ammo-type" onchange="handleAmmoTypeChange(this)">
                ${options}
            </select>
            <input type="text" name="ammo_type_other[]" class="form-control ammo-other" 
                   placeholder="Specify type" style="display: none;">
        `;
        
        // Restore calibre dropdown for ammunition
        let calibreOptions = '<option value="">Select Calibre</option>';
        ammunitionCalibres.forEach(cal => {
            calibreOptions += `<option value="${cal.id}">${escapeHtml(cal.calibre)}</option>`;
        });
        calibreOptions += '<option value="other">Other</option>';
        
        calibreCell.innerHTML = `
            <select name="calibre_id[]" class="form-control calibre-type" onchange="handleCalibreChange(this)">
                ${calibreOptions}
            </select>
            <input type="text" name="calibre_other[]" class="form-control calibre-other" 
                   placeholder="Specify calibre" style="display: none;">
        `;
        
        // Restore values
        if (prevAmmoType) {
            row.querySelector('.ammo-type').value = prevAmmoType;
            handleAmmoTypeChange(row.querySelector('.ammo-type'));
        }
        if (prevAmmoOther) {
            row.querySelector('.ammo-other').value = prevAmmoOther;
        }
        if (prevCalibre) {
            row.querySelector('.calibre-type').value = prevCalibre;
            handleCalibreChange(row.querySelector('.calibre-type'));
        }
        if (prevCalibreOther) {
            row.querySelector('.calibre-other').value = prevCalibreOther;
        }
    } else if (selectedType === 'Non-Lethal') {
        let options = '<option value="">Select Non-Lethal Type</option>';
        nonLethalTypes.forEach(nl => {
            options += `<option value="${escapeHtml(nl)}">${escapeHtml(nl)}</option>`;
        });
        options += '<option value="other">Other</option>';
        
        typeField.innerHTML = `
            <select name="non_lethal_type[]" class="form-control non-lethal-type" onchange="handleNonLethalTypeChange(this)">
                ${options}
            </select>
            <input type="text" name="non_lethal_type_other[]" class="form-control non-lethal-other" 
                   placeholder="Specify non-lethal item" style="display: none;">
        `;
        
        calibreCell.innerHTML = `
            <input type="text" name="specification[]" class="form-control specification" 
                   placeholder="Enter specification (e.g., model, size)">
        `;
        
        // Restore values
        if (prevNLType) {
            row.querySelector('.non-lethal-type').value = prevNLType;
            handleNonLethalTypeChange(row.querySelector('.non-lethal-type'));
        }
        if (prevNLOther) {
            row.querySelector('.non-lethal-other').value = prevNLOther;
        }
        if (prevSpec) {
            row.querySelector('.specification').value = prevSpec;
        }
    } else {
        typeField.innerHTML = '';
        calibreCell.innerHTML = `
            <select name="calibre_id[]" class="form-control calibre-type" onchange="handleCalibreChange(this)">
                <option value="">Select Calibre</option>
            </select>
            <input type="text" name="calibre_other[]" class="form-control calibre-other" 
                   placeholder="Specify calibre" style="display: none;">
        `;
    }
}

function handleWeaponTypeChange(select) {
    const row = select.closest('tr');
    const otherInput = row.querySelector('.weapon-other');
    if (select.value === 'other') {
        otherInput.style.display = 'block';
        otherInput.required = true;
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
}

function handleAmmoTypeChange(select) {
    const row = select.closest('tr');
    const otherInput = row.querySelector('.ammo-other');
    if (select.value === 'other') {
        otherInput.style.display = 'block';
        otherInput.required = true;
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
}

function handleNonLethalTypeChange(select) {
    const row = select.closest('tr');
    const otherInput = row.querySelector('.non-lethal-other');
    if (select.value === 'other') {
        otherInput.style.display = 'block';
        otherInput.required = true;
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
}

function handleCalibreChange(select) {
    const row = select.closest('tr');
    const otherInput = row.querySelector('.calibre-other');
    if (select.value === 'other') {
        otherInput.style.display = 'block';
        otherInput.required = true;
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
}

function handlePurposeChange(select) {
    const row = select.closest('tr');
    const otherInput = row.querySelector('.purpose-other');
    if (select.value === 'Other') {
        otherInput.style.display = 'block';
        otherInput.required = true;
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize existing rows with proper handlers
document.addEventListener('DOMContentLoaded', function() {
    // Filter item types on page load based on requisition type
    filterItemTypes();
    
    // Add event listener for requisition type change
    const reqTypeSelect = document.getElementById('requisition_type');
    if (reqTypeSelect) {
        reqTypeSelect.addEventListener('change', filterItemTypes);
    }
    
    document.querySelectorAll('.item-type').forEach(select => {
        if (select.value) {
            handleItemTypeChange(select);
        }
    });
    
    document.querySelectorAll('.weapon-type').forEach(select => {
        if (select.value === 'other') {
            const row = select.closest('tr');
            const otherInput = row.querySelector('.weapon-other');
            if (otherInput) otherInput.style.display = 'block';
        }
    });
    
    document.querySelectorAll('.ammo-type').forEach(select => {
        if (select.value === 'other') {
            const row = select.closest('tr');
            const otherInput = row.querySelector('.ammo-other');
            if (otherInput) otherInput.style.display = 'block';
        }
    });
    
    document.querySelectorAll('.calibre-type').forEach(select => {
        if (select.value === 'other') {
            const row = select.closest('tr');
            const otherInput = row.querySelector('.calibre-other');
            if (otherInput) otherInput.style.display = 'block';
        }
    });
    
    document.querySelectorAll('.purpose-select').forEach(select => {
        if (select.value === 'Other') {
            const row = select.closest('tr');
            const otherInput = row.querySelector('.purpose-other');
            if (otherInput) otherInput.style.display = 'block';
        }
    });
    
function saveAsDraft() {
    const form = document.querySelector('form');
    const draftInput = document.createElement('input');
    draftInput.type = 'hidden';
    draftInput.name = 'status';
    draftInput.value = 'Draft';
    form.appendChild(draftInput);
    form.submit();
}

    updateRemoveButtons();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
