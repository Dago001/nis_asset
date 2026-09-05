<?php
/**
 * Ammunition Inventory Model
 */
class AmmunitionModel extends Model {
    protected $table = 'ammunition_inventory';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT a.*, 
                at.ammo_type, ac.calibre,
                u.full_name as created_by_name
                FROM {$this->table} a
                LEFT JOIN ammunition_types at ON a.ammo_type_id = at.id
                LEFT JOIN ammunition_calibres ac ON a.calibre_id = ac.id
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT a.*, at.ammo_type, ac.calibre
                FROM {$this->table} a
                LEFT JOIN ammunition_types at ON a.ammo_type_id = at.id
                LEFT JOIN ammunition_calibres ac ON a.calibre_id = ac.id
                ORDER BY a.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getAvailable() {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE balance > 0 ORDER BY ammo_id"
        )->fetchAll();
    }
    
    public function getExpiring($days = 30) {
        return $this->query(
            "SELECT a.*, at.ammo_type, ac.calibre,
                    DATEDIFF(expiry_date, CURDATE()) as days_remaining
             FROM {$this->table} a
             LEFT JOIN ammunition_types at ON a.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON a.calibre_id = ac.id
             WHERE a.expiry_date IS NOT NULL
             AND a.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND a.expiry_date >= CURDATE()
             ORDER BY a.expiry_date ASC",
            [$days]
        )->fetchAll();
    }
    
    public function getLowStock($threshold = 100) {
        return $this->query(
            "SELECT a.*, at.ammo_type, ac.calibre
             FROM {$this->table} a
             LEFT JOIN ammunition_types at ON a.ammo_type_id = at.id
             LEFT JOIN ammunition_calibres ac ON a.calibre_id = ac.id
             WHERE a.balance < ?
             ORDER BY a.balance ASC",
            [$threshold]
        )->fetchAll();
    }
    
    public function getByCalibre($calibreId) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE calibre_id = ? ORDER BY created_at DESC",
            [$calibreId]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT a.*, at.ammo_type, ac.calibre
                FROM {$this->table} a
                LEFT JOIN ammunition_types at ON a.ammo_type_id = at.id
                LEFT JOIN ammunition_calibres ac ON a.calibre_id = ac.id
                WHERE a.ammo_id LIKE ? 
                OR a.batch_number LIKE ? 
                OR at.ammo_type LIKE ?
                OR ac.calibre LIKE ?
                ORDER BY a.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term])->fetchAll();
    }
    
    public function updateBalance($id, $issued) {
        $ammo = $this->find($id);
        if (!$ammo) return false;
        
        $newIssued = $ammo['quantity_issued'] + $issued;
        $newBalance = $ammo['quantity_received'] - $newIssued;
        
        return $this->update($id, [
            'quantity_issued' => $newIssued,
            'balance' => $newBalance
        ]);
    }
    
    public function getStatistics() {
        $stats = [
            'total_types' => $this->count(),
            'total_rounds' => 0,
            'expiring_soon' => 0,
            'low_stock' => 0,
            'by_calibre' => []
        ];
        
        // Total rounds
        $rounds = $this->query("SELECT SUM(balance) as total FROM {$this->table}")->fetch();
        $stats['total_rounds'] = $rounds['total'] ?? 0;
        
        // Expiring soon
        $stats['expiring_soon'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE expiry_date IS NOT NULL
             AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND expiry_date >= CURDATE()"
        )->fetch()['count'];
        
        // Low stock
        $stats['low_stock'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE balance < 100"
        )->fetch()['count'];
        
        // By calibre
        $calibres = $this->query(
            "SELECT COALESCE(ac.calibre, 'Other') as calibre, 
                    COUNT(*) as type_count, 
                    SUM(balance) as total_rounds
             FROM {$this->table} a
             LEFT JOIN ammunition_calibres ac ON a.calibre_id = ac.id
             GROUP BY COALESCE(ac.calibre, 'Other')
             ORDER BY total_rounds DESC"
        )->fetchAll();
        
        foreach ($calibres as $c) {
            $stats['by_calibre'][$c['calibre']] = [
                'type_count' => $c['type_count'],
                'total_rounds' => $c['total_rounds']
            ];
        }
        
        return $stats;
    }
    
    public function checkDuplicateAmmoId($ammoId, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE ammo_id = ?";
        $params = [$ammoId];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->query($sql, $params)->fetch()['count'] > 0;
    }
}