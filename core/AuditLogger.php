<?php
/**
 * Audit Logger - Log all system activities
 */
class AuditLogger {
    
    /**
     * Log an action to audit trail
     */
    public static function log($action, $table = null, $recordId = null, $userId = null, $description = null, $oldData = null, $newData = null, $ip = null) {
        $userId = $userId ?? (Auth::check() ? Auth::id() : null);
        $ip = $ip ?? Security::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Convert arrays to JSON — never persist secrets (passwords, tokens,
        // 2FA secrets, CSRF tokens) into the audit trail.
        $oldDataJson = $oldData ? json_encode(Database::redact($oldData)) : null;
        $newDataJson = $newData ? json_encode(Database::redact($newData)) : null;
        
        // Insert into database
        return Database::insert('audit_logs', [
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_data' => $oldDataJson,
            'new_data' => $newDataJson,
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => $userAgent
        ]);
    }
    
    /**
     * Get audit logs with filters
     */
    public static function getLogs($filters = [], $limit = 100, $offset = 0) {
        $sql = "SELECT al.*, u.username, u.full_name 
                FROM audit_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['table_name'])) {
            $sql .= " AND al.table_name = ?";
            $params[] = $filters['table_name'];
        }
        
        if (!empty($filters['record_id'])) {
            $sql .= " AND al.record_id = ?";
            $params[] = $filters['record_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND al.created_at >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND al.created_at <= ?";
            $params[] = $filters['end_date'];
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Log asset creation
     */
    public static function logCreate($table, $recordId, $data) {
        return self::log('CREATE', $table, $recordId, null, 'Created new record', null, $data);
    }
    
    /**
     * Log asset update
     */
    public static function logUpdate($table, $recordId, $oldData, $newData) {
        return self::log('UPDATE', $table, $recordId, null, 'Updated record', $oldData, $newData);
    }
    
    /**
     * Log asset deletion
     */
    public static function logDelete($table, $recordId, $data) {
        return self::log('DELETE', $table, $recordId, null, 'Deleted record', $data, null);
    }
    
    /**
     * Log asset view
     */
    public static function logView($table, $recordId) {
        return self::log('VIEW', $table, $recordId, null, 'Viewed record');
    }
    
    /**
     * Log export
     */
    public static function logExport($type, $format) {
        return self::log('EXPORT', $type, null, null, "Exported $type as $format");
    }
}