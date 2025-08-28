<?php
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= isset($base_url) ? $base_url : '' ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-brand">
                <a href="<?= isset($base_url) ? $base_url : '' ?>index.php">
                    <h1><?= SITE_NAME ?></h1>
                </a>
            </div>
            
            <div class="nav-links">
                <div class="user-info">
                    <span>Welcome, <?= getCurrentUserName() ?>!</span>
                    <a href="<?= isset($base_url) ? $base_url : '' ?>Auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <?php if ($flash): ?>
        <div class="flash-message <?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <main class="main-content">
