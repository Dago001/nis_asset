<?php
/**
 * Dashboard View
 *
 * Variables expected:
 * $stats - Dashboard statistics array
 * $activities - Recent activities array
 * $user - Current user data
 * $isSuperAdmin - Boolean for super admin
 * $userPermissions - Array of user permissions
 */
$init_charts = false;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
if (file_exists(__DIR__ . '/../layouts/alerts.php')) {
    require_once __DIR__ . '/../layouts/alerts.php';
}

$totalAlerts = (int)($stats['expiring_ammunition'] ?? 0) + (int)($stats['unserviceable_weapons'] ?? 0);
$assetTotal = (int)($stats['total_land'] ?? 0)
    + (int)($stats['total_buildings'] ?? 0)
    + (int)($stats['total_rented'] ?? 0)
    + (int)($stats['total_projects'] ?? 0)
    + (int)($stats['total_movable'] ?? 0)
    + (int)($stats['total_ict'] ?? 0);
$fleetTotal = (int)($stats['total_vehicles'] ?? 0)
    + (int)($stats['total_aircraft'] ?? 0)
    + (int)($stats['total_marine'] ?? 0)
    + (int)($stats['total_motorcycles'] ?? 0);

$cards = [
    [
        'key' => 'total_weapons',
        'api' => 'weapons.total',
        'label' => 'Total Weapons',
        'value' => $stats['total_weapons'] ?? 0,
        'meta' => number_format($stats['weapons_issued'] ?? 0) . ' issued',
        'icon' => 'fa-gun',
        'tone' => 'danger',
        'href' => BASE_URL . '/weapons',
        'action' => 'View Weapons',
    ],
    [
        'key' => 'total_ammunition',
        'api' => 'ammunition.total_types',
        'label' => 'Ammunition Types',
        'value' => $stats['total_ammunition'] ?? 0,
        'meta' => number_format($stats['ammunition_balance'] ?? 0) . ' rounds total',
        'icon' => 'fa-bullseye',
        'tone' => 'gold',
        'href' => BASE_URL . '/ammunition',
        'action' => 'View Ammo',
    ],
    [
        'key' => 'total_land',
        'api' => 'assets.land',
        'label' => 'Land Assets',
        'value' => $stats['total_land'] ?? 0,
        'meta' => 'Registered parcels',
        'icon' => 'fa-map-marked-alt',
        'tone' => 'green',
        'href' => BASE_URL . '/land',
        'action' => 'View Land',
    ],
    [
        'key' => 'total_buildings',
        'api' => 'assets.buildings',
        'label' => 'Buildings',
        'value' => $stats['total_buildings'] ?? 0,
        'meta' => 'Facility records',
        'icon' => 'fa-building',
        'tone' => 'blue',
        'href' => BASE_URL . '/buildings',
        'action' => 'View Buildings',
    ],
    [
        'key' => 'total_rented',
        'api' => 'assets.rented',
        'label' => 'Rented Properties',
        'value' => $stats['total_rented'] ?? 0,
        'meta' => 'Lease portfolio',
        'icon' => 'fa-house-user',
        'tone' => 'brown',
        'href' => BASE_URL . '/rented',
        'action' => 'View Rented',
    ],
    [
        'key' => 'total_projects',
        'api' => 'assets.projects',
        'label' => 'Ongoing Projects',
        'value' => $stats['total_projects'] ?? 0,
        'meta' => 'Active delivery',
        'icon' => 'fa-hard-hat',
        'tone' => 'olive',
        'href' => BASE_URL . '/projects',
        'action' => 'View Projects',
    ],
    [
        'key' => 'total_movable',
        'api' => 'assets.movable',
        'label' => 'Movable Assets',
        'value' => $stats['total_movable'] ?? 0,
        'meta' => 'Tracked equipment',
        'icon' => 'fa-tools',
        'tone' => 'teal',
        'href' => BASE_URL . '/movable',
        'action' => 'View Movable',
    ],
    [
        'key' => 'total_ict',
        'api' => 'assets.ict',
        'label' => 'ICT Assets',
        'value' => $stats['total_ict'] ?? 0,
        'meta' => 'Digital inventory',
        'icon' => 'fa-server',
        'tone' => 'dark',
        'href' => BASE_URL . '/ict',
        'action' => 'View ICT',
    ],
    [
        'key' => 'total_vehicles',
        'api' => 'fleet.vehicles',
        'label' => 'Vehicles',
        'value' => $stats['total_vehicles'] ?? 0,
        'meta' => 'Fleet records',
        'icon' => 'fa-car',
        'tone' => 'brown',
        'href' => BASE_URL . '/fleet/vehicles',
        'action' => 'View Vehicles',
    ],
    [
        'key' => 'total_aircraft',
        'api' => 'fleet.aircraft',
        'label' => 'Aircraft',
        'value' => $stats['total_aircraft'] ?? 0,
        'meta' => 'Fleet records',
        'icon' => 'fa-helicopter',
        'tone' => 'gold',
        'href' => BASE_URL . '/fleet/aircraft',
        'action' => 'View Aircraft',
    ],
    [
        'key' => 'total_motorcycles',
        'api' => 'fleet.motorcycles',
        'label' => 'Motorcycles',
        'value' => $stats['total_motorcycles'] ?? 0,
        'meta' => 'Fleet records',
        'icon' => 'fa-motorcycle',
        'tone' => 'teal',
        'href' => BASE_URL . '/fleet/motorcycles',
        'action' => 'View Motorcycles',
    ],
    [
        'key' => 'pending_requisitions',
        'api' => 'requisitions.pending',
        'label' => 'Pending Requisitions',
        'value' => $stats['pending_requisitions'] ?? 0,
        'meta' => 'Awaiting action',
        'icon' => 'fa-file-signature',
        'tone' => 'gold',
        'href' => BASE_URL . '/requisition/pending',
        'action' => 'Review',
    ],
    [
        'key' => 'total_users',
        'api' => 'users.total',
        'label' => 'Active Users',
        'value' => $stats['total_users'] ?? 0,
        'meta' => 'Enabled accounts',
        'icon' => 'fa-users',
        'tone' => 'green',
        'href' => BASE_URL . '/users',
        'action' => 'Manage Users',
    ],
    [
        'key' => 'system_alerts',
        'api' => null,
        'label' => 'System Alerts',
        'value' => $totalAlerts,
        'meta' => number_format($stats['expiring_ammunition'] ?? 0) . ' expiring | ' . number_format($stats['unserviceable_weapons'] ?? 0) . ' unserviceable',
        'icon' => 'fa-exclamation-triangle',
        'tone' => $totalAlerts > 0 ? 'danger' : 'green',
        'href' => BASE_URL . '/weapons?filter=unserviceable',
        'action' => 'View Alerts',
    ],
];

