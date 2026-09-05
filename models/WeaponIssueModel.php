<?php
/**
 * NIS Asset Management System
 * 
 * Weapon Issue Model
 * Handles database operations for weapon and ammunition issues
 */

class WeaponIssueModel extends Model {
    
    protected $weaponTable = 'weapon_issue_log';
    protected $ammoTable = 'ammunition_issue_log';
    
    /**
     * Get weapon issue with details
     */
    public function getWeaponIssue($id) {
        $sql = "SELECT wil.*, wi.weapon_id, wi.make_model, wi.serial_no,
                       wc.calibre_name, wt.type_name,
                       issuer.full_name as issued_by_name,
                       req.requisition_number
                FROM {$this->weaponTable} wil
                JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                LEFT JOIN weapon_calibres wc ON wi.calibre_id = wc.id
                LEFT JOIN weapon_types wt ON wi.weapon_type_id = wt.id
                LEFT JOIN users issuer ON wil.issued_by = issuer.id
                LEFT JOIN requisitions req ON wil.requisition_id = req.id
                WHERE wil.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    /**
     * Get ammunition issue with details
     */
    public function getAmmunitionIssue($id) {
        $sql = "SELECT ail.*, ai.ammo_id, ai.ammo_type, ai.calibre,
                       issuer.full_name as issued_by_name,
                       req.requisition_number
                FROM {$this->ammoTable} ail
                JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                LEFT JOIN users issuer ON ail.issued_by = issuer.id
                LEFT JOIN requisitions req ON ail.requisition_id = req.id
                WHERE ail.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    /**
     * Get all weapon issues with pagination
     */
    public function getAllWeaponIssues($limit = 50, $offset = 0, $status = null) {
        $sql = "SELECT wil.*, wi.weapon_id, wi.make_model, wi.serial_no,
                       u.full_name as issued_by_name
                FROM {$this->weaponTable} wil
                JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                LEFT JOIN users u ON wil.issued_by = u.id
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND wil.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY wil.issue_date DESC, wil.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Get all ammunition issues with pagination
     */
    public function getAllAmmunitionIssues($limit = 50, $offset = 0, $status = null) {
        $sql = "SELECT ail.*, ai.ammo_id, ai.ammo_type, ai.calibre,
                       u.full_name as issued_by_name
                FROM {$this->ammoTable} ail
                JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                LEFT JOIN users u ON ail.issued_by = u.id
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND ail.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ail.issue_date DESC, ail.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Get currently issued weapons
     */
    public function getIssuedWeapons() {
        $sql = "SELECT wil.*, wi.weapon_id, wi.make_model, wi.serial_no
                FROM {$this->weaponTable} wil
                JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                WHERE wil.status = 'Issued'
                ORDER BY wil.issue_date DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    /**
     * Get currently issued ammunition
     */
    public function getIssuedAmmunition() {
        $sql = "SELECT ail.*, ai.ammo_id, ai.ammo_type, ai.calibre, ai.balance
                FROM {$this->ammoTable} ail
                JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                WHERE ail.status = 'Issued' OR ail.status IS NULL
                ORDER BY ail.issue_date DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    /**
     * Get overdue issues (expected return date passed)
     */
    public function getOverdueIssues() {
        $sql = "SELECT wil.*, wi.weapon_id, wi.make_model, wi.serial_no,
                       DATEDIFF(CURDATE(), wil.expected_return_date) as days_overdue
                FROM {$this->weaponTable} wil
                JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                WHERE wil.status = 'Issued' 
                  AND wil.expected_return_date IS NOT NULL 
                  AND wil.expected_return_date < CURDATE()
                ORDER BY wil.expected_return_date ASC";
        
        return $this->query($sql)->fetchAll();
    }
    
    /**
     * Get issues by officer name
     */
    public function getByOfficer($officerName, $type = 'weapon') {
        if ($type === 'weapon') {
            $sql = "SELECT wil.*, wi.weapon_id, wi.make_model
                    FROM {$this->weaponTable} wil
                    JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                    WHERE wil.officer_name LIKE ?
                    ORDER BY wil.issue_date DESC";
        } else {
            $sql = "SELECT ail.*, ai.ammo_id, ai.ammo_type
                    FROM {$this->ammoTable} ail
                    JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                    WHERE ail.issued_to LIKE ?
                    ORDER BY ail.issue_date DESC";
        }
        
        return $this->query($sql, ["%$officerName%"])->fetchAll();
    }
    
    /**
     * Get issues by date range
     */
    public function getByDateRange($startDate, $endDate, $type = 'weapon') {
        if ($type === 'weapon') {
            $sql = "SELECT wil.*, wi.weapon_id, wi.make_model
                    FROM {$this->weaponTable} wil
                    JOIN weapons_inventory wi ON wil.weapon_id = wi.id
                    WHERE wil.issue_date BETWEEN ? AND ?
                    ORDER BY wil.issue_date DESC";
        } else {
            $sql = "SELECT ail.*, ai.ammo_id, ai.ammo_type
                    FROM {$this->ammoTable} ail
                    JOIN ammunition_inventory ai ON ail.ammo_id = ai.id
                    WHERE ail.issue_date BETWEEN ? AND ?
                    ORDER BY ail.issue_date DESC";
        }
        
        return $this->query($sql, [$startDate, $endDate])->fetchAll();
    }
    
    /**
     * Get statistics for dashboard
     */
    public function getStatistics() {
        $stats = [
            'total_weapon_issues' => 0,
            'total_ammo_issues' => 0,
            'active_weapon_issues' => 0,
            'active_ammo_issues' => 0,
            'overdue_issues' => 0,
            'weapons_by_type' => [],
            'monthly_trend' => []
        ];
        
        // Total weapon issues
        $stats['total_weapon_issues'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->weaponTable}"
        )->fetch()['count'] ?? 0;
        
        // Total ammunition issues
        $stats['total_ammo_issues'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->ammoTable}"
        )->fetch()['count'] ?? 0;
        
        // Active weapon issues
        $stats['active_weapon_issues'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->weaponTable} WHERE status = 'Issued'"
        )->fetch()['count'] ?? 0;
        
        // Overdue issues
        $stats['overdue_issues'] = $this->query(
            "SELECT COUNT(*) as count FROM {$this->weaponTable} 
             WHERE status = 'Issued' AND expected_return_date IS NOT NULL AND expected_return_date < CURDATE()"
        )->fetch()['count'] ?? 0;
        
        // Monthly trend (last 6 months)
        $stats['monthly_trend'] = $this->query(
            "SELECT DATE_FORMAT(issue_date, '%Y-%m') as month, 
                    COUNT(*) as weapon_count,
                    (SELECT COUNT(*) FROM {$this->ammoTable} WHERE DATE_FORMAT(issue_date, '%Y-%m') = month) as ammo_count
             FROM {$this->weaponTable}
             WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
             ORDER BY month DESC"
        )->fetchAll();
        
        return $stats;
    }
    
    /**
     * Process return of weapon
     */
    public function returnWeapon($issueId, $returnData) {
        $issue = $this->getWeaponIssue($issueId);
        
        if (!$issue) {
            return ['success' => false, 'message' => 'Issue record not found'];
        }
        
        if ($issue['status'] != 'Issued') {
            return ['success' => false, 'message' => 'Weapon has already been returned'];
        }
        
        Database::beginTransaction();
        
        try {
            // Update issue log
            $this->update($this->weaponTable, $issueId, [
                'actual_return_date' => $returnData['return_date'] ?? date('Y-m-d'),
                'return_condition' => $returnData['return_condition'],
                'status' => 'Returned',
                'remarks' => $issue['remarks'] . "\nReturn remarks: " . ($returnData['remarks'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update weapon status
            $weaponModel = new WeaponsModel();
            $weaponModel->update($issue['weapon_id'], [
                'current_location' => 'Armoury',
                'custodian' => null,
                'custodian_rank' => null,
                'custodian_nis' => null
            ]);
            
            Database::commit();
            return ['success' => true, 'message' => 'Weapon returned successfully'];
            
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Weapon return error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process return: ' . $e->getMessage()];
        }
    }
}