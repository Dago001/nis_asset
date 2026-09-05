<?php
$title = 'Issue Weapon / Ammunition';
$active = 'weapon_issue';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// These variables should come from the controller
$availableWeapons = isset($availableWeapons) ? $availableWeapons : [];
$availableAmmunition = isset($availableAmmunition) ? $availableAmmunition : [];
$requisitions = isset($requisitions) ? $requisitions : [];

// Get old input and errors from session
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
                Issue Weapon / Ammunition
            </h1>
            <p>Issue firearms and ammunition to authorized personnel</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/weapon_issue" class="btn btn-info">
                <i class="fas fa-tachometer-alt"></i> Issue Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>/weapons" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Weapons
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo Security::escape($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo Security::escape($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Issue Type Selector -->
    <div class="issue-type-selector">
        <button class="type-btn <?php echo (!isset($_GET['type']) || $_GET['type'] == 'weapon') ? 'active' : ''; ?>" 
                onclick="showIssueType('weapon')">
            <i class="fas fa-gun"></i> Issue Weapon
        </button>
        <button class="type-btn <?php echo (isset($_GET['type']) && $_GET['type'] == 'ammunition') ? 'active' : ''; ?>" 
                onclick="showIssueType('ammunition')">
            <i class="fas fa-bullseye"></i> Issue Ammunition
        </button>
    </div>

    <!-- Weapon Issue Form -->
    <div id="weapon-issue-form" class="issue-form <?php echo (!isset($_GET['type']) || $_GET['type'] == 'weapon') ? 'active' : ''; ?>">
        <div class="form-section">
            <div class="section-title">
                <h3><i class="fas fa-gun"></i> Issue Weapon</h3>
            </div>

            <form method="POST" action="<?php echo BASE_URL; ?>/weapon_issue/store" id="weaponIssueForm">
                <?php echo Security::csrfField(); ?>
                <input type="hidden" name="issue_type" value="weapon">

                <!-- Requisition Selection -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="requisition_id">Requisition Reference (Optional)</label>
                        <select name="requisition_id" id="requisition_id" class="form-control <?php echo isset($errors['requisition_id']) ? 'error' : ''; ?>" 
                                onchange="loadRequisitionDetails(this.value, 'weapon')">
                            <option value="">-- Direct Issue (No Requisition) --</option>
                            <?php foreach ($requisitions as $req): ?>
                                <?php 
                                    $remWpn = $req['remaining_weapons'] ?? $req['total_weapons'] ?? 0;
                                    $totWpn = (int)($req['total_weapons'] ?? 0);
                                    $totItems = (int)($req['total_items'] ?? 0);
                                    if (in_array($req['status'] ?? '', ['Issued', 'Completed'])) continue;
                                    if ($totItems > 0 && $totWpn > 0 && $remWpn <= 0) continue;
                                    if ($totItems > 0 && $totWpn == 0) continue;
                                ?>
                                <option value="<?php echo $req['id']; ?>" 
                                        data-officer="<?php echo Security::escape($req['requesting_officer_name'] ?? ''); ?>"
                                        data-rank="<?php echo Security::escape($req['requesting_rank'] ?? ''); ?>"
                                        data-nis="<?php echo Security::escape($req['requesting_nis'] ?? ''); ?>"
                                        data-unit="<?php echo Security::escape($req['requesting_command_name'] ?? $req['command_name'] ?? ''); ?>"
                                        data-purpose="<?php echo Security::escape($req['weapon_purpose'] ?? $req['justification'] ?? ''); ?>"
                                        data-purpose-other="<?php echo Security::escape($req['weapon_purpose_other'] ?? ''); ?>"
                                        data-approved-by="<?php echo Security::escape($req['final_approved_by_name'] ?? $req['approved_by_name'] ?? $req['hq_vetted_by_name'] ?? $req['command_approved_by_name'] ?? ''); ?>"
                                        data-remaining-weapons="<?php echo $remWpn; ?>"
                                        data-total-weapons="<?php echo $totWpn; ?>"
                                        <?php echo ((isset($_GET['requisition_id']) && $_GET['requisition_id'] == $req['id']) || ($old['requisition_id'] ?? '') == $req['id']) ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($req['requisition_number'] . ' - ' . ($req['requesting_officer_name'] ?? '') . ' (' . ($totWpn > 0 ? $remWpn . ' weapons' : 'General') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['requisition_id'])): ?>
                            <small class="error-text"><?php echo $errors['requisition_id']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Select if this issue is linked to an approved requisition</small>
                    </div>

                    <div class="form-group">
                        <label for="issue_date" class="required">Issue Date</label>
                        <input type="date" name="issue_date" id="issue_date" class="form-control <?php echo isset($errors['issue_date']) ? 'error' : ''; ?>" 
                               value="<?php echo Security::escape($old['issue_date'] ?? date('Y-m-d')); ?>" required>
                        <?php if (isset($errors['issue_date'])): ?>
                            <small class="error-text"><?php echo $errors['issue_date']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Weapon Selection -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="weapon_id" class="required">Select Weapon</label>
                        <select name="weapon_id" id="weapon_id" class="form-control <?php echo isset($errors['weapon_id']) ? 'error' : ''; ?>" 
                                required onchange="updateWeaponDetails(this)">
                            <option value="">-- Select Weapon --</option>
                            <?php foreach ($availableWeapons as $weapon): ?>
                                <option value="<?php echo $weapon['id']; ?>"
                                        data-type="<?php echo Security::escape($weapon['type_name'] ?? $weapon['weapon_type_other'] ?? 'Other'); ?>"
                                        data-model="<?php echo Security::escape($weapon['make_model']); ?>"
                                        data-serial="<?php echo Security::escape($weapon['serial_no']); ?>"
                                        data-calibre="<?php echo Security::escape($weapon['calibre_name'] ?? $weapon['calibre_other'] ?? 'N/A'); ?>"
                                        <?php echo ($old['weapon_id'] ?? '') == $weapon['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($weapon['weapon_id'] . ' - ' . $weapon['make_model'] . ' (SN: ' . $weapon['serial_no'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['weapon_id'])): ?>
                            <small class="error-text"><?php echo $errors['weapon_id']; ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="weapon_quantity" class="required">Quantity to Issue</label>
                        <input type="number" name="quantity" id="weapon_quantity" class="form-control" value="1" min="1" required>
                        <small class="form-hint">Number of weapons to issue</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Weapon Details</label>
                        <div class="weapon-details-display" id="weaponDetails">
                            <div class="detail-item">
                                <span class="label">Type:</span> 
                                <span id="weaponTypeDisplay">
                                    <?php if (!empty($old['weapon_id'])): 
                                        foreach ($availableWeapons as $w) {
                                            if ($w['id'] == $old['weapon_id']) {
                                                echo Security::escape($w['type_name'] ?? $w['weapon_type_other'] ?? 'Other');
                                                break;
                                            }
                                        }
                                    else: ?>-<?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Model:</span> 
                                <span id="weaponModelDisplay">
                                    <?php if (!empty($old['weapon_id'])): 
                                        foreach ($availableWeapons as $w) {
                                            if ($w['id'] == $old['weapon_id']) {
                                                echo Security::escape($w['make_model']);
                                                break;
                                            }
                                        }
                                    else: ?>-<?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Serial:</span> 
                                <span id="weaponSerialDisplay">
                                    <?php if (!empty($old['weapon_id'])): 
                                        foreach ($availableWeapons as $w) {
                                            if ($w['id'] == $old['weapon_id']) {
                                                echo Security::escape($w['serial_no']);
                                                break;
                                            }
                                        }
                                    else: ?>-<?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Calibre:</span> 
                                <span id="weaponCalibreDisplay">
                                    <?php if (!empty($old['weapon_id'])): 
                                        foreach ($availableWeapons as $w) {
                                            if ($w['id'] == $old['weapon_id']) {
                                                echo Security::escape($w['calibre_name'] ?? $w['calibre_other'] ?? 'N/A');
                                                break;
                                            }
                                        }
                                    else: ?>-<?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Officer Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="officer_name" class="required">Officer Name</label>
                        <input type="text" name="officer_name" id="officer_name" class="form-control <?php echo isset($errors['officer_name']) ? 'error' : ''; ?>" 
                               value="<?php echo Security::escape($old['officer_name'] ?? ''); ?>" required>
                        <?php if (isset($errors['officer_name'])): ?>
                            <small class="error-text"><?php echo $errors['officer_name']; ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="officer_rank" class="required">Rank</label>
                        <select name="officer_rank" id="officer_rank" class="form-control <?php echo isset($errors['officer_rank']) ? 'error' : ''; ?>" required>
                            <option value="">Select Rank</option>
                            <?php foreach (getNisRanks() as $rank): ?>
                                <option value="<?php echo htmlspecialchars($rank); ?>" <?php echo ($old['officer_rank'] ?? '') === $rank ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rank); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['officer_rank'])): ?>
                            <small class="error-text"><?php echo $errors['officer_rank']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="officer_nis">NIS Number</label>
                        <input type="text" name="officer_nis" id="officer_nis"
                               maxlength="20" inputmode="numeric" pattern="[0-9]*" title="Numbers only"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               class="form-control <?php echo isset($errors['officer_nis']) ? 'error' : ''; ?>"
                               value="<?php echo Security::escape($old['officer_nis'] ?? ''); ?>">
                        <?php if (isset($errors['officer_nis'])): ?>
                            <small class="error-text"><?php echo $errors['officer_nis']; ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="unit" class="required">Unit/Department</label>
                        <input type="text" name="unit" id="unit" class="form-control <?php echo isset($errors['unit']) ? 'error' : ''; ?>" 
                               value="<?php echo Security::escape($old['unit'] ?? ''); ?>" required>
                        <?php if (isset($errors['unit'])): ?>
                            <small class="error-text"><?php echo $errors['unit']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Purpose and Approval -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="purpose" class="required">Purpose of Issue</label>
                        <select name="purpose" id="purpose" class="form-control <?php echo isset($errors['purpose']) ? 'error' : ''; ?>" 
                                required onchange="togglePurposeOther(this)">
                            <option value="">Select Purpose</option>
                            <option value="Special Operation" <?php echo ($old['purpose'] ?? '') == 'Special Operation' ? 'selected' : ''; ?>>Special Operation</option>
                            <option value="Training Exercise" <?php echo ($old['purpose'] ?? '') == 'Training Exercise' ? 'selected' : ''; ?>>Training Exercise</option>
                            <option value="Patrol Duty" <?php echo ($old['purpose'] ?? '') == 'Patrol Duty' ? 'selected' : ''; ?>>Patrol Duty</option>
                            <option value="Security Detail" <?php echo ($old['purpose'] ?? '') == 'Security Detail' ? 'selected' : ''; ?>>Security Detail</option>
                            <option value="Emergency Response" <?php echo ($old['purpose'] ?? '') == 'Emergency Response' ? 'selected' : ''; ?>>Emergency Response</option>
                            <option value="Escort Duty" <?php echo ($old['purpose'] ?? '') == 'Escort Duty' ? 'selected' : ''; ?>>Escort Duty</option>
                            <option value="Border Patrol" <?php echo ($old['purpose'] ?? '') == 'Border Patrol' ? 'selected' : ''; ?>>Border Patrol</option>
                            <option value="Training" <?php echo ($old['purpose'] ?? '') == 'Training' ? 'selected' : ''; ?>>Training</option>
                            <option value="Operation" <?php echo ($old['purpose'] ?? '') == 'Operation' ? 'selected' : ''; ?>>Operation</option>
                            <option value="Patrol" <?php echo ($old['purpose'] ?? '') == 'Patrol' ? 'selected' : ''; ?>>Patrol</option>
                            <option value="Maintenance" <?php echo ($old['purpose'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="Crowd Control" <?php echo ($old['purpose'] ?? '') == 'Crowd Control' ? 'selected' : ''; ?>>Crowd Control</option>
                            <option value="Riot Control" <?php echo ($old['purpose'] ?? '') == 'Riot Control' ? 'selected' : ''; ?>>Riot Control</option>
                            <option value="Other" <?php echo ($old['purpose'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other (Specify)</option>
                        </select>
                        <?php if (isset($errors['purpose'])): ?>
                            <small class="error-text"><?php echo $errors['purpose']; ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" id="purposeOtherGroup" style="<?php echo ($old['purpose'] ?? '') == 'Other' ? 'display: block;' : 'display: none;'; ?>">
                        <label for="purpose_other">Specify Purpose</label>
                        <input type="text" name="purpose_other" id="purpose_other" class="form-control" 
                               value="<?php echo Security::escape($old['purpose_other'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="approved_by" class="required">Approved By</label>
                        <input type="text" name="approved_by" id="approved_by" class="form-control <?php echo isset($errors['approved_by']) ? 'error' : ''; ?>" 
                               value="<?php echo Security::escape($old['approved_by'] ?? ''); ?>" required>
                        <?php if (isset($errors['approved_by'])): ?>
                            <small class="error-text"><?php echo $errors['approved_by']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Return Date and Remarks -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="expected_return_date">Expected Return Date</label>
                        <input type="date" name="expected_return_date" id="expected_return_date" class="form-control <?php echo isset($errors['expected_return_date']) ? 'error' : ''; ?>" 
                               value="<?php echo Security::escape($old['expected_return_date'] ?? date('Y-m-d', strtotime('+30 days'))); ?>">
                        <?php if (isset($errors['expected_return_date'])): ?>
                            <small class="error-text"><?php echo $errors['expected_return_date']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Leave empty if not applicable</small>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Remarks / Notes</label>
                        <input type="text" name="remarks" id="remarks" class="form-control" 
                               value="<?php echo Security::escape($old['remarks'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Issue Weapon
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetWeaponForm()">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ammunition Issue Form -->
    <div id="ammunition-issue-form" class="issue-form <?php echo (isset($_GET['type']) && $_GET['type'] == 'ammunition') ? 'active' : ''; ?>">
        <div class="form-section">
            <div class="section-title">
                <h3><i class="fas fa-bullseye"></i> Issue Ammunition</h3>
            </div>

            <form method="POST" action="<?php echo BASE_URL; ?>/weapon_issue/store" id="ammoIssueForm">
                <?php echo Security::csrfField(); ?>
                <input type="hidden" name="issue_type" value="ammunition">

                <!-- Requisition Selection -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="ammo_requisition_id">Requisition Reference (Optional)</label>
                        <select name="requisition_id" id="ammo_requisition_id" class="form-control <?php echo isset($errors['requisition_id']) ? 'error' : ''; ?>" 
                                onchange="loadRequisitionDetails(this.value, 'ammo')">
                            <option value="">-- Direct Issue (No Requisition) --</option>
                            <?php foreach ($requisitions as $req): ?>
                                <?php 
                                    $remAmmo = $req['remaining_ammunition'] ?? $req['total_ammunition'] ?? 0;
                                    $totAmmo = (int)($req['total_ammunition'] ?? 0);
                                    $totItems = (int)($req['total_items'] ?? 0);
                                    if (in_array($req['status'] ?? '', ['Issued', 'Completed'])) continue;
                                    if ($totItems > 0 && $totAmmo > 0 && $remAmmo <= 0) continue;
                                    if ($totItems > 0 && $totAmmo == 0) continue;
                                ?>
                                <option value="<?php echo $req['id']; ?>" 
                                        data-officer="<?php echo Security::escape($req['requesting_officer_name'] ?? ''); ?>"
                                        data-rank="<?php echo Security::escape($req['requesting_rank'] ?? ''); ?>"
                                        data-nis="<?php echo Security::escape($req['requesting_nis'] ?? ''); ?>"
                                        data-unit="<?php echo Security::escape($req['requesting_command_name'] ?? $req['command_name'] ?? ''); ?>"
                                        data-purpose="<?php echo Security::escape($req['ammo_purpose'] ?? $req['justification'] ?? ''); ?>"
                                        data-purpose-other="<?php echo Security::escape($req['ammo_purpose_other'] ?? ''); ?>"
                                        data-approved-by="<?php echo Security::escape($req['final_approved_by_name'] ?? $req['approved_by_name'] ?? $req['hq_vetted_by_name'] ?? $req['command_approved_by_name'] ?? ''); ?>"
                                        data-remaining-ammo="<?php echo $remAmmo; ?>"
                                        data-total-ammo="<?php echo $totAmmo; ?>"
                                        <?php echo ((isset($_GET['requisition_id']) && $_GET['requisition_id'] == $req['id']) || ($old['requisition_id'] ?? '') == $req['id']) ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($req['requisition_number'] . ' - ' . ($req['requesting_officer_name'] ?? '') . ' (' . ($totAmmo > 0 ? $remAmmo . ' rounds' : 'General') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['requisition_id'])): ?>
                            <small class="error-text"><?php echo $errors['requisition_id']; ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="ammo_issue_date" class="required">Issue Date</label>
                        <input type="date" name="issue_date" id="ammo_issue_date" class="form-control <?php echo isset($errors['issue_date']) ? 'error' : ''; ?>" 
                               value="<?php echo Security::escape($old['issue_date'] ?? date('Y-m-d')); ?>" required>
                        <?php if (isset($errors['issue_date'])): ?>
                            <small class="error-text"><?php echo $errors['issue_date']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ammunition Selection -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="ammo_id" class="required">Select Ammunition</label>
                        <select name="ammo_id" id="ammo_id" class="form-control <?php echo isset($errors['ammo_id']) ? 'error' : ''; ?>" 
                                required onchange="updateAmmoDetails(this)">
                            <option value="">-- Select Ammunition --</option>
                            <?php foreach ($availableAmmunition as $ammo): ?>
                                <option value="<?php echo $ammo['id']; ?>"
                                        data-type="<?php echo Security::escape($ammo['ammo_type'] ?? 'Ammunition'); ?>"
                                        data-calibre="<?php echo Security::escape($ammo['calibre'] ?? 'N/A'); ?>"
                                        data-balance="<?php echo $ammo['balance']; ?>"
                                        <?php echo ($old['ammo_id'] ?? '') == $ammo['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($ammo['ammo_id'] . ' - ' . ($ammo['ammo_type'] ?? '') . ' (' . ($ammo['calibre'] ?? '') . ') - Balance: ' . $ammo['balance']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['ammo_id'])): ?>
                            <small class="error-text"><?php echo $errors['ammo_id']; ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Ammunition Details</label>
                        <div class="weapon-details-display" id="ammoDetails">
                            <div class="detail-item">
                                <span class="label">Type:</span> 
                                <span id="ammoTypeDisplay">
                                    <?php if (!empty($old['ammo_id'])): 
                                        foreach ($availableAmmunition as $a) {
                                            if ($a['id'] == $old['ammo_id']) {
                                                echo Security::escape($a['ammo_type'] ?? 'Ammunition');
                                                break;
                                            }
                                        }
                                    else: ?>-<?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Calibre:</span> 
                                <span id="ammoCalibreDisplay">
                                    <?php if (!empty($old['ammo_id'])): 
                                        foreach ($availableAmmunition as $a) {
                                            if ($a['id'] == $old['ammo_id']) {
                                                echo Security::escape($a['calibre'] ?? 'N/A');
                                                break;
                                            }
                                        }
                                    else: ?>-<?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Balance:</span> 
                                <span id="ammoBalanceDisplay">
                                    <?php if (!empty($old['ammo_id'])): 
                                        foreach ($availableAmmunition as $a) {
                                            if ($a['id'] == $old['ammo_id']) {
                                                echo $a['balance'];
                                                break;
                                            }
                                        }
                                    else: ?>-<?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quantity to Issue -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="units_issued" class="required">Units to Issue</label>
                        <input type="number" name="units_issued" id="units_issued" class="form-control <?php echo isset($errors['units_issued']) ? 'error' : ''; ?>" 
                               min="1" value="<?php echo Security::escape($old['units_issued'] ?? '1'); ?>" required oninput="calculateTotalRounds()">
                        <?php if (isset($errors['units_issued'])): ?>
                            <small class="error-text"><?php echo $errors['units_issued']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">1 unit = 30 rounds (standard)</small>
                    </div>

                    <div class="form-group">
                        <label for="total_rounds">Total Rounds</label>
                        <input type="number" name="total_rounds" id="total_rounds" class="form-control" 
                               value="<?php echo Security::escape($old['total_rounds'] ?? '30'); ?>" readonly>
                        <small class="form-hint">Calculated automatically</small>
                    </div>
                </div>

                <!-- Recipient Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="issued_to" class="required">Issued To (Officer Name)</label>
                        <input type="text" name="issued_to" id="issued_to" class="form-control <?php echo isset($errors['issued_to']) ? 'error' : ''; ?>" 
                               value="<?php echo Security::escape($old['issued_to'] ?? ''); ?>" required>
                        <?php if (isset($errors['issued_to'])): ?>
                            <small class="error-text"><?php echo $errors['issued_to']; ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="ammo_purpose" class="required">Purpose</label>
                        <select name="purpose" id="ammo_purpose" class="form-control <?php echo isset($errors['purpose']) ? 'error' : ''; ?>" 
                                required onchange="toggleAmmoPurposeOther(this)">
                            <option value="">Select Purpose</option>
                            <option value="Special Operation" <?php echo ($old['purpose'] ?? '') == 'Special Operation' ? 'selected' : ''; ?>>Special Operation</option>
                            <option value="Training Exercise" <?php echo ($old['purpose'] ?? '') == 'Training Exercise' ? 'selected' : ''; ?>>Training Exercise</option>
                            <option value="Patrol Duty" <?php echo ($old['purpose'] ?? '') == 'Patrol Duty' ? 'selected' : ''; ?>>Patrol Duty</option>
                            <option value="Security Detail" <?php echo ($old['purpose'] ?? '') == 'Security Detail' ? 'selected' : ''; ?>>Security Detail</option>
                            <option value="Emergency Response" <?php echo ($old['purpose'] ?? '') == 'Emergency Response' ? 'selected' : ''; ?>>Emergency Response</option>
                            <option value="Escort Duty" <?php echo ($old['purpose'] ?? '') == 'Escort Duty' ? 'selected' : ''; ?>>Escort Duty</option>
                            <option value="Border Patrol" <?php echo ($old['purpose'] ?? '') == 'Border Patrol' ? 'selected' : ''; ?>>Border Patrol</option>
                            <option value="Training" <?php echo ($old['purpose'] ?? '') == 'Training' ? 'selected' : ''; ?>>Training</option>
                            <option value="Operation" <?php echo ($old['purpose'] ?? '') == 'Operation' ? 'selected' : ''; ?>>Operation</option>
                            <option value="Patrol" <?php echo ($old['purpose'] ?? '') == 'Patrol' ? 'selected' : ''; ?>>Patrol</option>
                            <option value="Maintenance" <?php echo ($old['purpose'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="Crowd Control" <?php echo ($old['purpose'] ?? '') == 'Crowd Control' ? 'selected' : ''; ?>>Crowd Control</option>
                            <option value="Riot Control" <?php echo ($old['purpose'] ?? '') == 'Riot Control' ? 'selected' : ''; ?>>Riot Control</option>
                            <option value="Other" <?php echo ($old['purpose'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other (Specify)</option>
                        </select>
                        <?php if (isset($errors['purpose'])): ?>
                            <small class="error-text"><?php echo $errors['purpose']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group" id="ammoPurposeOtherGroup" style="<?php echo ($old['purpose'] ?? '') == 'Other' ? 'display: block;' : 'display: none;'; ?>">
                    <label for="ammo_purpose_other">Specify Purpose</label>
                    <input type="text" name="purpose_other" id="ammo_purpose_other" class="form-control" 
                           value="<?php echo Security::escape($old['purpose_other'] ?? ''); ?>">
                </div>

                <!-- Approval and Remarks -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="ammo_approved_by" class="required">Approved By</label>
                        <input type="text" name="approved_by" id="ammo_approved_by" class="form-control <?php echo isset($errors['approved_by']) ? 'error' : ''; ?>" 
                               value="<?php echo Security::escape($old['approved_by'] ?? ''); ?>" required>
                        <?php if (isset($errors['approved_by'])): ?>
                            <small class="error-text"><?php echo $errors['approved_by']; ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="ammo_remarks">Remarks / Notes</label>
                        <input type="text" name="remarks" id="ammo_remarks" class="form-control" 
                               value="<?php echo Security::escape($old['remarks'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Issue Ammunition
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetAmmoForm()">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Reference - Available Weapons Summary -->
    <div class="info-card">
        <div class="info-header" onclick="toggleInfo()">
            <h4><i class="fas fa-info-circle"></i> Available Inventory Summary</h4>
            <i class="fas fa-chevron-down" id="infoToggleIcon"></i>
        </div>
        <div class="info-body" id="infoBody">
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Available Weapons:</span>
                    <span class="summary-value"><?php echo count($availableWeapons); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Available Ammunition Types:</span>
                    <span class="summary-value"><?php echo count($availableAmmunition); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Ammunition Rounds:</span>
                    <span class="summary-value">
                        <?php 
                        $totalRounds = 0;
                        foreach ($availableAmmunition as $ammo) {
                            $totalRounds += $ammo['balance'] ?? 0;
                        }
                        echo number_format($totalRounds);
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Issue Type Selector */
.issue-type-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    background: var(--light-bg);
    padding: 15px;
    border-radius: 10px;
}

.type-btn {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    background: var(--surface);
    color: var(--text-primary);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 1rem;
}

.type-btn:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.type-btn.active {
    background: var(--success-color);
    color: white;
}

/* Issue Forms */
.issue-form {
    display: none;
}

.issue-form.active {
    display: block;
}

/* Form Section */
.form-section {
    background: var(--surface);
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.section-title {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
}

.section-title h3 {
    margin: 0;
    font-size: 1.3rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title h3 i {
    color: var(--success-color);
}

/* Form Grid */
.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
    margin-bottom: 25px;
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
    font-weight: bold;
}

.form-control {
    padding: 12px 15px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
    background: var(--surface);
}

.form-control:focus {
    outline: none;
    border-color: var(--success-color);
    box-shadow: 0 0 0 4px rgba(32, 112, 39, 0.1);
}

.form-control.error {
    border-color: var(--danger-color);
    background-color: #fff8f8;
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

/* Weapon Details Display */
.weapon-details-display {
    background: var(--light-bg);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    flex: 1;
}

.detail-item {
    display: flex;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px dashed var(--border-color);
}

.detail-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.detail-item .label {
    font-weight: 600;
    width: 80px;
    color: var(--text-secondary);
}

.detail-item span:last-child {
    color: var(--text-primary);
    font-weight: 500;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid var(--border-color);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
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
    box-shadow: 0 6px 12px rgba(32, 112, 39, 0.3);
}

.btn-secondary {
    background: var(--text-secondary);
    color: white;
}

.btn-secondary:hover {
    background: #6c757d;
    transform: translateY(-2px);
}

.btn-info {
    background: var(--info-color);
    color: white;
}

.btn-info:hover {
    background: #155E75;
    transform: translateY(-2px);
}

/* Alerts */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    font-size: 1.2rem;
}

/* Info Card */
.info-card {
    background: var(--surface);
    border-radius: 12px;
    margin-top: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    overflow: hidden;
}

.info-header {
    padding: 18px 25px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    color: white;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-header h4 {
    margin: 0;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-header i {
    transition: transform 0.3s ease;
}

.info-body {
    padding: 25px;
    border-top: 1px solid var(--border-color);
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.summary-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 15px;
    background: var(--light-bg);
    border-radius: 8px;
}

.summary-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.summary-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--success-color);
    line-height: 1.2;
}

/* Responsive */
@media (max-width: 768px) {
    .issue-type-selector {
        flex-direction: column;
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
    
    .summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Tab switching
function showIssueType(type) {
    document.querySelectorAll('.issue-form').forEach(form => {
        form.classList.remove('active');
    });
    
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById(type + '-issue-form').classList.add('active');
    
    // Find and activate the clicked button
    const buttons = document.querySelectorAll('.type-btn');
    for (let btn of buttons) {
        if (btn.textContent.includes(type === 'weapon' ? 'Weapon' : 'Ammunition')) {
            btn.classList.add('active');
            break;
        }
    }
    
    // Update URL without reloading
    const url = new URL(window.location);
    url.searchParams.set('type', type);
    window.history.pushState({}, '', url);
}

// Toggle info card
function toggleInfo() {
    const infoBody = document.getElementById('infoBody');
    const toggleIcon = document.getElementById('infoToggleIcon');
    
    if (infoBody.style.display === 'none') {
        infoBody.style.display = 'block';
        toggleIcon.className = 'fas fa-chevron-up';
    } else {
        infoBody.style.display = 'none';
        toggleIcon.className = 'fas fa-chevron-down';
    }
}

// Purpose other field toggle for weapon form
function togglePurposeOther(select) {
    const otherGroup = document.getElementById('purposeOtherGroup');
    if (select.value === 'Other') {
        otherGroup.style.display = 'block';
        document.getElementById('purpose_other').required = true;
    } else {
        otherGroup.style.display = 'none';
        document.getElementById('purpose_other').required = false;
    }
}

// Purpose other field toggle for ammo form
function toggleAmmoPurposeOther(select) {
    const otherGroup = document.getElementById('ammoPurposeOtherGroup');
    if (select.value === 'Other') {
        otherGroup.style.display = 'block';
        document.getElementById('ammo_purpose_other').required = true;
    } else {
        otherGroup.style.display = 'none';
        document.getElementById('ammo_purpose_other').required = false;
    }
}

// Load requisition details
function loadRequisitionDetails(requisitionId, type) {
    const select = document.getElementById(type === 'weapon' ? 'requisition_id' : 'ammo_requisition_id');
    if (!select) return;
    
    if (!requisitionId) {
        if (type === 'weapon') {
            const officerField = document.getElementById('officer_name');
            const rankField = document.getElementById('officer_rank');
            const nisField = document.getElementById('officer_nis');
            const unitField = document.getElementById('unit');
            const purposeSelect = document.getElementById('purpose');
            const purposeOther = document.getElementById('purpose_other');
            const purposeOtherGroup = document.getElementById('purposeOtherGroup');
            const approvedByField = document.getElementById('approved_by');

            if (officerField) officerField.value = '';
            if (rankField) rankField.selectedIndex = 0;
            if (nisField) nisField.value = '';
            if (unitField) unitField.value = '';
            if (purposeSelect) purposeSelect.selectedIndex = 0;
            if (purposeOther) purposeOther.value = '';
            if (purposeOtherGroup) purposeOtherGroup.style.display = 'none';
            if (approvedByField) approvedByField.value = '';
            const qtyField = document.getElementById('weapon_quantity') || document.getElementById('quantity');
            if (qtyField) qtyField.value = 1;
        } else {
            const issuedTo = document.getElementById('issued_to');
            const ammoPurpose = document.getElementById('ammo_purpose');
            const ammoPurposeOther = document.getElementById('ammo_purpose_other');
            const ammoPurposeOtherGroup = document.getElementById('ammoPurposeOtherGroup');
            const ammoApprovedBy = document.getElementById('ammo_approved_by');

            if (issuedTo) issuedTo.value = '';
            if (ammoPurpose) ammoPurpose.selectedIndex = 0;
            if (ammoPurposeOther) ammoPurposeOther.value = '';
            if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'none';
            if (ammoApprovedBy) ammoApprovedBy.value = '';
            const unitsField = document.getElementById('units_issued');
            if (unitsField) {
                unitsField.value = 1;
                if (typeof calculateTotalRounds === 'function') {
                    calculateTotalRounds();
                }
            }
        }
        return;
    }
    
    const option = select.options[select.selectedIndex];
    if (!option) return;
    
    const officer = option.getAttribute('data-officer') || '';
    const rank = option.getAttribute('data-rank') || '';
    const nis = option.getAttribute('data-nis') || '';
    const unit = option.getAttribute('data-unit') || '';
    const purpose = option.getAttribute('data-purpose') || '';
    const purposeOther = option.getAttribute('data-purpose-other') || '';
    const approvedBy = option.getAttribute('data-approved-by') || '';
    
    if (type === 'weapon') {
        const officerField = document.getElementById('officer_name');
        const rankField = document.getElementById('officer_rank');
        const nisField = document.getElementById('officer_nis');
        const unitField = document.getElementById('unit');
        const purposeSelect = document.getElementById('purpose');
        const purposeOtherInput = document.getElementById('purpose_other');
        const purposeOtherGroup = document.getElementById('purposeOtherGroup');
        const approvedByField = document.getElementById('approved_by');
        const remWeapons = option.getAttribute('data-remaining-weapons') || option.getAttribute('data-total-weapons') || '';
        const qtyField = document.getElementById('weapon_quantity') || document.getElementById('quantity');
        
        if (officerField) officerField.value = officer;
        if (nisField) nisField.value = nis;
        if (unitField) unitField.value = unit;
        if (approvedByField) approvedByField.value = approvedBy;
        if (qtyField && remWeapons && parseInt(remWeapons) > 0) {
            qtyField.value = parseInt(remWeapons);
        }
        
        // Rank selection matching
        if (rankField && rank) {
            const rLower = rank.trim().toLowerCase();
            let matchedRank = false;

            // 1. Exact match on value or text
            for (let i = 0; i < rankField.options.length; i++) {
                const val = (rankField.options[i].value || '').trim().toLowerCase();
                const txt = (rankField.options[i].text || '').trim().toLowerCase();
                if (val === rLower || txt === rLower) {
                    rankField.selectedIndex = i;
                    matchedRank = true;
                    break;
                }
            }

            // 2. Match abbreviation in parentheses (e.g. "SI", "ASI-1")
            if (!matchedRank) {
                for (let i = 0; i < rankField.options.length; i++) {
                    const optStr = rankField.options[i].value || rankField.options[i].text || '';
                    const parenMatch = optStr.match(/\(([^)]+)\)/);
                    if (parenMatch) {
                        const abbr = parenMatch[1].trim().toLowerCase();
                        if (abbr === rLower || abbr.replace(/[^a-z0-9]/g, '') === rLower.replace(/[^a-z0-9]/g, '')) {
                            rankField.selectedIndex = i;
                            matchedRank = true;
                            break;
                        }
                    }
                }
            }

            // 3. Clean alphanumeric match
            if (!matchedRank) {
                const rClean = rLower.replace(/[^a-z0-9]/g, '');
                for (let i = 0; i < rankField.options.length; i++) {
                    const valClean = (rankField.options[i].value || '').toLowerCase().replace(/[^a-z0-9]/g, '');
                    if (valClean && valClean === rClean) {
                        rankField.selectedIndex = i;
                        matchedRank = true;
                        break;
                    }
                }
            }

            if (!matchedRank) {
                rankField.value = rank;
            }
        }
        
        // Purpose & Specify Purpose matching
        if (purposeSelect) {
            let matched = false;
            if (purpose && !purposeOther) {
                for (let i = 0; i < purposeSelect.options.length; i++) {
                    const optVal = purposeSelect.options[i].value;
                    if (optVal && optVal.toLowerCase() === purpose.toLowerCase()) {
                        purposeSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                    if (optVal && (optVal.toLowerCase().includes(purpose.toLowerCase()) || purpose.toLowerCase().includes(optVal.toLowerCase()))) {
                        purposeSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            
            if (purposeOther) {
                purposeSelect.value = 'Other';
                if (purposeOtherInput) purposeOtherInput.value = purposeOther;
                if (purposeOtherGroup) purposeOtherGroup.style.display = 'block';
            } else if (matched) {
                if (purposeSelect.value === 'Other') {
                    if (purposeOtherInput) purposeOtherInput.value = purpose;
                    if (purposeOtherGroup) purposeOtherGroup.style.display = 'block';
                } else {
                    if (purposeOtherInput) purposeOtherInput.value = '';
                    if (purposeOtherGroup) purposeOtherGroup.style.display = 'none';
                }
            } else if (purpose) {
                purposeSelect.value = 'Other';
                if (purposeOtherInput) purposeOtherInput.value = purpose;
                if (purposeOtherGroup) purposeOtherGroup.style.display = 'block';
            }
        }
    } else {
        const issuedTo = document.getElementById('issued_to');
        const ammoApprovedBy = document.getElementById('ammo_approved_by');
        const ammoPurposeSelect = document.getElementById('ammo_purpose');
        const ammoPurposeOtherInput = document.getElementById('ammo_purpose_other');
        const ammoPurposeOtherGroup = document.getElementById('ammoPurposeOtherGroup');
        
        if (issuedTo) issuedTo.value = officer;
        if (ammoApprovedBy) ammoApprovedBy.value = approvedBy;
        
        if (ammoPurposeSelect) {
            let matched = false;
            if (purpose && !purposeOther) {
                for (let i = 0; i < ammoPurposeSelect.options.length; i++) {
                    const optVal = ammoPurposeSelect.options[i].value;
                    if (optVal && optVal.toLowerCase() === purpose.toLowerCase()) {
                        ammoPurposeSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                    if (optVal && (optVal.toLowerCase().includes(purpose.toLowerCase()) || purpose.toLowerCase().includes(optVal.toLowerCase()))) {
                        ammoPurposeSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            
            if (purposeOther) {
                ammoPurposeSelect.value = 'Other';
                if (ammoPurposeOtherInput) ammoPurposeOtherInput.value = purposeOther;
                if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'block';
            } else if (matched) {
                if (ammoPurposeSelect.value === 'Other') {
                    if (ammoPurposeOtherInput) ammoPurposeOtherInput.value = purpose;
                    if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'block';
                } else {
                    if (ammoPurposeOtherInput) ammoPurposeOtherInput.value = '';
                    if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'none';
                }
            } else if (purpose) {
                ammoPurposeSelect.value = 'Other';
                if (ammoPurposeOtherInput) ammoPurposeOtherInput.value = purpose;
                if (ammoPurposeOtherGroup) ammoPurposeOtherGroup.style.display = 'block';
            }
        }

        const remAmmo = option.getAttribute('data-remaining-ammo') || option.getAttribute('data-total-ammo') || '';
        const unitsField = document.getElementById('units_issued');
        if (unitsField && remAmmo && parseInt(remAmmo) > 0) {
            unitsField.value = parseInt(remAmmo);
            if (typeof calculateTotalRounds === 'function') {
                calculateTotalRounds();
            }
        }
    }
}

// Update weapon details when weapon selected
function updateWeaponDetails(select) {
    const option = select.options[select.selectedIndex];
    
    document.getElementById('weaponTypeDisplay').textContent = option.getAttribute('data-type') || '-';
    document.getElementById('weaponModelDisplay').textContent = option.getAttribute('data-model') || '-';
    document.getElementById('weaponSerialDisplay').textContent = option.getAttribute('data-serial') || '-';
    document.getElementById('weaponCalibreDisplay').textContent = option.getAttribute('data-calibre') || '-';
}

// Update ammunition details when ammo selected
function updateAmmoDetails(select) {
    const option = select.options[select.selectedIndex];
    
    document.getElementById('ammoTypeDisplay').textContent = option.getAttribute('data-type') || '-';
    document.getElementById('ammoCalibreDisplay').textContent = option.getAttribute('data-calibre') || '-';
    
    const balance = parseInt(option.getAttribute('data-balance')) || 0;
    document.getElementById('ammoBalanceDisplay').textContent = balance;
    
    // Update max for units issued
    const unitsInput = document.getElementById('units_issued');
    unitsInput.max = balance;
    
    if (parseInt(unitsInput.value) > balance) {
        unitsInput.value = balance;
        calculateTotalRounds();
    }
}

// Calculate total rounds
function calculateTotalRounds() {
    const units = parseInt(document.getElementById('units_issued').value) || 0;
    const roundsPerUnit = 30; // Standard
    const total = units * roundsPerUnit;
    document.getElementById('total_rounds').value = total;
}

// Reset weapon form
function resetWeaponForm() {
    if (confirm('Reset weapon issue form? All entered data will be lost.')) {
        document.getElementById('weaponIssueForm').reset();
        
        // Reset weapon details display
        document.getElementById('weaponTypeDisplay').textContent = '-';
        document.getElementById('weaponModelDisplay').textContent = '-';
        document.getElementById('weaponSerialDisplay').textContent = '-';
        document.getElementById('weaponCalibreDisplay').textContent = '-';
        
        // Hide other field
        document.getElementById('purposeOtherGroup').style.display = 'none';
        
        // Set default date
        document.getElementById('issue_date').value = new Date().toISOString().split('T')[0];
        
        // Show notification
        showNotification('info', 'Form has been reset');
    }
}

// Reset ammunition form
function resetAmmoForm() {
    if (confirm('Reset ammunition issue form? All entered data will be lost.')) {
        document.getElementById('ammoIssueForm').reset();
        
        // Reset ammo details display
        document.getElementById('ammoTypeDisplay').textContent = '-';
        document.getElementById('ammoCalibreDisplay').textContent = '-';
        document.getElementById('ammoBalanceDisplay').textContent = '-';
        
        // Hide other field
        document.getElementById('ammoPurposeOtherGroup').style.display = 'none';
        
        // Reset total rounds
        document.getElementById('total_rounds').value = '30';
        
        // Set default date
        document.getElementById('ammo_issue_date').value = new Date().toISOString().split('T')[0];
        
        // Show notification
        showNotification('info', 'Form has been reset');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates if not set
    const today = new Date().toISOString().split('T')[0];
    
    const issueDate = document.getElementById('issue_date');
    if (issueDate && !issueDate.value) {
        issueDate.value = today;
    }
    
    const ammoIssueDate = document.getElementById('ammo_issue_date');
    if (ammoIssueDate && !ammoIssueDate.value) {
        ammoIssueDate.value = today;
    }
    
    // Set default expected return date (30 days from now) if not set
    const returnDate = document.getElementById('expected_return_date');
    if (returnDate && !returnDate.value) {
        const date = new Date();
        date.setDate(date.getDate() + 30);
        returnDate.value = date.toISOString().split('T')[0];
    }
    
    // Initialize any preselected values
    const weaponSelect = document.getElementById('weapon_id');
    if (weaponSelect && weaponSelect.value) {
        updateWeaponDetails(weaponSelect);
    }
    
    const ammoSelect = document.getElementById('ammo_id');
    if (ammoSelect && ammoSelect.value) {
        updateAmmoDetails(ammoSelect);
        calculateTotalRounds();
    }
    
    // Auto-populate when URL contains requisition_id parameter
    const urlParams = new URLSearchParams(window.location.search);
    const reqIdFromUrl = urlParams.get('requisition_id');
    if (reqIdFromUrl) {
        const weaponReqSelect = document.getElementById('requisition_id');
        const ammoReqSelect = document.getElementById('ammo_requisition_id');
        
        if (weaponReqSelect) {
            weaponReqSelect.value = reqIdFromUrl;
            if (weaponReqSelect.value) {
                loadRequisitionDetails(weaponReqSelect.value, 'weapon');
            }
        }
        if (ammoReqSelect) {
            ammoReqSelect.value = reqIdFromUrl;
            if (ammoReqSelect.value) {
                loadRequisitionDetails(ammoReqSelect.value, 'ammo');
            }
        }
    }
});

// Form validation
document.getElementById('weaponIssueForm')?.addEventListener('submit', function(e) {
    const weaponId = document.getElementById('weapon_id').value;
    const officerName = document.getElementById('officer_name').value;
    
    if (!weaponId) {
        e.preventDefault();
        alert('Please select a weapon to issue');
        return false;
    }
    
    if (!officerName) {
        e.preventDefault();
        alert('Please enter officer name');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
});

document.getElementById('ammoIssueForm')?.addEventListener('submit', function(e) {
    const ammoId = document.getElementById('ammo_id').value;
    const units = parseInt(document.getElementById('units_issued').value) || 0;
    const balance = parseInt(document.getElementById('ammo_id').options[document.getElementById('ammo_id').selectedIndex]?.getAttribute('data-balance') || 0);
    
    if (!ammoId) {
        e.preventDefault();
        alert('Please select ammunition to issue');
        return false;
    }
    
    if (units <= 0) {
        e.preventDefault();
        alert('Please enter valid units to issue');
        return false;
    }
    
    if (units > balance) {
        e.preventDefault();
        alert(`Cannot issue more than available balance. Available: ${balance} units`);
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
});

// Show notification function (if not already defined)
function showNotification(type, message) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(type, message);
    } else {
        alert(message);
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
