<?php
/**
 * Dashboard Model
 * Handles all data operations for the dashboard
 */
class DashboardModel {
    
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    /**
     * Get all dashboard statistics
     */
    public function getStatistics() {
        if (isset($_SESSION['dash_stats_cache'], $_SESSION['dash_stats_time']) && (time() - $_SESSION['dash_stats_time'] < 30)) {
            return $_SESSION['dash_stats_cache'];
        }

        $stats = $this->getDefaultStats();
        
        if (!$this->db) {
            return $stats;
        }
        
        try {
            // Weapons statistics
            $stats = array_merge($stats, $this->getWeaponsStats());
            
            // Ammunition statistics
            $stats = array_merge($stats, $this->getAmmunitionStats());
            
            // Asset statistics
            $stats = array_merge($stats, $this->getAssetStats());
            
            // Fleet statistics
            $stats = array_merge($stats, $this->getFleetStats());
            
            // User statistics
            $stats = array_merge($stats, $this->getUserStats());
            
            // Alert statistics
            $stats = array_merge($stats, $this->getAlertStats());
            
        } catch (Exception $e) {
            error_log("Dashboard statistics error: " . $e->getMessage());
        }
        
        $_SESSION['dash_stats_cache'] = $stats;
        $_SESSION['dash_stats_time'] = time();

        return $stats;
    }
    
    /**
     * Get default statistics values
     */
    private function getDefaultStats() {
        return [
            'total_weapons' => 0,
            'weapons_issued' => 0,
            'total_ammunition' => 0,
            'ammunition_balance' => 0,
            'total_land' => 0,
            'total_buildings' => 0,
            'total_rented' => 0,
            'total_projects' => 0,
            'total_movable' => 0,
            'total_ict' => 0,
            'total_vehicles' => 0,
            'total_aircraft' => 0,
            'total_marine' => 0,
            'total_motorcycles' => 0,
            'total_users' => 1,
            'pending_requisitions' => 0,
            'expiring_ammunition' => 0,
            'expiring_insurance' => 0,
            'service_due_vehicles' => 0,
            'unserviceable_weapons' => 0
        ];
    }
    
