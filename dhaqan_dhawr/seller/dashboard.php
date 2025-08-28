<?php
// Include initialization file (handles config, session, DB, and auth)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Seller Dashboard';
$base_url = '../';

// Require seller role
requireSeller();

$user_id = getCurrentUserId();

// Get seller information
$stmt = $conn->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();

if (!$seller) {
    setFlashMessage('Please complete your seller application first.', 'error');
    header('Location: apply.php');
    exit();
}

// Get seller statistics
$seller_id = $seller['id'];

// Clean seller dashboard - remove debug output

// Total products
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$total_products = $stmt->get_result()->fetch_assoc()['total'];

// Approved products
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND status = 'approved'");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$approved_products = $stmt->get_result()->fetch_assoc()['total'];

// Pending products
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND status = 'pending'");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$pending_products = $stmt->get_result()->fetch_assoc()['total'];

// Total orders
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.id) as total 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE p.seller_id = ?
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];

// Recent products - Force correct seller filtering
$filtered_products = [];

// Direct query to ensure proper filtering
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          JOIN categories c ON p.category_id = c.id 
          WHERE p.seller_id = " . intval($seller_id) . "
          ORDER BY p.created_at DESC 
          LIMIT 5";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Double-check seller ownership
        if ($row['seller_id'] == $seller_id) {
            $filtered_products[] = $row;
        }
    }
}

// Recent orders
$stmt = $conn->prepare("
    SELECT o.*, u.name as buyer_name, COUNT(oi.id) as item_count
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    JOIN users u ON o.user_id = u.id
    WHERE p.seller_id = ? 
    GROUP BY o.id
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$recent_orders = $stmt->get_result();

include '../includes/header.php';
?>
<?php include '../includes/seller_sidebar.php'; ?>
<div class="seller-layout">
    <main class="seller-main">
        <div class="container">
        <div class="admin-header">
            <h1><i class="fas fa-store"></i> Seller Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars(getCurrentUserName()); ?>!</p>
        </div>
        
        <?php if (!$seller['approved']): ?>
            <div class="flash-message info">
                <div class="container">
                    <strong>Application Status:</strong> Your seller application is pending approval. You'll be able to add products once approved.
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_products; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $approved_products; ?></h3>
                    <p>Approved Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $pending_products; ?></h3>
                    <p>Pending Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_orders; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
        </div>
        
        
        <!-- Recent Products -->
        <div class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-box"></i> Recent Products</h2>
                <a href="products.php" class="btn btn-outline">View All</a>
            </div>
            
            <?php if (!empty($filtered_products)): ?>
                <div class="product-grid">
                    <?php foreach ($filtered_products as $product): ?>
                        <div class="product-card">
                            <img src="../uploads/products/<?= htmlspecialchars($product['main_image']); ?>" alt="<?= htmlspecialchars($product['title']); ?>">
                            <h3><?= htmlspecialchars($product['title']); ?></h3>
                            <p class="price">$<?= number_format($product['price'], 2); ?></p>
                            <p class="category"><?= htmlspecialchars($product['category_name']); ?></p>
                            <p class="stock <?= ($product['stock'] ?? 0) <= 5 ? 'low-stock' : ''; ?>">Stock: <?= $product['stock'] ?? 0; ?> units</p>
                            <div class="product-status">
                                <span class="badge badge-<?= $product['status'] === 'approved' ? 'success' : ($product['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?= ucfirst($product['status']); ?>
                                </span>
                            </div>
                            <a href="edit_product.php?id=<?= $product['id']; ?>" class="btn btn-secondary">Edit</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center">No products yet. <?php if ($seller['approved']): ?><a href="add_product.php">Add your first product</a><?php endif; ?></p>
            <?php endif; ?>
        </div>
        
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
        </div>
    </main>
</div>

<style>
.dashboard-header {
    text-align: center;
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    color: #2c3e50;
}

.stat-content p {
    margin: 0;
    color: #666;
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

.product-status {
    margin: 0.5rem 1rem;
}

.stock {
    color: #666;
    font-size: 0.9rem;
    margin: 0.5rem 0;
}

.stock.low-stock {
    color: #e74c3c;
    font-weight: bold;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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
</style>

<?php include '../includes/admin_footer.php'; ?>
