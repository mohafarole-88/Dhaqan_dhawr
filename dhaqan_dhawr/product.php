<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

// Allow both authenticated and guest users to view product details

$page_title = 'Product Details';
$base_url = '';

$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    setFlashMessage('Product not found.', 'error');
    header('Location: index.php');
    exit();
}

// Get product details
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, s.shop_name, s.bio as seller_bio, u.name as seller_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN sellers s ON p.seller_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE p.id = ? AND p.status = 'approved'
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('Product not found or not available.', 'error');
    header('Location: index.php');
    exit();
}

$product = $result->fetch_assoc();

// Get additional images
$stmt = $conn->prepare("SELECT file_path FROM product_images WHERE product_id = ? ORDER BY id");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$additional_images = $stmt->get_result();

// Get related products
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, s.shop_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN sellers s ON p.seller_id = s.id
    WHERE p.status = 'approved' 
    AND p.category_id = ? 
    AND p.id != ?
    ORDER BY p.created_at DESC
    LIMIT 4
");
$stmt->bind_param("ii", $product['category_id'], $product_id);
$stmt->execute();
$related_products = $stmt->get_result();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        setFlashMessage('Please log in to add items to cart.', 'error');
        header('Location: Auth/login.php');
        exit();
    }
    
    if (!isBuyer()) {
        setFlashMessage('Only buyers can add items to cart.', 'error');
        header('Location: product.php?id=' . $product_id);
        exit();
    }
    
    $quantity = (int)($_POST['quantity'] ?? 1);
    $user_id = getCurrentUserId();
    
    if ($quantity > 0 && $quantity <= $product['stock']) {
        $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, qty) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE qty = qty + ?");
        $stmt->bind_param("iiii", $user_id, $product_id, $quantity, $quantity);
        
        if ($stmt->execute()) {
            setFlashMessage('Product added to cart successfully!', 'success');
            header('Location: buyer/cart.php');
            exit();
        } else {
            setFlashMessage('Failed to add product to cart.', 'error');
        }
    } else {
        setFlashMessage('Invalid quantity.', 'error');
    }
}

include 'includes/buyer_header.php';
?>

