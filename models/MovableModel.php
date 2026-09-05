<?php
/**
 * Movable Assets Model
 */
class MovableModel extends Model {
    protected $table = 'movable_assets';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT ma.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                u.full_name as created_by_name
                FROM {$this->table} ma
                LEFT JOIN states s ON ma.state_id = s.id
                LEFT JOIN lgas l ON ma.lga_id = l.id
                LEFT JOIN zones z ON ma.zone_id = z.id
                LEFT JOIN commands c ON ma.command_id = c.id
                LEFT JOIN users u ON ma.created_by = u.id
                WHERE ma.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT ma.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name
                FROM {$this->table} ma
                LEFT JOIN states s ON ma.state_id = s.id
                LEFT JOIN lgas l ON ma.lga_id = l.id
                LEFT JOIN zones z ON ma.zone_id = z.id
                LEFT JOIN commands c ON ma.command_id = c.id
                ORDER BY ma.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByCustodian($custodian) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE custodian_name = ? ORDER BY created_at DESC",
            [$custodian]
        )->fetchAll();
    }
    
    public function getByCondition($condition) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE condition_status = ? ORDER BY created_at DESC",
            [$condition]
        )->fetchAll();
    }
    
    public function getByAssetType($type) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE asset_type = ? ORDER BY created_at DESC",
            [$type]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT ma.*, s.state_name, l.lga_name
                FROM {$this->table} ma
                LEFT JOIN states s ON ma.state_id = s.id
                LEFT JOIN lgas l ON ma.lga_id = l.id
                WHERE ma.asset_code LIKE ? 
                OR ma.asset_type LIKE ? 
                OR ma.make_model LIKE ?
                OR ma.serial_number LIKE ?
                OR ma.custodian_name LIKE ?
                ORDER BY ma.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term, $term])->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'total_value' => 0,
            'by_condition' => [],
            'by_type' => []
        ];
        
        // Total value
        $value = $this->query("SELECT SUM(purchase_value) as total FROM {$this->table}")->fetch();
        $stats['total_value'] = $value['total'] ?? 0;
        
        // By condition
        $conditions = $this->query(
            "SELECT condition_status, COUNT(*) as count, SUM(purchase_value) as value 
             FROM {$this->table} GROUP BY condition_status"
        )->fetchAll();
        
        foreach ($conditions as $c) {
            $stats['by_condition'][$c['condition_status'] ?: 'Unknown'] = [
                'count' => $c['count'],
                'value' => $c['value'] ?? 0
            ];
        }
        
        // By type
        $types = $this->query(
            "SELECT asset_type, COUNT(*) as count, SUM(purchase_value) as value 
             FROM {$this->table} GROUP BY asset_type"
        )->fetchAll();
        
        foreach ($types as $t) {
            $stats['by_type'][$t['asset_type']] = [
                'count' => $t['count'],
                'value' => $t['value'] ?? 0
            ];
        }
        
        return $stats;
    }
    
    public function generateAssetCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT asset_code FROM {$this->table} 
             WHERE asset_code LIKE 'MOV-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("MOV-%s%s-%04d", $year, $month, $seq);
    }
}