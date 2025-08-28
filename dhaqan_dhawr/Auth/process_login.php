<?php
require_once __DIR__ . '/../includes/init.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?show_login=1');
    exit();
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$errors = [];
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
if (empty($email)) {
    $errors[] = 'Email is required';
}

if (empty($password)) {
    $errors[] = 'Password is required';
}

// If no validation errors, attempt login
if (empty($errors)) {
    $stmt = $conn->prepare("SELECT id, name, email, password_hash, role, verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (verifyPassword($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['verified'] = (bool)$user['verified'];
            
            setFlashMessage('Welcome back, ' . $user['name'] . '!', 'success');
            
            // For AJAX requests, return JSON response
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'type' => 'login',
                    'message' => 'Login successful! Welcome back!',
                    'redirect' => $user['role'] === 'admin' ? '../admin/index.php' : 
                                ($user['role'] === 'seller' ? '../seller/dashboard.php' : '../index.php')
                ]);
                exit();
            }
            
            // Redirect based on role with success parameter
            if ($user['role'] === 'admin') {
                header('Location: ../admin/index.php?success=login');
            } elseif ($user['role'] === 'seller') {
                header('Location: ../seller/dashboard.php?success=login');
            } else {
                header('Location: ../index.php?success=login');
            }
            exit();
        } else {
            $errors[] = 'Invalid email or password';
        }
    } else {
        $errors[] = 'Invalid email or password';
    }
}

// If we get here, there were errors
if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    
    // For AJAX requests, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'type' => 'login',
            'message' => 'Invalid credentials! Please check your email and password.',
            'errors' => $errors
        ]);
        exit();
    }
}

header('Location: ../index.php?show_login=1&error=1');
exit();
?>
