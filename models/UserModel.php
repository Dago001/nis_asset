<?php
/**
 * User Model
 */
class UserModel extends Model {
    protected $table = 'users';
    protected $primaryKey = 'id';
    
    public function getWithRoles($id) {
        $sql = "SELECT u.*, 
                GROUP_CONCAT(r.role_name SEPARATOR ', ') as role_names,
                GROUP_CONCAT(r.id) as role_ids
                FROM {$this->table} u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.id = ?
                GROUP BY u.id";
        
        return $this->query($sql, [$id])->fetch();
    }
    
    public function getAllWithRoles() {
        $sql = "SELECT u.*, 
                GROUP_CONCAT(r.role_name SEPARATOR ', ') as role_names
                FROM {$this->table} u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                GROUP BY u.id
                ORDER BY u.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
    
    public function getByUsername($username) {
        return $this->query("SELECT * FROM {$this->table} WHERE username = ?", [$username])->fetch();
    }
    
    public function getByEmail($email) {
        return $this->query("SELECT * FROM {$this->table} WHERE email = ?", [$email])->fetch();
    }
    
    public function updateLastLogin($id, $ip) {
        return $this->update($id, [
            'last_login' => date('Y-m-d H:i:s'),
            'last_ip' => $ip
        ]);
    }
    
    public function incrementLoginAttempts($id) {
        $user = $this->find($id);
        $attempts = ($user['login_attempts'] ?? 0) + 1;
        
        $data = ['login_attempts' => $attempts];
        
        if ($attempts >= Config::get('max_login_attempts', 5)) {
            $data['lockout_until'] = date('Y-m-d H:i:s', time() + Config::get('lockout_duration', 900));
        }
        
        return $this->update($id, $data);
    }
    
    public function resetLoginAttempts($id) {
        return $this->update($id, [
            'login_attempts' => 0,
            'lockout_until' => null
        ]);
    }
    
    public function changePassword($id, $password) {
        return $this->update($id, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }
    
    public function setPasswordResetToken($email) {
        $user = $this->getByEmail($email);
        if (!$user) return false;
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        $this->update($user['id'], [
            'password_reset_token' => $token,
            'password_reset_expires' => $expires
        ]);
        
        return $token;
    }
    
    public function verifyPasswordResetToken($token) {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE password_reset_token = ? 
             AND password_reset_expires > NOW()",
            [$token]
        )->fetch();
    }
    
    public function clearPasswordResetToken($id) {
        return $this->update($id, [
            'password_reset_token' => null,
            'password_reset_expires' => null
        ]);
    }
    
    public function enable2FA($id, $secret) {
        return $this->update($id, [
            'two_factor_secret' => $secret,
            'two_factor_enabled' => 1
        ]);
    }
    
    public function disable2FA($id) {
        return $this->update($id, [
            'two_factor_secret' => null,
            'two_factor_enabled' => 0
        ]);
    }
    
    public function search($term) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE username LIKE ? 
                OR full_name LIKE ? 
                OR email LIKE ? 
                OR nis_number LIKE ?
                ORDER BY full_name ASC
                LIMIT 50";
        
        $term = "%$term%";
        return $this->query($sql, [$term, $term, $term, $term])->fetchAll();
    }
}