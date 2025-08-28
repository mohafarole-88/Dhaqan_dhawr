<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/../includes/init.php';

$page_title = 'Become a Seller';
$base_url = '../';

// Require seller role
requireSeller();

// Check if user already has a seller application
$user_id = getCurrentUserId();
$stmt = $conn->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existing_seller = $result->fetch_assoc();

if ($existing_seller) {
    if ($existing_seller['approved']) {
        setFlashMessage('You are already an approved seller!', 'success');
        header('Location: dashboard.php');
        exit();
    } else {
        setFlashMessage('Your seller application is pending approval.', 'info');
        header('Location: dashboard.php');
        exit();
    }
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = sanitizeInput($_POST['shop_name'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    
    // Validation
    if (empty($shop_name)) {
        $errors[] = 'Shop name is required';
    }
    
    if (empty($bio)) {
        $errors[] = 'Shop bio is required';
    }
    
    if (empty($location)) {
        $errors[] = 'Location is required';
    }
    
    // If no errors, create seller application
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO sellers (user_id, shop_name, bio, location, approved) VALUES (?, ?, ?, ?, FALSE)");
        $stmt->bind_param("isss", $user_id, $shop_name, $bio, $location);
        
        if ($stmt->execute()) {
            setFlashMessage('Your seller application has been submitted successfully! It will be reviewed by our admin team.', 'success');
            header('Location: dashboard.php');
            exit();
        } else {
            $errors[] = 'Application submission failed. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<main>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center">Become a Seller</h2>
            <p class="text-center mb-3">Share your Somali cultural treasures with the world</p>
            
            <?php if (!empty($errors)): ?>
                <div class="flash-message error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="info-box mb-3">
                <h4><i class="fas fa-info-circle"></i> Seller Guidelines</h4>
                <ul>
                    <li>All products must be authentic Somali cultural items</li>
                    <li>High-quality images are required for all products</li>
                    <li>Accurate descriptions and cultural context are important</li>
                    <li>Fair pricing and honest representation are expected</li>
                    <li>Applications are reviewed within 2-3 business days</li>
                </ul>
            </div>
            
            <form method="POST" action="" onsubmit="return validateForm(this);">
                <div class="form-group">
                    <label for="shop_name">Shop Name *</label>
                    <input type="text" id="shop_name" name="shop_name" value="<?= htmlspecialchars($_POST['shop_name'] ?? ''); ?>" required>
                    <small>Choose a unique name for your shop</small>
                </div>
                
                <div class="form-group">
                    <label for="bio">Shop Bio *</label>
                    <textarea id="bio" name="bio" rows="4" required><?= htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                    <small>Tell customers about your shop and the cultural items you offer</small>
                </div>
                
                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text" id="location" name="location" value="<?= htmlspecialchars($_POST['location'] ?? ''); ?>" required>
                    <small>City, Country (e.g., Mogadishu, Somalia)</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary w-100">Submit Application</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <p><a href="../index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</main>

<style>
.info-box {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 5px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.info-box h4 {
    color: #1976d2;
    margin-bottom: 0.5rem;
}

.info-box ul {
    margin: 0;
    padding-left: 1.5rem;
}

.info-box li {
    margin-bottom: 0.25rem;
    color: #1565c0;
}
</style>

<?php include '../includes/footer.php'; ?>
