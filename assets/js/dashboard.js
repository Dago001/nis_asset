/**
 * Dashboard Specific JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // This script is loaded on every page (views/layouts/footer.php), but its
    // stats/markup only exist on the dashboard itself. Without this guard,
    // every other page also polled /api/dashboard_stats and updateStats()
    // threw (stats.weapons.total on an unexpected/absent shape), spamming
    // the console with "Failed to refresh stats" on pages that have none of
    // these elements to update.
    if (!document.getElementById('total-weapons')) {
        return;
    }

    refreshDashboardStats();

    // Auto-refresh every 5 minutes
    setInterval(refreshDashboardStats, 300000);
});

function refreshDashboardStats() {
    ajaxRequest('/api/dashboard_stats')
        .then(data => {
            if (data.success) {
                updateStats(data.stats);
            }
        })
        .catch(error => console.error('Failed to refresh stats:', error));
}

function updateStats(stats) {
    if (!stats) return;

    // Update weapons stats
    updateStatCard('total-weapons', stats.weapons?.total);
    updateStatCard('issued-weapons', stats.weapons?.issued);
    updateStatCard('serviceable-weapons', stats.weapons?.serviceable);

    // Update ammunition stats
    updateStatCard('total-ammo', stats.ammunition?.total_types);
    updateStatCard('total-rounds', stats.ammunition?.total_rounds);
    updateStatCard('expiring-ammo', stats.ammunition?.expiring_soon);

    // Update asset stats
    updateStatCard('land-assets', stats.assets?.land);
    updateStatCard('buildings', stats.assets?.buildings);
    updateStatCard('vehicles', stats.fleet?.vehicles);

    // Update requisitions
    updateStatCard('pending-requisitions', stats.requisitions?.pending);

    // Update recent activity
    if (stats.recent_activity) {
        updateRecentActivity(stats.recent_activity);
    }
}

function updateStatCard(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = formatNumber(value);
        
        // Add animation
        element.classList.add('pulse');
        setTimeout(() => element.classList.remove('pulse'), 500);
    }
}

function updateRecentActivity(activities) {
    const container = document.getElementById('recent-activity');
    if (!container) return;
    
    container.innerHTML = '';
    
    activities.forEach(activity => {
        const item = document.createElement('div');
        item.className = 'activity-item';
        
        let icon = 'fa-info-circle';
        if (activity.action.includes('CREATE')) icon = 'fa-plus-circle text-success';
        else if (activity.action.includes('UPDATE')) icon = 'fa-edit text-warning';
        else if (activity.action.includes('DELETE')) icon = 'fa-trash text-danger';
        else if (activity.action.includes('LOGIN')) icon = 'fa-sign-in-alt text-info';
        
        item.innerHTML = `
            <div class="activity-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="activity-details">
                <div class="activity-title">${activity.action}</div>
                <div class="activity-description">
                    ${activity.description || ''}
                    ${activity.full_name ? `by ${activity.full_name}` : ''}
                </div>
            </div>
            <div class="activity-time">${timeAgo(activity.created_at)}</div>
        `;
        
        container.appendChild(item);
    });
}

function timeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diff = Math.floor((now - past) / 1000); // seconds
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    return Math.floor(diff / 86400) + ' days ago';
}