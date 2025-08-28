<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is admin
requireAdmin();

$page_title = "Category Management";
$error_message = '';
$success_message = '';

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            if ($action === 'add_category') {
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                
                if (empty($name)) {
                    throw new Exception("Category name is required.");
                }
                
                $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $description);
                $stmt->execute();
                $success_message = "Category added successfully.";
                
            } elseif ($action === 'update_category') {
                $category_id = (int)$_POST['category_id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                
                if (empty($name)) {
                    throw new Exception("Category name is required.");
                }
                
                $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $description, $category_id);
                $stmt->execute();
                $success_message = "Category updated successfully.";
                
            } elseif ($action === 'delete_category') {
                $category_id = (int)$_POST['category_id'];
                
                // Check if category has products
                $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                $check_stmt->bind_param("i", $category_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $product_count = $check_result->fetch_assoc()['count'];
                
                if ($product_count > 0) {
                    throw new Exception("Cannot delete category with {$product_count} product(s). Please reassign or delete the products first.");
                }
                
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->bind_param("i", $category_id);
                $stmt->execute();
                $success_message = "Category deleted successfully.";
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all categories with product counts
try {
    $sql = "SELECT c.*, COUNT(p.id) as product_count 
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id 
            GROUP BY c.id 
            ORDER BY c.name ASC";
    
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error fetching categories: " . $e->getMessage();
}

// Set base URL for admin pages
$base_url = '../';
include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
    <div class="container">
        <div class="admin-header">
            <h1>Category Management</h1>
            <p>Manage product categories for the marketplace.</p>
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
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count($categories); ?></h3>
                    <p>Total Categories</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <h3><?= array_sum(array_column($categories, 'product_count')); ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($categories, function($c) { return $c['product_count'] > 0; })); ?></h3>
                    <p>Active Categories</p>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <!-- Add New Category Form -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-plus-circle"></i> Add New Category</h2>
                </div>
                <form method="POST" class="admin-form">
                    <input type="hidden" name="action" value="add_category">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-tag"></i> Category Name *</label>
                            <input type="text" id="name" name="name" required class="form-control" placeholder="Enter category name">
                        </div>
                        <div class="form-group">
                            <label for="description"><i class="fas fa-align-left"></i> Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Enter category description (optional)"></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                </form>
            </div>

            <!-- Categories List -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Existing Categories</h2>
                </div>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No categories found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?= $category['id']; ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($category['name']); ?></strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($category['description'] ?? 'No description'); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= $category['product_count']; ?> products</span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-primary" onclick="editCategory(<?= $category['id']; ?>, '<?= htmlspecialchars($category['name'], ENT_QUOTES); ?>', '<?= htmlspecialchars($category['description'] ?? '', ENT_QUOTES); ?>')">
                                                    Edit
                                                </button>
                                                <?php if ($category['product_count'] == 0): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_category">
                                                        <input type="hidden" name="category_id" value="<?= $category['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category? This action cannot be undone.')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled title="Cannot delete category with products">
                                                        Delete
                                                    </button>
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

<!-- Edit Category Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Category</h2>
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="update_category">
            <input type="hidden" name="category_id" id="edit_category_id">
            <div class="form-group">
                <label for="edit_name">Category Name *</label>
                <input type="text" id="edit_name" name="name" required class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Category</button>
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name, description) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking on X or outside the modal
document.querySelector('.close').onclick = function() {
    closeEditModal();
}

window.onclick = function(event) {
    var modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}
</script>

<?php include '../includes/admin_footer.php'; ?>
