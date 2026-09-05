<?php
/**
 * Base Model Class
 */
class Model {
    
    protected $table;
    protected $primaryKey = 'id';
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Whitelist an ORDER BY column + direction so they can be safely inlined.
     * Returns "" when $orderBy is falsy, otherwise " ORDER BY `col` DIR".
     */
    protected function safeOrderBy($orderBy, $direction = 'ASC') {
        if (!$orderBy || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string) $orderBy)) {
            return '';
        }
        $dir = strtoupper((string) $direction) === 'DESC' ? 'DESC' : 'ASC';
        return " ORDER BY `{$orderBy}` {$dir}";
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all records
     */
    public function all($orderBy = null, $direction = 'ASC') {
        $sql = "SELECT * FROM {$this->table}" . $this->safeOrderBy($orderBy, $direction);

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get records with pagination
     */
    public function paginate($page = 1, $limit = 10, $orderBy = null, $direction = 'ASC') {
        $page   = max(1, (int) $page);
        $limit  = max(1, min(200, (int) $limit));
        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM {$this->table}"
             . $this->safeOrderBy($orderBy, $direction)
             . " LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as count FROM {$this->table}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute();
        $total = $countStmt->fetch()['count'];
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'last_page' => ceil($total / $limit)
        ];
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }
        $setClause = implode(', ', $set);
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $values = array_values($data);
        $values[] = $id;
        
        return $stmt->execute($values);
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Count records
     */
    public function count($where = null, $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'];
    }
    
    /**
     * Find by where clause
     */
    public function where($conditions, $params = []) {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Find one by where clause
     */
    public function findWhere($conditions, $params = []) {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions} LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Execute raw query
     */
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}