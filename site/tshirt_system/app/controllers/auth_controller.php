<?php
/**
 * Authentication Controller
 * 
 * Handles user authentication, login, logout, and password reset
 */

require_once APP_PATH . '/models/user.php';
require_once APP_PATH . '/helpers/session.php';
require_once APP_PATH . '/helpers/security.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        SessionHelper::start();
        $this->userModel = new UserModel();
    }
    
    /**
     * Show login form
     */
    public function showLoginForm() {
        // If already logged in, redirect to dashboard
        if (SessionHelper::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        
        // Get flash message if any
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/auth/login.php';
    }
    
    /**
     * Process login form
     */
    public function login() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Validate input
        $email = SecurityHelper::sanitizeInput($_POST['email']);
        $password = $_POST['password']; // Don't sanitize password
        
        if (empty($email) || empty($password)) {
            SessionHelper::setFlash('danger', 'Email and password are required.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Attempt to verify user
        $user = $this->userModel->verify($email, $password);
        
        if ($user) {
            // Set user session data
            SessionHelper::setUserSession($user);
            
            // Log the login action
            SecurityHelper::logAction($user['id'], 'login', 'users', $user['id']);
            
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } else {
            SessionHelper::setFlash('danger', 'Invalid email or password.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
    
    /**
     * Log user out
     */
    public function logout() {
        // Log the logout action if user is logged in
        if (SessionHelper::isLoggedIn()) {
            SecurityHelper::logAction(SessionHelper::getUserId(), 'logout', 'users', SessionHelper::getUserId());
        }
        
        SessionHelper::destroy();
        
        SessionHelper::start(); // Start a new clean session
        SessionHelper::setFlash('success', 'You have been logged out successfully.');
        
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    
    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm() {
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/auth/forgot_password.php';
    }
    
    /**
     * Process forgot password form
     */
    public function processForgotPassword() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/forgot-password');
            exit;
        }
        
        $email = SecurityHelper::sanitizeInput($_POST['email']);
        
        if (empty($email) || !SecurityHelper::validateEmail($email)) {
            SessionHelper::setFlash('danger', 'Please enter a valid email address.');
            header('Location: ' . BASE_URL . '/forgot-password');
            exit;
        }
        
        // Get user by email
        $user = $this->userModel->getByEmail($email);
        
        if ($user) {
            // Generate reset token
            $token = SecurityHelper::generateRandomToken();
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
            
            // Store token in database
            $this->userModel->storeResetToken($user['id'], $token, $expiry);
            
            // Send email with reset link
            $resetLink = BASE_URL . '/reset-password/' . $token;
            
            // In a real application, send an actual email here
            // For now, just log it
            error_log("Password reset link for {$email}: {$resetLink}");
            
            SessionHelper::setFlash('success', 'If an account exists with that email, a password reset link has been sent.');
        } else {
            // Don't reveal that the email doesn't exist
            SessionHelper::setFlash('success', 'If an account exists with that email, a password reset link has been sent.');
        }
        
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    
    /**
     * Show reset password form
     */
    public function showResetPasswordForm($token) {
        // Verify token exists and is valid
        $user = $this->userModel->verifyResetToken($token);
        
        if (!$user) {
            SessionHelper::setFlash('danger', 'Invalid or expired password reset token.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/auth/reset_password.php';
    }
    
    /**
     * Process reset password form
     */
    public function processResetPassword() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $token = SecurityHelper::sanitizeInput($_POST['token']);
        $password = $_POST['password'];
        $passwordConfirm = $_POST['password_confirm'];
        
        // Verify token
        $user = $this->userModel->verifyResetToken($token);
        
        if (!$user) {
            SessionHelper::setFlash('danger', 'Invalid or expired password reset token.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Validate password
        if (empty($password) || strlen($password) < 8) {
            SessionHelper::setFlash('danger', 'Password must be at least 8 characters long.');
            header('Location: ' . BASE_URL . '/reset-password/' . $token);
            exit;
        }
        
        if ($password !== $passwordConfirm) {
            SessionHelper::setFlash('danger', 'Passwords do not match.');
            header('Location: ' . BASE_URL . '/reset-password/' . $token);
            exit;
        }
        
        // Reset the password
        $this->userModel->resetPassword($user['id'], $password);
        
        // Log the action
        SecurityHelper::logAction($user['id'], 'reset_password', 'users', $user['id']);
        
        SessionHelper::setFlash('success', 'Your password has been reset successfully. You can now log in with your new password.');
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
?>