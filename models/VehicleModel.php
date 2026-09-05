<?php
/**
 * Vehicle Assets Model
 */
class VehicleModel extends Model {
    protected $table = 'vehicle_assets';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT v.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                u.full_name as created_by_name
                FROM {$this->table} v
                LEFT JOIN states s ON v.state_id = s.id
                LEFT JOIN lgas l ON v.lga_id = l.id
                LEFT JOIN zones z ON v.zone_id = z.id
                LEFT JOIN commands c ON v.command_id = c.id
                LEFT JOIN users u ON v.created_by = u.id
                WHERE v.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT v.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name
                FROM {$this->table} v
                LEFT JOIN states s ON v.state_id = s.id
                LEFT JOIN lgas l ON v.lga_id = l.id
                LEFT JOIN zones z ON v.zone_id = z.id
                LEFT JOIN commands c ON v.command_id = c.id
                ORDER BY v.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByStatus($status) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE operational_status = ? ORDER BY created_at DESC",
            [$status]
        )->fetchAll();
    }
    
    public function getByCondition($condition) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE `condition` = ? ORDER BY created_at DESC",
            [$condition]
        )->fetchAll();
    }
    
    public function getServiceDue($days = 30) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE next_service_date IS NOT NULL
             AND next_service_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND next_service_date >= CURDATE()
             ORDER BY next_service_date ASC",
            [$days]
        )->fetchAll();
    }
    
    public function getInsuranceExpiring($days = 30) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE insurance_expiry IS NOT NULL
             AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND insurance_expiry >= CURDATE()
             ORDER BY insurance_expiry ASC",
            [$days]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT v.*, s.state_name, l.lga_name
                FROM {$this->table} v
                LEFT JOIN states s ON v.state_id = s.id
                LEFT JOIN lgas l ON v.lga_id = l.id
                WHERE v.asset_code LIKE ? 
                OR v.registration_number LIKE ? 
                OR v.vin_chassis_number LIKE ?
                OR v.engine_number LIKE ?
                OR v.make_manufacturer LIKE ?
                OR v.assigned_officer LIKE ?
                ORDER BY v.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term, $term, $term])->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'total_value' => 0,
            'by_status' => [],
            'by_condition' => [],
            'service_due' => 0,
            'insurance_expiring' => 0
        ];
        
        // Total value
        $value = $this->query("SELECT SUM(purchase_value) as total FROM {$this->table}")->fetch();
        $stats['total_value'] = $value['total'] ?? 0;
        
        // By status
        $statuses = $this->query(
            "SELECT operational_status, COUNT(*) as count FROM {$this->table} GROUP BY operational_status"
        )->fetchAll();
        
        foreach ($statuses as $s) {
            $stats['by_status'][$s['operational_status'] ?: 'Unknown'] = $s['count'];
        }
        
        // By condition
        $conditions = $this->query(
            "SELECT `condition`, COUNT(*) as count FROM {$this->table} GROUP BY `condition`"
        )->fetchAll();
        
        foreach ($conditions as $c) {
            $stats['by_condition'][$c['condition'] ?: 'Unknown'] = $c['count'];
        }
        
        // Service due
        $stats['service_due'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE next_service_date IS NOT NULL
             AND next_service_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND next_service_date >= CURDATE()"
        )->fetch()['count'];
        
        // Insurance expiring
        $stats['insurance_expiring'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE insurance_expiry IS NOT NULL
             AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND insurance_expiry >= CURDATE()"
        )->fetch()['count'];
        
        return $stats;
    }
    
    public function generateAssetCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT asset_code FROM {$this->table} 
             WHERE asset_code LIKE 'VHL-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("VHL-%s%s-%04d", $year, $month, $seq);
    }
}