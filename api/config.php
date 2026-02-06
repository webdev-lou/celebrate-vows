<?php
/**
 * Database Configuration
 * Update these values with your Hostinger MySQL credentials
 */

// Database credentials - UPDATE THESE FOR YOUR HOSTINGER ACCOUNT
define('DB_HOST', 'localhost');
define('DB_NAME', 'u120438863_wedding_db'); // e.g., u123456789_wedding
define('DB_USER', 'u120438863_wedding_db'); // e.g., u123456789_admin
define('DB_PASS', '42@3qiKpc3cF?0dLfa');
define('DB_CHARSET', 'utf8mb4');

// Site settings
define('SITE_URL', 'https://maedmikomplete.celebratevows.com/'); // Update for production
define('ADMIN_SESSION_NAME', 'wedding_admin_session');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');

// Error reporting (set to false in production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Asia/Manila');