    /**
     * Get weapons statistics
     */
    private function getWeaponsStats() {
        $stats = [];
        
        // Total weapons
        $result = $this->fetchSingle("SELECT COUNT(*) as count FROM weapons_inventory");
        $stats['total_weapons'] = $result['count'] ?? 0;
        
        // Issued weapons
        $result = $this->fetchSingle("SELECT COUNT(*) as count FROM weapons_inventory WHERE current_location = 'Issued'");
        $stats['weapons_issued'] = $result['count'] ?? 0;
        
        // Unserviceable weapons
        $result = $this->fetchSingle("SELECT COUNT(*) as count FROM weapons_inventory WHERE `condition` = 'Unserviceable'");
        $stats['unserviceable_weapons'] = $result['count'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get ammunition statistics
     */
    private function getAmmunitionStats() {
        $stats = [];
        
        // Total ammunition types
        $result = $this->fetchSingle("SELECT COUNT(*) as count FROM ammunition_inventory");
        $stats['total_ammunition'] = $result['count'] ?? 0;
        
        // Total ammunition balance
        $result = $this->fetchSingle("SELECT COALESCE(SUM(balance), 0) as total FROM ammunition_inventory");
        $stats['ammunition_balance'] = $result['total'] ?? 0;
        
        // Expiring ammunition (next 90 days)
        $result = $this->fetchSingle("
            SELECT COUNT(*) as count FROM ammunition_inventory 
            WHERE expiry_date IS NOT NULL 
            AND expiry_date <= DATE_ADD(NOW(), INTERVAL 90 DAY)
            AND expiry_date >= CURDATE()
        ");
        $stats['expiring_ammunition'] = $result['count'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get asset statistics
     */
    private function getAssetStats() {
        $stats = [];
        
        $assetTables = [
            'total_land' => 'land_assets',
            'total_buildings' => 'building_assets',
            'total_rented' => 'rented_properties',
            'total_projects' => 'ongoing_projects',
            'total_movable' => 'movable_assets',
            'total_ict' => 'ict_assets'
        ];
        
        foreach ($assetTables as $key => $table) {
            $result = $this->fetchSingle("SELECT COUNT(*) as count FROM $table");
            $stats[$key] = $result['count'] ?? 0;
        }
        
        return $stats;
    }
    
    /**
     * Get fleet statistics
     */
    private function getFleetStats() {
        $stats = [];
        
        $fleetTables = [
            'total_vehicles' => 'vehicle_assets',
            'total_aircraft' => 'aircraft_assets',
            'total_marine' => 'marine_assets',
            'total_motorcycles' => 'motorcycle_assets'
        ];
        
        foreach ($fleetTables as $key => $table) {
            $result = $this->fetchSingle("SELECT COUNT(*) as count FROM $table");
            $stats[$key] = $result['count'] ?? 0;
        }
        
        // Service due vehicles (next 7 days)
        $result = $this->fetchSingle("
            SELECT COUNT(*) as count FROM vehicle_assets 
            WHERE next_service_date IS NOT NULL 
            AND next_service_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ");
        $stats['service_due_vehicles'] = $result['count'] ?? 0;
        
        // Expiring insurance (next 30 days)
        $result = $this->fetchSingle("
            SELECT COUNT(*) as count FROM vehicle_assets 
            WHERE insurance_expiry IS NOT NULL 
            AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $stats['expiring_insurance'] = $result['count'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get user statistics
     */
    private function getUserStats() {
        $stats = [];
        
        // Active users
        $result = $this->fetchSingle("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $stats['total_users'] = $result['count'] ?? 1;
        
        // Pending requisitions
        $result = $this->fetchSingle("SELECT COUNT(*) as count FROM requisitions WHERE status = 'Pending'");
        $stats['pending_requisitions'] = $result['count'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get alert statistics
     */
    private function getAlertStats() {
        $stats = [];
        
        $stats['total_alerts'] = 
            ($stats['expiring_ammunition'] ?? 0) + 
            ($stats['unserviceable_weapons'] ?? 0) + 
            ($stats['expiring_insurance'] ?? 0) + 
            ($stats['service_due_vehicles'] ?? 0);
        
        return $stats;
    }
    
    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 10) {
        try {
            $stmt = $this->db->query("
                SELECT al.*, u.full_name, u.username
                FROM audit_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                ORDER BY al.created_at DESC 
                LIMIT " . intval($limit)
            );
            return $stmt->fetchAll() ?: [];
        } catch (Exception $e) {
            error_log("Error fetching activities: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get system health status
     */
    public function getSystemHealth() {
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'storage' => $this->checkStorageHealth(),
            'memory' => $this->checkMemoryUsage(),
            'last_backup' => $this->getLastBackupInfo()
        ];
        
        return $health;
    }
    
    /**
     * Check database health
     */
    private function checkDatabaseHealth() {
        try {
            $this->db->query("SELECT 1")->fetch();
            return ['status' => 'healthy', 'message' => 'Database connection OK'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }
    }
    
    /**
     * Check storage health
     */
    private function checkStorageHealth() {
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            return ['status' => 'warning', 'message' => 'Upload directory not found'];
        }
        
        $freeSpace = disk_free_space($uploadDir);
        $totalSpace = disk_total_space($uploadDir);
        $usedPercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        
        if ($usedPercentage > 90) {
            return ['status' => 'error', 'message' => 'Storage critically low'];
        } elseif ($usedPercentage > 75) {
            return ['status' => 'warning', 'message' => 'Storage running low'];
        }
        
        return ['status' => 'healthy', 'message' => 'Storage OK'];
    }
    
    /**
     * Check memory usage
     */
    private function checkMemoryUsage() {
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $usagePercentage = ($memoryUsage / $this->convertToBytes($memoryLimit)) * 100;
        
        if ($usagePercentage > 80) {
            return ['status' => 'warning', 'message' => 'High memory usage'];
        }
        
        return ['status' => 'healthy', 'message' => 'Memory usage normal'];
    }
    
    /**
     * Get last backup info
     */
    private function getLastBackupInfo() {
        $backupDir = __DIR__ . '/../../backups/';
        if (!is_dir($backupDir)) {
            return ['status' => 'warning', 'message' => 'No backups found'];
        }
        
        $latestBackup = null;
        $latestTime = 0;
        
        foreach (glob($backupDir . '*.sql') as $file) {
            if (filemtime($file) > $latestTime) {
                $latestTime = filemtime($file);
                $latestBackup = $file;
            }
        }
        
        if ($latestBackup) {
            $daysOld = floor((time() - $latestTime) / 86400);
            if ($daysOld > 7) {
                return ['status' => 'warning', 'message' => 'Backup is ' . $daysOld . ' days old'];
            }
            return ['status' => 'healthy', 'message' => 'Backup OK - ' . $daysOld . ' days old'];
        }
        
        return ['status' => 'warning', 'message' => 'No backups found'];
    }
    
    /**
     * Helper method to fetch single row
     */
    private function fetchSingle($sql) {
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Query error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Convert memory string to bytes
     */
    private function convertToBytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int)$value;
        
        switch ($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
}