<?php
$title = 'Create Requisition';
$active = 'requisitions';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Get data from controller
$weaponTypes = isset($weaponTypes) ? $weaponTypes : [];
$commands = isset($commands) ? $commands : [];

// Hardcoded ammunition types
$ammunitionTypes = [
    '7.62x51mm Live', '7.62x51mm Blank', '7.62x51mm Tracer',
    '7.62x39mm Live', '7.62x39mm Blank', '7.62x39mm Tracer',
    '5.56x45mm Live', '5.56x45mm Blank', '5.56x45mm Tracer',
    '9x19mm Live', '9x19mm Blank', '9x19mm Tracer',
    '12 Gauge Buckshot', '12 Gauge Slug', '12 Gauge Training',
    '.45 ACP Live', '.45 ACP Blank',
    '5.7x28mm Live', '5.7x28mm Blank'
];

// Hardcoded non-lethal types
$nonLethalTypes = [
    'Taser', 'Pepper Spray', 'Batons', 'Rubber Bullets', 'Bean Bag Rounds',
    'Stun Grenade', 'Water Cannon', 'Tear Gas', 'Riot Shield', 'Riot Helmet',
    'Riot Gear', 'Long Range Acoustic Device', 'Net Gun'
];

$weaponCalibres = isset($weaponCalibres) ? $weaponCalibres : [];
$ammoCalibres = isset($ammoCalibres) ? $ammoCalibres : [];

$old = Session::get('old', []);
$errors = Session::get('errors', []);
Session::remove('old');
Session::remove('errors');

