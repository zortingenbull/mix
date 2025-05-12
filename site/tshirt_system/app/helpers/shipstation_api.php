<?php
/**
 * ShipStation API Helper
 * 
 * Provides methods to interact with the ShipStation API
 */

class ShipStationAPI {
    private $apiKey;
    private $apiSecret;
    private $apiUrl;
    
    public function __construct() {
        $this->apiKey = SHIPSTATION_API_KEY;
        $this->apiSecret = SHIPSTATION_API_SECRET;
        $this->apiUrl = SHIPSTATION_API_URL;
    }
    
    /**
     * Make API request to ShipStation
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->apiUrl . $endpoint;
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ':' . $this->apiSecret);
        
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception('API error: HTTP code ' . $httpCode . ' - ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get orders from ShipStation
     */
    public function getOrders($params = []) {
        $endpoint = '/orders';
        
        // Add query parameters if provided
        if (!empty($params)) {
            $queryString = http_build_query($params);
            $endpoint .= '?' . $queryString;
        }
        
        return $this->makeRequest($endpoint);
    }
    
    /**
     * Get order by ID
     */
    public function getOrder($orderId) {
        $endpoint = '/orders/' . $orderId;
        
        return $this->makeRequest($endpoint);
    }
    
    /**
     * Create/update order in ShipStation
     */
    public function createUpdateOrder($orderData) {
        $endpoint = '/orders/createorder';
        
        return $this->makeRequest($endpoint, 'POST', $orderData);
    }
    
    /**
     * Get shipping label
     */
    public function createLabel($labelData) {
        $endpoint = '/orders/createlabelfororder';
        
        return $this->makeRequest($endpoint, 'POST', $labelData);
    }
    
    /**
     * Update order status in ShipStation
     */
    public function updateOrderStatus($orderId, $status) {
        $data = [
            'orderId' => $orderId,
            'orderStatus' => $status
        ];
        
        $endpoint = '/orders/markasshipped';
        
        return $this->makeRequest($endpoint, 'POST', $data);
    }
    
    /**
     * Get shipping rates
     */
    public function getRates($rateData) {
        $endpoint = '/shipments/getrates';
        
        return $this->makeRequest($endpoint, 'POST', $rateData);
    }
    
    /**
     * Get carriers
     */
    public function getCarriers() {
        $endpoint = '/carriers';
        
        return $this->makeRequest($endpoint);
    }
    
    /**
     * Get shipments
     */
    public function getShipments($params = []) {
        $endpoint = '/shipments';
        
        // Add query parameters if provided
        if (!empty($params)) {
            $queryString = http_build_query($params);
            $endpoint .= '?' . $queryString;
        }
        
        return $this->makeRequest($endpoint);
    }
}
?>