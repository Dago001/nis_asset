<?php
/**
 * Input Validator
 */
class Validator {
    
    private $data = [];
    private $errors = [];
    private $rules = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function validate($rules) {
        $this->rules = $rules;
        $this->errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $fieldRules = explode('|', $ruleString);
            
            foreach ($fieldRules as $rule) {
                $this->validateRule($field, $value, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    private function validateRule($field, $value, $rule) {
        // Required
        if ($rule === 'required' && (empty($value) && $value !== '0')) {
            $this->errors[$field][] = ucfirst($field) . ' is required';
            return;
        }
        
        // Skip other rules if empty and not required
        if (empty($value) && $value !== '0') {
            return;
        }
        
        // Email
        if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = 'Invalid email format';
        }
        
        // URL
        if ($rule === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = 'Invalid URL format';
        }
        
        // Integer
        if ($rule === 'int' && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = 'Must be an integer';
        }
        
        // Numeric
        if ($rule === 'numeric' && !is_numeric($value)) {
            $this->errors[$field][] = 'Must be a number';
        }
        
        // Date
        if ($rule === 'date') {
            $d = DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->errors[$field][] = 'Invalid date format (YYYY-MM-DD)';
            }
        }
        
        // DateTime
        if ($rule === 'datetime') {
            $d = DateTime::createFromFormat('Y-m-d H:i:s', $value);
            if (!$d || $d->format('Y-m-d H:i:s') !== $value) {
                $this->errors[$field][] = 'Invalid datetime format (YYYY-MM-DD HH:MM:SS)';
            }
        }
        
        // Boolean
        if ($rule === 'boolean') {
            if (!in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                $this->errors[$field][] = 'Must be a boolean value';
            }
        }
        
        // Min length
        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this->errors[$field][] = ucfirst($field) . ' must be at least ' . $min . ' characters';
            }
        }
        
        // Max length
        if (strpos($rule, 'max:') === 0) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $this->errors[$field][] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
            }
        }
        
        // Between
        if (preg_match('/between:(\d+),(\d+)/', $rule, $matches)) {
            $min = (int) $matches[1];
            $max = (int) $matches[2];
            $len = strlen($value);
            if ($len < $min || $len > $max) {
                $this->errors[$field][] = ucfirst($field) . ' must be between ' . $min . ' and ' . $max . ' characters';
            }
        }
        
        // Min value
        if (strpos($rule, 'min_value:') === 0) {
            $min = (float) substr($rule, 10);
            if ((float) $value < $min) {
                $this->errors[$field][] = ucfirst($field) . ' must be at least ' . $min;
            }
        }
        
        // Max value
        if (strpos($rule, 'max_value:') === 0) {
            $max = (float) substr($rule, 10);
            if ((float) $value > $max) {
                $this->errors[$field][] = ucfirst($field) . ' must not exceed ' . $max;
            }
        }
        
        // Alpha
        if ($rule === 'alpha' && !ctype_alpha($value)) {
            $this->errors[$field][] = 'Must contain only letters';
        }
        
        // Alpha numeric
        if ($rule === 'alpha_num' && !ctype_alnum($value)) {
            $this->errors[$field][] = 'Must contain only letters and numbers';
        }
        
        // Alpha dash
        if ($rule === 'alpha_dash' && !preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) {
            $this->errors[$field][] = 'Must contain only letters, numbers, underscores and dashes';
        }
        
        // Name (letters, spaces, hyphens, apostrophes, dots)
        if ($rule === 'name' && !isValidName($value)) {
            $this->errors[$field][] = 'Must contain only alphabets, spaces, hyphens (-), and apostrophes (\')';
        }

        // Phone (11 digits only)
        if ($rule === 'phone' && !isValidPhone($value)) {
            $this->errors[$field][] = 'Phone number must be exactly 11 digits';
        }
        
        // NIS Number
        if ($rule === 'nis' && !preg_match('/^[A-Z0-9\-]+$/i', $value)) {
            $this->errors[$field][] = 'Invalid NIS number format';
        }
        
        // Password strength
        if ($rule === 'password') {
            $hasMinLen = strlen($value) >= 8;
            $hasUppercase = preg_match('/[A-Z]/', $value);
            $hasLowercase = preg_match('/[a-z]/', $value);
            $hasNumber = preg_match('/[0-9]/', $value);
            $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $value);
            
            if (!$hasMinLen || !$hasUppercase || !$hasLowercase || !$hasNumber || !$hasSpecial) {
                $this->errors[$field][] = 'Password must be at least 8 characters long and contain at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special character.';
            }
        }
        
        // Unique (for database)
        if (preg_match('/unique:(\w+),(\w+),(\d+)/', $rule, $matches)) {
            $table = $matches[1];
            $column = $matches[2];
            $excludeId = $matches[3] ?? null;
            
            $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
            $params = [$value];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $result = Database::fetchOne($sql, $params);
            if ($result['count'] > 0) {
                $this->errors[$field][] = ucfirst($field) . ' already exists';
            }
        }
    }
    
    public function errors() {
        return $this->errors;
    }
    
    public function firstError($field) {
        return $this->errors[$field][0] ?? null;
    }
    
    public function hasError($field) {
        return isset($this->errors[$field]);
    }
}