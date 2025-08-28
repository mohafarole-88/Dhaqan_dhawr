<?php
require_once __DIR__ . '/includes/init.php';

$page_title = 'Dhaqan Dhowr - Somali Cultural Marketplace';
$base_url = './';

// Fetch featured cultural products
$featured_products = [];
$categories = [];

try {
    // Get featured products
    $stmt = $conn->prepare("SELECT p.*, s.shop_name, c.name as category_name 
                           FROM products p 
                           JOIN sellers s ON p.seller_id = s.id 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE p.status = 'approved' 
                           ORDER BY p.created_at DESC LIMIT 8");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $featured_products[] = $row;
    }
    
    // Get cultural categories
    $stmt = $conn->prepare("SELECT c.*, COUNT(p.id) as product_count 
                           FROM categories c 
                           LEFT JOIN products p ON c.id = p.category_id AND p.status = 'approved'
                           GROUP BY c.id 
                           ORDER BY product_count DESC LIMIT 6");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
} catch (Exception $e) {
    error_log("Homepage query failed: " . $e->getMessage());
}

include 'includes/buyer_header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-overlay">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Welcome to Dhaqan Dhowr</h1>
                <p class="hero-subtitle">Somali Cultural Marketplace - Preserving Heritage Through Commerce</p>
                <p class="hero-description">Discover authentic Somali traditional tools, cultural items, and heritage products from verified sellers across the community.</p>
                <div class="hero-actions">
                    <a href="#categories" class="btn btn-primary btn-lg">Explore Cultural Items</a>
                    <a href="about.php" class="btn btn-outline btn-lg">Learn About Our Heritage</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cultural Categories Section -->
<section id="categories" class="categories-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Traditional Cultural Categories</h2>
            <p>Explore authentic Somali cultural tools and items organized by traditional usage</p>
        </div>
        
        <div class="categories-grid">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-<?= getCategoryIcon($category['name']); ?>"></i>
                        </div>
                        <h3><?= htmlspecialchars($category['name']); ?></h3>
                        <p><?= $category['product_count']; ?> items available</p>
                        <a href="category.php?id=<?= $category['id']; ?>" class="btn btn-outline">Browse Items</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>Cultural categories are being organized. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="featured-products">
    <div class="container">
        <div class="section-header">
            <h2>Featured Cultural Items</h2>
            <p>Recently added authentic traditional tools and cultural products</p>
        </div>
        
        <?php if (!empty($featured_products)): ?>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['main_image'])): ?>
                                <img src="uploads/products/<?= htmlspecialchars($product['main_image']); ?>" alt="<?= htmlspecialchars($product['title']); ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-category"><?= htmlspecialchars($product['category_name']); ?></div>
                            <h3 class="product-title"><?= htmlspecialchars($product['title']); ?></h3>
                            <p class="product-description"><?= htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                            <div class="product-meta">
                                <span class="product-price">$<?= number_format($product['price'], 2); ?></span>
                                <span class="product-seller">by <?= htmlspecialchars($product['shop_name']); ?></span>
                            </div>
                            <a href="product.php?id=<?= $product['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="search.php" class="btn btn-outline btn-lg">View All Cultural Items</a>
            </div>
        <?php else: ?>
            <div class="no-products text-center">
                <i class="fas fa-box-open fa-3x mb-3"></i>
                <h3>Cultural Items Coming Soon</h3>
                <p>Our community of sellers is preparing authentic traditional tools and cultural items.</p>
                <?php if (isLoggedIn() && getCurrentUser()['role'] === 'seller'): ?>
                    <a href="seller/add_product.php" class="btn btn-primary">Add Your Cultural Items</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Cultural Heritage Section -->
<section class="heritage-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2>Preserving Somali Heritage</h2>
                <p>Dhaqan Dhowr is more than a marketplace - it's a digital preservation of Somali cultural heritage. Each item tells a story of our ancestors' wisdom and craftsmanship.</p>
                <ul class="heritage-features">
                    <li><i class="fas fa-check-circle"></i> Authentic traditional tools verified by cultural experts</li>
                    <li><i class="fas fa-check-circle"></i> Detailed cultural context and usage history</li>
                    <li><i class="fas fa-check-circle"></i> Direct connection with skilled artisans and sellers</li>
                    <li><i class="fas fa-check-circle"></i> Supporting local Somali communities worldwide</li>
                </ul>
                <a href="about.php" class="btn btn-primary">Learn More About Our Mission</a>
            </div>
            <div class="col-md-6">
                <div class="heritage-image">
                    <i class="fas fa-star fa-5x"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
// Helper function for category icons
function getCategoryIcon($categoryName) {
    $icons = [
        'Food Tools' => 'utensils',
        'Clothing' => 'tshirt', 
        'Cleaning Tools' => 'broom',
        'Animal Watering Tools' => 'tint',
        'Cultural Foods' => 'seedling',
        'Jewelry' => 'gem',
        'Pottery' => 'vase',
        'Textiles' => 'cut',
        'Musical Instruments' => 'music',
        'Art & Crafts' => 'palette'
    ];
    return $icons[$categoryName] ?? 'box';
}

include 'includes/footer.php'; 
?>
