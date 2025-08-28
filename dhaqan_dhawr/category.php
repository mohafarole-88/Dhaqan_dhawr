<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

// Allow both authenticated and guest users to browse categories

$page_title = 'Browse Categories';
$base_url = '';

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$categories = [];
$products = [];
$current_category = null;
$error_message = '';

try {
    // Check if database connection exists
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection not available");
    }

    // Fetch all categories (adjust query based on actual table structure)
    $cat_sql = "SELECT id, name, parent_id FROM categories ORDER BY name ASC";
    $cat_result = $conn->query($cat_sql);

    if ($cat_result === false) {
        throw new Exception("Error fetching categories: " . $conn->error);
    }

    if ($cat_result && $cat_result->num_rows > 0) {
        while ($row = $cat_result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    // If specific category is selected, fetch its products
    if ($category_id > 0) {
        // Get category details
        $cat_detail_sql = "SELECT id, name, parent_id FROM categories WHERE id = ?";
        $cat_detail_stmt = $conn->prepare($cat_detail_sql);
        $cat_detail_stmt->bind_param("i", $category_id);
        $cat_detail_stmt->execute();
        $cat_detail_result = $cat_detail_stmt->get_result();
        $current_category = $cat_detail_result->fetch_assoc();

        if ($current_category) {
            $page_title = $current_category['name'] . ' - Browse Products';
            
            // Fetch products for this category
            $prod_sql = "SELECT p.id, p.title, p.price, p.main_image, p.stock, p.description, s.shop_name
                         FROM products p
                         JOIN sellers s ON p.seller_id = s.id
                         WHERE p.category_id = ? AND p.status = 'approved' AND p.stock > 0
                         ORDER BY p.created_at DESC";
            $prod_stmt = $conn->prepare($prod_sql);
            $prod_stmt->bind_param("i", $category_id);
            $prod_stmt->execute();
            $prod_result = $prod_stmt->get_result();

            if ($prod_result && $prod_result->num_rows > 0) {
                while ($row = $prod_result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
        }
    }

} catch (Exception $e) {
    error_log("Category page error: " . $e->getMessage());
    if (DEBUG_MODE) {
        $error_message = "Debug: " . $e->getMessage();
    } else {
        $error_message = "We're experiencing some technical difficulties. Please try again later.";
    }
}

include 'includes/buyer_header.php';
?>

<main class="category-main">
    <div class="container">
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($current_category): ?>
            <!-- Category Detail View -->
            <div class="category-header">
                <h1><?= htmlspecialchars($current_category['name']); ?></h1>
                <nav class="breadcrumb">
                    <a href="index.php">Home</a> &gt;
                    <a href="category.php">Categories</a> &gt;
                    <span><?= htmlspecialchars($current_category['name']); ?></span>
                </nav>
                
            </div>

            <div class="products-section">
                <h2>Products in <?= htmlspecialchars($current_category['name']); ?></h2>
                
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <p>No products available in this category at the moment.</p>
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
                                    <p class="seller">by <?= htmlspecialchars($product['shop_name']); ?></p>
                                    <p class="stock"><?= $product['stock']; ?> in stock</p>
                                    <a href="product.php?id=<?= $product['id']; ?>" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Categories List View -->
            <div class="categories-header">
                <h1>Browse Categories</h1>
                <p>Explore our collection of authentic Somali cultural treasures by category</p>
                
            </div>

            <div class="categories-grid">
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <p>No categories available at the moment.</p>
                        <a href="search.php" class="btn btn-primary">Browse All Products</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-content">
                                <h3><?= htmlspecialchars($category['name']); ?></h3>
                                <a href="category.php?id=<?= $category['id']; ?>" class="btn btn-primary">Browse Products</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>


<?php include 'includes/footer.php'; ?>



