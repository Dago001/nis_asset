<?php
/**
 * Permission Model
 */
class PermissionModel extends Model {
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    
    public function getByModule($module) {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE module = ? ORDER BY permission_key",
            [$module]
        )->fetchAll();
    }
    
    public function getAllGrouped() {
        $permissions = $this->query("SELECT * FROM {$this->table} ORDER BY module, permission_key")->fetchAll();
        
        $grouped = [];
        foreach ($permissions as $perm) {
            $grouped[$perm['module']][] = $perm;
        }
        
        return $grouped;
    }
    
    public function getByKey($key) {
        return $this->query("SELECT * FROM {$this->table} WHERE permission_key = ?", [$key])->fetch();
    }
    
    public function getModules() {
        return $this->query("SELECT DISTINCT module FROM {$this->table} ORDER BY module")->fetchAll();
    }
    
    public function getUserPermissions($userId) {
        $sql = "SELECT DISTINCT p.* 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ? 
                AND (rp.can_view = 1 OR rp.can_create = 1 OR rp.can_edit = 1 OR rp.can_delete = 1 OR rp.can_approve = 1)
                ORDER BY p.module, p.permission_key";
        
        return $this->query($sql, [$userId])->fetchAll();
    }
    
    public function getRolePermissions($roleId) {
        $sql = "SELECT p.*, rp.can_view, rp.can_create, rp.can_edit, rp.can_delete, rp.can_approve
                FROM permissions p
                LEFT JOIN role_permissions rp ON p.id = rp.permission_id AND rp.role_id = ?
                ORDER BY p.module, p.permission_key";
        
        return $this->query($sql, [$roleId])->fetchAll();
    }
    
    public function createIfNotExists($key, $module, $description) {
        $existing = $this->getByKey($key);
        if ($existing) return $existing['id'];
        
        return $this->create([
            'permission_key' => $key,
            'module' => $module,
            'description' => $description
        ]);
    }
}