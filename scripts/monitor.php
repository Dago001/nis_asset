<?php
/**
 * System Monitoring Script
 * Run via cron: every 5 minutes (e.g. * / 5 * * * * php /var/www/nis-ams/scripts/monitor.php)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

require_once __DIR__ . '/../config/init.php';

class SystemMonitor {
    
    private $alerts = [];
    private $emailAlerts = true;
    private $adminEmail = 'admin@immigration.gov.ng';
    
    public function run() {
        $this->checkDatabaseConnection();
        $this->checkDiskSpace();
        $this->checkLowStock();
        $this->checkExpiringItems();
        $this->sendAlerts();
    }
    
    private function checkDatabaseConnection() {
        try {
            Database::getInstance();
            $this->log("Database connection OK");
        } catch (Exception $e) {
            $this->alerts[] = [
                'level' => 'CRITICAL',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function checkDiskSpace() {
        $path = Config::get('upload_path');
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $usedPercent = 100 - (($free / $total) * 100);
        
        if ($usedPercent > 90) {
            $this->alerts[] = [
                'level' => 'WARNING',
                'message' => "Disk usage is at " . round($usedPercent, 2) . "%"
            ];
        }
        
        $this->log("Disk usage: " . round($usedPercent, 2) . "%");
    }
    
    private function checkLowStock() {
        // Low ammunition
        $lowAmmo = Database::fetchAll(
            "SELECT ai.*, at.ammo_type, ac.calibre 
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             WHERE ai.balance < 100
             ORDER BY ai.balance ASC
             LIMIT 10"
        );
        
        foreach ($lowAmmo as $ammo) {
            $this->alerts[] = [
                'level' => 'WARNING',
                'message' => "Low ammunition: {$ammo['ammo_id']} - Balance: {$ammo['balance']}"
            ];
        }
        
        // Low weapons count by type
        $lowWeapons = Database::fetchAll(
            "SELECT wt.type_name, COUNT(*) as count
             FROM weapons_inventory wi
             JOIN weapon_types wt ON wi.weapon_type_id = wt.id
             GROUP BY wt.type_name
             HAVING count < 5"
        );
        
        foreach ($lowWeapons as $weapon) {
            $this->alerts[] = [
                'level' => 'INFO',
                'message' => "Low stock: {$weapon['type_name']} - Only {$weapon['count']} remaining"
            ];
        }
    }
    
    private function checkExpiringItems() {
        // Expiring ammunition
        $expiringAmmo = Database::fetchAll(
            "SELECT ai.*, at.ammo_type, ac.calibre,
                    DATEDIFF(expiry_date, CURDATE()) as days_remaining
             FROM ammunition_inventory ai
             LEFT JOIN ammunition_types at ON ai.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON ai.calibre_id = ac.id
             WHERE ai.expiry_date IS NOT NULL
             AND ai.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND ai.expiry_date >= CURDATE()
             ORDER BY ai.expiry_date ASC"
        );
        
        foreach ($expiringAmmo as $ammo) {
            $this->alerts[] = [
                'level' => 'WARNING',
                'message' => "Expiring soon: {$ammo['ammo_id']} - {$ammo['days_remaining']} days remaining"
            ];
        }
        
        // Expiring insurance
        $expiringInsurance = Database::fetchAll(
            "SELECT asset_code, make_manufacturer, registration_number, insurance_expiry,
                    DATEDIFF(insurance_expiry, CURDATE()) as days_remaining
             FROM vehicle_assets
             WHERE insurance_expiry IS NOT NULL
             AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND insurance_expiry >= CURDATE()
             ORDER BY insurance_expiry ASC"
        );
        
        foreach ($expiringInsurance as $vehicle) {
            $this->alerts[] = [
                'level' => 'WARNING',
                'message' => "Insurance expiring: {$vehicle['asset_code']} - {$vehicle['days_remaining']} days"
            ];
        }
    }
    
    private function sendAlerts() {
        if (empty($this->alerts)) {
            $this->log("No alerts to send");
            return;
        }
        
        // Log alerts
        foreach ($this->alerts as $alert) {
            $this->log("[{$alert['level']}] {$alert['message']}");
        }
        
        // Send email for critical alerts
        if ($this->emailAlerts) {
            $criticalAlerts = array_filter($this->alerts, function($alert) {
                return $alert['level'] === 'CRITICAL';
            });
            
            if (!empty($criticalAlerts)) {
                $this->sendEmailAlert($criticalAlerts);
            }
        }
    }
    
    private function sendEmailAlert($alerts) {
        $subject = "NIS-AMS Critical Alert - " . date('Y-m-d H:i:s');
        $message = "The following critical alerts were detected:\n\n";
        
        foreach ($alerts as $alert) {
            $message .= "- {$alert['message']}\n";
        }
        
        mail($this->adminEmail, $subject, $message);
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        echo $logMessage;
        
        $logFile = BASE_PATH . '/logs/monitor.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// Run monitor
$monitor = new SystemMonitor();
$monitor->run();