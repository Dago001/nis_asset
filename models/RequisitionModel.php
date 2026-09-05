<?php
/**
 * Requisitions Model
 */
class RequisitionModel extends Model {
    protected $table = 'requisitions';
    protected $primaryKey = 'id';
    
    public function getWithDetails($id) {
        $sql = "SELECT r.*, 
                u.full_name as requester_name, c.command_name,
                creator.full_name as created_by_name,
                approver.full_name as approved_by_name
                FROM {$this->table} r
                LEFT JOIN users u ON r.requesting_officer_id = u.id
                LEFT JOIN commands c ON r.requesting_command_id = c.id
                LEFT JOIN users creator ON r.created_by = creator.id
                LEFT JOIN users approver ON r.approved_by = approver.id
                WHERE r.id = ?";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT r.*, 
                u.full_name as requester_name, c.command_name,
                COUNT(ri.id) as item_count
                FROM {$this->table} r
                LEFT JOIN users u ON r.requesting_officer_id = u.id
                LEFT JOIN commands c ON r.requesting_command_id = c.id
                LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
                GROUP BY r.id
                ORDER BY r.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getPending() {
        $sql = "SELECT r.*, u.full_name as requester_name, c.command_name
                FROM {$this->table} r
                LEFT JOIN users u ON r.requesting_officer_id = u.id
                LEFT JOIN commands c ON r.requesting_command_id = c.id
                WHERE r.status = 'Pending'
                ORDER BY r.priority_level DESC, r.created_at ASC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getUserRequisitions($userId) {
        $sql = "SELECT r.*, c.command_name, COUNT(ri.id) as item_count
                FROM {$this->table} r
                LEFT JOIN commands c ON r.requesting_command_id = c.id
                LEFT JOIN requisition_items ri ON r.id = ri.requisition_id
                WHERE r.created_by = ?
                GROUP BY r.id
                ORDER BY r.created_at DESC";
        
        return $this->query($sql, [$userId])->fetchAll();
    }
    
    public function getByStatus($status) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC",
            [$status]
        )->fetchAll();
    }
    
    public function getByDateRange($startDate, $endDate) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE requisition_date BETWEEN ? AND ? 
             ORDER BY requisition_date DESC",
            [$startDate, $endDate]
        )->fetchAll();
    }
    
    public function getItems($requisitionId) {
        $sql = "SELECT ri.*,
                wt.type_name as weapon_type_name,
                at.ammo_type as ammo_type_name,
                ac.calibre as calibre_name
                FROM requisition_items ri
                LEFT JOIN weapon_types wt ON ri.weapon_type_id = wt.id
                LEFT JOIN ammunition_types at ON ri.ammo_type_id = at.id
                LEFT JOIN ammunition_calibres ac ON ri.calibre_id = ac.id
                WHERE ri.requisition_id = ?
                ORDER BY ri.id";
        
        return $this->query($sql, [$requisitionId])->fetchAll();
    }
    
    public function approve($id, $userId, $remarks = null) {
        return $this->update($id, [
            'status' => 'Approved',
            'approved_by' => $userId,
            'approval_date' => date('Y-m-d H:i:s'),
            'approval_remarks' => $remarks
        ]);
    }
    
    public function reject($id, $userId, $reason) {
        return $this->update($id, [
            'status' => 'Rejected',
            'approved_by' => $userId,
            'approval_date' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason
        ]);
    }
    
    public function markIssued($id, $userId) {
        return $this->update($id, [
            'status' => 'Issued',
            'issued_by' => $userId,
            'issue_date' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function markCompleted($id) {
        return $this->update($id, ['status' => 'Completed']);
    }
    
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'issued' => 0,
            'completed' => 0,
            'by_priority' => []
        ];
        
        // Count by status
        $statuses = $this->query(
            "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status"
        )->fetchAll();
        
        foreach ($statuses as $s) {
            $stats[strtolower($s['status'])] = $s['count'];
        }
        
        // By priority
        $priorities = $this->query(
            "SELECT priority_level, COUNT(*) as count FROM {$this->table} GROUP BY priority_level"
        )->fetchAll();
        
        foreach ($priorities as $p) {
            $stats['by_priority'][$p['priority_level']] = $p['count'];
        }
        
        return $stats;
    }
    
    public function generateRequisitionNumber() {
        $year = date('Y');
        $month = date('m');
        
        $last = $this->query(
            "SELECT requisition_number FROM {$this->table} 
             WHERE requisition_number LIKE 'REQ-{$year}{$month}%' 
             ORDER BY id DESC LIMIT 1"
        )->fetch();
        
        if ($last) {
            $seq = intval(substr($last['requisition_number'], -4)) + 1;
        } else {
            $seq = 1;
        }
        
        return sprintf("REQ-%s%s-%04d", $year, $month, $seq);
    }
}