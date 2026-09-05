<?php
/**
 * Fleet Dashboard View
 */

// These variables should come from the controller
$stats = isset($stats) ? $stats : [
    'total_vehicles' => 0,
    'total_aircraft' => 0,
    'total_marine' => 0,
    'total_motorcycles' => 0,
    'active_vehicles' => 0,
    'vehicles_in_repair' => 0,
    'expiring_insurance' => 0,
    'total_fleet_value' => 0
];

$recent_vehicles = isset($recent_vehicles) ? $recent_vehicles : [];
$user = isset($user) ? $user : ['full_name' => 'User', 'roles' => ['Staff']];
$isSuperAdmin = isset($isSuperAdmin) ? $isSuperAdmin : false;

// Set page variables
$title = isset($title) ? $title : 'Fleet Dashboard';
$active = isset($active) ? $active : 'fleet-dashboard';
$init_charts = false;

// Include layouts
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-truck"></i>
                Fleet Management Dashboard
            </h1>
            <p>Overview of all fleet assets including vehicles, aircraft, marine, and motorcycles</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon vehicles">
                <i class="fas fa-car"></i>
            </div>
            <div class="stat-details">
                <h4>Vehicles</h4>
                <p class="stat-number"><?php echo number_format($stats['total_vehicles']); ?></p>
                <div class="stat-sub">
                    <span class="active"><?php echo number_format($stats['active_vehicles']); ?> Active</span>
                    <span class="repair"><?php echo number_format($stats['vehicles_in_repair']); ?> In Repair</span>
                </div>
            </div>
            <div class="stat-action">
                <a href="<?php echo BASE_URL; ?>/fleet/vehicles" class="btn-link" title="View Vehicles"><i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon aircraft">
                <i class="fas fa-helicopter"></i>
            </div>
            <div class="stat-details">
                <h4>Aircraft</h4>
                <p class="stat-number"><?php echo number_format($stats['total_aircraft']); ?></p>
            </div>
            <div class="stat-action">
                <a href="<?php echo BASE_URL; ?>/fleet/aircraft" class="btn-link" title="View Aircraft"><i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon marine">
                <i class="fas fa-ship"></i>
            </div>
            <div class="stat-details">
                <h4>Marine</h4>
                <p class="stat-number"><?php echo number_format($stats['total_marine']); ?></p>
            </div>
            <div class="stat-action">
                <a href="<?php echo BASE_URL; ?>/fleet/marine" class="btn-link" title="View Marine"><i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon motorcycles">
                <i class="fas fa-motorcycle"></i>
            </div>
            <div class="stat-details">
                <h4>Motorcycles</h4>
                <p class="stat-number"><?php echo number_format($stats['total_motorcycles']); ?></p>
            </div>
            <div class="stat-action">
                <a href="<?php echo BASE_URL; ?>/fleet/motorcycles" class="btn-link" title="View Motorcycles"><i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <!-- Fleet Value & Alerts -->
    <div class="stats-grid">
        <div class="stat-card total-value">
            <div class="stat-icon value">
                <i class="fas fa-coins"></i>
            </div>
            <div class="stat-details">
                <h4>Total Fleet Value</h4>
                <p class="stat-number">₦<?php echo number_format($stats['total_fleet_value'], 2); ?></p>
            </div>
        </div>

        <div class="stat-card alert-card">
            <div class="stat-icon alert">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h4>Insurance Expiring Soon</h4>
                <p class="stat-number"><?php echo $stats['expiring_insurance']; ?></p>
                <div class="stat-sub">Vehicles needing renewal within 30 days</div>
            </div>
            <div class="stat-action">
                <a href="<?php echo BASE_URL; ?>/fleet/vehicles?filter=expiring_insurance" class="btn-link" title="Review Expiring Insurance"><i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <!-- Recent Vehicles -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-history"></i> Recently Added Vehicles</h2>
            <a href="<?php echo BASE_URL; ?>/fleet/vehicles" class="view-all">View All</a>
        </div>

        <div class="table-responsive">
            <?php if (empty($recent_vehicles)): ?>
                <div class="empty-state">
                    <i class="fas fa-car"></i>
                    <p>No vehicles found</p>
                </div>
            <?php else: ?>
            <table class="asset-table">
                <thead>
                    <tr>
                        <th>Asset Code</th>
                        <th>Make/Model</th>
                        <th>Year</th>
                        <th>Registration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_vehicles as $vehicle): ?>
                    <tr>
                        <td><span class="asset-code"><?php echo Security::escape($vehicle['asset_code'] ?? ''); ?></span></td>
                        <td><?php echo Security::escape($vehicle['make_manufacturer'] ?? ''); ?></td>
                        <td><?php echo Security::escape($vehicle['model_year'] ?? ''); ?></td>
                        <td><?php echo Security::escape($vehicle['registration_number'] ?? ''); ?></td>
                        <td>
                            <?php 
                            $status = $vehicle['operational_status'] ?? '';
                            $statusClass = '';
                            if ($status == 'Active') $statusClass = 'status-active';
                            elseif ($status == 'In Repair') $statusClass = 'status-warning';
                            elseif ($status == 'Grounded') $statusClass = 'status-rejected';
                            else $statusClass = 'status-pending';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo Security::escape($status); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/fleet/vehicles/show/<?php echo $vehicle['id'] ?? ''; ?>" class="btn-icon" title="View Details">
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

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stats-grid > div {
    min-width: 0;
}

