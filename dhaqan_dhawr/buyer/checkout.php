<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Checkout';
$base_url = '../';

// Require authentication
requireAuth();

// Require buyer role
requireBuyer();

$user_id = getCurrentUserId();
$errors = [];
$success_message = '';

// Get cart items
try {
    $cart_sql = "SELECT ci.*, p.title, p.price, p.main_image, p.stock, s.shop_name
                 FROM cart_items ci
                 JOIN products p ON ci.product_id = p.id
                 JOIN sellers s ON p.seller_id = s.id
                 WHERE ci.user_id = ? AND p.status = 'approved'";
    $cart_stmt = $conn->prepare($cart_sql);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    $cart_items = [];
    $total = 0;
    
    while ($item = $cart_result->fetch_assoc()) {
        $cart_items[] = $item;
        $total += $item['price'] * $item['qty'];
    }
} catch (Exception $e) {
    error_log("Cart fetch error: " . $e->getMessage());
    setFlashMessage('Error loading cart items.', 'error');
    header('Location: cart.php');
    exit();
}

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart_items)) {
    $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');
    $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Validation
    if (empty($shipping_address)) {
        $errors[] = 'Shipping address is required';
    }
    
    if (empty($payment_method)) {
        $errors[] = 'Payment method is required';
    }
    
    // Check stock availability
    foreach ($cart_items as $item) {
        if ($item['qty'] > $item['stock']) {
            $errors[] = "Insufficient stock for {$item['title']}. Available: {$item['stock']}";
        }
    }
    
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Create order
            $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, notes, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $order_stmt->bind_param("idsss", $user_id, $total, $shipping_address, $payment_method, $notes);
            $order_stmt->execute();
            $order_id = $conn->insert_id;
            
            // Create order items
            foreach ($cart_items as $item) {
                $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $order_item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['qty'], $item['price']);
                $order_item_stmt->execute();
                
                // Update product stock
                $new_stock = $item['stock'] - $item['qty'];
                $update_stock_stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $update_stock_stmt->bind_param("ii", $new_stock, $item['product_id']);
                $update_stock_stmt->execute();
            }
            
            // Clear cart
            $clear_cart_stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $clear_cart_stmt->bind_param("i", $user_id);
            $clear_cart_stmt->execute();
            
            $conn->commit();
            
            setFlashMessage('Order placed successfully! Your order number is #' . $order_id, 'success');
            header('Location: orders.php');
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Checkout error: " . $e->getMessage());
            $errors[] = 'An error occurred while processing your order. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<main>
    <div class="container">
        <div class="checkout-container">
            <h1>Checkout</h1>
            
            <?php if (empty($cart_items)): ?>
                <div class="empty-state">
                    <h2>Your cart is empty</h2>
                    <p>Add some products to your cart before checkout.</p>
                    <a href="../index.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="checkout-layout">
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <h3>Order Summary</h3>
                        <div class="cart-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
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
                                        <p class="quantity">Qty: <?= $item['qty']; ?></p>
                                        <p class="price">$<?= number_format($item['price'] * $item['qty'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-total">
                            <h4>Total: $<?= number_format($total, 2); ?></h4>
                        </div>
                    </div>

                    <!-- Checkout Form -->
                    <div class="checkout-form">
                        <h3>Shipping & Payment Information</h3>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="shipping_address">Shipping Address *</label>
                                <textarea id="shipping_address" name="shipping_address" rows="3" required><?= htmlspecialchars($_POST['shipping_address'] ?? ''); ?></textarea>
                                <small>Please provide your complete shipping address</small>
                            </div>

                            <div class="form-group">
                                <label for="payment_method">Payment Method *</label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="">Select payment method</option>
                                    <option value="cash_on_delivery" <?= ($_POST['payment_method'] ?? '') === 'cash_on_delivery' ? 'selected' : ''; ?>>Cash on Delivery</option>
                                    <option value="bank_transfer" <?= ($_POST['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="mobile_money" <?= ($_POST['payment_method'] ?? '') === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="notes">Order Notes</label>
                                <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                                <small>Any special instructions for your order</small>
                            </div>

                            <div class="checkout-actions">
                                <a href="cart.php" class="btn btn-outline">Back to Cart</a>
                                <button type="submit" class="btn btn-primary">Place Order</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.checkout-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.checkout-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.order-summary {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
}

.order-summary h3 {
    margin-bottom: 1rem;
    color: #333;
}

.cart-items {
    margin-bottom: 1.5rem;
}

.cart-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.cart-item:last-child {
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

.order-total {
    border-top: 2px solid #dee2e6;
    padding-top: 1rem;
    text-align: right;
}

.order-total h4 {
    margin: 0;
    font-size: 1.2rem;
    color: #333;
}

.checkout-form {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
}

.checkout-form h3 {
    margin-bottom: 1.5rem;
    color: #333;
}

.checkout-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .checkout-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .checkout-actions {
        flex-direction: column;
    }
}
</style>

<?php include '../includes/footer.php'; ?>


