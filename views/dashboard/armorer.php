<?php
$userRoles = $_SESSION['roles'] ?? [];
$isCommandArmorer = in_array('Command Armorer', $userRoles, true);
$isHQArmorer      = in_array('HQ Armorer', $userRoles, true);
$isStandardArmorer = in_array('Armorer', $userRoles, true);

if ($isCommandArmorer) {
    $titleSuffix = 'Command Armorer Dashboard';
    $scopeLabel  = 'Command Armoury';
    $scopeColor  = '#2196f3';
} elseif ($isHQArmorer) {
    $titleSuffix = 'HQ Armorer Dashboard';
    $scopeLabel  = 'Service-Wide (HQ) Armoury';
    $scopeColor  = '#207027';
} else {
    $titleSuffix = 'Armorer Dashboard';
    $scopeLabel  = 'Armoury & Issuance';
    $scopeColor  = '#207027';
}
$title = $titleSuffix;
$active = 'dashboard';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>


<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-gun" style="color: <?php echo $scopeColor; ?>;"></i>
                <?php echo htmlspecialchars($titleSuffix); ?>
            </h1>
            <p>
                Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>.
                <span style="display:inline-block; margin-left:8px; padding:2px 10px; border-radius:12px; background:<?php echo $scopeColor; ?>22; color:<?php echo $scopeColor; ?>; font-weight:600; font-size:0.82rem; border:1px solid <?php echo $scopeColor; ?>44;">
                    <?php echo $scopeLabel; ?>
                </span>
            </p>
        </div>
        <div class="header-actions">
            <!-- <a href="<?php echo BASE_URL; ?>/weapon_issue/create" class="btn btn-primary" style="background: linear-gradient(135deg, #207027 0%, #2196f3 100%); border: none;">
                <i class="fas fa-plus-circle"></i> Issue Weapon/Ammunition
            </a>
            <a href="<?php echo BASE_URL; ?>/returns/create" class="btn btn-secondary">
                <i class="fas fa-undo"></i> Log Return
            </a> -->
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <!-- Card 1 -->
        <div class="stat-card" style="background: var(--surface); border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; ">
            <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(32, 112, 39, 0.1); color: #207027; display: flex; justify-content: center; align-items: center; font-size: 1.5rem;">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <h3 style="font-size: 1.6rem; font-weight: 700; margin: 0 0 4px 0; color: var(--text-primary);"><?php echo number_format($weaponsStats['total']); ?></h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">Total Weapons</p>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="stat-card" style="background: var(--surface); border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; ">
            <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(41, 128, 185, 0.1); color: var(--info-color); display: flex; justify-content: center; align-items: center; font-size: 1.5rem;">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <h3 style="font-size: 1.6rem; font-weight: 700; margin: 0 0 4px 0; color: var(--text-primary);"><?php echo number_format($weaponsStats['available']); ?></h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">Available in Armoury</p>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="stat-card" style="background: var(--surface); border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; ">
            <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(183, 121, 31, 0.1); color: #B7791F; display: flex; justify-content: center; align-items: center; font-size: 1.5rem;">
                <i class="fas fa-external-link-alt"></i>
            </div>
            <div>
                <h3 style="font-size: 1.6rem; font-weight: 700; margin: 0 0 4px 0; color: var(--text-primary);"><?php echo number_format($weaponsStats['issued']); ?></h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">Issued Out</p>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="stat-card" style="background: var(--surface); border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; ">
            <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(180, 35, 24, 0.1); color: #B42318; display: flex; justify-content: center; align-items: center; font-size: 1.5rem;">
                <i class="fas fa-cubes"></i>
            </div>
            <div>
                <h3 style="font-size: 1.6rem; font-weight: 700; margin: 0 0 4px 0; color: var(--text-primary);"><?php echo number_format($ammoStats['avail_rounds']); ?></h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">Available Ammo Rounds</p>
            </div>
        </div>
    </div>

    <?php if (!empty($pendingVetting)): ?>
    <!-- Requisitions awaiting THIS HQ Armorer's own vetting decision — the
         "Pending Issuance Queue" below only ever shows Armorer_Issue-stage
         items (already fully approved); an HQ Armorer's actual next action
         (vet or reject a Command-Approval-cleared requisition) used to only
         be reachable via the Requisitions Queue page, not this dashboard. -->
    <div class="card" style="background: var(--surface); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color); margin-bottom: 24px;">
        <div class="card-header" style="padding: 15px 20px; border-bottom: 1px solid var(--border-color); background: linear-gradient(135deg, #B7791F 0%, #C69214 100%); color: white; border-top-left-radius: 12px; border-top-right-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1rem;"><i class="fas fa-magnifying-glass"></i> Requisitions Awaiting Your Vetting</h3>
            <span class="badge" style="background: rgba(255,255,255,0.25); color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem;"><?php echo count($pendingVetting); ?> Requisitions</span>
        </div>
        <div class="card-body" style="padding: 20px;">
            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left; font-size: 0.85rem; color: var(--text-secondary);">
                            <th style="padding: 10px;">Req Number</th>
                            <th style="padding: 10px;">Command</th>
                            <th style="padding: 10px;">Priority</th>
                            <th style="padding: 10px;">Raised By</th>
                            <th style="padding: 10px;">Command-Approved</th>
                            <th style="padding: 10px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingVetting as $req): ?>
                            <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                                <td style="padding: 12px 10px; font-weight: 600; color: var(--info-color);">
                                    <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $req['id']; ?>" style="color: var(--info-color); text-decoration: none;"><?php echo htmlspecialchars($req['requisition_number']); ?></a>
                                </td>
                                <td style="padding: 12px 10px;"><?php echo htmlspecialchars($req['command_name'] ?? ''); ?></td>
                                <td style="padding: 12px 10px;">
                                    <span class="badge" style="background: <?php echo $req['priority_level'] == 'Urgent' ? '#B42318' : ($req['priority_level'] == 'High' ? '#B7791F' : '#207027'); ?>; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;">
                                        <?php echo htmlspecialchars($req['priority_level']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 10px;"><?php echo htmlspecialchars($req['requester_name'] ?? ''); ?></td>
                                <td style="padding: 12px 10px; color: var(--text-secondary);"><?php echo !empty($req['command_approval_date']) ? date('M j, Y g:i A', strtotime($req['command_approval_date'])) : '-'; ?></td>
                                <td style="padding: 12px 10px; text-align: center;">
                                    <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $req['id']; ?>" class="btn btn-success" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 4px; background: #B7791F; border: none; text-decoration: none; display: inline-block; color: white;">
                                        <i class="fas fa-clipboard-check"></i> Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
        <!-- Left Column: Pending Approved Requisitions Queue -->
        <div class="card" style="background: var(--surface); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color);">
            <div class="card-header" style="padding: 15px 20px; border-bottom: 1px solid var(--border-color); background: linear-gradient(135deg, #134617 0%, #0F2F24 100%); color: white; border-top-left-radius: 12px; border-top-right-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1rem;"><i class="fas fa-clipboard-check"></i> Pending Issuance Queue</h3>
                <span class="badge" style="background: rgba(255,255,255,0.22); color: white; padding: 3px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.3);"><?php echo count($pendingIssues); ?> Requisition<?php echo count($pendingIssues) === 1 ? '' : 's'; ?></span>
            </div>
            <div class="card-body" style="padding: 20px;">
                <?php if (empty($pendingIssues)): ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                        <i class="fas fa-hourglass" style="font-size: 3rem; margin-bottom: 15px; color: #bdc3c7;"></i>
                        <h4>No Pending Requisitions Ready for Issue</h4>
                        <p style="font-size: 0.85rem; margin-top: 5px;">Approved requisitions will appear here automatically.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; font-size: 0.85rem; color: var(--text-secondary);">
                                    <th style="padding: 10px;">Req Number</th>
                                    <th style="padding: 10px;">Command</th>
                                    <th style="padding: 10px;">Type</th>
                                    <th style="padding: 10px;">Priority</th>
                                    <th style="padding: 10px;">Officer</th>
                                    <th style="padding: 10px;">Status</th>
                                    <th style="padding: 10px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingIssues as $issue): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                                        <td style="padding: 12px 10px; font-weight: 600;">
                                            <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $issue['id']; ?>" style="color: var(--info-color, #1f6f8b); text-decoration: none;">
                                                <?php echo htmlspecialchars($issue['requisition_number']); ?>
                                            </a>
                                        </td>
                                        <td style="padding: 12px 10px;"><?php echo htmlspecialchars($issue['command_name'] ?? 'HQ Armoury'); ?></td>
                                        <td style="padding: 12px 10px;">
                                            <span class="badge" style="background: var(--light-bg); color: var(--text-primary); border: 1px solid var(--border-color); padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($issue['requisition_type'] ?? 'Weapon'); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 10px;">
                                            <span class="badge" style="background: <?php echo ($issue['priority_level'] ?? '') == 'Urgent' ? '#B42318' : (($issue['priority_level'] ?? '') == 'High' ? '#B7791F' : '#207027'); ?>; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($issue['priority_level'] ?? 'Normal'); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 10px;"><?php echo htmlspecialchars($issue['requesting_officer_name'] ?? 'Officer'); ?></td>
                                        <td style="padding: 12px 10px;">
                                            <span class="badge" style="background: <?php echo ($issue['status'] ?? '') === 'Approved' ? '#207027' : '#1F6F8B'; ?>; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($issue['status'] ?? 'Approved'); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 10px; text-align: center;">
                                            <?php if (!empty($isHQArmorer) || !empty($isStandardArmorer) || in_array('HQ Armorer', $_SESSION['roles'] ?? [], true) || in_array('Armorer', $_SESSION['roles'] ?? [], true)): ?>

                                                <?php
                                                    $issueTypeParam = '';
                                                    if (($issue['remaining_weapons'] ?? 1) <= 0 && ($issue['remaining_ammo'] ?? 0) > 0) {
                                                        $issueTypeParam = '&type=ammunition';
                                                    }
                                                ?>
                                                <a href="<?php echo BASE_URL; ?>/weapon_issue/create?requisition_id=<?php echo $issue['id']; ?><?php echo $issueTypeParam; ?>" class="btn btn-success" style="padding: 5px 12px; font-size: 0.75rem; border-radius: 6px; background: #207027; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; color: white; font-weight: 600;">
                                                    <i class="fas fa-truck-ramp-box"></i> Issue Items
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $issue['id']; ?>" class="btn btn-outline-primary" style="padding: 5px 12px; font-size: 0.75rem; border-radius: 6px; border: 1px solid var(--border-color); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; color: var(--text-primary); font-weight: 600;">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Quick Quick actions & recent logs -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Inventory Quick Status -->
            <div class="card" style="background: var(--surface); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color); padding: 20px;">
                <h4 style="margin: 0 0 15px 0; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-toolbox" style="color: #207027;"></i> Quick Actions
                </h4>
                <div style="display: grid; grid-template-columns: 1fr; gap: 10px;">
                    <a href="<?php echo BASE_URL; ?>/weapons" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: var(--light-bg); border-radius: 8px; color: var(--text-primary); text-decoration: none; font-size: 0.85rem; font-weight: 600; hover: background: #f0f0f0;">
                        <span><i class="fas fa-gun" style="color: #207027; margin-right: 8px;"></i> Weapons Inventory</span>
                        <i class="fas fa-chevron-right" style="font-size: 0.75rem; color: #bdc3c7;"></i>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/ammunition" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: var(--light-bg); border-radius: 8px; color: var(--text-primary); text-decoration: none; font-size: 0.85rem; font-weight: 600; hover: background: #f0f0f0;">
                        <span><i class="fas fa-cubes" style="color: var(--info-color); margin-right: 8px;"></i> Ammunition Stock</span>
                        <i class="fas fa-chevron-right" style="font-size: 0.75rem; color: #bdc3c7;"></i>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/returns" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: var(--light-bg); border-radius: 8px; color: var(--text-primary); text-decoration: none; font-size: 0.85rem; font-weight: 600; hover: background: #f0f0f0;">
                        <span><i class="fas fa-rotate-left" style="color: #B7791F; margin-right: 8px;"></i> Returns Log</span>
                        <i class="fas fa-chevron-right" style="font-size: 0.75rem; color: #bdc3c7;"></i>
                    </a>
                </div>
            </div>

            <!-- Serviceability Status -->
            <div class="card" style="background: var(--surface); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color); padding: 20px;">
                <h4 style="margin: 0 0 15px 0; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-notes-medical" style="color: #B42318;"></i> Weapon Quality Status
                </h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.85rem;">
                    <span style="color: #207027;"><i class="fas fa-check-circle"></i> Serviceable:</span>
                    <strong style="font-weight: 600;"><?php echo $weaponsStats['serviceable']; ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                    <span style="color: #B42318;"><i class="fas fa-times-circle"></i> Unserviceable:</span>
                    <strong style="font-weight: 600;"><?php echo ($weaponsStats['total'] - $weaponsStats['serviceable']); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Issue Log -->
    <div class="card" style="background: var(--surface); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color); margin-bottom: 24px;">
        <div class="card-header" style="padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 0.95rem; color: var(--text-primary); font-weight: 600;"><i class="fas fa-history"></i> Recent Weapon Issue Log</h3>
            <a href="<?php echo BASE_URL; ?>/weapon_issue" style="font-size: 0.8rem; color: var(--info-color); text-decoration: none; font-weight: 600;">View All Issues</a>
        </div>
        <div class="card-body" style="padding: 20px;">
            <?php if (empty($recentIssuesLog)): ?>
                <div style="text-align: center; padding: 20px; color: var(--text-secondary); font-size: 0.85rem;">No weapon issuance logged recently.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color); text-align: left; font-size: 0.85rem; color: var(--text-secondary);">
                                <th style="padding: 10px;">S/N</th>
                                <th style="padding: 10px;">Weapon Serial</th>
                                <th style="padding: 10px;">Make/Model</th>
                                <th style="padding: 10px;">Issued To</th>
                                <th style="padding: 10px;">Issue Date</th>
                                <th style="padding: 10px;">Issuer</th>
                                <th style="padding: 10px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentIssuesLog as $idx => $log): ?>
                                <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                                    <td style="padding: 10px;"><?php echo $idx + 1; ?></td>
                                    <td style="padding: 10px; font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($log['serial_no'] ?? ''); ?></td>
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($log['make_model']); ?></td>
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($log['officer_name']); ?></td>
                                    <td style="padding: 10px;"><?php echo date('d/m/Y', strtotime($log['issue_date'])); ?></td>
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($log['issuer_name'] ?? 'System'); ?></td>
                                    <td style="padding: 10px;">
                                        <span class="badge" style="background: <?php echo $log['status'] == 'Issued' ? '#B7791F' : '#207027'; ?>; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;">
                                            <?php echo htmlspecialchars($log['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
