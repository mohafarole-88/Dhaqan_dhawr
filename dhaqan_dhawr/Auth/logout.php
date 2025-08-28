<?php
require_once __DIR__ . '/../includes/init.php';

// Destroy session
session_destroy();

// Start new session for flash message
session_start();
setFlashMessage('You have been logged out successfully.', 'success');

header('Location: ../index.php');
exit();
?>
