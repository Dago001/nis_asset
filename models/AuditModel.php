<?php
/**
 * Quarterly Audit Model
 */
class AuditModel extends Model {
    protected $table = 'quarterly_audits';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT qa.*, 
                u.full_name as created_by_name, c.command_name
                FROM {$this->table} qa
                LEFT JOIN users u ON qa.created_by = u.id
                LEFT JOIN commands c ON qa.command_id = c.id
                WHERE qa.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT qa.*, c.command_name
                FROM {$this->table} qa
                LEFT JOIN commands c ON qa.command_id = c.id
                ORDER BY qa.created_at DESC
                LIMIT 100";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByQuarter($quarter, $year) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE quarter = ? AND year = ? ORDER BY audit_date DESC",
            [$quarter, $year]
        )->fetchAll();
    }
    
    public function getByDateRange($startDate, $endDate) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE audit_date BETWEEN ? AND ? 
             ORDER BY audit_date DESC",
            [$startDate, $endDate]
        )->fetchAll();
    }
    
    public function getWeapons($auditId) {
        return $this->query(
            "SELECT aw.*, w.weapon_id, w.make_model, w.serial_no
             FROM audit_weapons aw
             JOIN weapons_inventory w ON aw.weapon_id = w.id
             WHERE aw.audit_id = ?",
            [$auditId]
        )->fetchAll();
    }
    
    public function getAmmunition($auditId) {
        return $this->query(
            "SELECT aa.*, a.ammo_id, a.ammo_type, a.calibre
             FROM audit_ammunition aa
             JOIN ammunition_inventory a ON aa.ammo_id = a.id
             WHERE aa.audit_id = ?",
            [$auditId]
        )->fetchAll();
    }
    
    public function getMissingWeapons($auditId) {
        return $this->query(
            "SELECT * FROM audit_missing_weapons WHERE audit_id = ?",
            [$auditId]
        )->fetchAll();
    }
    
    public function getVarianceSummary($auditId) {
        $sql = "SELECT 
                SUM(CASE WHEN variance_value != 0 THEN 1 ELSE 0 END) as items_with_variance,
                SUM(CASE WHEN variance_value < 0 THEN 1 ELSE 0 END) as negative_variance,
                SUM(CASE WHEN variance_value > 0 THEN 1 ELSE 0 END) as positive_variance,
                SUM(ABS(variance_value)) as total_absolute_variance
                FROM (
                    SELECT variance_value FROM audit_weapons WHERE audit_id = ?
                    UNION ALL
                    SELECT variance_value FROM audit_ammunition WHERE audit_id = ?
                ) as all_variances";
        
        return $this->query($sql, [$auditId, $auditId])->fetch();
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'by_quarter' => [],
            'total_weapons_audited' => 0,
            'total_ammunition_audited' => 0,
            'total_variances' => 0,
            'total_missing' => 0
        ];
        
        // By quarter
        $quarters = $this->query(
            "SELECT CONCAT(quarter, ' ', year) as period, COUNT(*) as count 
             FROM {$this->table} GROUP BY year, quarter ORDER BY year DESC, quarter DESC"
        )->fetchAll();
        
        foreach ($quarters as $q) {
            $stats['by_quarter'][$q['period']] = $q['count'];
        }
        
        // Totals
        $totals = $this->query(
            "SELECT 
                SUM(total_weapons_audited) as weapons,
                SUM(total_ammunition_audited) as ammo,
                SUM(weapons_with_variance + ammunition_with_variance) as variances,
                SUM(total_missing_weapons) as missing
             FROM {$this->table}"
        )->fetch();
        
        $stats['total_weapons_audited'] = $totals['weapons'] ?? 0;
        $stats['total_ammunition_audited'] = $totals['ammo'] ?? 0;
        $stats['total_variances'] = $totals['variances'] ?? 0;
        $stats['total_missing'] = $totals['missing'] ?? 0;
        
        return $stats;
    }
    
    public function generateAuditNumber() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT audit_number FROM {$this->table} 
             WHERE audit_number LIKE 'AUD-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['audit_number'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("AUD-%s%s-%04d", $year, $month, $seq);
    }
}