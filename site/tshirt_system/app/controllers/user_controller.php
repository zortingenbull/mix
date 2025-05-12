<?php
/**
 * User Controller
 * 
 * Handles user management operations (CRUD)
 */

require_once APP_PATH . '/models/user.php';
require_once APP_PATH . '/helpers/session.php';
require_once APP_PATH . '/helpers/security.php';

class UserController {
    private $userModel;
    
    public function __construct() {
        SessionHelper::start();
        
        // Check if user is logged in
        if (!SessionHelper::isLoggedIn()) {
            SessionHelper::setFlash('danger', 'Please log in to access this page.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Check if user has admin role
        if (SessionHelper::getUserRole() != ROLE_ADMIN) {
            SessionHelper::setFlash('danger', 'You do not have permission to access this page.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        $this->userModel = new UserModel();
    }
    
    /**
     * List all users
     */
    public function index() {
        $users = $this->userModel->getAll();
        
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/users/index.php';
    }
    
    /**
     * Show create user form
     */
    public function create() {
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/users/create.php';
    }
    
    /**
     * Store new user
     */
    public function store() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/users/create');
            exit;
        }
        
        // Validate input
        $name = SecurityHelper::sanitizeInput($_POST['name']);
        $email = SecurityHelper::sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $role = (int) $_POST['role'];
        
        if (empty($name) || empty($email) || empty($password)) {
            SessionHelper::setFlash('danger', 'All fields are required.');
            header('Location: ' . BASE_URL . '/users/create');
            exit;
        }
        
        if (!SecurityHelper::validateEmail($email)) {
            SessionHelper::setFlash('danger', 'Please enter a valid email address.');
            header('Location: ' . BASE_URL . '/users/create');
            exit;
        }
        
        if (strlen($password) < 8) {
            SessionHelper::setFlash('danger', 'Password must be at least 8 characters long.');
            header('Location: ' . BASE_URL . '/users/create');
            exit;
        }
        
        // Check if email already exists
        $existingUser = $this->userModel->getByEmail($email);
        
        if ($existingUser) {
            SessionHelper::setFlash('danger', 'Email address is already in use.');
            header('Location: ' . BASE_URL . '/users/create');
            exit;
        }
        
        // Create new user
        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role
        ]);
        
        if ($userId) {
            // Log action
            SecurityHelper::logAction(SessionHelper::getUserId(), 'create_user', 'users', $userId);
            
            SessionHelper::setFlash('success', 'User created successfully.');
            header('Location: ' . BASE_URL . '/users');
            exit;
        } else {
            SessionHelper::setFlash('danger', 'Failed to create user.');
            header('Location: ' . BASE_URL . '/users/create');
            exit;
        }
    }
    
    /**
     * Show edit user form
     */
    public function edit($id) {
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            SessionHelper::setFlash('danger', 'User not found.');
            header('Location: ' . BASE_URL . '/users');
            exit;
        }
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/users/edit.php';
    }
    
    /**
     * Update user
     */
    public function update() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/users');
            exit;
        }
        
        $id = (int) $_POST['id'];
        $name = SecurityHelper::sanitizeInput($_POST['name']);
        $email = SecurityHelper::sanitizeInput($_POST['email']);
        $role = (int) $_POST['role'];
        
        // Get current user data
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            SessionHelper::setFlash('danger', 'User not found.');
            header('Location: ' . BASE_URL . '/users');
            exit;
        }
        
        // Check if email is already in use by another user
        if ($email != $user['email']) {
            $existingUser = $this->userModel->getByEmail($email);
            
            if ($existingUser && $existingUser['id'] != $id) {
                SessionHelper::setFlash('danger', 'Email address is already in use by another user.');
                header('Location: ' . BASE_URL . '/users/edit/' . $id);
                exit;
            }
        }
        
        // Prepare data for update
        $data = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => $role
        ];
        
        // Add password to data if provided
        if (!empty($_POST['password'])) {
            $password = $_POST['password'];
            
            if (strlen($password) < 8) {
                SessionHelper::setFlash('danger', 'Password must be at least 8 characters long.');
                header('Location: ' . BASE_URL . '/users/edit/' . $id);
                exit;
            }
            
            $data['password'] = $password;
        }
        
        // Update user
        $result = $this->userModel->update($data);
        
        if ($result) {
            // Log action
            SecurityHelper::logAction(SessionHelper::getUserId(), 'update_user', 'users', $id);
            
            SessionHelper::setFlash('success', 'User updated successfully.');
            header('Location: ' . BASE_URL . '/users');
            exit;
        } else {
            SessionHelper::setFlash('danger', 'Failed to update user.');
            header('Location: ' . BASE_URL . '/users/edit/' . $id);
            exit;
        }
    }
    
    /**
     * Delete user
     */
    public function delete() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/users');
            exit;
        }
        
        $id = (int) $_POST['id'];
        
        // Don't allow deleting the logged in user
        if ($id == SessionHelper::getUserId()) {
            SessionHelper::setFlash('danger', 'You cannot delete your own account.');
            header('Location: ' . BASE_URL . '/users');
            exit;
        }
        
        // Delete user
        $result = $this->userModel->delete($id);
        
        if ($result) {
            // Log action
            SecurityHelper::logAction(SessionHelper::getUserId(), 'delete_user', 'users', $id);
            
            SessionHelper::setFlash('success', 'User deleted successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to delete user.');
        }
        
        header('Location: ' . BASE_URL . '/users');
        exit;
    }
    
    /**
     * Show user profile
     */
    public function profile() {
        $id = SessionHelper::getUserId();
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            SessionHelper::setFlash('danger', 'User not found.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/users/profile.php';
    }
    
    /**
     * Update user profile
     */
    public function updateProfile() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/profile');
            exit;
        }
        
        $id = SessionHelper::getUserId();
        $name = SecurityHelper::sanitizeInput($_POST['name']);
        $email = SecurityHelper::sanitizeInput($_POST['email']);
        
        // Get current user data
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            SessionHelper::setFlash('danger', 'User not found.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        // Check if email is already in use by another user
        if ($email != $user['email']) {
            $existingUser = $this->userModel->getByEmail($email);
            
            if ($existingUser && $existingUser['id'] != $id) {
                SessionHelper::setFlash('danger', 'Email address is already in use by another user.');
                header('Location: ' . BASE_URL . '/profile');
                exit;
            }
        }
        
        // Prepare data for update
        $data = [
            'id' => $id,
            'name' => $name,
            'email' => $email
        ];
        
        // Add password to data if provided
        if (!empty($_POST['password']) && !empty($_POST['current_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['password'];
            
            // Verify current password
            if (!SecurityHelper::verifyPassword($currentPassword, $user['password'])) {
                SessionHelper::setFlash('danger', 'Current password is incorrect.');
                header('Location: ' . BASE_URL . '/profile');
                exit;
            }
            
            if (strlen($newPassword) < 8) {
                SessionHelper::setFlash('danger', 'New password must be at least 8 characters long.');
                header('Location: ' . BASE_URL . '/profile');
                exit;
            }
            
            $data['password'] = $newPassword;
        }
        
        // Update user
        $result = $this->userModel->update($data);
        
        if ($result) {
            // Update session data
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            // Log action
            SecurityHelper::logAction($id, 'update_profile', 'users', $id);
            
            SessionHelper::setFlash('success', 'Profile updated successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to update profile.');
        }
        
        header('Location: ' . BASE_URL . '/profile');
        exit;
    }
}
?>