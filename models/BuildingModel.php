<?php
/**
 * Building Assets Model
 */
class BuildingModel extends Model {
    protected $table = 'building_assets';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT ba.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                la.asset_code as land_asset_code,
                u.full_name as created_by_name
                FROM {$this->table} ba
                LEFT JOIN states s ON ba.state_id = s.id
                LEFT JOIN lgas l ON ba.lga_id = l.id
                LEFT JOIN zones z ON ba.zone_id = z.id
                LEFT JOIN commands c ON ba.command_id = c.id
                LEFT JOIN land_assets la ON ba.land_id = la.id
                LEFT JOIN users u ON ba.created_by = u.id
                WHERE ba.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT ba.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                la.asset_code as land_asset_code
                FROM {$this->table} ba
                LEFT JOIN states s ON ba.state_id = s.id
                LEFT JOIN lgas l ON ba.lga_id = l.id
                LEFT JOIN zones z ON ba.zone_id = z.id
                LEFT JOIN commands c ON ba.command_id = c.id
                LEFT JOIN land_assets la ON ba.land_id = la.id
                ORDER BY ba.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByLandAsset($landId) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE land_id = ? ORDER BY created_at DESC",
            [$landId]
        )->fetchAll();
    }
    
    public function getByCondition($condition) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE condition_status = ? ORDER BY created_at DESC",
            [$condition]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT ba.*, s.state_name, l.lga_name
                FROM {$this->table} ba
                LEFT JOIN states s ON ba.state_id = s.id
                LEFT JOIN lgas l ON ba.lga_id = l.id
                WHERE ba.asset_code LIKE ? 
                OR ba.building_name LIKE ? 
                OR ba.address LIKE ?
                OR ba.construction_contractor LIKE ?
                ORDER BY ba.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term])->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'by_condition' => [],
            'by_zone' => [],
            'total_value' => 0
        ];
        
        // By condition
        $conditions = $this->query(
            "SELECT condition_status, COUNT(*) as count FROM {$this->table} GROUP BY condition_status"
        )->fetchAll();
        
        foreach ($conditions as $c) {
            $stats['by_condition'][$c['condition_status'] ?: 'Unknown'] = $c['count'];
        }
        
        // By zone
        $zones = $this->query(
            "SELECT z.zone_name, COUNT(ba.id) as count
             FROM zones z
             LEFT JOIN {$this->table} ba ON z.id = ba.zone_id
             GROUP BY z.id"
        )->fetchAll();
        
        foreach ($zones as $z) {
            $stats['by_zone'][$z['zone_name']] = $z['count'];
        }
        
        // Total value
        $value = $this->query("SELECT SUM(contract_sum) as total FROM {$this->table}")->fetch();
        $stats['total_value'] = $value['total'] ?? 0;
        
        return $stats;
    }
    
    public function generateAssetCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT asset_code FROM {$this->table} 
             WHERE asset_code LIKE 'BLDG-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("BLDG-%s%s-%04d", $year, $month, $seq);
    }
}