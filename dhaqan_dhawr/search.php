<?php
// Include initialization file (handles config, session, DB, and auth)
require_once __DIR__ . '/includes/init.php';

// Allow both authenticated and guest users to search

$page_title = 'Search Products';
$base_url = '';

// Get search parameters
$search_query = sanitizeInput($_GET['q'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 0);
$sort = sanitizeInput($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

// Get categories for filter
$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->get_result();

// Build search query
$where_conditions = ["p.status = 'approved'"];
$params = [];
$param_types = "";

if (!empty($search_query)) {
    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ? OR p.cultural_notes LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
    $param_types .= "i";
}

if ($min_price > 0) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
    $param_types .= "d";
}

if ($max_price > 0) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
    $param_types .= "d";
}

$where_clause = implode(" AND ", $where_conditions);

// Build sort clause
$sort_clause = match($sort) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'oldest' => 'p.created_at ASC',
    default => 'p.created_at DESC'
};

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $total_products = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $result = $conn->query($count_sql);
    $total_products = $result->fetch_assoc()['total'];
}
$total_pages = ceil($total_products / $per_page);
$offset = ($page - 1) * $per_page;

// Get products
$sql = "
    SELECT p.*, c.name as category_name, s.shop_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN sellers s ON p.seller_id = s.id
    WHERE $where_clause
    ORDER BY $sort_clause
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $params[] = $per_page;
    $params[] = $offset;
    $param_types .= "ii";
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$products = $stmt->get_result();

include 'includes/buyer_header.php';
?>

<main class="search-main">
    <div class="container">
        <div class="search-header">
            <h1>Search Products</h1>
            <?php if (!empty($search_query)): ?>
                <p>Search results for: "<strong><?= htmlspecialchars($search_query); ?></strong>"</p>
            <?php endif; ?>
        </div>
        
        <div class="search-container">
            <!-- Filters Sidebar -->
            <div class="filters-sidebar">
                <h3>Filters</h3>
                
                <form method="GET" action="" class="filters-form">
                    <div class="form-group">
                        <label for="q">Search</label>
                        <input type="text" id="q" name="q" value="<?= htmlspecialchars($search_query); ?>" placeholder="Search products..." class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <option value="<?= $category['id']; ?>" <?= $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="min_price">Min Price ($)</label>
                        <input type="number" id="min_price" name="min_price" value="<?= $min_price; ?>" min="0" step="0.01" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_price">Max Price ($)</label>
                        <input type="number" id="max_price" name="max_price" value="<?= $max_price; ?>" min="0" step="0.01" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort" class="form-control">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <a href="search.php" class="btn btn-outline w-100">Clear All</a>
                    </div>
                </form>
            </div>
            
            <!-- Search Results -->
            <div class="search-results">
                <div class="results-header">
                    <h2>Products (<?= number_format($total_products); ?> results)</h2>
                    
                    <?php if ($total_products > 0): ?>
                        <div class="results-info">
                            Showing <?= number_format($offset + 1); ?> - <?= number_format(min($offset + $per_page, $total_products)); ?> of <?= number_format($total_products); ?> products
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($products->num_rows > 0): ?>
                    <div class="product-grid">
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <div class="product-card">
                                <img src="uploads/products/<?= htmlspecialchars($product['main_image']); ?>" alt="<?= htmlspecialchars($product['title']); ?>">
                                <div class="card-body">
                                    <h3><?= htmlspecialchars($product['title']); ?></h3>
                                    <p class="price">$<?= number_format($product['price'], 2); ?></p>
                                    <p class="category"><?= htmlspecialchars($product['category_name']); ?></p>
                                    <p class="seller">by <?= htmlspecialchars($product['shop_name']); ?></p>
                                    
                                    <div class="product-actions">
                                        <a href="product.php?id=<?= $product['id']; ?>" class="btn btn-primary">View Details</a>
                                        <?php if (isLoggedIn() && isBuyer()): ?>
                                            <form method="POST" action="buyer/cart.php" style="display: inline;">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-outline">Add to Cart</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="active"><?= $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?= $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-results">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No products found</h3>
                            <p>Try adjusting your search criteria or browse our categories.</p>
                            <a href="index.php" class="btn btn-primary">Browse All Products</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
