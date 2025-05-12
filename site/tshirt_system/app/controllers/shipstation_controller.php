<?php
/**
 * ShipStation Controller
 * 
 * Handles ShipStation API integration and order syncing
 */

require_once APP_PATH . '/helpers/shipstation_api.php';
require_once APP_PATH . '/models/order.php';
require_once APP_PATH . '/helpers/session.php';
require_once APP_PATH . '/helpers/security.php';

class ShipStationController {
    private $shipStationAPI;
    private $orderModel;
    
    public function __construct() {
        SessionHelper::start();
        
        // Check if user is logged in
        if (!SessionHelper::isLoggedIn()) {
            SessionHelper::setFlash('danger', 'Please log in to access this page.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Check if user has permission (Admin or Manager)
        if (SessionHelper::getUserRole() > ROLE_MANAGER) {
            SessionHelper::setFlash('danger', 'You do not have permission to access this page.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        
        $this->shipStationAPI = new ShipStationAPI();
        $this->orderModel = new OrderModel();
    }
    
    /**
     * Sync orders from ShipStation
     */
    public function syncOrders() {
        try {
            // Get orders from ShipStation (last 30 days by default)
            $params = [
                'orderDateStart' => date('Y-m-d', strtotime('-30 days')),
                'orderDateEnd' => date('Y-m-d', strtotime('+1 day')),
                'pageSize' => 100
            ];
            
            $response = $this->shipStationAPI->getOrders($params);
            
            $orders = $response['orders'] ?? [];
            $synced = 0;
            $updated = 0;
            
            foreach ($orders as $order) {
                // Check if order already exists in database
                $existingOrder = $this->orderModel->getByShipStationId($order['orderId']);
                
                // Prepare order data
                $orderData = [
                    'shipstation_order_id' => $order['orderId'],
                    'customer_name' => $order['shipTo']['name'],
                    'customer_email' => $order['customerEmail'],
                    'order_number' => $order['orderNumber'],
                    'order_total' => $order['orderTotal'],
                    'status' => STATUS_PENDING, // Default status for new orders
                    'assigned_to' => null,
                    'shipping_method' => $order['serviceCode'],
                    'shipping_address' => json_encode($order['shipTo']),
                    'order_details' => json_encode($order)
                ];
                
                if ($existingOrder) {
                    // Update existing order
                    $orderData['id'] = $existingOrder['id'];
                    
                    // Don't update status if it's already been modified locally
                    if ($existingOrder['status'] === STATUS_PENDING) {
                        $orderData['status'] = STATUS_PENDING;
                    } else {
                        unset($orderData['status']);
                    }
                    
                    // Keep current assignment
                    unset($orderData['assigned_to']);
                    
                    $this->orderModel->update($orderData);
                    $updated++;
                } else {
                    // Create new order
                    $this->orderModel->create($orderData);
                    $synced++;
                }
            }
            
            // Log the sync action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'sync_orders',
                'shipstation',
                0,
                "Synced {$synced} new orders, updated {$updated} existing orders"
            );
            
            SessionHelper::setFlash('success', "Sync complete! {$synced} new orders imported, {$updated} orders updated.");
            header('Location: ' . BASE_URL . '/orders');
            exit;
        } catch (Exception $e) {
            SessionHelper::setFlash('danger', 'Error syncing orders: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/orders');
            exit;
        }
    }
    
    /**
     * Update order status in ShipStation (mark as shipped)
     */
    public function markOrderShipped($orderId, $trackingNumber, $carrierCode) {
        try {
            // Get order from database
            $order = $this->orderModel->getById($orderId);
            
            if (!$order) {
                throw new Exception('Order not found.');
            }
            
            // Prepare data for ShipStation
            $data = [
                'orderId' => $order['shipstation_order_id'],
                'carrierCode' => $carrierCode,
                'trackingNumber' => $trackingNumber,
                'notifyCustomer' => true,
                'shipDate' => date('Y-m-d')
            ];
            
            // Update in ShipStation
            $response = $this->shipStationAPI->updateOrderStatus($order['shipstation_order_id'], 'shipped');
            
            // Update local order
            $updateData = [
                'id' => $orderId,
                'status' => STATUS_SHIPPED,
                'tracking_number' => $trackingNumber,
                'shipped_date' => date('Y-m-d H:i:s')
            ];
            
            $this->orderModel->update($updateData);
            
            // Log the action
            SecurityHelper::logAction(
                SessionHelper::getUserId(),
                'mark_shipped',
                'orders',
                $orderId,
                "Tracking: {$trackingNumber}, Carrier: {$carrierCode}"
            );
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Error marking order as shipped: ' . $e->getMessage());
        }
    }
    
    /**
     * Create shipping label
     */
    public function createShippingLabel($orderId) {
        try {
            // Get order from database
            $order = $this->orderModel->getById($orderId);
            
            if (!$order) {
                throw new Exception('Order not found.');
            }
            
            // Get label from ShipStation
            $response = $this->shipStationAPI->createLabel([
                'orderId' => $order['shipstation_order_id'],
                'testLabel' => false
            ]);
            
            // Return label URL
            return $response['labelDownloadURL'] ?? null;
        } catch (Exception $e) {
            throw new Exception('Error creating shipping label: ' . $e->getMessage());
        }
    }
}
?>