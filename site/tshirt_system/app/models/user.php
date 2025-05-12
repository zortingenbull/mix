<?php
/**
 * User Model
 * 
 * Handles database operations for users
 */

require_once ROOT_PATH . '/config/database.php';
require_once APP_PATH . '/helpers/security.php';

class UserModel {
    private $conn;
    private $table = 'users';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all users
     */
    public function getAll() {
        $query = "SELECT id, name, email, role, created_at FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        $query = "SELECT id, name, email, role, created_at FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        
        return $stmt->fetch();
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash the password
        $password = SecurityHelper::hashPassword($data['password']);
        
        $stmt->execute([
            $data['name'],
            $data['email'],
            $password,
            $data['role']
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Update user
     */
    public function update($data) {
        $fields = [];
        $values = [];
        
        // Only include fields that are present in the data array
        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = $data['name'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $values[] = $data['email'];
        }
        
        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = SecurityHelper::hashPassword($data['password']);
        }
        
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $values[] = $data['role'];
        }
        
        // Add ID at the end for the WHERE clause
        $values[] = $data['id'];
        
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($values);
    }
    
    /**
     * Delete user
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Verify user credentials
     */
    public function verify($email, $password) {
        $user = $this->getByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if (SecurityHelper::verifyPassword($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Store password reset token
     */
    public function storeResetToken($userId, $token, $expiry) {
        $query = "UPDATE " . $this->table . " SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([$token, $expiry, $userId]);
    }
    
    /**
     * Verify reset token
     */
    public function verifyResetToken($token) {
        $query = "SELECT id FROM " . $this->table . " WHERE reset_token = ? AND reset_token_expiry > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$token]);
        
        return $stmt->fetch();
    }
    
    /**
     * Reset user password
     */
    public function resetPassword($userId, $newPassword) {
        $query = "UPDATE " . $this->table . " SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        $password = SecurityHelper::hashPassword($newPassword);
        
        return $stmt->execute([$password, $userId]);
    }
    
    /**
     * Get user permissions
     */
    public function getUserPermissions($userId) {
        $query = "SELECT p.name FROM permissions p
                 JOIN user_roles ur ON p.id = ur.permission_id
                 WHERE ur.user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        
        $permissions = [];
        while ($row = $stmt->fetch()) {
            $permissions[] = $row['name'];
        }
        
        return $permissions;
    }
}
?>