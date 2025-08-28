<?php
// Set session configuration before any output
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

// Include configuration
require_once __DIR__ . '/config.php';

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Include authentication helper functions
require_once __DIR__ . '/auth_check.php';
?>
