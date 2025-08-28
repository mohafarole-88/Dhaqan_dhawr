<?php
// Authentication helper functions

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function isAdmin() {
    return hasRole('admin');
}

function isSeller() {
    return hasRole('seller');
}

function isBuyer() {
    return hasRole('buyer');
}

function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = 'Please log in to access this page.';
        $_SESSION['flash_type'] = 'error';
        
        // Always redirect to main page with login popup
        $redirect_path = 'index.php?show_login=1';
        if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
            strpos($_SERVER['PHP_SELF'], '/buyer/') !== false || 
            strpos($_SERVER['PHP_SELF'], '/seller/') !== false) {
            $redirect_path = '../index.php?show_login=1';
        }
        
        header('Location: ' . $redirect_path);
        exit();
    }
}

function requireRole($role) {
    requireAuth();
    if (!hasRole($role)) {
        $_SESSION['flash_message'] = 'You do not have permission to access this page.';
        $_SESSION['flash_type'] = 'error';
        header('Location: index.php');
        exit();
    }
}

function requireAdmin() {
    requireRole('admin');
}

function requireSeller() {
    requireRole('seller');
}

function requireBuyer() {
    requireRole('buyer');
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['name'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    $message = $_SESSION['flash_message'] ?? null;
    $type = $_SESSION['flash_type'] ?? 'info';
    
    if ($message) {
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    
    return null;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
