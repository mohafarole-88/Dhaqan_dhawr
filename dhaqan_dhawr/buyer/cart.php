<?php
// Include initialization file (handles config, session, DB, and auth)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Shopping Cart';
$base_url = '../';

// Require buyer role
requireBuyer();

$user_id = getCurrentUserId();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($action === 'add') {
        // Add to cart
        $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, qty) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE qty = qty + ?");
        $stmt->bind_param("iiii", $user_id, $product_id, $quantity, $quantity);
        $stmt->execute();
        
        setFlashMessage('Product added to cart!', 'success');
        header('Location: cart.php');
        exit();
    } elseif ($action === 'update') {
        // Update quantity
        if ($quantity > 0) {
            $stmt = $conn->prepare("UPDATE cart_items SET qty = ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
            $stmt->execute();
        } else {
            // Remove if quantity is 0
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        }
        
        setFlashMessage('Cart updated!', 'success');
        header('Location: cart.php');
        exit();
    } elseif ($action === 'remove') {
        // Remove from cart
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        
        setFlashMessage('Product removed from cart!', 'success');
        header('Location: cart.php');
        exit();
    }
}

// Get cart items
$stmt = $conn->prepare("
    SELECT ci.*, p.title, p.price, p.main_image, p.stock, s.shop_name
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    JOIN sellers s ON p.seller_id = s.id
    WHERE ci.user_id = ? AND p.status = 'approved'
    ORDER BY ci.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

$total_items = 0;
$total_amount = 0;
$cart_data = [];

while ($item = $cart_items->fetch_assoc()) {
    $cart_data[] = $item;
    $total_items += $item['qty'];
    $total_amount += $item['price'] * $item['qty'];
}

include '../includes/buyer_header.php';
?>

<main>
    <div class="container">
        <h1>Shopping Cart</h1>
        
        <?php if (empty($cart_data)): ?>
            <div class="empty-cart">
                <div class="text-center">
                    <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any products to your cart yet.</p>
                    <a href="../index.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach ($cart_data as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <img src="../uploads/products/<?= htmlspecialchars($item['main_image']); ?>" alt="<?= htmlspecialchars($item['title']); ?>">
                            </div>
                            
                            <div class="item-details">
                                <h3><?= htmlspecialchars($item['title']); ?></h3>
                                <p class="seller">by <?= htmlspecialchars($item['shop_name']); ?></p>
                                <p class="price">$<?= number_format($item['price'], 2); ?></p>
                                
                                <div class="quantity-controls">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?= $item['product_id']; ?>">
                                        <button type="button" class="qty-btn" onclick="updateQuantity(<?= $item['product_id']; ?>, -1)">-</button>
                                        <input type="number" name="quantity" value="<?= $item['qty']; ?>" min="1" max="<?= $item['stock']; ?>" class="qty-input" onchange="this.form.submit()">
                                        <button type="button" class="qty-btn" onclick="updateQuantity(<?= $item['product_id']; ?>, 1)">+</button>
                                    </form>
                                </div>
                                
                                <p class="stock-info"><?= $item['stock']; ?> available</p>
                            </div>
                            
                            <div class="item-total">
                                <p class="total-price">$<?= number_format($item['price'] * $item['qty'], 2); ?></p>
                                
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= $item['product_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item from cart?')">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    
                    <div class="summary-item">
                        <span>Items (<?= $total_items; ?>):</span>
                        <span>$<?= number_format($total_amount, 2); ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span>Shipping:</span>
                        <span>Free</span>
                    </div>
                    
                    <div class="summary-item total">
                        <span>Total:</span>
                        <span>$<?= number_format($total_amount, 2); ?></span>
                    </div>
                    
                    <div class="cart-actions">
                        <a href="../index.php" class="btn btn-outline w-100 mb-2">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                        
                        <a href="checkout.php" class="btn btn-primary w-100">
                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                        </a>
                    </div>
                    
                    <div class="payment-info">
                        <h4>Payment Methods</h4>
                        <ul>
                            <li><i class="fas fa-university"></i> Bank Transfer</li>
                            <li><i class="fas fa-money-bill-wave"></i> Cash on Delivery (Somalia only)</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.cart-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.cart-items {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.cart-item {
    display: grid;
    grid-template-columns: 120px 1fr auto;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
    align-items: center;
}

.cart-item:last-child {
    border-bottom: none;
}

.item-image img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 5px;
}

.item-details h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.seller {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.price {
    font-weight: bold;
    color: #e74c3c;
    margin-bottom: 0.5rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.qty-btn {
    width: 30px;
    height: 30px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 3px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover {
    background: #f8f9fa;
}

.qty-input {
    width: 50px;
    height: 30px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.stock-info {
    font-size: 0.8rem;
    color: #666;
}

.item-total {
    text-align: right;
}

.total-price {
    font-size: 1.2rem;
    font-weight: bold;
    color: #e74c3c;
    margin-bottom: 0.5rem;
}

.cart-summary {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: fit-content;
    position: sticky;
    top: 2rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
}

.summary-item.total {
    font-size: 1.2rem;
    font-weight: bold;
    color: #e74c3c;
    border-bottom: none;
}

.cart-actions {
    margin: 2rem 0;
}

.payment-info {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.payment-info h4 {
    margin-bottom: 1rem;
}

.payment-info ul {
    list-style: none;
    padding: 0;
}

.payment-info li {
    margin-bottom: 0.5rem;
    color: #666;
}

.payment-info i {
    margin-right: 0.5rem;
    color: #e74c3c;
}

.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .cart-container {
        grid-template-columns: 1fr;
    }
    
    .cart-item {
        grid-template-columns: 80px 1fr;
        gap: 1rem;
    }
    
    .item-total {
        grid-column: 1 / -1;
        text-align: left;
        margin-top: 1rem;
    }
    
    .cart-summary {
        position: static;
    }
}
</style>

<script>
function updateQuantity(productId, change) {
    const input = document.querySelector(`input[name="quantity"][data-product="${productId}"]`);
    if (input) {
        const currentValue = parseInt(input.value) || 0;
        const newValue = Math.max(1, currentValue + change);
        input.value = newValue;
        input.form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
