<?php
require_once __DIR__ . '/includes/init.php';

$page_title = 'Products - Dhaqan Dhowr';
$base_url = './';

// Get filter parameters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = ["p.status = 'approved'"];
$params = [];
$param_types = '';

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
    $param_types .= 'i';
}

if (!empty($search_query)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$order_by = match($sort_by) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.name ASC',
    'oldest' => 'p.created_at ASC',
    default => 'p.created_at DESC'
};

// Get products
$products = [];
$total_products = 0;

try {
    // Count total products
    $count_sql = "SELECT COUNT(*) as total 
                  FROM products p 
                  JOIN sellers s ON p.seller_id = s.id 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE $where_clause";
    
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $total_products = $count_stmt->get_result()->fetch_assoc()['total'];

    // Get products for current page with main image
    $sql = "SELECT p.*, s.shop_name, c.name as category_name,
                   (SELECT pi.file_path FROM product_images pi 
                    WHERE pi.product_id = p.id 
                    ORDER BY pi.id LIMIT 1) as main_image
            FROM products p 
            JOIN sellers s ON p.seller_id = s.id 
            JOIN categories c ON p.category_id = c.id 
            WHERE $where_clause 
            ORDER BY $order_by 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $all_params = array_merge($params, [$per_page, $offset]);
    $all_param_types = $param_types . 'ii';
    $stmt->bind_param($all_param_types, ...$all_params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    // Get categories for filter
    $categories_stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
    $categories_stmt->execute();
    $categories = $categories_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Products page error: " . $e->getMessage());
}

$total_pages = ceil($total_products / $per_page);

include 'includes/buyer_header.php';
?>

<main class="category-main">
    <div class="container">
        <div class="categories-header">
            <h1>Cultural Products</h1>
            <p>Discover authentic Somali traditional tools and cultural items</p>
        </div>

        <!-- Filters and Search -->
        <div class="products-filters">
            <div class="filters-row">
                <!-- Category Filter -->
                <div class="filter-group">
                    <label for="category-filter">Category:</label>
                    <select id="category-filter" onchange="updateFilters()">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $category_id == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sort Filter -->
                <div class="filter-group">
                    <label for="sort-filter">Sort by:</label>
                    <select id="sort-filter" onchange="updateFilters()">
                        <option value="newest" <?= $sort_by == 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sort_by == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Name A-Z</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="filter-group search-group">
                    <div class="navbar-search">
                        <form action="products.php" method="GET" class="search-form">
                            <input type="hidden" name="category" value="<?= $category_id ?>">
                            <input type="hidden" name="sort" value="<?= $sort_by ?>">
                            <input type="text" name="q" placeholder="Search cultural items..." value="<?= htmlspecialchars($search_query ?? '') ?>" class="search-input">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Results Info -->
            <div class="results-info">
                <span class="results-count">
                    Showing <?= count($products) ?> of <?= $total_products ?> products
                </span>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="products-grid">
            <?php if (empty($products)): ?>
                <div class="no-products">
                    <div class="no-products-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3>No products found</h3>
                    <p>Try adjusting your search criteria or browse all categories.</p>
                    <a href="products.php" class="btn btn-primary">View All Products</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php
                            // Use image from database if available
                            if (!empty($product['main_image'])) {
                                $image_path = 'uploads/products/' . $product['main_image'];
                                // Check if the database image file exists
                                if (!file_exists($image_path)) {
                                    $image_path = 'assets/images/placeholder-product.svg';
                                }
                            } else {
                                // Fallback to file system search for backward compatibility
                                $image_extensions = ['png', 'jpg', 'jpeg'];
                                $image_path = null;
                                
                                foreach ($image_extensions as $ext) {
                                    $test_path = 'uploads/products/' . $product['id'] . '_main.' . $ext;
                                    if (file_exists($test_path)) {
                                        $image_path = $test_path;
                                        break;
                                    }
                                }
                                
                                // If no main image found, try numbered images
                                if (!$image_path) {
                                    foreach ($image_extensions as $ext) {
                                        $test_path = 'uploads/products/' . $product['id'] . '_0.' . $ext;
                                        if (file_exists($test_path)) {
                                            $image_path = $test_path;
                                            break;
                                        }
                                    }
                                }
                                
                                // Final fallback to placeholder
                                if (!$image_path) {
                                    $image_path = 'assets/images/placeholder-product.svg';
                                }
                            }
                            ?>
                            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['name'] ?? '') ?>" loading="lazy" onerror="this.src='assets/images/placeholder-product.svg'">
                            <div class="product-overlay">
                                <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-category">
                                <?= htmlspecialchars($product['category_name'] ?? '') ?>
                            </div>
                            <h3 class="product-title">
                                <a href="product.php?id=<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['name'] ?? '') ?>
                                </a>
                            </h3>
                            <p class="product-description">
                                <?= htmlspecialchars(substr($product['description'] ?? '', 0, 100)) ?>...
                            </p>
                            <div class="product-meta">
                                <div class="product-price">
                                    $<?= number_format($product['price'], 2) ?>
                                </div>
                                <div class="product-seller">
                                    by <?= htmlspecialchars($product['shop_name'] ?? '') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                       class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function updateFilters() {
    const category = document.getElementById('category-filter').value;
    const sort = document.getElementById('sort-filter').value;
    const currentUrl = new URL(window.location);
    
    currentUrl.searchParams.set('category', category);
    currentUrl.searchParams.set('sort', sort);
    currentUrl.searchParams.delete('page'); // Reset to first page
    
    window.location.href = currentUrl.toString();
}
</script>

<style>
.products-page {
    min-height: 70vh;
}


.products-filters {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
}

.filters-row {
    display: flex;
    gap: 2rem;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.filter-group select {
    padding: 0.75rem 1rem;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.2s ease;
}

.filter-group select:focus {
    outline: none;
    border-color: #1976d2;
}

.search-group {
    flex: 1;
    max-width: 300px;
}

.results-info {
    color: #666;
    font-size: 0.9rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.product-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.product-info {
    padding: 1.5rem;
}

.product-category {
    color: #1976d2;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}

.product-title {
    margin: 0 0 0.75rem 0;
    font-size: 1.1rem;
}

.product-title a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.2s ease;
}

.product-title a:hover {
    color: #1976d2;
}

.product-description {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1976d2;
}

.product-seller {
    font-size: 0.8rem;
    color: #666;
}

.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
}

.no-products-icon {
    font-size: 4rem;
    color: #ccc;
    margin-bottom: 1rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination-btn {
    padding: 0.75rem 1rem;
    background: white;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    color: #2c3e50;
    text-decoration: none;
    transition: all 0.2s ease;
}

.pagination-btn:hover,
.pagination-btn.active {
    background: #1976d2;
    color: white;
    border-color: #1976d2;
}

@media (max-width: 768px) {
    .filters-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-group {
        max-width: none;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
