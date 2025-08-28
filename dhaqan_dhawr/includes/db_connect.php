<?php
// Create connection with error handling and multiple host fallback
try {
    // Try multiple host configurations for MariaDB compatibility
    $hosts = [DB_HOST, '127.0.0.1', 'localhost', '::1'];
    $conn = null;
    $last_error = '';
    
    foreach ($hosts as $host) {
        try {
            // Create connection with timeout
            $conn = new mysqli();
            
            // Set connection timeout (2 seconds) before connecting
            mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
            
            // Now connect
            $conn->real_connect($host, DB_USER, DB_PASS, DB_NAME);
            
            if (!$conn->connect_error) {
                break; // Connection successful
            }
            $last_error = $conn->connect_error;
            $conn = null;
        } catch (Exception $e) {
            $last_error = $e->getMessage();
            continue;
        }
    }
    
    // Check if any connection succeeded
    if (!$conn) {
        throw new Exception("Database connection failed: " . $last_error);
    }
    
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+00:00'");
    
    // Also create PDO connection for compatibility
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // If PDO fails, we still have mysqli connection
        error_log("PDO connection failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    if (DEBUG_MODE) {
        die("Database connection error: " . $e->getMessage());
    } else {
        die("Sorry, we're experiencing technical difficulties. Please try again later.");
    }
}
?>
