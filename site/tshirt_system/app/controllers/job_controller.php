<?php
/**
 * Job Controller
 * 
 * Handles print job operations and queue
 */

require_once APP_PATH . '/models/job.php';
require_once APP_PATH . '/models/order.php';
require_once APP_PATH . '/models/user.php';
require_once APP_PATH . '/helpers/session.php';
require_once APP_PATH . '/helpers/security.php';

class JobController {
    private $jobModel;
    private $orderModel;
    private $userModel;
    
    public function __construct() {
        SessionHelper::start();
        
        // Check if user is logged in
        if (!SessionHelper::isLoggedIn()) {
            SessionHelper::setFlash('danger', 'Please log in to access this page.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $this->jobModel = new JobModel();
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * List all jobs
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
        
        // For production users, only show their assigned jobs
        if (SessionHelper::getUserRole() == ROLE_PRODUCTION) {
            $jobs = $this->jobModel->getByAssignedUser(SessionHelper::getUserId());
        } else {
            // Get jobs with filters
            $jobs = $this->jobModel->getAll(100, 0, $filters);
        }
        
        // Get all users for assignment dropdown
        $users = $this->userModel->getAll();
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/jobs/index.php';
    }
    
    /**
     * Show job details
     */
    public function show($id) {
        $job = $this->jobModel->getById($id);
        
        if (!$job) {
            SessionHelper::setFlash('danger', 'Job not found.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        // Check if user has access to this job
        if (SessionHelper::getUserRole() == ROLE_PRODUCTION && $job['assigned_to'] != SessionHelper::getUserId()) {
            SessionHelper::setFlash('danger', 'You do not have permission to view this job.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/jobs/show.php';
    }
    
    /**
     * Show create job form
     */
    public function create($orderId = null) {
        // Check if user has permission
        if (SessionHelper::getUserRole() > ROLE_MANAGER) {
            SessionHelper::setFlash('danger', 'You do not have permission to create jobs.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        if ($orderId) {
            $order = $this->orderModel->getById($orderId);
            
            if (!$order) {
                SessionHelper::setFlash('danger', 'Order not found.');
                header('Location: ' . BASE_URL . '/orders');
                exit;
            }
            
            // Check if job already exists
            $existingJob = $this->jobModel->getByOrderId($orderId);
            
            if (!empty($existingJob)) {
                SessionHelper::setFlash('warning', 'A job already exists for this order.');
                header('Location: ' . BASE_URL . '/orders/' . $orderId);
                exit;
            }
        }
        
        // Get orders for dropdown
        $orders = $this->orderModel->getAll(100, 0, ['status' => STATUS_PENDING]);
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/jobs/create.php';
    }
    
    /**
     * Store new job
     */
    public function store() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/jobs/create');
            exit;
        }
        
        // Check if user has permission
        if (SessionHelper::getUserRole() > ROLE_MANAGER) {
            SessionHelper::setFlash('danger', 'You do not have permission to create jobs.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        $orderId = (int) $_POST['order_id'];
        $status = (int) $_POST['status'];
        $notes = SecurityHelper::sanitizeInput($_POST['notes']);
        
        // Validate input
        if (empty($orderId)) {
            SessionHelper::setFlash('danger', 'Order ID is required.');
            header('Location: ' . BASE_URL . '/jobs/create');
            exit;
        }
        
        // Get order
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            SessionHelper::setFlash('danger', 'Order not found.');
            header('Location: ' . BASE_URL . '/jobs/create');
            exit;
        }
        
        // Check if job already exists
        $existingJob = $this->jobModel->getByOrderId($orderId);
        
        if (!empty($existingJob)) {
            SessionHelper::setFlash('warning', 'A job already exists for this order.');
            header('Location: ' . BASE_URL . '/orders/' . $orderId);
            exit;
        }
        
        // Create new job
        $jobId = $this->jobModel->create([
            'order_id' => $orderId,
            'status' => $status,
            'notes' => $notes,
            'updated_by' => SessionHelper::getUserId()
        ]);
        
        if ($jobId) {
            // Update order status if needed
            if ($order['status'] == STATUS_PENDING && $status > STATUS_PENDING) {
                $this->orderModel->update([
                    'id' => $orderId,
                    'status' => $status
                ]);
            }
            
            // Log action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'create_job',
                'jobs',
                $jobId,
                "Created for order #" . $order['order_number']
            );
            
            SessionHelper::setFlash('success', 'Job created successfully.');
            header('Location: ' . BASE_URL . '/jobs/' . $jobId);
            exit;
        } else {
            SessionHelper::setFlash('danger', 'Failed to create job.');
            header('Location: ' . BASE_URL . '/jobs/create');
            exit;
        }
    }
    
    /**
     * Update job status
     */
    public function updateStatus() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        $jobId = (int) $_POST['job_id'];
        $status = (int) $_POST['status'];
        
        // Get job
        $job = $this->jobModel->getById($jobId);
        
        if (!$job) {
            SessionHelper::setFlash('danger', 'Job not found.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        // Check if user has access to this job
        if (SessionHelper::getUserRole() == ROLE_PRODUCTION && $job['assigned_to'] != SessionHelper::getUserId()) {
            SessionHelper::setFlash('danger', 'You do not have permission to update this job.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        // Update job status
        $result = $this->jobModel->update([
            'id' => $jobId,
            'status' => $status,
            'updated_by' => SessionHelper::getUserId()
        ]);
        
        if ($result) {
            // Get status name for log
            $statusNames = [
                STATUS_PENDING => 'Pending',
                STATUS_QUEUED => 'Queued',
                STATUS_IN_PROGRESS => 'In Progress',
                STATUS_PRINTED => 'Printed',
                STATUS_SHIPPED => 'Shipped'
            ];
            
            // Log action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'update_job_status',
                'jobs',
                $jobId,
                "Status updated to: " . ($statusNames[$status] ?? 'Unknown')
            );
            
            // Update order status if needed
            if ($status > $job['status']) {
                $order = $this->orderModel->getById($job['order_id']);
                
                if ($order && $order['status'] < $status) {
                    $this->orderModel->update([
                        'id' => $job['order_id'],
                        'status' => $status
                    ]);
                }
            }
            
            SessionHelper::setFlash('success', 'Job status updated successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to update job status.');
        }
        
        // Redirect back to the page they came from
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '/jobs/' . $jobId;
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
    
    /**
     * Update job notes
     */
    public function updateNotes() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        $jobId = (int) $_POST['job_id'];
        $notes = SecurityHelper::sanitizeInput($_POST['notes']);
        
        // Get job
        $job = $this->jobModel->getById($jobId);
        
        if (!$job) {
            SessionHelper::setFlash('danger', 'Job not found.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        // Check if user has access to this job
        if (SessionHelper::getUserRole() == ROLE_PRODUCTION && $job['assigned_to'] != SessionHelper::getUserId()) {
            SessionHelper::setFlash('danger', 'You do not have permission to update this job.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        // Update job notes
        $result = $this->jobModel->update([
            'id' => $jobId,
            'notes' => $notes,
            'updated_by' => SessionHelper::getUserId()
        ]);
        
        if ($result) {
            // Log action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'update_job_notes',
                'jobs',
                $jobId
            );
            
            SessionHelper::setFlash('success', 'Job notes updated successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to update job notes.');
        }
        
        // Redirect back to job details
        header('Location: ' . BASE_URL . '/jobs/' . $jobId);
        exit;
    }
    
    /**
     * Start job
     */
    public function startJob() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        $jobId = (int) $_POST['job_id'];
        
        // Get job
        $job = $this->jobModel->getById($jobId);
        
        if (!$job) {
            SessionHelper::setFlash('danger', 'Job not found.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        // Check if user has access to this job
        if (SessionHelper::getUserRole() == ROLE_PRODUCTION && $job['assigned_to'] != SessionHelper::getUserId()) {
            SessionHelper::setFlash('danger', 'You do not have permission to update this job.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        // Update job status to In Progress
        $result = $this->jobModel->update([
            'id' => $jobId,
            'status' => STATUS_IN_PROGRESS,
            'updated_by' => SessionHelper::getUserId()
        ]);
        
        if ($result) {
            // Log action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'start_job',
                'jobs',
                $jobId
            );
            
            // Update order status
            $this->orderModel->update([
                'id' => $job['order_id'],
                'status' => STATUS_IN_PROGRESS
            ]);
            
            SessionHelper::setFlash('success', 'Job started successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to start job.');
        }
        
        // Redirect back to job details
        header('Location: ' . BASE_URL . '/jobs/' . $jobId);
        exit;
    }
    
    /**
     * Mark job as printed
     */
    public function markPrinted() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        $jobId = (int) $_POST['job_id'];
        
        // Get job
        $job = $this->jobModel->getById($jobId);
        
        if (!$job) {
            SessionHelper::setFlash('danger', 'Job not found.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        // Check if user has access to this job
        if (SessionHelper::getUserRole() == ROLE_PRODUCTION && $job['assigned_to'] != SessionHelper::getUserId()) {
            SessionHelper::setFlash('danger', 'You do not have permission to update this job.');
            header('Location: ' . BASE_URL . '/jobs');
            exit;
        }
        
        // Update job status to Printed
        $result = $this->jobModel->update([
            'id' => $jobId,
            'status' => STATUS_PRINTED,
            'updated_by' => SessionHelper::getUserId()
        ]);
        
        if ($result) {
            // Log action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'mark_printed',
                'jobs',
                $jobId
            );
            
            // Update order status
            $this->orderModel->update([
                'id' => $job['order_id'],
                'status' => STATUS_PRINTED
            ]);
            
            SessionHelper::setFlash('success', 'Job marked as printed successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to mark job as printed.');
        }
        
        // Redirect back to job details
        header('Location: ' . BASE_URL . '/jobs/' . $jobId);
        exit;
    }
}
?>