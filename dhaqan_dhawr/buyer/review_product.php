<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Review Product';
$base_url = '../';

// Require authentication and buyer role
requireAuth();
requireBuyer();

$product_id = $_GET['product_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;
$error_message = '';
$success_message = '';

if (!$product_id || !$order_id) {
    header('Location: orders.php');
    exit();
}

$user_id = getCurrentUserId();

// Verify that the user has purchased this product in this order
try {
    $verify_sql = "SELECT oi.*, o.status as order_status, p.title, p.main_image, s.shop_name
                   FROM order_items oi
                   JOIN orders o ON oi.order_id = o.id
                   JOIN products p ON oi.product_id = p.id
                   JOIN sellers s ON p.seller_id = s.id
                   WHERE oi.order_id = ? AND oi.product_id = ? AND o.user_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("iii", $order_id, $product_id, $user_id);
    $verify_stmt->execute();
    $order_item = $verify_stmt->get_result()->fetch_assoc();
    
    if (!$order_item) {
        header('Location: orders.php');
        exit();
    }
    
    // Check if order status allows reviews
    if (!in_array($order_item['order_status'], ['delivered', 'completed'])) {
        $error_message = "You can only review products from delivered or completed orders.";
    }
    
    // Check if already reviewed
    $review_check_sql = "SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?";
    $review_check_stmt = $conn->prepare($review_check_sql);
    $review_check_stmt->bind_param("iii", $user_id, $product_id, $order_id);
    $review_check_stmt->execute();
    $review_check_result = $review_check_stmt->get_result();
    
    if ($review_check_result->num_rows > 0) {
        $error_message = "You have already reviewed this product for this order.";
    }
    
} catch (Exception $e) {
    error_log("Order verification error: " . $e->getMessage());
    $error_message = "Failed to verify order details.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error_message = "Please select a valid rating (1-5 stars).";
    } elseif (empty($comment)) {
        $error_message = "Please provide a comment with your review.";
    } else {
        try {
            // Insert the review
            $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $product_id, $user_id, $order_id, $rating, $comment);
            $stmt->execute();
            
            $success_message = "Thank you for your review!";
            
            // Redirect after a short delay
            header("Refresh: 2; URL=orders.php");
            
        } catch (Exception $e) {
            error_log("Review submission error: " . $e->getMessage());
            $error_message = "Failed to submit review. Please try again.";
        }
    }
}

include '../includes/header.php';
?>

<main>
    <div class="container">
        <div class="review-container">
            <div class="review-header">
                <h1>Review Product</h1>
                <a href="orders.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message); ?>
                    <p><a href="orders.php">Return to Orders</a></p>
                </div>
            <?php elseif ($success_message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message); ?>
                    <p>Redirecting to orders...</p>
                </div>
            <?php elseif (isset($order_item)): ?>
                <div class="product-review-card">
                    <div class="product-info">
                        <div class="product-image">
                            <?php
                            $image_path = "../uploads/products/" . htmlspecialchars($order_item['main_image']);
                            $image_exists = file_exists($image_path) && !empty($order_item['main_image']);
                            ?>
                            <?php if ($image_exists): ?>
                                <img src="<?= $image_path; ?>" alt="<?= htmlspecialchars($order_item['title']); ?>">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-details">
                            <h2><?= htmlspecialchars($order_item['title']); ?></h2>
                            <p class="seller">by <?= htmlspecialchars($order_item['shop_name']); ?></p>
                            <p class="order-info">Order #<?= $order_id; ?> • <?= date('F j, Y', strtotime($order_item['created_at'] ?? 'now')); ?></p>
                        </div>
                    </div>
                    
                    <form method="POST" action="" class="review-form">
                        <div class="form-group">
                            <label for="rating">Rating *</label>
                            <div class="rating-input">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?= $i; ?>" name="rating" value="<?= $i; ?>" required>
                                    <label for="star<?= $i; ?>" class="star">
                                        <i class="fas fa-star"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-labels">
                                <span>Poor</span>
                                <span>Fair</span>
                                <span>Good</span>
                                <span>Very Good</span>
                                <span>Excellent</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="comment">Your Review *</label>
                            <textarea id="comment" name="comment" rows="5" placeholder="Share your experience with this product..." required></textarea>
                            <small>Tell others what you think about this product. Be honest and helpful!</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-large">
                                <i class="fas fa-paper-plane"></i> Submit Review
                            </button>
                            <a href="orders.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.review-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 0;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.product-review-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 2rem;
}

.product-info {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f8f9fa;
}

.product-image {
    width: 120px;
    height: 120px;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.9rem;
}

.product-details h2 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1.5rem;
}

.product-details .seller {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 1rem;
}

.product-details .order-info {
    margin: 0;
    color: #888;
    font-size: 0.9rem;
}

.review-form .form-group {
    margin-bottom: 2rem;
}

.review-form label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 500;
    color: #333;
    font-size: 1.1rem;
}

.rating-input {
    display: flex;
    flex-direction: row-reverse;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input .star {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s ease;
}

.rating-input .star:hover,
.rating-input .star:hover ~ .star,
.rating-input input[type="radio"]:checked ~ .star {
    color: #ffc107;
}

.rating-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #666;
    margin-top: 0.5rem;
}

.review-form textarea {
    width: 100%;
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-family: inherit;
    resize: vertical;
    font-size: 1rem;
}

.review-form textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.review-form small {
    display: block;
    margin-top: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #f8f9fa;
}

@media (max-width: 768px) {
    .review-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .product-info {
        flex-direction: column;
        text-align: center;
    }
    
    .product-image {
        align-self: center;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .rating-labels {
        font-size: 0.7rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