.stat-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: relative;

    min-width: 0;
    overflow: hidden;
}

.stat-card.total-value {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card.total-value .stat-number {
    color: white;
    overflow-wrap: anywhere;
    word-break: break-word;
    max-width: 100%;
}



.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-icon.vehicles {
    background: #e3f2fd;
    color: #1976d2;
}

.stat-icon.aircraft {
    background: #e8f5e9;
    color: #388e3c;
}

.stat-icon.marine {
    background: #e0f2f1;
    color: #00796b;
}

.stat-icon.motorcycles {
    background: #fff3e0;
    color: #f57c00;
}

.stat-icon.value {
    background: rgba(255,255,255,0.2);
    color: white;
}

.stat-icon.alert {
    background: #fff3e0;
    color: #f57c00;
}

.stat-details {
    flex: 1;
}

.stat-details h4 {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    color: #53665E;
}

.stat-card.total-value .stat-details h4 {
    color: rgba(255,255,255,0.9);
}

.stat-number {
    font-size: clamp(0.9rem, 1.35vw + 0.35rem, 1.6rem);
    font-weight: 400;
    margin: 0 0 5px 0;
    color: #134617;
    line-height: 1.2;
    overflow-wrap: anywhere;
    word-break: break-word;
    max-width: 100%;
}

.stat-sub {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.76rem;
    font-weight: 600;
    margin-top: 6px;
    flex-wrap: wrap;
}

.stat-sub .active {
    color: #1b5e20;
    background: #e8f5e9;
    padding: 2px 7px;
    border-radius: 4px;
    margin-right: 0;
    display: inline-flex;
    align-items: center;
}

.stat-sub .repair {
    color: #b78103;
    background: #fff8e1;
    padding: 2px 7px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
}

.stat-action {
    position: absolute;
    bottom: 12px;
    right: 14px;
}

.stat-action .btn-link {
    color: #134617;
    font-size: 0.85rem;
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

.stat-action .btn-link:hover {
    background: #134617;
    color: #ffffff;
    transform: translateX(2px);
}

.content-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #D7E3DC;
}

.section-title h2 {
    margin: 0;
    font-size: 1.2rem;
    color: #134617;
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-all {
    color: #207027;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
}

.view-all:hover {
    text-decoration: underline;
}

.table-responsive {
    overflow-x: auto;
}

.asset-table {
    width: 100%;
    border-collapse: collapse;
}

.asset-table th {
    background: var(--light-bg);
    padding: 12px 10px;
    text-align: left;
    font-weight: 600;
    color: #53665E;
    border-bottom: 2px solid #D7E3DC;
}

.asset-table td {
    padding: 10px;
    border-bottom: 1px solid #D7E3DC;
}

.asset-code {
    font-family: monospace;
    font-weight: 600;
    color: #134617;
    background: var(--light-bg);
    padding: 3px 6px;
    border-radius: 4px;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.status-pending {
    background: #e2e3e5;
    color: #383d41;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: var(--light-bg);
    color: #134617;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-icon:hover {
    background: #207027;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #53665E;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr !important;
    }
    
    .stat-number {
        font-size: 1.4rem !important;
        overflow-wrap: anywhere;
    word-break: break-word;
    max-width: 100%;
}
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
