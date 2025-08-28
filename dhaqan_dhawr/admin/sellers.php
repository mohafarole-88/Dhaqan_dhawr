<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is admin
requireAdmin();

$page_title = "Seller Applications";
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

// Handle seller application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $seller_id = (int)$_POST['seller_id'];
    $action = $_POST['action'];
    
    // Redirect after POST to prevent resubmission
    $redirect_url = $_SERVER['REQUEST_URI'];
    
    try {
        if ($action === 'approve_seller') {
            $stmt = $conn->prepare("UPDATE sellers SET approved = 1 WHERE id = ?");
            $stmt->bind_param("i", $seller_id);
            $stmt->execute();
            $success_message = "Seller approved successfully.";
            header("Location: $redirect_url?success=" . urlencode($success_message));
            exit;
        } elseif ($action === 'reject_seller') {
            $stmt = $conn->prepare("UPDATE sellers SET approved = 0 WHERE id = ?");
            $stmt->bind_param("i", $seller_id);
            $stmt->execute();
            $success_message = "Seller rejected successfully.";
            header("Location: $redirect_url?success=" . urlencode($success_message));
            exit;
        } elseif ($action === 'delete_seller') {
            // Delete seller and associated user
            $stmt = $conn->prepare("SELECT user_id FROM sellers WHERE id = ?");
            $stmt->bind_param("i", $seller_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $seller = $result->fetch_assoc();
            
            if ($seller) {
                // Delete seller record
                $stmt = $conn->prepare("DELETE FROM sellers WHERE id = ?");
                $stmt->bind_param("i", $seller_id);
                $stmt->execute();
                
                // Update user role back to buyer
                $stmt = $conn->prepare("UPDATE users SET role = 'buyer' WHERE id = ?");
                $stmt->bind_param("i", $seller['user_id']);
                $stmt->execute();
                
                $success_message = "Seller application deleted successfully.";
                header("Location: $redirect_url?success=" . urlencode($success_message));
                exit;
            }
        }
    } catch (Exception $e) {
        $error_message = "Error processing request: " . $e->getMessage();
    }
}

// Fetch all seller applications
try {
    $sql = "SELECT s.*, u.name as user_name, u.email, u.phone, u.created_at as user_created_at
            FROM sellers s
            JOIN users u ON s.user_id = u.id
            ORDER BY s.created_at DESC";
    
    $result = $conn->query($sql);
    $sellers = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sellers[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error fetching seller applications: " . $e->getMessage();
    $sellers = [];
}

// Calculate statistics
$pending_sellers = array_filter($sellers, function($s) { return !$s['approved']; });
$approved_sellers = array_filter($sellers, function($s) { return $s['approved']; });

include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
        <div class="container">
            <div class="admin-header">
                <h1><i class="fas fa-store"></i> Seller Applications</h1>
                <p>Manage and review seller applications for the marketplace.</p>
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

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($sellers); ?></h3>
                        <p>Total Applications</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($pending_sellers); ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($approved_sellers); ?></h3>
                        <p>Approved Sellers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($sellers) > 0 ? round((count($approved_sellers) / count($sellers)) * 100) : 0; ?>%</h3>
                        <p>Approval Rate</p>
                    </div>
                </div>
            </div>

            <!-- Seller Applications Table -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> All Seller Applications</h2>
                    <a href="index.php" class="btn btn-outline">Dashboard</a>
                </div>
                
                <div class="admin-content">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Seller Info</th>
                                    <th>Shop Details</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sellers)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No seller applications found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sellers as $seller): ?>
                                        <tr>
                                            <td>#<?= $seller['id']; ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($seller['user_name']); ?></strong><br>
                                                <small>User ID: <?= $seller['user_id']; ?></small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($seller['shop_name']); ?></strong><br>
                                                <small><?= htmlspecialchars($seller['location']); ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($seller['email']); ?><br>
                                                <small><?= htmlspecialchars($seller['phone'] ?? 'N/A'); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $seller['approved'] ? 'success' : 'warning'; ?>">
                                                    <?= $seller['approved'] ? 'Approved' : 'Pending'; ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($seller['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if (!$seller['approved']): ?>
                                                        <form method="POST" style="display: inline;" class="action-form">
                                                            <input type="hidden" name="seller_id" value="<?= $seller['id']; ?>">
                                                            <input type="hidden" name="action" value="approve_seller">
                                                            <button type="button" class="btn btn-sm btn-success" 
                                                                    onclick="return handleSellerButtonClick(this)"
                                                                    data-action="approve" 
                                                                    data-type="seller" 
                                                                    data-name="<?= htmlspecialchars($seller['user_name']); ?>" 
                                                                    data-shop="<?= htmlspecialchars($seller['shop_name']); ?>">
                                                                Approve
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;" class="action-form">
                                                            <input type="hidden" name="seller_id" value="<?= $seller['id']; ?>">
                                                            <input type="hidden" name="action" value="reject_seller">
                                                            <button type="button" class="btn btn-sm btn-warning" 
                                                                    onclick="return handleSellerButtonClick(this)"
                                                                    data-action="reject" 
                                                                    data-type="seller" 
                                                                    data-name="<?= htmlspecialchars($seller['user_name']); ?>" 
                                                                    data-shop="<?= htmlspecialchars($seller['shop_name']); ?>">
                                                                Reject
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" style="display: inline;" class="action-form">
                                                        <input type="hidden" name="seller_id" value="<?= $seller['id']; ?>">
                                                        <input type="hidden" name="action" value="delete_seller">
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick="return handleSellerButtonClick(this)"
                                                                data-action="delete" 
                                                                data-type="seller" 
                                                                data-name="<?= htmlspecialchars($seller['user_name']); ?>" 
                                                                data-shop="<?= htmlspecialchars($seller['shop_name']); ?>">
                                                            Delete
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
// Simple button functionality without complex event handling
function handleSellerButtonClick(button) {
    const action = button.getAttribute('data-action');
    const type = button.getAttribute('data-type');
    const name = button.getAttribute('data-name');
    const shop = button.getAttribute('data-shop');
    
    let message = '';
    
    if (action === 'approve' && type === 'seller') {
        message = `Are you sure you want to approve the seller application for:\n\nName: ${name}\nShop: ${shop}\n\nThis will allow them to start selling products on the platform.`;
    } else if (action === 'reject' && type === 'seller') {
        message = `Are you sure you want to reject the seller application for:\n\nName: ${name}\nShop: ${shop}\n\nThis will change their status to rejected. This action can be reversed later.`;
    } else if (action === 'delete' && type === 'seller') {
        message = `Are you sure you want to DELETE the seller application for:\n\nName: ${name}\nShop: ${shop}\n\nWARNING: This will permanently remove the seller application and revert the user to buyer status. This action cannot be undone!`;
    }
    
    if (confirm(message)) {
        button.closest('form').submit();
    }
    return false;
}
</script>

<?php include '../includes/admin_footer.php'; ?>
