<?php
/**
 * Order Model
 * 
 * Handles database operations for orders
 */

require_once ROOT_PATH . '/config/database.php';

class OrderModel {
    private $conn;
    private $table = 'orders';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all orders
     */
    public function getAll($limit = 100, $offset = 0, $filters = []) {
        $query = "SELECT o.*, u.name as assigned_user_name 
                 FROM " . $this->table . " o 
                 LEFT JOIN users u ON o.assigned_to = u.id";
        
        // Add filters if provided
        $whereClause = [];
        $params = [];
        
        if (!empty($filters)) {
            if (isset($filters['status']) && $filters['status'] !== '') {
                $whereClause[] = "o.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['assigned_to']) && $filters['assigned_to'] !== '') {
                $whereClause[] = "o.assigned_to = ?";
                $params[] = $filters['assigned_to'];
            }
            
            if (isset($filters['search']) && $filters['search'] !== '') {
                $whereClause[] = "(o.customer_name LIKE ? OR o.shipstation_order_id LIKE ?)";
                $search = "%" . $filters['search'] . "%";
                $params[] = $search;
                $params[] = $search;
            }
        }
        
        if (!empty($whereClause)) {
            $query .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get order by ID
     */
    public function getById($id) {
        $query = "SELECT o.*, u.name as assigned_user_name 
                 FROM " . $this->table . " o 
                 LEFT JOIN users u ON o.assigned_to = u.id 
                 WHERE o.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get order by ShipStation order ID
     */
    public function getByShipStationId($shipstationOrderId) {
        $query = "SELECT * FROM " . $this->table . " WHERE shipstation_order_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shipstationOrderId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Create new order
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                 (shipstation_order_id, customer_name, customer_email, order_number, order_total,
                  status, assigned_to, shipping_method, shipping_address, order_details, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->execute([
            $data['shipstation_order_id'],
            $data['customer_name'],
            $data['customer_email'],
            $data['order_number'],
            $data['order_total'],
            $data['status'],
            $data['assigned_to'],
            $data['shipping_method'],
            $data['shipping_address'],
            $data['order_details']
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Update order
     */
    public function update($data) {
        $fields = [];
        $values = [];
        
        // Only include fields that are present in the data array
        if (isset($data['customer_name'])) {
            $fields[] = "customer_name = ?";
            $values[] = $data['customer_name'];
        }
        
        if (isset($data['customer_email'])) {
            $fields[] = "customer_email = ?";
            $values[] = $data['customer_email'];
        }
        
        if (isset($data['order_number'])) {
            $fields[] = "order_number = ?";
            $values[] = $data['order_number'];
        }
        
        if (isset($data['order_total'])) {
            $fields[] = "order_total = ?";
            $values[] = $data['order_total'];
        }
        
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $values[] = $data['status'];
        }
        
        if (isset($data['assigned_to'])) {
            $fields[] = "assigned_to = ?";
            $values[] = $data['assigned_to'];
        }
        
        if (isset($data['shipping_method'])) {
            $fields[] = "shipping_method = ?";
            $values[] = $data['shipping_method'];
        }
        
        if (isset($data['shipping_address'])) {
            $fields[] = "shipping_address = ?";
            $values[] = $data['shipping_address'];
        }
        
        if (isset($data['order_details'])) {
            $fields[] = "order_details = ?";
            $values[] = $data['order_details'];
        }
        
        if (isset($data['tracking_number'])) {
            $fields[] = "tracking_number = ?";
            $values[] = $data['tracking_number'];
        }
        
        if (isset($data['shipped_date'])) {
            $fields[] = "shipped_date = ?";
            $values[] = $data['shipped_date'];
        }
        
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
     * Delete order
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Count orders by status
     */
    public function countByStatus() {
        $query = "SELECT status, COUNT(*) as count FROM " . $this->table . " GROUP BY status";
        
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
     * Get orders by status
     */
    public function getByStatus($status, $limit = 20) {
        $query = "SELECT o.*, u.name as assigned_user_name 
                 FROM " . $this->table . " o 
                 LEFT JOIN users u ON o.assigned_to = u.id 
                 WHERE o.status = ? 
                 ORDER BY o.created_at DESC 
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$status, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get orders assigned to a user
     */
    public function getByAssignedUser($userId, $limit = 20) {
        $query = "SELECT * FROM " . $this->table . " WHERE assigned_to = ? ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    }
}
?>