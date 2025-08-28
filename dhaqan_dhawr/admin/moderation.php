<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Moderation Panel';
$base_url = '../';

// Require authentication
requireAuth();

// Require admin role
requireAdmin();

$success_message = '';
$error_message = '';

// Handle moderation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $item_id = (int)($_POST['item_id'] ?? 0);
    $item_type = $_POST['item_type'] ?? '';
    
    try {
        if ($action === 'approve_seller' && $item_type === 'seller') {
            $stmt = $conn->prepare("UPDATE sellers SET approved = 1 WHERE id = ?");
            $stmt->bind_param("i", $item_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_message = 'Seller approved successfully!';
                
                // Redirect to prevent form resubmission
                header('Location: moderation.php?success=seller_approved');
                exit();
            } else {
                $error_message = 'Failed to approve seller or seller not found.';
            }
        } elseif ($action === 'reject_seller' && $item_type === 'seller') {
            $stmt = $conn->prepare("DELETE FROM sellers WHERE id = ?");
            $stmt->bind_param("i", $item_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_message = 'Seller rejected and removed successfully!';
                
                // Redirect to prevent form resubmission
                header('Location: moderation.php?success=seller_rejected');
                exit();
            } else {
                $error_message = 'Failed to reject seller or seller not found.';
            }
        } elseif ($action === 'approve_product' && $item_type === 'product') {
            $stmt = $conn->prepare("UPDATE products SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $item_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_message = 'Product approved successfully!';
                
                // Redirect to prevent form resubmission
                header('Location: moderation.php?success=product_approved');
                exit();
            } else {
                $error_message = 'Failed to approve product or product not found.';
            }
        } elseif ($action === 'reject_product' && $item_type === 'product') {
            $stmt = $conn->prepare("UPDATE products SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $item_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_message = 'Product rejected successfully!';
                
                // Redirect to prevent form resubmission
                header('Location: moderation.php?success=product_rejected');
                exit();
            } else {
                $error_message = 'Failed to reject product or product not found.';
            }
        }
    } catch (Exception $e) {
        error_log("Moderation action error: " . $e->getMessage());
        $error_message = 'An error occurred while processing the action: ' . $e->getMessage();
    }
}

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'seller_approved':
            $success_message = 'Seller approved successfully!';
            break;
        case 'seller_rejected':
            $success_message = 'Seller rejected successfully!';
            break;
        case 'product_approved':
            $success_message = 'Product approved successfully!';
            break;
        case 'product_rejected':
            $success_message = 'Product rejected successfully!';
            break;
    }
}

// Fetch pending sellers
try {
    $pending_sellers_sql = "SELECT s.*, u.name as user_name, u.email, u.created_at as user_created_at
                           FROM sellers s
                           JOIN users u ON s.user_id = u.id
                           WHERE s.approved = 0
                           ORDER BY s.created_at ASC";
    $pending_sellers_result = $conn->query($pending_sellers_sql);
    $pending_sellers = [];
    while ($row = $pending_sellers_result->fetch_assoc()) {
        $pending_sellers[] = $row;
    }
} catch (Exception $e) {
    error_log("Pending sellers fetch error: " . $e->getMessage());
    $pending_sellers = [];
}

// Fetch pending products
try {
    $pending_products_sql = "SELECT p.*, c.name as category_name, s.shop_name, u.name as seller_name
                            FROM products p
                            JOIN categories c ON p.category_id = c.id
                            JOIN sellers s ON p.seller_id = s.id
                            JOIN users u ON s.user_id = u.id
                            WHERE p.status = 'pending'
                            ORDER BY p.created_at ASC";
    $pending_products_result = $conn->query($pending_products_sql);
    $pending_products = [];
    while ($row = $pending_products_result->fetch_assoc()) {
        $pending_products[] = $row;
    }
} catch (Exception $e) {
    error_log("Pending products fetch error: " . $e->getMessage());
    $pending_products = [];
}

// Initialize reported items (placeholder for future implementation)
$reported_items = [];

