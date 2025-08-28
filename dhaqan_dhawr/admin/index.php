<?php
// Include initialization file (handles config, session, DB, and auth)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Admin Dashboard';
$base_url = '../';

// Require admin role
requireAdmin();

// Get admin statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Total sellers
$result = $conn->query("SELECT COUNT(*) as total FROM sellers");
$stats['total_sellers'] = $result->fetch_assoc()['total'];

// Pending seller applications
$result = $conn->query("SELECT COUNT(*) as total FROM sellers WHERE approved = 0");
$stats['pending_sellers'] = $result->fetch_assoc()['total'];

// Total products
$result = $conn->query("SELECT COUNT(*) as total FROM products");
$stats['total_products'] = $result->fetch_assoc()['total'];

// Pending products
$result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'pending'");
$stats['pending_products'] = $result->fetch_assoc()['total'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['total'];

// Total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Recent orders
$stmt = $conn->prepare("
    SELECT o.*, u.name as buyer_name, COUNT(oi.id) as item_count
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_orders = $stmt->get_result();

// Recent products
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, s.shop_name
    FROM products p 
    JOIN categories c ON p.category_id = c.id
    JOIN sellers s ON p.seller_id = s.id
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_products = $stmt->get_result();

// Pending seller applications
$stmt = $conn->prepare("
    SELECT s.*, u.name, u.email
    FROM sellers s
    JOIN users u ON s.user_id = u.id
    WHERE s.approved = 0
    ORDER BY s.created_at DESC
    LIMIT 5
");
$stmt->execute();
$pending_sellers = $stmt->get_result();

include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="seller-layout">

    <main class="seller-main">
        <div class="container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars(getCurrentUserName()); ?>!</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_users']); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_sellers']); ?></h3>
                    <p>Total Sellers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_products']); ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['total_orders']); ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <h3>$<?= number_format($stats['total_revenue'], 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= number_format($stats['pending_products']); ?></h3>
                    <p>Pending Products</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <a href="moderation.php" class="btn btn-primary">
                    <i class="fas fa-tasks"></i> Moderation Queue
                </a>
                <a href="users.php" class="btn btn-secondary">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="reports.php" class="btn btn-secondary">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="categories.php" class="btn btn-outline">
                    <i class="fas fa-tags"></i> Manage Categories
                </a>
                <a href="reviews.php" class="btn btn-outline">
                    <i class="fas fa-star"></i> Manage Reviews
                </a>
            </div>
        </div>
        
        <!-- Pending Applications -->
        <?php if ($pending_sellers->num_rows > 0): ?>
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Pending Seller Applications</h2>
                    <a href="moderation.php" class="btn btn-outline">View All</a>
                </div>
                
                <div class="admin-content">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Shop Name</th>
                                    <th>Location</th>
                                    <th>Applied</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php while ($seller = $pending_sellers->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($seller['name']); ?></strong><br>
                                        <small><?= htmlspecialchars($seller['email']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($seller['shop_name']); ?></td>
                                    <td><?= htmlspecialchars($seller['location']); ?></td>
                                    <td><?= date('M j, Y', strtotime($seller['created_at'])); ?></td>
                                    <td>
                                        <a href="review_seller.php?id=<?= $seller['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recent Orders -->
        <div class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-shopping-bag"></i> Recent Orders</h2>
                <a href="orders.php" class="btn btn-outline">View All</a>
            </div>
            
            <?php if ($recent_orders->num_rows > 0): ?>
                <div class="admin-content">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $order['id']; ?></td>
                                    <td><?= htmlspecialchars($order['buyer_name']); ?></td>
                                    <td>$<?= number_format($order['total_amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?= $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'info'); ?>">
                                            <?= ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order_details.php?id=<?= $order['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-center">No orders yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Recent Products -->
        <div class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-box"></i> Recent Products</h2>
                <a href="products.php" class="btn btn-outline">View All</a>
            </div>
            
            <?php if ($recent_products->num_rows > 0): ?>
                <div class="admin-content">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Seller</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php while ($product = $recent_products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($product['title']); ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($product['shop_name']); ?></td>
                                    <td><?= htmlspecialchars($product['category_name']); ?></td>
                                    <td>$<?= number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?= $product['status'] === 'approved' ? 'success' : ($product['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?= ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="review_product.php?id=<?= $product['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-center">No products yet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.admin-header {
    text-align: center;
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
    justify-items: center;
}

.stat-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-height: 140px;
    width: 100%;
    max-width: 280px;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #4285f4, #1a73e8);
    color: white;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
}

.stat-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
}

.stat-content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 2.2rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1.2;
}

.stat-content p {
    margin: 0;
    color: #5f6368;
    font-size: 0.95rem;
    font-weight: 500;
    line-height: 1.3;
    word-wrap: break-word;
    hyphens: auto;
}

.quick-actions {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.dashboard-section {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Auto-centering for incomplete rows */
.stats-grid:has(.stat-card:nth-child(4n+1):last-child) {
    justify-content: center;
}

.stats-grid:has(.stat-card:nth-child(4n+2):last-child) {
    justify-content: center;
}

.stats-grid:has(.stat-card:nth-child(4n+3):last-child) {
    justify-content: center;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .stat-card {
        max-width: 260px;
    }
}

@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        padding: 1.5rem;
        min-height: 120px;
        max-width: 240px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .stat-content h3 {
        font-size: 1.8rem;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .stat-card {
        padding: 1.25rem;
        gap: 0.75rem;
        min-height: 100px;
        max-width: 200px;
    }
    
    .stat-icon {
        width: 45px;
        height: 45px;
        font-size: 1.3rem;
    }
    
    .stat-content h3 {
        font-size: 1.6rem;
    }
    
    .stat-content p {
        font-size: 0.85rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        max-width: 100%;
    }
}
    </main>
</div>

<?php include '../includes/admin_footer.php'; ?>
