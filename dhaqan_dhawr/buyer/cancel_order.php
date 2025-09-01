<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Cancel Order';
$base_url = '../';

// Require authentication
requireAuth();

// Require buyer role
requireBuyer();

$user_id = getCurrentUserId();
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error_message = '';
$success_message = '';

if ($order_id <= 0) {
    $error_message = 'Invalid order ID.';
} else {
    try {
        // Check if order exists and belongs to user
        $check_sql = "SELECT id, status FROM orders WHERE id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $order_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            $error_message = 'Order not found or access denied.';
        } else {
            $order = $check_result->fetch_assoc();
            
            // Check if order can be cancelled
            if ($order['status'] !== 'pending') {
                $error_message = 'This order cannot be cancelled. Only pending orders can be cancelled.';
            } else {
                // Process cancellation
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    try {
                        $conn->begin_transaction();
                        
                        // Update order status to cancelled
                        $cancel_sql = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
                        $cancel_stmt = $conn->prepare($cancel_sql);
                        $cancel_stmt->bind_param("i", $order_id);
                        $cancel_stmt->execute();
                        
                        // Restore product stock
                        $items_sql = "SELECT oi.product_id, oi.quantity 
                                      FROM order_items oi 
                                      WHERE oi.order_id = ?";
                        $items_stmt = $conn->prepare($items_sql);
                        $items_stmt->bind_param("i", $order_id);
                        $items_stmt->execute();
                        $items_result = $items_stmt->get_result();
                        
                        while ($item = $items_result->fetch_assoc()) {
                            $restore_sql = "UPDATE products SET stock = stock + ? WHERE id = ?";
                            $restore_stmt = $conn->prepare($restore_sql);
                            $restore_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                            $restore_stmt->execute();
                        }
                        
                        $conn->commit();
                        
                        setFlashMessage('Order #' . $order_id . ' has been cancelled successfully.', 'success');
                        header('Location: orders.php');
                        exit();
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        error_log("Order cancellation error: " . $e->getMessage());
                        $error_message = 'An error occurred while cancelling the order. Please try again.';
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Cancel order error: " . $e->getMessage());
        $error_message = "Unable to process cancellation. Please try again later.";
    }
}

include '../includes/buyer_header.php';
?>

<main>
    <div class="container">
        <div class="cancel-order-container">
            <h1>Cancel Order</h1>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message); ?>
                </div>
                <div class="back-link">
                    <a href="orders.php" class="btn btn-outline">← Back to Orders</a>
                </div>
            <?php else: ?>
                <div class="cancel-confirmation">
                    <div class="warning-card">
                        <div class="warning-icon">⚠️</div>
                        <div class="warning-content">
                            <h2>Are you sure you want to cancel Order #<?= $order_id; ?>?</h2>
                            <p>This action cannot be undone. Once cancelled:</p>
                            <ul>
                                <li>Your order will be permanently cancelled</li>
                                <li>Product stock will be restored</li>
                                <li>You will need to place a new order if you change your mind</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="cancel-actions">
                        <form method="POST" action="">
                            <button type="submit" class="btn btn-danger">Yes, Cancel Order</button>
                            <a href="orders.php" class="btn btn-outline">No, Keep Order</a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.cancel-order-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem 0;
}

.cancel-order-container h1 {
    text-align: center;
    margin-bottom: 2rem;
    color: #333;
}

.cancel-confirmation {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.warning-card {
    display: flex;
    gap: 1rem;
    padding: 2rem;
    background: #fff3cd;
    border-bottom: 1px solid #ffeaa7;
}

.warning-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.warning-content h2 {
    margin: 0 0 1rem 0;
    color: #856404;
    font-size: 1.2rem;
}

.warning-content p {
    margin: 0 0 1rem 0;
    color: #856404;
}

.warning-content ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #856404;
}

.warning-content li {
    margin-bottom: 0.5rem;
}

.cancel-actions {
    padding: 2rem;
    text-align: center;
}

.cancel-actions form {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: 1px solid #dc3545;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-danger:hover {
    background: #c82333;
    border-color: #bd2130;
}

.back-link {
    text-align: center;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .warning-card {
        flex-direction: column;
        text-align: center;
    }
    
    .cancel-actions form {
        flex-direction: column;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
