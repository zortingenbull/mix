<?php
/**
 * Main entry point
 * 
 * Handles routing and dispatches requests to appropriate controllers
 */

// Define application root path
define('ROOT_PATH', dirname(__DIR__) . '/tshirt_system');

// Load config
require_once ROOT_PATH . '/config/config.php';

// Load helpers
require_once APP_PATH . '/helpers/session.php';
require_once APP_PATH . '/helpers/security.php';

// Start session
SessionHelper::start();

// Check if session is expired
if (SessionHelper::isLoggedIn() && SessionHelper::isSessionExpired()) {
    SessionHelper::destroy();
    SessionHelper::start();
    SessionHelper::setFlash('warning', 'Your session has expired. Please log in again.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// Parse URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path from URI if it exists
$basePath = parse_url(BASE_URL, PHP_URL_PATH);
if (!empty($basePath) && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// Split URI into segments
$segments = explode('/', trim($uri, '/'));
$controller = !empty($segments[0]) ? $segments[0] : 'dashboard';
$action = isset($segments[1]) ? $segments[1] : 'index';
$params = array_slice($segments, 2);

// Map routes to controllers
$routes = [
    // Auth routes
    'login' => ['controller' => 'AuthController', 'action' => 'showLoginForm'],
    'authenticate' => ['controller' => 'AuthController', 'action' => 'login'],
    'logout' => ['controller' => 'AuthController', 'action' => 'logout'],
    'forgot-password' => ['controller' => 'AuthController', 'action' => 'showForgotPasswordForm'],
    'reset-password' => ['controller' => 'AuthController', 'action' => 'showResetPasswordForm'],
    
    // Dashboard
    'dashboard' => ['controller' => 'DashboardController', 'action' => 'index'],
    
    // Users
    'users' => ['controller' => 'UserController', 'action' => 'index'],
    'users/create' => ['controller' => 'UserController', 'action' => 'create'],
    'users/store' => ['controller' => 'UserController', 'action' => 'store'],
    'users/edit' => ['controller' => 'UserController', 'action' => 'edit'],
    'users/update' => ['controller' => 'UserController', 'action' => 'update'],
    'users/delete' => ['controller' => 'UserController', 'action' => 'delete'],
    'profile' => ['controller' => 'UserController', 'action' => 'profile'],
    'profile/update' => ['controller' => 'UserController', 'action' => 'updateProfile'],
    
    // Orders
    'orders' => ['controller' => 'OrderController', 'action' => 'index'],
    'orders/sync' => ['controller' => 'ShipStationController', 'action' => 'syncOrders'],
    'orders/assign' => ['controller' => 'OrderController', 'action' => 'assign'],
    'orders/update-status' => ['controller' => 'OrderController', 'action' => 'updateStatus'],
    'orders/notes' => ['controller' => 'OrderController', 'action' => 'addNotes'],
    
    // Jobs
    'jobs' => ['controller' => 'JobController', 'action' => 'index'],
    'jobs/create' => ['controller' => 'JobController', 'action' => 'create'],
    'jobs/store' => ['controller' => 'JobController', 'action' => 'store'],
    'jobs/update-status' => ['controller' => 'JobController', 'action' => 'updateStatus'],
    'jobs/update-notes' => ['controller' => 'JobController', 'action' => 'updateNotes'],
    'jobs/start' => ['controller' => 'JobController', 'action' => 'startJob'],
    'jobs/mark-printed' => ['controller' => 'JobController', 'action' => 'markPrinted'],
    
    // Files
    'files' => ['controller' => 'FileController', 'action' => 'index'],
    'files/create' => ['controller' => 'FileController', 'action' => 'create'],
    'files/upload' => ['controller' => 'FileController', 'action' => 'upload'],
    'files/download' => ['controller' => 'FileController', 'action' => 'download'],
    'files/delete' => ['controller' => 'FileController', 'action' => 'delete'],
    'files/list' => ['controller' => 'FileController', 'action' => 'listFiles'],
    
    // Shipping
    'shipping' => ['controller' => 'ShippingController', 'action' => 'index'],
    'shipping/mark-shipped' => ['controller' => 'ShippingController', 'action' => 'markShipped'],
    'shipping/generate-label' => ['controller' => 'ShippingController', 'action' => 'generateLabel'],
    'shipping/manual' => ['controller' => 'ShippingController', 'action' => 'manualShipping'],
    
    // Logs (admin only)
    'logs' => ['controller' => 'LogController', 'action' => 'index'],
];

// Handle dynamic routes (with parameters)
$dynamicRoutes = [
    'orders' => ['controller' => 'OrderController', 'action' => 'show'],
    'jobs' => ['controller' => 'JobController', 'action' => 'show'],
    'users/edit' => ['controller' => 'UserController', 'action' => 'edit'],
    'files/download' => ['controller' => 'FileController', 'action' => 'download'],
    'files/create' => ['controller' => 'FileController', 'action' => 'create'],
    'jobs/create' => ['controller' => 'JobController', 'action' => 'create'],
    'files/list' => ['controller' => 'FileController', 'action' => 'listFiles'],
    'reset-password' => ['controller' => 'AuthController', 'action' => 'showResetPasswordForm'],
];

// Construct route key
$routeKey = !empty($controller) ? $controller : '';
if (!empty($action) && $action !== 'index') {
    $routeKey .= '/' . $action;
}

// Check if route exists in routes map
if (isset($routes[$routeKey])) {
    $route = $routes[$routeKey];
    $controllerName = $route['controller'];
    $actionName = $route['action'];
    $params = [];
} elseif (isset($dynamicRoutes[$controller]) && !empty($action) && is_numeric($action)) {
    // Handle dynamic routes with numeric ID
    $route = $dynamicRoutes[$controller];
    $controllerName = $route['controller'];
    $actionName = $route['action'];
    $params = [$action]; // The ID is the first parameter
} elseif (isset($dynamicRoutes[$routeKey]) && !empty($params[0])) {
    // Handle dynamic routes with parameters
    $route = $dynamicRoutes[$routeKey];
    $controllerName = $route['controller'];
    $actionName = $route['action'];
    // Keep the parameters
} else {
    // If route not found in map, use default controller/action pattern
    $controllerClassName = ucfirst($controller) . 'Controller';
    $controllerName = $controllerClassName;
    $actionName = $action;
}

// If accessing the login page and already logged in, redirect to dashboard
if ($controllerName === 'AuthController' && 
    ($actionName === 'showLoginForm' || $actionName === 'login') && 
    SessionHelper::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}

// Load controller
$controllerFile = APP_PATH . '/controllers/' . strtolower(str_replace('Controller', '', $controllerName)) . '_controller.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    
    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        
        if (method_exists($controller, $actionName)) {
            call_user_func_array([$controller, $actionName], $params);
        } else {
            // Action not found - return 404
            header('HTTP/1.0 404 Not Found');
            include PUBLIC_PATH . '/404.php';
        }
    } else {
        // Controller class not found - return 404
        header('HTTP/1.0 404 Not Found');
        include PUBLIC_PATH . '/404.php';
    }
} else {
    // Controller file not found - return 404
    header('HTTP/1.0 404 Not Found');
    include PUBLIC_PATH . '/404.php';
}
?>