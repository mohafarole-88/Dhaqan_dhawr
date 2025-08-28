<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is seller
requireSeller();

$page_title = "My Products";
$base_url = '../'; // Fix CSS path issue
$error_message = '';
$success_message = '';

// Handle product operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = (int)$_POST['product_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'delete_product') {
            // Get seller ID first for proper verification
            $user_id = getCurrentUserId();
            $stmt = $conn->prepare("SELECT id FROM sellers WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $seller_result = $stmt->get_result();
            $seller = $seller_result->fetch_assoc();
            
            if ($seller) {
                // Verify the product belongs to this seller
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
                $stmt->bind_param("ii", $product_id, $seller['id']);
                $stmt->execute();
            
                if ($stmt->affected_rows > 0) {
                    $success_message = "Product deleted successfully.";
                } else {
                    $error_message = "Product not found or you don't have permission to delete it.";
                }
            } else {
                $error_message = "Seller profile not found.";
            }
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get seller ID first
$user_id = getCurrentUserId();
$stmt = $conn->prepare("SELECT id FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$seller_result = $stmt->get_result();
$seller = $seller_result->fetch_assoc();

if (!$seller) {
    setFlashMessage('Seller profile not found.', 'error');
    header('Location: apply.php');
    exit();
}

$seller_id = $seller['id'];

// Fetch seller's products
try {
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE p.seller_id = ? 
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error fetching products: " . $e->getMessage();
}

include '../includes/header.php';
?>

<?php include '../includes/seller_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
        <div class="container">
            <div class="admin-header">
                <h1><i class="fas fa-box"></i> My Products</h1>
                <p>Manage your product listings.</p>
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

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($products); ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($products, function($p) { return $p['status'] === 'approved'; })); ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($products, function($p) { return $p['status'] === 'pending'; })); ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= array_sum(array_column($products, 'stock')); ?></h3>
                        <p>Total Stock</p>
                    </div>
                </div>
            </div>

            <div class="admin-content">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <h3>No Products Yet</h3>
                        <p>You haven't added any products to your shop yet.</p>
                        <a href="add_product.php" class="btn btn-primary">Add Your First Product</a>
                    </div>
                <?php else: ?>
                    <div class="table-section">
                        <div class="table-header">
                            <h3>My Products</h3>
                            <a href="add_product.php" class="view-all-btn">Add Product</a>
                        </div>
                        <div class="table-container">
                            <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= $product['id']; ?></td>
                                        <td>
                                            <?php if (!empty($product['main_image'])): ?>
                                                <img src="../uploads/products/<?= htmlspecialchars($product['main_image']); ?>" 
                                                     alt="<?= htmlspecialchars($product['title']); ?>" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #6c757d;">
                                                    No Image
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($product['title']); ?></strong><br>
                                            <small><?= htmlspecialchars(substr($product['description'], 0, 100)); ?>...</small>
                                        </td>
                                        <td><?= htmlspecialchars($product['category_name']); ?></td>
                                        <td>$<?= number_format($product['price'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?= $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                                                <?= $product['stock']; ?> in stock
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $product['status'] === 'approved' ? 'success' : ($product['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                <?= ucfirst($product['status']); ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($product['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../product.php?id=<?= $product['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                    View
                                                </a>
                                                <a href="edit_product.php?id=<?= $product['id']; ?>" class="btn btn-sm btn-secondary">
                                                    Edit
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="admin-actions">
                <a href="add_product.php" class="btn btn-primary">Add New Product</a>
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/admin_footer.php'; ?>
