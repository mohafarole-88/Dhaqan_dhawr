<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dhaqan_dhowr');

// Application settings
define('SITE_NAME', 'Dhaqan Dhowr');
define('SITE_DESCRIPTION', 'Somali Cultural Marketplace - Preserving Heritage Through Commerce');
define('PASSWORD_MIN_LENGTH', 8);
define('DEBUG_MODE', false);

// Set timezone
date_default_timezone_set('UTC');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
