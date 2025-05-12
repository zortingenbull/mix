<?php
/**
 * Job Model
 * 
 * Handles database operations for print jobs
 */

require_once ROOT_PATH . '/config/database.php';

class JobModel {
    private $conn;
    private $table = 'jobs';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all jobs
     */
    public function getAll($limit = 100, $offset = 0, $filters = []) {
        $query = "SELECT j.*, o.order_number, o.customer_name, u.name as assigned_user_name, 
                 u2.name as updated_by_name
                 FROM " . $this->table . " j 
                 JOIN orders o ON j.order_id = o.id
                 LEFT JOIN users u ON o.assigned_to = u.id
                 LEFT JOIN users u2 ON j.updated_by = u2.id";
        
        // Add filters if provided
        $whereClause = [];
        $params = [];
        
        if (!empty($filters)) {
            if (isset($filters['status']) && $filters['status'] !== '') {
                $whereClause[] = "j.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['assigned_to']) && $filters['assigned_to'] !== '') {
                $whereClause[] = "o.assigned_to = ?";
                $params[] = $filters['assigned_to'];
            }
            
            if (isset($filters['search']) && $filters['search'] !== '') {
                $whereClause[] = "(o.customer_name LIKE ? OR o.order_number LIKE ?)";
                $search = "%" . $filters['search'] . "%";
                $params[] = $search;
                $params[] = $search;
            }
        }
        
        if (!empty($whereClause)) {
            $query .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $query .= " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get job by ID
     */
    public function getById($id) {
        $query = "SELECT j.*, o.order_number, o.customer_name, o.id as order_id, o.assigned_to,
                 u.name as assigned_user_name, u2.name as updated_by_name
                 FROM " . $this->table . " j 
                 JOIN orders o ON j.order_id = o.id
                 LEFT JOIN users u ON o.assigned_to = u.id
                 LEFT JOIN users u2 ON j.updated_by = u2.id
                 WHERE j.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get jobs by order ID
     */
    public function getByOrderId($orderId) {
        $query = "SELECT j.*, u.name as updated_by_name 
                 FROM " . $this->table . " j 
                 LEFT JOIN users u ON j.updated_by = u.id
                 WHERE j.order_id = ?
                 ORDER BY j.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get jobs assigned to user
     */
    public function getByAssignedUser($userId, $limit = 50) {
        $query = "SELECT j.*, o.order_number, o.customer_name, u.name as updated_by_name
                 FROM " . $this->table . " j 
                 JOIN orders o ON j.order_id = o.id
                 LEFT JOIN users u ON j.updated_by = u.id
                 WHERE o.assigned_to = ? AND j.status < ?
                 ORDER BY j.status ASC, j.created_at ASC
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, STATUS_SHIPPED, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Create new job
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                 (order_id, status, notes, updated_by, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->execute([
            $data['order_id'],
            $data['status'],
            $data['notes'],
            $data['updated_by']
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Update job
     */
    public function update($data) {
        $fields = [];
        $values = [];
        
        // Only include fields that are present in the data array
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $values[] = $data['status'];
        }
        
        if (isset($data['notes'])) {
            $fields[] = "notes = ?";
            $values[] = $data['notes'];
        }
        
        if (isset($data['updated_by'])) {
            $fields[] = "updated_by = ?";
            $values[] = $data['updated_by'];
        }
        
        // Always update the updated_at timestamp
        $fields[] = "updated_at = NOW()";
        
        if (empty($fields)) {
            return false;
        }
        
        // Add ID at the end for the WHERE clause
        $values[] = $data['id'];
        
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute($values);
    }
    
    /**
     * Delete job
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Count jobs by status
     */
    public function countByStatus() {
        $query = "SELECT j.status, COUNT(*) as count 
                 FROM " . $this->table . " j 
                 GROUP BY j.status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetchAll();
        
        $counts = [
            STATUS_PENDING => 0,
            STATUS_QUEUED => 0,
            STATUS_IN_PROGRESS => 0,
            STATUS_PRINTED => 0,
            STATUS_SHIPPED => 0
        ];
        
        foreach ($result as $row) {
            $counts[$row['status']] = $row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Get jobs ready for shipping
     */
    public function getReadyForShipping($limit = 50) {
        $query = "SELECT j.*, o.order_number, o.customer_name, o.shipstation_order_id, o.shipping_method,
                 o.shipping_address, o.tracking_number, u.name as assigned_user_name
                 FROM " . $this->table . " j 
                 JOIN orders o ON j.order_id = o.id
                 LEFT JOIN users u ON o.assigned_to = u.id
                 WHERE j.status = ? AND o.status != ?
                 ORDER BY j.updated_at ASC
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([STATUS_PRINTED, STATUS_SHIPPED, $limit]);
        
        return $stmt->fetchAll();
    }
}
?>