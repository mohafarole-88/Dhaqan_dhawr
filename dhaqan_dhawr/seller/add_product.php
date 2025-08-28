<?php
require_once '../includes/init.php';

$page_title = 'Add Product';
$base_url = '../';

// Require seller role and approval
requireSeller();

$user_id = getCurrentUserId();

// Check if seller is approved
$stmt = $conn->prepare("SELECT * FROM sellers WHERE user_id = ? AND approved = 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();

if (!$seller) {
    setFlashMessage('You must be an approved seller to add products.', 'error');
    header('Location: dashboard.php');
    exit();
}

// Get categories for dropdown
$stmt = $conn->prepare("SELECT id, name, parent_id FROM categories ORDER BY name");
$stmt->execute();
$categories_result = $stmt->get_result();
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $cultural_notes = sanitizeInput($_POST['cultural_notes'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Product title is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Product description is required';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Please select a category';
    }
    
    if ($price <= 0) {
        $errors[] = 'Price must be greater than 0';
    }
    
    if ($stock < 0) {
        $errors[] = 'Stock cannot be negative';
    }
    
    // Handle main image upload
    $main_image = '';
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['main_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Main image must be a JPEG, PNG, GIF, or WebP file';
        } elseif ($file['size'] > $max_size) {
            $errors[] = 'Main image must be less than 5MB';
        } else {
            $upload_dir = '../uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $main_image = uniqid() . '_main.' . $file_extension;
            $upload_path = $upload_dir . $main_image;
            
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                $errors[] = 'Failed to upload main image';
            }
        }
    } else {
        $errors[] = 'Main image is required';
    }
    
    // If no errors, create product
    if (empty($errors)) {
        $seller_id = $seller['id'];
        
        $stmt = $conn->prepare("INSERT INTO products (seller_id, category_id, title, description, price, stock, main_image, cultural_notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iissdiss", $seller_id, $category_id, $title, $description, $price, $stock, $main_image, $cultural_notes);
        
        if ($stmt->execute()) {
            $product_id = $conn->insert_id;
            
            // Handle additional images
            if (isset($_FILES['additional_images'])) {
                $additional_images = $_FILES['additional_images'];
                
                for ($i = 0; $i < count($additional_images['name']); $i++) {
                    if ($additional_images['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $additional_images['name'][$i],
                            'type' => $additional_images['type'][$i],
                            'tmp_name' => $additional_images['tmp_name'][$i],
                            'error' => $additional_images['error'][$i],
                            'size' => $additional_images['size'][$i]
                        ];
                        
                        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $image_name = uniqid() . '_' . $i . '.' . $file_extension;
                            $upload_path = $upload_dir . $image_name;
                            
                            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                                $stmt = $conn->prepare("INSERT INTO product_images (product_id, file_path) VALUES (?, ?)");
                                $stmt->bind_param("is", $product_id, $image_name);
                                $stmt->execute();
                            }
                        }
                    }
                }
            }
            
            setFlashMessage('Product added successfully! It will be reviewed by our admin team.', 'success');
            header('Location: dashboard.php');
            exit();
        } else {
            $errors[] = 'Failed to create product. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<?php include '../includes/seller_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
        <div class="container">
        <div class="form-container">
            <h2 class="text-center">Add New Product</h2>
            <p class="text-center mb-3">Share your Somali cultural treasures</p>
            
            <?php if (!empty($errors)): ?>
                <div class="flash-message error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateForm(this);">
                <div class="form-group">
                    <label for="title">Product Title *</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id']; ?>" <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?= htmlspecialchars($_POST['price'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock Quantity *</label>
                        <input type="number" id="stock" name="stock" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Product Description *</label>
                    <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <small>Describe the product, its features, and materials used</small>
                </div>
                
                <div class="form-group">
                    <label for="cultural_notes">Cultural Notes</label>
                    <textarea id="cultural_notes" name="cultural_notes" rows="3"><?= htmlspecialchars($_POST['cultural_notes'] ?? ''); ?></textarea>
                    <small>Share the cultural significance, history, or traditional use of this item</small>
                </div>
                
                <div class="form-group">
                    <label for="main_image">Main Product Image *</label>
                    <input type="file" id="main_image" name="main_image" accept="image/*" required onchange="previewImage(this, 'main_preview')">
                    <small>This will be the primary image displayed for your product (max 5MB)</small>
                    <img id="main_preview" src="#" alt="Preview" style="display: none; max-width: 200px; margin-top: 10px;">
                </div>
                
                <div class="form-group">
                    <label for="additional_images">Additional Images</label>
                    <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple onchange="previewMultipleImages(this, 'additional_preview')">
                    <small>You can upload multiple additional images (max 5MB each)</small>
                    <div id="additional_preview" style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;"></div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary w-100">Add Product</button>
                </div>
            </form>
            
        </div>
    </main>
</div>

<script>
function previewMultipleImages(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    preview.style.display = 'flex';
    
    if (input.files) {
        for (let i = 0; i < input.files.length; i++) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '150px';
                img.style.maxHeight = '150px';
                img.style.objectFit = 'cover';
                img.style.borderRadius = '5px';
                preview.appendChild(img);
            };
            reader.readAsDataURL(input.files[i]);
        }
    }
}
</script>

<?php include '../includes/footer.php'; ?>
