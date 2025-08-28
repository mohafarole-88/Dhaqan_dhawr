<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is admin
requireAdmin();

$page_title = "Comments Management";
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

// Handle comment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Redirect after POST to prevent resubmission
    $redirect_url = $_SERVER['REQUEST_URI'];
    
    try {
        if ($action === 'mark_read') {
            $comment_id = (int)$_POST['comment_id'];
            $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
            $stmt->execute([$comment_id]);
            $success_message = "Message marked as read successfully.";
            header("Location: $redirect_url?success=" . urlencode($success_message));
            exit;
        } elseif ($action === 'delete') {
            $comment_id = (int)$_POST['comment_id'];
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$comment_id]);
            $success_message = "Message deleted successfully.";
            header("Location: $redirect_url?success=" . urlencode($success_message));
            exit;
        }
    } catch (Exception $e) {
        $error_message = "Error processing request: " . $e->getMessage();
    }
}

// Create table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Fetch all comments
try {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC");
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching comments: " . $e->getMessage();
    $comments = [];
}

include '../includes/admin_header.php';
?>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="seller-layout">
    <main class="seller-main">
    <div class="container">
        <div class="admin-header">
            <h1>Comments Management</h1>
            <p>View and manage contact form submissions from users</p>
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
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count($comments); ?></h3>
                    <p>Total Messages</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-envelope-open"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($comments, function($c) { return !$c['is_read']; })); ?></h3>
                    <p>Unread Messages</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($comments, function($c) { return $c['is_read']; })); ?></h3>
                    <p>Read Messages</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($comments, function($c) { return date('Y-m-d', strtotime($c['created_at'])) === date('Y-m-d'); })); ?></h3>
                    <p>Today's Messages</p>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-comments"></i> Contact Messages</h2>
            </div>
            
            <div class="admin-content">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php if (empty($comments)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No messages found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><?= $comment['id']; ?></td>
                                    <td><?= htmlspecialchars($comment['name']); ?></td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($comment['email']); ?>">
                                            <?= htmlspecialchars($comment['email']); ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($comment['subject']); ?></td>
                                    <td>
                                        <div class="message-preview">
                                            <?= htmlspecialchars(substr($comment['message'], 0, 100)); ?>
                                            <?= strlen($comment['message']) > 100 ? '...' : ''; ?>
                                        </div>
                                        <?php if (strlen($comment['message']) > 100): ?>
                                            <button class="btn btn-sm btn-outline" onclick="showFullMessage(<?= $comment['id']; ?>)">
                                                View Full
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $comment['is_read'] ? 'success' : 'warning'; ?>">
                                            <?= $comment['is_read'] ? 'Read' : 'Unread'; ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($comment['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (!$comment['is_read']): ?>
                                                <form method="POST" style="display: inline;" class="action-form">
                                                    <input type="hidden" name="comment_id" value="<?= $comment['id']; ?>">
                                                    <input type="hidden" name="action" value="mark_read">
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            onclick="return handleButtonClick(this)"
                                                            data-action="mark_read" 
                                                            data-type="message" 
                                                            data-name="<?= htmlspecialchars($comment['name']); ?>">
                                                        Mark Read
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;" class="action-form">
                                                <input type="hidden" name="comment_id" value="<?= $comment['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="return handleButtonClick(this)"
                                                        data-action="delete" 
                                                        data-type="message" 
                                                        data-name="<?= htmlspecialchars($comment['name']); ?>">
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

<!-- Message Modal -->
<div id="messageModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-envelope"></i> Full Message</h3>
            <span class="close" onclick="closeMessageModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="messageContent"></div>
        </div>
    </div>
</div>

<script>
function showFullMessage(commentId) {
    // Find the comment data
    const comments = <?= json_encode($comments); ?>;
    const comment = comments.find(c => c.id == commentId);
    
    if (comment) {
        document.getElementById('messageContent').innerHTML = 
            '<strong>From:</strong> ' + comment.name + '<br>' +
            '<strong>Email:</strong> ' + comment.email + '<br>' +
            '<strong>Subject:</strong> ' + comment.subject + '<br><br>' +
            '<strong>Message:</strong><br>' + comment.message.replace(/\n/g, '<br>');
        
        document.getElementById('messageModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeMessageModal() {
    document.getElementById('messageModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Handle button clicks with confirmation
function handleButtonClick(button) {
    const action = button.getAttribute('data-action');
    const type = button.getAttribute('data-type');
    const name = button.getAttribute('data-name');
    
    let message = '';
    
    if (action === 'mark_read' && type === 'message') {
        message = `Are you sure you want to mark the message from "${name}" as read?`;
    } else if (action === 'delete' && type === 'message') {
        message = `Are you sure you want to delete the message from "${name}"?\n\nThis action cannot be undone.`;
    }
    
    if (confirm(message)) {
        button.closest('form').submit();
    }
    return false;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('messageModal');
    if (event.target === modal) {
        closeMessageModal();
    }
}

// Prevent modal from closing when clicking inside modal content
document.querySelector('.modal-content').addEventListener('click', function(event) {
    event.stopPropagation();
});
</script>

<?php include '../includes/admin_footer.php'; ?>
