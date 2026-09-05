<?php
/**
 * Rented Properties Model
 */
class RentedModel extends Model {
    protected $table = 'rented_properties';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT rp.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                u.full_name as created_by_name
                FROM {$this->table} rp
                LEFT JOIN states s ON rp.state_id = s.id
                LEFT JOIN lgas l ON rp.lga_id = l.id
                LEFT JOIN zones z ON rp.zone_id = z.id
                LEFT JOIN commands c ON rp.command_id = c.id
                LEFT JOIN users u ON rp.created_by = u.id
                WHERE rp.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT rp.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name
                FROM {$this->table} rp
                LEFT JOIN states s ON rp.state_id = s.id
                LEFT JOIN lgas l ON rp.lga_id = l.id
                LEFT JOIN zones z ON rp.zone_id = z.id
                LEFT JOIN commands c ON rp.command_id = c.id
                ORDER BY rp.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getExpiring($days = 30) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE expiry_date IS NOT NULL
             AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND expiry_date >= CURDATE()
             ORDER BY expiry_date ASC",
            [$days]
        )->fetchAll();
    }
    
    public function getExpired() {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE expiry_date IS NOT NULL
             AND expiry_date < CURDATE()
             ORDER BY expiry_date DESC"
        )->fetchAll();
    }
    
    public function getByFundingSource($source) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE funding_source = ? ORDER BY created_at DESC",
            [$source]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT rp.*, s.state_name, l.lga_name
                FROM {$this->table} rp
                LEFT JOIN states s ON rp.state_id = s.id
                LEFT JOIN lgas l ON rp.lga_id = l.id
                WHERE rp.asset_code LIKE ? 
                OR rp.property_address LIKE ? 
                OR rp.owner_lessor_name LIKE ?
                OR rp.lease_agreement_ref LIKE ?
                ORDER BY rp.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term])->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'total_annual_rent' => 0,
            'expiring_soon' => 0,
            'expired' => 0
        ];
        
        // Total annual rent
        $rent = $this->query("SELECT SUM(annual_rent) as total FROM {$this->table}")->fetch();
        $stats['total_annual_rent'] = $rent['total'] ?? 0;
        
        // Expiring soon
        $stats['expiring_soon'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE expiry_date IS NOT NULL
             AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND expiry_date >= CURDATE()"
        )->fetch()['count'];
        
        // Expired
        $stats['expired'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE expiry_date IS NOT NULL
             AND expiry_date < CURDATE()"
        )->fetch()['count'];
        
        return $stats;
    }
    
    public function generateAssetCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT asset_code FROM {$this->table} 
             WHERE asset_code LIKE 'RNT-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("RNT-%s%s-%04d", $year, $month, $seq);
    }
}