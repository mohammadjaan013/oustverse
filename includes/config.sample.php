<?php
/**
 * Configuration File - Sample
 * Copy this file to config.php and update with your settings
 */

// Site Information
define('SITE_NAME', 'Biziverse ERP');
define('SITE_TAGLINE', 'Smart Business Console');
define('BASE_URL', 'http://localhost/biziverse-clone'); // Update for production

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'biziverse_erp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'BIZIVERSE_SESSION');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// Pagination
define('RECORDS_PER_PAGE', 25);

// Date & Time
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd-m-Y');
define('DISPLAY_DATETIME_FORMAT', 'd-m-Y h:i A');

// File Upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Currency
define('CURRENCY_SYMBOL', '₹');
define('CURRENCY_CODE', 'INR');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
