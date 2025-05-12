<?php
/**
 * Main configuration file
 * 
 * Contains global settings, constants, and configuration parameters
 */

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Base URL - update this to your domain
// Base URL - update this to your domain
define('BASE_URL', 'http://orelet.site');

// Application paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', dirname(ROOT_PATH) . '/public_html');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');

// ShipStation API credentials
define('SHIPSTATION_API_KEY', 'your_api_key');
define('SHIPSTATION_API_SECRET', 'your_api_secret');
define('SHIPSTATION_API_URL', 'https://ssapi.shipstation.com');

// Security settings
define('CSRF_TOKEN_SECRET', 'change_this_to_a_random_string');
define('PASSWORD_COST', 12); // For bcrypt
define('SESSION_LIFETIME', 3600); // 1 hour

// User roles
define('ROLE_ADMIN', 1);
define('ROLE_MANAGER', 2);
define('ROLE_PRODUCTION', 3);
define('ROLE_SHIPPING', 4);
define('ROLE_VIEWER', 5);

// Order statuses
define('STATUS_PENDING', 1);
define('STATUS_QUEUED', 2);
define('STATUS_IN_PROGRESS', 3);
define('STATUS_PRINTED', 4);
define('STATUS_SHIPPED', 5);

// File types
define('FILE_TYPE_ARTWORK', 1);
define('FILE_TYPE_MOCKUP', 2);
define('FILE_TYPE_FINAL', 3);

// Time zone
date_default_timezone_set('America/New_York');
?>