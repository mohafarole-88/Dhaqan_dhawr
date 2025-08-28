<?php
// Get current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<!-- Sidebar Toggle Button (Mobile) -->
<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Admin Sidebar -->
<div class="seller-sidebar" id="adminSidebar">
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Overview
        </a>
        <a href="users.php" class="nav-item <?= $current_page === 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Manage Users
        </a>
        <a href="sellers.php" class="nav-item <?= $current_page === 'sellers.php' ? 'active' : '' ?>">
            <i class="fas fa-store"></i> Seller Applications
        </a>
        <a href="categories.php" class="nav-item <?= $current_page === 'categories.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Categories
        </a>
        <a href="moderation.php" class="nav-item <?= $current_page === 'moderation.php' ? 'active' : '' ?>">
            <i class="fas fa-eye"></i> Product Moderation
        </a>
        <a href="orders.php" class="nav-item <?= $current_page === 'orders.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i> All Orders
        </a>
        <a href="comments.php" class="nav-item <?= $current_page === 'comments.php' ? 'active' : '' ?>">
            <i class="fas fa-comments"></i> Comments
        </a>
        <a href="reports.php" class="nav-item <?= $current_page === 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Analytics
        </a>
    </nav>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    
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
                document.getElementById('adminSidebar').classList.remove('show');
            }
            // Don't prevent navigation
        });
    });
});
</script>
