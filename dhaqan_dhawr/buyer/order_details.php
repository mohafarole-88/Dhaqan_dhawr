<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Order Details';
$base_url = '../';

// Require authentication
requireAuth();

// Require buyer role
requireBuyer();

$user_id = getCurrentUserId();
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null;
$error_message = '';

if ($order_id <= 0) {
    $error_message = 'Invalid order ID.';
} else {
    try {
        // Fetch order details
        $order_sql = "SELECT o.* FROM orders o WHERE o.id = ? AND o.user_id = ?";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("ii", $order_id, $user_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        
        if ($order_result->num_rows === 0) {
            $error_message = 'Order not found or access denied.';
        } else {
            $order = $order_result->fetch_assoc();
            
            // Fetch order items
            $items_sql = "SELECT oi.*, p.title, p.main_image, p.description, s.shop_name, s.location
                          FROM order_items oi
                          JOIN products p ON oi.product_id = p.id
                          JOIN sellers s ON p.seller_id = s.id
                          WHERE oi.order_id = ?";
            $items_stmt = $conn->prepare($items_sql);
            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            $order['items'] = [];
            while ($item = $items_result->fetch_assoc()) {
                $order['items'][] = $item;
            }
        }
    } catch (Exception $e) {
        error_log("Order details fetch error: " . $e->getMessage());
        $error_message = "Unable to load order details. Please try again later.";
    }
}

include '../includes/buyer_header.php';
?>

<main>
    <div class="container">
        <div class="order-details-container">
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message); ?>
                </div>
                <div class="back-link">
                    <a href="orders.php" class="btn btn-outline">← Back to Orders</a>
                </div>
            <?php elseif ($order): ?>
                <div class="order-header">
                    <div class="header-content">
                        <h1>Order #<?= $order['id']; ?></h1>
                        <div class="order-meta">
                            <span class="order-date"><?= date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
                            <span class="status-badge status-<?= $order['status']; ?>">
                                <?= ucfirst(htmlspecialchars($order['status'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="back-link">
                        <a href="orders.php" class="btn btn-outline">← Back to Orders</a>
                    </div>
                </div>

                <div class="order-content">
                    <!-- Order Items -->
                    <div class="section">
                        <h2>Order Items</h2>
                        <div class="items-list">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="item-card">
                                    <div class="item-image">
                                        <?php
                                        $image_path = "../uploads/products/" . htmlspecialchars($item['main_image']);
                                        $image_exists = file_exists($image_path) && !empty($item['main_image']);
                                        ?>
                                        <?php if ($image_exists): ?>
                                            <img src="<?= $image_path; ?>" alt="<?= htmlspecialchars($item['title']); ?>">
                                        <?php else: ?>
                                            <div class="no-image">No Image</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-info">
                                        <h3><?= htmlspecialchars($item['title']); ?></h3>
                                        <p class="seller">by <?= htmlspecialchars($item['shop_name']); ?></p>
                                        <?php if (!empty($item['location'])): ?>
                                            <p class="location">📍 <?= htmlspecialchars($item['location']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($item['description'])): ?>
                                            <p class="description"><?= htmlspecialchars(substr($item['description'], 0, 150)); ?>...</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-pricing">
                                        <div class="quantity">Qty: <?= $item['quantity']; ?></div>
                                        <div class="unit-price">$<?= number_format($item['price'], 2); ?> each</div>
                                        <div class="total-price">$<?= number_format($item['price'] * $item['quantity'], 2); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="section">
                        <h2>Order Summary</h2>
                        <div class="summary-card">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span>$<?= number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span>$<?= number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping & Payment Info -->
                    <div class="section">
                        <h2>Shipping & Payment Information</h2>
                        <div class="info-grid">
                            <div class="info-card">
                                <h3>Shipping Address</h3>
                                <p><?= nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            </div>
                            <div class="info-card">
                                <h3>Payment Method</h3>
                                <p><?= ucwords(str_replace('_', ' ', htmlspecialchars($order['payment_method']))); ?></p>
                            </div>
                        </div>
                        <?php if (!empty($order['notes'])): ?>
                            <div class="info-card">
                                <h3>Order Notes</h3>
                                <p><?= htmlspecialchars($order['notes']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Order Actions -->
                    <div class="section">
                        <div class="order-actions">
                            <?php if ($order['status'] === 'pending'): ?>
                                <a href="cancel_order.php?id=<?= $order['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</a>
                            <?php endif; ?>
                            <a href="orders.php" class="btn btn-primary">Back to Orders</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.order-details-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem 0;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #dee2e6;
}

.header-content h1 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.order-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.order-date {
    color: #666;
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
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

.section {
    margin-bottom: 2rem;
}

.section h2 {
    margin-bottom: 1rem;
    color: #333;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.5rem;
}

.items-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.item-card {
    display: flex;
    gap: 1rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
}

.item-image {
    width: 120px;
    height: 120px;
    flex-shrink: 0;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
}

.no-image {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: #6c757d;
    border-radius: 4px;
}

.item-info {
    flex: 1;
}

.item-info h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.item-info p {
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
}

.seller {
    color: #666;
    font-weight: 500;
}

.location {
    color: #666;
}

.description {
    color: #666;
    line-height: 1.4;
}

.item-pricing {
    text-align: right;
    min-width: 120px;
}

.quantity {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.unit-price {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.total-price {
    font-weight: bold;
    font-size: 1.1rem;
    color: #333;
}

.summary-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.summary-row.total {
    border-top: 1px solid #dee2e6;
    padding-top: 0.5rem;
    margin-top: 1rem;
    font-weight: bold;
    font-size: 1.1rem;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
}

.info-card h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1rem;
}

.info-card p {
    margin: 0;
    color: #666;
    line-height: 1.4;
}

.order-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .order-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .item-card {
        flex-direction: column;
    }
    
    .item-pricing {
        text-align: left;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .order-actions {
        flex-direction: column;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
