<?php
/**
 * Footer Layout
 */

// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/nis_ams');
}

// Ensure Session class exists
if (!class_exists('Session')) {
    class Session {
        public static function get($key, $default = null) { 
            return $_SESSION[$key] ?? $default; 
        }
        public static function remove($key) { 
            unset($_SESSION[$key]); 
        }
    }
}
?>
<?php
$isAuthPage = isset($title) && in_array($title, ['Login', 'Two-Factor Authentication', 'Verifying Location', 'Forgot Password', 'Reset Password', 'Unauthorized']);
?>
<?php if (!$isAuthPage): ?>
    <!-- Footer -->
    <footer class="footer" style="margin-top: auto; background: var(--surface, #F7FAF8); padding: 15px 0; border-top: 1px solid var(--border-color, #D7E3DC); width: 100%; clear: both;">
        <div class="footer-container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <p style="margin: 0; color: var(--text-secondary, #6c757d); font-size: 0.9rem;">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(class_exists('Config') ? Config::get('company_name', 'Nigeria Immigration Service') : 'Nigeria Immigration Service'); ?>. All rights reserved.</p>
                <p style="margin: 0; color: var(--text-secondary, #6c757d); font-size: 0.9rem;">Designed and Developed by NIS Web Team</p>
            </div>
        </div>
    </footer>
<?php endif; ?>
    
    <!-- Notification Container -->
    <div class="notification-container" id="notificationContainer"></div>
    
    <!-- JavaScript -->
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    <?php if (!$isAuthPage): ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/forms.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/tables.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/charts.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/api.js"></script>
    <?php endif; ?>
    
    <!-- Page specific JavaScript -->
    <?php if (isset($extra_js) && is_array($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Notification System JavaScript -->
    <script>
    // Global notification function
    function showNotification(type, message) {
        // Don't show generic error messages
        if (message === 'An error occurred. Please try again.') {
            console.warn('Suppressed generic error message');
            return;
        }
        
        const container = document.getElementById('notificationContainer');
        
        // If container doesn't exist, create it
        if (!container) {
            const newContainer = document.createElement('div');
            newContainer.className = 'notification-container';
            newContainer.id = 'notificationContainer';
            document.body.appendChild(newContainer);
        }
        
        const container_el = document.getElementById('notificationContainer');
        
        const titles = {
            'success': 'Success!',
            'error': 'Error!',
            'warning': 'Warning!',
            'info': 'Information'
        };
        
        const icons = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas ${icons[type]}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">${titles[type]}</div>
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container_el.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }

    // Initialize components and check for messages
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize functions if they exist
        if (typeof initDynamicDropdowns === 'function') initDynamicDropdowns();
        if (typeof initFileUpload === 'function') initFileUpload();
        if (typeof initDataTables === 'function') initDataTables();
        if (typeof initSelectAll === 'function') initSelectAll();
        if (typeof initColumnToggle === 'function') initColumnToggle();
        
        <?php if (isset($init_charts) && $init_charts): ?>
        if (typeof initCharts === 'function') initCharts();
        <?php endif; ?>
        
        // Check for session messages
        <?php 
        // Success messages
        $success = Session::get('success');
        if ($success): 
        ?>
            showNotification('success', '<?php echo addslashes($success); ?>');
        <?php 
            Session::remove('success');
        endif; 
        ?>
        
        <?php 
        // Error messages
        $error = Session::get('error');
        if ($error): 
        ?>
            showNotification('error', '<?php echo addslashes($error); ?>');
        <?php 
            Session::remove('error');
        endif; 
        ?>
        
        <?php 
        // Warning messages
        $warning = Session::get('warning');
        if ($warning): 
        ?>
            showNotification('warning', '<?php echo addslashes($warning); ?>');
        <?php 
            Session::remove('warning');
        endif; 
        ?>
        
        <?php 
        // Info messages
        $info = Session::get('info');
        if ($info): 
        ?>
            showNotification('info', '<?php echo addslashes($info); ?>');
        <?php 
            Session::remove('info');
        endif; 
        ?>
        
        <?php 
        // Validation errors array
        $errors = Session::get('errors');
        if ($errors && is_array($errors)): 
            foreach ($errors as $field => $errorMessage): 
        ?>
                showNotification('error', '<?php echo addslashes(is_array($errorMessage) ? implode(', ', $errorMessage) : $errorMessage); ?>');
        <?php 
            endforeach;
            Session::remove('errors');
        endif; 
        ?>
    });
    
    // Add slideOut animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>