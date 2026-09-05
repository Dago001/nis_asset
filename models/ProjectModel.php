<?php
/**
 * Ongoing Projects Model
 */
class ProjectModel extends Model {
    protected $table = 'ongoing_projects';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT p.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name,
                u.full_name as created_by_name
                FROM {$this->table} p
                LEFT JOIN states s ON p.state_id = s.id
                LEFT JOIN lgas l ON p.lga_id = l.id
                LEFT JOIN zones z ON p.zone_id = z.id
                LEFT JOIN commands c ON p.command_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT p.*, 
                s.state_name, l.lga_name, z.zone_name, c.command_name
                FROM {$this->table} p
                LEFT JOIN states s ON p.state_id = s.id
                LEFT JOIN lgas l ON p.lga_id = l.id
                LEFT JOIN zones z ON p.zone_id = z.id
                LEFT JOIN commands c ON p.command_id = c.id
                ORDER BY p.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByStatus($status) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC",
            [$status]
        )->fetchAll();
    }
    
    public function getOverdue() {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE expected_completion_date IS NOT NULL
             AND expected_completion_date < CURDATE()
             AND status IN ('Planning', 'In Progress')
             ORDER BY expected_completion_date ASC"
        )->fetchAll();
    }
    
    public function getByZone($zoneId) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE zone_id = ? ORDER BY created_at DESC",
            [$zoneId]
        )->fetchAll();
    }
    
    public function search($term) {
        $sql = "SELECT p.*, s.state_name, l.lga_name
                FROM {$this->table} p
                LEFT JOIN states s ON p.state_id = s.id
                LEFT JOIN lgas l ON p.lga_id = l.id
                WHERE p.project_code LIKE ? 
                OR p.project_title LIKE ? 
                OR p.contractor LIKE ?
                ORDER BY p.created_at DESC
                LIMIT 100";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term])->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'total_value' => 0,
            'by_status' => [],
            'overdue' => 0
        ];
        
        // Total value
        $value = $this->query("SELECT SUM(contract_sum) as total FROM {$this->table}")->fetch();
        $stats['total_value'] = $value['total'] ?? 0;
        
        // By status
        $statuses = $this->query(
            "SELECT status, COUNT(*) as count, SUM(contract_sum) as value 
             FROM {$this->table} GROUP BY status"
        )->fetchAll();
        
        foreach ($statuses as $s) {
            $stats['by_status'][$s['status']] = [
                'count' => $s['count'],
                'value' => $s['value'] ?? 0
            ];
        }
        
        // Overdue
        $stats['overdue'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE expected_completion_date IS NOT NULL
             AND expected_completion_date < CURDATE()
             AND status IN ('Planning', 'In Progress')"
        )->fetch()['count'];
        
        return $stats;
    }
    
    public function generateProjectCode() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT project_code FROM {$this->table} 
             WHERE project_code LIKE 'PRJ-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['project_code'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("PRJ-%s%s-%04d", $year, $month, $seq);
    }
}