<?php
/**
 * Order Controller
 * 
 * Handles order management and operations
 */

require_once APP_PATH . '/models/order.php';
require_once APP_PATH . '/models/user.php';
require_once APP_PATH . '/models/job.php';
require_once APP_PATH . '/controllers/shipstation_controller.php';
require_once APP_PATH . '/helpers/session.php';
require_once APP_PATH . '/helpers/security.php';

class OrderController {
    private $orderModel;
    private $userModel;
    private $jobModel;
    private $shipStationController;
    
    public function __construct() {
        SessionHelper::start();
        
        // Check if user is logged in
        if (!SessionHelper::isLoggedIn()) {
            SessionHelper::setFlash('danger', 'Please log in to access this page.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
        $this->jobModel = new JobModel();
        $this->shipStationController = new ShipStationController();
    }
    
    /**
     * List all orders
     */
    public function index() {
        // Process filters
        $filters = [];
        
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = (int) $_GET['status'];
        }
        
        if (isset($_GET['assigned_to']) && $_GET['assigned_to'] !== '') {
            $filters['assigned_to'] = (int) $_GET['assigned_to'];
        }
        
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $filters['search'] = SecurityHelper::sanitizeInput($_GET['search']);
        }
        
        // Get orders with filters
        $orders = $this->orderModel->getAll(100, 0, $filters);
        
        // Get all users for assignment dropdown
        $users = $this->userModel->getAll();
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/orders/index.php';
    }
    
    /**
     * Show order details
     */
    public function show($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            SessionHelper::setFlash('danger', 'Order not found.');
            header('Location: ' . BASE_URL . '/orders');
            exit;
        }
        
        // Get jobs associated with this order
        $jobs = $this->jobModel->getByOrderId($id);
        
