<?php
/**
 * Aircraft Assets Model
 */
class AircraftModel extends Model {
    protected $table = 'aircraft_assets';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT a.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                u.full_name as created_by_name
                FROM {$this->table} a
                LEFT JOIN states s ON a.state_id = s.id
                LEFT JOIN lgas l ON a.lga_id = l.id
                LEFT JOIN zones z ON a.zone_id = z.id
                LEFT JOIN commands c ON a.command_id = c.id
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT a.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name
                FROM {$this->table} a
                LEFT JOIN states s ON a.state_id = s.id
                LEFT JOIN lgas l ON a.lga_id = l.id
                LEFT JOIN zones z ON a.zone_id = z.id
                LEFT JOIN commands c ON a.command_id = c.id
                ORDER BY a.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByType($type) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE aircraft_type = ? ORDER BY created_at DESC",
            [$type]
        )->fetchAll();
    }
    
    public function getByStatus($status) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE operational_status = ? ORDER BY created_at DESC",
            [$status]
        )->fetchAll();
    }
    
    public function getMaintenanceDue($days = 30) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE next_overhaul IS NOT NULL
             AND next_overhaul <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND next_overhaul >= CURDATE()
             ORDER BY next_overhaul ASC",
            [$days]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT a.*, s.state_name, l.lga_name
                FROM {$this->table} a
                LEFT JOIN states s ON a.state_id = s.id
                LEFT JOIN lgas l ON a.lga_id = l.id
                WHERE a.asset_code LIKE ? 
                OR a.tail_number LIKE ? 
                OR a.model_manufacturer LIKE ?
                OR a.chassis_serial LIKE ?
                OR a.assigned_unit_pilot LIKE ?
                ORDER BY a.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term, $term])->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'total_value' => 0,
            'by_type' => [],
            'by_status' => [],
            'total_flight_hours' => 0,
            'maintenance_due' => 0
        ];
        
        // Total value
        $value = $this->query("SELECT SUM(capital_value) as total FROM {$this->table}")->fetch();
        $stats['total_value'] = $value['total'] ?? 0;
        
        // By type
        $types = $this->query(
            "SELECT aircraft_type, COUNT(*) as count, SUM(capital_value) as value 
             FROM {$this->table} GROUP BY aircraft_type"
        )->fetchAll();
        
        foreach ($types as $t) {
            $stats['by_type'][$t['aircraft_type'] ?: 'Unknown'] = [
                'count' => $t['count'],
                'value' => $t['value'] ?? 0
            ];
        }
        
        // By status
        $statuses = $this->query(
            "SELECT operational_status, COUNT(*) as count FROM {$this->table} GROUP BY operational_status"
        )->fetchAll();
        
        foreach ($statuses as $s) {
            $stats['by_status'][$s['operational_status'] ?: 'Unknown'] = $s['count'];
        }
        
        // Total flight hours
        $hours = $this->query("SELECT SUM(flight_hours) as total FROM {$this->table}")->fetch();
        $stats['total_flight_hours'] = $hours['total'] ?? 0;
        
        // Maintenance due
        $stats['maintenance_due'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE next_overhaul IS NOT NULL
             AND next_overhaul <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND next_overhaul >= CURDATE()"
        )->fetch()['count'];
        
        return $stats;
    }
    
    public function generateAssetCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT asset_code FROM {$this->table} 
             WHERE asset_code LIKE 'AC-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['asset_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("AC-%s%s-%04d", $year, $month, $seq);
    }
}