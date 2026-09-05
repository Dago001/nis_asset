<?php
$title = 'Create Quarterly Audit';
$active = 'audit';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

$old = Session::get('old', []);
$errors = Session::get('errors', []);
Session::remove('old');
Session::remove('errors');

// Determine current quarter
$currentMonth = date('n');
$currentYear = date('Y');
$currentQuarter = 'Q' . ceil($currentMonth / 3);
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Create Quarterly Audit
            </h1>
            <p>Conduct quarterly audit of weapons and ammunition</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/audit/quarterly" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Audits
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="form-section">
        <form method="POST" action="<?php echo BASE_URL; ?>/audit/quarterly/store" id="auditForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Audit Header -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-file-signature"></i> Audit Details</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Audit Date</label>
                        <input type="date" name="audit_date" id="audit_date" 
                               value="<?php echo Security::escape($old['audit_date'] ?? date('Y-m-d')); ?>" 
                               required class="form-control <?php echo isset($errors['audit_date']) ? 'error' : ''; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['audit_date'])): ?>
                            <small class="error-text"><?php echo $errors['audit_date']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Quarter</label>
                        <select name="quarter" id="quarter" required 
                                class="form-control <?php echo isset($errors['quarter']) ? 'error' : ''; ?>">
                            <option value="">Select Quarter</option>
                            <option value="Q1" <?php echo ($old['quarter'] ?? $currentQuarter) == 'Q1' ? 'selected' : ''; ?>>Q1 (January - March)</option>
                            <option value="Q2" <?php echo ($old['quarter'] ?? $currentQuarter) == 'Q2' ? 'selected' : ''; ?>>Q2 (April - June)</option>
                            <option value="Q3" <?php echo ($old['quarter'] ?? $currentQuarter) == 'Q3' ? 'selected' : ''; ?>>Q3 (July - September)</option>
                            <option value="Q4" <?php echo ($old['quarter'] ?? $currentQuarter) == 'Q4' ? 'selected' : ''; ?>>Q4 (October - December)</option>
                        </select>
                        <?php if (isset($errors['quarter'])): ?>
                            <small class="error-text"><?php echo $errors['quarter']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Year</label>
                        <select name="year" id="year" required 
                                class="form-control <?php echo isset($errors['year']) ? 'error' : ''; ?>">
                            <option value="">Select Year</option>
                            <?php for ($y = $currentYear; $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($old['year'] ?? $currentYear) == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                        <?php if (isset($errors['year'])): ?>
                            <small class="error-text"><?php echo $errors['year']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Audit Location</label>
                        <input type="text" name="audit_location" 
                               value="<?php echo Security::escape($old['audit_location'] ?? ''); ?>" 
                               required maxlength="200" 
                               class="form-control <?php echo isset($errors['audit_location']) ? 'error' : ''; ?>"
                               placeholder="e.g., Main Armoury, HQ">
                        <?php if (isset($errors['audit_location'])): ?>
                            <small class="error-text"><?php echo $errors['audit_location']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Command/Formation</label>
                        <select name="command_id" id="command_id" required 
                                class="form-control <?php echo isset($errors['command_id']) ? 'error' : ''; ?>">
                            <option value="">Select Command</option>
                            <?php foreach ($commands as $cmd): ?>
                            <option value="<?php echo $cmd['id']; ?>" 
                                    <?php echo ($old['command_id'] ?? '') == $cmd['id'] ? 'selected' : ''; ?>>
                                <?php echo Security::escape($cmd['command_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['command_id'])): ?>
                            <small class="error-text"><?php echo $errors['command_id']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Audit Officer Information -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-user-shield"></i> Audit Officer</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Officer Name</label>
                        <input type="text" name="audit_officer" 
                               value="<?php echo Security::escape($old['audit_officer'] ?? ''); ?>" 
                               required maxlength="100" pattern="[a-zA-Z\s\-'.]+" title="Alphabets, spaces, hyphens (-), and apostrophes (') only"
                               class="form-control <?php echo isset($errors['audit_officer']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['audit_officer'])): ?>
                            <small class="error-text"><?php echo $errors['audit_officer']; ?></small>
                        <?php endif; ?>
                        <small class="form-hint">Alphabets, spaces, hyphens (-), and apostrophes (') only</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Rank</label>
                        <select name="auditor_rank" required
                                class="form-control <?php echo isset($errors['auditor_rank']) ? 'error' : ''; ?>">
                            <option value="">Select Rank</option>
                            <?php foreach (getNisRanks() as $rank): ?>
                                <option value="<?php echo htmlspecialchars($rank); ?>" <?php echo ($old['auditor_rank'] ?? '') === $rank ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rank); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['auditor_rank'])): ?>
                            <small class="error-text"><?php echo $errors['auditor_rank']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">NIS Number</label>
                        <input type="text" name="auditor_nis"
                               value="<?php echo Security::escape($old['auditor_nis'] ?? ''); ?>"
                               required maxlength="20" inputmode="numeric" pattern="[0-9]+" title="Numbers only"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               class="form-control <?php echo isset($errors['auditor_nis']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['auditor_nis'])): ?>
                            <small class="error-text"><?php echo $errors['auditor_nis']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Unit/Department</label>
                        <input type="text" name="unit" 
                               value="<?php echo Security::escape($old['unit'] ?? ''); ?>" 
                               required maxlength="100" 
                               class="form-control <?php echo isset($errors['unit']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['unit'])): ?>
                            <small class="error-text"><?php echo $errors['unit']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Weapons Audit Section -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-gun"></i> Weapons Audit</h3>
                    <div class="section-actions">
                        <button type="button" class="btn btn-sm btn-info" onclick="scanWeaponBarcode()">
                            <i class="fas fa-qrcode"></i> Scan Barcode
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="addWeaponAuditRow()">
                            <i class="fas fa-plus"></i> Add Weapon
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="audit-table" id="weaponsAuditTable">
                        <thead>
                            <tr>
                                <th>Weapon ID</th>
                                <th>Type</th>
                                <th>Make/Model</th>
                                <th>Serial Number</th>
                                <th>System Status</th>
                                <th>Physical Status</th>
                                <th>Variance</th>
                                <th>Condition</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="weaponsAuditBody">
                            <?php if (isset($old['weapon_id']) && is_array($old['weapon_id'])): ?>
                                <?php foreach ($old['weapon_id'] as $index => $weaponId): ?>
                                <tr class="audit-row weapon-audit-row">
                                    <td>
                                        <select name="weapon_id[]" class="form-control weapon-select" required onchange="loadWeaponDetails(this)">
                                            <option value="">Select Weapon</option>
                                            <?php foreach ($weapons as $weapon): ?>
                                            <option value="<?php echo $weapon['id']; ?>" 
                                                    <?php echo $weaponId == $weapon['id'] ? 'selected' : ''; ?>
                                                    data-type="<?php echo Security::escape($weapon['type_name']); ?>"
                                                    data-make="<?php echo Security::escape($weapon['make_model']); ?>"
                                                    data-serial="<?php echo Security::escape($weapon['serial_no']); ?>"
                                                    data-status="<?php echo Security::escape($weapon['condition']); ?>"
                                                    data-location="<?php echo Security::escape($weapon['current_location']); ?>">
                                                <?php echo Security::escape($weapon['weapon_id'] . ' - ' . $weapon['make_model']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="weapon-type"><?php echo Security::escape($old['weapon_type'][$index] ?? ''); ?></td>
                                    <td class="weapon-make"><?php echo Security::escape($old['make_model'][$index] ?? ''); ?></td>
                                    <td class="weapon-serial"><?php echo Security::escape($old['serial_number'][$index] ?? ''); ?></td>
                                    <td class="system-status"><?php echo Security::escape($old['system_status'][$index] ?? ''); ?></td>
                                    <td>
                                        <select name="physical_status[]" class="form-control" required onchange="calculateWeaponVariance(this)">
                                            <option value="">Select Status</option>
                                            <option value="Present" <?php echo ($old['physical_status'][$index] ?? '') == 'Present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="Missing" <?php echo ($old['physical_status'][$index] ?? '') == 'Missing' ? 'selected' : ''; ?>>Missing</option>
                                            <option value="Damaged" <?php echo ($old['physical_status'][$index] ?? '') == 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                                            <option value="Under Repair" <?php echo ($old['physical_status'][$index] ?? '') == 'Under Repair' ? 'selected' : ''; ?>>Under Repair</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="variance[]" class="form-control variance-display" 
                                               value="<?php echo Security::escape($old['variance'][$index] ?? ''); ?>" readonly>
                                        <input type="hidden" name="variance_value[]" class="variance-value" 
                                               value="<?php echo Security::escape($old['variance_value'][$index] ?? '0'); ?>">
                                    </td>
                                    <td>
                                        <select name="condition[]" class="form-control">
                                            <option value="">Select Condition</option>
                                            <option value="Serviceable" <?php echo ($old['condition'][$index] ?? '') == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                            <option value="Unserviceable" <?php echo ($old['condition'][$index] ?? '') == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                            <option value="Under Repair" <?php echo ($old['condition'][$index] ?? '') == 'Under Repair' ? 'selected' : ''; ?>>Under Repair</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="weapon_remarks[]" class="form-control" 
                                               value="<?php echo Security::escape($old['weapon_remarks'][$index] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-icon delete" onclick="removeAuditRow(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr class="audit-row weapon-audit-row">
                                <td>
                                    <select name="weapon_id[]" class="form-control weapon-select" required onchange="loadWeaponDetails(this)">
                                        <option value="">Select Weapon</option>
                                        <?php foreach ($weapons as $weapon): ?>
                                        <option value="<?php echo $weapon['id']; ?>" 
                                                data-type="<?php echo Security::escape($weapon['type_name']); ?>"
                                                data-make="<?php echo Security::escape($weapon['make_model']); ?>"
                                                data-serial="<?php echo Security::escape($weapon['serial_no']); ?>"
                                                data-status="<?php echo Security::escape($weapon['condition']); ?>"
                                                data-location="<?php echo Security::escape($weapon['current_location']); ?>">
                                            <?php echo Security::escape($weapon['weapon_id'] . ' - ' . $weapon['make_model']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="weapon-type"></td>
                                <td class="weapon-make"></td>
                                <td class="weapon-serial"></td>
                                <td class="system-status"></td>
                                <td>
                                    <select name="physical_status[]" class="form-control" required onchange="calculateWeaponVariance(this)">
                                        <option value="">Select Status</option>
                                        <option value="Present">Present</option>
                                        <option value="Missing">Missing</option>
                                        <option value="Damaged">Damaged</option>
                                        <option value="Under Repair">Under Repair</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="variance[]" class="form-control variance-display" readonly>
                                    <input type="hidden" name="variance_value[]" class="variance-value" value="0">
                                </td>
                                <td>
                                    <select name="condition[]" class="form-control">
                                        <option value="">Select Condition</option>
                                        <option value="Serviceable">Serviceable</option>
                                        <option value="Unserviceable">Unserviceable</option>
                                        <option value="Under Repair">Under Repair</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="weapon_remarks[]" class="form-control">
                                </td>
                                <td>
                                    <button type="button" class="btn-icon delete" onclick="removeAuditRow(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Ammunition Audit Section -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-bullseye"></i> Ammunition Audit</h3>
                    <button type="button" class="btn btn-sm btn-success" onclick="addAmmoAuditRow()">
                        <i class="fas fa-plus"></i> Add Ammunition
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="audit-table" id="ammoAuditTable">
                        <thead>
                            <tr>
                                <th>Ammunition</th>
                                <th>Type</th>
                                <th>Calibre</th>
                                <th>System Units</th>
                                <th>Physical Units</th>
                                <th>Variance</th>
                                <th>Condition</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="ammoAuditBody">
                            <?php if (isset($old['ammo_id']) && is_array($old['ammo_id'])): ?>
                                <?php foreach ($old['ammo_id'] as $index => $ammoId): ?>
                                <tr class="audit-row ammo-audit-row">
                                    <td>
                                        <select name="ammo_id[]" class="form-control ammo-select" required onchange="loadAmmoDetails(this)">
                                            <option value="">Select Ammunition</option>
                                            <?php foreach ($ammunition as $ammo): ?>
                                            <option value="<?php echo $ammo['id']; ?>" 
                                                    <?php echo $ammoId == $ammo['id'] ? 'selected' : ''; ?>
                                                    data-type="<?php echo Security::escape($ammo['ammo_type']); ?>"
                                                    data-calibre="<?php echo Security::escape($ammo['calibre']); ?>"
                                                    data-balance="<?php echo $ammo['balance']; ?>">
                                                <?php echo Security::escape($ammo['ammo_id'] . ' - ' . ($ammo['ammo_type'] ?? '') . ' (' . $ammo['calibre'] . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="ammo-type"><?php echo Security::escape($old['ammo_type'][$index] ?? ''); ?></td>
                                    <td class="ammo-calibre"><?php echo Security::escape($old['calibre'][$index] ?? ''); ?></td>
                                    <td class="system-units text-right"><?php echo Security::escape($old['system_units'][$index] ?? ''); ?></td>
                                    <td>
                                        <input type="number" name="physical_units[]" class="form-control physical-units" 
                                               required min="0" value="<?php echo Security::escape($old['physical_units'][$index] ?? ''); ?>"
                                               onchange="calculateAmmoVariance(this)">
                                    </td>
                                    <td>
                                        <input type="text" name="ammo_variance[]" class="form-control ammo-variance-display" 
                                               value="<?php echo Security::escape($old['ammo_variance'][$index] ?? ''); ?>" readonly>
                                        <input type="hidden" name="ammo_variance_value[]" class="ammo-variance-value" 
                                               value="<?php echo Security::escape($old['ammo_variance_value'][$index] ?? '0'); ?>">
                                    </td>
                                    <td>
                                        <select name="ammo_condition[]" class="form-control">
                                            <option value="">Select Condition</option>
                                            <option value="Serviceable" <?php echo ($old['ammo_condition'][$index] ?? '') == 'Serviceable' ? 'selected' : ''; ?>>Serviceable</option>
                                            <option value="Unserviceable" <?php echo ($old['ammo_condition'][$index] ?? '') == 'Unserviceable' ? 'selected' : ''; ?>>Unserviceable</option>
                                            <option value="Condemned" <?php echo ($old['ammo_condition'][$index] ?? '') == 'Condemned' ? 'selected' : ''; ?>>Condemned</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="ammo_remarks[]" class="form-control" 
                                               value="<?php echo Security::escape($old['ammo_remarks'][$index] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-icon delete" onclick="removeAuditRow(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr class="audit-row ammo-audit-row">
                                <td>
                                    <select name="ammo_id[]" class="form-control ammo-select" required onchange="loadAmmoDetails(this)">
                                        <option value="">Select Ammunition</option>
                                        <?php foreach ($ammunition as $ammo): ?>
                                        <option value="<?php echo $ammo['id']; ?>" 
                                                data-type="<?php echo Security::escape($ammo['ammo_type']); ?>"
                                                data-calibre="<?php echo Security::escape($ammo['calibre']); ?>"
                                                data-balance="<?php echo $ammo['balance']; ?>">
                                            <?php echo Security::escape($ammo['ammo_id'] . ' - ' . ($ammo['ammo_type'] ?? '') . ' (' . $ammo['calibre'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="ammo-type"></td>
                                <td class="ammo-calibre"></td>
                                <td class="system-units text-right"></td>
                                <td>
                                    <input type="number" name="physical_units[]" class="form-control physical-units" 
                                           required min="0" onchange="calculateAmmoVariance(this)">
                                </td>
                                <td>
                                    <input type="text" name="ammo_variance[]" class="form-control ammo-variance-display" readonly>
                                    <input type="hidden" name="ammo_variance_value[]" class="ammo-variance-value" value="0">
                                </td>
                                <td>
                                    <select name="ammo_condition[]" class="form-control">
                                        <option value="">Select Condition</option>
                                        <option value="Serviceable">Serviceable</option>
                                        <option value="Unserviceable">Unserviceable</option>
                                        <option value="Condemned">Condemned</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="ammo_remarks[]" class="form-control">
                                </td>
                                <td>
                                    <button type="button" class="btn-icon delete" onclick="removeAuditRow(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Missing Weapons Section -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-exclamation-triangle"></i> Missing Weapons</h3>
                    <button type="button" class="btn btn-sm btn-warning" onclick="addMissingWeaponRow()">
                        <i class="fas fa-plus"></i> Add Missing Weapon
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="audit-table" id="missingWeaponsTable">
                        <thead>
                            <tr>
                                <th>Arm Type</th>
                                <th>Serial Number</th>
                                <th>Last Known Location</th>
                                <th>Date Missing</th>
                                <th>Reported By</th>
                                <th>Investigation Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="missingWeaponsBody">
                            <?php if (isset($old['missing_arm_type']) && is_array($old['missing_arm_type'])): ?>
                                <?php foreach ($old['missing_arm_type'] as $index => $armType): ?>
                                <tr class="missing-row">
                                    <td>
                                        <input type="text" name="missing_arm_type[]" class="form-control" 
                                               value="<?php echo Security::escape($armType); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="missing_serial[]" class="form-control" 
                                               value="<?php echo Security::escape($old['missing_serial'][$index] ?? ''); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="missing_location[]" class="form-control" 
                                               value="<?php echo Security::escape($old['missing_location'][$index] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <input type="date" name="missing_date[]" class="form-control" 
                                               value="<?php echo Security::escape($old['missing_date'][$index] ?? ''); ?>"
                                               max="<?php echo date('Y-m-d'); ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="missing_reported_by[]" class="form-control" 
                                               value="<?php echo Security::escape($old['missing_reported_by'][$index] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <select name="missing_investigation_status[]" class="form-control">
                                            <option value="Reported" <?php echo ($old['missing_investigation_status'][$index] ?? '') == 'Reported' ? 'selected' : ''; ?>>Reported</option>
                                            <option value="Under Investigation" <?php echo ($old['missing_investigation_status'][$index] ?? '') == 'Under Investigation' ? 'selected' : ''; ?>>Under Investigation</option>
                                            <option value="Board of Inquiry" <?php echo ($old['missing_investigation_status'][$index] ?? '') == 'Board of Inquiry' ? 'selected' : ''; ?>>Board of Inquiry</option>
                                            <option value="Closed" <?php echo ($old['missing_investigation_status'][$index] ?? '') == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-icon delete" onclick="removeAuditRow(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Audit Summary -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-chart-pie"></i> Audit Summary</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Total Weapons Audited</label>
                        <input type="number" name="total_weapons_audited" id="total_weapons_audited" 
                               class="form-control" readonly value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Weapons with Variance</label>
                        <input type="number" name="weapons_with_variance" id="weapons_with_variance" 
                               class="form-control" readonly value="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Total Ammunition Audited</label>
                        <input type="number" name="total_ammunition_audited" id="total_ammunition_audited" 
                               class="form-control" readonly value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Ammunition with Variance</label>
                        <input type="number" name="ammunition_with_variance" id="ammunition_with_variance" 
                               class="form-control" readonly value="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Total Missing Weapons</label>
                        <input type="number" name="total_missing_weapons" id="total_missing_weapons" 
                               class="form-control" readonly value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Audit Conclusion</label>
                    <textarea name="audit_conclusion" rows="3" class="form-control"><?php echo Security::escape($old['audit_conclusion'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Recommending Officer</label>
                        <input type="text" name="recommending_officer" 
                               value="<?php echo Security::escape($old['recommending_officer'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Approving Officer</label>
                        <input type="text" name="approving_officer" 
                               value="<?php echo Security::escape($old['approving_officer'] ?? ''); ?>" 
                               class="form-control">
                    </div>
                </div>
            </div>
            
            <!-- Audit Remarks -->
            <div class="form-section-inner">
                <div class="section-title">
                    <h3><i class="fas fa-sticky-note"></i> Audit Remarks</h3>
                </div>
                
                <div class="form-group">
                    <textarea name="audit_remarks" rows="4" class="form-control"><?php echo Security::escape($old['audit_remarks'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" name="status" value="Submitted" class="btn btn-success submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Audit
                </button>
                <button type="submit" name="status" value="Draft" class="btn btn-secondary">
                    <i class="fas fa-save"></i> Save as Draft
                </button>
                <button type="button" class="btn btn-outline" onclick="resetForm('auditForm')">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
                <a href="<?php echo BASE_URL; ?>/audit/quarterly" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Barcode/QR Scan Modal -->
<div id="barcodeScanModal" class="scan-modal-overlay" style="display:none;">
    <div class="scan-modal">
        <div class="scan-modal-header">
            <h3><i class="fas fa-qrcode"></i> Scan Weapon Barcode</h3>
            <button type="button" class="scan-modal-close" onclick="closeBarcodeScanModal()">&times;</button>
        </div>
        <div id="qr-reader"></div>
        <p id="barcodeScanStatus" class="scan-status">Starting camera...</p>
        <div class="scan-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeBarcodeScanModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div>

<style>
.audit-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.audit-table th {
    background: var(--light-bg);
    padding: 10px 6px;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    border-bottom: 2px solid var(--border-color);
}

.audit-table td {
    padding: 6px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: top;
}

.audit-table select,
.audit-table input {
    width: 100%;
    padding: 4px 6px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 0.8rem;
}

.audit-table .btn-icon {
    width: 28px;
    height: 28px;
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

.section-actions {
    display: flex;
    gap: 10px;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
}

.variance-display.positive {
    color: var(--success-color);
    font-weight: 600;
}

.variance-display.negative {
    color: var(--danger-color);
    font-weight: 600;
}

@media (max-width: 768px) {
    .audit-table {
        font-size: 0.75rem;
    }
    
    .audit-table td {
        padding: 4px;
    }
    
    .section-actions {
        flex-direction: column;
    }
}

/* Barcode/QR Scan Modal */
.scan-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(19, 70, 23, 0.55);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
}

.scan-modal {
    background: var(--surface);
    border-radius: 10px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.scan-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #D7E3DC;
}

.scan-modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #134617;
}

.scan-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    color: #53665E;
    padding: 0;
}

.scan-modal-close:hover {
    color: #B42318;
}

#qr-reader {
    width: 100%;
    padding: 15px 20px 0;
}

#qr-reader video {
    border-radius: 6px;
}

.scan-status {
    text-align: center;
    padding: 10px 20px;
    margin: 0;
    color: #53665E;
    font-size: 0.9rem;
}

.scan-status.scan-error {
    color: #B42318;
    font-weight: 600;
}

.scan-modal-footer {
    padding: 12px 20px 20px;
    text-align: center;
}
</style>

<script src="<?php echo BASE_URL; ?>/assets/js/html5-qrcode.min.js"></script>
<script>
// Weapon audit row management
function addWeaponAuditRow() {
    const tbody = document.getElementById('weaponsAuditBody');
    const newRow = document.createElement('tr');
    newRow.className = 'audit-row weapon-audit-row';
    newRow.innerHTML = `
        <td>
            <select name="weapon_id[]" class="form-control weapon-select" required onchange="loadWeaponDetails(this)">
                <option value="">Select Weapon</option>
                <?php foreach ($weapons as $weapon): ?>
                <option value="<?php echo $weapon['id']; ?>" 
                        data-type="<?php echo Security::escape($weapon['type_name']); ?>"
                        data-make="<?php echo Security::escape($weapon['make_model']); ?>"
                        data-serial="<?php echo Security::escape($weapon['serial_no']); ?>"
                        data-status="<?php echo Security::escape($weapon['condition']); ?>"
                        data-location="<?php echo Security::escape($weapon['current_location']); ?>">
                    <?php echo Security::escape($weapon['weapon_id'] . ' - ' . $weapon['make_model']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="weapon-type"></td>
        <td class="weapon-make"></td>
        <td class="weapon-serial"></td>
        <td class="system-status"></td>
        <td>
            <select name="physical_status[]" class="form-control" required onchange="calculateWeaponVariance(this)">
                <option value="">Select Status</option>
                <option value="Present">Present</option>
                <option value="Missing">Missing</option>
                <option value="Damaged">Damaged</option>
                <option value="Under Repair">Under Repair</option>
            </select>
        </td>
        <td>
            <input type="text" name="variance[]" class="form-control variance-display" readonly>
            <input type="hidden" name="variance_value[]" class="variance-value" value="0">
        </td>
        <td>
            <select name="condition[]" class="form-control">
                <option value="">Select Condition</option>
                <option value="Serviceable">Serviceable</option>
                <option value="Unserviceable">Unserviceable</option>
                <option value="Under Repair">Under Repair</option>
            </select>
        </td>
        <td>
            <input type="text" name="weapon_remarks[]" class="form-control">
        </td>
        <td>
            <button type="button" class="btn-icon delete" onclick="removeAuditRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateAuditSummary();
}

// Ammunition audit row management
function addAmmoAuditRow() {
    const tbody = document.getElementById('ammoAuditBody');
    const newRow = document.createElement('tr');
    newRow.className = 'audit-row ammo-audit-row';
    newRow.innerHTML = `
        <td>
            <select name="ammo_id[]" class="form-control ammo-select" required onchange="loadAmmoDetails(this)">
                <option value="">Select Ammunition</option>
                <?php foreach ($ammunition as $ammo): ?>
                <option value="<?php echo $ammo['id']; ?>" 
                        data-type="<?php echo Security::escape($ammo['ammo_type']); ?>"
                        data-calibre="<?php echo Security::escape($ammo['calibre']); ?>"
                        data-balance="<?php echo $ammo['balance']; ?>">
                    <?php echo Security::escape($ammo['ammo_id'] . ' - ' . ($ammo['ammo_type'] ?? '') . ' (' . $ammo['calibre'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="ammo-type"></td>
        <td class="ammo-calibre"></td>
        <td class="system-units text-right"></td>
        <td>
            <input type="number" name="physical_units[]" class="form-control physical-units" 
                   required min="0" onchange="calculateAmmoVariance(this)">
        </td>
        <td>
            <input type="text" name="ammo_variance[]" class="form-control ammo-variance-display" readonly>
            <input type="hidden" name="ammo_variance_value[]" class="ammo-variance-value" value="0">
        </td>
        <td>
            <select name="ammo_condition[]" class="form-control">
                <option value="">Select Condition</option>
                <option value="Serviceable">Serviceable</option>
                <option value="Unserviceable">Unserviceable</option>
                <option value="Condemned">Condemned</option>
            </select>
        </td>
        <td>
            <input type="text" name="ammo_remarks[]" class="form-control">
        </td>
        <td>
            <button type="button" class="btn-icon delete" onclick="removeAuditRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateAuditSummary();
}

// Missing weapons row management
function addMissingWeaponRow() {
    const tbody = document.getElementById('missingWeaponsBody');
    const newRow = document.createElement('tr');
    newRow.className = 'missing-row';
    newRow.innerHTML = `
        <td>
            <input type="text" name="missing_arm_type[]" class="form-control" required placeholder="Weapon type">
        </td>
        <td>
            <input type="text" name="missing_serial[]" class="form-control" required placeholder="Serial number">
        </td>
        <td>
            <input type="text" name="missing_location[]" class="form-control" placeholder="Last known location">
        </td>
        <td>
            <input type="date" name="missing_date[]" class="form-control" max="<?php echo date('Y-m-d'); ?>">
        </td>
        <td>
            <input type="text" name="missing_reported_by[]" class="form-control" placeholder="Reporting officer">
        </td>
        <td>
            <select name="missing_investigation_status[]" class="form-control">
                <option value="Reported">Reported</option>
                <option value="Under Investigation">Under Investigation</option>
                <option value="Board of Inquiry">Board of Inquiry</option>
                <option value="Closed">Closed</option>
            </select>
        </td>
        <td>
            <button type="button" class="btn-icon delete" onclick="removeAuditRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateMissingWeaponsCount();
}

// Remove row
function removeAuditRow(button) {
    const row = button.closest('tr');
    row.remove();
    updateAuditSummary();
    updateMissingWeaponsCount();
}

// Load weapon details when selected
function loadWeaponDetails(select) {
    const row = select.closest('tr');
    const selectedOption = select.options[select.selectedIndex];
    
    const typeCell = row.querySelector('.weapon-type');
    const makeCell = row.querySelector('.weapon-make');
    const serialCell = row.querySelector('.weapon-serial');
    const statusCell = row.querySelector('.system-status');
    
    typeCell.textContent = selectedOption.getAttribute('data-type') || '';
    makeCell.textContent = selectedOption.getAttribute('data-make') || '';
    serialCell.textContent = selectedOption.getAttribute('data-serial') || '';
    statusCell.textContent = selectedOption.getAttribute('data-status') || '';
    
    // Reset physical status and variance
    const physicalSelect = row.querySelector('select[name="physical_status[]"]');
    const varianceDisplay = row.querySelector('.variance-display');
    const varianceValue = row.querySelector('.variance-value');
    
    if (physicalSelect) physicalSelect.value = '';
    if (varianceDisplay) varianceDisplay.value = '';
    if (varianceValue) varianceValue.value = '0';
}

// Load ammunition details when selected
function loadAmmoDetails(select) {
    const row = select.closest('tr');
    const selectedOption = select.options[select.selectedIndex];
    
    const typeCell = row.querySelector('.ammo-type');
    const calibreCell = row.querySelector('.ammo-calibre');
    const systemUnitsCell = row.querySelector('.system-units');
    
    typeCell.textContent = selectedOption.getAttribute('data-type') || '';
    calibreCell.textContent = selectedOption.getAttribute('data-calibre') || '';
    
    const balance = selectedOption.getAttribute('data-balance') || '0';
    systemUnitsCell.textContent = balance;
    
    // Reset physical units and variance
    const physicalUnits = row.querySelector('.physical-units');
    const varianceDisplay = row.querySelector('.ammo-variance-display');
    const varianceValue = row.querySelector('.ammo-variance-value');
    
    if (physicalUnits) {
        physicalUnits.value = '';
        physicalUnits.max = balance;
    }
    if (varianceDisplay) varianceDisplay.value = '';
    if (varianceValue) varianceValue.value = '0';
}

// Calculate weapon variance
function calculateWeaponVariance(select) {
    const row = select.closest('tr');
    const systemStatus = row.querySelector('.system-status').textContent;
    const physicalStatus = select.value;
    const varianceDisplay = row.querySelector('.variance-display');
    const varianceValue = row.querySelector('.variance-value');
    
    let variance = '';
    let varianceVal = 0;
    
    if (systemStatus && physicalStatus) {
        if (systemStatus === 'Serviceable' && physicalStatus === 'Present') {
            variance = 'OK';
            varianceVal = 0;
            varianceDisplay.classList.remove('positive', 'negative');
        } else if (physicalStatus === 'Missing') {
            variance = 'MISSING';
            varianceVal = -1;
            varianceDisplay.classList.add('negative');
            varianceDisplay.classList.remove('positive');
        } else if (physicalStatus === 'Damaged' || physicalStatus === 'Under Repair') {
            variance = 'VAR';
            varianceVal = 1;
            varianceDisplay.classList.add('positive');
            varianceDisplay.classList.remove('negative');
        } else {
            variance = '?';
            varianceVal = 0;
            varianceDisplay.classList.remove('positive', 'negative');
        }
    }
    
    varianceDisplay.value = variance;
    varianceValue.value = varianceVal;
    updateAuditSummary();
}

// Calculate ammunition variance
function calculateAmmoVariance(input) {
    const row = input.closest('tr');
    const systemUnits = parseInt(row.querySelector('.system-units').textContent) || 0;
    const physicalUnits = parseInt(input.value) || 0;
    const varianceDisplay = row.querySelector('.ammo-variance-display');
    const varianceValue = row.querySelector('.ammo-variance-value');
    
    const variance = physicalUnits - systemUnits;
    
    varianceDisplay.value = variance;
    varianceValue.value = variance;
    
    if (variance > 0) {
        varianceDisplay.classList.add('positive');
        varianceDisplay.classList.remove('negative');
    } else if (variance < 0) {
        varianceDisplay.classList.add('negative');
        varianceDisplay.classList.remove('positive');
    } else {
        varianceDisplay.classList.remove('positive', 'negative');
    }
    
    updateAuditSummary();
}

// Update audit summary counts
function updateAuditSummary() {
    // Weapons count
    const weaponRows = document.querySelectorAll('.weapon-audit-row');
    document.getElementById('total_weapons_audited').value = weaponRows.length;
    
    // Weapons with variance
    let weaponsWithVariance = 0;
    weaponRows.forEach(row => {
        const varianceValue = row.querySelector('.variance-value');
        if (varianceValue && parseInt(varianceValue.value) !== 0) {
            weaponsWithVariance++;
        }
    });
    document.getElementById('weapons_with_variance').value = weaponsWithVariance;
    
    // Ammunition count
    const ammoRows = document.querySelectorAll('.ammo-audit-row');
    document.getElementById('total_ammunition_audited').value = ammoRows.length;
    
    // Ammunition with variance
    let ammoWithVariance = 0;
    ammoRows.forEach(row => {
        const varianceValue = row.querySelector('.ammo-variance-value');
        if (varianceValue && parseInt(varianceValue.value) !== 0) {
            ammoWithVariance++;
        }
    });
    document.getElementById('ammunition_with_variance').value = ammoWithVariance;
}

// Update missing weapons count
function updateMissingWeaponsCount() {
    const missingRows = document.querySelectorAll('.missing-row');
    document.getElementById('total_missing_weapons').value = missingRows.length;
}

// Scan a weapon's barcode/QR label with the device camera and add it
// straight to the audit table — matches on the weapon_id/serial_no already
// embedded as data attributes on every option in the (shared) weapon
// dropdown, so no extra lookup request is needed.
let html5QrScanner = null;

function scanWeaponBarcode() {
    const modal = document.getElementById('barcodeScanModal');
    const status = document.getElementById('barcodeScanStatus');
    modal.style.display = 'flex';
    status.className = 'scan-status';
    status.textContent = 'Starting camera...';

    if (typeof Html5Qrcode === 'undefined') {
        status.textContent = 'Scanner failed to load. Check your connection and try again.';
        status.className = 'scan-status scan-error';
        return;
    }

    html5QrScanner = new Html5Qrcode('qr-reader');
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 150 },
        formatsToSupport: [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.ITF
        ]
    };

    Html5Qrcode.getCameras().then(function(cameras) {
        if (!cameras || !cameras.length) {
            status.textContent = 'No camera found on this device.';
            status.className = 'scan-status scan-error';
            return;
        }
        // Prefer the rear/environment camera on phones and tablets.
        const backCamera = cameras.find(c => /back|rear|environment/i.test(c.label));
        const cameraId = backCamera ? backCamera.id : cameras[cameras.length - 1].id;

        html5QrScanner.start(
            cameraId,
            config,
            onBarcodeScanSuccess,
            function() { /* per-frame "nothing decoded yet" — fires constantly while aiming, ignore */ }
        ).then(function() {
            status.textContent = "Point the camera at a weapon's barcode or QR label.";
        }).catch(function(err) {
            status.textContent = 'Could not start camera: ' + err;
            status.className = 'scan-status scan-error';
        });
    }).catch(function(err) {
        status.textContent = 'Camera access denied or unavailable: ' + err;
        status.className = 'scan-status scan-error';
    });
}

function closeBarcodeScanModal() {
    if (html5QrScanner) {
        const scanner = html5QrScanner;
        html5QrScanner = null;
        scanner.stop().then(function() { scanner.clear(); }).catch(function() {});
    }
    document.getElementById('barcodeScanModal').style.display = 'none';
}

function onBarcodeScanSuccess(decodedText) {
    // Stop the camera immediately so the same code isn't decoded again
    // while the modal is still closing.
    if (html5QrScanner) {
        const scanner = html5QrScanner;
        html5QrScanner = null;
        scanner.stop().then(function() { scanner.clear(); }).catch(function() {});
    }
    document.getElementById('barcodeScanModal').style.display = 'none';

    const code = decodedText.trim();

    // Every weapon-select dropdown on the page carries the same option list
    // (weapon_id, serial_no as data attributes) — search the first one.
    const templateSelect = document.querySelector('.weapon-select');
    let matchedOption = null;
    if (templateSelect) {
        for (const opt of templateSelect.options) {
            if (!opt.value) continue;
            const serial = (opt.getAttribute('data-serial') || '').trim();
            const label = (opt.textContent || '').trim(); // "WPN-100000 - Make Model"
            const weaponIdPart = label.split(' - ')[0].trim();
            if (serial === code || weaponIdPart === code || label === code) {
                matchedOption = opt;
                break;
            }
        }
    }

    if (!matchedOption) {
        alert('No weapon found matching scanned code: "' + code + '". You can add it manually below.');
        return;
    }

    // Don't add a duplicate row for a weapon that's already listed.
    const existingSelect = Array.from(document.querySelectorAll('#weaponsAuditBody .weapon-select'))
        .find(s => s.value === matchedOption.value);
    if (existingSelect) {
        existingSelect.closest('tr').scrollIntoView({ behavior: 'smooth', block: 'center' });
        alert('This weapon is already in the audit list.');
        return;
    }

    addWeaponAuditRow();
    const selects = document.querySelectorAll('#weaponsAuditBody .weapon-select');
    const newSelect = selects[selects.length - 1];
    newSelect.value = matchedOption.value;
    loadWeaponDetails(newSelect);
    newSelect.closest('tr').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Reset form
function resetForm(formId) {
    if (confirm('Are you sure you want to reset the form? All unsaved data will be lost.')) {
        document.getElementById(formId).reset();
        
        // Clear dynamic rows
        document.getElementById('weaponsAuditBody').innerHTML = '';
        document.getElementById('ammoAuditBody').innerHTML = '';
        document.getElementById('missingWeaponsBody').innerHTML = '';
        
        // Add one default row for each section
        addWeaponAuditRow();
        addAmmoAuditRow();
        
        showNotification('info', 'Form has been reset');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize summary update for existing rows
    updateAuditSummary();
    updateMissingWeaponsCount();
    
    // Set up mutation observer to watch for row changes
    const observer = new MutationObserver(function(mutations) {
        updateAuditSummary();
        updateMissingWeaponsCount();
    });
    
    observer.observe(document.getElementById('weaponsAuditBody'), { childList: true, subtree: true });
    observer.observe(document.getElementById('ammoAuditBody'), { childList: true, subtree: true });
    observer.observe(document.getElementById('missingWeaponsBody'), { childList: true, subtree: true });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>