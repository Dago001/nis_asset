<?php
$title = 'Saved Reports';
$active = 'reports';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <i class="fas fa-bookmark"></i>
                Saved Reports
            </h1>
            <p>Access your saved report configurations</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/reports" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
        </div>
    </div>

    <!-- Saved Reports Grid -->
    <div class="content-card">
        <div class="section-title">
            <h2><i class="fas fa-list"></i> Your Saved Reports</h2>
        </div>

        <?php if (empty($reports)): ?>
            <div class="empty-state">
                <i class="fas fa-bookmark"></i>
                <p>You haven't saved any reports yet</p>
                <a href="<?php echo BASE_URL; ?>/reports" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> Generate a Report
                </a>
            </div>
        <?php else: ?>
            <div class="saved-reports-grid">
                <?php foreach ($reports as $report): ?>
                <div class="saved-report-card">
                    <div class="report-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="report-info">
                        <h3><?php echo Security::escape($report['report_name']); ?></h3>
                        <div class="report-meta">
                            <span class="report-type"><?php echo ucfirst($report['report_type']); ?></span>
                            <span class="report-date"><?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></span>
                        </div>
                        <?php 
                        $params = json_decode($report['parameters'], true);
                        if (!empty($params)):
                        ?>
                        <div class="report-params">
                            <small>Parameters:</small>
                            <?php foreach ($params as $key => $value): ?>
                                <span class="param-badge"><?php echo $key; ?>: <?php echo is_array($value) ? implode(', ', $value) : $value; ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
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
        <?php endif; ?>
    </div>
</div>

<style>
.saved-reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.saved-report-card {
    background: var(--surface);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
    border: 1px solid var(--border-color);
}

.saved-report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.report-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.report-info {
    flex: 1;
}

.report-info h3 {
    margin: 0 0 8px 0;
    font-size: 1rem;
    color: var(--text-primary);
}

.report-meta {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 0.8rem;
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

.report-params {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    align-items: center;
}

.report-params small {
    color: var(--text-secondary);
    margin-right: 5px;
}

.param-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
}

.report-actions {
    display: flex;
    gap: 5px;
    align-items: flex-start;
}

@media (max-width: 768px) {
    .saved-reports-grid {
        grid-template-columns: 1fr;
    }
    
    .saved-report-card {
        flex-direction: column;
    }
    
    .report-actions {
        justify-content: flex-end;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>