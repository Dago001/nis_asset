<?php
$title = 'Requisition Details';
$active = 'requisitions';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$itemsList = $items ?? [];
$totalItems = count($itemsList);
$itemPages = array_chunk($itemsList, 30);
$totalItemPages = count($itemPages);

$showApprovalButtons = false;
$showFulfillButton = false;
$userRoles = $_SESSION['roles'] ?? [];

// In accordance with workflow rules: Admin/SuperAdmin cannot approve weapon and ammunition.
// Only Command Approval Officer of that command (Stage 2) or HQ Armorer (Stage 3) can approve.
if ($requisition['status'] == 'Pending') {
    $stage = $requisition['approval_stage'] ?? 'Command_Approval';
    if ($stage === 'Command_Approval') {
        if (in_array('Command Approval Officer', $userRoles) && Auth::commandId() == $requisition['requesting_command_id']) {
            $showApprovalButtons = true;
        }
    } elseif ($stage === 'HQ_Vetting') {
        if (in_array('HQ Armorer', $userRoles)) {
            $showApprovalButtons = true;
        }
    }
} elseif (($requisition['status'] == 'Approved' || $requisition['status'] == 'Partially Issued') && ($requisition['approval_stage'] ?? '') === 'Armorer_Issue') {
    if (in_array('Armorer', $userRoles) || in_array('HQ Armorer', $userRoles)) {
        $showFulfillButton = true;
    }
}
?>

