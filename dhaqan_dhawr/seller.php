<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

// Require authentication to view seller profiles
requireAuth();

$page_title = 'Seller Profile';
$base_url = '';

// Get seller ID from URL
$seller_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($seller_id <= 0) {
    setFlashMessage('Invalid seller ID.', 'error');
    header('Location: index.php');
    exit();
}

// Initialize variables
$seller = null;
$products = [];
$error_message = '';

try {
    // Fetch seller information
    $seller_sql = "SELECT s.*, u.name as user_name, u.email, u.created_at as user_created_at
                   FROM sellers s
                   JOIN users u ON s.user_id = u.id
                   WHERE s.id = ? AND s.approved = 1";
    $seller_stmt = $conn->prepare($seller_sql);
    $seller_stmt->bind_param("i", $seller_id);
    $seller_stmt->execute();
    $seller_result = $seller_stmt->get_result();
    $seller = $seller_result->fetch_assoc();

    if (!$seller) {
        setFlashMessage('Seller not found or not approved.', 'error');
        header('Location: index.php');
        exit();
    }

    $page_title = $seller['shop_name'] . ' - Seller Profile';

    // Fetch seller's products
    $products_sql = "SELECT p.*, c.name as category_name
                     FROM products p
                     JOIN categories c ON p.category_id = c.id
                     WHERE p.seller_id = ? AND p.status = 'approved' AND p.stock > 0
                     ORDER BY p.created_at DESC";
    $products_stmt = $conn->prepare($products_sql);
    $products_stmt->bind_param("i", $seller_id);
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();

    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }

} catch (Exception $e) {
    error_log("Seller profile error: " . $e->getMessage());
    $error_message = "We're experiencing some technical difficulties. Please try again later.";
}

include 'includes/header.php';
?>

<main>
    <div class="container">
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($seller): ?>
            <!-- Seller Profile Header -->
            <div class="seller-header">
                <div class="seller-info">
                    <h1><?= htmlspecialchars($seller['shop_name']); ?></h1>
                    <p class="seller-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($seller['location']); ?>
                    </p>
                    <p class="seller-member-since">
                        Member since <?= date('F Y', strtotime($seller['user_created_at'])); ?>
                    </p>
                </div>
                
                <div class="seller-actions">
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $seller['user_id']): ?>
                        <a href="messages.php?start_conversation=<?= $seller['user_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-envelope"></i> Contact Seller
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Seller Bio -->
            <?php if (!empty($seller['bio'])): ?>
                <div class="seller-bio">
                    <h3>About This Seller</h3>
                    <p><?= nl2br(htmlspecialchars($seller['bio'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Seller's Products -->
            <div class="seller-products">
                <h2>Products by <?= htmlspecialchars($seller['shop_name']); ?></h2>
                
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <p>This seller doesn't have any products available at the moment.</p>
                        <a href="search.php" class="btn btn-primary">Browse All Products</a>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <?php
                                $image_path = "uploads/products/" . htmlspecialchars($product['main_image']);
                                $image_exists = file_exists($image_path) && !empty($product['main_image']);
                                ?>
                                <div class="product-image">
                                    <?php if ($image_exists): ?>
                                        <img src="<?= $image_path; ?>" alt="<?= htmlspecialchars($product['title']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><?= htmlspecialchars($product['title']); ?></h3>
                                    <p class="price">$<?= number_format($product['price'], 2); ?></p>
                                    <p class="category"><?= htmlspecialchars($product['category_name']); ?></p>
                                    <p class="stock"><?= $product['stock']; ?> in stock</p>
                                    <a href="product.php?id=<?= $product['id']; ?>" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <h2>Seller Not Found</h2>
                <p>The seller you're looking for doesn't exist or is not approved.</p>
                <a href="index.php" class="btn btn-primary">Go to Homepage</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.seller-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.seller-info h1 {
    margin: 0 0 1rem 0;
    color: #333;
}

.seller-location {
    color: #666;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.seller-member-since {
    color: #999;
    margin: 0;
    font-size: 0.9rem;
}

.seller-actions {
    display: flex;
    gap: 1rem;
}

.seller-bio {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.seller-bio h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.seller-bio p {
    margin: 0;
    line-height: 1.6;
    color: #666;
}

.seller-products {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
}

.seller-products h2 {
    margin: 0 0 1.5rem 0;
    color: #333;
}

@media (max-width: 768px) {
    .seller-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .seller-actions {
        justify-content: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>