        // Get users for assignment
        $users = $this->userModel->getAll();
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/orders/show.php';
    }
    
    /**
     * Assign order to user
     */
    public function assign() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/orders');
            exit;
        }
        
        $orderId = (int) $_POST['order_id'];
        $userId = !empty($_POST['user_id']) ? (int) $_POST['user_id'] : null;
        
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            SessionHelper::setFlash('danger', 'Order not found.');
            header('Location: ' . BASE_URL . '/orders');
            exit;
        }
        
        // Update order assignment
        $result = $this->orderModel->update([
            'id' => $orderId,
            'assigned_to' => $userId
        ]);
        
        if ($result) {
            // Log action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'assign_order',
                'orders',
                $orderId,
                "Assigned to user ID: " . ($userId ?? 'None')
            );
            
            // If assigning to production user, create job if it doesn't exist
            if ($userId && SessionHelper::getUserRole() <= ROLE_MANAGER) {
                $user = $this->userModel->getById($userId);
                
                if ($user && $user['role'] == ROLE_PRODUCTION) {
                    $existingJob = $this->jobModel->getByOrderId($orderId);
                    
                    if (!$existingJob) {
                        $this->jobModel->create([
                            'order_id' => $orderId,
                            'status' => STATUS_PENDING,
                            'notes' => 'Automatically created when assigned to production user',
                            'updated_by' => SessionHelper::getUserId()
                        ]);
                    }
                }
            }
            
            SessionHelper::setFlash('success', 'Order assigned successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to assign order.');
        }
        
        // Redirect back to the page they came from
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '/orders';
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
    
    /**
     * Update order status
     */
    public function updateStatus() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/orders');
            exit;
        }
        
        $orderId = (int) $_POST['order_id'];
        $status = (int) $_POST['status'];
        
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            SessionHelper::setFlash('danger', 'Order not found.');
            header('Location: ' . BASE_URL . '/orders');
            exit;
        }
        
        // Update order status
        $result = $this->orderModel->update([
            'id' => $orderId,
            'status' => $status
        ]);
        
        if ($result) {
            // Get status name for log
            $statusNames = array(
                STATUS_PENDING     => 'Pending',
                STATUS_QUEUED      => 'Queued',
                STATUS_IN_PROGRESS => 'In Progress',
                STATUS_PRINTED     => 'Printed',
                STATUS_SHIPPED     => 'Shipped'
            );
            
            // Log action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'update_order_status',
                'orders',
                $orderId,
                "Status updated to: " . (isset($statusNames[$status]) ? $statusNames[$status] : 'Unknown')
            );
            
            // If status is set to Shipped, update job status too
            if ($status == STATUS_SHIPPED) {
                $jobs = $this->jobModel->getByOrderId($orderId);
                
                foreach ($jobs as $job) {
                    $this->jobModel->update([
                        'id' => $job['id'],
                        'status' => STATUS_SHIPPED,
                        'updated_by' => SessionHelper::getUserId()
                    ]);
                }
            }
            
            SessionHelper::setFlash('success', 'Order status updated successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to update order status.');
        }
        
        // Redirect back to the page they came from
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '/orders';
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
    
    /**
     * Add notes to order
     */
    public function addNotes() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/orders');
            exit;
        }
        
        $orderId = (int) $_POST['order_id'];
        $notes = SecurityHelper::sanitizeInput($_POST['notes']);
        
        // Get current order
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            SessionHelper::setFlash('danger', 'Order not found.');
            header('Location: ' . BASE_URL . '/orders');
            exit;
        }
        
        // Create job with notes if it doesn't exist
        $job = $this->jobModel->getByOrderId($orderId);
        
        if ($job) {
            // Update existing job
            $this->jobModel->update([
                'id' => $job['id'],
                'notes' => $notes,
                'updated_by' => SessionHelper::getUserId()
            ]);
        } else {
            // Create new job
            $this->jobModel->create([
                'order_id' => $orderId,
                'status' => STATUS_PENDING,
                'notes' => $notes,
                'updated_by' => SessionHelper::getUserId()
            ]);
        }
        
        // Log action
        SecurityHelper::logAction(
            SessionHelper::getUserId(),
            'add_order_notes',
            'orders',
            $orderId,
            "Notes added/updated"
        );
        
        SessionHelper::setFlash('success', 'Notes added successfully.');
        
        // Redirect back to order details
        header('Location: ' . BASE_URL . '/orders/' . $orderId);
        exit;
    }
    
    /**
     * Mark order as shipped
     */
    public function markShipped() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/shipping');
            exit;
        }
        
        $orderId = (int) $_POST['order_id'];
        $trackingNumber = SecurityHelper::sanitizeInput($_POST['tracking_number']);
        $carrierCode = SecurityHelper::sanitizeInput($_POST['carrier_code']);
        
        try {
            // Mark as shipped in ShipStation and update local DB
            $result = $this->shipStationController->markOrderShipped($orderId, $trackingNumber, $carrierCode);
            
            if ($result) {
                SessionHelper::setFlash('success', 'Order marked as shipped successfully.');
            } else {
                SessionHelper::setFlash('danger', 'Failed to mark order as shipped.');
            }
        } catch (Exception $e) {
            SessionHelper::setFlash('danger', $e->getMessage());
        }
        
        // Redirect back to shipping page
        header('Location: ' . BASE_URL . '/shipping');
        exit;
    }
    
    /**
     * Generate shipping label
     */
    public function generateLabel() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/shipping');
            exit;
        }
        
        $orderId = (int) $_POST['order_id'];
        
        try {
            // Generate label via ShipStation
            $labelUrl = $this->shipStationController->createShippingLabel($orderId);
            
            if ($labelUrl) {
                // Log action
                SecurityHelper::logAction(
                    SessionHelper::getUserId(),
                    'generate_label',
                    'orders',
                    $orderId
                );
                
                // Redirect to label URL
                header('Location: ' . $labelUrl);
                exit;
            } else {
                SessionHelper::setFlash('danger', 'Failed to generate shipping label.');
            }
        } catch (Exception $e) {
            SessionHelper::setFlash('danger', $e->getMessage());
        }
        
        // Redirect back to shipping page
        header('Location: ' . BASE_URL . '/shipping');
        exit;
    }
}
?>