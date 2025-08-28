<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

$page_title = 'My Profile';
$base_url = '';

// Require authentication
requireAuth();

$user_id = getCurrentUserId();
$errors = [];
$success_message = '';

// Fetch user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        setFlashMessage('User not found.', 'error');
        header('Location: index.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    setFlashMessage('An error occurred while loading your profile.', 'error');
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = 'This email address is already registered';
        }
    }

    // Password change validation
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
    }

    // If no errors, update profile
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $email, $phone, $address, $hashed_password, $user_id);
            } else {
                // Update without password
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $email, $phone, $address, $user_id);
            }

            if ($stmt->execute()) {
                $success_message = 'Profile updated successfully!';
                // Update session data
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                // Refresh user data
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['address'] = $address;
            } else {
                $errors[] = 'Failed to update profile. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $errors[] = 'An error occurred while updating your profile.';
        }
    }
}

include 'includes/buyer_header.php';
?>

<main class="profile-main">
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p class="subtitle">Manage Your Account Settings and Personal Information</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="profile-info">
                <div class="info-card">
                    <h3><i class="fas fa-user"></i> Account Information</h3>
                    <p><strong>Role:</strong> <?= ucfirst(htmlspecialchars($_SESSION['role'])); ?></p>
                    <p><strong>Member since:</strong> <?= date('F j, Y', strtotime($user['created_at'])); ?></p>
                </div>

                <?php if ($_SESSION['role'] === 'seller'): ?>
                    <?php
                    // Fetch seller information
                    $seller_stmt = $conn->prepare("SELECT * FROM sellers WHERE user_id = ?");
                    $seller_stmt->bind_param("i", $user_id);
                    $seller_stmt->execute();
                    $seller_result = $seller_stmt->get_result();
                    $seller = $seller_result->fetch_assoc();
                    ?>
                    <?php if ($seller): ?>
                        <div class="info-card">
                            <h3><i class="fas fa-store"></i> Seller Information</h3>
                            <p><strong>Shop Name:</strong> <?= htmlspecialchars($seller['shop_name']); ?></p>
                            <p><strong>Status:</strong> 
                                <?php if ($seller['approved']): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending Approval</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>Location:</strong> <?= htmlspecialchars($seller['location']); ?></p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <form method="POST" action="" class="profile-form">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" class="form-control"><?= htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Change Password</h3>
                    <p class="form-help">Leave blank if you don't want to change your password</p>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control">
                        <small>Minimum <?= PASSWORD_MIN_LENGTH; ?> characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>

        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>


