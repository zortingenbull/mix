<?php
/**
 * Shipping Controller
 * 
 * Handles shipping operations
 */

require_once APP_PATH . '/models/job.php';
require_once APP_PATH . '/models/order.php';
require_once APP_PATH . '/controllers/shipstation_controller.php';
require_once APP_PATH . '/helpers/session.php';
require_once APP_PATH . '/helpers/security.php';

class ShippingController {
    private $jobModel;
    private $orderModel;
    private $shipStationController;
    
    public function __construct() {
        SessionHelper::start();
        
        // Check if user is logged in
        if (!SessionHelper::isLoggedIn()) {
            SessionHelper::setFlash('danger', 'Please log in to access this page.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Check if user has permission
        if (SessionHelper::getUserRole() != ROLE_SHIPPING && SessionHelper::getUserRole() > ROLE_MANAGER) {
            SessionHelper::setFlash('danger', 'You do not have permission to access this page.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        $this->jobModel = new JobModel();
        $this->orderModel = new OrderModel();
        $this->shipStationController = new ShipStationController();
    }
    
    /**
     * Show shipping console
     */
    public function index() {
        // Get jobs ready for shipping
        $jobs = $this->jobModel->getReadyForShipping();
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/shipping/index.php';
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
        
        if (empty($trackingNumber)) {
            SessionHelper::setFlash('danger', 'Tracking number is required.');
            header('Location: ' . BASE_URL . '/shipping');
            exit;
        }
        
        try {
            // Mark as shipped in ShipStation and update local database
            $result = $this->shipStationController->markOrderShipped($orderId, $trackingNumber, $carrierCode);
            
            if ($result) {
                // Update all jobs for this order to shipped status
                $jobs = $this->jobModel->getByOrderId($orderId);
                
                foreach ($jobs as $job) {
                    $this->jobModel->update([
                        'id' => $job['id'],
                        'status' => STATUS_SHIPPED,
                        'updated_by' => SessionHelper::getUserId()
                    ]);
                }
                
                // Log action
                SecurityHelper::logAction(
                    SessionHelper::getUserId(),
                    'mark_shipped',
                    'orders',
                    $orderId,
                    "Tracking: {$trackingNumber}, Carrier: {$carrierCode}"
                );
                
                SessionHelper::setFlash('success', 'Order marked as shipped successfully.');
            } else {
                SessionHelper::setFlash('danger', 'Failed to mark order as shipped.');
            }
        } catch (Exception $e) {
            SessionHelper::setFlash('danger', $e->getMessage());
        }
        
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
        
        header('Location: ' . BASE_URL . '/shipping');
        exit;
    }
    
    /**
     * Manual shipping entry
     */
    public function manualShipping() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/shipping');
            exit;
        }
        
        $orderId = (int) $_POST['order_id'];
        $trackingNumber = SecurityHelper::sanitizeInput($_POST['tracking_number']);
        $carrierCode = SecurityHelper::sanitizeInput($_POST['carrier_code']);
        
        if (empty($trackingNumber)) {
            SessionHelper::setFlash('danger', 'Tracking number is required.');
            header('Location: ' . BASE_URL . '/shipping');
            exit;
        }
        
        // Get order
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            SessionHelper::setFlash('danger', 'Order not found.');
            header('Location: ' . BASE_URL . '/shipping');
            exit;
        }
        
        // Update order status and tracking info
        $result = $this->orderModel->update([
            'id' => $orderId,
            'status' => STATUS_SHIPPED,
            'tracking_number' => $trackingNumber,
            'shipped_date' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // Update all jobs for this order to shipped status
            $jobs = $this->jobModel->getByOrderId($orderId);
            
            foreach ($jobs as $job) {
                $this->jobModel->update([
                    'id' => $job['id'],
                    'status' => STATUS_SHIPPED,
                    'updated_by' => SessionHelper::getUserId()
                ]);
            }
            
            // Log action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'manual_shipping',
                'orders',
                $orderId,
                "Tracking: {$trackingNumber}, Carrier: {$carrierCode}"
            );
            
            SessionHelper::setFlash('success', 'Order marked as shipped successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to mark order as shipped.');
        }
        
        header('Location: ' . BASE_URL . '/shipping');
        exit;
    }
}
?>