// For Command Approval Officer, remove Active Users and System Alerts cards
$userSessionRoles = $_SESSION['roles'] ?? [];
$isCommandApprovalOfficer = (in_array('Command Approval Officer', $userSessionRoles, true) || (class_exists('Auth') && Auth::hasRole('Command Approval Officer'))) && !($isSuperAdmin ?? false);
if ($isCommandApprovalOfficer) {
    $cards = array_values(array_filter($cards, function($c) {
        return !in_array($c['key'], ['total_users', 'system_alerts'], true);
    }));
}

$healthItems = [
    ['label' => 'Weapons issued', 'value' => $stats['weapons_issued'] ?? 0, 'max' => max((int)($stats['total_weapons'] ?? 0), 1), 'tone' => 'danger'],
    ['label' => 'Ammunition alerts', 'value' => $stats['expiring_ammunition'] ?? 0, 'max' => max($totalAlerts, 1), 'tone' => 'gold'],
    ['label' => 'Unserviceable weapons', 'value' => $stats['unserviceable_weapons'] ?? 0, 'max' => max($totalAlerts, 1), 'tone' => 'danger'],
];
?>

<style>
.nis-dashboard {
    --dash-green: #134617;
    --dash-green-2: #207027;
    --dash-ink: #10251d;
    --dash-muted: #68776f;
    --dash-border: #dfe8e3;
    --dash-bg: #f5f8f6;
    --dash-card: #ffffff;
    --dash-red: #b42318;
    --dash-gold: #b7791f;
    --dash-blue: #1f6f8b;
    --dash-teal: #0b7a5a;
    --dash-olive: #556b2f;
    padding-bottom: 28px;
    color: var(--dash-ink);
}
[data-theme="dark"] .nis-dashboard {
    --dash-green: #299631;
    --dash-green-2: #37bf43;
    --dash-ink: #dae6e2;
    --dash-muted: #9ca9a2;
    --dash-border: #2f3733;
    --dash-bg: #1b221e;
    --dash-card: #1f1f1f;
    --dash-red: #e7564b;
    --dash-gold: #e3ab59;
    --dash-blue: #3cacd4;
    --dash-teal: #13d89f;
    --dash-olive: #8db14f;
}