<main class="product-main">
    <div class="container">
        <div class="product-detail">
            <!-- Product Images -->
            <div class="product-images">
                <div class="main-image">
                    <img id="main-image" src="uploads/products/<?= htmlspecialchars($product['main_image']); ?>" alt="<?= htmlspecialchars($product['title']); ?>">
                </div>
                
                <?php if ($additional_images->num_rows > 0): ?>
                    <div class="image-gallery">
                        <div class="thumbnail active" onclick="changeImage('uploads/products/<?= htmlspecialchars($product['main_image']); ?>')">
                            <img src="uploads/products/<?= htmlspecialchars($product['main_image']); ?>" alt="Main">
                        </div>
                        <?php while ($image = $additional_images->fetch_assoc()): ?>
                            <div class="thumbnail" onclick="changeImage('uploads/products/<?= htmlspecialchars($image['file_path']); ?>')">
                                <img src="uploads/products/<?= htmlspecialchars($image['file_path']); ?>" alt="Additional">
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="product-info">
                <nav class="breadcrumb">
                    <a href="index.php">Home</a> &gt;
                    <a href="category.php?id=<?= $product['category_id']; ?>"><?= htmlspecialchars($product['category_name']); ?></a> &gt;
                    <span><?= htmlspecialchars($product['title']); ?></span>
                </nav>
                
                <h1><?= htmlspecialchars($product['title']); ?></h1>
                
                <div class="product-meta">
                    <p class="seller">by <a href="seller.php?id=<?= $product['seller_id']; ?>"><?= htmlspecialchars($product['shop_name']); ?></a></p>
                    <p class="category">Category: <?= htmlspecialchars($product['category_name']); ?></p>
                </div>
                
                <div class="price-section">
                    <h2 class="price">$<?= number_format($product['price'], 2); ?></h2>
                    <p class="stock"><?= $product['stock']; ?> in stock</p>
                </div>
                
                <div class="product-description">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <?php if (!empty($product['cultural_notes'])): ?>
                    <div class="cultural-notes">
                        <h3>Cultural Significance</h3>
                        <p><?= nl2br(htmlspecialchars($product['cultural_notes'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Add to Cart Form -->
                <?php if (isLoggedIn() && isBuyer()): ?>
                    <form method="POST" action="" class="add-to-cart-form">
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn" onclick="updateQuantity(-1)">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $product['stock']; ?>" readonly>
                                <button type="button" class="qty-btn" onclick="updateQuantity(1)">+</button>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-large">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>
                <?php elseif (!isLoggedIn()): ?>
                    <div class="login-prompt">
                        <p>Please <a href="Auth/login.php">log in</a> to add this item to your cart.</p>
                        <a href="Auth/login.php" class="btn btn-primary">Login</a>
                    </div>
                <?php endif; ?>
                
                <!-- Seller Info -->
                <div class="seller-info">
                    <h3>About the Seller</h3>
                    <div class="seller-details">
                        <h4><?= htmlspecialchars($product['shop_name']); ?></h4>
                        <p><?= htmlspecialchars($product['seller_bio']); ?></p>
                        <a href="seller.php?id=<?= $product['seller_id']; ?>" class="btn btn-outline">View Shop</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2>Customer Reviews</h2>
            <?php
            // Fetch reviews for this product
            $reviews_sql = "SELECT r.*, u.name as reviewer_name, o.id as order_id
                           FROM reviews r
                           JOIN users u ON r.user_id = u.id
                           JOIN orders o ON r.order_id = o.id
                           WHERE r.product_id = ? AND r.status = 'active'
                           ORDER BY r.created_at DESC";
            $reviews_stmt = $conn->prepare($reviews_sql);
            $reviews_stmt->bind_param("i", $product_id);
            $reviews_stmt->execute();
            $reviews = $reviews_stmt->get_result();
            
            if ($reviews->num_rows > 0):
                // Calculate average rating
                $total_rating = 0;
                $review_count = 0;
                $reviews_array = [];
                
                while ($review = $reviews->fetch_assoc()) {
                    $total_rating += $review['rating'];
                    $review_count++;
                    $reviews_array[] = $review;
                }
                
                $average_rating = $total_rating / $review_count;
            ?>
                <div class="reviews-summary">
                    <div class="average-rating">
                        <div class="rating-display">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $average_rating ? 'filled' : ''; ?>">
                                    <i class="fas fa-star"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-text">
                            <span class="rating-number"><?= number_format($average_rating, 1); ?></span>
                            <span class="rating-count">(<?= $review_count; ?> reviews)</span>
                        </div>
                    </div>
                </div>
                
                <div class="reviews-list">
                    <?php foreach ($reviews_array as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <h4><?= htmlspecialchars($review['reviewer_name']); ?></h4>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= $review['rating'] ? 'filled' : ''; ?>">
                                                <i class="fas fa-star"></i>
                                            </span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-date">
                                    <?= date('M j, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            <div class="review-content">
                                <p><?= nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-reviews">
                    <p>No reviews yet. Be the first to review this product!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Related Products -->
        <?php if ($related_products->num_rows > 0): ?>
            <div class="related-products">
                <h2>Related Products</h2>
                <div class="product-grid">
                    <?php while ($related = $related_products->fetch_assoc()): ?>
                        <div class="product-card">
                            <img src="uploads/products/<?= htmlspecialchars($related['main_image']); ?>" alt="<?= htmlspecialchars($related['title']); ?>">
                            <h3><?= htmlspecialchars($related['title']); ?></h3>
                            <p class="price">$<?= number_format($related['price'], 2); ?></p>
                            <p class="category"><?= htmlspecialchars($related['category_name']); ?></p>
                            <p class="seller">by <?= htmlspecialchars($related['shop_name']); ?></p>
                            <a href="product.php?id=<?= $related['id']; ?>" class="btn">View Details</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>


<script>
function changeImage(src) {
    document.getElementById('main-image').src = src;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    event.target.parentElement.classList.add('active');
}

function updateQuantity(change) {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value) || 1;
    const newValue = Math.max(1, Math.min(currentValue + change, <?= $product['stock']; ?>));
    input.value = newValue;
}
</script>

<?php include 'includes/footer.php'; ?>
