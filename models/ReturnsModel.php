<?php
/**
 * Returns Model
 */
class ReturnsModel extends Model {
    protected $table = 'returns';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT r.*, 
                u.full_name as received_by_name,
                creator.full_name as created_by_name,
                req.requisition_number
                FROM {$this->table} r
                LEFT JOIN users u ON r.received_by = u.id
                LEFT JOIN users creator ON r.created_by = creator.id
                LEFT JOIN requisitions req ON r.requisition_id = req.id
                WHERE r.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT r.*, 
                u.full_name as received_by_name,
                req.requisition_number
                FROM {$this->table} r
                LEFT JOIN users u ON r.received_by = u.id
                LEFT JOIN requisitions req ON r.requisition_id = req.id
                ORDER BY r.created_at DESC
                LIMIT 100";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getWeapons($returnId) {
        return $this->query(
            "SELECT rw.*, w.weapon_id, w.make_model
             FROM return_weapons rw
             JOIN weapons_inventory w ON rw.weapon_id = w.id
             WHERE rw.return_id = ?",
            [$returnId]
        )->fetchAll();
    }
    
    public function getAmmunition($returnId) {
        return $this->query(
            "SELECT ra.*, a.ammo_id, a.ammo_type, a.calibre
             FROM return_ammunition ra
             JOIN ammunition_inventory a ON ra.ammo_id = a.id
             WHERE ra.return_id = ?",
            [$returnId]
        )->fetchAll();
    }
    
    public function getByStatus($status) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC",
            [$status]
        )->fetchAll();
    }
    
    public function getByOfficer($officer) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE returning_officer_name LIKE ? 
             ORDER BY return_date DESC",
            ["%$officer%"]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT r.*, req.requisition_number
                FROM {$this->table} r
                LEFT JOIN requisitions req ON r.requisition_id = req.id
                WHERE r.return_number LIKE ? 
                OR r.returning_officer_name LIKE ? 
                OR r.returning_unit LIKE ?
                OR req.requisition_number LIKE ?
                ORDER BY r.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term])->fetchAll();
    }
    
    public function processReturn($id, $userId) {
        Database::beginTransaction();
        
        try {
            // Update return status
            $this->update($id, [
                'status' => 'Processed',
                'received_by' => $userId
            ]);
            
            // Update weapon statuses
            $weapons = $this->getWeapons($id);
            foreach ($weapons as $weapon) {
                Database::update('weapons_inventory', [
                    'current_location' => 'Armoury',
                    'custodian' => null
                ], 'id = ?', [$weapon['weapon_id']]);
            }
            
            // Update ammunition balances
            $ammo = $this->getAmmunition($id);
            foreach ($ammo as $item) {
                $ammoModel = new AmmunitionModel();
                $ammoModel->updateBalance($item['ammo_id'], $item['rounds_used']);
            }
            
            Database::commit();
            return true;
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Return processing error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'pending' => 0,
            'processed' => 0,
            'verified' => 0,
            'completed' => 0,
            'total_weapons_returned' => 0,
            'total_ammunition_returned' => 0
        ];
        
        // Count by status
        $statuses = $this->query(
            "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status"
        )->fetchAll();
        
        foreach ($statuses as $s) {
            $stats[strtolower($s['status'])] = $s['count'];
        }
        
        // Total weapons returned
        $weapons = $this->query(
            "SELECT SUM(arm_total) as total FROM return_weapons"
        )->fetch();
        $stats['total_weapons_returned'] = $weapons['total'] ?? 0;
        
        // Total ammunition returned
        $ammo = $this->query(
            "SELECT SUM(rounds_returned) as total FROM return_ammunition"
        )->fetch();
        $stats['total_ammunition_returned'] = $ammo['total'] ?? 0;
        
        return $stats;
    }
    
    public function generateReturnNumber() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT return_number FROM {$this->table} 
             WHERE return_number LIKE 'RET-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['return_number'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("RET-%s%s-%04d", $year, $month, $seq);
    }
}