<div class="container-fluid req-show-container">
    <!-- Page Header & Action Bar -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/requisition"><i class="fas fa-file-signature"></i> Requisitions</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Requisition Details</span>
            </div>
            <h1 class="page-title">
                Requisition Request
                <span class="header-badge-code" title="Click to copy Requisition #" onclick="copyToClipboard('<?php echo Security::escape($requisition['requisition_number']); ?>', 'Requisition #')">
                    <?php echo Security::escape($requisition['requisition_number']); ?> <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/requisition" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
            <?php if ($requisition['status'] == 'Pending' && Auth::can('requisition.edit')): ?>
            <a href="<?php echo BASE_URL; ?>/requisition/edit/<?php echo $requisition['id']; ?>" class="pro-btn pro-btn-primary">
                <i class="fas fa-pen-to-square"></i> <span>Edit Request</span>
            </a>
            <?php endif; ?>
            
            <?php if ($showApprovalButtons): ?>
            <button type="button" class="pro-btn pro-btn-primary" onclick="approveRequisition()">
                <i class="fas fa-check-circle"></i> <span><?php echo ($requisition['approval_stage'] ?? '') == 'HQ_Vetting' ? 'Approve & Send to Armorer' : 'Approve & Forward'; ?></span>
            </button>
            <button type="button" class="pro-btn pro-btn-danger" onclick="rejectRequisition()">
                <i class="fas fa-circle-xmark"></i> <span>Reject</span>
            </button>
            <?php endif; ?>

            <?php if ($showFulfillButton): ?>
            <a href="<?php echo BASE_URL; ?>/weapon_issue?requisition_id=<?php echo $requisition['id']; ?>" class="pro-btn pro-btn-primary" style="background: #207027;">
                <i class="fas fa-dolly"></i> <span>Fulfill / Issue Items</span>
            </a>
            <?php endif; ?>

            <button type="button" class="pro-btn pro-btn-outline" onclick="window.print()">
                <i class="fas fa-print"></i> <span>Print</span>
            </button>
        </div>
    </div>

    <!-- Workflow Visual Progress Bar -->
    <div class="pro-card progress-card" style="margin-bottom: 24px;">
        <div class="pro-card-body" style="padding: 24px 20px;">
            <div class="workflow-progress">
                <?php
                // Workflow: Command Armorer creates -> Command Approval
                // Officer approves -> HQ Armorer approves/rejects -> Armorer
                // issues. The separate "HQ Approval"/HQ_Supervisor stage
                // this stepper used to show between HQ Vetting and
                // Fulfillment no longer exists in RequisitionController::
                // approve() — HQ Armorer's decision at HQ_Vetting now goes
                // straight to Armorer_Issue, so a requisition can never
                // actually reach it.
                $stages = [
                    'Command_Entry' => ['label' => 'Command Armorer', 'icon' => 'fa-user-shield'],
                    'Command_Approval' => ['label' => 'Command Approval Officer', 'icon' => 'fa-building'],
                    'HQ_Vetting' => ['label' => 'HQ Armorer', 'icon' => 'fa-shield'],
                    'Armorer_Issue' => ['label' => 'Fulfillment', 'icon' => 'fa-dolly'],
                    'Completed' => ['label' => 'Completed', 'icon' => 'fa-circle-check']
                ];

                $stageKeys = array_keys($stages);
                $currentStage = $requisition['approval_stage'] ?? 'Command_Entry';
                $isRejected = ($requisition['status'] == 'Rejected');
                $currentIndex = array_search($currentStage, $stageKeys);
                if ($requisition['status'] == 'Issued' || $requisition['status'] == 'Completed') {
                    $currentIndex = count($stages) - 1;
                }
                ?>
                <div class="progress-track-wrapper" style="position: relative; display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                    <div class="progress-line-bg" style="position: absolute; top: 20px; left: 6%; right: 6%; height: 4px; background: #e0e0e0; z-index: 1;"></div>
                    <div class="progress-line-active" style="position: absolute; top: 20px; left: 6%; width: <?php echo ($currentIndex / (count($stages) - 1)) * 88; ?>%; height: 4px; background: <?php echo $isRejected ? '#B42318' : '#207027'; ?>; z-index: 2; transition: width 0.4s ease;"></div>
                    
                    <?php foreach ($stageKeys as $index => $key): ?>
                        <?php
                        $stage = $stages[$key];
                        $iconBg = '#fff'; $iconColor = '#999'; $iconBorder = '2px solid #e0e0e0';
                        if ($index < $currentIndex) { $iconBg = '#207027'; $iconColor = '#fff'; $iconBorder = '2px solid #207027'; }
                        elseif ($index === $currentIndex) {
                            $iconBg = $isRejected ? '#B42318' : '#207027';
                            $iconColor = '#fff';
                            $iconBorder = $isRejected ? '2px solid #B42318' : '2px solid #207027';
                        }
                        ?>
                        <div class="progress-step" style="z-index: 3; position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; max-width: 20%; padding: 0 6px; text-align: center;">
                            <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $iconBg; ?>; color: <?php echo $iconColor; ?>; border: <?php echo $iconBorder; ?>; display: flex; align-items: center; justify-content: center; font-size: 1rem; margin-bottom: 8px;">
                                <i class="fas <?php echo $stage['icon']; ?>"></i>
                            </div>
                            <span class="step-label" style="font-size: 0.8rem; font-weight: 600; line-height: 1.25; color: <?php echo $index <= $currentIndex ? '#0F172A' : '#94A3B8'; ?>;"><?php echo $stage['label']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Metrics Bar -->
    <!-- KPI Summary Metrics Bar -->
    <div class="kpi-metrics-grid">
        <div class="kpi-card">
            <div class="kpi-icon icon-serial"><i class="fas fa-file-invoice"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Requisition Ref</span>
                <span class="kpi-value text-mono font-medium"><?php echo Security::escape($requisition['requisition_number']); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-shield"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Status State</span>
                <span class="kpi-value">
                    <?php 
                    $reqSt = $requisition['status'];
                    $reqB = 'badge-info';
                    if ($reqSt == 'Pending') $reqB = 'badge-warning';
                    elseif ($reqSt == 'Approved' || $reqSt == 'Completed' || $reqSt == 'Issued') $reqB = 'badge-success';
                    elseif ($reqSt == 'Rejected') $reqB = 'badge-danger';
                    ?>
                    <span class="custom-badge <?php echo $reqB; ?>"><?php echo Security::escape($reqSt); ?></span>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-building"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Requesting Command</span>
                <span class="kpi-value font-medium">
                    <?php echo Security::escape($requisition['command_name'] ?? 'State Command'); ?>
                    <?php if (!empty($requisition['zone_name'])): ?>
                        <small class="text-muted" style="font-size:0.75rem; display:block;"><?php echo Security::escape($requisition['zone_name']); ?></small>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-flag"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Priority Level</span>
                <span class="kpi-value font-medium">
                    <?php 
                    $prio = $requisition['priority_level'] ?? $requisition['priority'] ?? 'Medium';
                    $prioB = 'badge-neutral';
                    if ($prio == 'Urgent') $prioB = 'badge-danger';
                    elseif ($prio == 'High') $prioB = 'badge-warning';
                    elseif ($prio == 'Low') $prioB = 'badge-info';
                    ?>
                    <span class="custom-badge <?php echo $prioB; ?>"><?php echo Security::escape($prio); ?></span>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content Layout (2 Columns) -->
    <div class="show-layout-grid">
        <!-- Main Column (Left 70%) -->
        <div class="show-main-column">
            <!-- Requested Items List -->
            <div class="pro-card">
                <div class="pro-card-header flex-between">
                    <h3><i class="fas fa-list-check"></i> Requested Items List</h3>
                    <span class="history-count-badge"><?php echo $totalItems; ?> Line Items</span>
                </div>
                <div class="pro-card-body pad-none">
                    <?php if (empty($itemsList)): ?>
                        <div class="pro-empty-state">
                            <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                            <p>No item lines requested for this requisition</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="pro-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th style="width: 110px;">Category</th>
                                        <th>Item Description / Type</th>
                                        <th>Calibre / Specification</th>
                                        <th>Purpose</th>
                                        <th class="text-right" style="width: 90px;">Qty Req.</th>
                                        <th class="text-right" style="width: 90px;">Qty Appr.</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $itemIndex = 1;
                                    foreach ($itemsList as $item): 
                                        $cat = $item['item_type'] ?? 'Asset';
                                        $catBadge = 'badge-neutral';
                                        if ($cat === 'Weapon') $catBadge = 'badge-cat-weapon';
                                        elseif ($cat === 'Ammunition') $catBadge = 'badge-cat-ammo';
                                        elseif ($cat === 'Non-Lethal') $catBadge = 'badge-cat-nonlethal';
                                        
                                        $calName = !empty($item['calibre_name']) && $item['calibre_name'] !== '-' 
                                            ? $item['calibre_name'] 
                                            : (!empty($item['calibre_other']) ? $item['calibre_other'] : '-');
                                            
                                        $descName = !empty($item['item_display_name']) 
                                            ? $item['item_display_name'] 
                                            : (!empty($item['weapon_type_name']) ? $item['weapon_type_name'] : (!empty($item['ammo_type_name']) ? $item['ammo_type_name'] : $cat));
                                    ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo $itemIndex; ?></td>
                                        <td>
                                            <span class="category-pill <?php echo $catBadge; ?>">
                                                <?php echo Security::escape($cat); ?>
                                            </span>
                                        </td>
                                        <td class="font-semibold">
                                            <?php echo Security::escape($descName); ?>
                                        </td>
                                        <td>
                                            <?php if ($calName !== '-'): ?>
                                                <span class="calibre-pill"><?php echo Security::escape($calName); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="purpose-pill"><?php echo Security::escape($item['purpose'] ?? $item['purpose_other'] ?? 'Operational'); ?></span>
                                        </td>
                                        <td class="text-right font-bold" style="font-size: 0.95rem;"><?php echo number_format($item['quantity'] ?? 0); ?></td>
                                        <td class="text-right font-bold text-success" style="font-size: 0.95rem;">
                                            <?php echo ($requisition['status'] === 'Approved' || $requisition['status'] === 'Completed' || $requisition['status'] === 'Issued') ? number_format($item['quantity'] ?? 0) : '<span class="text-muted small">Pending</span>'; ?>
                                        </td>
                                        <td class="small text-muted"><?php echo Security::escape($item['remarks'] ?? '-'); ?></td>
                                    </tr>
                                    <?php 
                                    $itemIndex++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Justification Card -->
            <?php if (!empty($requisition['justification'])): ?>
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-comment-dots"></i> Request Justification & Operational Purpose</h3>
                </div>
                <div class="pro-card-body">
                    <div class="remarks-box">
                        <?php echo nl2br(Security::escape($requisition['justification'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($requisition['rejection_reason'])): ?>
            <div class="pro-card" style="border-left: 4px solid #DC2626;">
                <div class="pro-card-header" style="background: #FEF2F2;">
                    <h3 style="color: #991B1B;"><i class="fas fa-circle-exclamation"></i> Rejection Reason</h3>
                </div>
                <div class="pro-card-body">
                    <p style="color: #991B1B; font-weight: 500; margin: 0;"><?php echo nl2br(Security::escape($requisition['rejection_reason'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Operational Approval & Verification Trail -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-timeline"></i> Operational Approval & Verification Trail</h3>
                </div>
                <div class="pro-card-body">
                    <div class="decision-timeline">
                        <!-- Stage 1: Submission -->
                        <div class="decision-item">
                            <div class="decision-icon bg-step-done"><i class="fas fa-paper-plane"></i></div>
                            <div class="decision-content">
                                <div class="decision-header">
                                    <strong>1. Requisition Entry & Submission (Command Armorer)</strong>
                                    <span class="decision-date"><?php echo date('d M Y, h:i A', strtotime($requisition['created_at'])); ?></span>
                                </div>
                                <p class="decision-actor">Submitted by: <strong><?php echo Security::escape($requisition['created_by_name'] ?? $requisition['requesting_officer_name']); ?></strong> (<?php echo Security::escape($requisition['requesting_rank'] ?? 'Officer'); ?>)</p>
                            </div>
                        </div>

                        <!-- Stage 2: Command Approval -->
                        <div class="decision-item">
                            <div class="decision-icon <?php echo !empty($requisition['command_approved_by']) ? 'bg-step-done' : (($requisition['approval_stage'] ?? '') === 'Command_Approval' ? 'bg-step-active' : 'bg-step-pending'); ?>">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="decision-content">
                                <div class="decision-header">
                                    <strong>2. Command Approval (Command Approval Officer)</strong>
                                    <?php if (!empty($requisition['command_approval_date'])): ?>
                                        <span class="decision-date"><?php echo date('d M Y, h:i A', strtotime($requisition['command_approval_date'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($requisition['command_approved_by_name'])): ?>
                                    <p class="decision-actor">Approved by: <strong><?php echo Security::escape($requisition['command_approved_by_name']); ?></strong></p>
                                    <?php if (!empty($requisition['command_approval_remarks'])): ?>
                                        <div class="decision-remarks">"<?php echo Security::escape($requisition['command_approval_remarks']); ?>"</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="decision-actor text-muted"><?php echo (($requisition['approval_stage'] ?? '') === 'Command_Approval' && $requisition['status'] === 'Pending') ? 'Awaiting Command Approval Officer decision' : 'Pending command level approval'; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Stage 3: HQ Vetting -->
                        <div class="decision-item">
                            <div class="decision-icon <?php echo !empty($requisition['hq_vetted_by']) ? 'bg-step-done' : (($requisition['approval_stage'] ?? '') === 'HQ_Vetting' ? 'bg-step-active' : 'bg-step-pending'); ?>">
                                <i class="fas fa-shield"></i>
                            </div>
                            <div class="decision-content">
                                <div class="decision-header">
                                    <strong>3. HQ Stock Vetting & Authorization (HQ Armorer)</strong>
                                    <?php if (!empty($requisition['hq_vetting_date'])): ?>
                                        <span class="decision-date"><?php echo date('d M Y, h:i A', strtotime($requisition['hq_vetting_date'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($requisition['hq_vetted_by_name'])): ?>
                                    <p class="decision-actor">Vetted & Authorized by: <strong><?php echo Security::escape($requisition['hq_vetted_by_name']); ?></strong> (HQ Armorer)</p>
                                    <?php if (!empty($requisition['hq_vetting_remarks'])): ?>
                                        <div class="decision-remarks">"<?php echo Security::escape($requisition['hq_vetting_remarks']); ?>"</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="decision-actor text-muted"><?php echo (($requisition['approval_stage'] ?? '') === 'HQ_Vetting' && $requisition['status'] === 'Pending') ? 'Awaiting HQ Armorer vetting & authorization' : 'Pending prior stages'; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Stage 4: Fulfillment -->
                        <div class="decision-item">
                            <div class="decision-icon <?php echo !empty($requisition['issued_by']) ? 'bg-step-done' : (($requisition['approval_stage'] ?? '') === 'Armorer_Issue' ? 'bg-step-active' : 'bg-step-pending'); ?>">
                                <i class="fas fa-dolly"></i>
                            </div>
                            <div class="decision-content">
                                <div class="decision-header">
                                    <strong>4. Fulfillment & Handover (Armorer)</strong>
                                    <?php if (!empty($requisition['issue_date'])): ?>
                                        <span class="decision-date"><?php echo date('d M Y, h:i A', strtotime($requisition['issue_date'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($requisition['issued_by_name'])): ?>
                                    <p class="decision-actor">Issued & Fulfilled by: <strong><?php echo Security::escape($requisition['issued_by_name']); ?></strong> (Armorer)</p>
                                <?php else: ?>
                                    <p class="decision-actor text-muted"><?php echo (($requisition['approval_stage'] ?? '') === 'Armorer_Issue') ? 'Ready for armorer issuance & inventory transfer' : 'Awaiting HQ authorization'; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column (Right 30%) -->
        <div class="show-sidebar-column">
            <!-- Request Specs Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sliders"></i> Requisition Info</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Requisition Date</span>
                        <span class="side-value text-mono"><?php echo date('d/m/Y', strtotime($requisition['requisition_date'])); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Request Type</span>
                        <span class="side-value font-medium"><?php echo Security::escape($requisition['requisition_type'] ?? 'Standard'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Expected Return</span>
                        <span class="side-value font-medium">
                            <?php echo !empty($requisition['expected_return_date']) ? date('d/m/Y', strtotime($requisition['expected_return_date'])) : '<span class="text-muted">Indefinite / Deployment</span>'; ?>
                        </span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Requesting Command</span>
                        <span class="side-value font-semibold"><?php echo Security::escape($requisition['command_name'] ?? 'State Command'); ?></span>
                    </div>
                    <?php if (!empty($requisition['zone_name'])): ?>
                    <div class="pro-side-item">
                        <span class="side-label">Zonal Division</span>
                        <span class="side-value"><?php echo Security::escape($requisition['zone_name']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Officer Info Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-user-tie"></i> Requesting Officer</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Officer Name</span>
                        <span class="side-value font-bold"><?php echo Security::escape($requisition['requesting_officer_name'] ?? 'Officer'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Rank</span>
                        <span class="side-value"><?php echo Security::escape($requisition['requesting_rank'] ?? '-'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Service (NIS) No</span>
                        <span class="side-value text-mono"><?php echo Security::escape($requisition['requesting_nis'] ?? '-'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Phone Number</span>
                        <span class="side-value"><?php echo Security::escape($requisition['requesting_phone'] ?? '-'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Record Metadata Card -->
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Record Metadata</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Logged By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($requisition['created_by_name'] ?? 'System Admin'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Created Timestamp</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i:s', strtotime($requisition['created_at'])); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Last Modified</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i:s', strtotime($requisition['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Actions Bar -->
    <div class="pro-bottom-actions">
        <?php if ($requisition['status'] == 'Pending' && Auth::can('requisition.edit')): ?>
        <a href="<?php echo BASE_URL; ?>/requisition/edit/<?php echo $requisition['id']; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-pen-to-square"></i> <span>Edit Requisition</span>
        </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/requisition" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back to Requisitions</span>
        </a>
    </div>
</div>

<div id="copyToast" class="copy-toast"></div>

<!-- CSS Styling & Mobile Responsiveness -->
<style>
:root {
    --nis-forest: #134617;
    --nis-emerald: #2E7D32;
    --card-bg: #FFFFFF;
    --border-light: #E2E8F0;
    --text-dark: #0F172A;
    --text-muted: #64748B;
}
[data-theme="dark"] {
    --nis-forest: #299631;
    --nis-emerald: #52bf57;
    --card-bg: #1f1f1f;
    --border-light: #2b323b;
    --text-dark: #d9dde8;
    --text-muted: #dee0e3;
}


.req-show-container { padding-bottom: 40px; }

.req-show-container .page-header {
    display: flex !important; justify-content: space-between !important; align-items: center !important;
    flex-wrap: wrap !important; gap: 16px !important; background: #ffffff !important; padding: 20px 24px !important;
    border-radius: 12px !important; border: 1px solid #E2E8F0 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.02) !important; margin-bottom: 24px !important;
}

.req-show-container .header-content { flex: 1 1 280px !important; min-width: 0 !important; }
.req-show-container .header-content h1 { font-size: 1.5rem !important; font-weight: 700 !important; color: #0F172A !important; margin: 4px 0 0 0 !important; display: flex !important; align-items: center !important; flex-wrap: wrap !important; gap: 10px !important; }

.header-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px; }
.header-breadcrumb a { color: var(--nis-emerald); text-decoration: none; font-weight: 500; }
.breadcrumb-separator { font-size: 0.7rem; color: #94A3B8; }

.header-badge-code { display: inline-flex; align-items: center; gap: 6px; background: #F1F5F9; color: var(--nis-forest); border: 1px solid #CBD5E1; font-family: 'SF Mono', monospace; font-size: 0.95rem; padding: 3px 10px; border-radius: 6px; cursor: pointer; }

.pro-btn {
    display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important;
    padding: 9px 18px !important; font-size: 0.88rem !important; font-weight: 600 !important; border-radius: 8px !important;
    white-space: nowrap !important; height: 40px !important; box-sizing: border-box !important; text-decoration: none !important;
    line-height: 1 !important; cursor: pointer !important; user-select: none !important; outline: none !important; border: 1px solid transparent !important;
}

.pro-btn span { display: inline-block !important; color: inherit !important; background: transparent !important; }
.pro-btn i { font-size: 0.95rem !important; color: inherit !important; }

.pro-btn-secondary { background: #F1F5F9 !important; color: #334155 !important; border-color: #CBD5E1 !important; }
.pro-btn-primary { background: #134617 !important; color: #FFFFFF !important; }
.pro-btn-outline { background: #FFFFFF !important; color: #0F172A !important; border-color: #94A3B8 !important; }
.pro-btn-danger { background: #DC2626 !important; color: #FFFFFF !important; }

.req-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; flex-wrap: nowrap !important; }

.kpi-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
.kpi-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
.icon-serial { background: #E0F2FE; color: #0284C7; }
.icon-condition { background: #DCFCE7; color: #16A34A; }
.icon-location { background: #FEF3C7; color: #D97706; }
.icon-calibre { background: #F3E8FF; color: #9333EA; }

.kpi-details { display: flex; flex-direction: column; gap: 2px; }
.kpi-label { font-size: 0.78rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted); }
.kpi-value { font-size: 1rem; color: var(--text-dark); }

.custom-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; }
.badge-success { background: #DEF7EC; color: #03543F; }
.badge-warning { background: #FEF08A; color: #713F12; }
.badge-info    { background: #E1EFFE; color: #1E429F; }
.badge-danger  { background: #FDE8E8; color: #9B1C1C; }
.badge-neutral { background: #F1F5F9; color: #334155; }

/* Item Category & Calibre Pills */
.category-pill { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
.badge-cat-weapon { background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; }
.badge-cat-ammo { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
.badge-cat-nonlethal { background: #E0E7FF; color: #4338CA; border: 1px solid #C7D2FE; }

.calibre-pill { display: inline-flex; align-items: center; background: #F5F3FF; color: #6D28D9; border: 1px solid #DDD6FE; padding: 2px 8px; border-radius: 6px; font-size: 0.82rem; font-weight: 600; }
.purpose-pill { display: inline-flex; align-items: center; background: #F8FAFC; color: #475569; border: 1px solid #E2E8F0; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem; }

/* Decision Timeline */
.decision-timeline { display: flex; flex-direction: column; gap: 18px; position: relative; }
.decision-timeline::before { content: ''; position: absolute; top: 10px; bottom: 10px; left: 18px; width: 2px; background: #E2E8F0; z-index: 1; }
.decision-item { display: flex; gap: 16px; align-items: flex-start; position: relative; z-index: 2; }
.decision-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; box-shadow: 0 0 0 4px #FFFFFF; }
.bg-step-done { background: #134617; color: #FFFFFF; }
.bg-step-active { background: #F59E0B; color: #FFFFFF; }
.bg-step-pending { background: #E2E8F0; color: #94A3B8; }
.decision-content { flex: 1; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 16px; }
.decision-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 4px; }
.decision-header strong { font-size: 0.92rem; color: #0F172A; }
.decision-date { font-size: 0.78rem; color: #64748B; font-weight: 500; }
.decision-actor { margin: 0; font-size: 0.85rem; color: #334155; }
.decision-remarks { margin-top: 6px; padding: 6px 10px; background: #FFFFFF; border-left: 3px solid #134617; border-radius: 4px; font-size: 0.82rem; font-style: italic; color: #475569; }

.show-layout-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 24px; margin-bottom: 24px; }
.pro-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); margin-bottom: 24px; overflow: hidden; }
.pro-card-header { padding: 16px 20px; background: #F8FAFC; border-bottom: 1px solid var(--border-light); }
.pro-card-header h3 { margin: 0; font-size: 1.05rem; font-weight: 600; color: var(--nis-forest); display: flex; align-items: center; gap: 10px; }
.pro-card-body { padding: 20px; }
.pro-card-body.pad-none { padding: 0; }

.pro-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px 24px; }
.pro-detail-item { display: flex; flex-direction: column; gap: 4px; }
.item-label { font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
.item-value { font-size: 0.95rem; color: var(--text-dark); }

.pro-side-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F1F5F9; }
.pro-side-item:last-child { border-bottom: none; }
.side-label { font-size: 0.85rem; color: var(--text-muted); }
.side-value { font-size: 0.9rem; color: var(--text-dark); text-align: right; }

.pro-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.pro-table th { background: #F8FAFC; padding: 12px 16px; text-align: left; font-weight: 600; color: var(--text-muted); border-bottom: 1px solid var(--border-light); }
.pro-table td { padding: 12px 16px; border-bottom: 1px solid #F1F5F9; color: var(--text-dark); vertical-align: middle; }

.remarks-box { background: #F8FAFC; border-left: 4px solid var(--nis-emerald); padding: 14px 18px; border-radius: 0 8px 8px 0; font-size: 0.92rem; line-height: 1.6; }
.text-mono { font-family: 'SF Mono', monospace; }
.bold { font-weight: 700; }
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
.font-bold { font-weight: 700; }
.flex-between { display: flex; justify-content: space-between; align-items: center; }
.history-count-badge { font-size: 0.75rem; background: #E2E8F0; color: #475569; padding: 3px 10px; border-radius: 12px; font-weight: 600; }
.pro-empty-state { text-align: center; padding: 30px; color: var(--text-muted); }
.empty-icon { font-size: 2.2rem; margin-bottom: 8px; opacity: 0.6; }
.pro-bottom-actions { display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-light); flex-wrap: wrap; }

.copy-toast { position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: white; padding: 10px 18px; border-radius: 8px; font-size: 0.88rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); opacity: 0; transform: translateY(10px); transition: all 0.3s ease; pointer-events: none; z-index: 9999; }
.copy-toast.show { opacity: 1; transform: translateY(0); }

@media print {
    .no-print, .header-actions, .pro-bottom-actions, .sidebar, footer, .progress-card { display: none !important; }
    tr.print-page-break { break-after: page !important; page-break-after: always !important; }
    .page-header { border: none !important; box-shadow: none !important; padding: 0 !important; margin-bottom: 15px !important; }
    .pro-card { box-shadow: none !important; border: 1px solid #CBD5E1 !important; break-inside: avoid; }
}

@media (max-width: 1024px) { .show-layout-grid { grid-template-columns: 1fr; } .kpi-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .req-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 16px !important; gap: 14px !important; }
    .req-show-container .header-content { flex: 1 1 100% !important; width: 100% !important; }
    .req-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .req-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid { grid-template-columns: 1fr; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .req-show-container .header-actions { grid-template-columns: 1fr !important; } }
</style>

<script>
function approveRequisition() {
    const remarks = prompt('Please enter any approval remarks/comments (optional):', '');
    if (remarks !== null) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/requisition/approve/<?php echo $requisition['id']; ?>';
        const csrf = document.createElement('input');
        csrf.type = 'hidden'; csrf.name = 'csrf_token'; csrf.value = '<?php echo Security::csrfToken(); ?>';
        form.appendChild(csrf);
        const remarksInput = document.createElement('input');
        remarksInput.type = 'hidden'; remarksInput.name = 'approval_remarks'; remarksInput.value = remarks;
        form.appendChild(remarksInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectRequisition() {
    const reason = prompt('Please enter reason for rejection:');
    if (reason) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/requisition/reject/<?php echo $requisition['id']; ?>';
        const csrf = document.createElement('input');
        csrf.type = 'hidden'; csrf.name = 'csrf_token'; csrf.value = '<?php echo Security::csrfToken(); ?>';
        form.appendChild(csrf);
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden'; reasonInput.name = 'rejection_reason'; reasonInput.value = reason;
        form.appendChild(reasonInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function copyToClipboard(text, label) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.getElementById('copyToast');
        toast.innerHTML = `<i class="fas fa-check-circle" style="color:#4ADE80;"></i> Copied ${label}: <strong>${text}</strong>`;
        toast.classList.add('show');
        setTimeout(() => { toast.classList.remove('show'); }, 3000);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
