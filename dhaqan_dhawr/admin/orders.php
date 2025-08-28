<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is admin
requireAdmin();

$page_title = "Order Management";
$base_url = '../';
$error_message = '';
$success_message = '';

// Handle admin actions (limited to emergency interventions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = (int)$_POST['order_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'cancel_order') {
            // Admin can only cancel orders in emergency situations
            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $success_message = "Order cancelled by admin.";
        }
    } catch (Exception $e) {
        $error_message = "Error updating order: " . $e->getMessage();
    }
}

// Fetch all orders with user and seller information for admin overview
try {
    $sql = "SELECT o.*, u.name as buyer_name, u.email as buyer_email,
            GROUP_CONCAT(DISTINCT s.shop_name SEPARATOR ', ') as shop_names
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN sellers s ON p.seller_id = s.id
            GROUP BY o.id
            ORDER BY o.created_at DESC";
    
    $result = $conn->query($sql);
    $orders = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error fetching orders: " . $e->getMessage();
    $orders = []; // Initialize empty array on error
}

include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
        <div class="container">
            <div class="admin-header">
                <h1><i class="fas fa-shopping-cart"></i> Order Overview</h1>
                <p>Monitor all orders in the marketplace. Sellers manage their own order statuses.</p>
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
                        <h3>$<?= number_format(array_sum(array_map(function($order) { return $order['total_amount'] ?? 0; }, $orders)), 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <div class="admin-content">
                <div class="table-section">
                    <div class="table-header">
                        <h3>Order Overview</h3>
                        <a href="index.php" class="view-all-btn">Dashboard</a>
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
                                <th>Seller(s)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">No orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id']; ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($order['buyer_name']); ?></strong><br>
                                            <small><?= htmlspecialchars($order['buyer_email']); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            // Fetch order items for this order
                                            $items_sql = "SELECT oi.*, p.title, p.main_image 
                                                         FROM order_items oi 
                                                         JOIN products p ON oi.product_id = p.id 
                                                         WHERE oi.order_id = ?";
                                            $items_stmt = $conn->prepare($items_sql);
                                            $items_stmt->bind_param("i", $order['id']);
                                            $items_stmt->execute();
                                            $items_result = $items_stmt->get_result();
                                            $item_count = $items_result->num_rows;
                                            ?>
                                            <span class="badge badge-info"><?= $item_count; ?> item<?= $item_count > 1 ? 's' : ''; ?></span>
                                        </td>
                                        <td>$<?= number_format($order['total_amount'] ?? 0, 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?= $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'info'); ?>">
                                                <?= ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($order['payment_method']); ?></td>
                                        <td>
                                            <small><?= htmlspecialchars(substr($order['shipping_address'], 0, 50)); ?>...</small>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <small><?= htmlspecialchars($order['shop_names'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-primary" onclick="viewOrderDetails(<?= $order['id']; ?>)">
                                                    View Details
                                                </button>
                                                <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Emergency cancellation: Are you sure you want to cancel this order? This should only be done in exceptional circumstances.')">
                                                        <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                                        <input type="hidden" name="action" value="cancel_order">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            Emergency Cancel
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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

<!-- Order Details Modal -->
<div id="orderModal" class="modal" style="display: none;">
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
            <li>Complete order items list</li>
            <li>Seller information</li>
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

<?php include '../includes/admin_footer.php'; ?>
