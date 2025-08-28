<?php
// Get current user info if logged in
$current_user = isLoggedIn() ? getCurrentUser() : null;
$cart_count = 0;

// Get cart count for logged in users
if ($current_user) {
    try {
        $stmt = $conn->prepare("SELECT SUM(qty) as total FROM cart_items WHERE user_id = ?");
        $stmt->bind_param("i", $current_user['id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $cart_count = $result['total'] ?? 0;
    } catch (Exception $e) {
        $cart_count = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Dhaqan Dhowr - Somali Cultural Marketplace' ?></title>
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<!-- Top Navigation Bar -->
<header class="buyer-header">
    <nav class="buyer-navbar">
        <div class="navbar-container">
            <!-- Brand -->
            <div class="navbar-brand">
                <a href="<?= $base_url; ?>index.php">
                    <span class="brand-text">Dhaqan Dhowr</span>
                </a>
            </div>

            <!-- Main Navigation -->
            <div class="navbar-nav">
                <a href="<?= $base_url ?>index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    Home
                </a>
                <a href="<?= $base_url ?>products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                    Products
                </a>
                <a href="<?= $base_url ?>category.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'category.php' ? 'active' : '' ?>">
                    Categories
                </a>
                <a href="<?= $base_url ?>about.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>">
                    About
                </a>
                <a href="<?= $base_url ?>contact.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>">
                    Contact
                </a>
            </div>

            <!-- Search Bar -->
            <div class="navbar-search">
                <form action="<?= $base_url ?>search.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Search cultural items..." class="search-input" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- User Actions -->
            <div class="navbar-actions">
                <?php if ($current_user): ?>
                    <!-- Cart -->
                    <a href="<?= $base_url ?>buyer/cart.php" class="nav-action cart-link <?= basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : '' ?>">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- User Dropdown -->
                    <div class="nav-dropdown user-dropdown">
                        <a href="#" class="nav-action dropdown-toggle">
                            <i class="fas fa-user-circle"></i>
                            <span class="user-name"><?= htmlspecialchars($current_user['name']) ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-header">
                                <strong><?= htmlspecialchars($current_user['name']) ?></strong>
                                <small><?= ucfirst($current_user['role']) ?></small>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="<?= $base_url ?>profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <?php if ($current_user['role'] === 'buyer'): ?>
                                <a href="<?= $base_url ?>buyer/orders.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            <?php endif; ?>
                            <a href="<?= $base_url ?>messages.php" class="dropdown-item">
                                <i class="fas fa-comments"></i> Messages
                            </a>
                            <?php if ($current_user['role'] === 'seller'): ?>
                                <div class="dropdown-divider"></div>
                                <a href="<?= $base_url ?>seller/dashboard.php" class="dropdown-item">
                                    <i class="fas fa-store"></i> Seller Dashboard
                                </a>
                            <?php endif; ?>
                            <?php if ($current_user['role'] === 'admin'): ?>
                                <div class="dropdown-divider"></div>
                                <a href="<?= $base_url ?>admin/index.php" class="dropdown-item">
                                    <i class="fas fa-cog"></i> Admin Panel
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= $base_url ?>Auth/logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Guest Actions -->
                    <a href="<?= $base_url ?>index.php?show_login=1" class="btn btn-outline btn-sm">Login</a>
                    <a href="<?= $base_url ?>index.php?show_signup=1" class="btn btn-primary btn-sm">Sign Up</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-content">
            <a href="<?= $base_url ?>index.php" class="mobile-nav-link">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="<?= $base_url ?>products.php" class="mobile-nav-link">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="<?= $base_url ?>category.php" class="mobile-nav-link">
                <i class="fas fa-th-large"></i> Categories
            </a>
            <a href="<?= $base_url ?>about.php" class="mobile-nav-link">
                <i class="fas fa-info-circle"></i> About
            </a>
            <a href="<?= $base_url ?>contact.php" class="mobile-nav-link">
                <i class="fas fa-envelope"></i> Contact
            </a>
            
            <?php if ($current_user): ?>
                <div class="mobile-menu-divider"></div>
                <a href="<?= $base_url ?>profile.php" class="mobile-nav-link">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="<?= $base_url ?>buyer/orders.php" class="mobile-nav-link">
                    <i class="fas fa-shopping-bag"></i> My Orders
                </a>
                <a href="<?= $base_url ?>buyer/cart.php" class="mobile-nav-link">
                    <i class="fas fa-shopping-cart"></i> Cart <?php if ($cart_count > 0): ?>(<?= $cart_count ?>)<?php endif; ?>
                </a>
                <a href="<?= $base_url ?>messages.php" class="mobile-nav-link">
                    <i class="fas fa-comments"></i> Messages
                </a>
                <a href="<?= $base_url ?>Auth/logout.php" class="mobile-nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <div class="mobile-menu-divider"></div>
                <a href="<?= $base_url ?>Auth/login.php" class="mobile-nav-link">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="<?= $base_url ?>Auth/register.php" class="mobile-nav-link">
                    <i class="fas fa-user-plus"></i> Sign Up
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenu.classList.toggle('active');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const mobileMenu = document.getElementById('mobileMenu');
    const toggleButton = document.querySelector('.mobile-menu-toggle');
    
    if (!mobileMenu.contains(event.target) && !toggleButton.contains(event.target)) {
        mobileMenu.classList.remove('active');
    }
});

// Handle dropdown menus
document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            dropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    });
});
</script>

<!-- Include main.js for popup and alert functionality -->
<script src="<?= isset($base_url) ? $base_url : '' ?>assets/js/main.js"></script>
