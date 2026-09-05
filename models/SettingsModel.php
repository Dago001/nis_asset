<?php
/**
 * Settings Model
 * Handles all database operations for system settings
 */
class SettingsModel {
    
    private $cache = [];
    
    public function __construct() {
        // No need for connection, using static Database methods
    }
    
    /**
     * Get all settings grouped by group
     */
    public function getAllSettings() {
        $sql = "SELECT * FROM system_settings ORDER BY setting_group, id ASC";
        $result = Database::fetchAll($sql);
        
        if ($result === false) {
            return [];
        }
        
        $settings = [];
        foreach ($result as $row) {
            $group = $row['setting_group'] ?: 'general';
            
            // Decrypt if encrypted
            if ($row['is_encrypted'] && $row['setting_value']) {
                $row['setting_value'] = $this->decrypt($row['setting_value']);
            }
            
            // Cast value based on data type
            $row['setting_value'] = $this->castValue($row['setting_value'], $row['data_type']);
            
            if (!isset($settings[$group])) {
                $settings[$group] = [];
            }
            $settings[$group][] = $row;
        }
        
        return $settings;
    }
    
    /**
     * Get setting by key
     */
    public function getSetting($key, $default = null) {
        // Check cache first
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        $sql = "SELECT * FROM system_settings WHERE setting_key = ? LIMIT 1";
        $result = Database::fetchOne($sql, [$key]);
        
        if ($result) {
            // Decrypt if encrypted
            $value = $result['setting_value'];
            if ($result['is_encrypted'] && $value) {
                $value = $this->decrypt($value);
            }
            
            // Cast value based on data type
            $value = $this->castValue($value, $result['data_type']);
            
            // Store in cache
            $this->cache[$key] = $value;
            
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Get the full raw database row for a setting key.
     * Returns null if not found.
     */
    public function getSettingRow(string $key): ?array {
        $sql = "SELECT * FROM system_settings WHERE setting_key = ? LIMIT 1";
        $row = Database::fetchOne($sql, [$key]);
        if (!$row) return null;
        
        // Decrypt sensitive values so the caller always sees plain text
        if ($row['is_encrypted'] && $row['setting_value']) {
            $row['setting_value'] = $this->decrypt($row['setting_value']);
        }
        return $row;
    }
    
    /**
     * Update or create setting
     */
    public function saveSetting($key, $value, $group = 'general', $dataType = 'string', $description = '', $isEncrypted = false) {
        // Check if setting exists
        $sql = "SELECT id FROM system_settings WHERE setting_key = ?";
        $existing = Database::fetchOne($sql, [$key]);
        
        // Encrypt if needed
        $valueToStore = $value;
        if ($isEncrypted && $value) {
            $valueToStore = $this->encrypt($value);
        }
        
        if ($existing) {
            // Update existing
            $sql = "UPDATE system_settings SET 
                    setting_value = ?,
                    setting_group = ?,
                    data_type = ?,
                    description = ?,
                    is_encrypted = ?,
                    updated_at = NOW()
                    WHERE setting_key = ?";
            
            $result = Database::update('system_settings', [
                'setting_value' => $valueToStore,
                'setting_group' => $group,
                'data_type' => $dataType,
                'description' => $description,
                'is_encrypted' => $isEncrypted ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'setting_key = ?', [$key]);
        } else {
            // Insert new
            $result = Database::insert('system_settings', [
                'setting_key' => $key,
                'setting_value' => $valueToStore,
                'setting_group' => $group,
                'data_type' => $dataType,
                'description' => $description,
                'is_encrypted' => $isEncrypted ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Clear cache for this key
        unset($this->cache[$key]);
        
        return $result !== false;
    }
    
    /**
     * Update multiple settings at once
     */
    public function saveSettings($settings) {
        Database::beginTransaction();
        
        try {
            foreach ($settings as $key => $data) {
                $success = $this->saveSetting(
                    $key,
                    $data['value'],
                    $data['group'] ?? 'general',
                    $data['data_type'] ?? 'string',
                    $data['description'] ?? '',
                    $data['is_encrypted'] ?? false
                );
                
                if (!$success) {
                    throw new Exception("Failed to save setting: {$key}");
                }
            }
            
            Database::commit();
            
            // Clear entire cache
            $this->cache = [];
            
            return true;
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Settings save error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete setting
     */
    public function deleteSetting($key) {
        $result = Database::delete('system_settings', 'setting_key = ?', [$key]);
        
        // Clear cache
        unset($this->cache[$key]);
        
        return $result !== false;
    }
    
    /**
     * Get setting groups
     */
    public function getGroups() {
        $sql = "SELECT DISTINCT setting_group FROM system_settings WHERE setting_group IS NOT NULL AND setting_group != '' ORDER BY setting_group";
        $result = Database::fetchAll($sql);
        
        $groups = ['general' => 'General'];
        if ($result) {
            foreach ($result as $row) {
                $group = $row['setting_group'];
                $groups[$group] = ucfirst(str_replace('_', ' ', $group));
            }
        }
        
        return $groups;
    }
    
    /**
     * Cast value based on data type
     */
    private function castValue($value, $type) {
        if ($value === null || $value === '') {
            return null;
        }
        
        switch ($type) {
            case 'boolean':
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
            case 'int':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'json':
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;
            case 'array':
                if (is_array($value)) return $value;
                return array_map('trim', explode(',', $value));
            default:
                return (string)$value;
        }
    }
    
    /**
     * Encrypt a value for storage. ENCRYPTION_KEY should be set via .env in
     * any real deployment — see config/config.php. A fresh random IV is
     * generated per value and stored alongside the ciphertext (standard
     * practice for CBC mode; the IV isn't secret, only the key is).
     */
    private function encrypt($value) {
        $key = hash('sha256', defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'your-secret-key-here', true);
        $method = 'AES-256-CBC';
        $iv = random_bytes(openssl_cipher_iv_length($method));

        $encrypted = openssl_encrypt($value, $method, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a value stored by encrypt(). Also tolerates the legacy format
     * (static key-derived IV, no IV prefix) from before this was hardened,
     * so previously-stored secrets don't get orphaned.
     */
    private function decrypt($value) {
        $key = hash('sha256', defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'your-secret-key-here', true);
        $method = 'AES-256-CBC';
        $ivLen = openssl_cipher_iv_length($method);
        $raw = base64_decode($value);

        if ($raw === false || strlen($raw) <= $ivLen) {
            // Too short to contain an IV — try the legacy static-IV format.
            $legacyIv = substr(hash('sha256', defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'your-secret-key-here'), 0, 16);
            $legacyKey = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'your-secret-key-here';
            return openssl_decrypt($raw === false ? '' : $raw, $method, $legacyKey, 0, $legacyIv);
        }

        $iv = substr($raw, 0, $ivLen);
        $ciphertext = substr($raw, $ivLen);
        return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    }
}