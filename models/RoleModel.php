<?php
/**
 * Role Model
 */
class RoleModel extends Model {
    protected $table = 'roles';
    protected $primaryKey = 'id';
    
    public function getWithPermissions($id) {
        $role = $this->find($id);
        if (!$role) return null;
        
        $permissions = $this->query(
            "SELECT p.*, rp.can_view, rp.can_create, rp.can_edit, rp.can_delete, rp.can_approve
             FROM permissions p
             LEFT JOIN role_permissions rp ON p.id = rp.permission_id AND rp.role_id = ?
             ORDER BY p.module, p.permission_key",
            [$id]
        )->fetchAll();
        
        $role['permissions'] = $permissions;
        return $role;
    }
    
    public function getAllWithUserCount() {
        $sql = "SELECT r.*, COUNT(ur.user_id) as user_count
                FROM {$this->table} r
                LEFT JOIN user_roles ur ON r.id = ur.role_id
                GROUP BY r.id
                ORDER BY r.role_name";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByName($name) {
        return $this->query("SELECT * FROM {$this->table} WHERE role_name = ?", [$name])->fetch();
    }
    
    public function assignPermission($roleId, $permissionId, $perms) {
        $sql = "INSERT INTO role_permissions 
                (role_id, permission_id, can_view, can_create, can_edit, can_delete, can_approve)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                can_view = VALUES(can_view),
                can_create = VALUES(can_create),
                can_edit = VALUES(can_edit),
                can_delete = VALUES(can_delete),
                can_approve = VALUES(can_approve)";
        
        return $this->query($sql, [
            $roleId,
            $permissionId,
            $perms['view'] ?? 0,
            $perms['create'] ?? 0,
            $perms['edit'] ?? 0,
            $perms['delete'] ?? 0,
            $perms['approve'] ?? 0
        ]);
    }
    
    public function removePermission($roleId, $permissionId) {
        return $this->query(
            "DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?",
            [$roleId, $permissionId]
        );
    }
    
    public function getSystemRoles() {
        return $this->query("SELECT * FROM {$this->table} WHERE is_system_role = 1 ORDER BY role_name")->fetchAll();
    }
    
    public function getCustomRoles() {
        return $this->query("SELECT * FROM {$this->table} WHERE is_system_role = 0 ORDER BY role_name")->fetchAll();
    }
}