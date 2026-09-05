<?php
$title = 'Inventory Item Details';
$active = 'inventory';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$stockQty = $item['quantity_in_stock'] ?? $item['quantity'] ?? 0;
$reorderLvl = $item['reorder_level'] ?? 10;
?>

<div class="container-fluid inv-show-container">
    <div class="page-header">
        <div class="header-content">
            <div class="header-breadcrumb">
                <a href="<?php echo BASE_URL; ?>/inventory"><i class="fas fa-boxes-stacked"></i> General Stores & Inventory</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-current">Item Details</span>
            </div>
            <h1 class="page-title">
                <?php echo Security::escape($item['item_name'] ?? 'Inventory Item'); ?>
                <span class="header-badge-code" onclick="copyToClipboard('<?php echo Security::escape($item['item_code'] ?? 'INV-000'); ?>', 'Item Code')">
                    <?php echo Security::escape($item['item_code'] ?? 'N/A'); ?> <i class="fas fa-copy copy-icon"></i>
                </span>
            </h1>
        </div>
        <div class="header-actions">
            <a href="<?php echo BASE_URL; ?>/inventory" class="pro-btn pro-btn-secondary">
                <i class="fas fa-arrow-left"></i> <span>Back</span>
            </a>
            <?php if (Auth::can('inventory.edit')): ?>
            <a href="<?php echo BASE_URL; ?>/inventory/edit/<?php echo $item['id']; ?>" class="pro-btn pro-btn-primary">
                <i class="fas fa-pen-to-square"></i> <span>Edit Item</span>
            </a>
            <?php endif; ?>
            <button type="button" class="pro-btn pro-btn-outline" onclick="window.print()">
                <i class="fas fa-print"></i> <span>Print</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Metrics Bar -->
    <div class="kpi-metrics-grid">
        <div class="kpi-card">
            <div class="kpi-icon icon-serial"><i class="fas fa-barcode"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">SKU / Item Code</span>
                <span class="kpi-value text-mono font-medium"><?php echo Security::escape($item['item_code'] ?? 'N/A'); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-condition"><i class="fas fa-cubes"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Current Stock Balance</span>
                <span class="kpi-value font-medium <?php echo $stockQty <= $reorderLvl ? 'text-danger' : 'text-success'; ?>">
                    <?php echo number_format($stockQty); ?> Units
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-location"><i class="fas fa-warehouse"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Store Location</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($item['location'] ?? 'Central Store'); ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-calibre"><i class="fas fa-tag"></i></div>
            <div class="kpi-details">
                <span class="kpi-label">Item Category</span>
                <span class="kpi-value font-medium"><?php echo Security::escape($item['category'] ?? 'General Stores'); ?></span>
            </div>
        </div>
    </div>

    <?php if ($stockQty <= $reorderLvl): ?>
    <div class="pro-alert-banner alert-warning">
        <div class="alert-icon"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="alert-body">
            <h4>Low Inventory Reorder Alert</h4>
            <p>Current stock balance (<strong><?php echo number_format($stockQty); ?> units</strong>) is at or below the reorder threshold (<?php echo number_format($reorderLvl); ?> units).</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="show-layout-grid">
        <div class="show-main-column">
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-sliders"></i> Inventory Specifications</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-detail-grid">
                        <div class="pro-detail-item">
                            <span class="item-label">Item Code</span>
                            <span class="item-value text-mono font-medium"><?php echo Security::escape($item['item_code'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="pro-detail-item">
                            <span class="item-label">Item Description</span>
                            <span class="item-value font-medium"><?php echo Security::escape($item['item_name'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="show-sidebar-column">
            <div class="pro-card">
                <div class="pro-card-header">
                    <h3><i class="fas fa-database"></i> Record Metadata</h3>
                </div>
                <div class="pro-card-body">
                    <div class="pro-side-item">
                        <span class="side-label">Logged By</span>
                        <span class="side-value font-medium"><?php echo Security::escape($item['created_by_name'] ?? 'System Admin'); ?></span>
                    </div>
                    <div class="pro-side-item">
                        <span class="side-label">Created Timestamp</span>
                        <span class="side-value text-mono small"><?php echo date('d/m/Y H:i:s', strtotime($item['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pro-bottom-actions">
        <?php if (Auth::can('inventory.edit')): ?>
        <a href="<?php echo BASE_URL; ?>/inventory/edit/<?php echo $item['id']; ?>" class="pro-btn pro-btn-primary">
            <i class="fas fa-pen-to-square"></i> <span>Edit Item</span>
        </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/inventory" class="pro-btn pro-btn-secondary">
            <i class="fas fa-arrow-left"></i> <span>Back</span>
        </a>
    </div>
</div>

<div id="copyToast" class="copy-toast"></div>

<style>
:root { --nis-forest: #134617; --nis-emerald: #2E7D32; --card-bg: #FFFFFF; --border-light: #E2E8F0; --text-dark: #0F172A; --text-muted: #64748B; }
[data-theme="dark"] {
    --nis-forest: #299631;
    --nis-emerald: #52bf57;
    --card-bg: #1f1f1f;
    --border-light: #2b323b;
    --text-dark: #d9dde8;
    --text-muted: #dee0e3;
}

.inv-show-container { padding-bottom: 40px; }
.inv-show-container .page-header { display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 16px !important; background: #ffffff !important; padding: 20px 24px !important; border-radius: 12px !important; border: 1px solid #E2E8F0 !important; margin-bottom: 24px !important; }
.inv-show-container .header-content { flex: 1 1 280px !important; }
.inv-show-container .header-content h1 { font-size: 1.5rem !important; font-weight: 700 !important; color: #0F172A !important; margin: 4px 0 0 0 !important; display: flex !important; align-items: center !important; gap: 10px !important; }
.header-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px; }
.header-breadcrumb a { color: var(--nis-emerald); text-decoration: none; font-weight: 500; }
.breadcrumb-separator { font-size: 0.7rem; color: #94A3B8; }
.header-badge-code { display: inline-flex; align-items: center; gap: 6px; background: #F1F5F9; color: var(--nis-forest); border: 1px solid #CBD5E1; font-family: 'SF Mono', monospace; font-size: 0.95rem; padding: 3px 10px; border-radius: 6px; cursor: pointer; }
.pro-btn { display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; padding: 9px 18px !important; font-size: 0.88rem !important; font-weight: 600 !important; border-radius: 8px !important; white-space: nowrap !important; height: 40px !important; border: 1px solid transparent !important; }
.pro-btn span { display: inline-block !important; color: inherit !important; background: transparent !important; }
.pro-btn i { font-size: 0.95rem !important; color: inherit !important; }
.pro-btn-secondary { background: #F1F5F9 !important; color: #334155 !important; border-color: #CBD5E1 !important; }
.pro-btn-primary { background: #134617 !important; color: #FFFFFF !important; }
.pro-btn-outline { background: #FFFFFF !important; color: #0F172A !important; border-color: #94A3B8 !important; }
.inv-show-container .header-actions { display: flex !important; align-items: center !important; gap: 10px !important; }
.kpi-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; }
.kpi-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.icon-serial { background: #E0F2FE; color: #0284C7; }
.icon-condition { background: #DCFCE7; color: #16A34A; }
.icon-location { background: #FEF3C7; color: #D97706; }
.icon-calibre { background: #F3E8FF; color: #9333EA; }
.kpi-details { display: flex; flex-direction: column; gap: 2px; }
.kpi-label { font-size: 0.78rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted); }
.kpi-value { font-size: 1rem; color: var(--text-dark); }
.custom-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; }
.badge-warning { background: #FEF08A; color: #713F12; }
.show-layout-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 24px; margin-bottom: 24px; }
.pro-card { background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; margin-bottom: 24px; overflow: hidden; }
.pro-card-header { padding: 16px 20px; background: #F8FAFC; border-bottom: 1px solid var(--border-light); }
.pro-card-header h3 { margin: 0; font-size: 1.05rem; font-weight: 600; color: var(--nis-forest); display: flex; align-items: center; gap: 10px; }
.pro-card-body { padding: 20px; }
.pro-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px 24px; }
.pro-detail-item { display: flex; flex-direction: column; gap: 4px; }
.item-label { font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
.item-value { font-size: 0.95rem; color: var(--text-dark); }
.pro-side-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F1F5F9; }
.side-label { font-size: 0.85rem; color: var(--text-muted); }
.side-value { font-size: 0.9rem; color: var(--text-dark); }
.text-mono { font-family: 'SF Mono', monospace; }
.bold { font-weight: 700; }
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
.pro-bottom-actions { display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-light); }
.copy-toast { position: fixed; bottom: 24px; right: 24px; background: #0F172A; color: white; padding: 10px 18px; border-radius: 8px; opacity: 0; pointer-events: none; z-index: 9999; }
.copy-toast.show { opacity: 1; }

@media (max-width: 1024px) { .show-layout-grid { grid-template-columns: 1fr; } .kpi-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .inv-show-container .page-header { flex-direction: column !important; align-items: stretch !important; padding: 16px !important; gap: 14px !important; }
    .inv-show-container .header-content { width: 100% !important; }
    .inv-show-container .header-actions { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; width: 100% !important; }
    .inv-show-container .header-actions .pro-btn { width: 100% !important; padding: 8px 6px !important; font-size: 0.8rem !important; }
    .pro-detail-grid { grid-template-columns: 1fr; }
    .pro-bottom-actions { flex-direction: column; }
    .pro-bottom-actions .pro-btn { width: 100%; }
}
@media (max-width: 480px) { .kpi-metrics-grid { grid-template-columns: 1fr; } .inv-show-container .header-actions { grid-template-columns: 1fr !important; } }
</style>

<script>
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
