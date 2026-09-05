<?php
/**
 * Security Class - CSRF, XSS, Input Validation
 */
class Security {
    
    /**
     * Generate CSRF token
     */
    public static function csrfToken() {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf_token');
    }
    
    /**
     * Validate CSRF token (constant-time comparison).
     */
    public static function validateCsrfToken($token) {
        if (!is_string($token) || $token === '' || !Session::has('_csrf_token')) {
            return false;
        }
        return hash_equals((string) Session::get('_csrf_token'), $token);
    }
    
    /**
     * CSRF field for forms
     */
    public static function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }
    
    /**
     * Escape output for HTML (XSS prevention)
     */
    public static function escape($data) {
        if (is_array($data)) {
            return array_map([self::class, 'escape'], $data);
        }
        return htmlspecialchars((string) ($data ?? ''), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Neutralise spreadsheet formula injection in a CSV cell.
     * Excel / LibreOffice treat a leading = + - @ (or tab/CR) as a formula.
     * Plain numbers (incl. negatives / decimals) are left untouched.
     */
    public static function csvCell($value) {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $value = (string) ($value ?? '');
        if ($value === '' || is_numeric($value)) {
            return $value;
        }
        if (preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    /**
     * Apply csvCell() to every field of a row.
     */
    public static function csvRow($row) {
        return array_map([self::class, 'csvCell'], (array) $row);
    }

    /**
     * Drop-in replacement for fputcsv() that sanitises every field first.
     */
    public static function fputcsv($handle, $fields) {
        return fputcsv($handle, self::csvRow($fields));
    }

    /**
     * Enforce a minimum password policy.
     * Returns null when acceptable, or an error string.
     */
    public static function passwordPolicyError($password) {
        // Floor is 8, not 12 — matches the "Minimum 8 characters" every
        // password form in the app (Create User, Change Password) actually
        // shows the user. This used to hard-floor at 12 regardless of
        // config, so an 8-character password matching the form's own hint
        // text was rejected with a min-length error the form never warned
        // about. PASSWORD_MIN_LENGTH can still raise this above 8 for a
        // stricter policy — it just can't go below what the UI advertises.
        $min = (int) Config::get('password_min_length', 8);
        if ($min < 8) { $min = 8; }

        if (strlen($password) < $min) {
            return "Password must be at least {$min} characters long.";
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            return 'Password must contain both letters and numbers.';
        }
        // "Security → Require strong passwords" — adds uppercase + lowercase +
        // special-character requirements on top of the baseline above.
        if (Config::get('require_strong_password', false)) {
            if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password)) {
                return 'Password must contain both uppercase and lowercase letters.';
            }
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                return 'Password must contain at least one special character.';
            }
        }
        $weak = ['password', 'admin@123', 'admin123', '123456789012', 'qwertyuiop', 'letmein', 'nisadmin'];
        if (in_array(strtolower($password), $weak, true)) {
            return 'That password is too common. Choose something less predictable.';
        }
        return null;
    }
    
    /**
     * Sanitize input
     */
    public static function sanitize($data, $type = 'string') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitize($item, $type);
            }, $data);
        }
        
        switch ($type) {
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($data, FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate input
     */
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Required
            if (strpos($rule, 'required') !== false && (empty($value) && $value !== '0')) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }
            
            // Skip other validation if empty and not required
            if (empty($value) && $value !== '0') {
                continue;
            }
            
            // Email
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'Invalid email format';
            }

            // Name (Alphabets, spaces, hyphens, apostrophes, dots)
            if (strpos($rule, 'name') !== false && !isValidName($value)) {
                $errors[$field] = ucfirst($field) . " must contain only alphabets, spaces, hyphens (-), and apostrophes (')";
            }

            // Phone (11 digits only)
            if (strpos($rule, 'phone') !== false && !isValidPhone($value)) {
                $errors[$field] = ucfirst($field) . ' must be exactly 11 digits';
            }
            
            // URL
            if (strpos($rule, 'url') !== false && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[$field] = 'Invalid URL format';
            }
            
            // Integer
            if (strpos($rule, 'int') !== false && !filter_var($value, FILTER_VALIDATE_INT)) {
                $errors[$field] = 'Must be an integer';
            }
            
            // Numeric
            if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                $errors[$field] = 'Must be a number';
            }
            
            // Date
            if (strpos($rule, 'date') !== false) {
                $d = DateTime::createFromFormat('Y-m-d', $value);
                if (!$d || $d->format('Y-m-d') !== $value) {
                    $errors[$field] = 'Invalid date format (YYYY-MM-DD)';
                }
            }
            
            // Min length
            preg_match('/min:(\d+)/', $rule, $minMatch);
            if ($minMatch && strlen($value) < (int)$minMatch[1]) {
                $errors[$field] = ucfirst($field) . ' must be at least ' . $minMatch[1] . ' characters';
            }
            
            // Max length
            preg_match('/max:(\d+)/', $rule, $maxMatch);
            if ($maxMatch && strlen($value) > (int)$maxMatch[1]) {
                $errors[$field] = ucfirst($field) . ' must not exceed ' . $maxMatch[1] . ' characters';
            }
            
            // Match field
            preg_match('/match:(\w+)/', $rule, $matchMatch);
            if ($matchMatch && $value !== ($data[$matchMatch[1]] ?? '')) {
                $errors[$field] = ucfirst($field) . ' does not match ' . $matchMatch[1];
            }
            
            // Unique
            preg_match('/unique:(\w+),(\w+)/', $rule, $uniqueMatch);
            if ($uniqueMatch) {
                $table = $uniqueMatch[1];
                $column = $uniqueMatch[2];
                $excludeId = $data['id'] ?? null;
                
                $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
                $params = [$value];
                
                if ($excludeId) {
                    $sql .= " AND id != ?";
                    $params[] = $excludeId;
                }
                
                $result = Database::fetchOne($sql, $params);
                if ($result['count'] > 0) {
                    $errors[$field] = ucfirst($field) . ' already exists';
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Check if request is AJAX
     */
    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public static function getClientIp() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        if (defined('TRUST_PROXY') && TRUST_PROXY === true) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip = trim($ips[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}