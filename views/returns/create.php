<?php
$title = 'Create Return';
$active = 'returns';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Get old input and errors from session directly
$old = isset($_SESSION['old']) ? $_SESSION['old'] : [];
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];

// Clear session data after retrieving
if (isset($_SESSION['old'])) unset($_SESSION['old']);
if (isset($_SESSION['errors'])) unset($_SESSION['errors']);

// Get requisition ID from query string if provided
$requisitionId = isset($_GET['requisition_id']) ? $_GET['requisition_id'] : null;

// Generate CSRF token using Security class
$csrfToken = Security::csrfToken();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Create New Return
            </h1>
            <p>Record return of weapons and/or ammunition</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/returns" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Returns
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
        <form method="POST" action="<?php echo BASE_URL; ?>/returns/store" id="returnForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <!-- Return Header -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-signature"></i> Return Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Return Date</label>
                        <input type="date" name="return_date" id="return_date" 
                               value="<?php echo htmlspecialchars($old['return_date'] ?? date('Y-m-d')); ?>" 
                               required class="form-control <?php echo isset($errors['return_date']) ? 'error' : ''; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['return_date'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['return_date']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Return Type</label>
                        <select name="return_type" id="return_type" required 
                                class="form-control <?php echo isset($errors['return_type']) ? 'error' : ''; ?>">
                            <option value="">Select Type</option>
                            <option value="Weapon" <?php echo ($old['return_type'] ?? '') == 'Weapon' ? 'selected' : ''; ?>>Weapon Only</option>
                            <option value="Ammunition" <?php echo ($old['return_type'] ?? '') == 'Ammunition' ? 'selected' : ''; ?>>Ammunition Only</option>
                            <option value="Both" <?php echo ($old['return_type'] ?? '') == 'Both' ? 'selected' : ''; ?>>Both Weapons & Ammunition</option>
                        </select>
                        <?php if (isset($errors['return_type'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['return_type']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Related Requisition</label>
                        <select name="requisition_id" id="requisition_id" 
                                class="form-control <?php echo isset($errors['requisition_id']) ? 'error' : ''; ?>">
                            <option value="">None (Direct Return)</option>
                            <?php if (isset($requisitions) && !empty($requisitions)): ?>
                                <?php foreach ($requisitions as $req): ?>
                                <option value="<?php echo $req['id']; ?>" 
                                        <?php echo ($old['requisition_id'] ?? $requisitionId) == $req['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($req['requisition_number'] . ' - ' . ($req['requesting_officer_name'] ?? '') . ' (' . ($req['status'] ?? '') . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['requisition_id'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['requisition_id']); ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Select if returning items from a specific requisition</small>
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
                               value="<?php echo htmlspecialchars($old['returning_officer_name'] ?? ''); ?>" 
                               required maxlength="100" pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                               class="form-control <?php echo isset($errors['returning_officer_name']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['returning_officer_name'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['returning_officer_name']); ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Alphabets, spaces, hyphens (-), and apostrophes (') only</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Rank</label>
                        <select name="returning_rank" id="returning_rank" required 
                                class="form-control <?php echo isset($errors['returning_rank']) ? 'error' : ''; ?>">
                            <option value="">Select Rank</option>
                            <?php
                            $ranks = getNisRanks();
                            foreach ($ranks as $rank):
                            ?>
                            <option value="<?php echo htmlspecialchars($rank); ?>" <?php echo ($old['returning_rank'] ?? '') == $rank ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rank); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['returning_rank'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['returning_rank']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">NIS Number</label>
                        <input type="text" name="returning_nis"
                               value="<?php echo htmlspecialchars($old['returning_nis'] ?? ''); ?>"
                               required maxlength="20" inputmode="numeric" pattern="[0-9]+" title="Numbers only"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               class="form-control <?php echo isset($errors['returning_nis']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['returning_nis'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['returning_nis']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Unit/Department</label>
                        <input type="text" name="returning_unit" 
                               value="<?php echo htmlspecialchars($old['returning_unit'] ?? ''); ?>" 
                               required maxlength="100" 
                               class="form-control <?php echo isset($errors['returning_unit']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['returning_unit'])): ?>
                            <small class="error-text"><?php echo htmlspecialchars($errors['returning_unit']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Return Items - Weapons -->
            <div class="form-section-inner" id="weaponsSection" style="<?php echo (($old['return_type'] ?? '') == 'Ammunition') ? 'display: none;' : ''; ?>">
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
                                <th>Weapon</th>
                                <th>Condition</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="weaponsBody">
                            <?php if (isset($old['weapon_id']) && is_array($old['weapon_id']) && !empty($old['weapon_id'][0])): ?>
                                <?php foreach ($old['weapon_id'] as $index => $weaponId): ?>
                                    <?php if (!empty($weaponId)): ?>
                                    <tr class="weapon-row">
                                        <td>
                                            <select name="weapon_id[]" class="form-control weapon-select" required>
                                                <option value="">Select Weapon</option>
                                                <?php if (isset($availableWeapons) && !empty($availableWeapons)): ?>
                                                    <?php foreach ($availableWeapons as $weapon): ?>
                                                    <option value="<?php echo $weapon['id']; ?>" 
                                                            data-type="<?php echo htmlspecialchars($weapon['type_name'] ?? ''); ?>"
                                                            data-serial="<?php echo htmlspecialchars($weapon['serial_no'] ?? ''); ?>"
                                                            <?php echo $weaponId == $weapon['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($weapon['weapon_id'] . ' - ' . ($weapon['make_model'] ?? '') . ' (' . ($weapon['serial_no'] ?? '') . ')'); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="condition[]" class="form-control" required>
                                                <option value="">Select Condition</option>
                                                <option value="Serviceable" <?php echo ($old['condition'][$index] ?? '') == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                                <option value="Unserviceable" <?php echo ($old['condition'][$index] ?? '') == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                                <option value="Damaged" <?php echo ($old['condition'][$index] ?? '') == 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                                                <option value="Missing Parts" <?php echo ($old['condition'][$index] ?? '') == 'Missing Parts' ? 'selected' : ''; ?>>Missing Parts</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="weapon_remarks[]" class="form-control" 
                                                   value="<?php echo htmlspecialchars($old['weapon_remarks'][$index] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-icon delete" onclick="removeWeaponRow(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr class="weapon-row">
                                <td>
                                    <select name="weapon_id[]" class="form-control weapon-select" required>
                                        <option value="">Select Weapon</option>
                                        <?php if (isset($availableWeapons) && !empty($availableWeapons)): ?>
                                            <?php foreach ($availableWeapons as $weapon): ?>
                                            <option value="<?php echo $weapon['id']; ?>" 
                                                    data-type="<?php echo htmlspecialchars($weapon['type_name'] ?? ''); ?>"
                                                    data-serial="<?php echo htmlspecialchars($weapon['serial_no'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($weapon['weapon_id'] . ' - ' . ($weapon['make_model'] ?? '') . ' (' . ($weapon['serial_no'] ?? '') . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </td>
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
            <div class="form-section-inner" id="ammunitionSection" style="<?php echo (($old['return_type'] ?? '') == 'Weapon') ? 'display: none;' : ''; ?>">
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
                                <th>Rounds Returned</th>
                                <th>Condition</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="ammoBody">
                            <?php if (isset($old['ammo_id']) && is_array($old['ammo_id']) && !empty($old['ammo_id'][0])): ?>
                                <?php foreach ($old['ammo_id'] as $index => $ammoId): ?>
                                    <?php if (!empty($ammoId)): ?>
                                    <tr class="ammo-row">
                                        <td>
                                            <select name="ammo_id[]" class="form-control ammo-select" required>
                                                <option value="">Select Ammunition</option>
                                                <?php if (isset($issuedAmmunition) && !empty($issuedAmmunition)): ?>
                                                    <?php foreach ($issuedAmmunition as $ammo): ?>
                                                    <option value="<?php echo $ammo['id']; ?>" 
                                                            data-batch="<?php echo $ammo['batch_number'] ?? ''; ?>"
                                                            <?php echo $ammoId == $ammo['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars(($ammo['ammo_id'] ?? '') . ' - ' . ($ammo['ammo_type'] ?? '') . ' (' . ($ammo['calibre'] ?? '') . ')'); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="batch_number[]" class="form-control batch-input"
                                                   value="<?php echo htmlspecialchars($old['batch_number'][$index] ?? ''); ?>"
                                                   placeholder="Batch number">
                                        </td>
                                        <td>
                                            <input type="number" name="rounds_returned[]" class="form-control" 
                                                   required min="1"
                                                   value="<?php echo htmlspecialchars($old['rounds_returned'][$index] ?? 1); ?>">
                                        </td>
                                        <td>
                                            <select name="ammo_condition[]" class="form-control" required>
                                                <option value="">Select Condition</option>
                                                <option value="Serviceable" <?php echo ($old['ammo_condition'][$index] ?? '') == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                                <option value="Unserviceable" <?php echo ($old['ammo_condition'][$index] ?? '') == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                                <option value="Damaged" <?php echo ($old['ammo_condition'][$index] ?? '') == 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="ammo_remarks[]" class="form-control" 
                                                   value="<?php echo htmlspecialchars($old['ammo_remarks'][$index] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-icon delete" onclick="removeAmmoRow(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr class="ammo-row">
                                <td>
                                    <select name="ammo_id[]" class="form-control ammo-select" required>
                                        <option value="">Select Ammunition</option>
                                        <?php if (isset($issuedAmmunition) && !empty($issuedAmmunition)): ?>
                                            <?php foreach ($issuedAmmunition as $ammo): ?>
                                            <option value="<?php echo $ammo['id']; ?>" 
                                                    data-batch="<?php echo $ammo['batch_number'] ?? ''; ?>">
                                                <?php echo htmlspecialchars(($ammo['ammo_id'] ?? '') . ' - ' . ($ammo['ammo_type'] ?? '') . ' (' . ($ammo['calibre'] ?? '') . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="batch_number[]" class="form-control batch-input"
                                           placeholder="Batch number">
                                </td>
                                <td>
                                    <input type="number" name="rounds_returned[]" class="form-control" 
                                           required min="1" value="1">
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
                    <textarea name="remarks" rows="3" class="form-control"><?php echo htmlspecialchars($old['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-success submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Return
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm('returnForm')">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
                <a href="<?php echo BASE_URL; ?>/returns" class="btn btn-outline">
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


/* Page Header */
.page-header {
    background: var(--surface);
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-content h1 {
    margin-bottom: 5px;
    color: var(--text-primary);
}

.header-content p {
    color: var(--text-secondary);
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

/* Form Sections */
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
    border-bottom: 1px solid var(--border-color);
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
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-title h3 i {
    color: var(--success-color);
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
    color: var(--text-primary);
    margin-bottom: 8px;
    font-size: 0.95rem;
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

/* Items Table */
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

.btn-sm {
    padding: 5px 10px;
    font-size: 0.85rem;
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

.submit-btn {
    min-width: 150px;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .header-actions {
        justify-content: center;
    }
    
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
    
    .items-table {
        font-size: 0.8rem;
    }
    
    .items-table td {
        padding: 5px;
    }
}
</style>

<script>
// Master list of available weapons and ammunition
let availableWeapons = <?php echo json_encode($availableWeapons ?? []); ?>;
let issuedAmmunition = <?php echo json_encode($issuedAmmunition ?? []); ?>;
const baseUrl = '<?php echo BASE_URL; ?>';

// Global row removal functions
window.removeRow = function(button) {
    const row = button.closest('tr');
    if (!row) return;
    const tbody = row.parentNode;
    if (tbody && tbody.children.length > 1) {
        row.remove();
    } else {
        alert('At least one row is required');
    }
    updateRemoveButtons();
};
window.removeWeaponRow = window.removeRow;
window.removeAmmoRow = window.removeRow;

// Update remove buttons state
function updateRemoveButtons() {
    const weaponRows = document.querySelectorAll('.weapon-row');
    const ammoRows = document.querySelectorAll('.ammo-row');
    
    document.querySelectorAll('.weapon-row .delete, .ammo-row .delete').forEach(btn => {
        btn.disabled = false;
    });
    
    if (weaponRows.length <= 1) {
        weaponRows.forEach(row => {
            const btn = row.querySelector('.delete');
            if (btn) btn.disabled = true;
        });
    }
    
    if (ammoRows.length <= 1) {
        ammoRows.forEach(row => {
            const btn = row.querySelector('.delete');
            if (btn) btn.disabled = true;
        });
    }
}

// Add weapon row
window.addWeaponRow = function() {
    const tbody = document.getElementById('weaponsBody');
    if (!tbody) return;
    const newRow = document.createElement('tr');
    newRow.className = 'weapon-row';
    
    let options = '<option value="">Select Weapon</option>';
    if (Array.isArray(availableWeapons)) {
        availableWeapons.forEach(item => {
            options += `<option value="${item.id}">${item.weapon_id} - ${item.make_model || ''} (${item.serial_no || ''})</option>`;
        });
    }
    
    newRow.innerHTML = `
        <td>
            <select name="weapon_id[]" class="form-control weapon-select" required>
                ${options}
            </select>
        </td>
        <td>
            <select name="condition[]" class="form-control" required>
                <option value="">Select Condition</option>
                <option value="Serviceable" selected>Serviceable</option>
                <option value="Unserviceable">Unserviceable</option>
                <option value="Damaged">Damaged</option>
                <option value="Missing Parts">Missing Parts</option>
            </select>
        </td>
        <td>
            <input type="text" name="weapon_remarks[]" class="form-control" placeholder="Remarks">
        </td>
        <td>
            <button type="button" class="btn-icon delete" onclick="removeWeaponRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateRemoveButtons();
};

// Add ammunition row
window.addAmmoRow = function() {
    const tbody = document.getElementById('ammoBody');
    if (!tbody) return;
    const newRow = document.createElement('tr');
    newRow.className = 'ammo-row';
    
    let options = '<option value="">Select Ammunition</option>';
    if (Array.isArray(issuedAmmunition)) {
        issuedAmmunition.forEach(item => {
            options += `<option value="${item.id}">${item.ammo_id || ''} - ${item.ammo_type || ''} (${item.calibre || ''})</option>`;
        });
    }
    
    newRow.innerHTML = `
        <td>
            <select name="ammo_id[]" class="form-control ammo-select" required>
                ${options}
            </select>
        </td>
        <td>
            <input type="text" name="batch_number[]" class="form-control batch-input" placeholder="Batch number">
        </td>
        <td>
            <input type="number" name="rounds_returned[]" class="form-control" required min="1" value="1">
        </td>
        <td>
            <select name="ammo_condition[]" class="form-control" required>
                <option value="">Select Condition</option>
                <option value="Serviceable" selected>Serviceable</option>
                <option value="Unserviceable">Unserviceable</option>
                <option value="Damaged">Damaged</option>
            </select>
        </td>
        <td>
            <input type="text" name="ammo_remarks[]" class="form-control" placeholder="Remarks">
        </td>
        <td>
            <button type="button" class="btn-icon delete" onclick="removeAmmoRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateRemoveButtons();
};

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

document.addEventListener('DOMContentLoaded', function() {
    const returnTypeSelect = document.getElementById('return_type');
    if (returnTypeSelect) {
        returnTypeSelect.addEventListener('change', updateSectionVisibility);
    }

    // Sync initial visibility and input required/disabled states
    updateSectionVisibility();

    // Requisition change event to load issued items automatically
    const requisitionSelect = document.getElementById('requisition_id');
    if (requisitionSelect) {
        requisitionSelect.addEventListener('change', function() {
            const reqId = this.value;
            const weaponsBody = document.getElementById('weaponsBody');
            const ammoBody = document.getElementById('ammoBody');

            if (!reqId) {
                if (weaponsBody) weaponsBody.innerHTML = '';
                if (ammoBody) ammoBody.innerHTML = '';
                addWeaponRow();
                addAmmoRow();
                updateRemoveButtons();
                updateSectionVisibility();
                return;
            }
            
            if (weaponsBody) weaponsBody.innerHTML = '<tr><td colspan="4" class="text-center" style="padding: 16px;"><i class="fas fa-spinner fa-spin"></i> Loading issued weapons...</td></tr>';
            if (ammoBody) ammoBody.innerHTML = '<tr><td colspan="6" class="text-center" style="padding: 16px;"><i class="fas fa-spinner fa-spin"></i> Loading issued ammunition...</td></tr>';
            
            fetch(`${baseUrl}/api/get_requisition_issued_items.php?requisition_id=${reqId}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        const weapons = res.weapons || [];
                        const ammo = res.ammunition || [];
                        
                        let returnType = '';
                        if (weapons.length > 0 && ammo.length > 0) {
                            returnType = 'Both';
                        } else if (weapons.length > 0) {
                            returnType = 'Weapon';
                        } else if (ammo.length > 0) {
                            returnType = 'Ammunition';
                        }
                        
                        if (returnType && returnTypeSelect) {
                            returnTypeSelect.value = returnType;
                        }
                        
                        if (weaponsBody) {
                            weaponsBody.innerHTML = '';
                            if (weapons.length > 0) {
                                weapons.forEach(w => {
                                    const exists = availableWeapons.some(item => String(item.id) === String(w.id));
                                    if (!exists) availableWeapons.push(w);
                                    
                                    const tr = document.createElement('tr');
                                    tr.className = 'weapon-row';
                                    let options = '<option value="">Select Weapon</option>';
                                    availableWeapons.forEach(item => {
                                        const selected = String(item.id) === String(w.id) ? 'selected' : '';
                                        options += `<option value="${item.id}" ${selected}>${item.weapon_id} - ${item.make_model || ''} (${item.serial_no || ''})</option>`;
                                    });
                                    
                                    tr.innerHTML = `
                                        <td>
                                            <select name="weapon_id[]" class="form-control weapon-select" required>
                                                ${options}
                                            </select>
                                        </td>
                                        <td>
                                            <select name="condition[]" class="form-control" required>
                                                <option value="">Select Condition</option>
                                                <option value="Serviceable" selected>Serviceable</option>
                                                <option value="Unserviceable">Unserviceable</option>
                                                <option value="Damaged">Damaged</option>
                                                <option value="Missing Parts">Missing Parts</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="weapon_remarks[]" class="form-control" placeholder="Remarks">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-icon delete" onclick="removeWeaponRow(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    `;
                                    weaponsBody.appendChild(tr);
                                });
                            } else {
                                addWeaponRow();
                            }
                        }
                        
                        if (ammoBody) {
                            ammoBody.innerHTML = '';
                            if (ammo.length > 0) {
                                ammo.forEach(a => {
                                    const exists = issuedAmmunition.some(item => String(item.id) === String(a.id));
                                    if (!exists) issuedAmmunition.push(a);
                                    
                                    const tr = document.createElement('tr');
                                    tr.className = 'ammo-row';
                                    let options = '<option value="">Select Ammunition</option>';
                                    issuedAmmunition.forEach(item => {
                                        const selected = String(item.id) === String(a.id) ? 'selected' : '';
                                        options += `<option value="${item.id}" ${selected}>${item.ammo_id || ''} - ${item.ammo_type || ''} (${item.calibre || ''})</option>`;
                                    });
                                    
                                    tr.innerHTML = `
                                        <td>
                                            <select name="ammo_id[]" class="form-control ammo-select" required>
                                                ${options}
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="batch_number[]" class="form-control batch-input" placeholder="Batch number" value="${a.batch_number || ''}">
                                        </td>
                                        <td>
                                            <input type="number" name="rounds_returned[]" class="form-control" required min="1" value="${a.rounds_issued || 1}">
                                        </td>
                                        <td>
                                            <select name="ammo_condition[]" class="form-control" required>
                                                <option value="">Select Condition</option>
                                                <option value="Serviceable" selected>Serviceable</option>
                                                <option value="Unserviceable">Unserviceable</option>
                                                <option value="Damaged">Damaged</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="ammo_remarks[]" class="form-control" placeholder="Remarks">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-icon delete" onclick="removeAmmoRow(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    `;
                                    ammoBody.appendChild(tr);
                                });
                            } else {
                                addAmmoRow();
                            }
                        }
                        
                        updateRemoveButtons();
                        updateSectionVisibility();
                    } else {
                        alert('Notice: ' + (res.error || 'Unable to load issued items'));
                        if (weaponsBody) weaponsBody.innerHTML = '';
                        if (ammoBody) ammoBody.innerHTML = '';
                        addWeaponRow();
                        addAmmoRow();
                        updateRemoveButtons();
                        updateSectionVisibility();
                    }
                })
                .catch(err => {
                    console.error('Issued items load error:', err);
                    if (weaponsBody) weaponsBody.innerHTML = '';
                    if (ammoBody) ammoBody.innerHTML = '';
                    addWeaponRow();
                    addAmmoRow();
                    updateRemoveButtons();
                    updateSectionVisibility();
                });
        });
    }

    // Form submission validation
    const returnForm = document.getElementById('returnForm');
    if (returnForm) {
        returnForm.addEventListener('submit', function(e) {
            const returnDate = document.getElementById('return_date');
            if (!returnDate || !returnDate.value) {
                e.preventDefault();
                alert('Please select the Return Date.');
                if (returnDate) returnDate.focus();
                return false;
            }

            const returnTypeSelect = document.getElementById('return_type');
            const returnType = returnTypeSelect ? returnTypeSelect.value : '';
            if (!returnType) {
                e.preventDefault();
                alert('Please select Return Type (Weapon, Ammunition, or Both).');
                if (returnTypeSelect) returnTypeSelect.focus();
                return false;
            }

            const officerName = document.querySelector('input[name="returning_officer_name"]');
            if (!officerName || !officerName.value.trim()) {
                e.preventDefault();
                alert('Returning Officer Name is required.');
                if (officerName) officerName.focus();
                return false;
            }
            if (!/^[a-zA-Z\s\-'.]+$/.test(officerName.value.trim())) {
                e.preventDefault();
                alert("Officer Name must contain only alphabets, spaces, hyphens (-), and apostrophes (').");
                officerName.focus();
                return false;
            }

            const rankSelect = document.getElementById('returning_rank');
            if (!rankSelect || !rankSelect.value) {
                e.preventDefault();
                alert('Please select the Returning Officer Rank.');
                if (rankSelect) rankSelect.focus();
                return false;
            }

            const nisInput = document.querySelector('input[name="returning_nis"]');
            if (!nisInput || !nisInput.value.trim()) {
                e.preventDefault();
                alert('NIS Number is required.');
                if (nisInput) nisInput.focus();
                return false;
            }
            if (!/^\d+$/.test(nisInput.value.trim())) {
                e.preventDefault();
                alert('NIS Number must contain numbers only.');
                nisInput.focus();
                return false;
            }

            const unitInput = document.querySelector('input[name="returning_unit"]');
            if (!unitInput || !unitInput.value.trim()) {
                e.preventDefault();
                alert('Returning Unit/Department is required.');
                if (unitInput) unitInput.focus();
                return false;
            }
            
            if (returnType === 'Weapon' || returnType === 'Both') {
                const weaponSelects = document.querySelectorAll('.weapon-row select[name="weapon_id[]"]');
                let hasWeapon = false;
                weaponSelects.forEach(select => {
                    if (select.value) hasWeapon = true;
                });
                
                if (!hasWeapon) {
                    e.preventDefault();
                    alert('Please select at least one Weapon in the Weapons Returned list.');
                    const firstWeaponSelect = document.querySelector('.weapon-row select[name="weapon_id[]"]');
                    if (firstWeaponSelect) firstWeaponSelect.focus();
                    return false;
                }
            }
            
            if (returnType === 'Ammunition' || returnType === 'Both') {
                const ammoSelects = document.querySelectorAll('.ammo-row select[name="ammo_id[]"]');
                let hasAmmo = false;
                ammoSelects.forEach(select => {
                    if (select.value) hasAmmo = true;
                });
                
                if (!hasAmmo) {
                    e.preventDefault();
                    alert('Please select at least one Ammunition item in the Ammunition Returned list.');
                    const firstAmmoSelect = document.querySelector('.ammo-row select[name="ammo_id[]"]');
                    if (firstAmmoSelect) firstAmmoSelect.focus();
                    return false;
                }
            }

            // Show submit loading feedback
            const submitBtn = returnForm.querySelector('.submit-btn');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Return...';
            }
            
            return true;
        });
    }

    // Initialize remove buttons
    updateRemoveButtons();
});

function resetForm(formId) {
    if (confirm('Are you sure you want to reset the form? All unsaved data will be lost.')) {
        const form = document.getElementById(formId);
        if (form) form.reset();
        updateSectionVisibility();
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
