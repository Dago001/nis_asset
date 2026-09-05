<?php
/**
 * Header Layout
 */

// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/nis_ams');
}

// Get CSRF token safely
$csrfToken = '';
if (class_exists('Security')) {
    try {
        $csrfToken = Security::csrfToken();
    } catch (Exception $e) {
        $csrfToken = '';
    }
}

// Get app name safely
$appName = 'NIS Asset Management System';
if (class_exists('Config')) {
    try {
        $appName = Config::get('app_name', 'NIS Asset Management System');
    } catch (Exception $e) {
        $appName = 'NIS Asset Management System';
    }
}

// System-wide default theme (Customization → Default Theme). A per-browser
// override in localStorage, applied by theme.js before paint, wins over this.
$defaultTheme = 'light';
if (class_exists('Config')) {
    try {
        $t = Config::get('default_theme', 'light');
        $defaultTheme = ($t === 'dark') ? 'dark' : 'light';
    } catch (Exception $e) {
        $defaultTheme = 'light';
    }
}

// Customization → Primary brand color. Only accept a real #hex so a bad
// setting value can't inject arbitrary CSS.
$primaryColor = null;
if (class_exists('Config')) {
    try {
        $pc = (string) Config::get('primary_color', '');
        if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $pc)) {
            $primaryColor = $pc;
        }
    } catch (Exception $e) {
        $primaryColor = null;
    }
}

$pageTitle = $title ?? 'Dashboard';

