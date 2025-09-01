<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Reports & Analytics';
$base_url = '../';

// Require authentication
requireAuth();

// Require admin role
requireAdmin();

$stats = [];
$error_message = '';

try {
    // Get total users
    $users_sql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN role = 'buyer' THEN 1 END) as total_buyers,
                    COUNT(CASE WHEN role = 'seller' THEN 1 END) as total_sellers,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as total_admins
                  FROM users";
    $users_result = $conn->query($users_sql);
    $stats['users'] = $users_result->fetch_assoc();

    // Get total products
    $products_sql = "SELECT 
                       COUNT(*) as total_products,
                       COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_products,
                       COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_products,
                       COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_products
                     FROM products";
    $products_result = $conn->query($products_sql);
    $stats['products'] = $products_result->fetch_assoc();

    // Get total orders (excluding cancelled for totals)
    $orders_sql = "SELECT 
                     COUNT(CASE WHEN status != 'cancelled' THEN 1 END) as total_orders,
                     SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as total_revenue,
                     COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                     COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as processing_orders,
                     COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
                     COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                     COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                     COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
                   FROM orders";
    $orders_result = $conn->query($orders_sql);
    $stats['orders'] = $orders_result->fetch_assoc();

    // Get total sellers
    $sellers_sql = "SELECT 
                      COUNT(*) as total_sellers,
                      COUNT(CASE WHEN approved = 1 THEN 1 END) as approved_sellers,
                      COUNT(CASE WHEN approved = 0 THEN 1 END) as pending_sellers
                    FROM sellers";
    $sellers_result = $conn->query($sellers_sql);
    $stats['sellers'] = $sellers_result->fetch_assoc();

    // Get recent orders
    $recent_orders_sql = "SELECT o.*, u.name as customer_name
                          FROM orders o
                          JOIN users u ON o.user_id = u.id
                          ORDER BY o.created_at DESC
                          LIMIT 10";
    $recent_orders_result = $conn->query($recent_orders_sql);
    $recent_orders = [];
    if ($recent_orders_result) {
        while ($row = $recent_orders_result->fetch_assoc()) {
            $recent_orders[] = $row;
        }
    }

    // Get top selling products (exclude cancelled orders)
    $top_products_sql = "SELECT p.title, p.price, s.shop_name,
                         COUNT(oi.id) as times_ordered,
                         COALESCE(SUM(oi.quantity), 0) as total_quantity
                         FROM products p
                         JOIN sellers s ON p.seller_id = s.id
                         LEFT JOIN order_items oi ON p.id = oi.product_id
                         LEFT JOIN orders o ON oi.order_id = o.id
                         WHERE p.status = 'approved' AND (o.status IS NULL OR o.status != 'cancelled')
                         GROUP BY p.id
                         ORDER BY total_quantity DESC
                         LIMIT 10";
    $top_products_result = $conn->query($top_products_sql);
    $top_products = [];
    if ($top_products_result) {
        while ($row = $top_products_result->fetch_assoc()) {
            $top_products[] = $row;
        }
    }

    // Get category statistics
    $category_stats_sql = "SELECT c.name,
                           COUNT(p.id) as product_count,
                           COUNT(CASE WHEN p.status = 'approved' THEN 1 END) as approved_count
                           FROM categories c
                           LEFT JOIN products p ON c.id = p.category_id
                           GROUP BY c.id
                           ORDER BY product_count DESC";
    $category_stats_result = $conn->query($category_stats_sql);
    $category_stats = [];
    if ($category_stats_result) {
        while ($row = $category_stats_result->fetch_assoc()) {
            $category_stats[] = $row;
        }
    }

} catch (Exception $e) {
    error_log("Reports fetch error: " . $e->getMessage());
    if (DEBUG_MODE) {
        $error_message = "Database error: " . $e->getMessage();
    } else {
        $error_message = "We're experiencing some technical difficulties. Please try again later.";
    }
}

