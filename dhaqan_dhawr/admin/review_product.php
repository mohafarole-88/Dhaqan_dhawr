<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Review Product';
$base_url = '../';

// Require authentication and admin role
requireAuth();
requireAdmin();

$product_id = $_GET['id'] ?? null;
$error_message = '';
$success_message = '';

if (!$product_id) {
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if (in_array($action, ['approve', 'reject'])) {
        try {
            // Update product status
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $product_id);
            $stmt->execute();
            
            // Log the moderation action
            $admin_id = getCurrentUserId();
            $stmt = $conn->prepare("INSERT INTO moderation_logs (admin_id, action, target_type, target_id, notes) VALUES (?, ?, 'product', ?, ?)");
            $stmt->bind_param("isis", $admin_id, $action, $product_id, $notes);
            $stmt->execute();
            
            $success_message = "Product " . ucfirst($action) . "d successfully.";
            
            // Redirect after a short delay
            header("Refresh: 2; URL=index.php");
            
        } catch (Exception $e) {
            error_log("Product moderation error: " . $e->getMessage());
            $error_message = "Failed to " . $action . " product. Please try again.";
        }
    }
}

// Fetch product details
try {
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name, s.shop_name, u.name as seller_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN sellers s ON p.seller_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        header('Location: index.php');
        exit();
    }
    
    // Fetch product images
    $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $images = $stmt->get_result();
    
} catch (Exception $e) {
    error_log("Product fetch error: " . $e->getMessage());
    $error_message = "Failed to load product details.";
}

include '../includes/header.php';
?>

<main>
    <div class="container">
        <div class="review-container">
            <div class="review-header">
                <h1>Review Product</h1>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message); ?>
                    <p>Redirecting to dashboard...</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($product) && !$success_message): ?>
                <div class="product-review-card">
                    <div class="product-images">
                        <div class="main-image">
                            <?php
                            $image_path = "../uploads/products/" . htmlspecialchars($product['main_image']);
                            $image_exists = file_exists($image_path) && !empty($product['main_image']);
                            ?>
                            <?php if ($image_exists): ?>
                                <img src="<?= $image_path; ?>" alt="<?= htmlspecialchars($product['title']); ?>">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($images->num_rows > 0): ?>
                            <div class="additional-images">
                                <?php while ($image = $images->fetch_assoc()): ?>
                                    <div class="image-thumbnail">
                                        <img src="../uploads/products/<?= htmlspecialchars($image['file_path']); ?>" 
                                             alt="Additional image">
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-details">
                        <h2><?= htmlspecialchars($product['title']); ?></h2>
                        
                        <div class="product-meta">
                            <div class="meta-item">
                                <strong>Seller:</strong>
                                <span><?= htmlspecialchars($product['shop_name']); ?> (<?= htmlspecialchars($product['seller_name']); ?>)</span>
                            </div>
                            <div class="meta-item">
                                <strong>Category:</strong>
                                <span><?= htmlspecialchars($product['category_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <strong>Price:</strong>
                                <span>$<?= number_format($product['price'], 2); ?></span>
                            </div>
                            <div class="meta-item">
                                <strong>Stock:</strong>
                                <span><?= $product['stock']; ?> units</span>
                            </div>
                            <div class="meta-item">
                                <strong>Status:</strong>
                                <span class="status-badge status-<?= $product['status']; ?>">
                                    <?= ucfirst(htmlspecialchars($product['status'])); ?>
                                </span>
                            </div>
                            <div class="meta-item">
                                <strong>Submitted:</strong>
                                <span><?= date('F j, Y g:i A', strtotime($product['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="product-description">
                            <h3>Description</h3>
                            <p><?= nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                        
                        <?php if (!empty($product['cultural_notes'])): ?>
                            <div class="cultural-notes">
                                <h3>Cultural Notes</h3>
                                <p><?= nl2br(htmlspecialchars($product['cultural_notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($product['status'] === 'pending'): ?>
                    <div class="review-actions">
                        <h3>Review Decision</h3>
                        
                        <form method="POST" action="" class="review-form">
                            <div class="form-group">
                                <label for="notes">Review Notes (Optional)</label>
                                <textarea id="notes" name="notes" rows="4" placeholder="Add any notes about your decision..."></textarea>
                            </div>
                            
                            <div class="action-buttons">
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-large">
                                    <i class="fas fa-check"></i> Approve Product
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-large">
                                    <i class="fas fa-times"></i> Reject Product
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="review-status">
                        <h3>Review Status</h3>
                        <p>This product has already been <?= $product['status']; ?>.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.review-container {
    max-width: 1200px;
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
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

.product-images {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.main-image {
    width: 100%;
    max-width: 400px;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
}

.main-image img {
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
    font-size: 1.1rem;
}

.additional-images {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.image-thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 4px;
    overflow: hidden;
    border: 2px solid #dee2e6;
}

.image-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-details h2 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1.8rem;
}

.product-meta {
    margin-bottom: 1.5rem;
}

.meta-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.meta-item strong {
    color: #333;
    min-width: 120px;
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

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.product-description,
.cultural-notes {
    margin-bottom: 1.5rem;
}

.product-description h3,
.cultural-notes h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1.1rem;
}

.product-description p,
.cultural-notes p {
    margin: 0;
    line-height: 1.6;
    color: #555;
}

.review-actions {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 2rem;
}

.review-actions h3 {
    margin: 0 0 1.5rem 0;
    color: #333;
}

.review-form .form-group {
    margin-bottom: 1.5rem;
}

.review-form label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.review-form textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-family: inherit;
    resize: vertical;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.review-status {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
}

.review-status h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.review-status p {
    margin: 0;
    color: #666;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .product-review-card {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .review-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .meta-item {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .meta-item strong {
        min-width: auto;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