// Public auth pages (login, 2FA, forgot/reset password, unauthorized) render
// as standalone screens — no logged-in topbar (notification bell, account
// menu), since there is no account yet / it isn't relevant.
$isAuthPage = in_array($pageTitle, [
    'Login', 'Two-Factor Authentication', 'Verifying Location', 'Forgot Password', 'Reset Password', 'Unauthorized',
], true);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $defaultTheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, viewport-fit=cover">
    <!-- Native mobile-app hints (installable / full-bleed / branded status bar) -->
    <meta name="theme-color" content="#134617">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="NIS AMS">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/nis-logo.png">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
    <meta name="app-base" content="<?php echo htmlspecialchars(rtrim(BASE_URL, '/')); ?>">
    <title><?php echo htmlspecialchars($appName); ?> - <?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/images/nis-logo.png" type="image/png">

    <!-- Critical background to prevent white flash before stylesheets paint -->
    <style>
        html, body {
            background-color: #f8f9fa;
            color: #212529;
            margin: 0;
            padding: 0;
        }
        html[data-theme="dark"], html[data-theme="dark"] body {
            background-color: #121814;
            color: #e9f2e7;
        }
    </style>

    <!-- Synchronous theme detection (0ms, no network delay) -->
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('nis_ams_theme');
                if (stored === 'light' || stored === 'dark') {
                    document.documentElement.setAttribute('data-theme', stored);
                }
            } catch (e) {}
        })();
    </script>

    <!-- Stylesheets (Loaded first for immediate visual paint) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/fontawesome.min.css?v=6.0.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/leaflet.css?v=1.9.4">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/app.css?v=1.0.9">
    <!-- Mobile / native-app layer — only affects phones & tablets (all rules are
         inside max-width media queries); loaded after app.css so it layers on top. -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/mobile.css?v=1.0.0">
    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>?v=1.0.5">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Heavy JavaScript (Deferred so they do NOT block page rendering or cause white screen) -->
    <script src="<?php echo BASE_URL; ?>/assets/js/theme.js?v=1.0.0" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/chart.min.js?v=4.0.0" defer></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/leaflet.js?v=1.9.4" defer></script>

    <?php if ($primaryColor): ?>
    <!-- Customization → Primary brand color override. --primary-light and
         --secondary-color are derived (lightened) so hover/accent states
         still read as "the same brand color", not two unrelated hues. -->
    <?php
        $hex = ltrim($primaryColor, '#');
        if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
        $lighten = function ($r, $g, $b, $amount) {
            $r = min(255, $r + (255 - $r) * $amount);
            $g = min(255, $g + (255 - $g) * $amount);
            $b = min(255, $b + (255 - $b) * $amount);
            return sprintf('#%02x%02x%02x', $r, $g, $b);
        };
        $primaryLight = $lighten($r, $g, $b, 0.28);
    ?>
    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
            --primary-light: <?php echo $primaryLight; ?>;
            --secondary-color: <?php echo $primaryLight; ?>;
            /* --text-primary and --success-color are intentionally NOT
               overridden here.
               --text-primary is the general body/heading/table text color
               (see app.css's :root) and must stay a fixed neutral dark
               regardless of the chosen brand accent — reusing the
               customizable brand color for it made all page text render in
               whatever accent an admin picked instead of black.
               --success-color is a semantic status color (used as readable
               foreground text — button labels, code-style "key" chips,
               "Developed"/"Active" style badges — not just tints), and was
               being set to $primaryLight, a 28%-toward-white lighten of the
               chosen accent. That lighten amount reads fine for a subtle
               hover tint but is too pale for foreground text on a plain
               white background — exactly the washed-out/dim look reported
               on pages like Settings (Export/Import buttons, setting-key
               chips). Leaving it alone falls back to app.css's own
               hand-picked, fully-saturated default (or the dark theme's
               own value in dark mode). */
        }
    </style>
    <?php endif; ?>
    

    
    <!-- Notification Styles -->
    <style>
    /* Notification styles */
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 400px;
    }

    .notification {
        background: white;
        border-radius: 10px;
        padding: 16px 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.3s ease;
        border-left: 4px solid;
        position: relative;
        overflow: hidden;
    }

    .notification::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        width: 100%;
        animation: progress 5s linear forwards;
    }

    .notification.success {
        border-left-color: #207027;
        background: #f0f9f4;
    }

    .notification.success::after {
        background: #207027;
    }

    .notification.success i {
        color: #207027;
    }

    .notification.error {
        border-left-color: #B42318;
        background: #fef2f2;
    }

    .notification.error::after {
        background: #B42318;
    }

    .notification.error i {
        color: #B42318;
    }

    .notification.warning {
        border-left-color: #C69214;
        background: #fff8e6;
    }

    .notification.warning::after {
        background: #C69214;
    }

    .notification.warning i {
        color: #C69214;
    }

    .notification.info {
        border-left-color: #1F6F8B;
        background: #e8f4fd;
    }

    .notification.info::after {
        background: #1F6F8B;
    }

    .notification.info i {
        color: #1F6F8B;
    }

    .notification-icon {
        font-size: 1.5rem;
    }

    .notification-content {
        flex: 1;
    }

    .notification-title {
        font-weight: 700;
        margin-bottom: 4px;
        color: #134617;
    }

    .notification-message {
        font-size: 0.9rem;
        color: #4a5568;
    }

    .notification-close {
        background: transparent;
        border: none;
        color: #999;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0 5px;
        transition: color 0.3s;
    }

    .notification-close:hover {
        color: #134617;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes progress {
        from {
            width: 100%;
        }
        to {
            width: 0%;
        }
    }

    /* Toast notifications had no dark-theme coverage at all — same tinted
       backgrounds app.css already uses for .alert-success/.badge-* etc. */
    [data-theme="dark"] .notification {
        background: var(--surface);
    }
    [data-theme="dark"] .notification-title {
        color: var(--text-primary);
    }
    [data-theme="dark"] .notification-message {
        color: var(--text-secondary);
    }
    [data-theme="dark"] .notification-close {
        color: var(--text-secondary);
    }
    [data-theme="dark"] .notification-close:hover {
        color: var(--text-primary);
    }
    [data-theme="dark"] .notification.success {
        background: rgba(59, 181, 74, 0.16);
    }
    [data-theme="dark"] .notification.error {
        background: rgba(231, 86, 75, 0.16);
    }
    [data-theme="dark"] .notification.warning {
        background: rgba(255, 179, 71, 0.16);
    }
    [data-theme="dark"] .notification.info {
        background: rgba(93, 173, 226, 0.16);
    }
    </style>
    
    <!-- Header Notification Bell & Dropdown Styles -->
    <style>
    .bell-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        font-size: 1.15rem;
        color: #ffffff;
        cursor: pointer;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        transition: all 0.2s ease;
        outline: none;
    }
    .bell-btn:hover {
        background: rgba(255, 255, 255, 0.22);
        transform: scale(1.05);
    }
    .bell-badge {
        position: absolute;
        top: -3px;
        right: -3px;
        background: #dc2626;
        color: #ffffff;
        font-size: 0.65rem;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        line-height: 18px;
        padding: 0 4px;
        border-radius: 9px;
        text-align: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.35);
        border: 1.5px solid #134617;
        box-sizing: border-box;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .notification-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: var(--surface, #ffffff);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        width: 360px;
        max-width: 90vw;
        z-index: 10000;
        border: 1px solid var(--border-color, #e2e8f0);
        overflow: hidden;
        animation: notifFadeIn 0.2s ease-out;
    }
    @keyframes notifFadeIn {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .notif-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color, #f1f5f9);
        color: var(--text-primary, #1e293b);
        text-decoration: none;
        transition: background 0.15s ease;
        position: relative;
    }
    .notif-item:hover {
        background: var(--light-bg, #f8fafc);
    }
    .notif-item:last-child {
        border-bottom: none;
    }
    [data-theme="dark"] .notif-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    [data-theme="dark"] .notification-dropdown {
        background: var(--surface);
        border-color: var(--border-color);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
    }
    </style>
    
    <!-- Ensure overlay is hidden by default -->
    <style>
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }
        
        .sidebar {
            transition: transform 0.3s ease;
        }
        
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <?php if (!$isAuthPage): ?>
    <!-- Header -->
    <header>
        <div class="header-container">
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="logo-title-container">
                <img src="<?php echo BASE_URL; ?>/assets/images/nis-logo-white.png" alt="NIS Logo" class="header-logo" onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/assets/images/nis-logo.png';">
                <div class="title-container">
                    <h1>
                        <span class="title-full"><?php echo htmlspecialchars($appName); ?></span>
                        <span class="title-short">NIS AMS</span>
                    </h1>
                    <h2>Works & Logistics</h2>
                </div>
            </div>

            <!-- User Menu -->
            <div class="admin-profile-container" style="position: relative; display: flex; align-items: center; gap: 15px;">
                <!-- Notification Bell -->
                <div class="notification-bell-container" style="position: relative; display: inline-block;">
                    <button class="bell-btn" id="bellBtn" type="button" aria-label="Notifications" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="bell-badge" id="bellBadge" style="display: none;">0</span>
                    </button>
                    <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--light-bg);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-weight: 700; font-size: 0.88rem; color: var(--text-primary);">Notifications</span>
                                <span id="notifBadgeCount" style="background: rgba(19, 70, 23, 0.12); color: var(--primary-color, #134617); font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 10px;">0 new</span>
                            </div>
                            <button type="button" id="markAllReadBtn" onclick="markAllNotificationsAsRead(event)" style="background: transparent; border: none; color: var(--primary-color, #134617); font-size: 0.75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; padding: 3px 6px; border-radius: 4px;">
                                <i class="fas fa-check-double"></i> Mark all as read
                            </button>
                        </div>
                        <div id="notificationsList" style="display: flex; flex-direction: column; max-height: 380px; overflow-y: auto;">
                            <div style="padding: 30px 20px; text-align: center; color: var(--text-secondary);">
                                <i class="far fa-bell-slash" style="font-size: 1.8rem; margin-bottom: 8px; opacity: 0.4; display: block;"></i>
                                <div style="font-size: 0.82rem; font-weight: 500;">No unread notifications</div>
                            </div>
                        </div>
                        <div style="padding: 8px 16px; background: var(--light-bg); border-top: 1px solid var(--border-color); text-align: center;">
                            <a href="<?php echo BASE_URL; ?>/requisition" style="color: var(--primary-color, #134617); font-size: 0.78rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-clipboard-list"></i> View All Requisitions & Requests
                            </a>
                        </div>
                    </div>
                </div>

                <?php 
                $baseDir = defined('BASE_PATH') ? BASE_PATH : (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2));
                
                // Fetch profile_image into session if not yet loaded in this session
                if (!isset($_SESSION['profile_image']) && !empty($_SESSION['user_id'])) {
                    $uImg = Database::fetchOne("SELECT profile_image FROM users WHERE id = ?", [$_SESSION['user_id']]);
                    $_SESSION['profile_image'] = (!empty($uImg['profile_image'])) ? $uImg['profile_image'] : '';
                }
                
                $profileImgPath = $_SESSION['profile_image'] ?? '';
                $headerAvatar = (!empty($profileImgPath) && file_exists($baseDir . '/' . $profileImgPath))
                    ? BASE_URL . '/' . htmlspecialchars($profileImgPath)
                    : null;
                ?>
                <button class="admin-profile-btn" id="adminProfileBtn" style="background: linear-gradient(135deg, #134617 0%, #207027 100%); color: white; border: none; border-radius: 6px; padding: 6px 12px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;">
                    <?php if ($headerAvatar): ?>
                        <img src="<?php echo $headerAvatar; ?>" alt="Avatar" width="26" height="26" style="width: 26px; height: 26px; border-radius: 50%; object-fit: cover; border: 1.5px solid rgba(255,255,255,0.8); flex-shrink: 0; display: inline-block;">
                    <?php else: ?>
                        <i class="fas fa-user-shield"></i>
                    <?php endif; ?>
                    <span>
                        <?php
                        echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator');
                        if (!empty($_SESSION['command_id'])) {
                            if (!isset($_SESSION['command_name'])) {
                                $cmd = Database::fetchOne("SELECT command_name FROM commands WHERE id = ?", [$_SESSION['command_id']]);
                                $_SESSION['command_name'] = $cmd['command_name'] ?? 'Unknown Command';
                            }
                            echo ' (' . htmlspecialchars($_SESSION['command_name']) . ')';
                        }
                        ?>
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="admin-profile-menu" id="adminProfileMenu" style="display: none; position: absolute; top: 100%; right: 0; background: var(--surface); border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); min-width: 220px; z-index: 9999; margin-top: 10px; border: 1px solid var(--border-color);">
                    <div style="padding: 12px; background: linear-gradient(135deg, #134617 0%, #207027 100%); color: white; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px;">
                        <?php if ($headerAvatar): ?>
                            <img src="<?php echo $headerAvatar; ?>" alt="Avatar" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.8); flex-shrink: 0;">
                        <?php else: ?>
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        <?php endif; ?>
                        <div style="min-width: 0;">
                            <div style="font-weight: 600; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></div>
                            <div style="font-size: 0.78rem; opacity: 0.9; margin-bottom: 2px;"><?php echo htmlspecialchars(implode(', ', $_SESSION['roles'] ?? ['Staff'])); ?></div>
                            <?php if (!empty($_SESSION['command_name'])): ?>
                                <div style="font-size: 0.72rem; opacity: 0.85; font-style: italic;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($_SESSION['command_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="<?php echo BASE_URL; ?>/users/profile" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--text-primary); text-decoration: none; border-left: 3px solid transparent; font-size: 0.9rem;">
                        <i class="fas fa-user-circle" style="width: 16px; color: var(--primary-light);"></i>
                        <span>My Profile</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/auth/change-password" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--text-primary); text-decoration: none; border-left: 3px solid transparent; font-size: 0.9rem;">
                        <i class="fas fa-key" style="width: 16px; color: var(--primary-light);"></i>
                        <span>Change Password</span>
                    </a>

                    <?php if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin']): ?>
                        <div style="height: 1px; background: var(--border-color); margin: 5px 0;"></div>
                        <a href="<?php echo BASE_URL; ?>/users" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--text-primary); text-decoration: none; border-left: 3px solid transparent; font-size: 0.9rem;">
                            <i class="fas fa-users-cog" style="width: 16px; color: var(--primary-light);"></i>
                            <span>User Management</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/settings" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--text-primary); text-decoration: none; border-left: 3px solid transparent; font-size: 0.9rem;">
                            <i class="fas fa-cogs" style="width: 16px; color: var(--primary-light);"></i>
                            <span>System Settings</span>
                        </a>
                    <?php endif; ?>

                    <div style="height: 1px; background: var(--border-color); margin: 5px 0;"></div>

                    <a href="<?php echo BASE_URL; ?>/auth/logout" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--danger-color); text-decoration: none; border-left: 3px solid transparent; font-size: 0.9rem;">
                        <i class="fas fa-sign-out-alt" style="width: 16px; color: var(--danger-color);"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php endif; ?>


    <!-- Simple JavaScript for Dropdown -->
    <script>
        // Simple dropdown toggle
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded - initializing dropdown');
            
            const profileBtn = document.getElementById('adminProfileBtn');
            const profileMenu = document.getElementById('adminProfileMenu');

            // Notification Bell dropdown functionality
            const bellBtn = document.getElementById('bellBtn');
            const bellBadge = document.getElementById('bellBadge');
            const bellDropdown = document.getElementById('notificationDropdown');
            const notificationsList = document.getElementById('notificationsList');
            const notifBadgeCount = document.getElementById('notifBadgeCount');
            
            if (bellBtn && bellDropdown) {
                bellDropdown.style.display = 'none';

                // Notification → "Play sound for notifications"
                const notificationSoundEnabled = <?php echo (class_exists('Config') && Config::get('notification_sound', false)) ? 'true' : 'false'; ?>;
                let lastNotifCount = null;

                function playNotificationSound() {
                    try {
                        const Ctx = window.AudioContext || window.webkitAudioContext;
                        if (!Ctx) return;
                        const ctx = new Ctx();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(880, ctx.currentTime);
                        gain.gain.setValueAtTime(0.15, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                        osc.connect(gain).connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.35);
                    } catch (e) { /* audio unavailable */ }
                }

                // Fetch unread notifications
                function fetchNotifications() {
                    fetch('<?php echo BASE_URL; ?>/api/notifications/unread')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const count = data.data.count || 0;
                                if (notificationSoundEnabled && lastNotifCount !== null && count > lastNotifCount) {
                                    playNotificationSound();
                                }
                                lastNotifCount = count;
                                
                                if (count > 0) {
                                    bellBadge.innerText = count > 99 ? '99+' : count;
                                    bellBadge.style.display = 'inline-flex';
                                    if (notifBadgeCount) notifBadgeCount.innerText = count + ' new';
                                } else {
                                    bellBadge.style.display = 'none';
                                    bellBadge.innerText = '0';
                                    if (notifBadgeCount) notifBadgeCount.innerText = '0 new';
                                }

                                const notifs = data.data.notifications || [];
                                if (notifs.length > 0) {
                                    notificationsList.innerHTML = notifs.map(n => `
                                        <a href="${n.link}" onclick="handleNotifClick('${n.id}', '${n.link}', event)" class="notif-item">
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: ${n.icon_bg}; color: ${n.icon_color}; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0; margin-top: 2px;">
                                                <i class="${n.icon}"></i>
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 3px;">
                                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 1px 6px; border-radius: 4px; background: ${n.badge_bg}; color: ${n.badge_color}; text-transform: uppercase; letter-spacing: 0.3px;">${n.badge}</span>
                                                    <span style="font-size: 0.68rem; color: var(--text-secondary); white-space: nowrap;"><i class="far fa-clock" style="margin-right: 2px;"></i> ${n.time_ago}</span>
                                                </div>
                                                <div style="font-size: 0.8rem; font-weight: 500; line-height: 1.35; color: var(--text-primary); word-break: break-word;">${n.message}</div>
                                            </div>
                                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #2563eb; flex-shrink: 0; margin-top: 8px;"></span>
                                        </a>
                                    `).join('');
                                } else {
                                    notificationsList.innerHTML = `
                                        <div style="padding: 30px 20px; text-align: center; color: var(--text-secondary);">
                                            <i class="far fa-bell-slash" style="font-size: 1.8rem; margin-bottom: 8px; opacity: 0.4; display: block;"></i>
                                            <div style="font-size: 0.82rem; font-weight: 500;">No unread notifications</div>
                                        </div>`;
                                }
                            }
                        })
                        .catch(err => console.error('Error fetching notifications:', err));
                }

                // Handle single notification click: mark read and redirect
                window.handleNotifClick = function(id, link, event) {
                    event.preventDefault();
                    fetch('<?php echo BASE_URL; ?>/notifications/mark-read/' + encodeURIComponent(id))
                        .then(() => {
                            window.location.href = link;
                        })
                        .catch(() => {
                            window.location.href = link;
                        });
                };
                
                // Fetch initially
                fetchNotifications();
                
                // Poll every 15 seconds
                setInterval(fetchNotifications, 15000);
                
                bellBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (bellDropdown.style.display === 'none' || bellDropdown.style.display === '') {
                        bellDropdown.style.display = 'block';
                        // Close profile menu if open
                        if (profileMenu) profileMenu.style.display = 'none';
                    } else {
                        bellDropdown.style.display = 'none';
                    }
                };
                
                // Close when clicking outside
                document.addEventListener('click', function(e) {
                    if (!bellBtn.contains(e.target) && !bellDropdown.contains(e.target)) {
                        bellDropdown.style.display = 'none';
                    }
                });
            }
            
            // Mark all notifications as read function
            window.markAllNotificationsAsRead = function(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken); ?>');
                
                fetch('<?php echo BASE_URL; ?>/notifications/mark-all-read', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bellBadge.style.display = 'none';
                        bellBadge.innerText = '0';
                        if (notifBadgeCount) notifBadgeCount.innerText = '0 new';
                        notificationsList.innerHTML = `
                            <div style="padding: 30px 20px; text-align: center; color: var(--text-secondary);">
                                <i class="far fa-bell-slash" style="font-size: 1.8rem; margin-bottom: 8px; opacity: 0.4; display: block;"></i>
                                <div style="font-size: 0.82rem; font-weight: 500;">No unread notifications</div>
                            </div>`;
                    }
                })
                .catch(err => console.error('Error marking all as read:', err));
            };
            
            if (profileBtn && profileMenu) {
                console.log('Profile button found');
                
                // Set initial state
                profileMenu.style.display = 'none';
                
                // Toggle on click
                profileBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (profileMenu.style.display === 'none' || profileMenu.style.display === '') {
                        profileMenu.style.display = 'block';
                        console.log('Menu opened');
                    } else {
                        profileMenu.style.display = 'none';
                        console.log('Menu closed');
                    }
                };
                
                // Close when clicking outside
                document.onclick = function(e) {
                    if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                        profileMenu.style.display = 'none';
                    }
                };
            } else {
                console.log('Profile button or menu not found');
            }

            // Mobile menu toggle: handled solely by views/layouts/sidebar.php
            // (addEventListener there). This file used to attach a second,
            // independent .onclick handler to the same #mobileMenuToggle /
            // #sidebarOverlay elements — both fired on every click, each
            // reading and rewriting classList/inline styles the other had
            // just changed, leaving .sidebar-overlay's class and its inline
            // display style out of sync with each other after a single tap.
        });
    </script>