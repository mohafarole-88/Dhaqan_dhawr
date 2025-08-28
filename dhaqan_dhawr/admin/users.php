<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is admin
requireAdmin();

$page_title = "User Management";
$base_url = '../'; // Fix CSS path issue
$error_message = '';
$success_message = '';

// Handle GET parameters for messages after redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Redirect after POST to prevent resubmission
    $redirect_url = $_SERVER['REQUEST_URI'];
    
    try {
        if ($action === 'add_user') {
            // Add new user
            $name = trim($_POST['user_name']);
            $email = trim($_POST['user_email']);
            $phone = trim($_POST['user_phone']);
            $password = $_POST['user_password'];
            $confirm_password = $_POST['confirm_password'];
            $role = $_POST['user_role'];
            
            // Validate password confirmation
            if ($password !== $confirm_password) {
                $error_message = "Passwords do not match.";
            } else {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error_message = "Email address already exists.";
                } else {
                    // Hash password
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user
                    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password_hash, role, verified, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
                    $stmt->bind_param("sssss", $name, $email, $phone, $password_hash, $role);
                    $stmt->execute();
                    $user_id = $conn->insert_id;
                    
                    // If seller, create seller record
                    if ($role === 'seller') {
                        $shop_name = trim($_POST['shop_name']);
                        $shop_location = trim($_POST['shop_location']);
                        
                        $stmt = $conn->prepare("INSERT INTO sellers (user_id, shop_name, location, approved, created_at) VALUES (?, ?, ?, 1, NOW())");
                        $stmt->bind_param("iss", $user_id, $shop_name, $shop_location);
                        $stmt->execute();
                    }
                    
                    $success_message = "User created successfully.";
                    header("Location: $redirect_url?success=" . urlencode($success_message));
                    exit;
                }
            }
        } else {
            // Handle existing user status updates
            $user_id = (int)$_POST['user_id'];
            
            if ($action === 'approve_seller') {
                $stmt = $conn->prepare("UPDATE sellers SET approved = 1 WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $success_message = "Seller approved successfully.";
                header("Location: $redirect_url?success=" . urlencode($success_message));
                exit;
            } elseif ($action === 'reject_seller') {
                $stmt = $conn->prepare("UPDATE sellers SET approved = 0 WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $success_message = "Seller rejected successfully.";
                header("Location: $redirect_url?success=" . urlencode($success_message));
                exit;
            } elseif ($action === 'deactivate_user') {
                $stmt = $conn->prepare("UPDATE users SET verified = 0 WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $success_message = "User deactivated successfully.";
                header("Location: $redirect_url?success=" . urlencode($success_message));
                exit;
            } elseif ($action === 'activate_user') {
                $stmt = $conn->prepare("UPDATE users SET verified = 1 WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $success_message = "User activated successfully.";
                header("Location: $redirect_url?success=" . urlencode($success_message));
                exit;
            } elseif ($action === 'delete_user') {
                // Check if user is not an admin (prevent admin deletion)
                $check_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $user_role = $check_stmt->get_result()->fetch_assoc()['role'];
                
                if ($user_role === 'admin') {
                    $error_message = "Cannot delete admin users.";
                } else {
                    // Delete related records first (foreign key constraints)
                    $conn->begin_transaction();
                    try {
                        // Delete seller record if exists
                        $stmt = $conn->prepare("DELETE FROM sellers WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        
                        // Delete orders and order items
                        $stmt = $conn->prepare("DELETE oi FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        
                        $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        
                        // Delete products if seller
                        $stmt = $conn->prepare("DELETE FROM products WHERE seller_id IN (SELECT id FROM sellers WHERE user_id = ?)");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        
                        // Finally delete the user
                        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        
                        $conn->commit();
                        $success_message = "User deleted successfully.";
                        header("Location: $redirect_url?success=" . urlencode($success_message));
                        exit;
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error_message = "Error deleting user: " . $e->getMessage();
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error_message = "Error processing request: " . $e->getMessage();
    }
}

// Fetch all users with their roles and seller status
try {
    $sql = "SELECT u.*, 
            CASE 
                WHEN s.id IS NOT NULL THEN 'seller'
                WHEN u.role = 'admin' THEN 'admin'
                ELSE 'buyer'
            END as actual_role,
            s.approved as seller_approved,
            s.shop_name,
            s.location
            FROM users u
            LEFT JOIN sellers s ON u.id = s.user_id
            ORDER BY u.created_at DESC";
    
    $result = $conn->query($sql);
    $users = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error fetching users: " . $e->getMessage();
    $users = []; // Initialize as empty array to prevent undefined variable errors
}

include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
    <div class="container">
        <div class="admin-header">
            <h1>User Management</h1>
            <p>Manage all users, approve seller applications, and monitor user activity.</p>
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
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count($users); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($users, function($u) { return $u['actual_role'] === 'buyer'; })); ?></h3>
                    <p>Buyers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($users, function($u) { return $u['actual_role'] === 'seller' && $u['seller_approved']; })); ?></h3>
                    <p>Approved Sellers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($users, function($u) { return $u['actual_role'] === 'seller' && !$u['seller_approved']; })); ?></h3>
                    <p>Pending Sellers</p>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-users"></i> All Users</h2>
                <button class="btn btn-primary" onclick="openAddUserModal()">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div>
            
            <div class="admin-content">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Shop Name</th>
                                <th>Location</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id']; ?></td>
                                    <td><?= htmlspecialchars($user['name']); ?></td>
                                    <td><?= htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?= $user['actual_role']; ?>">
                                            <?= ucfirst($user['actual_role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $user['verified'] ? 'success' : 'warning'; ?>">
                                            <?= $user['verified'] ? 'Verified' : 'Unverified'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['actual_role'] === 'seller'): ?>
                                            <?= htmlspecialchars($user['shop_name'] ?? 'N/A'); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['actual_role'] === 'seller'): ?>
                                            <?= htmlspecialchars($user['location'] ?? 'N/A'); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($user['actual_role'] === 'seller' && !$user['seller_approved']): ?>
                                                <form method="POST" style="display: inline;" class="action-form">
                                                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                                    <input type="hidden" name="action" value="approve_seller">
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            onclick="return handleButtonClick(this)"
                                                            data-action="approve" 
                                                            data-type="seller" 
                                                            data-name="<?= htmlspecialchars($user['name']); ?>" 
                                                            data-shop="<?= htmlspecialchars($user['shop_name'] ?? ''); ?>">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" class="action-form">
                                                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                                    <input type="hidden" name="action" value="reject_seller">
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="return handleButtonClick(this)"
                                                            data-action="reject" 
                                                            data-type="seller" 
                                                            data-name="<?= htmlspecialchars($user['name']); ?>" 
                                                            data-shop="<?= htmlspecialchars($user['shop_name'] ?? ''); ?>">
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['verified']): ?>
                                                <form method="POST" style="display: inline;" class="action-form">
                                                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                                    <input type="hidden" name="action" value="deactivate_user">
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            onclick="return handleButtonClick(this)"
                                                            data-action="deactivate" 
                                                            data-type="user" 
                                                            data-name="<?= htmlspecialchars($user['name']); ?>">
                                                        Deactivate
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;" class="action-form">
                                                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                                    <input type="hidden" name="action" value="activate_user">
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            onclick="return handleButtonClick(this)"
                                                            data-action="activate" 
                                                            data-type="user" 
                                                            data-name="<?= htmlspecialchars($user['name']); ?>">
                                                        Activate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <form method="POST" style="display: inline;" class="action-form">
                                                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirmDelete('<?= htmlspecialchars($user['name']); ?>')"
                                                            data-action="delete" 
                                                            data-type="user" 
                                                            data-name="<?= htmlspecialchars($user['name']); ?>">
                                                        Delete
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

<!-- Add User Modal -->
<div id="addUserModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New User</h3>
            <span class="close" onclick="closeAddUserModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addUserForm" method="POST" action="">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_name">Full Name</label>
                        <input type="text" id="user_name" name="user_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_email">Email Address</label>
                        <input type="email" id="user_email" name="user_email" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_phone">Phone Number</label>
                        <input type="tel" id="user_phone" name="user_phone" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_role">User Role</label>
                        <select id="user_role" name="user_role" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="buyer">Buyer</option>
                            <option value="seller">Seller</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_password">Password</label>
                        <input type="password" id="user_password" name="user_password" class="form-control" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                </div>
                
                <div id="sellerFields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shop_name">Shop Name</label>
                            <input type="text" id="shop_name" name="shop_name" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="shop_location">Shop Location</label>
                            <input type="text" id="shop_location" name="shop_location" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore background scrolling
    document.getElementById('addUserForm').reset();
    document.getElementById('sellerFields').style.display = 'none';
}

// Show/hide seller fields based on role selection
document.getElementById('user_role').addEventListener('change', function() {
    const sellerFields = document.getElementById('sellerFields');
    if (this.value === 'seller') {
        sellerFields.style.display = 'block';
        document.getElementById('shop_name').required = true;
        document.getElementById('shop_location').required = true;
    } else {
        sellerFields.style.display = 'none';
        document.getElementById('shop_name').required = false;
        document.getElementById('shop_location').required = false;
    }
});

// Password confirmation validation
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('user_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addUserModal');
    if (event.target === modal) {
        closeAddUserModal();
    }
}

// Prevent modal from closing when clicking inside modal content
document.querySelector('.modal-content').addEventListener('click', function(event) {
    event.stopPropagation();
});

// Simple button functionality without complex event handling
function handleButtonClick(button) {
    const action = button.getAttribute('data-action');
    const type = button.getAttribute('data-type');
    const name = button.getAttribute('data-name');
    const shop = button.getAttribute('data-shop');
    
    let message = '';
    
    if (action === 'approve' && type === 'seller') {
        message = `Are you sure you want to approve the seller application for:\n\nName: ${name}\nShop: ${shop}\n\nThis will allow them to start selling products on the platform.`;
    } else if (action === 'reject' && type === 'seller') {
        message = `Are you sure you want to reject the seller application for:\n\nName: ${name}\nShop: ${shop}\n\nThis action can be reversed later if needed.`;
    } else if (action === 'deactivate' && type === 'user') {
        message = `Are you sure you want to deactivate the account for:\n\nUser: ${name}\n\nThis will prevent them from logging in until reactivated.`;
    } else if (action === 'activate' && type === 'user') {
        message = `Are you sure you want to activate the account for:\n\nUser: ${name}\n\nThis will allow them to log in and use the platform.`;
    }
    
    if (confirm(message)) {
        button.closest('form').submit();
    }
    return false;
}

// Dedicated delete confirmation function
function confirmDelete(userName) {
    const message = `⚠️ PERMANENT ACTION WARNING ⚠️\n\nAre you sure you want to PERMANENTLY DELETE the user:\n\nUser: ${userName}\n\nThis will:\n• Delete their account permanently\n• Remove all their orders and data\n• Remove their seller profile (if applicable)\n• This action CANNOT be undone\n\nOnly proceed if you are absolutely certain!`;
    
    return confirm(message);
}
</script>

<?php include '../includes/admin_footer.php'; ?>
