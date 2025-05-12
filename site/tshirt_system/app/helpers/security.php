<?php
/**
 * Security Helper
 * 
 * Contains functions for handling CSRF protection, input validation, and other security features
 */

class SecurityHelper {
    /**
     * Generate a CSRF token and store it in the session
     */
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        // Regenerate if token is older than 30 minutes
        if ($_SESSION['csrf_token_time'] < time() - 1800) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize input to prevent XSS attacks
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
        } else {
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Hash password using bcrypt
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    }
    
    /**
     * Verify password against stored hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate a random token for password reset
     */
    public static function generateRandomToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Log user action
     */
    public static function logAction($userId, $action, $targetType, $targetId) {
        require_once APP_PATH . '/models/log.php';
        
        $log = new LogModel();
        $log->create([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    }
}
?>