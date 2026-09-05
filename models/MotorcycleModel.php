<?php
/**
 * Motorcycle Assets Model
 */
class MotorcycleModel extends Model {
    protected $table = 'motorcycle_assets';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT m.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                u.full_name as created_by_name
                FROM {$this->table} m
                LEFT JOIN states s ON m.state_id = s.id
                LEFT JOIN lgas l ON m.lga_id = l.id
                LEFT JOIN zones z ON m.zone_id = z.id
                LEFT JOIN commands c ON m.command_id = c.id
                LEFT JOIN users u ON m.created_by = u.id
                WHERE m.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT m.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name
                FROM {$this->table} m
                LEFT JOIN states s ON m.state_id = s.id
                LEFT JOIN lgas l ON m.lga_id = l.id
                LEFT JOIN zones z ON m.zone_id = z.id
                LEFT JOIN commands c ON m.command_id = c.id
                ORDER BY m.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByStatus($status) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE insurance_status = ? ORDER BY created_at DESC",
            [$status]
        )->fetchAll();
    }
    
    public function getServiceDue($days = 30) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE next_service_due IS NOT NULL
             AND next_service_due <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND next_service_due >= CURDATE()
             ORDER BY next_service_due ASC",
            [$days]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT m.*, s.state_name, l.lga_name
                FROM {$this->table} m
                LEFT JOIN states s ON m.state_id = s.id
                LEFT JOIN lgas l ON m.lga_id = l.id
                WHERE m.asset_code LIKE ? 
                OR m.registration_number LIKE ? 
                OR m.chassis_number LIKE ?
                OR m.engine_number LIKE ?
                OR m.make_model LIKE ?
                OR m.assigned_officer_unit LIKE ?
                ORDER BY m.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term, $term, $term])->fetchAll();
    }
    
    public function generateAssetCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT asset_code FROM {$this->table} 
             WHERE asset_code LIKE 'MTR-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("MTR-%s%s-%04d", $year, $month, $seq);
    }
}