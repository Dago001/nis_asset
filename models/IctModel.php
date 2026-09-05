<?php
/**
 * ICT Assets Model
 */
class IctModel extends Model {
    protected $table = 'ict_assets';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT ia.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                u.full_name as created_by_name
                FROM {$this->table} ia
                LEFT JOIN states s ON ia.state_id = s.id
                LEFT JOIN lgas l ON ia.lga_id = l.id
                LEFT JOIN zones z ON ia.zone_id = z.id
                LEFT JOIN commands c ON ia.command_id = c.id
                LEFT JOIN users u ON ia.created_by = u.id
                WHERE ia.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT ia.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name
                FROM {$this->table} ia
                LEFT JOIN states s ON ia.state_id = s.id
                LEFT JOIN lgas l ON ia.lga_id = l.id
                LEFT JOIN zones z ON ia.zone_id = z.id
                LEFT JOIN commands c ON ia.command_id = c.id
                ORDER BY ia.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByCategory($category) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE asset_category = ? ORDER BY created_at DESC",
            [$category]
        )->fetchAll();
    }
    
    public function getByStatus($status) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE current_status = ? ORDER BY created_at DESC",
            [$status]
        )->fetchAll();
    }
    
    public function getWarrantyExpiring($days = 30) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE warranty_expiry IS NOT NULL
             AND warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND warranty_expiry >= CURDATE()
             ORDER BY warranty_expiry ASC",
            [$days]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT ia.*, s.state_name, l.lga_name
                FROM {$this->table} ia
                LEFT JOIN states s ON ia.state_id = s.id
                LEFT JOIN lgas l ON ia.lga_id = l.id
                WHERE ia.asset_code LIKE ? 
                OR ia.asset_description LIKE ? 
                OR ia.make_model LIKE ?
                OR ia.serial_number LIKE ?
                OR ia.responsible_officer LIKE ?
                OR ia.ip_address LIKE ?
                OR ia.mac_address LIKE ?
                ORDER BY ia.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term, $term, $term, $term])->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'total_value' => 0,
            'by_category' => [],
            'by_status' => [],
            'warranty_expiring' => 0
        ];
        
        // Total value
        $value = $this->query("SELECT SUM(purchase_value) as total FROM {$this->table}")->fetch();
        $stats['total_value'] = $value['total'] ?? 0;
        
        // By category
        $categories = $this->query(
            "SELECT asset_category, COUNT(*) as count, SUM(purchase_value) as value 
             FROM {$this->table} GROUP BY asset_category"
        )->fetchAll();
        
        foreach ($categories as $c) {
            $stats['by_category'][$c['asset_category'] ?: 'Other'] = [
                'count' => $c['count'],
                'value' => $c['value'] ?? 0
            ];
        }
        
        // By status
        $statuses = $this->query(
            "SELECT current_status, COUNT(*) as count FROM {$this->table} GROUP BY current_status"
        )->fetchAll();
        
        foreach ($statuses as $s) {
            $stats['by_status'][$s['current_status'] ?: 'Unknown'] = $s['count'];
        }
        
        // Warranty expiring
        $stats['warranty_expiring'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE warranty_expiry IS NOT NULL
             AND warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND warranty_expiry >= CURDATE()"
        )->fetch()['count'];
        
        return $stats;
    }
    
    public function generateAssetCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT asset_code FROM {$this->table} 
             WHERE asset_code LIKE 'ICT-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("ICT-%s%s-%04d", $year, $month, $seq);
    }
}