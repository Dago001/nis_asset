<?php
$title = 'Reports Dashboard';
$active = 'reports';
$init_charts = true;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-chart-bar"></i>
                Reports Dashboard
            </h1>
            <p>Generate and view system reports</p>
        </div>
        <div class="header-actions">
            <?php if (Auth::can('reports.export')): ?>
            <a href="<?php echo BASE_URL; ?>/reports/saved" class="btn btn-info">
                <i class="fas fa-bookmark"></i> Saved Reports
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Report Cards -->
    <div class="report-categories">
        <h2><i class="fas fa-bolt"></i>Reports</h2>
        <div class="category-grid">
            <a href="<?php echo BASE_URL; ?>/reports/summary" class="category-card">
                <div class="category-icon executive">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="category-details">
                    <h3>Summary</h3>
                    <p>High-level overview of all assets with key statistics</p>
                </div>
                <div class="category-action">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="<?php echo BASE_URL; ?>/reports/assets" class="category-card">
                <div class="category-icon assets">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="category-details">
                    <h3>Asset Reports</h3>
                    <p>Comprehensive reports on all asset categories</p>
                </div>
                <div class="category-action">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="<?php echo BASE_URL; ?>/reports/weapons" class="category-card">
                <div class="category-icon weapons">
                    <i class="fas fa-gun"></i>
                </div>
                <div class="category-details">
                    <h3>Weapons Reports</h3>
                    <p>Weapons inventory, issuance, and status reports</p>
                </div>
                <div class="category-action">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="<?php echo BASE_URL; ?>/reports/ammunition" class="category-card">
                <div class="category-icon ammo">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="category-details">
                    <h3>Ammunition Reports</h3>
                    <p>Ammunition stock levels, expiry tracking, and usage</p>
                </div>
                <div class="category-action">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="<?php echo BASE_URL; ?>/reports/fleet" class="category-card">
                <div class="category-icon fleet">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="category-details">
                    <h3>Fleet Reports</h3>
                    <p>Vehicles, aircraft, marine, and motorcycles reports</p>
                </div>
                <div class="category-action">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="<?php echo BASE_URL; ?>/audit/history" class="category-card">
                <div class="category-icon audit">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="category-details">
                    <h3>Audit Reports</h3>
                    <p>Quarterly audit results and variance reports</p>
                </div>
                <div class="category-action">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Saved Reports Section -->
    <?php if (!empty($recentReports)): ?>
    <div class="saved-reports">
        <h2><i class="fas fa-bookmark"></i> Recent Saved Reports</h2>
        <div class="reports-grid">
            <?php foreach ($recentReports as $report): ?>
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-file-alt"></i>
                    <h3><?php echo Security::escape($report['report_name']); ?></h3>
                </div>
                <div class="report-meta">
                    <span class="report-type"><?php echo Security::escape($report['report_type']); ?></span>
                    <span class="report-date"><?php echo date('d/m/Y', strtotime($report['created_at'])); ?></span>
                </div>
                <div class="report-actions">
                    <a href="<?php echo BASE_URL; ?>/reports/load/<?php echo $report['id']; ?>" class="btn-icon" title="Load Report">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/reports/delete/<?php echo $report['id']; ?>" class="btn-icon delete" title="Delete" onclick="return confirm('Delete this saved report?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($recentReports) > 4): ?>
        <div class="view-all">
            <a href="<?php echo BASE_URL; ?>/reports/saved">View All Saved Reports <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    

<style>
.report-categories,
.saved-reports,
.report-templates {
    margin-bottom: 40px;
}

.report-categories h2,
.saved-reports h2,
.report-templates h2 {
    font-size: 1.3rem;
    color: var(--text-primary);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.category-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.3s;
    position: relative;
}

.category-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.category-card:hover .category-action i {
    transform: translateX(5px);
}

.category-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
}

.category-icon.executive {
    background: #e3f2fd;
    color: #1976d2;
}

.category-icon.assets {
    background: #e8f5e9;
    color: #388e3c;
}

.category-icon.weapons {
    background: #ffebee;
    color: #c62828;
}

.category-icon.ammo {
    background: #fff3e0;
    color: #f57c00;
}

.category-icon.fleet {
    background: #e0f2f1;
    color: #00796b;
}

.category-icon.audit {
    background: #d1c4e9;
    color: #512da8;
}

.category-details {
    flex: 1;
}

.category-details h3 {
    margin: 0 0 5px 0;
    font-size: 1rem;
    color: var(--text-primary);
}

.category-details p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.category-action {
    color: var(--text-secondary);
    transition: all 0.3s;
}

.category-action i {
    transition: transform 0.3s;
}

/* Saved Reports */
.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 15px;
}

.report-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.report-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.report-header i {
    font-size: 1.5rem;
    color: var(--success-color);
}

.report-header h3 {
    margin: 0;
    font-size: 1rem;
    color: var(--text-primary);
    flex: 1;
}

.report-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 0.85rem;
}

.report-type {
    background: var(--light-bg);
    padding: 3px 8px;
    border-radius: 4px;
    color: var(--text-secondary);
}

.report-date {
    color: var(--text-light);
}

.report-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.view-all {
    text-align: right;
}

.view-all a {
    color: var(--success-color);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}

.view-all a:hover {
    text-decoration: underline;
}

/* Templates */
.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.template-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: relative;
}

.template-icon {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.template-icon.pdf {
    background: #ffebee;
    color: #c62828;
}

.template-icon.excel {
    background: #e8f5e9;
    color: #2e7d32;
}

.template-icon.csv {
    background: #e3f2fd;
    color: #1565c0;
}

.template-details {
    flex: 1;
}

.template-details h3 {
    margin: 0 0 5px 0;
    font-size: 1rem;
    color: var(--text-primary);
}

.template-details p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.template-badge {
    position: absolute;
    top: -5px;
    right: 10px;
    background: var(--success-color);
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .category-grid,
    .reports-grid,
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .category-card {
        padding: 15px;
    }
    
    .category-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>