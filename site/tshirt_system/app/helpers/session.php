<?php
/**
 * Session Helper
 * 
 * Manages user sessions and provides session-related functionality
 */

class SessionHelper {
    /**
     * Start session with secure settings
     */
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            // Set secure session settings
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);
            
            $cookieParams = session_get_cookie_params();
            session_set_cookie_params(
                SESSION_LIFETIME,
                $cookieParams["path"],
                $cookieParams["domain"],
                isset($_SERVER['HTTPS']), // Secure if HTTPS
                true // HttpOnly flag
            );
            
            session_name('tshirt_system_session');
            session_start();
            
            // Regenerate session ID periodically to prevent session fixation
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 3600) { // Regenerate after 1 hour
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Set user session data after successful login
     */
    public static function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Get current user role
     */
    public static function getUserRole() {
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    /**
     * Check if session is expired
     */
    public static function isSessionExpired() {
        $max_lifetime = SESSION_LIFETIME;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $max_lifetime)) {
            return true;
        }
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    /**
     * Destroy user session (logout)
     */
    public static function destroy() {
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Set flash message to be displayed once
     */
    public static function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Get flash message and clear it
     */
    public static function getFlash() {
        $flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
?>