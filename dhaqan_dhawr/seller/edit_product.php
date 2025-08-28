<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Edit Product';
$base_url = '../';

// Require authentication
requireAuth();

// Require seller role
requireSeller();

$user_id = getCurrentUserId();
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success_message = '';

if ($product_id <= 0) {
    setFlashMessage('Invalid product ID.', 'error');
    header('Location: dashboard.php');
    exit();
}

// Fetch product data
try {
    $product_sql = "SELECT p.*, c.name as category_name 
                   FROM products p
                   JOIN categories c ON p.category_id = c.id
                   WHERE p.id = ? AND p.seller_id = (SELECT id FROM sellers WHERE user_id = ? AND approved = 1)";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param("ii", $product_id, $user_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $product = $product_result->fetch_assoc();

    if (!$product) {
        setFlashMessage('Product not found or you do not have permission to edit it.', 'error');
        header('Location: dashboard.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Product fetch error: " . $e->getMessage());
    setFlashMessage('An error occurred while loading the product.', 'error');
    header('Location: dashboard.php');
    exit();
}

// Fetch categories for dropdown
try {
    $categories_sql = "SELECT id, name FROM categories ORDER BY name ASC";
    $categories_result = $conn->query($categories_sql);
    $categories = [];
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
} catch (Exception $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Product title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Product description is required';
    }
    
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero';
    }
    
    if ($stock < 0) {
        $errors[] = 'Stock cannot be negative';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Please select a category';
    }
    
    // Handle image upload
    $main_image = $product['main_image']; // Keep existing image by default
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleImageUpload($_FILES['main_image']);
        if (isset($upload_result['error'])) {
            $errors[] = $upload_result['error'];
        } else {
            $main_image = $upload_result['filename'];
        }
    }
    
    if (empty($errors)) {
        try {
            $update_sql = "UPDATE products SET title = ?, description = ?, price = ?, stock = ?, category_id = ?, main_image = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssdiisi", $title, $description, $price, $stock, $category_id, $main_image, $product_id);
            
            if ($update_stmt->execute()) {
                $success_message = 'Product updated successfully!';
                // Refresh product data
                $product['title'] = $title;
                $product['description'] = $description;
                $product['price'] = $price;
                $product['stock'] = $stock;
                $product['category_id'] = $category_id;
                $product['main_image'] = $main_image;
            } else {
                $errors[] = 'Failed to update product. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Product update error: " . $e->getMessage());
            $errors[] = 'An error occurred while updating the product.';
        }
    }
}

include '../includes/header.php';
?>

<main>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center">Edit Product</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Product Title *</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($product['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Product Description *</label>
                    <textarea id="description" name="description" rows="5" required><?= htmlspecialchars($product['description']); ?></textarea>
                    <small>Provide detailed information about the product, its cultural significance, and features</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?= $product['price']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock Quantity *</label>
                        <input type="number" id="stock" name="stock" min="0" value="<?= $product['stock']; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id']; ?>" <?= $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="main_image">Product Image</label>
                    <?php if (!empty($product['main_image'])): ?>
                        <div class="current-image">
                            <img src="../uploads/products/<?= htmlspecialchars($product['main_image']); ?>" alt="Current product image" style="max-width: 200px; height: auto;">
                            <p>Current image: <?= htmlspecialchars($product['main_image']); ?></p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="main_image" name="main_image" accept="image/*">
                    <small>Upload a new image to replace the current one (optional)</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.current-image {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
    text-align: center;
}

.current-image img {
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.current-image p {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/footer.php'; ?>


