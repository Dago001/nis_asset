<?php
$title = 'Active Sessions';
$active = 'sessions';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$flashSuccess = $_SESSION['success'] ?? null;
$flashError   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

/** Very small UA sniff — just enough to say "Chrome on Windows" rather than
 *  dumping the raw user-agent string at an admin. */
function describeDevice(?string $ua): string {
    $ua = $ua ?? '';
    if ($ua === '') return 'Unknown device';

    $browser = 'Unknown browser';
    if (stripos($ua, 'Edg/') !== false) $browser = 'Edge';
    elseif (stripos($ua, 'Chrome/') !== false) $browser = 'Chrome';
    elseif (stripos($ua, 'Firefox/') !== false) $browser = 'Firefox';
    elseif (stripos($ua, 'Safari/') !== false) $browser = 'Safari';

    $os = 'Unknown OS';
    if (stripos($ua, 'Windows') !== false) $os = 'Windows';
    elseif (stripos($ua, 'Android') !== false) $os = 'Android';
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $os = 'iOS';
    elseif (stripos($ua, 'Mac OS') !== false) $os = 'macOS';
    elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';

    return "{$browser} on {$os}";
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return floor($diff / 86400) . ' day(s) ago';
}
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-user-clock"></i> Active Sessions</h1>
            <p><?php echo count($sessions); ?> session(s) active in the last 30 minutes</p>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
    <div class="alert-flash alert-flash-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flashSuccess); ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
    <div class="alert-flash alert-flash-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($flashError); ?></div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
    <div class="empty-state">
        <i class="fas fa-moon" style="font-size:2.5rem;color:var(--text-secondary);margin-bottom:12px;"></i>
        <p>No one else is currently active.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="asset-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Last Active</th>
                    <th>Session Started</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                <?php $isYou = ($s['session_id'] === $currentSessionId); ?>
                <tr<?php echo $isYou ? ' style="background:rgba(32,112,39,0.06);"' : ''; ?>>
                    <td>
                        <strong><?php echo htmlspecialchars($s['full_name'] ?? ''); ?></strong>
                        <br><small class="text-muted">@<?php echo htmlspecialchars($s['username'] ?? ''); ?></small>
                        <?php if ($isYou): ?><span class="badge badge-success">This device</span><?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($s['role_names'] ?? '—'); ?></td>
                    <td class="text-mono"><?php echo htmlspecialchars($s['ip_address'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars(describeDevice($s['user_agent'])); ?></td>
                    <td><?php echo htmlspecialchars(timeAgo($s['last_activity'])); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($s['created_at'])); ?></td>
                    <td>
                        <?php if (!$isYou): ?>
                        <a href="<?php echo BASE_URL; ?>/sessions/terminate/<?php echo urlencode($s['session_id']); ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('End this session for <?php echo htmlspecialchars(addslashes($s['full_name'] ?? 'this user')); ?>? They will be signed out immediately on their next action.')">
                            <i class="fas fa-power-off"></i> Terminate
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
.alert-flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
.alert-flash-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-flash-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
[data-theme="dark"] .alert-flash-success { background: rgba(16,185,129,0.16); color: #34d399; border-color: rgba(16,185,129,0.35); }
[data-theme="dark"] .alert-flash-error { background: rgba(239,68,68,0.14); color: #f87171; border-color: rgba(239,68,68,0.3); }
.btn-sm { padding: 5px 10px; font-size: 0.8rem; }
.btn-danger { background: var(--danger-color); color: #fff; border: none; border-radius: 5px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
.btn-danger:hover { background: #8f1c13; }
.text-mono { font-family: 'Courier New', monospace; font-size: 0.85rem; }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
