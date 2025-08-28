<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is admin
requireAdmin();

$page_title = "Product Management";
$base_url = '../';
$error_message = '';
$success_message = '';

// Handle GET parameters for messages after redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Handle product status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = (int)$_POST['product_id'];
    $action = $_POST['action'];
    
    // Redirect after POST to prevent resubmission
    $redirect_url = $_SERVER['REQUEST_URI'];
    
    try {
        if ($action === 'approve_product') {
            $stmt = $conn->prepare("UPDATE products SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $success_message = "Product approved successfully.";
            header("Location: $redirect_url?success=" . urlencode($success_message));
            exit;
        } elseif ($action === 'reject_product') {
            $stmt = $conn->prepare("UPDATE products SET status = 'rejected', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $success_message = "Product rejected successfully.";
            header("Location: $redirect_url?success=" . urlencode($success_message));
            exit;
        } elseif ($action === 'delete_product') {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $success_message = "Product deleted successfully.";
            header("Location: $redirect_url?success=" . urlencode($success_message));
            exit;
        }
    } catch (Exception $e) {
        $error_message = "Error updating product: " . $e->getMessage();
    }
}

// Fetch all products with seller and category information
try {
    $sql = "SELECT p.*, c.name as category_name, s.shop_name, u.name as seller_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            JOIN sellers s ON p.seller_id = s.user_id
            JOIN users u ON s.user_id = u.id
            ORDER BY p.created_at DESC";
    
    $result = $conn->query($sql);
    $products = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error fetching products: " . $e->getMessage();
}

include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
    <div class="container">
        <div class="admin-header">
            <h1>Product Management</h1>
            <p>Review and manage all products in the marketplace.</p>
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
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($products, function($p) { return $p['status'] === 'pending'; })); ?></h3>
                    <p>Pending Review</p>
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
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($products, function($p) { return $p['status'] === 'rejected'; })); ?></h3>
                    <p>Rejected</p>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <div class="table-section">
                <div class="table-header">
                    <h3>Product Management</h3>
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
                            <th>Seller</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="10" class="text-center">No products found.</td>
                            </tr>
                        <?php else: ?>
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
                                    <td>
                                        <strong><?= htmlspecialchars($product['seller_name']); ?></strong><br>
                                        <small><?= htmlspecialchars($product['shop_name']); ?></small>
                                    </td>
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
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            
                                            <?php if ($product['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;" class="action-form">
                                                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                                    <input type="hidden" name="action" value="approve_product">
                                                    <button type="submit" class="btn btn-sm btn-success action-btn" 
                                                            data-action="approve" 
                                                            data-type="product" 
                                                            data-name="<?= htmlspecialchars($product['title']); ?>" 
                                                            data-seller="<?= htmlspecialchars($product['seller_name']); ?>">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" class="action-form">
                                                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                                    <input type="hidden" name="action" value="reject_product">
                                                    <button type="submit" class="btn btn-sm btn-danger action-btn" 
                                                            data-action="reject" 
                                                            data-type="product" 
                                                            data-name="<?= htmlspecialchars($product['title']); ?>" 
                                                            data-seller="<?= htmlspecialchars($product['seller_name']); ?>">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;" class="action-form">
                                                <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                                <input type="hidden" name="action" value="delete_product">
                                                <button type="submit" class="btn btn-sm btn-danger action-btn" 
                                                        data-action="delete" 
                                                        data-type="product" 
                                                        data-name="<?= htmlspecialchars($product['title']); ?>" 
                                                        data-seller="<?= htmlspecialchars($product['seller_name']); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <div class="admin-actions">
            <a href="index.php" class="btn btn-outline">Back to Dashboard</a>
        </div>
    </div>
    </main>
</div>

<script>
// Enhanced button functionality with confirmation dialogs
document.addEventListener('DOMContentLoaded', function() {
    // Override any CSS that might prevent button clicks
    const style = document.createElement('style');
    style.textContent = `
        .action-buttons .btn {
            pointer-events: auto !important;
            z-index: 1000 !important;
            cursor: pointer !important;
            position: relative !important;
        }
        .action-form {
            pointer-events: auto !important;
        }
    `;
    document.head.appendChild(style);
    
    // Remove any overlays that might block clicks
    const overlays = document.querySelectorAll('.overlay, .modal-backdrop');
    overlays.forEach(overlay => {
        if (overlay.style.display !== 'none') {
            overlay.remove();
        }
    });
    
    // Add click handlers to action buttons
    const actionButtons = document.querySelectorAll('.action-btn');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const action = this.dataset.action;
            const type = this.dataset.type;
            const name = this.dataset.name;
            const seller = this.dataset.seller;
            
            let message = '';
            let title = '';
            
            if (action === 'approve' && type === 'product') {
                title = 'Approve Product';
                message = `Are you sure you want to approve this product?\n\nProduct: ${name}\nSeller: ${seller}\n\nThis will make the product visible to buyers on the marketplace.`;
            } else if (action === 'reject' && type === 'product') {
                title = 'Reject Product';
                message = `Are you sure you want to reject this product?\n\nProduct: ${name}\nSeller: ${seller}\n\nThis will prevent the product from being sold. This action can be reversed later.`;
            } else if (action === 'delete' && type === 'product') {
                title = 'Delete Product';
                message = `Are you sure you want to DELETE this product?\n\nProduct: ${name}\nSeller: ${seller}\n\nWARNING: This will permanently remove the product from the marketplace. This action cannot be undone!`;
            }
            
            if (confirm(message)) {
                this.closest('form').submit();
            }
        });
    });
});
</script>

<?php include '../includes/admin_footer.php'; ?>