// Generate CSRF token using Security class
$csrfToken = Security::csrfToken();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Create New Requisition
            </h1>
            <p>Request weapons, ammunition, and non-lethal equipment</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/requisition" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Requisitions
            </a>
        </div>
    </div>

    <!-- Display validation errors -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <strong>Please fix the following errors:</strong>
        <ul>
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo htmlspecialchars(is_array($error) ? implode(', ', $error) : $error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/requisition/store" id="requisitionForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <!-- Requisition Header -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-signature"></i> Requisition Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Requisition Date</label>
                        <input type="date" name="requisition_date" id="requisition_date" 
                               value="<?php echo htmlspecialchars($old['requisition_date'] ?? date('Y-m-d')); ?>" 
                               required class="form-control <?php echo isset($errors['requisition_date']) ? 'error' : ''; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['requisition_date'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['requisition_date']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Priority Level</label>
                        <select name="priority_level" id="priority_level" required 
                                class="form-control <?php echo isset($errors['priority_level']) ? 'error' : ''; ?>">
                            <option value="">Select Priority</option>
                            <option value="Low" <?php echo ($old['priority_level'] ?? '') == 'Low' ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo ($old['priority_level'] ?? '') == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo ($old['priority_level'] ?? '') == 'High' ? 'selected' : ''; ?>>High</option>
                            <option value="Urgent" <?php echo ($old['priority_level'] ?? '') == 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                        <?php if (isset($errors['priority_level'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['priority_level']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Requisition Type</label>
                        <select name="requisition_type" id="requisition_type" required 
                                class="form-control <?php echo isset($errors['requisition_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="Weapon" <?php echo ($old['requisition_type'] ?? '') == 'Weapon' ? 'selected' : ''; ?>>Weapon Only</option>
                            <option value="Ammunition" <?php echo ($old['requisition_type'] ?? '') == 'Ammunition' ? 'selected' : ''; ?>>Ammunition Only</option>
                            <option value="Non-Lethal" <?php echo ($old['requisition_type'] ?? '') == 'Non-Lethal' ? 'selected' : ''; ?>>Non-Lethal Only</option>
                            <option value="Both" <?php echo ($old['requisition_type'] ?? '') == 'Both' ? 'selected' : ''; ?>>Weapons & Ammunition</option>
                            <option value="All" <?php echo ($old['requisition_type'] ?? '') == 'All' ? 'selected' : ''; ?>>All Types</option>
                        </select>
                        <?php if (isset($errors['requisition_type'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['requisition_type']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Expected Return Date</label>
                        <input type="date" name="expected_return_date" id="expected_return_date" 
                               value="<?php echo htmlspecialchars($old['expected_return_date'] ?? ''); ?>" 
                               class="form-control">
                        <small class="form-hint">For temporary issues only</small>
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
                               value="<?php echo htmlspecialchars($old['requesting_officer_name'] ?? ''); ?>" 
                               required maxlength="255" pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                               class="form-control <?php echo isset($errors['requesting_officer_name']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['requesting_officer_name'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['requesting_officer_name']); ?></small>
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
                                        <?php echo ($old['requesting_rank'] ?? '') === $rank ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rank); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['requesting_rank'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['requesting_rank']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">NIS Number</label>
                        <input type="text" name="requesting_nis"
                               value="<?php echo htmlspecialchars($old['requesting_nis'] ?? ''); ?>"
                               required minlength="4" maxlength="5" inputmode="numeric" pattern="[0-9]{4,5}" title="NIS Number must be 4 or 5 digits"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 5)"
                               class="form-control <?php echo isset($errors['requesting_nis']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['requesting_nis'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['requesting_nis']); ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Must be 4 or 5 digits (e.g. 1234 or 12345)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Phone Number</label>
                        <input type="tel" name="requesting_phone"
                               value="<?php echo htmlspecialchars($old['requesting_phone'] ?? ''); ?>"
                               required minlength="11" maxlength="11" inputmode="numeric" pattern="\d{11}" title="Phone number must be exactly 11 digits"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                               class="form-control <?php echo isset($errors['requesting_phone']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['requesting_phone'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['requesting_phone']); ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Must be exactly 11 digits (e.g. 08012345678)</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Command/Formation</label>
                        <?php
                        // Locked to the requester's own command whenever
                        // they're command-restricted (Command Armorer) —
                        // not just for non-Super-Admins: HQ Armorer isn't
                        // Super Admin either, but also isn't tied to one
                        // command, so they still need to pick one.
                        $isCommandLocked = Auth::isCommandRestricted();
                        $userCommandId = Auth::commandId();
                        $selectedCommandId = $old['requesting_command_id'] ?? ($isCommandLocked ? $userCommandId : '');
                        $lockedCommandName = null;
                        if ($isCommandLocked) {
                            foreach ($commands as $cmd) {
                                if ((int) $cmd['id'] === (int) $userCommandId) {
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
                                    ? htmlspecialchars($lockedCommandName)
                                    : 'No command is assigned to your account — contact an administrator.'; ?>
                            </div>
                            <input type="hidden" name="requesting_command_id" value="<?php echo htmlspecialchars((string) $userCommandId); ?>">
                        <?php else: ?>
                            <select name="requesting_command_id" id="requesting_command_id" required
                                    class="form-control <?php echo isset($errors['requesting_command_id']) ? 'error' : ''; ?>">
                                <option value="">Select Command</option>
                                <?php foreach ($commands as $cmd): ?>
                                <option value="<?php echo $cmd['id']; ?>"
                                        <?php echo ($selectedCommandId == $cmd['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cmd['command_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <?php if (isset($errors['requesting_command_id'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['requesting_command_id']); ?></small>
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
                                <th>Item Details</th>
                                <th>Calibre/Specification</th>
                                <th>Quantity</th>
                                <th>Purpose</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php if (isset($old['item_type']) && is_array($old['item_type']) && count($old['item_type']) > 0): ?>
                                <?php foreach ($old['item_type'] as $index => $type): ?>
                                <tr class="item-row" data-index="<?php echo $index; ?>">
                                    <td>
                                        <select name="item_type[]" class="form-control item-type" required onchange="handleItemTypeChange(this)">
                                            <option value="">Select</option>
                                            <option value="Weapon" <?php echo $type == 'Weapon' ? 'selected' : ''; ?>>Weapon</option>
                                            <option value="Ammunition" <?php echo $type == 'Ammunition' ? 'selected' : ''; ?>>Ammunition</option>
                                            <option value="Non-Lethal" <?php echo $type == 'Non-Lethal' ? 'selected' : ''; ?>>Non-Lethal</option>
                                        </select>
                                    </td>
                                    <td class="type-field">
                                        <?php if ($type == 'Weapon'): ?>
                                            <select name="weapon_type_id[]" class="form-control weapon-type" onchange="handleWeaponTypeChange(this)">
                                                <option value="">Select Weapon Type</option>
                                                <?php foreach ($weaponTypes as $wt): ?>
                                                <option value="<?php echo $wt['id']; ?>" <?php echo ($old['weapon_type_id'][$index] ?? '') == $wt['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($wt['type_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                                <option value="other" <?php echo ($old['weapon_type_id'][$index] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                            <input type="text" name="weapon_type_other[]" class="form-control weapon-other" 
                                                   value="<?php echo htmlspecialchars($old['weapon_type_other'][$index] ?? ''); ?>" 
                                                   placeholder="Specify weapon type" style="<?php echo ($old['weapon_type_id'][$index] ?? '') == 'other' ? 'display:block;' : 'display:none;'; ?>">
                                        <?php elseif ($type == 'Ammunition'): ?>
                                            <select name="ammunition_type[]" class="form-control ammunition-type" onchange="handleAmmunitionTypeChange(this)">
                                                <option value="">Select Ammunition Type</option>
                                                <?php foreach ($ammunitionTypes as $ammo): ?>
                                                <option value="<?php echo htmlspecialchars($ammo); ?>" <?php echo ($old['ammunition_type'][$index] ?? '') == $ammo ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ammo); ?>
                                                </option>
                                                <?php endforeach; ?>
                                                <option value="other">Other</option>
                                            </select>
                                            <input type="text" name="ammunition_type_other[]" class="form-control ammunition-other" 
                                                   value="<?php echo htmlspecialchars($old['ammunition_type_other'][$index] ?? ''); ?>" 
                                                   placeholder="Specify ammunition type" style="<?php echo ($old['ammunition_type'][$index] ?? '') == 'other' ? 'display:block;' : 'display:none;'; ?>">
                                        <?php elseif ($type == 'Non-Lethal'): ?>
                                            <select name="non_lethal_type[]" class="form-control non-lethal-type" onchange="handleNonLethalTypeChange(this)">
                                                <option value="">Select Non-Lethal Type</option>
                                                <?php foreach ($nonLethalTypes as $nl): ?>
                                                <option value="<?php echo htmlspecialchars($nl); ?>" <?php echo ($old['non_lethal_type'][$index] ?? '') == $nl ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($nl); ?>
                                                </option>
                                                <?php endforeach; ?>
                                                <option value="other">Other</option>
                                            </select>
                                            <input type="text" name="non_lethal_type_other[]" class="form-control non-lethal-other" 
                                                   value="<?php echo htmlspecialchars($old['non_lethal_type_other'][$index] ?? ''); ?>" 
                                                   placeholder="Specify non-lethal item" style="<?php echo ($old['non_lethal_type'][$index] ?? '') == 'other' ? 'display:block;' : 'display:none;'; ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($type == 'Non-Lethal'): ?>
                                            <input type="text" name="specification[]" class="form-control specification" 
                                                   value="<?php echo htmlspecialchars($old['specification'][$index] ?? ''); ?>" 
                                                   placeholder="Enter specification (e.g., model, size)">
                                        <?php else: ?>
                                            <select name="calibre_id[]" class="form-control calibre-type" onchange="handleCalibreChange(this)">
                                                <option value="">Select Calibre</option>
                                                <?php 
                                                $activeCalibres = ($type == 'Weapon') ? $weaponCalibres : $ammoCalibres;
                                                foreach ($activeCalibres as $cal): 
                                                    $calVal = ($type == 'Weapon') ? ($cal['calibre_name'] ?? '') : ($cal['calibre'] ?? '');
                                                ?>
                                                <option value="<?php echo $cal['id']; ?>" <?php echo ($old['calibre_id'][$index] ?? '') == $cal['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($calVal); ?>
                                                </option>
                                                <?php endforeach; ?>
                                                <option value="other" <?php echo ($old['calibre_id'][$index] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                            <input type="text" name="calibre_other[]" class="form-control calibre-other" 
                                                   value="<?php echo htmlspecialchars($old['calibre_other'][$index] ?? ''); ?>" 
                                                   placeholder="Specify calibre" style="<?php echo ($old['calibre_id'][$index] ?? '') == 'other' ? 'display:block;' : 'display:none;'; ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" name="quantity[]" class="form-control quantity-input" required min="1"
                                               value="<?php echo htmlspecialchars($old['quantity'][$index] ?? 1); ?>">
                                    </td>
                                    <td>
                                        <select name="purpose[]" class="form-control purpose-select" onchange="handlePurposeChange(this)">
                                            <option value="">Select Purpose</option>
                                            <option value="Training" <?php echo ($old['purpose'][$index] ?? '') == 'Training' ? 'selected' : ''; ?>>Training</option>
                                            <option value="Operation" <?php echo ($old['purpose'][$index] ?? '') == 'Operation' ? 'selected' : ''; ?>>Operation</option>
                                            <option value="Patrol" <?php echo ($old['purpose'][$index] ?? '') == 'Patrol' ? 'selected' : ''; ?>>Patrol</option>
                                            <option value="Maintenance" <?php echo ($old['purpose'][$index] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="Crowd Control" <?php echo ($old['purpose'][$index] ?? '') == 'Crowd Control' ? 'selected' : ''; ?>>Crowd Control</option>
                                            <option value="Riot Control" <?php echo ($old['purpose'][$index] ?? '') == 'Riot Control' ? 'selected' : ''; ?>>Riot Control</option>
                                            <option value="Other" <?php echo ($old['purpose'][$index] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <input type="text" name="purpose_other[]" class="form-control purpose-other" 
                                               value="<?php echo htmlspecialchars($old['purpose_other'][$index] ?? ''); ?>" 
                                               placeholder="Specify purpose" style="<?php echo ($old['purpose'][$index] ?? '') == 'Other' ? 'display:block;' : 'display:none;'; ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="item_remarks[]" class="form-control" 
                                               value="<?php echo htmlspecialchars($old['item_remarks'][$index] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-icon delete" onclick="removeItemRow(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr class="item-row" data-index="0">
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
                                    <input type="number" name="quantity[]" class="form-control quantity-input" required min="1" value="1">
                                </td>
                                <td>
                                    <select name="purpose[]" class="form-control purpose-select" onchange="handlePurposeChange(this)">
                                        <option value="">Select Purpose</option>
                                        <option value="Training">Training</option>
                                        <option value="Operation">Operation</option>
                                        <option value="Patrol">Patrol</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Crowd Control">Crowd Control</option>
                                        <option value="Riot Control">Riot Control</option>
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
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; font-weight: bold;">Total Items:</td>
                                <td>
                                    <input type="number" id="totalItems" readonly value="<?php echo array_sum($old['quantity'] ?? [1]); ?>" style="background-color: #e8f5e8; font-weight: bold; text-align: center; width: 100%; padding: 8px;">
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php if (isset($errors['items'])): ?>
                    <small class="error-text"><?php echo htmlspecialchars($errors['items']); ?></small>
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
                              class="form-control <?php echo isset($errors['justification']) ? 'error' : ''; ?>"
                              placeholder="Provide detailed justification for this requisition"><?php echo htmlspecialchars($old['justification'] ?? ''); ?></textarea>
                    <?php if (isset($errors['justification'])): ?>
                        <small class="error-text"><?php echo htmlspecialchars($errors['justification']); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Remarks -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-sticky-note"></i> Additional Remarks</h3>
                </div>
                
                <div class="form-group">
                    <textarea name="remarks" rows="3" class="form-control"><?php echo htmlspecialchars($old['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Requisition
                </button>
                <button type="button" class="btn btn-secondary" onclick="saveAsDraft()">
                    <i class="fas fa-save"></i> Save as Draft
                </button>
                <a href="<?php echo BASE_URL; ?>/requisition" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
:root {
    --primary-color: #134617;
    --primary-light: #207027;
    --secondary-color: #207027;
    --secondary-dark: #134617;
    --success-color: #207027;
    --danger-color: #B42318;
    --warning-color: #C69214;
    --info-color: #1F6F8B;
    --light-bg: #F7FAF8;
    --border-color: #D7E3DC;
    --text-primary: #212529;
    --text-secondary: #53665E;
}
[data-theme="dark"] {
    --primary-color: #299631;
    --primary-light: #37bf43;
    --secondary-color: #37bf43;
    --secondary-dark: #299631;
    --success-color: #37bf43;
    --danger-color: #e7564b;
    --warning-color: #eec052;
    --info-color: #3cacd4;
    --light-bg: #1a231d;
    --border-color: #2f3832;
    --text-primary: #d8e9d9;
    --text-secondary: #dfe2e1;
}


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

/* Form Row */
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
    color: var(--text-primary);
    margin-bottom: 8px;
}

.required::after {
    content: " *";
    color: var(--danger-color);
}

.form-control {
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--success-color);
    box-shadow: 0 0 0 3px rgba(32, 112, 39, 0.2);
}

.form-control.error {
    border-color: var(--danger-color);
    background-color: #fff5f5;
}

.form-control-static {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.95rem;
    background: var(--light-bg, #f7faf8);
    color: var(--text-primary, #212529);
}

.form-control-static i {
    color: var(--success-color);
}

.error-text {
    color: var(--danger-color);
    font-size: 0.85rem;
    margin-top: 5px;
}

.form-hint {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-top: 5px;
}

/* Alert */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert ul {
    margin: 10px 0 0 20px;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
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
    background: var(--success-color);
    color: white;
}

.btn-success:hover {
    background: var(--secondary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(32, 112, 39, 0.3);
}

.btn-secondary {
    background: var(--text-secondary);
    color: white;
}

.btn-secondary:hover {
    background: #6c757d;
}

.btn-outline {
    background: transparent;
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}

.btn-outline:hover {
    background: var(--light-bg);
    color: var(--text-primary);
    border-color: var(--success-color);
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: var(--light-bg);
    color: var(--text-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-icon:hover {
    background: var(--danger-color);
    color: white;
}

.btn-icon.delete:hover {
    background: var(--danger-color);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .items-table {
        font-size: 0.8rem;
    }
    
    .items-table td {
        padding: 5px;
    }
}
</style>

<script>
// Item data from server
const weaponTypes = <?php echo json_encode($weaponTypes); ?>;
const ammunitionTypes = <?php echo json_encode($ammunitionTypes); ?>;
const nonLethalTypes = <?php echo json_encode($nonLethalTypes); ?>;
const weaponCalibres = <?php echo json_encode($weaponCalibres); ?>;
const ammunitionCalibres = <?php echo json_encode($ammoCalibres); ?>;

let itemCounter = <?php echo isset($old['item_type']) ? count($old['item_type']) : 1; ?>;

function addItemRow() {
    const tbody = document.getElementById('itemsBody');
    const newRow = document.createElement('tr');
    newRow.className = 'item-row';
    newRow.setAttribute('data-index', itemCounter);
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
            <input type="number" name="quantity[]" class="form-control quantity-input" required min="1" value="1" onchange="calculateTotals()">
        </td>
        <td>
            <select name="purpose[]" class="form-control purpose-select" onchange="handlePurposeChange(this)">
                <option value="">Select Purpose</option>
                <option value="Training">Training</option>
                <option value="Operation">Operation</option>
                <option value="Patrol">Patrol</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Crowd Control">Crowd Control</option>
                <option value="Riot Control">Riot Control</option>
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
    itemCounter++;
    filterItemTypes();
    updateRemoveButtons();
    calculateTotals();
}

function removeItemRow(button) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    if (tbody.children.length > 1) {
        row.remove();
        calculateTotals();
    } else {
        alert('At least one item is required');
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

function calculateTotals() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    let total = 0;
    quantityInputs.forEach(input => {
        total += parseInt(input.value) || 0;
    });
    document.getElementById('totalItems').value = total;
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
    const prevAmmoType = row.querySelector('.ammunition-type') ? row.querySelector('.ammunition-type').value : '';
    const prevAmmoOther = row.querySelector('.ammunition-other') ? row.querySelector('.ammunition-other').value : '';
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
                   placeholder="Specify weapon type" style="display: none;">
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
        let options = '<option value="">Select Ammunition Type</option>';
        ammunitionTypes.forEach(ammo => {
            options += `<option value="${escapeHtml(ammo)}">${escapeHtml(ammo)}</option>`;
        });
        options += '<option value="other">Other</option>';
        
        typeField.innerHTML = `
            <select name="ammunition_type[]" class="form-control ammunition-type" onchange="handleAmmunitionTypeChange(this)">
                ${options}
            </select>
            <input type="text" name="ammunition_type_other[]" class="form-control ammunition-other" 
                   placeholder="Specify ammunition type" style="display: none;">
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
            row.querySelector('.ammunition-type').value = prevAmmoType;
            handleAmmunitionTypeChange(row.querySelector('.ammunition-type'));
        }
        if (prevAmmoOther) {
            row.querySelector('.ammunition-other').value = prevAmmoOther;
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

function handleAmmunitionTypeChange(select) {
    const row = select.closest('tr');
    const otherInput = row.querySelector('.ammunition-other');
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

function saveAsDraft() {
    const form = document.getElementById('requisitionForm');
    const draftInput = document.createElement('input');
    draftInput.type = 'hidden';
    draftInput.name = 'status';
    draftInput.value = 'Draft';
    form.appendChild(draftInput);
    form.submit();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize existing rows
    document.querySelectorAll('.item-type').forEach(select => {
        if (select.value) {
            handleItemTypeChange(select);
            
            // Set selected values for existing rows
            setTimeout(() => {
                const row = select.closest('tr');
                
                // Set weapon type if exists
                const weaponSelect = row.querySelector('.weapon-type');
                if (weaponSelect && weaponSelect.value) {
                    if (weaponSelect.value === 'other') {
                        const otherInput = row.querySelector('.weapon-other');
                        if (otherInput) otherInput.style.display = 'block';
                    }
                }
                
                // Set ammunition type if exists
                const ammoSelect = row.querySelector('.ammunition-type');
                if (ammoSelect && ammoSelect.value) {
                    if (ammoSelect.value === 'other') {
                        const otherInput = row.querySelector('.ammunition-other');
                        if (otherInput) otherInput.style.display = 'block';
                    }
                }
                
                // Set non-lethal type if exists
                const nonLethalSelect = row.querySelector('.non-lethal-type');
                if (nonLethalSelect && nonLethalSelect.value) {
                    if (nonLethalSelect.value === 'other') {
                        const otherInput = row.querySelector('.non-lethal-other');
                        if (otherInput) otherInput.style.display = 'block';
                    }
                }
                
                // Set calibre if exists
                const calibreSelect = row.querySelector('.calibre-type');
                if (calibreSelect && calibreSelect.value) {
                    if (calibreSelect.value === 'other') {
                        const otherInput = row.querySelector('.calibre-other');
                        if (otherInput) otherInput.style.display = 'block';
                    }
                }
                
                // Set purpose if exists
                const purposeSelect = row.querySelector('.purpose-select');
                if (purposeSelect && purposeSelect.value === 'Other') {
                    const otherInput = row.querySelector('.purpose-other');
                    if (otherInput) otherInput.style.display = 'block';
                }
            }, 100);
        }
    });
    
    // Filter item types on page load based on requisition type
    filterItemTypes();
    
    // Add event listener for requisition type change
    const reqTypeSelect = document.getElementById('requisition_type');
    if (reqTypeSelect) {
        reqTypeSelect.addEventListener('change', filterItemTypes);
    }
    
    // Add quantity change listeners
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', calculateTotals);
    });
    
    updateRemoveButtons();
    calculateTotals();
    
    // Form validation
    document.getElementById('requisitionForm').addEventListener('submit', function(e) {
        const itemTypes = document.querySelectorAll('select[name="item_type[]"]');
        let hasItems = false;
        
        itemTypes.forEach(select => {
            if (select.value) hasItems = true;
        });
        
        if (!hasItems) {
            e.preventDefault();
            alert('Please add at least one requisition item');
            return false;
        }
        
        // Check if all required fields are filled
        const requiredFields = document.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                isValid = false;
            } else {
                field.classList.remove('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
            return false;
        }
        
        return true;
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
