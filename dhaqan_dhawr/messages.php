<?php
// Include initialization file (handles config, session, and DB connection)
require_once __DIR__ . '/includes/init.php';

$page_title = 'Messages';
$base_url = '';

// Require authentication
requireAuth();

$user_id = getCurrentUserId();
$errors = [];
$success_message = '';

// Get conversation ID from URL
$conversation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle starting new conversation from seller page
$start_conversation_with = isset($_GET['start_conversation']) ? (int)$_GET['start_conversation'] : 0;

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message_text = sanitizeInput($_POST['message_text'] ?? '');
    $recipient_id = (int)($_POST['recipient_id'] ?? 0);
    
    if (empty($message_text)) {
        $errors[] = 'Message cannot be empty';
    }
    
    if ($recipient_id <= 0) {
        $errors[] = 'Invalid recipient';
    }
    
    if (empty($errors)) {
        try {
            // Check if conversation exists
            $conv_stmt = $conn->prepare("SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
            $conv_stmt->bind_param("iiii", $user_id, $recipient_id, $recipient_id, $user_id);
            $conv_stmt->execute();
            $conv_result = $conv_stmt->get_result();
            $conversation = $conv_result->fetch_assoc();
            
            if ($conversation) {
                $conversation_id = $conversation['id'];
            } else {
                // Create new conversation
                $new_conv_stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $new_conv_stmt->bind_param("ii", $user_id, $recipient_id);
                if (!$new_conv_stmt->execute()) {
                    error_log("Conversation creation failed: " . $new_conv_stmt->error);
                    throw new Exception("Failed to create conversation: " . $new_conv_stmt->error);
                }
                $conversation_id = $conn->insert_id;
            }
            
            // Insert message
            $msg_stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $msg_stmt->bind_param("iis", $conversation_id, $user_id, $message_text);
            
            if ($msg_stmt->execute()) {
                // Update conversation timestamp
                $update_conv_stmt = $conn->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
                $update_conv_stmt->bind_param("i", $conversation_id);
                $update_conv_stmt->execute();
                
                $success_message = 'Message sent successfully!';
                // Redirect to refresh the page and show the new message
                header('Location: messages.php?id=' . $conversation_id);
                exit();
            } else {
                error_log("Message insert failed: " . $msg_stmt->error);
                $errors[] = 'Failed to send message: ' . $msg_stmt->error;
            }
        } catch (Exception $e) {
            error_log("Message send error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $errors[] = 'An error occurred while sending the message: ' . $e->getMessage();
        }
    }
}

// Handle starting new conversation
if ($start_conversation_with > 0) {
    try {
        // Check if conversation already exists
        $existing_conv_stmt = $conn->prepare("SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
        $existing_conv_stmt->bind_param("iiii", $user_id, $start_conversation_with, $start_conversation_with, $user_id);
        $existing_conv_stmt->execute();
        $existing_conv_result = $existing_conv_stmt->get_result();
        $existing_conversation = $existing_conv_result->fetch_assoc();
        
        if ($existing_conversation) {
            // Redirect to existing conversation
            header('Location: messages.php?id=' . $existing_conversation['id']);
            exit();
        } else {
            // Create new conversation
            $new_conv_stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $new_conv_stmt->bind_param("ii", $user_id, $start_conversation_with);
            $new_conv_stmt->execute();
            $new_conversation_id = $conn->insert_id;
            
            // Redirect to new conversation
            header('Location: messages.php?id=' . $new_conversation_id);
            exit();
        }
    } catch (Exception $e) {
        error_log("New conversation error: " . $e->getMessage());
        $errors[] = 'Failed to start conversation. Please try again.';
    }
}

// Fetch conversations for the current user
try {
    $conv_sql = "SELECT c.id, c.created_at, c.updated_at,
                  u1.id as user1_id, u1.name as user1_name,
                  u2.id as user2_id, u2.name as user2_name,
                  (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.read_at IS NULL AND m.sender_id != ?) as unread_count,
                  (SELECT m.message FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message
                  FROM conversations c
                  JOIN users u1 ON c.user1_id = u1.id
                  JOIN users u2 ON c.user2_id = u2.id
                  WHERE c.user1_id = ? OR c.user2_id = ?
                  ORDER BY c.updated_at DESC";
    $conv_stmt = $conn->prepare($conv_sql);
    $conv_stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $conv_stmt->execute();
    $conv_result = $conv_stmt->get_result();
    $conversations = [];
    
    while ($row = $conv_result->fetch_assoc()) {
        // Determine the other user in the conversation
        if ($row['user1_id'] == $user_id) {
            $row['other_user_id'] = $row['user2_id'];
            $row['other_user_name'] = $row['user2_name'];
        } else {
            $row['other_user_id'] = $row['user1_id'];
            $row['other_user_name'] = $row['user1_name'];
        }
        $conversations[] = $row;
    }
} catch (Exception $e) {
    error_log("Conversations fetch error: " . $e->getMessage());
    $conversations = [];
}

// Fetch messages for specific conversation
$messages = [];
$current_conversation = null;
if ($conversation_id > 0) {
    try {
        // Get conversation details
        $conv_detail_sql = "SELECT c.*, 
                           u1.id as user1_id, u1.name as user1_name,
                           u2.id as user2_id, u2.name as user2_name
                           FROM conversations c
                           JOIN users u1 ON c.user1_id = u1.id
                           JOIN users u2 ON c.user2_id = u2.id
                           WHERE c.id = ? AND (c.user1_id = ? OR c.user2_id = ?)";
        $conv_detail_stmt = $conn->prepare($conv_detail_sql);
        $conv_detail_stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
        $conv_detail_stmt->execute();
        $conv_detail_result = $conv_detail_stmt->get_result();
        $current_conversation = $conv_detail_result->fetch_assoc();
        
        if ($current_conversation) {
            // Mark messages as read
            $read_stmt = $conn->prepare("UPDATE messages SET read_at = NOW() WHERE conversation_id = ? AND sender_id != ? AND read_at IS NULL");
            $read_stmt->bind_param("ii", $conversation_id, $user_id);
            $read_stmt->execute();
            
            // Fetch messages
            $msg_sql = "SELECT m.*, u.name as sender_name 
                       FROM messages m
                       JOIN users u ON m.sender_id = u.id
                       WHERE m.conversation_id = ?
                       ORDER BY m.created_at ASC";
            $msg_stmt = $conn->prepare($msg_sql);
            $msg_stmt->bind_param("i", $conversation_id);
            $msg_stmt->execute();
            $msg_result = $msg_stmt->get_result();
            
            while ($row = $msg_result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Messages fetch error: " . $e->getMessage());
    }
}

// Check if user is seller to determine layout
$user_role = getCurrentUserRole();
$is_seller = ($user_role === 'seller');

if ($is_seller) {
    include 'includes/header.php';
} else {
    include 'includes/buyer_header.php';
}
?>

<?php if ($is_seller): ?>
    <?php include 'includes/seller_sidebar.php'; ?>
    <div class="seller-layout">
        <main class="seller-main">
            <div class="container">
                <div class="admin-header">
                    <h1><i class="fas fa-envelope"></i> Messages</h1>
                    <p>Communicate with buyers and sellers.</p>
                </div>
<?php else: ?>
    <main class="messages-main">
        <div class="container">
            <div class="messages-container">
                <h1>Messages</h1>
<?php endif; ?>
            
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

            <div class="messages-layout" style="margin-top: 1.5rem;">
                <!-- Conversations List -->
                <div class="conversations-panel">
                    <h3>Chat List</h3>
                    
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <p>No conversations yet.</p>
                            <p>Start messaging with sellers or buyers!</p>
                        </div>
                    <?php else: ?>
                        <div class="conversations-list">
                            <?php foreach ($conversations as $conv): ?>
                                <a href="messages.php?id=<?= $conv['id']; ?>" 
                                   class="conversation-item <?= $conversation_id == $conv['id'] ? 'active' : ''; ?>">
                                    <div class="conversation-avatar">
                                        <div class="avatar-circle">
                                            <?= strtoupper(substr($conv['other_user_name'], 0, 1)); ?>
                                        </div>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="online-indicator"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-header">
                                            <h4><?= htmlspecialchars($conv['other_user_name']); ?></h4>
                                            <span class="conversation-time"><?= date('g:i A', strtotime($conv['updated_at'])); ?></span>
                                        </div>
                                        <p class="last-message"><?= htmlspecialchars(substr($conv['last_message'] ?? 'No messages yet', 0, 50)) . (strlen($conv['last_message'] ?? '') > 50 ? '...' : ''); ?></p>
                                    </div>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?= $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Messages Panel -->
                <div class="messages-panel">
                    <?php if ($current_conversation): ?>
                        <!-- Conversation Header -->
                        <div class="chat-header">
                            <div class="chat-user-info">
                                <div class="chat-avatar">
                                    <?php 
                                    $other_user_name = $current_conversation['user1_id'] == $user_id ? $current_conversation['user2_name'] : $current_conversation['user1_name'];
                                    echo strtoupper(substr($other_user_name, 0, 1));
                                    ?>
                                </div>
                                <div class="chat-user-details">
                                    <h3><?= htmlspecialchars($other_user_name); ?></h3>
                                    <span class="user-status">Online</span>
                                </div>
                            </div>
                            <div class="chat-actions">
                                <button class="chat-action-btn" title="More options">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="messages-list" id="messages-list">
                            <?php if (empty($messages)): ?>
                                <div class="empty-state">
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message <?= $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                        <?php if ($message['sender_id'] != $user_id): ?>
                                            <div class="message-avatar">
                                                <?= strtoupper(substr($message['sender_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="message-content">
                                            <div class="message-bubble">
                                                <p><?= nl2br(htmlspecialchars($message['message'])); ?></p>
                                            </div>
                                            <span class="message-time"><?= date('g:i A', strtotime($message['created_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Message Input -->
                        <form method="POST" action="" class="message-input-form">
                            <input type="hidden" name="recipient_id" value="<?= $current_conversation['user1_id'] == $user_id ? $current_conversation['user2_id'] : $current_conversation['user1_id']; ?>">
                            <div class="input-container">
                                <button type="button" class="attachment-btn" title="Attach file">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                <textarea name="message_text" placeholder="Type a message..." required class="message-input" rows="1" onkeydown="handleEnterKey(event)"></textarea>
                                <button type="submit" name="send_message" class="send-btn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- No Conversation Selected -->
                        <div class="no-conversation">
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <h3>Select a Conversation</h3>
                                <p>Choose a conversation from the list to start messaging.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php if ($is_seller): ?>
            </div>
        </main>
    </div>
<?php else: ?>
    </div>
</main>
<?php endif; ?>

<script>
// Auto-scroll to bottom of messages
document.addEventListener('DOMContentLoaded', function() {
    const messagesList = document.getElementById('messages-list');
    if (messagesList) {
        messagesList.scrollTop = messagesList.scrollHeight;
    }
});

// Handle Enter key for sending messages
function handleEnterKey(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        event.target.closest('form').submit();
    }
}

// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.querySelector('.message-input');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }
});
</script>

<style>
/* Modern Chat UI Styles */
.messages-main {
    padding: 2rem 0;
    min-height: 90vh;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
}

.messages-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}

.messages-container h1 {
    text-align: center;
    color: white;
    margin-bottom: 2rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.messages-layout {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 0;
    height: 85vh;
    box-shadow: 0 25px 80px rgba(0,0,0,0.15);
    border-radius: 16px;
    overflow: hidden;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    border: 1px solid #e1e5e9;
}

.conversations-sidebar {
    width: 380px;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e1e5e9;
}

.conversations-sidebar h2 {
    color: #2c3e50;
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

/* Conversations Panel */
.conversations-panel {
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e1e5e9;
}

.conversations-panel h3 {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    margin: 0;
    padding: 1rem 1.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 0;
}

/* Conversations List */
.conversations-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem 0;
}

.conversation-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.2s ease;
    position: relative;
    margin: 0;
    border-radius: 0;
    background: white;
    border-bottom: 1px solid #f0f2f5;
}

.conversation-item:hover {
    background: #f8f9fa;
}

.conversation-item.active {
    background: #e3f2fd;
    color: #1976d2;
    border-left: 3px solid #1976d2;
}

.conversation-avatar {
    position: relative;
    margin-right: 1rem;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #1976d2;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 500;
    font-size: 1rem;
}

.conversation-item.active .avatar-circle {
    background: #1976d2;
    color: white;
}

.online-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    background: #f39c12;
    border-radius: 50%;
    border: 2px solid white;
}

.conversation-info {
    flex: 1;
    min-width: 0;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}

.conversation-info h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: inherit;
}

.conversation-time {
    font-size: 0.75rem;
    opacity: 0.7;
}

.last-message {
    margin: 0;
    font-size: 0.85rem;
    opacity: 0.8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.unread-badge {
    background: #f39c12;
    color: white;
    border-radius: 50%;
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    min-width: 20px;
    text-align: center;
    font-weight: bold;
    margin-left: 0.5rem;
}

/* Messages Panel */
.messages-panel {
    display: flex;
    flex-direction: column;
    background: white;
}

.chat-header {
    background: white;
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.chat-user-info {
    display: flex;
    align-items: center;
}

.chat-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.chat-user-details h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    color: #333;
}

.user-status {
    font-size: 0.8rem;
    color: #f39c12;
    font-weight: 500;
}

.chat-action-btn {
    background: none;
    border: none;
    color: #666;
    font-size: 1.2rem;
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chat-action-btn:hover {
    background: #f8f9fa;
    color: #333;
}

/* Messages List */
.messages-list {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fc 100%);
    background-image: 
        radial-gradient(circle at 25px 25px, rgba(44, 62, 80, 0.03) 2%, transparent 0%),
        radial-gradient(circle at 75px 75px, rgba(243, 156, 18, 0.02) 2%, transparent 0%);
    background-size: 100px 100px;
}

.message {
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-end;
}

.message.sent {
    justify-content: flex-end;
}

.message.received {
    justify-content: flex-start;
}

.message-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #1976d2;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    font-weight: 500;
    margin-right: 0.5rem;
    flex-shrink: 0;
}

.message-content {
    max-width: 60%;
    display: flex;
    flex-direction: column;
}

.message.sent .message-content {
    align-items: flex-end;
}

.message.received .message-content {
    align-items: flex-start;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: 1rem 1.25rem;
    margin-bottom: 0.5rem;
}

.message-bubble {
    padding: 0;
    border-radius: 0;
    position: relative;
    word-wrap: break-word;
    max-width: 100%;
    font-size: 0.95rem;
    line-height: 1.4;
    background: none !important;
    box-shadow: none;
    border: none;
    color: #ffffff !important;
}

.message.sent .message-bubble {
    background: none !important;
    color: #ffffff !important;
    border-radius: 0;
    box-shadow: none;
    font-weight: 500;
}

.message.received .message-bubble {
    background: none !important;
    color: #1976d2 !important;
    border-radius: 0;
    box-shadow: none;
    font-weight: 500;
    padding: 0;
}

.message-bubble p {
    margin: 0;
    line-height: 1.5;
}

.message.sent .message-bubble p {
    color: #ffffff !important;
}

.message.received .message-bubble p {
    color: #1976d2 !important;
}

.message.sent .message-time {
    font-size: 0.75rem;
    opacity: 0.9;
    margin-top: 0.5rem;
    padding: 0;
    color: #ffffff !important;
}

.message.received .message-time {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 0;
    padding: 0 1.25rem;
    color: #666 !important;
}

/* Message Input */
.message-input-form {
    padding: 1.5rem;
    background: white;
    border-top: 1px solid #e1e5e9;
}

.input-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #f8f9fa;
    border-radius: 24px;
    padding: 0.5rem 1rem;
    border: 1px solid #e1e5e9;
    transition: all 0.2s ease;
}

.input-container:focus-within {
    border-color: #1976d2;
    background: white;
}

.attachment-btn {
    background: none;
    border: none;
    color: #666;
    font-size: 1.2rem;
    padding: 0.75rem;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.attachment-btn:hover {
    background: #e9ecef;
    color: #333;
}

.message-input {
    flex: 1;
    border: none;
    outline: none;
    resize: none;
    font-size: 0.95rem;
    line-height: 1.4;
    padding: 0.5rem 0;
    background: transparent;
    color: #333;
    font-family: inherit;
    min-height: 20px;
    max-height: 100px;
}

.send-button {
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1rem;
}

.send-button:hover {
    background: #1565c0;
    transform: scale(1.05);
}

/* Empty States */
.no-conversation {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    background: #f8f9fc;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #666;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.3;
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-weight: 500;
}

.empty-state p {
    margin: 0;
    opacity: 0.8;
}

/* Scrollbar Styling */
.conversations-list::-webkit-scrollbar,
.messages-list::-webkit-scrollbar {
    width: 6px;
}

.conversations-list::-webkit-scrollbar-track,
.messages-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.conversations-list::-webkit-scrollbar-thumb,
.messages-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.conversations-list::-webkit-scrollbar-thumb:hover,
.messages-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive Design */
@media (max-width: 768px) {
    .messages-layout {
        grid-template-columns: 1fr;
        height: 85vh;
    }
    
    .conversations-panel {
        display: none;
    }
    
    .messages-panel {
        border-radius: 20px;
    }
    
    .messages-container {
        padding: 0 0.5rem;
    }
    
    .chat-header {
        padding: 1rem;
    }
    
    .messages-list {
        padding: 1rem;
    }
    
    .message-input-form {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .message-content {
        max-width: 85%;
    }
    
    .input-container {
        padding: 0.25rem;
    }
    
    .attachment-btn,
    .send-btn {
        width: 40px;
        height: 40px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>


