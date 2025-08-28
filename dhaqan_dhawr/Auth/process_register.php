<?php
require_once __DIR__ . '/../includes/init.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?show_signup=1');
    exit();
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$errors = [];
$name = sanitizeInput($_POST['name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = sanitizeInput($_POST['role'] ?? 'buyer');

// Validation
if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email) || !validateEmail($email)) {
    $errors[] = 'Valid email is required';
}

if (empty($phone)) {
    $errors[] = 'Phone number is required';
}

if (empty($password) || strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

if (!in_array($role, ['buyer', 'seller'])) {
    $errors[] = 'Invalid role selected';
}

// Check if email already exists
if (empty($errors)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Email already registered';
    }
}

// If no errors, create user
if (empty($errors)) {
    $password_hash = hashPassword($password);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password_hash, role, verified, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
    $stmt->bind_param("sssss", $name, $email, $phone, $password_hash, $role);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Auto-login the user
        $_SESSION['user_id'] = $user_id;
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['verified'] = true;
        
        setFlashMessage('Welcome to Dhaqan Dhowr, ' . $name . '!', 'success');
        
        // For AJAX requests, return JSON response
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'type' => 'signup',
                'message' => 'Signup successful! Welcome to Dhaqan Dhowr!',
                'redirect' => '../index.php'
            ]);
            exit();
        }
        
        header('Location: ../index.php?success=signup');
        exit();
    } else {
        $errors[] = 'Registration failed. Please try again.';
    }
}

// If we get here, there were errors
if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    
    // For AJAX requests, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'type' => 'signup',
            'message' => 'Registration failed! Please check your information and try again.',
            'errors' => $errors
        ]);
        exit();
    }
}

header('Location: ../index.php?show_signup=1&error=1');
exit();
?>
