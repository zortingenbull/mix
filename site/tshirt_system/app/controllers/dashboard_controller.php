<?php
/**
 * Dashboard Controller
 * 
 * Handles dashboard display and statistics
 */

require_once APP_PATH . '/models/order.php';
require_once APP_PATH . '/models/job.php';
require_once APP_PATH . '/models/file.php';
require_once APP_PATH . '/models/user.php';
require_once APP_PATH . '/helpers/session.php';

class DashboardController {
    private $orderModel;
    private $jobModel;
    private $fileModel;
    private $userModel;
    
    public function __construct() {
        SessionHelper::start();
        
        // Check if user is logged in
        if (!SessionHelper::isLoggedIn()) {
            SessionHelper::setFlash('danger', 'Please log in to access this page.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $this->orderModel = new OrderModel();
        $this->jobModel = new JobModel();
        $this->fileModel = new FileModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * Show dashboard
     */
    public function index() {
        // Get order statistics
        $orderStats = $this->orderModel->countByStatus();
        
        // Get recent orders
        $recentOrders = $this->orderModel->getAll(10, 0);
        
        // For production users, get their assigned jobs
        if (SessionHelper::getUserRole() == ROLE_PRODUCTION) {
            $assignedJobs = $this->jobModel->getByAssignedUser(SessionHelper::getUserId(), 10);
        }
        
        // For shipping users, get jobs ready for shipping
        if (SessionHelper::getUserRole() == ROLE_SHIPPING || SessionHelper::getUserRole() <= ROLE_MANAGER) {
            $readyToShip = $this->jobModel->getReadyForShipping(10);
        }
        
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/dashboard/index.php';
    }
    
    /**
     * Get order statistics
     */
    public function getOrderStats() {
        // Check if user is logged in
        if (!SessionHelper::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $stats = $this->orderModel->countByStatus();
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        exit;
    }
}
?>