<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'My Orders';
$base_url = '../';

// Require authentication
requireAuth();

// Require buyer role
requireBuyer();

$user_id = getCurrentUserId();
$orders = [];
$error_message = '';

try {
    // Fetch user's orders
    $orders_sql = "SELECT o.*, 
                   COUNT(oi.id) as item_count
                   FROM orders o
                   LEFT JOIN order_items oi ON o.id = oi.order_id
                   WHERE o.user_id = ?
                   GROUP BY o.id
                   ORDER BY o.created_at DESC";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("i", $user_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    
    while ($order = $orders_result->fetch_assoc()) {
        // Fetch order items for each order
        $items_sql = "SELECT oi.*, p.title, p.main_image, s.shop_name
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      JOIN sellers s ON p.seller_id = s.id
                      WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $order['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $order['items'] = [];
        while ($item = $items_result->fetch_assoc()) {
            $order['items'][] = $item;
        }
        
        $orders[] = $order;
    }
} catch (Exception $e) {
    error_log("Orders fetch error: " . $e->getMessage());
    $error_message = "We're experiencing some technical difficulties. Please try again later.";
}

include '../includes/buyer_header.php';
?>

<main>
    <div class="container">
        <div class="orders-container">
            <h1>My Orders</h1>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <h2>No orders yet</h2>
                    <p>Start shopping to see your orders here.</p>
                    <a href="../index.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3>Order #<?= $order['id']; ?></h3>
                                    <p class="order-date"><?= date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                    <p class="order-total">Total: $<?= number_format($order['total_amount'], 2); ?></p>
                                </div>
                                <div class="order-status">
                                    <span class="status-badge status-<?= $order['status']; ?>">
                                        <?= ucfirst(htmlspecialchars($order['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
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
                                        <div class="item-details">
                                            <h4><?= htmlspecialchars($item['title']); ?></h4>
                                            <p class="seller">by <?= htmlspecialchars($item['shop_name']); ?></p>
                                            <p class="quantity">Qty: <?= $item['quantity']; ?></p>
                                            <p class="price">$<?= number_format($item['price'], 2); ?> each</p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="order-details">
                                <div class="detail-row">
                                    <strong>Shipping Address:</strong>
                                    <span><?= nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                                </div>
                                <div class="detail-row">
                                    <strong>Payment Method:</strong>
                                    <span><?= ucwords(str_replace('_', ' ', htmlspecialchars($order['payment_method']))); ?></span>
                                </div>
                                <?php if (!empty($order['notes'])): ?>
                                    <div class="detail-row">
                                        <strong>Notes:</strong>
                                        <span><?= htmlspecialchars($order['notes']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="order-actions">
                                <a href="order_details.php?id=<?= $order['id']; ?>" class="btn btn-primary">View Details</a>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <a href="cancel_order.php?id=<?= $order['id']; ?>" class="btn btn-outline" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</a>
                                <?php elseif ($order['status'] === 'delivered' || $order['status'] === 'completed'): ?>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <?php
                                        // Check if user has already reviewed this product for this order
                                        $review_check_sql = "SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?";
                                        $review_check_stmt = $conn->prepare($review_check_sql);
                                        $review_check_stmt->bind_param("iii", $user_id, $item['product_id'], $order['id']);
                                        $review_check_stmt->execute();
                                        $review_check_result = $review_check_stmt->get_result();
                                        $has_reviewed = $review_check_result->num_rows > 0;
                                        ?>
                                        <?php if (!$has_reviewed): ?>
                                            <a href="review_product.php?product_id=<?= $item['product_id']; ?>&order_id=<?= $order['id']; ?>" class="btn btn-success">
                                                <i class="fas fa-star"></i> Review <?= htmlspecialchars($item['title']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="reviewed-badge">
                                                <i class="fas fa-check"></i> Reviewed
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.orders-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem 0;
}

.orders-list {
    margin-top: 2rem;
}

.order-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f8f9fa;
}

.order-info h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.order-date {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9rem;
}

.order-total {
    margin: 0;
    font-weight: bold;
    color: #333;
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

.reviewed-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #d4edda;
    color: #155724;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.order-items {
    margin-bottom: 1.5rem;
}

.order-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 80px;
    height: 80px;
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
    font-size: 0.8rem;
    color: #6c757d;
    border-radius: 4px;
}

.item-details h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
}

.item-details p {
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
}

.item-details .seller {
    color: #666;
}

.item-details .quantity {
    color: #666;
}

.item-details .price {
    font-weight: bold;
    color: #333;
}

.order-details {
    background: #f8f9fa;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-row strong {
    color: #333;
    min-width: 120px;
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
    
    .order-actions {
        flex-direction: column;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .detail-row strong {
        min-width: auto;
    }
}
</style>

<?php include '../includes/footer.php'; ?>


