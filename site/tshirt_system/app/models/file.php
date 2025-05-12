<?php
/**
 * File Model
 * 
 * Handles database operations for files
 */

require_once ROOT_PATH . '/config/database.php';

class FileModel {
    private $conn;
    private $table = 'files';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all files
     */
    public function getAll($limit = 100, $offset = 0) {
        $query = "SELECT f.*, o.order_number, u.name as uploaded_by_name 
                 FROM " . $this->table . " f 
                 JOIN orders o ON f.order_id = o.id
                 LEFT JOIN users u ON f.uploaded_by = u.id
                 ORDER BY f.uploaded_at DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit, $offset]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get file by ID
     */
    public function getById($id) {
        $query = "SELECT f.*, o.order_number, u.name as uploaded_by_name 
                 FROM " . $this->table . " f 
                 JOIN orders o ON f.order_id = o.id
                 LEFT JOIN users u ON f.uploaded_by = u.id
                 WHERE f.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get files by order ID
     */
    public function getByOrderId($orderId) {
        $query = "SELECT f.*, u.name as uploaded_by_name 
                 FROM " . $this->table . " f 
                 LEFT JOIN users u ON f.uploaded_by = u.id
                 WHERE f.order_id = ?
                 ORDER BY f.uploaded_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Create new file record
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                 (order_id, filename, original_filename, file_type, file_size, uploaded_by, uploaded_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->execute([
            $data['order_id'],
            $data['filename'],
            $data['original_filename'],
            $data['file_type'],
            $data['file_size'],
            $data['uploaded_by']
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Delete file
     */
    public function delete($id) {
        // Get file info first
        $file = $this->getById($id);
        
        if (!$file) {
            return false;
        }
        
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$id]);
        
        if ($result) {
            // Delete physical file
            $filePath = UPLOAD_PATH . '/' . $file['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        return $result;
    }
    
    /**
     * Get files by type and order ID
     */
    public function getByTypeAndOrderId($type, $orderId) {
        $query = "SELECT f.*, u.name as uploaded_by_name 
                 FROM " . $this->table . " f 
                 LEFT JOIN users u ON f.uploaded_by = u.id
                 WHERE f.order_id = ? AND f.file_type = ?
                 ORDER BY f.uploaded_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderId, $type]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Count files by type
     */
    public function countByType() {
        $query = "SELECT file_type, COUNT(*) as count 
                 FROM " . $this->table . "
                 GROUP BY file_type";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetchAll();
        
        $counts = [
            FILE_TYPE_ARTWORK => 0,
            FILE_TYPE_MOCKUP => 0,
            FILE_TYPE_FINAL => 0
        ];
        
        foreach ($result as $row) {
            $counts[$row['file_type']] = $row['count'];
        }
        
        return $counts;
    }
}
?>