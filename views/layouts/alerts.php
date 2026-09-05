<?php
/**
 * Alerts Layout
 */

// Ensure Session class exists
if (!class_exists('Session')) {
    class Session {
        public static function get($key, $default = null) { return $_SESSION[$key] ?? $default; }
        public static function remove($key) { unset($_SESSION[$key]); }
    }
}

// Display all session messages
$types = ['success', 'error', 'warning', 'info'];

foreach ($types as $type) {
    $message = Session::get($type);
    if ($message) {
        $icon = $type === 'success' ? 'check-circle' : 
                ($type === 'error' ? 'exclamation-circle' : 
                ($type === 'warning' ? 'exclamation-triangle' : 'info-circle'));
        
        echo '<div class="alert alert-' . $type . ' alert-dismissible">';
        echo '<i class="fas fa-' . $icon . '"></i>';
        echo '<span>' . htmlspecialchars($message) . '</span>';
        echo '<button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
        echo '</div>';
        
        Session::remove($type);
    }
}

// Display validation errors
$errors = Session::get('errors', []);
if (!empty($errors)) {
    echo '<div class="alert alert-error alert-dismissible">';
    echo '<i class="fas fa-exclamation-circle"></i>';
    echo '<div>';
    echo '<strong>Please fix the following errors:</strong>';
    echo '<ul>';
    foreach ($errors as $field => $fieldErrors) {
        if (is_array($fieldErrors)) {
            foreach ($fieldErrors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
        } else {
            echo '<li>' . htmlspecialchars($fieldErrors) . '</li>';
        }
    }
    echo '</ul>';
    echo '</div>';
    echo '<button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
    echo '</div>';
    
    Session::remove('errors');
}
?>