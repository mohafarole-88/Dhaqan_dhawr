<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Manage Reviews';
$base_url = '../';

// Require authentication and admin role
requireAuth();
requireAdmin();

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $review_id = (int)($_POST['review_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if ($review_id && in_array($action, ['flag', 'remove', 'activate'])) {
        try {
            $admin_id = getCurrentUserId();
            
            switch ($action) {
                case 'flag':
                    $stmt = $conn->prepare("UPDATE reviews SET status = 'flagged' WHERE id = ?");
                    $stmt->bind_param("i", $review_id);
                    $stmt->execute();
                    $success_message = "Review flagged successfully.";
                    break;
                    
                case 'remove':
                    $stmt = $conn->prepare("UPDATE reviews SET status = 'removed' WHERE id = ?");
                    $stmt->bind_param("i", $review_id);
                    $stmt->execute();
                    $success_message = "Review removed successfully.";
                    break;
                    
                case 'activate':
                    $stmt = $conn->prepare("UPDATE reviews SET status = 'active' WHERE id = ?");
                    $stmt->bind_param("i", $review_id);
                    $stmt->execute();
                    $success_message = "Review activated successfully.";
                    break;
            }
            
            // Log the moderation action
            $stmt = $conn->prepare("INSERT INTO moderation_logs (admin_id, action, target_type, target_id, notes) VALUES (?, ?, 'review', ?, ?)");
            $stmt->bind_param("isis", $admin_id, $action, $review_id, $notes);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Review moderation error: " . $e->getMessage());
            $error_message = "Failed to " . $action . " review. Please try again.";
        }
    }
}

// Fetch reviews with filtering
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    // Build the WHERE clause based on status filter
    $where_clause = "";
    $params = [];
    $types = "";
    
    if ($status_filter !== 'all') {
        $where_clause = "WHERE r.status = ?";
        $params[] = $status_filter;
        $types = "s";
    }
    
    // Count total reviews
    $count_sql = "SELECT COUNT(*) as total FROM reviews r " . $where_clause;
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
    } else {
        $count_stmt = $conn->query($count_sql);
    }
    $total_reviews = $count_stmt->fetch_assoc()['total'];
    $total_pages = ceil($total_reviews / $per_page);
    
    // Fetch reviews
    $reviews_sql = "SELECT r.*, p.title as product_title, u.name as reviewer_name, s.shop_name
                    FROM reviews r
                    JOIN products p ON r.product_id = p.id
                    JOIN users u ON r.user_id = u.id
                    JOIN sellers s ON p.seller_id = s.id
                    " . $where_clause . "
                    ORDER BY r.created_at DESC
                    LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $reviews_stmt = $conn->prepare($reviews_sql);
    $reviews_stmt->bind_param($types, ...$params);
    $reviews_stmt->execute();
    $reviews = $reviews_stmt->get_result();
    
} catch (Exception $e) {
    error_log("Reviews fetch error: " . $e->getMessage());
    $error_message = "Failed to load reviews.";
}

include '../includes/header.php';
?>

<main>
    <div class="container">
        <div class="admin-main">
            <div class="admin-header">
                <h1>Manage Reviews</h1>
                <p>Monitor and moderate customer reviews</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" action="" class="filters-form">
                    <div class="filter-group">
                        <label for="status">Filter by Status:</label>
                        <select name="status" id="status" onchange="this.form.submit()">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : ''; ?>>All Reviews</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="flagged" <?= $status_filter === 'flagged' ? 'selected' : ''; ?>>Flagged</option>
                            <option value="removed" <?= $status_filter === 'removed' ? 'selected' : ''; ?>>Removed</option>
                        </select>
                    </div>
                </form>
                
                <div class="stats-summary">
                    <span class="stat-item">
                        <strong>Total:</strong> <?= number_format($total_reviews); ?>
                    </span>
                    <span class="stat-item">
                        <strong>Active:</strong> <?= number_format($reviews->num_rows); ?>
                    </span>
                </div>
            </div>
            
            <!-- Reviews Table -->
            <div class="admin-content">
                <?php if ($reviews->num_rows > 0): ?>
                    <div class="admin-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reviewer</th>
                                    <th>Product</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($review = $reviews->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($review['reviewer_name']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="product-info">
                                                <strong><?= htmlspecialchars($review['product_title']); ?></strong>
                                                <small>by <?= htmlspecialchars($review['shop_name']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="rating-display">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?= $i <= $review['rating'] ? 'filled' : ''; ?>">
                                                        <i class="fas fa-star"></i>
                                                    </span>
                                                <?php endfor; ?>
                                                <span class="rating-number"><?= $review['rating']; ?>/5</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="comment-preview">
                                                <?= htmlspecialchars(substr($review['comment'], 0, 100)); ?>
                                                <?= strlen($review['comment']) > 100 ? '...' : ''; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $review['status']; ?>">
                                                <?= ucfirst(htmlspecialchars($review['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($review['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($review['status'] === 'active'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="review_id" value="<?= $review['id']; ?>">
                                                        <input type="hidden" name="action" value="flag">
                                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Flag this review?')">
                                                            <i class="fas fa-flag"></i> Flag
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($review['status'] === 'flagged'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="review_id" value="<?= $review['id']; ?>">
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Activate
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if (in_array($review['status'], ['active', 'flagged'])): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="review_id" value="<?= $review['id']; ?>">
                                                        <input type="hidden" name="action" value="remove">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this review? This action cannot be undone.')">
                                                            <i class="fas fa-trash"></i> Remove
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1; ?>&status=<?= $status_filter; ?>" class="btn btn-outline">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <span class="page-info">
                                Page <?= $page; ?> of <?= $total_pages; ?>
                            </span>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1; ?>&status=<?= $status_filter; ?>" class="btn btn-outline">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <h2>No reviews found</h2>
                        <p>No reviews match the current filter criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
.admin-main {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 0;
}

.admin-header {
    text-align: center;
    margin-bottom: 2rem;
}

.admin-header h1 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.admin-header p {
    margin: 0;
    color: #666;
    font-size: 1.1rem;
}

.filters-section {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filters-form .filter-group {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filters-form label {
    font-weight: 500;
    color: #333;
}

.filters-form select {
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 1rem;
}

.stats-summary {
    display: flex;
    gap: 2rem;
}

.stat-item {
    color: #666;
}

.stat-item strong {
    color: #333;
}

.admin-table {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.admin-table table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #f8f9fa;
}

.admin-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.product-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.product-info small {
    color: #666;
    font-size: 0.9rem;
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rating-display .star {
    font-size: 0.9rem;
    color: #ddd;
}

.rating-display .star.filled {
    color: #ffc107;
}

.rating-number {
    font-weight: 500;
    color: #333;
}

.comment-preview {
    max-width: 300px;
    color: #555;
    line-height: 1.4;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-flagged {
    background: #fff3cd;
    color: #856404;
}

.status-removed {
    background: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
}

.page-info {
    color: #666;
    font-weight: 500;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.empty-state h2 {
    margin: 0 0 1rem 0;
    color: #333;
}

.empty-state p {
    margin: 0;
    color: #666;
}

@media (max-width: 768px) {
    .filters-section {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .stats-summary {
        justify-content: center;
    }
    
    .admin-table {
        overflow-x: auto;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
