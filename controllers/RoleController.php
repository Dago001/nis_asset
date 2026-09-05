<?php
/**
 * Role & Permission Management Controller
 */
class RoleController extends Controller {
    
    public function __construct() {
        if (method_exists(get_parent_class($this), '__construct')) {
            parent::__construct();
        }
    }
    
    /**
     * Display all roles with user counts
     */
    public function index() {
        // Only Super Admin can manage roles
        if (!Auth::isSuperAdmin()) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to view roles and permissions']);
            return;
        }
        
        $roleModel = new RoleModel();
        $roles = $roleModel->getAllWithUserCount();
        
        $this->view('roles/index', [
            'roles' => $roles,
            'title' => 'Roles & Permissions',
            'active' => 'roles'
        ]);
    }
    
    /**
     * Edit a role's permissions matrix
     */
    public function edit($id) {
        if (!Auth::isSuperAdmin()) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to edit roles']);
            return;
        }
        
        $roleModel = new RoleModel();
        $role = $roleModel->getWithPermissions($id);
        
        if (!$role) {
            $this->redirect('roles', ['error' => 'Role not found']);
            return;
        }
        
        // Group permissions by module
        $groupedPermissions = [];
        foreach ($role['permissions'] as $perm) {
            $groupedPermissions[$perm['module']][] = $perm;
        }
        
        $this->view('roles/edit', [
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
            'title' => 'Edit Role Permissions',
            'active' => 'roles'
        ]);
    }
    
    /**
     * Save the updated permissions matrix
     */
    public function update($id) {
        if (!Auth::isSuperAdmin()) {
            $this->redirect('dashboard', ['error' => 'You do not have permission to update roles']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('roles', ['error' => 'Invalid request method']);
            return;
        }
        
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirect("roles/edit/$id", ['error' => 'Invalid security token']);
            return;
        }
        
        $roleModel = new RoleModel();
        $role = $roleModel->find($id);
        if (!$role) {
            $this->redirect('roles', ['error' => 'Role not found']);
            return;
        }
        
        Database::beginTransaction();
        try {
            // Get all permission IDs
            $permissions = Database::fetchAll("SELECT id FROM permissions");
            
            // Loop through each permission and check if checkboxes were selected
            foreach ($permissions as $p) {
                $pid = $p['id'];
                
                // Get checkbox status
                $view = isset($_POST["perm_{$pid}_view"]) ? 1 : 0;
                $create = isset($_POST["perm_{$pid}_create"]) ? 1 : 0;
                $edit = isset($_POST["perm_{$pid}_edit"]) ? 1 : 0;
                $delete = isset($_POST["perm_{$pid}_delete"]) ? 1 : 0;
                $approve = isset($_POST["perm_{$pid}_approve"]) ? 1 : 0;
                
                if ($view || $create || $edit || $delete || $approve) {
                    $roleModel->assignPermission($id, $pid, [
                        'view' => $view,
                        'create' => $create,
                        'edit' => $edit,
                        'delete' => $delete,
                        'approve' => $approve
                    ]);
                } else {
                    $roleModel->removePermission($id, $pid);
                }
            }
            
            Database::commit();
            $this->redirect('roles', ['success' => 'Permissions updated successfully for ' . htmlspecialchars($role['role_name'])]);
        } catch (Exception $e) {
            Database::rollBack();
            error_log("Error updating permissions: " . $e->getMessage());
            $this->redirect("roles/edit/$id", ['error' => 'Failed to update permissions: ' . $e->getMessage()]);
        }
    }
}