include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
        <div class="container">
            <div class="admin-header">
                <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                <p>View comprehensive analytics and reports for the marketplace.</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['users']['total_users'] ?? 0); ?></h3>
                        <p>Total Users</p>
                        <small><?= number_format($stats['users']['total_buyers'] ?? 0); ?> Buyers, <?= number_format($stats['users']['total_sellers'] ?? 0); ?> Sellers</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['products']['total_products'] ?? 0); ?></h3>
                        <p>Total Products</p>
                        <small><?= number_format($stats['products']['approved_products'] ?? 0); ?> Approved, <?= number_format($stats['products']['pending_products'] ?? 0); ?> Pending</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['orders']['total_orders'] ?? 0); ?></h3>
                        <p>Total Orders</p>
                        <small>$<?= number_format($stats['orders']['total_revenue'] ?? 0, 2); ?> Revenue</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['sellers']['total_sellers'] ?? 0); ?></h3>
                        <p>Total Sellers</p>
                        <small><?= number_format($stats['sellers']['approved_sellers'] ?? 0); ?> Approved, <?= number_format($stats['sellers']['pending_sellers'] ?? 0); ?> Pending</small>
                    </div>
                </div>
            </div>

            <!-- Order Statistics -->
            <div class="report-section">
                <h2>Order Statistics</h2>
                <div class="order-stats">
                    <div class="stat-item">
                        <span class="stat-label">Pending Orders:</span>
                        <span class="stat-value"><?= number_format($stats['orders']['pending_orders'] ?? 0); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Processing Orders:</span>
                        <span class="stat-value"><?= number_format($stats['orders']['processing_orders'] ?? 0); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Shipped Orders:</span>
                        <span class="stat-value"><?= number_format($stats['orders']['shipped_orders'] ?? 0); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Delivered Orders:</span>
                        <span class="stat-value"><?= number_format($stats['orders']['delivered_orders'] ?? 0); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Completed Orders:</span>
                        <span class="stat-value"><?= number_format($stats['orders']['completed_orders'] ?? 0); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Cancelled Orders:</span>
                        <span class="stat-value"><?= number_format($stats['orders']['cancelled_orders'] ?? 0); ?></span>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="report-section">
                <h2>Recent Orders</h2>
                <?php if (empty($recent_orders)): ?>
                    <div class="empty-state">
                        <p>No orders found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id']; ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']); ?></td>
                                        <td>$<?= number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $order['status']; ?>">
                                                <?= ucfirst(htmlspecialchars($order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Products -->
            <div class="report-section">
                <h2>Top Selling Products</h2>
                <?php if (empty($top_products)): ?>
                    <div class="empty-state">
                        <p>No product sales data available.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Orders</th>
                                    <th>Quantity Sold</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['title']); ?></td>
                                        <td><?= htmlspecialchars($product['shop_name']); ?></td>
                                        <td>$<?= number_format($product['price'], 2); ?></td>
                                        <td><?= number_format($product['times_ordered']); ?></td>
                                        <td><?= number_format($product['total_quantity'] ?? 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Category Statistics -->
            <div class="report-section">
                <h2>Category Statistics</h2>
                <?php if (empty($category_stats)): ?>
                    <div class="empty-state">
                        <p>No category data available.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Total Products</th>
                                    <th>Approved Products</th>
                                    <th>Pending Products</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category_stats as $category): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($category['name']); ?></td>
                                        <td><?= number_format($category['product_count']); ?></td>
                                        <td><?= number_format($category['approved_count']); ?></td>
                                        <td><?= number_format($category['product_count'] - $category['approved_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<style>
.reports-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: #007bff;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.8rem;
    color: #333;
}

.stat-content p {
    margin: 0 0 0.25rem 0;
    font-weight: 500;
    color: #666;
}

.stat-content small {
    color: #999;
    font-size: 0.8rem;
}

.report-section {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.report-section h2 {
    margin-bottom: 1.5rem;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}

.order-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat-label {
    font-weight: 500;
    color: #666;
}

.stat-value {
    font-weight: bold;
    color: #333;
    font-size: 1.1rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.data-table th,
.data-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-confirmed {
    background: #cce5ff;
    color: #004085;
}

.status-shipped {
    background: #d1ecf1;
    color: #0c5460;
}

.status-delivered {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.table-responsive {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .stats-overview {
        grid-template-columns: 1fr;
    }
    
    .order-stats {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php include '../includes/admin_footer.php'; ?>