include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
        <div class="container">
            <div class="admin-header">
                <h1><i class="fas fa-shield-alt"></i> Product Moderation</h1>
                <p>Review and moderate pending sellers and products.</p>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count($pending_sellers); ?></h3>
                    <p>Pending Sellers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count($pending_products); ?></h3>
                    <p>Pending Products</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-flag"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count($reported_items); ?></h3>
                    <p>Reported Items</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count($pending_sellers) + count($pending_products) + count($reported_items); ?></h3>
                    <p>Total Pending</p>
                </div>
            </div>
        </div>

        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-tasks"></i> Moderation Queue</h2>
                    <a href="index.php" class="btn btn-outline">Dashboard</a>
                </div>
                
                <div class="admin-content">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending_sellers) && empty($pending_products)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No items pending moderation.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pending_sellers as $seller): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($seller['shop_name']); ?></strong><br>
                                                <small><?= htmlspecialchars($seller['user_name']); ?></small>
                                            </td>
                                            <td><span class="badge badge-info">Seller</span></td>
                                            <td>
                                                <small>Email: <?= htmlspecialchars($seller['email']); ?></small><br>
                                                <small>Location: <?= htmlspecialchars($seller['location']); ?></small>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($seller['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons" style="position: relative; z-index: 100;">
                                                    <form method="POST" style="display: inline-block; margin: 0;">
                                                        <input type="hidden" name="action" value="approve_seller">
                                                        <input type="hidden" name="item_id" value="<?= $seller['id']; ?>">
                                                        <input type="hidden" name="item_type" value="seller">
                                                        <button type="submit" class="btn btn-sm btn-success" style="pointer-events: auto; position: relative; z-index: 101;" onclick="return confirmAction('approve', 'seller', '<?= htmlspecialchars($seller['shop_name']); ?>')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline-block; margin: 0 0 0 5px;">
                                                        <input type="hidden" name="action" value="reject_seller">
                                                        <input type="hidden" name="item_id" value="<?= $seller['id']; ?>">
                                                        <input type="hidden" name="item_type" value="seller">
                                                        <button type="submit" class="btn btn-sm btn-danger" style="pointer-events: auto; position: relative; z-index: 101;" onclick="return confirmAction('reject', 'seller', '<?= htmlspecialchars($seller['shop_name']); ?>')">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php foreach ($pending_products as $product): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($product['title']); ?></strong><br>
                                                <small>by <?= htmlspecialchars($product['seller_name']); ?></small>
                                            </td>
                                            <td><span class="badge badge-warning">Product</span></td>
                                            <td>
                                                <small>Category: <?= htmlspecialchars($product['category_name']); ?></small><br>
                                                <small>Price: $<?= number_format($product['price'], 2); ?></small>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($product['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons" style="position: relative; z-index: 100;">
                                                    <form method="POST" style="display: inline-block; margin: 0;">
                                                        <input type="hidden" name="action" value="approve_product">
                                                        <input type="hidden" name="item_id" value="<?= $product['id']; ?>">
                                                        <input type="hidden" name="item_type" value="product">
                                                        <button type="submit" class="btn btn-sm btn-success" style="pointer-events: auto; position: relative; z-index: 101;" onclick="return confirmAction('approve', 'product', '<?= htmlspecialchars($product['title']); ?>')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline-block; margin: 0 0 0 5px;">
                                                        <input type="hidden" name="action" value="reject_product">
                                                        <input type="hidden" name="item_id" value="<?= $product['id']; ?>">
                                                        <input type="hidden" name="item_type" value="product">
                                                        <button type="submit" class="btn btn-sm btn-danger" style="pointer-events: auto; position: relative; z-index: 101;" onclick="return confirmAction('reject', 'product', '<?= htmlspecialchars($product['title']); ?>')">
                                                            <i class="fas fa-times"></i> Reject
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
function confirmAction(action, type, itemName) {
    let message = '';
    let actionText = action.charAt(0).toUpperCase() + action.slice(1);
    
    if (action === 'approve') {
        if (type === 'seller') {
            message = `Are you sure you want to approve the seller "${itemName}"?\n\nThis will allow them to start selling products on the platform.`;
        } else {
            message = `Are you sure you want to approve the product "${itemName}"?\n\nThis will make it visible to buyers on the marketplace.`;
        }
    } else if (action === 'reject') {
        if (type === 'seller') {
            message = `Are you sure you want to reject and remove the seller "${itemName}"?\n\nThis action cannot be undone and will permanently delete their application.`;
        } else {
            message = `Are you sure you want to reject the product "${itemName}"?\n\nThis will hide it from the marketplace and notify the seller.`;
        }
    }
    
    return confirm(message);
}

// Ensure buttons are clickable
document.addEventListener('DOMContentLoaded', function() {
    // Force override any CSS blocking clicks
    const style = document.createElement('style');
    style.textContent = `
        .action-buttons .btn {
            pointer-events: auto !important;
            position: relative !important;
            z-index: 999 !important;
            cursor: pointer !important;
        }
        .admin-table td {
            position: relative !important;
            z-index: 1 !important;
        }
        .table-container::before,
        .table-container::after {
            display: none !important;
        }
    `;
    document.head.appendChild(style);
    
    // Remove any overlays that might be blocking clicks
    const overlays = document.querySelectorAll('.popup-overlay, .overlay');
    overlays.forEach(overlay => {
        if (overlay.style.display !== 'none') {
            overlay.style.display = 'none';
        }
    });
    
    // Add direct click handlers to buttons
    const buttons = document.querySelectorAll('.action-buttons .btn');
    buttons.forEach(button => {
        // Remove existing event listeners
        button.onclick = null;
        
        // Add new click handler
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const action = this.closest('form').querySelector('input[name="action"]').value;
            const itemType = this.closest('form').querySelector('input[name="item_type"]').value;
            const itemName = this.textContent.includes('Approve') ? 
                (itemType === 'seller' ? this.closest('tr').querySelector('td:first-child strong').textContent :
                 this.closest('tr').querySelector('td:first-child strong').textContent) : 
                (itemType === 'seller' ? this.closest('tr').querySelector('td:first-child strong').textContent :
                 this.closest('tr').querySelector('td:first-child strong').textContent);
            
            const actionType = action.includes('approve') ? 'approve' : 'reject';
            
            if (confirmAction(actionType, itemType, itemName)) {
                this.closest('form').submit();
            }
        });
        
        // Ensure button is visible and clickable
        button.style.pointerEvents = 'auto';
        button.style.zIndex = '999';
        button.style.position = 'relative';
        button.style.cursor = 'pointer';
    });
    
});
</script>

<?php include '../includes/admin_footer.php'; ?>


