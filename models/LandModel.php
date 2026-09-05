<?php
/**
 * Land Assets Model
 */
class LandModel extends Model {
    protected $table = 'land_assets';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT la.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                u.full_name as created_by_name
                FROM {$this->table} la
                LEFT JOIN states s ON la.state_id = s.id
                LEFT JOIN lgas l ON la.lga_id = l.id
                LEFT JOIN zones z ON la.zone_id = z.id
                LEFT JOIN commands c ON la.command_id = c.id
                LEFT JOIN users u ON la.created_by = u.id
                WHERE la.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT la.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name
                FROM {$this->table} la
                LEFT JOIN states s ON la.state_id = s.id
                LEFT JOIN lgas l ON la.lga_id = l.id
                LEFT JOIN zones z ON la.zone_id = z.id
                LEFT JOIN commands c ON la.command_id = c.id
                ORDER BY la.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByCommand($commandId) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE command_id = ? ORDER BY created_at DESC",
            [$commandId]
        )->fetchAll();
    }
    
    public function getByZone($zoneId) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE zone_id = ? ORDER BY created_at DESC",
            [$zoneId]
        )->fetchAll();
    }
    
    public function getByStatus($status) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC",
            [$status]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT la.*, s.state_name, l.lga_name
                FROM {$this->table} la
                LEFT JOIN states s ON la.state_id = s.id
                LEFT JOIN lgas l ON la.lga_id = l.id
                WHERE la.asset_code LIKE ? 
                OR la.title_holder LIKE ? 
                OR la.address LIKE ?
                OR la.survey_plan_no LIKE ?
                OR la.certificate_of_occupancy_no LIKE ?
                ORDER BY la.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term, $term])->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'by_status' => [],
            'by_zone' => [],
            'total_area' => 0
        ];
        
        // By status
        $statuses = $this->query(
            "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status"
        )->fetchAll();
        
        foreach ($statuses as $s) {
            $stats['by_status'][$s['status'] ?: 'Unknown'] = $s['count'];
        }
        
        // By zone
        $zones = $this->query(
            "SELECT z.zone_name, COUNT(la.id) as count
             FROM zones z
             LEFT JOIN {$this->table} la ON z.id = la.zone_id
             GROUP BY z.id"
        )->fetchAll();
        
        foreach ($zones as $z) {
            $stats['by_zone'][$z['zone_name']] = $z['count'];
        }
        
        // Total area
        $area = $this->query("SELECT SUM(size) as total FROM {$this->table}")->fetch();
        $stats['total_area'] = $area['total'] ?? 0;
        
        return $stats;
    }
    
    public function generateAssetCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT asset_code FROM {$this->table} 
             WHERE asset_code LIKE 'LAND-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("LAND-%s%s-%04d", $year, $month, $seq);
    }
}