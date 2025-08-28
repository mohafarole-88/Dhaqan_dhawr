<?php
// Get current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Check if seller is approved for certain actions
$seller_approved = isset($_SESSION['seller_approved']) ? $_SESSION['seller_approved'] : false;

// Determine if we're in the root directory or seller subdirectory
$is_root = ($current_dir === 'dhaqan_dhawr' || $current_dir === 'htdocs');
$path_prefix = $is_root ? 'seller/' : '';
$profile_path = $is_root ? 'profile.php' : '../profile.php';
?>

<!-- Sidebar Toggle Button (Mobile) -->
<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Seller Sidebar -->
<div class="seller-sidebar" id="sellerSidebar">
    <nav class="sidebar-nav">
        <a href="<?= $path_prefix ?>dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Overview
        </a>
        <?php if ($seller_approved): ?>
            <a href="<?= $path_prefix ?>add_product.php" class="nav-item <?= $current_page === 'add_product.php' ? 'active' : '' ?>">
                <i class="fas fa-plus-circle"></i> Add Product
            </a>
        <?php endif; ?>
        <a href="<?= $path_prefix ?>products.php" class="nav-item <?= $current_page === 'products.php' ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Manage Products
        </a>
        <a href="<?= $path_prefix ?>orders.php" class="nav-item <?= $current_page === 'orders.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i> My Orders
        </a>
        <a href="<?= $is_root ? 'messages.php' : '../messages.php' ?>" class="nav-item <?= $current_page === 'messages.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i> Messages
        </a>
    </nav>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sellerSidebar');
    const main = document.querySelector('.seller-main');
    
    sidebar.classList.toggle('show');
    
    // Close sidebar when clicking outside on mobile
    if (sidebar.classList.contains('show')) {
        document.addEventListener('click', function closeSidebar(e) {
            if (!sidebar.contains(e.target) && !e.target.classList.contains('sidebar-toggle')) {
                sidebar.classList.remove('show');
                document.removeEventListener('click', closeSidebar);
            }
        });
    }
}

// Auto-close sidebar on mobile when navigating
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Only close sidebar on mobile, not desktop
            if (window.innerWidth <= 768) {
                document.getElementById('sellerSidebar').classList.remove('show');
            }
            // Don't prevent navigation
        });
    });
});
</script>
