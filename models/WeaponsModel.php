<?php
/**
 * Weapons Inventory Model
 */
class WeaponsModel extends Model {
    protected $table = 'weapons_inventory';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT w.*, 
                wt.type_name, wc.calibre_name,
                u.full_name as created_by_name
                FROM {$this->table} w
                LEFT JOIN weapon_types wt ON w.weapon_type_id = wt.id
                LEFT JOIN weapon_calibres wc ON w.calibre_id = wc.id
                LEFT JOIN users u ON w.created_by = u.id
                WHERE w.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT w.*, wt.type_name, wc.calibre_name
                FROM {$this->table} w
                LEFT JOIN weapon_types wt ON w.weapon_type_id = wt.id
                LEFT JOIN weapon_calibres wc ON w.calibre_id = wc.id
                ORDER BY w.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getAvailable() {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE current_location = 'Armoury' ORDER BY weapon_id"
        )->fetchAll();
    }
    
    public function getIssued() {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE current_location = 'Issued' ORDER BY weapon_id"
        )->fetchAll();
    }
    
    public function getByStatus($status) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE `condition` = ? ORDER BY created_at DESC",
            [$status]
        )->fetchAll();
    }
    
    public function getByType($typeId) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE weapon_type_id = ? ORDER BY created_at DESC",
            [$typeId]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT w.*, wt.type_name, wc.calibre_name
                FROM {$this->table} w
                LEFT JOIN weapon_types wt ON w.weapon_type_id = wt.id
                LEFT JOIN weapon_calibres wc ON w.calibre_id = wc.id
                WHERE w.weapon_id LIKE ? 
                OR w.serial_no LIKE ? 
                OR w.make_model LIKE ?
                OR w.custodian LIKE ?
                OR wt.type_name LIKE ?
                ORDER BY w.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term, $term])->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'issued' => 0,
            'serviceable' => 0,
            'unserviceable' => 0,
            'in_repair' => 0,
            'by_type' => [],
            'by_calibre' => []
        ];
        
        // Issued
        $stats['issued'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE current_location = 'Issued'"
        )->fetch()['count'];
        
        // Serviceable
        $stats['serviceable'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE `condition` = 'Serviceable'"
        )->fetch()['count'];
        
        // Unserviceable
        $stats['unserviceable'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE `condition` = 'Unserviceable'"
        )->fetch()['count'];
        
        // In repair
        $stats['in_repair'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE current_location = 'In Repair'"
        )->fetch()['count'];
        
        // By type
        $types = $this->query(
            "SELECT COALESCE(wt.type_name, 'Other') as type, COUNT(*) as count 
             FROM {$this->table} w
             LEFT JOIN weapon_types wt ON w.weapon_type_id = wt.id
             GROUP BY COALESCE(wt.type_name, 'Other')
             ORDER BY count DESC"
        )->fetchAll();
        
        foreach ($types as $t) {
            $stats['by_type'][$t['type']] = $t['count'];
        }
        
        // By calibre
        $calibres = $this->query(
            "SELECT COALESCE(wc.calibre_name, 'Other') as calibre, COUNT(*) as count 
             FROM {$this->table} w
             LEFT JOIN weapon_calibres wc ON w.calibre_id = wc.id
             GROUP BY COALESCE(wc.calibre_name, 'Other')
             ORDER BY count DESC"
        )->fetchAll();
        
        foreach ($calibres as $c) {
            $stats['by_calibre'][$c['calibre']] = $c['count'];
        }
        
        return $stats;
    }
    
    public function checkDuplicateSerial($serial, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE serial_no = ?";
        $params = [$serial];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->query($sql, $params)->fetch()['count'] > 0;
    }
    
    public function checkDuplicateWeaponId($weaponId, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE weapon_id = ?";
        $params = [$weaponId];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->query($sql, $params)->fetch()['count'] > 0;
    }
}