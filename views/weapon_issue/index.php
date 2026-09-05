<?php
$title = 'Weapons & Ammunition Issue';
$active = 'weapon_issue';
$extra_css = [BASE_URL . '/assets/css/weapon_issue.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// These variables come from the controller
$availableWeapons = isset($availableWeapons) ? $availableWeapons : [];
$availableAmmunition = isset($availableAmmunition) ? $availableAmmunition : [];
$requisitions = isset($requisitions) ? $requisitions : [];
$recentWeaponIssues = isset($recentWeaponIssues) ? $recentWeaponIssues : [];
$recentAmmoIssues = isset($recentAmmoIssues) ? $recentAmmoIssues : [];
$issuedWeapons = isset($issuedWeapons) ? $issuedWeapons : [];
$issuedAmmunition = isset($issuedAmmunition) ? $issuedAmmunition : [];
$statistics = isset($statistics) ? $statistics : [];
$issuedPage = isset($issuedPage) ? (int) $issuedPage : 1;
$issuedTotalPages = isset($issuedTotalPages) ? (int) $issuedTotalPages : 1;
$issuedTotalCount = isset($issuedTotalCount) ? (int) $issuedTotalCount : count($issuedWeapons);

// Generate CSRF token using Security class
$csrfToken = Security::csrfToken();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-hand-holding"></i>
                Weapons & Ammunition Issue
            </h1>
            <p>Issue firearms and ammunition to authorized personnel</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL ?? ''; ?>/weapon_issue/history" class="btn btn-info">
                <i class="fas fa-history"></i> View Full History
            </a>
            <a href="<?php echo BASE_URL ?? ''; ?>/weapons" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Weapons
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon issued">
                <i class="fas fa-gun"></i>
            </div>
            <div class="stat-details">
                <h4>Issued Weapons</h4>
                <p class="stat-number"><?php echo number_format($statistics['total_issued_weapons'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon available">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h4>Available Weapons</h4>
                <p class="stat-number"><?php echo number_format($statistics['available_weapons'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h4>Pending Returns</h4>
                <p class="stat-number"><?php echo number_format($statistics['pending_returns'] ?? 0); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ammo">
                <i class="fas fa-bullseye"></i>
            </div>
            <div class="stat-details">
                <h4>Ammo Types</h4>
                <p class="stat-number"><?php echo number_format($statistics['available_ammo'] ?? 0); ?></p>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-btn active" onclick="showTab('issue', this)">
            <i class="fas fa-plus-circle"></i> Issue Items
        </button>
        <button class="tab-btn" onclick="showTab('returns', this)">
            <i class="fas fa-undo-alt"></i> Process Returns
        </button>
        <button class="tab-btn" onclick="showTab('recent', this)">
            <i class="fas fa-history"></i> Recent Issues
        </button>
    </div>

    <!-- Issue Tab -->
    <div id="tab-issue" class="tab-content active">
        <!-- Issue Type Selector -->
        <div class="issue-type-selector">
            <button class="type-btn active" onclick="showIssueType('weapon', this)">
                <i class="fas fa-gun"></i> Issue Weapon
            </button>
            <button class="type-btn" onclick="showIssueType('ammunition', this)">
                <i class="fas fa-bullseye"></i> Issue Ammunition
            </button>
        </div>

        <!-- Weapon Issue Form -->
        <div id="weapon-issue-form" class="issue-form active">
            <div class="form-section">
                <div class="section-title">
                    <h3><i class="fas fa-gun"></i> Issue Weapon</h3>
                </div>

                <form method="POST" action="<?php echo BASE_URL ?? ''; ?>/weapon_issue/store" id="weaponIssueForm">
                    <!-- Manual CSRF field - THIS IS LINE 111 -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="issue_type" value="weapon">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="requisition_id">Requisition ID (Optional)</label>
                            <select name="requisition_id" id="requisition_id" class="form-control" onchange="loadRequisitionDetails(this.value, 'weapon')">
                                <option value="">None (Direct Issue)</option>
                                <?php foreach ($requisitions as $req): ?>
                                    <?php 
                                        $remWpn = $req['remaining_weapons'] ?? $req['total_weapons'] ?? 0;
                                        $totWpn = (int)($req['total_weapons'] ?? 0);
                                        $totItems = (int)($req['total_items'] ?? 0);
                                        if (in_array($req['status'] ?? '', ['Issued', 'Completed'])) continue;
                                        if ($totItems > 0 && $totWpn > 0 && $remWpn <= 0) continue;
                                        if ($totItems > 0 && $totWpn == 0) continue;
                                    ?>
                                    <option value="<?php echo $req['id'] ?? ''; ?>" 
                                            data-officer="<?php echo htmlspecialchars($req['requesting_officer_name'] ?? ''); ?>"
                                            data-rank="<?php echo htmlspecialchars($req['requesting_rank'] ?? ''); ?>"
                                            data-nis="<?php echo htmlspecialchars($req['requesting_nis'] ?? ''); ?>"
                                            data-unit="<?php echo htmlspecialchars($req['requesting_command_name'] ?? $req['command_name'] ?? ''); ?>"
                                            data-purpose="<?php echo htmlspecialchars($req['weapon_purpose'] ?? $req['justification'] ?? ''); ?>"
                                            data-purpose-other="<?php echo htmlspecialchars($req['weapon_purpose_other'] ?? ''); ?>"
                                            data-approved-by="<?php echo htmlspecialchars($req['final_approved_by_name'] ?? $req['approved_by_name'] ?? $req['hq_vetted_by_name'] ?? $req['command_approved_by_name'] ?? ''); ?>"
                                            data-remaining-weapons="<?php echo $remWpn; ?>"
                                            data-total-weapons="<?php echo $totWpn; ?>"
                                            <?php echo ((isset($_GET['requisition_id']) && $_GET['requisition_id'] == $req['id']) || ($old['requisition_id'] ?? '') == $req['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($req['requisition_number'] ?? '') . ' - ' . ($req['requesting_officer_name'] ?? '') . ' (' . ($totWpn > 0 ? $remWpn . ' weapons' : 'General') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="issue_date" class="required">Issue Date</label>
                            <input type="date" id="issue_date" name="issue_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="weapon_id" class="required">Select Weapon</label>
                            <select name="weapon_id" id="weapon_id" class="form-control" required onchange="updateWeaponDetails(this)">
                                <option value="">-- Select Weapon --</option>
                                <?php foreach ($availableWeapons as $weapon): ?>
                                <option value="<?php echo $weapon['id'] ?? ''; ?>"
                                        data-type="<?php echo htmlspecialchars($weapon['type_name'] ?? $weapon['weapon_type_other'] ?? 'Other'); ?>"
                                        data-model="<?php echo htmlspecialchars($weapon['make_model'] ?? ''); ?>"
                                        data-serial="<?php echo htmlspecialchars($weapon['serial_no'] ?? ''); ?>"
                                        data-calibre="<?php echo htmlspecialchars($weapon['calibre_name'] ?? $weapon['calibre_other'] ?? 'N/A'); ?>">
                                    <?php echo htmlspecialchars(($weapon['weapon_id'] ?? '') . ' - ' . ($weapon['make_model'] ?? '') . ' (SN: ' . ($weapon['serial_no'] ?? '') . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="weapon_quantity" class="required">Quantity to Issue</label>
                            <input type="number" name="quantity" id="weapon_quantity" class="form-control" value="1" min="1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Weapon Details</label>
                            <div class="weapon-details-display" id="weaponDetails">
                                <div class="detail-item"><span class="label">Type:</span> <span id="weaponTypeDisplay">-</span></div>
                                <div class="detail-item"><span class="label">Model:</span> <span id="weaponModelDisplay">-</span></div>
                                <div class="detail-item"><span class="label">Serial:</span> <span id="weaponSerialDisplay">-</span></div>
                                <div class="detail-item"><span class="label">Calibre:</span> <span id="weaponCalibreDisplay">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="officer_name" class="required">Officer Name</label>
                            <input type="text" name="officer_name" id="officer_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="officer_rank" class="required">Rank</label>
                            <select name="officer_rank" id="officer_rank" class="form-control" required>
                                <option value="">Select Rank</option>
                                <?php foreach (getNisRanks() as $rank): ?>
                                    <option value="<?php echo htmlspecialchars($rank); ?>"><?php echo htmlspecialchars($rank); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="officer_nis">NIS Number</label>
                            <input type="text" name="officer_nis" id="officer_nis"
                                   maxlength="20" inputmode="numeric" pattern="[0-9]*" title="Numbers only"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="unit" class="required">Unit/Department</label>
                            <input type="text" name="unit" id="unit" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="purpose" class="required">Purpose</label>
                            <select name="purpose" id="purpose" class="form-control" required onchange="togglePurposeOther(this)">
                                <option value="">Select Purpose</option>
                                <option value="Special Operation">Special Operation</option>
                                <option value="Training Exercise">Training Exercise</option>
                                <option value="Patrol Duty">Patrol Duty</option>
                                <option value="Security Detail">Security Detail</option>
                                <option value="Emergency Response">Emergency Response</option>
                                <option value="Escort Duty">Escort Duty</option>
                                <option value="Border Patrol">Border Patrol</option>
                                <option value="Training">Training</option>
                                <option value="Operation">Operation</option>
                                <option value="Patrol">Patrol</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Crowd Control">Crowd Control</option>
                                <option value="Riot Control">Riot Control</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group" id="purposeOtherGroup" style="display: none;">
                            <label for="purpose_other">Specify Purpose</label>
                            <input type="text" name="purpose_other" id="purpose_other" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="approved_by" class="required">Approved By</label>
                            <input type="text" name="approved_by" id="approved_by" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="expected_return_date">Expected Return Date</label>
                            <input type="date" name="expected_return_date" id="expected_return_date" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <input type="text" name="remarks" id="remarks" class="form-control">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-circle"></i> Issue Weapon
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetWeaponForm()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ammunition Issue Form -->
        <div id="ammunition-issue-form" class="issue-form">
            <div class="form-section">
                <div class="section-title">
                    <h3><i class="fas fa-bullseye"></i> Issue Ammunition</h3>
                </div>

                <form method="POST" action="<?php echo BASE_URL ?? ''; ?>/weapon_issue/store" id="ammoIssueForm">
                    <!-- Manual CSRF field -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="issue_type" value="ammunition">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ammo_requisition_id">Requisition ID (Optional)</label>
                            <select name="requisition_id" id="ammo_requisition_id" class="form-control" onchange="loadRequisitionDetails(this.value, 'ammo')">
                                <option value="">None (Direct Issue)</option>
                                <?php foreach ($requisitions as $req): ?>
                                    <?php 
                                        $remAmmo = $req['remaining_ammunition'] ?? $req['total_ammunition'] ?? 0;
                                        $totAmmo = (int)($req['total_ammunition'] ?? 0);
                                        $totItems = (int)($req['total_items'] ?? 0);
                                        if (in_array($req['status'] ?? '', ['Issued', 'Completed'])) continue;
                                        if ($totItems > 0 && $totAmmo > 0 && $remAmmo <= 0) continue;
                                        if ($totItems > 0 && $totAmmo == 0) continue;
                                    ?>
                                    <option value="<?php echo $req['id'] ?? ''; ?>" 
                                            data-officer="<?php echo htmlspecialchars($req['requesting_officer_name'] ?? ''); ?>"
                                            data-rank="<?php echo htmlspecialchars($req['requesting_rank'] ?? ''); ?>"
                                            data-nis="<?php echo htmlspecialchars($req['requesting_nis'] ?? ''); ?>"
                                            data-unit="<?php echo htmlspecialchars($req['requesting_command_name'] ?? $req['command_name'] ?? ''); ?>"
                                            data-purpose="<?php echo htmlspecialchars($req['ammo_purpose'] ?? $req['justification'] ?? ''); ?>"
                                            data-purpose-other="<?php echo htmlspecialchars($req['ammo_purpose_other'] ?? ''); ?>"
                                            data-approved-by="<?php echo htmlspecialchars($req['final_approved_by_name'] ?? $req['approved_by_name'] ?? $req['hq_vetted_by_name'] ?? $req['command_approved_by_name'] ?? ''); ?>"
                                            data-remaining-ammo="<?php echo $remAmmo; ?>"
                                            data-total-ammo="<?php echo $totAmmo; ?>"
                                            <?php echo ((isset($_GET['requisition_id']) && $_GET['requisition_id'] == $req['id']) || ($old['requisition_id'] ?? '') == $req['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($req['requisition_number'] ?? '') . ' - ' . ($req['requesting_officer_name'] ?? '') . ' (' . ($totAmmo > 0 ? $remAmmo . ' rounds' : 'General') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="ammo_issue_date" class="required">Issue Date</label>
                            <input type="date" id="ammo_issue_date" name="issue_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ammo_id" class="required">Select Ammunition</label>
                            <select name="ammo_id" id="ammo_id" class="form-control" required onchange="updateAmmoDetails(this)">
                                <option value="">-- Select Ammunition --</option>
                                <?php foreach ($availableAmmunition as $ammo): ?>
                                <option value="<?php echo $ammo['id'] ?? ''; ?>"
                                        data-type="<?php echo htmlspecialchars($ammo['ammo_type'] ?? 'Ammunition'); ?>"
                                        data-calibre="<?php echo htmlspecialchars($ammo['calibre'] ?? 'N/A'); ?>"
                                        data-balance="<?php echo $ammo['balance'] ?? 0; ?>">
                                    <?php echo htmlspecialchars(($ammo['ammo_id'] ?? '') . ' - ' . ($ammo['ammo_type'] ?? '') . ' (' . ($ammo['calibre'] ?? '') . ') - Balance: ' . ($ammo['balance'] ?? 0)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Ammunition Details</label>
                            <div class="weapon-details-display" id="ammoDetails">
                                <div class="detail-item"><span class="label">Type:</span> <span id="ammoTypeDisplay">-</span></div>
                                <div class="detail-item"><span class="label">Calibre:</span> <span id="ammoCalibreDisplay">-</span></div>
                                <div class="detail-item"><span class="label">Balance:</span> <span id="ammoBalanceDisplay">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="units_issued" class="required">Units to Issue</label>
                            <input type="number" name="units_issued" id="units_issued" class="form-control" 
                                   min="1" value="1" required oninput="calculateTotalRounds()">
                        </div>

                        <div class="form-group">
                            <label for="total_rounds">Total Rounds</label>
                            <input type="number" name="total_rounds" id="total_rounds" class="form-control" 
                                   value="30" readonly>
                            <small class="form-hint">Calculated automatically (30 rounds per unit)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="issued_to" class="required">Issued To (Officer Name)</label>
                            <input type="text" name="issued_to" id="issued_to" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="ammo_purpose" class="required">Purpose</label>
                            <select name="purpose" id="ammo_purpose" class="form-control" required onchange="toggleAmmoPurposeOther(this)">
                                <option value="">Select Purpose</option>
                                <option value="Special Operation">Special Operation</option>
                                <option value="Training Exercise">Training Exercise</option>
                                <option value="Patrol Duty">Patrol Duty</option>
                                <option value="Security Detail">Security Detail</option>
                                <option value="Emergency Response">Emergency Response</option>
                                <option value="Escort Duty">Escort Duty</option>
                                <option value="Border Patrol">Border Patrol</option>
                                <option value="Training">Training</option>
                                <option value="Operation">Operation</option>
                                <option value="Patrol">Patrol</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Crowd Control">Crowd Control</option>
                                <option value="Riot Control">Riot Control</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="ammoPurposeOtherGroup" style="display: none;">
                        <label for="ammo_purpose_other">Specify Purpose</label>
                        <input type="text" name="purpose_other" id="ammo_purpose_other" class="form-control">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ammo_approved_by" class="required">Approved By</label>
                            <input type="text" name="approved_by" id="ammo_approved_by" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="ammo_remarks">Remarks</label>
                            <input type="text" name="remarks" id="ammo_remarks" class="form-control">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-circle"></i> Issue Ammunition
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetAmmoForm()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Returns Tab -->
    <div id="tab-returns" class="tab-content">
        <div class="returns-section">
            <div class="section-title">
                <h3><i class="fas fa-undo-alt"></i> Process Returns</h3>
                <div class="card-actions">
                    <input type="text" id="searchReturns" class="search-input" placeholder="Search issued items...">
                </div>
            </div>

            <!-- Issued Weapons Table -->
            <div class="subsection">
                <h4>
                    <i class="fas fa-gun"></i> Issued Weapons
                    <small style="font-weight: 400; color: #6c757d;">(<?php echo number_format($issuedTotalCount); ?> awaiting return)</small>
                </h4>
                <div class="table-responsive">
                    <?php if (empty($issuedWeapons)): ?>
                        <div class="empty-state">
                            <i class="fas fa-gun"></i>
                            <p>No weapons currently issued</p>
                        </div>
                    <?php else: ?>
                    <table class="asset-table" id="issuedWeaponsTable">
                        <thead>
                            <tr>
                                <th>Weapon ID</th>
                                <th>Make/Model</th>
                                <th>Serial No.</th>
                                <th>Issued To</th>
                                <th>Issue Date</th>
                                <th>Expected Return</th>
                                <th>Days Out</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issuedWeapons as $weapon): 
                                $issueDate = isset($weapon['issue_date']) ? strtotime($weapon['issue_date']) : time();
                                $daysOut = floor((time() - $issueDate) / (60 * 60 * 24));
                                $expectedReturn = isset($weapon['expected_return_date']) ? strtotime($weapon['expected_return_date']) : 0;
                                $overdueClass = ($expectedReturn && $expectedReturn < time()) ? 'overdue' : '';
                            ?>
                            <tr class="<?php echo $overdueClass; ?>">
                                <td><?php echo htmlspecialchars($weapon['weapon_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($weapon['make_model'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($weapon['serial_no'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($weapon['officer_name'] ?? ''); ?></td>
                                <td><?php echo !empty($weapon['issue_date']) ? date('d/m/Y', strtotime($weapon['issue_date'])) : '-'; ?></td>
                                <td>
                                    <?php echo !empty($weapon['expected_return_date']) ? date('d/m/Y', strtotime($weapon['expected_return_date'])) : 'Not set'; ?>
                                </td>
                                <td>
                                    <span class="days-badge <?php echo $daysOut > 30 ? 'badge-danger' : ($daysOut > 14 ? 'badge-warning' : 'badge-success'); ?>">
                                        <?php echo $daysOut; ?> days
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL ?? ''; ?>/weapon_issue/return/<?php echo $weapon['id'] ?? ''; ?>?type=weapon" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-undo-alt"></i> Return
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($issuedTotalPages > 1): ?>
                        <?php
                        $issuedQueryParams = $_GET;
                        unset($issuedQueryParams['page']);
                        $issuedQueryString = http_build_query($issuedQueryParams);
                        $issuedQueryString = $issuedQueryString ? '&' . $issuedQueryString : '';
                        ?>
                        <div class="pagination">
                            <a href="?page=<?php echo max(1, $issuedPage - 1); ?><?php echo $issuedQueryString; ?>" class="page-link <?php echo $issuedPage <= 1 ? 'disabled' : ''; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                            <span class="page-info">Page <?php echo $issuedPage; ?> of <?php echo $issuedTotalPages; ?></span>
                            <a href="?page=<?php echo min($issuedTotalPages, $issuedPage + 1); ?><?php echo $issuedQueryString; ?>" class="page-link <?php echo $issuedPage >= $issuedTotalPages ? 'disabled' : ''; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Issued Ammunition Table -->
            <div class="subsection">
                <h4><i class="fas fa-bullseye"></i> Issued Ammunition</h4>
                <div class="table-responsive">
                    <?php if (empty($issuedAmmunition)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bullseye"></i>
                            <p>No ammunition currently issued</p>
                        </div>
                    <?php else: ?>
                    <table class="asset-table" id="issuedAmmoTable">
                        <thead>
                            <tr>
                                <th>Ammunition</th>
                                <th>Type/Calibre</th>
                                <th>Units Issued</th>
                                <th>Issued To</th>
                                <th>Issue Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issuedAmmunition as $ammo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ammo['ammo_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(($ammo['ammo_type'] ?? '') . ' (' . ($ammo['calibre'] ?? '') . ')'); ?></td>
                                <td><?php echo $ammo['units_issued'] ?? 0; ?></td>
                                <td><?php echo htmlspecialchars($ammo['issued_to'] ?? ''); ?></td>
                                <td><?php echo !empty($ammo['issue_date']) ? date('d/m/Y', strtotime($ammo['issue_date'])) : '-'; ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL ?? ''; ?>/weapon_issue/return/<?php echo $ammo['id'] ?? ''; ?>?type=ammunition" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-undo-alt"></i> Return
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Issues Tab -->
    <div id="tab-recent" class="tab-content">
        <div class="recent-section">
            <!-- Recent Weapon Issues -->
            <div class="subsection">
                <div class="section-title">
                    <h3><i class="fas fa-gun"></i> Recent Weapon Issues</h3>
                    <a href="<?php echo BASE_URL ?? ''; ?>/weapon_issue/history?type=weapons" class="view-all">View All</a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($recentWeaponIssues)): ?>
                        <div class="empty-state">
                            <i class="fas fa-gun"></i>
                            <p>No weapon issues recorded</p>
                        </div>
                    <?php else: ?>
                    <table class="asset-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Weapon ID</th>
                                <th>Make/Model</th>
                                <th>Officer</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentWeaponIssues as $issue): ?>
                            <tr>
                                <td><?php echo !empty($issue['issue_date']) ? date('d/m/Y', strtotime($issue['issue_date'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($issue['weapon_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($issue['make_model'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($issue['officer_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($issue['purpose'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = '';
                                    if (($issue['status'] ?? '') == 'Returned') $statusClass = 'status-active';
                                    elseif (($issue['status'] ?? '') == 'Issued') $statusClass = 'status-warning';
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($issue['status'] ?? 'Issued'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL ?? ''; ?>/weapon_issue/show/<?php echo $issue['id'] ?? ''; ?>?type=weapon" 
                                       class="btn-icon" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Ammunition Issues -->
            <div class="subsection">
                <div class="section-title">
                    <h3><i class="fas fa-bullseye"></i> Recent Ammunition Issues</h3>
                    <a href="<?php echo BASE_URL ?? ''; ?>/weapon_issue/history?type=ammunition" class="view-all">View All</a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($recentAmmoIssues)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bullseye"></i>
                            <p>No ammunition issues recorded</p>
                        </div>
                    <?php else: ?>
                    <table class="asset-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Ammunition</th>
                                <th>Units</th>
                                <th>Rounds</th>
                                <th>Issued To</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAmmoIssues as $issue): ?>
                            <tr>
                                <td><?php echo !empty($issue['issue_date']) ? date('d/m/Y', strtotime($issue['issue_date'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($issue['ammo_id'] ?? ''); ?></td>
                                <td><?php echo $issue['units_issued'] ?? 0; ?></td>
                                <td><?php echo $issue['total_rounds'] ?? 0; ?></td>
                                <td><?php echo htmlspecialchars($issue['issued_to'] ?? ''); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($issue['status'] ?? 'issued'); ?>">
                                        <?php echo htmlspecialchars($issue['status'] ?? 'Issued'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL ?? ''; ?>/weapon_issue/show/<?php echo $issue['id'] ?? ''; ?>?type=ammunition" 
                                       class="btn-icon" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tab switching - Fixed to accept element parameter
function showTab(tabName, element) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
    
    // Add active class to clicked button
    if (element) {
        element.classList.add('active');
    }
}

// Issue type switching - Fixed to accept element parameter
function showIssueType(type, element) {
    document.querySelectorAll('.issue-form').forEach(form => {
        form.classList.remove('active');
    });
    
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById(type + '-issue-form').classList.add('active');
    
    if (element) {
        element.classList.add('active');
    }
}

// Purpose other field toggle
function togglePurposeOther(select) {
    const otherGroup = document.getElementById('purposeOtherGroup');
    const otherInput = document.getElementById('purpose_other');
    
    if (select.value === 'Other') {
        otherGroup.style.display = 'block';
        if (otherInput) otherInput.required = true;
    } else {
        otherGroup.style.display = 'none';
        if (otherInput) {
            otherInput.required = false;
            otherInput.value = '';
        }
    }
}

function toggleAmmoPurposeOther(select) {
    const otherGroup = document.getElementById('ammoPurposeOtherGroup');
    const otherInput = document.getElementById('ammo_purpose_other');
    
    if (select.value === 'Other') {
        otherGroup.style.display = 'block';
        if (otherInput) otherInput.required = true;
    } else {
        otherGroup.style.display = 'none';
        if (otherInput) {
            otherInput.required = false;
            otherInput.value = '';
        }
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

// Update weapon details
function updateWeaponDetails(select) {
    const option = select.options[select.selectedIndex];
    
    document.getElementById('weaponTypeDisplay').textContent = option.getAttribute('data-type') || '-';
    document.getElementById('weaponModelDisplay').textContent = option.getAttribute('data-model') || '-';
    document.getElementById('weaponSerialDisplay').textContent = option.getAttribute('data-serial') || '-';
    document.getElementById('weaponCalibreDisplay').textContent = option.getAttribute('data-calibre') || '-';
}

// Update ammunition details
function updateAmmoDetails(select) {
    const option = select.options[select.selectedIndex];
    
    document.getElementById('ammoTypeDisplay').textContent = option.getAttribute('data-type') || '-';
    document.getElementById('ammoCalibreDisplay').textContent = option.getAttribute('data-calibre') || '-';
    document.getElementById('ammoBalanceDisplay').textContent = option.getAttribute('data-balance') || '-';
    
    // Update max for units issued
    const unitsInput = document.getElementById('units_issued');
    if (unitsInput) {
        const balance = parseInt(option.getAttribute('data-balance')) || 0;
        unitsInput.max = balance;
        
        if (parseInt(unitsInput.value) > balance) {
            unitsInput.value = balance;
            calculateTotalRounds();
        }
    }
}

// Calculate total rounds
function calculateTotalRounds() {
    const units = parseInt(document.getElementById('units_issued').value) || 0;
    const total = units * 30; // 30 rounds per unit as standard
    const totalField = document.getElementById('total_rounds');
    if (totalField) totalField.value = total;
}

// Reset forms
function resetWeaponForm() {
    if (confirm('Reset weapon issue form? All entered data will be lost.')) {
        const form = document.getElementById('weaponIssueForm');
        if (form) form.reset();
        
        // Reset weapon details display
        document.getElementById('weaponTypeDisplay').textContent = '-';
        document.getElementById('weaponModelDisplay').textContent = '-';
        document.getElementById('weaponSerialDisplay').textContent = '-';
        document.getElementById('weaponCalibreDisplay').textContent = '-';
        
        // Hide other field
        document.getElementById('purposeOtherGroup').style.display = 'none';
        
        // Set default date
        const today = new Date().toISOString().split('T')[0];
        const issueDate = document.getElementById('issue_date');
        if (issueDate) issueDate.value = today;
        
        // Set default return date (30 days from now)
        const date = new Date();
        date.setDate(date.getDate() + 30);
        const returnDate = document.getElementById('expected_return_date');
        if (returnDate) returnDate.value = date.toISOString().split('T')[0];
    }
}

function resetAmmoForm() {
    if (confirm('Reset ammunition issue form? All entered data will be lost.')) {
        const form = document.getElementById('ammoIssueForm');
        if (form) form.reset();
        
        // Reset ammo details display
        document.getElementById('ammoTypeDisplay').textContent = '-';
        document.getElementById('ammoCalibreDisplay').textContent = '-';
        document.getElementById('ammoBalanceDisplay').textContent = '-';
        
        // Hide other field
        document.getElementById('ammoPurposeOtherGroup').style.display = 'none';
        
        // Reset total rounds
        const totalField = document.getElementById('total_rounds');
        if (totalField) totalField.value = '30';
        
        // Set default date
        const today = new Date().toISOString().split('T')[0];
        const ammoIssueDate = document.getElementById('ammo_issue_date');
        if (ammoIssueDate) ammoIssueDate.value = today;
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
    
    // Issued-weapons search: the list is paginated server-side (50/page —
    // see WeaponIssueController::getIssuedWeapons()), so searching reloads
    // the page with ?search= instead of hiding rows in a fully-rendered
    // table.
    const searchReturns = document.getElementById('searchReturns');
    if (searchReturns) {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('search')) {
            searchReturns.value = urlParams.get('search');
        }

        let searchReturnsTimeout;
        searchReturns.addEventListener('input', function(e) {
            clearTimeout(searchReturnsTimeout);
            searchReturnsTimeout = setTimeout(function() {
                const term = e.target.value.trim();
                let url = window.location.pathname + '?page=1';
                if (term) url += '&search=' + encodeURIComponent(term);
                window.location.href = url;
            }, 600);
        });

        // Issued ammunition is a short, unpaginated list — a lightweight
        // client-side filter is fine for it.
        searchReturns.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('#issuedAmmoTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Auto-populate when URL contains requisition_id parameter
    const urlParamsReq = new URLSearchParams(window.location.search);
    const reqIdFromUrl = urlParamsReq.get('requisition_id');
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
    const approvedBy = document.getElementById('approved_by').value;
    
    if (!weaponId) {
        e.preventDefault();
        alert('Please select a weapon');
        return false;
    }
    
    if (!officerName) {
        e.preventDefault();
        alert('Please enter officer name');
        return false;
    }
    
    if (!approvedBy) {
        e.preventDefault();
        alert('Please enter approving officer');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
    }
});

document.getElementById('ammoIssueForm')?.addEventListener('submit', function(e) {
    const ammoId = document.getElementById('ammo_id').value;
    const units = parseInt(document.getElementById('units_issued').value) || 0;
    const issuedTo = document.getElementById('issued_to').value;
    const approvedBy = document.getElementById('ammo_approved_by').value;
    
    if (!ammoId) {
        e.preventDefault();
        alert('Please select ammunition');
        return false;
    }
    
    if (!issuedTo) {
        e.preventDefault();
        alert('Please enter who the ammunition is issued to');
        return false;
    }
    
    if (!approvedBy) {
        e.preventDefault();
        alert('Please enter approving officer');
        return false;
    }
    
    // Get selected option to check balance
    const select = document.getElementById('ammo_id');
    const selectedOption = select.options[select.selectedIndex];
    const balance = parseInt(selectedOption?.getAttribute('data-balance') || 0);
    
    if (units > balance) {
        e.preventDefault();
        alert(`Cannot issue more than available balance. Available: ${balance} units`);
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
