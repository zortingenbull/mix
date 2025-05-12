<?php
/**
 * File Controller
 * 
 * Handles file uploads and management
 */

require_once APP_PATH . '/models/file.php';
require_once APP_PATH . '/models/order.php';
require_once APP_PATH . '/helpers/session.php';
require_once APP_PATH . '/helpers/security.php';

class FileController {
    private $fileModel;
    private $orderModel;
    
    public function __construct() {
        SessionHelper::start();
        
        // Check if user is logged in
        if (!SessionHelper::isLoggedIn()) {
            SessionHelper::setFlash('danger', 'Please log in to access this page.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $this->fileModel = new FileModel();
        $this->orderModel = new OrderModel();
    }
    
    /**
     * List all files
     */
    public function index() {
        // Check if user has access
        if (SessionHelper::getUserRole() > ROLE_MANAGER) {
            SessionHelper::setFlash('danger', 'You do not have permission to access this page.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        $files = $this->fileModel->getAll();
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/files/index.php';
    }
    
    /**
     * Show file upload form
     */
    public function create($orderId = null) {
        // Check if user has access
        if (SessionHelper::getUserRole() > ROLE_MANAGER && SessionHelper::getUserRole() != ROLE_PRODUCTION) {
            SessionHelper::setFlash('danger', 'You do not have permission to access this page.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        if ($orderId) {
            $order = $this->orderModel->getById($orderId);
            
            if (!$order) {
                SessionHelper::setFlash('danger', 'Order not found.');
                header('Location: ' . BASE_URL . '/orders');
                exit;
            }
        } else {
            // Get orders for dropdown
            $orders = $this->orderModel->getAll(100, 0);
        }
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        $flash = SessionHelper::getFlash();
        
        include APP_PATH . '/views/files/create.php';
    }
    
    /**
     * Handle file upload
     */
    public function upload() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
        
        // Check if user has access
        if (SessionHelper::getUserRole() > ROLE_MANAGER && SessionHelper::getUserRole() != ROLE_PRODUCTION) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to upload files']);
            exit;
        }
        
        $orderId = (int) $_POST['order_id'];
        $fileType = isset($_POST['file_type']) ? (int) $_POST['file_type'] : FILE_TYPE_ARTWORK;
        
        // Validate order
        $order = $this->orderModel->getById($orderId);
        
        if (!$order) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid order ID']);
            exit;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK) {
            http_response_code(400);
            $error = isset($_FILES['file']) ? $this->getFileUploadError($_FILES['file']['error']) : 'No file uploaded';
            echo json_encode(['error' => $error]);
            exit;
        }
        
        $file = $_FILES['file'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed.']);
            exit;
        }
        
        // Validate file size (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['error' => 'File size exceeds the limit of 10MB.']);
            exit;
        }
        
        // Generate unique filename
        $originalFilename = $file['name'];
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $newFilename = uniqid('file_') . '.' . $extension;
        
        // Ensure upload directory exists
        $uploadDir = UPLOAD_PATH . '/' . $orderId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        $destination = $uploadDir . '/' . $newFilename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save file. Please try again.']);
            exit;
        }
        
        // Create file record in database
        $fileId = $this->fileModel->create([
            'order_id' => $orderId,
            'filename' => $orderId . '/' . $newFilename,
            'original_filename' => $originalFilename,
            'file_type' => $fileType,
            'file_size' => $file['size'],
            'uploaded_by' => SessionHelper::getUserId()
        ]);
        
        if (!$fileId) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save file information. Please try again.']);
            exit;
        }
        
        // Log action
        SecurityHelper::logAction(
            SessionHelper::getUserId(),
            'upload_file',
            'files',
            $fileId,
            "Uploaded file for order #" . $order['order_number']
        );
        
        // Return success response
        echo json_encode([
            'success' => true,
            'file_id' => $fileId,
            'message' => 'File uploaded successfully'
        ]);
        exit;
    }
    
    /**
     * List files for an order
     */
    public function listFiles($orderId) {
        $orderId = (int) $orderId;
        
        // Get files for order
        $files = $this->fileModel->getByOrderId($orderId);
        
        // Group files by type
        $filesByType = [
            FILE_TYPE_ARTWORK => [],
            FILE_TYPE_MOCKUP => [],
            FILE_TYPE_FINAL => []
        ];
        
        foreach ($files as $file) {
            $filesByType[$file['file_type']][] = $file;
        }
        
        $csrf_token = SecurityHelper::generateCsrfToken();
        
        include APP_PATH . '/views/files/list.php';
    }
    
    /**
     * Download file
     */
    public function download($id) {
        $file = $this->fileModel->getById($id);
        
        if (!$file) {
            SessionHelper::setFlash('danger', 'File not found.');
            header('Location: ' . BASE_URL . '/files');
            exit;
        }
        
        $filePath = UPLOAD_PATH . '/' . $file['filename'];
        
        if (!file_exists($filePath)) {
            SessionHelper::setFlash('danger', 'File not found on server.');
            header('Location: ' . BASE_URL . '/files');
            exit;
        }
        
        // Log download
        SecurityHelper::logAction(
            SessionHelper::getUserId(),
            'download_file',
            'files',
            $id,
            "Downloaded file: " . $file['original_filename']
        );
        
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        // Output file
        readfile($filePath);
        exit;
    }
    
    /**
     * Delete file
     */
    public function delete() {
        // Verify CSRF token
        if (!SecurityHelper::verifyCsrfToken($_POST['csrf_token'])) {
            SessionHelper::setFlash('danger', 'Invalid request, please try again.');
            header('Location: ' . BASE_URL . '/files');
            exit;
        }
        
        // Check if user has permission
        if (SessionHelper::getUserRole() > ROLE_MANAGER) {
            SessionHelper::setFlash('danger', 'You do not have permission to delete files.');
            header('Location: ' . BASE_URL . '/files');
            exit;
        }
        
        $id = (int) $_POST['file_id'];
        $file = $this->fileModel->getById($id);
        
        if (!$file) {
            SessionHelper::setFlash('danger', 'File not found.');
            header('Location: ' . BASE_URL . '/files');
            exit;
        }
        
        // Delete file
        $result = $this->fileModel->delete($id);
        
        if ($result) {
            // Log action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'delete_file',
                'files',
                $id,
                "Deleted file: " . $file['original_filename']
            );
            
            SessionHelper::setFlash('success', 'File deleted successfully.');
        } else {
            SessionHelper::setFlash('danger', 'Failed to delete file.');
        }
        
        // Redirect back
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '/files';
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
    
    /**
     * Get file upload error message
     */
    private function getFileUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
}
?>