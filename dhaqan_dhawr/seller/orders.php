<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is seller
requireSeller();

$page_title = "My Orders";
$base_url = '../'; // Fix CSS path issue
$error_message = '';
$success_message = '';

// Initialize orders array to prevent undefined variable errors
$orders = [];

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = (int)$_POST['order_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'update_status') {
            $new_status = $_POST['status'];
            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            $success_message = "Order status updated successfully.";
        }
    } catch (Exception $e) {
        $error_message = "Error updating order: " . $e->getMessage();
    }
}

// Get seller ID first
$seller_stmt = $conn->prepare("SELECT id FROM sellers WHERE user_id = ?");
$seller_stmt->bind_param("i", $_SESSION['user_id']);
$seller_stmt->execute();
$seller_result = $seller_stmt->get_result();
$seller_data = $seller_result->fetch_assoc();
$seller_id = $seller_data['id'] ?? null;

// Fetch orders for seller's products
try {
    if ($seller_id) {
        $sql = "SELECT DISTINCT o.*, u.name as buyer_name, u.email as buyer_email
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                JOIN users u ON o.user_id = u.id
                WHERE p.seller_id = ?
                ORDER BY o.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = null;
    }
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error fetching orders: " . $e->getMessage();
    // Keep orders as empty array if there's an error
    $orders = [];
}

include '../includes/header.php';
?>

<?php include '../includes/seller_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
        <div class="container">
            <div class="admin-header">
                <h1><i class="fas fa-shopping-cart"></i> My Orders</h1>
                <p>Manage orders for your products.</p>
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
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($orders); ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($orders, function($o) { return $o['status'] === 'pending'; })); ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($orders, function($o) { return $o['status'] === 'processing'; })); ?></h3>
                        <p>Processing</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($orders, function($o) { return $o['status'] === 'completed'; })); ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>$<?= number_format(array_sum(array_column($orders, 'total_amount')), 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <div class="admin-content">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <h3>No Orders Yet</h3>
                        <p>You haven't received any orders for your products yet.</p>
                        <a href="add_product.php" class="btn btn-primary">Add More Products</a>
                    </div>
                <?php else: ?>
                    <div class="table-section">
                        <div class="table-header">
                            <h3>My Orders</h3>
                            <a href="dashboard.php" class="view-all-btn">Dashboard</a>
                        </div>
                        <div class="table-container">
                            <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Buyer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Shipping Address</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id']; ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($order['buyer_name']); ?></strong><br>
                                        <small><?= htmlspecialchars($order['buyer_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        // Fetch order items for this order that belong to this seller
                                        $items_sql = "SELECT oi.*, p.title, p.main_image 
                                                     FROM order_items oi 
                                                     JOIN products p ON oi.product_id = p.id 
                                                     WHERE oi.order_id = ? AND p.seller_id = ?";
                                        $items_stmt = $conn->prepare($items_sql);
                                        $items_stmt->bind_param("ii", $order['id'], $seller_id);
                                        $items_stmt->execute();
                                        $items_result = $items_stmt->get_result();
                                        $item_count = $items_result->num_rows;
                                        ?>
                                        <span class="badge badge-info"><?= $item_count; ?> item<?= $item_count > 1 ? 's' : ''; ?></span>
                                    </td>
                                    <td>$<?= number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?= $order['status']; ?>">
                                            <?= ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($order['payment_method']); ?></td>
                                    <td>
                                        <small><?= htmlspecialchars(substr($order['shipping_address'], 0, 50)); ?>...</small>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-primary" onclick="viewOrderDetails(<?= $order['id']; ?>)">
                                                View Details
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to update this order status?')">
                                                <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
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
        </div>
    </main>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="orderDetails"></div>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    // This would typically make an AJAX call to get order details
    // For now, we'll show a simple message
    document.getElementById('orderDetails').innerHTML = `
        <h3>Order #${orderId} Details</h3>
        <p>Detailed order information would be loaded here via AJAX.</p>
        <p>This would include:</p>
        <ul>
            <li>Complete order items list (only your products)</li>
            <li>Buyer information</li>
            <li>Order history</li>
            <li>Shipping tracking</li>
        </ul>
    `;
    document.getElementById('orderModal').style.display = 'block';
}

// Close modal when clicking on X or outside the modal
document.querySelector('.close').onclick = function() {
    document.getElementById('orderModal').style.display = 'none';
}

window.onclick = function(event) {
    var modal = document.getElementById('orderModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

    </div>
    </main>
</div>

<?php include '../includes/admin_footer.php'; ?>