.map-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}
.pill-developed { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.pill-undeveloped { background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; }
.pill-fenced { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
.pill-litigation { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
.pill-total { background: #134617; color: #ffffff; border: 1px solid #134617; }

.region-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.78rem;
    white-space: nowrap;
}

.nis-map-popup {
    font-family: inherit;
    font-size: 0.85rem;
    padding: 4px;
    min-width: 200px;
}
.nis-map-popup h4 {
    margin: 0 0 6px 0;
    color: #134617;
    font-size: 0.95rem;
    font-weight: 700;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 4px;
}
.nis-map-popup .popup-row {
    margin: 3px 0;
    line-height: 1.4;
    color: #444;
}
.nis-map-popup .popup-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.75rem;
    font-weight: 600;
}

.dashboard-hero {
    padding: 16px 20px;
    margin-bottom: 18px;
    border-radius: 8px;
    color: #134617;
    background: #f8faf9;
    border: 1px solid #e0e8e3;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
}

.hero-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 6px;
}

.hero-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.hero-title i {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #e8f5e9;
    color: #134617;
    font-size: 1.1rem;
}

.hero-title h1 {
    margin: 0;
    color: #134617;
    font-size: 1.45rem;
    font-weight: 700;
}

.dashboard-hero p {
    margin: 0;
    color: #4a5568;
    font-size: 0.88rem;
}

.hero-refresh {
    border: 1px solid #134617;
    background: var(--surface);
    color: #134617;
    border-radius: 6px;
    padding: 6px 14px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    transition: all 0.2s ease;
}

.hero-refresh:hover {
    background: #134617;
    color: #ffffff;
}

.dashboard-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.summary-tile,
.dash-card,
.detail-section.dashboard-panel,
.quick-actions.dashboard-panel {
    background: var(--dash-card);
    border: 1px solid var(--dash-border);
    border-radius: 8px;
    box-shadow: 0 10px 26px rgba(16, 37, 29, 0.06);
}

.summary-tile {
    padding: 16px;
}

.summary-label {
    color: var(--dash-muted);
    font-size: 0.76rem;
    text-transform: uppercase;
    font-weight: 800;
}

.summary-value {
    margin-top: 8px;
    color: var(--dash-green);
    font-size: 1.55rem;
    font-weight: 800;
}

.dashboard-grid.modern-grid {
    grid-template-columns: repeat(4, minmax(210px, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.stat-card.dash-card {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 14px;
    padding: 16px;
    min-height: 152px;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.stat-card.dash-card:hover {
    transform: translateY(-3px);
    border-color: rgba(32, 112, 39, 0.28);
    box-shadow: 0 16px 34px rgba(16, 37, 29, 0.1);
}

/* .stat-card.dash-card::after {
    content: '';
    position: absolute;
    inset: auto 0 0 0;
    height: 3px;
    background: var(--dash-muted);
} */

.dash-card .card-icon {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.12rem;
}

.dash-card .card-icon.total_weapons,
.dash-card .card-icon.system_alerts {
    background: #ffebee;
    color: #c53030;
}

.dash-card .card-icon.total_ammunition,
.dash-card .card-icon.pending_requisitions {
    background: #fff8e1;
    color: #b7791f;
}

.dash-card .card-icon.total_land,
.dash-card .card-icon.total_users {
    background: #e8f5e9;
    color: #207027;
}

.dash-card .card-icon.total_buildings {
    background: #e3f2fd;
    color: #2b6cb0;
}

.dash-card .card-icon.total_rented {
    background: #efebe9;
    color: #8d6e63;
}

.dash-card .card-icon.total_projects {
    background: #f1f8e9;
    color: #556b2f;
}

.dash-card .card-icon.total_movable {
    background: #e0f2f1;
    color: #00695c;
}

.dash-card .card-icon.total_ict {
    background: #eceff1;
    color: #37474f;
}

.dash-card .card-icon.total_vehicles {
    background: #fbe9e7;
    color: #d84315;
}

.dash-card .card-icon.total_aircraft {
    background: #fff8e1;
    color: #b7791f;
}

.dash-card .card-icon.total_motorcycles {
    background: #e0f2f1;
    color: #0b7a5a;
}

.dash-card .card-content {
    min-width: 0;
}

.dash-card .card-value {
    color: var(--dash-ink);
    font-size: 1.65rem;
    font-weight: 850;
    line-height: 1.1;
    margin-bottom: 4px;
}

.dash-card .card-label {
    color: var(--dash-ink);
    font-size: 0.86rem;
    font-weight: 800;
    text-transform: none;
    letter-spacing: 0;
}

.dash-card .card-trend {
    color: var(--dash-muted);
    font-size: 0.75rem;
    margin-top: 6px;
}

.dash-card .card-action {
    display: flex;
    justify-content: flex-end;
    margin-top: 2px;
}

.dash-card .btn-link {
    color: var(--dash-green-2);
    font-size: 0.82rem;
    text-decoration: none !important;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f0f4f1;
    transition: all 0.2s ease;
}

.dash-card .btn-link:hover {
    background: var(--dash-green-2);
    color: #ffffff;
    transform: translateX(2px);
}

.dashboard-main-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr);
    gap: 18px;
    align-items: start;
}

.dashboard-panel {
    padding: 18px;
    margin: 0 0 18px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.section-header h3 {
    color: var(--dash-green);
    margin: 0;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.view-all {
    color: var(--dash-green-2);
    font-size: 0.8rem;
    font-weight: 800;
    text-decoration: none;
}

.health-list {
    display: grid;
    gap: 14px;
}

.health-row {
    display: grid;
    gap: 7px;
}

.health-top {
    display: flex;
    justify-content: space-between;
    color: var(--dash-muted);
    font-size: 0.78rem;
    font-weight: 700;
}

.health-track {
    height: 8px;
    border-radius: 999px;
    overflow: hidden;
    background: #edf3ef;
}

.health-fill {
    height: 100%;
    width: var(--fill);
    background: var(--dash-muted);
}

.activity-list.modern-activity {
    display: grid;
    gap: 10px;
}

.activity-item.modern-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid #edf2ef;
    border-radius: 8px;
    background: #fbfdfc;
}

.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #eef7f2;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.activity-title {
    color: var(--dash-ink);
    font-weight: 800;
    font-size: 0.84rem;
}

.activity-description,
.activity-time {
    color: var(--dash-muted);
    font-size: 0.76rem;
}

.quick-actions.dashboard-panel .action-grid,
.dashboard-panel .action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
    gap: 12px;
}

.quick-actions.dashboard-panel .action-card,
.dashboard-panel .action-card {
    border: 1px solid var(--dash-border);
    border-radius: 8px;
    background: var(--surface);
    color: var(--dash-ink);
    padding: 14px 12px;
    text-decoration: none;
    display: grid;
    gap: 8px;
    justify-items: start;
    transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.quick-actions.dashboard-panel .action-card:hover,
.dashboard-panel .action-card:hover {
    transform: translateY(-2px);
    border-color: rgba(32, 112, 39, 0.3);
    background: #f5fbf8;
}

.quick-actions.dashboard-panel .action-card i,
.dashboard-panel .action-card i {
    color: var(--dash-green-2);
    font-size: 1rem;
}

.quick-actions.dashboard-panel .action-card span,
.dashboard-panel .action-card span {
    font-size: 0.78rem;
    font-weight: 800;
}

.empty-state {
    padding: 24px;
    text-align: center;
    color: var(--dash-muted);
}

@media (max-width: 1180px) {
    .dashboard-grid.modern-grid,
    .dashboard-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-main-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .dashboard-hero {
        grid-template-columns: 1fr;
    }

    .hero-actions {
        align-items: flex-start;
    }

    .dashboard-grid.modern-grid,
    .dashboard-summary {
        grid-template-columns: 1fr;
    }

    .activity-item.modern-item {
        grid-template-columns: auto 1fr;
    }

.dashboard-rows-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
    box-sizing: border-box;
}

.dashboard-row {
    width: 100%;
    box-sizing: border-box;
}

.chart-container {
    height: 260px;
    position: relative;
    padding: 5px;
    width: 100%;
    box-sizing: border-box;
}

.chart-container-sm {
    height: 220px;
    position: relative;
    padding: 5px;
    width: 100%;
    box-sizing: border-box;
}

.chart-container canvas,
.chart-container-sm canvas {
    max-width: 100% !important;
    width: 100% !important;
    display: block;
}

@media (max-width: 1100px) {
    .dashboard-row.row-1,
    .dashboard-row.row-3 {
        grid-template-columns: 1fr !important;
    }

    .dashboard-row.row-2 {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 768px) {
    .dashboard-row.row-2 {
        grid-template-columns: 1fr !important;
    }

    .quick-actions .action-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
</style>

<div class="container-fluid nis-dashboard">
    <div class="dashboard-hero">
        <div class="hero-header-row">
            <div class="hero-title">
                <i class="fas fa-chart-line"></i>
                <h1>Dashboard</h1>
            </div>
            <button class="hero-refresh" type="button" onclick="refreshDashboard(event)">
                <i class="fas fa-sync-alt"></i>
                Refresh
            </button>
        </div>
        <p>Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>.</p>
    </div>

    <?php if (!empty($pendingMyApproval)): ?>
    <div class="approval-queue-panel">
        <div class="approval-queue-header">
            <h3><i class="fas fa-clipboard-check"></i> Requisitions Awaiting Your Approval</h3>
            <span class="approval-queue-count"><?php echo count($pendingMyApproval); ?></span>
        </div>
        <div class="table-responsive">
            <table class="approval-queue-table">
                <thead>
                    <tr>
                        <th>Req Number</th>
                        <th>Requested By</th>
                        <th>Priority</th>
                        <th>Items</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingMyApproval as $req): ?>
                    <tr>
                        <td><a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $req['id']; ?>"><?php echo htmlspecialchars($req['requisition_number']); ?></a></td>
                        <td><?php echo htmlspecialchars($req['requester_name'] ?? $req['requesting_officer_name'] ?? ''); ?></td>
                        <td>
                            <span class="approval-priority priority-<?php echo strtolower(htmlspecialchars($req['priority_level'])); ?>">
                                <?php echo htmlspecialchars($req['priority_level']); ?>
                            </span>
                        </td>
                        <td><?php echo (int) $req['item_count']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($req['created_at'])); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/requisition/show/<?php echo $req['id']; ?>" class="btn-review">
                                Review <i class="fas fa-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <style>
        .approval-queue-panel {
            background: var(--surface);
            border: 1px solid var(--border-color, var(--dash-border));
            border-radius: 10px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        .approval-queue-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            background: linear-gradient(135deg, #134617 0%, #207027 100%);
            color: #fff;
        }
        .approval-queue-header h3 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        .approval-queue-count {
            background: rgba(255,255,255,0.25);
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .approval-queue-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .approval-queue-table th {
            text-align: left;
            padding: 10px 20px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color, var(--dash-border));
            font-weight: 600;
        }
        .approval-queue-table td {
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color, var(--dash-border));
            color: var(--text-primary);
        }
        .approval-queue-table a {
            color: #207027;
            text-decoration: none;
            font-weight: 600;
        }
        .approval-priority {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .approval-priority.priority-urgent { background: rgba(180,35,24,0.15); color: #B42318; }
        .approval-priority.priority-high { background: rgba(183,121,31,0.15); color: #B7791F; }
        .approval-priority.priority-medium { background: rgba(31,111,139,0.15); color: #1F6F8B; }
        .approval-priority.priority-low { background: rgba(32,112,39,0.15); color: #207027; }
        .btn-review {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #207027;
            color: #fff !important;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        [data-theme="dark"] .approval-priority.priority-urgent { background: rgba(231,86,75,0.18); color: #f28b81; }
        [data-theme="dark"] .approval-priority.priority-high { background: rgba(238,192,82,0.18); color: #eec052; }
        [data-theme="dark"] .approval-priority.priority-medium { background: rgba(60,172,212,0.18); color: #7dd3f0; }
        [data-theme="dark"] .approval-priority.priority-low { background: rgba(55,191,67,0.18); color: #7be08a; }
    </style>
    <?php endif; ?>

    <div class="dashboard-grid modern-grid">
        <?php foreach ($cards as $card): ?>
            <div class="stat-card dash-card">
                <div class="card-icon <?php echo htmlspecialchars($card['key']); ?>">
                    <i class="fas <?php echo htmlspecialchars($card['icon']); ?>"></i>
                </div>
                <div class="card-content">
                    <div class="card-value" data-stat-key="<?php echo htmlspecialchars($card['key']); ?>"<?php if ($card['api']): ?> data-api-path="<?php echo htmlspecialchars($card['api']); ?>"<?php endif; ?>>
                        <?php echo number_format($card['value']); ?>
                    </div>
                    <div class="card-label"><?php echo htmlspecialchars($card['label']); ?></div>
                    <div class="card-trend" data-meta-key="<?php echo htmlspecialchars($card['key']); ?>"><?php echo htmlspecialchars($card['meta']); ?></div>
                </div>
                <div class="card-action">
                    <a href="<?php echo htmlspecialchars($card['href']); ?>" class="btn-link" title="<?php echo htmlspecialchars($card['label']); ?>">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Real-Time NIS Land Assets Map Section (Replaces Super Admin Controls) -->
    <div class="dashboard-panel land-map-panel" style="margin-bottom: 24px;">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-map-marked-alt" style="color: #134617;"></i>
                <span>Real-Time NIS Land Assets Map</span>
                <span class="live-map-badge" style="font-size: 0.75rem; font-weight: 500; background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; padding: 2px 8px; border-radius: 12px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-satellite-dish fa-spin"></i> Live Network
                </span>
            </h3>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <div class="map-view-toggle" style="display: flex; background: #e0e8e3; border-radius: 6px; padding: 2px; border: 1px solid #ccd8d0;">
                    <button type="button" id="btnVectorMap" onclick="toggleMapView('vector')" class="map-toggle-btn active" style="padding: 4px 10px; font-size: 0.78rem; font-weight: 600; border: none; border-radius: 4px; background: #134617; color: #fff; cursor: pointer;">
                        <i class="fas fa-layer-group"></i> Geopolitical Map
                    </button>
                    <button type="button" id="btnGisMap" onclick="toggleMapView('gis')" class="map-toggle-btn" style="padding: 4px 10px; font-size: 0.78rem; font-weight: 600; border: none; border-radius: 4px; background: transparent; color: #334e3e; cursor: pointer;">
                        <i class="fas fa-globe"></i> GIS Satellite Map
                    </button>
                </div>
                <div class="map-stats-summary" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <span class="map-stat-pill pill-developed"><i class="fas fa-check-circle"></i> Developed: <strong id="mapDevelopedCount">0</strong></span>
                    <span class="map-stat-pill pill-undeveloped"><i class="fas fa-layer-group"></i> Undeveloped: <strong id="mapUndevelopedCount">0</strong></span>
                    <span class="map-stat-pill pill-fenced"><i class="fas fa-border-all"></i> Fenced: <strong id="mapFencedCount">0</strong></span>
                    <span class="map-stat-pill pill-litigation"><i class="fas fa-gavel"></i> Litigation: <strong id="mapLitigationCount">0</strong></span>
                    <span class="map-stat-pill pill-total"><i class="fas fa-map-pin"></i> Total Locations: <strong id="mapTotalCount">0</strong></span>
                </div>
            </div>
        </div>

        <!-- Regional Legend Pills matching Nigeria Geopolitical Zones -->
        <div class="region-legend-bar" style="display: flex; gap: 8px; margin: 14px 0; padding: 10px 14px; background: #f8faf9; border-radius: 6px; border: 1px solid #e0e8e3; flex-wrap: wrap; align-items: center;">
            <span class="legend-region-title" style="font-weight: 600; font-size: 0.82rem; color: #134617;"><i class="fas fa-globe-africa"></i> Geopolitical Regions:</span>
            <span class="region-badge region-nc" style="background: #e0f7fa; color: #006064; border: 1px solid #b2ebf2;"><i class="fas fa-square" style="color: #00acc1;"></i> North Central: <strong id="regNC">0</strong></span>
            <span class="region-badge region-ne" style="background: #fffde7; color: #f57f17; border: 1px solid #fff9c4;"><i class="fas fa-square" style="color: #fbc02d;"></i> North East: <strong id="regNE">0</strong></span>
            <span class="region-badge region-nw" style="background: #f3e5f5; color: #4a148c; border: 1px solid #e1bee7;"><i class="fas fa-square" style="color: #8e24aa;"></i> North West: <strong id="regNW">0</strong></span>
            <span class="region-badge region-se" style="background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9;"><i class="fas fa-square" style="color: #4caf50;"></i> South East: <strong id="regSE">0</strong></span>
            <span class="region-badge region-ss" style="background: #fce4ec; color: #880e4f; border: 1px solid #f8bbd0;"><i class="fas fa-square" style="color: #e91e63;"></i> South South: <strong id="regSS">0</strong></span>
            <span class="region-badge region-sw" style="background: #efebe9; color: #4e342e; border: 1px solid #d7ccc8;"><i class="fas fa-square" style="color: #795548;"></i> South West: <strong id="regSW">0</strong></span>
            <span class="region-badge region-hq" style="background: #e8eaf6; color: #1a237e; border: 1px solid #c5cae9;"><i class="fas fa-building" style="color: #3f51b5;"></i> HQ / Central: <strong id="regHQ">0</strong></span>
        </div>

        <div id="nisMapOuterWrapper" style="position: relative; width: 100%; height: 530px; border-radius: 8px; border: 1px solid var(--border-color); overflow: hidden; background: var(--surface);">
            <!-- Ultra-Fast Vector Map of Nigeria -->
            <div id="nisVectorMapContainer" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; background: radial-gradient(circle at 50% 50%, #ffffff 0%, #f4f8f5 100%); p-3;">
                <div style="position: relative; width: 100%; max-width: 780px; height: 480px; display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 800 600" style="width: 100%; height: 100%; filter: drop-shadow(0 6px 12px rgba(0,0,0,0.1));" id="svgNigeriaMap">
                        <defs>
                            <filter id="shadow" x="-5%" y="-5%" width="110%" height="110%">
                                <feDropShadow dx="2" dy="4" stdDeviation="4" flood-opacity="0.15"/>
                            </filter>
                        </defs>
                        <!-- North West Region -->
                        <path d="M 220 50 L 510 60 L 480 180 L 400 230 L 260 210 L 170 140 Z" fill="#f3e5f5" stroke="#8e24aa" stroke-width="2.5" class="svg-region-path" data-region="North West"/>
                        <text x="340" y="120" font-size="15" font-weight="bold" fill="#4a148c">North West Region</text>

                        <!-- North East Region -->
                        <path d="M 510 60 L 770 70 L 740 330 L 610 330 L 530 240 L 480 180 Z" fill="#fffde7" stroke="#fbc02d" stroke-width="2.5" class="svg-region-path" data-region="North East"/>
                        <text x="610" y="160" font-size="15" font-weight="bold" fill="#f57f17">North East Region</text>

                        <!-- North Central Region -->
                        <path d="M 170 140 L 260 210 L 400 230 L 480 180 L 530 240 L 610 330 L 540 430 L 410 420 L 320 380 L 160 300 L 120 230 Z" fill="#e0f7fa" stroke="#00acc1" stroke-width="2.5" class="svg-region-path" data-region="North Central"/>
                        <text x="310" y="310" font-size="15" font-weight="bold" fill="#006064">North Central Region</text>

                        <!-- FCT Central Dot -->
                        <circle cx="370" cy="300" r="16" fill="#e8eaf6" stroke="#3f51b5" stroke-width="3"/>
                        <text x="370" y="304" font-size="11" font-weight="bold" fill="#1a237e" text-anchor="middle">FCT</text>

                        <!-- South West Region -->
                        <path d="M 40 350 L 160 300 L 320 380 L 240 460 L 180 430 L 80 430 Z" fill="#efebe9" stroke="#795548" stroke-width="2.5" class="svg-region-path" data-region="South West"/>
                        <text x="120" y="390" font-size="14" font-weight="bold" fill="#4e342e">South West</text>

                        <!-- South East Region -->
                        <path d="M 320 380 L 410 420 L 440 500 L 340 530 L 310 450 Z" fill="#e8f5e9" stroke="#4caf50" stroke-width="2.5" class="svg-region-path" data-region="South East"/>
                        <text x="350" y="460" font-size="14" font-weight="bold" fill="#1b5e20">South East</text>

                        <!-- South South Region -->
                        <path d="M 180 430 L 240 460 L 310 450 L 340 530 L 440 500 L 540 430 L 530 520 L 410 570 L 240 550 L 180 490 Z" fill="#fce4ec" stroke="#e91e63" stroke-width="2.5" class="svg-region-path" data-region="South South"/>
                        <text x="440" y="490" font-size="14" font-weight="bold" fill="#880e4f">South South</text>
                    </svg>

                    <!-- Interactive Pin Overlay Container -->
                    <div id="vectorMapPinsOverlay" style="position: absolute; inset: 0; pointer-events: none;"></div>
                </div>
            </div>

            <!-- Leaflet GIS Satellite Map -->
            <div id="nisLandMap" style="position: absolute; inset: 0; opacity: 0; pointer-events: none; transition: opacity 0.3s ease;"></div>
        </div>
    </div>

    <!-- 3-ROW STRUCTURED DASHBOARD LAYOUT (100% Full Width, 0 Right White Space) -->
    <div class="dashboard-rows-wrapper">
        
        <!-- ROW 1: Asset Portfolio Distribution + Operational Health -->
        <div class="dashboard-row row-1" style="display: grid; grid-template-columns: 1.8fr 1fr; gap: 18px; width: 100%;">
            <div class="detail-section dashboard-panel" style="margin-bottom: 0;">
                <div class="section-header">
                    <h3><i class="fas fa-chart-bar"></i> Asset Portfolio Distribution</h3>
                </div>
                <div class="chart-container" style="height: 260px; position: relative;">
                    <canvas id="assetDistributionChart"></canvas>
                </div>
            </div>

            <div class="detail-section dashboard-panel" style="margin-bottom: 0;">
                <div class="section-header">
                    <h3><i class="fas fa-shield-alt"></i> Operational Health</h3>
                </div>
                <div class="health-list">
                    <?php foreach ($healthItems as $item): ?>
                        <?php $percent = min(100, max(0, ((float)$item['value'] / (float)$item['max']) * 100)); ?>
                        <div class="health-row">
                            <div class="health-top">
                                <span><?php echo htmlspecialchars($item['label']); ?></span>
                                <strong><?php echo number_format($item['value']); ?></strong>
                            </div>
                            <div class="health-track">
                                <div class="health-fill" style="--fill: <?php echo number_format($percent, 2); ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ROW 2: The 3 Donut/Pie Charts (Weapons Status, Fleet Breakdown, Requisitions Flow) -->
        <div class="dashboard-row row-2" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; width: 100%;">
            <div class="detail-section dashboard-panel" style="margin-bottom: 0;">
                <div class="section-header">
                    <h3><i class="fas fa-crosshairs"></i> Weapons Status</h3>
                </div>
                <div class="chart-container-sm" style="height: 220px; position: relative;">
                    <canvas id="weaponStatusChart"></canvas>
                </div>
            </div>

            <div class="detail-section dashboard-panel" style="margin-bottom: 0;">
                <div class="section-header">
                    <h3><i class="fas fa-truck"></i> Fleet Breakdown</h3>
                </div>
                <div class="chart-container-sm" style="height: 220px; position: relative;">
                    <canvas id="fleetBreakdownChart"></canvas>
                </div>
            </div>
            
            <div class="detail-section dashboard-panel" style="margin-bottom: 0;">
                <div class="section-header">
                    <h3><i class="fas fa-file-contract"></i> Requisitions Flow</h3>
                </div>
                <div class="chart-container-sm" style="height: 220px; position: relative;">
                    <canvas id="requisitionStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ROW 3: Requisition Priorities & Quick Actions -->
        <div class="dashboard-row row-3" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 18px; width: 100%;">
            <div class="detail-section dashboard-panel" style="margin-bottom: 0;">
                <div class="section-header">
                    <h3><i class="fas fa-exclamation-circle"></i> Requisition Priorities</h3>
                </div>
                <div class="chart-container-sm" style="height: 220px; position: relative;">
                    <canvas id="requisitionPriorityChart"></canvas>
                </div>
            </div>

            <?php if (!in_array('CGIS', $_SESSION['roles'] ?? [])): ?>
            <div class="quick-actions dashboard-panel" style="margin-bottom: 0;">
                <div class="section-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="action-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                    <?php if ($isSuperAdmin || in_array('requisition.create', $userPermissions)): ?>
                    <a href="<?php echo BASE_URL; ?>/requisition/create" class="action-card">
                        <i class="fas fa-file-signature"></i>
                        <span>New Requisition</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($isSuperAdmin || in_array('audit.create', $userPermissions)): ?>
                    <a href="<?php echo BASE_URL; ?>/audit/quarterly/create" class="action-card">
                        <i class="fas fa-clipboard-check"></i>
                        <span>New Audit</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($isSuperAdmin || in_array('weapons.create', $userPermissions)): ?>
                    <a href="<?php echo BASE_URL; ?>/weapons/create" class="action-card">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Weapon</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($isSuperAdmin || in_array('land.create', $userPermissions)): ?>
                    <a href="<?php echo BASE_URL; ?>/land/create" class="action-card">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Add Land Asset</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($isSuperAdmin || in_array('reports.view', $userPermissions)): ?>
                    <a href="<?php echo BASE_URL; ?>/reports" class="action-card">
                        <i class="fas fa-chart-bar"></i>
                        <span>Generate Report</span>
                    </a>
                    <?php endif; ?>

                    <a href="#" onclick="refreshDashboard(event)" class="action-card">
                        <i class="fas fa-sync-alt"></i>
                        <span>Refresh</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php
// Extract priority counts
$priorities = ['Low', 'Medium', 'High', 'Urgent'];
$priorityData = [0, 0, 0, 0];
if (!empty($stats['requisitions_by_priority'])) {
    foreach ($stats['requisitions_by_priority'] as $p) {
        $idx = array_search($p['priority_level'], $priorities);
        if ($idx !== false) {
            $priorityData[$idx] = (int)$p['count'];
        }
    }
}
?>

<script>
// Global Chart Instances for Real-time Updates
let assetChartInstance = null;
let weaponChartInstance = null;
let fleetChartInstance = null;
let requisitionChartInstance = null;
let requisitionPriorityChartInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    // 1. Asset Distribution Chart
    const assetCtx = document.getElementById('assetDistributionChart');
    if (assetCtx) {
        assetChartInstance = new Chart(assetCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Land', 'Buildings', 'Rented', 'Movable', 'ICT', 'Vehicles', 'Aircraft', 'Motorcycles', 'Weapons'],
                datasets: [{
                    label: 'Items / Units',
                    data: [
                        <?php echo (int)($stats['total_land'] ?? 0); ?>,
                        <?php echo (int)($stats['total_buildings'] ?? 0); ?>,
                        <?php echo (int)($stats['total_rented'] ?? 0); ?>,
                        <?php echo (int)($stats['total_movable'] ?? 0); ?>,
                        <?php echo (int)($stats['total_ict'] ?? 0); ?>,
                        <?php echo (int)($stats['total_vehicles'] ?? 0); ?>,
                        <?php echo (int)($stats['total_aircraft'] ?? 0); ?>,
                        <?php echo (int)($stats['total_motorcycles'] ?? 0); ?>,
                        <?php echo (int)($stats['total_weapons'] ?? 0); ?>
                    ],
                    backgroundColor: [
                        '#207027', '#1F6F8B', '#B42318', '#C69214', 
                        '#556B2F', '#0B7A5A', '#B7791F', '#134617', '#4A5568'
                    ],
                    borderWidth: 0,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // 2. Weapons Status Breakdown Chart
    const weaponCtx = document.getElementById('weaponStatusChart');
    if (weaponCtx) {
        weaponChartInstance = new Chart(weaponCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Serviceable', 'Unserviceable', 'In Repair', 'Issued'],
                datasets: [{
                    data: [
                        <?php echo (int)($stats['serviceable_weapons'] ?? 0); ?>,
                        <?php echo (int)($stats['unserviceable_weapons'] ?? 0); ?>,
                        <?php echo (int)($stats['in_repair_weapons'] ?? 0); ?>,
                        <?php echo (int)($stats['weapons_issued'] ?? 0); ?>
                    ],
                    backgroundColor: ['#207027', '#B42318', '#C69214', '#1F6F8B']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 8,
                            font: { size: 9 }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 3. Fleet breakdown Chart
    const fleetCtx = document.getElementById('fleetBreakdownChart');
    if (fleetCtx) {
        fleetChartInstance = new Chart(fleetCtx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Vehicles', 'Aircraft', 'Marine', 'Motorcycles'],
                datasets: [{
                    data: [
                        <?php echo (int)($stats['total_vehicles'] ?? 0); ?>,
                        <?php echo (int)($stats['total_aircraft'] ?? 0); ?>,
                        <?php echo (int)($stats['total_marine'] ?? 0); ?>,
                        <?php echo (int)($stats['total_motorcycles'] ?? 0); ?>
                    ],
                    backgroundColor: ['#0B7A5A', '#B7791F', '#1F6F8B', '#134617']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 8,
                            font: { size: 9 }
                        }
                    }
                }
            }
        });
    }

    // 4. Requisition Status Flow Chart
    const reqCtx = document.getElementById('requisitionStatusChart');
    if (reqCtx) {
        requisitionChartInstance = new Chart(reqCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo (int)($stats['pending_requisitions'] ?? 0); ?>,
                        <?php echo (int)($stats['approved_requisitions'] ?? 0); ?>,
                        <?php echo (int)($stats['rejected_requisitions'] ?? 0); ?>
                    ],
                    backgroundColor: ['#C69214', '#207027', '#B42318']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 8,
                            font: { size: 9 }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 5. Requisition Priorities Bar Chart
    const reqPriorityCtx = document.getElementById('requisitionPriorityChart');
    if (reqPriorityCtx) {
        requisitionPriorityChartInstance = new Chart(reqPriorityCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Low', 'Medium', 'High', 'Urgent'],
                datasets: [{
                    label: 'Requisitions Count',
                    data: <?php echo json_encode($priorityData); ?>,
                    backgroundColor: ['#556B2F', '#0B7A5A', '#C69214', '#B42318'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});

// Use a root-relative path so the API resolves correctly on any hostname
// (localhost, 127.0.0.1, LAN IP, or mobile device) without CORS issues.
const dashboardStatsUrl = '<?php echo parse_url(BASE_URL, PHP_URL_PATH); ?>/api/dashboard_stats.php';

function dashboardFormatNumber(value) {
    const number = Number(value || 0);
    return new Intl.NumberFormat().format(number);
}

function dashboardGetPath(source, path) {
    return path.split('.').reduce((value, key) => value && value[key] !== undefined ? value[key] : null, source);
}

function dashboardSetStatus(message) {
    const status = document.getElementById('dashboardLiveStatus');
    if (status) status.textContent = message;
}

function dashboardEscape(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
}

function dashboardActivityIcon(action) {
    if ((action || '').includes('CREATE')) return { icon: 'fa-plus-circle', color: 'text-success' };
    if ((action || '').includes('UPDATE')) return { icon: 'fa-edit', color: 'text-warning' };
    if ((action || '').includes('DELETE')) return { icon: 'fa-trash', color: 'text-danger' };
    if ((action || '').includes('LOGIN')) return { icon: 'fa-sign-in-alt', color: 'text-info' };
    return { icon: 'fa-info-circle', color: '' };
}

function dashboardFormatDate(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

function dashboardRenderActivities(activities) {
    const list = document.getElementById('dashboardActivityList');
    if (!list || !Array.isArray(activities)) return;

    if (!activities.length) {
        list.innerHTML = '<div class="empty-state"><i class="fas fa-history"></i><p>No recent activity</p></div>';
        return;
    }

    list.innerHTML = activities.map((activity) => {
        const icon = dashboardActivityIcon(activity.action);
        const byLine = activity.full_name ? ` by ${dashboardEscape(activity.full_name)}` : '';
        return `
            <div class="activity-item modern-item">
                <div class="activity-icon">
                    <i class="fas ${icon.icon} ${icon.color}"></i>
                </div>
                <div class="activity-details">
                    <div class="activity-title">${dashboardEscape(activity.action)}</div>
                    <div class="activity-description">${dashboardEscape(activity.description)}${byLine}</div>
                </div>
                <div class="activity-time">${dashboardEscape(dashboardFormatDate(activity.created_at))}</div>
            </div>
        `;
    }).join('');
}

function dashboardApplyStats(payload) {
    if (!payload || !payload.stats) return;
    const stats = payload.stats;

    document.querySelectorAll('[data-api-path]').forEach((element) => {
        const nextValue = dashboardGetPath(stats, element.dataset.apiPath);
        if (nextValue !== null) {
            element.textContent = dashboardFormatNumber(nextValue);
        }
    });

    const assetTotal = ['land', 'buildings', 'rented', 'projects', 'movable', 'ict']
        .reduce((total, key) => total + Number((stats.assets && stats.assets[key]) || 0), 0);
    const fleetTotal = ['vehicles', 'aircraft', 'marine', 'motorcycles']
        .reduce((total, key) => total + Number((stats.fleet && stats.fleet[key]) || 0), 0);
    const systemAlerts = Number((stats.ammunition && stats.ammunition.expiring_soon) || 0)
        + Number((stats.weapons && stats.weapons.unserviceable) || 0);

    const totals = {
        asset_total: assetTotal,
        fleet_total: fleetTotal,
        pending_requisitions: stats.requisitions ? stats.requisitions.pending : null,
        system_alerts: systemAlerts,
    };

    Object.entries(totals).forEach(([key, value]) => {
        if (value === null) return;
        document.querySelectorAll(`[data-stat-key="${key}"]`).forEach((element) => {
            element.textContent = dashboardFormatNumber(value);
        });
    });

    const alertsMeta = document.querySelector('[data-meta-key="system_alerts"]');
    if (alertsMeta) {
        const expiring = Number((stats.ammunition && stats.ammunition.expiring_soon) || 0);
        const unserviceable = Number((stats.weapons && stats.weapons.unserviceable) || 0);
        alertsMeta.textContent = `${dashboardFormatNumber(expiring)} expiring | ${dashboardFormatNumber(unserviceable)} unserviceable`;
    }

    // Dynamic real-time chart updates
    if (assetChartInstance && stats.assets && stats.fleet && stats.weapons) {
        assetChartInstance.data.datasets[0].data = [
            Number(stats.assets.land || 0),
            Number(stats.assets.buildings || 0),
            Number(stats.assets.rented || 0),
            Number(stats.assets.movable || 0),
            Number(stats.assets.ict || 0),
            Number(stats.fleet.vehicles || 0),
            Number(stats.fleet.aircraft || 0),
            Number(stats.fleet.motorcycles || 0),
            Number(stats.weapons.total || 0)
        ];
        assetChartInstance.update();
    }

    if (weaponChartInstance && stats.weapons) {
        weaponChartInstance.data.datasets[0].data = [
            Number(stats.weapons.serviceable || 0),
            Number(stats.weapons.unserviceable || 0),
            Number(stats.weapons.in_repair || 0),
            Number(stats.weapons.issued || 0)
        ];
        weaponChartInstance.update();
    }

    if (fleetChartInstance && stats.fleet) {
        fleetChartInstance.data.datasets[0].data = [
            Number(stats.fleet.vehicles || 0),
            Number(stats.fleet.aircraft || 0),
            Number(stats.fleet.marine || 0),
            Number(stats.fleet.motorcycles || 0)
        ];
        fleetChartInstance.update();
    }

    if (requisitionChartInstance && stats.requisitions) {
        requisitionChartInstance.data.datasets[0].data = [
            Number(stats.requisitions.pending || 0),
            Number(stats.requisitions.approved || 0),
            Number(stats.requisitions.rejected || 0)
        ];
        requisitionChartInstance.update();
    }

    if (requisitionPriorityChartInstance && stats.requisitions_by_priority) {
        const priorities = ['Low', 'Medium', 'High', 'Urgent'];
        const priorityData = [0, 0, 0, 0];
        stats.requisitions_by_priority.forEach((p) => {
            const idx = priorities.indexOf(p.priority_level);
            if (idx !== -1) {
                priorityData[idx] = Number(p.count || 0);
            }
        });
        requisitionPriorityChartInstance.data.datasets[0].data = priorityData;
        requisitionPriorityChartInstance.update();
    }

    dashboardRenderActivities(stats.recent_activity);
}

let _dashRetryTimer = null;
let nisLandMapInstance = null;
let nisLandMarkersGroup = null;

function toggleMapView(mode) {
    const vectorContainer = document.getElementById('nisVectorMapContainer');
    const gisContainer = document.getElementById('nisLandMap');
    const btnVector = document.getElementById('btnVectorMap');
    const btnGis = document.getElementById('btnGisMap');

    if (mode === 'gis') {
        if (vectorContainer) { vectorContainer.style.opacity = '0'; vectorContainer.style.pointerEvents = 'none'; }
        if (gisContainer) { gisContainer.style.opacity = '1'; gisContainer.style.pointerEvents = 'auto'; }
        if (btnVector) { btnVector.style.background = 'transparent'; btnVector.style.color = '#334e3e'; btnVector.classList.remove('active'); }
        if (btnGis) { btnGis.style.background = '#134617'; btnGis.style.color = '#fff'; btnGis.classList.add('active'); }
        if (nisLandMapInstance) { setTimeout(() => nisLandMapInstance.invalidateSize(), 200); }
    } else {
        if (vectorContainer) { vectorContainer.style.opacity = '1'; vectorContainer.style.pointerEvents = 'auto'; }
        if (gisContainer) { gisContainer.style.opacity = '0'; gisContainer.style.pointerEvents = 'none'; }
        if (btnVector) { btnVector.style.background = '#134617'; btnVector.style.color = '#fff'; btnVector.classList.add('active'); }
        if (btnGis) { btnGis.style.background = 'transparent'; btnGis.style.color = '#334e3e'; btnGis.classList.remove('active'); }
    }
}

async function loadNisLandAssetsMap() {
    const mapContainer = document.getElementById('nisLandMap');
    if (!mapContainer) return;

    if (typeof L !== 'undefined' && !nisLandMapInstance) {
        // Initialize Nigeria map centered at [9.0820, 8.6753] with zoom level 6
        nisLandMapInstance = L.map('nisLandMap', {
            center: [9.0820, 8.6753],
            zoom: 6,
            zoomControl: true,
            scrollWheelZoom: true
        });

        // Add OpenStreetMap Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors | Nigeria Immigration Service'
        }).addTo(nisLandMapInstance);

        nisLandMarkersGroup = L.layerGroup().addTo(nisLandMapInstance);
    }

    try {
        const appBase = document.querySelector('meta[name="app-base"]')?.getAttribute('content') || '<?php echo BASE_URL; ?>';
        const fetchUrl = appBase.replace(/\/$/, '') + '/api/get_land_map_locations';

        const response = await fetch(fetchUrl, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });

        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();

        if (data && data.success) {
            // Update summary statistics
            const s = data.summary || {};
            const devEl = document.getElementById('mapDevelopedCount');
            if (devEl) devEl.textContent = (s.developed || 0).toLocaleString();
            const undevEl = document.getElementById('mapUndevelopedCount');
            if (undevEl) undevEl.textContent = (s.undeveloped || 0).toLocaleString();
            const fenEl = document.getElementById('mapFencedCount');
            if (fenEl) fenEl.textContent = (s.fenced || 0).toLocaleString();
            const litEl = document.getElementById('mapLitigationCount');
            if (litEl) litEl.textContent = (s.litigation || 0).toLocaleString();
            const totEl = document.getElementById('mapTotalCount');
            if (totEl) totEl.textContent = (s.total || 0).toLocaleString();

            const r = s.by_region || {};
            const rNC = document.getElementById('regNC'); if (rNC) rNC.textContent = (r['North Central'] || 0).toLocaleString();
            const rNE = document.getElementById('regNE'); if (rNE) rNE.textContent = (r['North East'] || 0).toLocaleString();
            const rNW = document.getElementById('regNW'); if (rNW) rNW.textContent = (r['North West'] || 0).toLocaleString();
            const rSE = document.getElementById('regSE'); if (rSE) rSE.textContent = (r['South East'] || 0).toLocaleString();
            const rSS = document.getElementById('regSS'); if (rSS) rSS.textContent = (r['South South'] || 0).toLocaleString();
            const rSW = document.getElementById('regSW'); if (rSW) rSW.textContent = (r['South West'] || 0).toLocaleString();
            const rHQ = document.getElementById('regHQ'); if (rHQ) rHQ.textContent = (r['HQ'] || 0).toLocaleString();

            // Render GIS markers if Leaflet loaded
            if (nisLandMarkersGroup) {
                nisLandMarkersGroup.clearLayers();

                const locations = data.locations || [];
                locations.forEach(loc => {
                    let pinColor = '#f57f17'; // default yellow/orange for undeveloped
                    let badgeStyle = 'background: #fff3e0; color: #e65100;';
                    const st = (loc.status || '').toLowerCase();

                    if (st.includes('developed')) {
                        pinColor = '#2e7d32'; // green
                        badgeStyle = 'background: #e8f5e9; color: #1b5e20;';
                    } else if (st.includes('fenced')) {
                        pinColor = '#1565c0'; // blue
                        badgeStyle = 'background: #e3f2fd; color: #0d47a1;';
                    } else if (st.includes('litigation')) {
                        pinColor = '#c62828'; // red
                        badgeStyle = 'background: #ffebee; color: #b71c1c;';
                    }

                    // Custom DivIcon pin
                    const customIcon = L.divIcon({
                        className: 'custom-map-pin',
                        html: `<div style="background-color: ${pinColor}; width: 26px; height: 26px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 2px solid #ffffff; box-shadow: 0 3px 8px rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center;">
                                 <i class="fas fa-building" style="transform: rotate(45deg); color: #ffffff; font-size: 11px;"></i>
                               </div>`,
                        iconSize: [26, 26],
                        iconAnchor: [13, 26],
                        popupAnchor: [0, -26]
                    });

                    const marker = L.marker([loc.lat, loc.lng], { icon: customIcon });

                    const popupContent = `
                        <div class="nis-map-popup">
                            <h4><i class="fas fa-map-marker-alt" style="color: ${pinColor};"></i> ${loc.asset_code}</h4>
                            <div class="popup-row"><strong>Title Holder:</strong> ${loc.title_holder}</div>
                            <div class="popup-row"><strong>Address:</strong> ${loc.address}</div>
                            <div class="popup-row"><strong>State / LGA:</strong> ${loc.state_name} (${loc.lga_name})</div>
                            <div class="popup-row"><strong>Command:</strong> ${loc.command_name}</div>
                            <div class="popup-row"><strong>Purpose:</strong> ${loc.purpose_use}</div>
                            <div class="popup-row"><strong>Size:</strong> ${loc.size} ${loc.size_unit}</div>
                            <div style="margin-top: 8px; display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #e0e0e0; padding-top: 6px;">
                                <span class="popup-badge" style="${badgeStyle}">${loc.status}</span>
                                <a href="<?php echo BASE_URL; ?>/land/show/${loc.id}" target="_blank" style="color: #134617; font-weight: 600; text-decoration: none; font-size: 0.8rem;">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    `;

                    marker.bindPopup(popupContent);
                    nisLandMarkersGroup.addLayer(marker);
                });
            }
        }
    } catch (err) {
        console.warn('[Map] Failed to load real-time land asset map locations:', err.message);
    }
}

async function updateDashboardStats(isManual = false) {
    try {
        if (isManual) dashboardSetStatus('Refreshing live data...');
        const response = await fetch(dashboardStatsUrl, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const payload = await response.json();
        if (payload && payload.success) {
            dashboardApplyStats(payload);
            dashboardSetStatus('Updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
            // Clear any pending retry — next update is via the main 15s interval
            if (_dashRetryTimer) { clearTimeout(_dashRetryTimer); _dashRetryTimer = null; }
        }
    } catch (error) {
        dashboardSetStatus('Reconnecting...');
        console.warn('[Dashboard] Stats fetch failed:', error.message);
        // Retry after 5 s so charts recover quickly when DB/server comes back
        if (!_dashRetryTimer) {
            _dashRetryTimer = setTimeout(() => {
                _dashRetryTimer = null;
                updateDashboardStats(false);
            }, 5000);
        }
    }

    // Refresh map locations in parallel
    loadNisLandAssetsMap();
}

function refreshDashboard(event) {
    if (event) event.preventDefault();
    const btn = event ? event.target.closest('button, a') : null;
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing';
        btn.style.pointerEvents = 'none';
    }

    updateDashboardStats(true).finally(() => {
        if (btn) {
            btn.innerHTML = originalHtml;
            btn.style.pointerEvents = '';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Initial fetch immediately on page load
    updateDashboardStats(false);
    loadNisLandAssetsMap();
    // Poll every 15 seconds for real-time feel
    setInterval(() => {
        updateDashboardStats(false);
    }, 15000);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
