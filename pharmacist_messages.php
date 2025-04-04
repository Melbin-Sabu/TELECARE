<?php
session_start();
include 'db_connect.php';

// Get all conversations
$sql = "SELECT c.id, c.user_id, c.pharmacist_id, 
        (SELECT full_name FROM signup WHERE id = c.user_id) as user_name,
        MAX(m.created_at) as last_message_time,
        (SELECT message FROM chat_messages 
         WHERE conversation_id = c.id 
         ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM chat_messages 
         WHERE conversation_id = c.id AND sender_type = 'user' AND is_read = 0) as unread_count
        FROM chat_conversations c
        LEFT JOIN chat_messages m ON c.id = m.conversation_id
        GROUP BY c.id
        ORDER BY last_message_time DESC";
$result = $conn->query($sql);
$conversations = [];
if ($result) {
    $conversations = $result->fetch_all(MYSQLI_ASSOC);
}

// Get active conversation
$active_conversation_id = null;
$active_user_name = "";
$messages = [];

if (isset($_GET['conversation_id'])) {
    $active_conversation_id = $_GET['conversation_id'];
    
    // Get user info
    $sql = "SELECT c.user_id, 
            (SELECT full_name FROM signup WHERE id = c.user_id) as user_name 
            FROM chat_conversations c
            WHERE c.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $active_conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $active_user_name = $result->fetch_assoc()['user_name'];
        
        // Mark messages as read
        $sql = "UPDATE chat_messages 
                SET is_read = 1 
                WHERE conversation_id = ? AND sender_type = 'user'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $active_conversation_id);
        $stmt->execute();
        
        // Get messages
        $sql = "SELECT id, sender_type, message, created_at, is_read 
                FROM chat_messages 
                WHERE conversation_id = ? 
                ORDER BY created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $active_conversation_id);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle sending message
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $conversation_id = $_POST['conversation_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        // Get pharmacist ID from conversation
        $sql = "SELECT pharmacist_id FROM chat_conversations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $pharmacist_id = $result->fetch_assoc()['pharmacist_id'];
            
            // Insert message
            $sql = "INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message) 
                    VALUES (?, 'pharmacist', ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $conversation_id, $pharmacist_id, $message);
            
            if ($stmt->execute()) {
                // Update conversation timestamp
                $sql = "UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $conversation_id);
                $stmt->execute();
                
                // Redirect to avoid form resubmission
                header("Location: pharmacist_messages.php?conversation_id=" . $conversation_id);
                exit();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Messages</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #3eed2b;
            --accent-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --card-shadow: 0 4px 8px rgba(0,0,0,0.1);
            --sidebar-bg: rgb(17, 172, 37);
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            line-height: 1.6;
        }
        
        .navbar {
            background-color: var(--sidebar-bg);
            color: white;
        }
        
        .navbar-brand {
            color: white;
            font-weight: 600;
        }
        
        .navbar-brand:hover {
            color: white;
        }
        
        .page-title {
            margin: 20px 0;
            color: var(--dark-text);
            font-weight: 600;
        }
        
        .chat-container {
            display: flex;
            height: calc(100vh - 120px);
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .chat-sidebar {
            width: 300px;
            background-color: #f5f5f5;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }
        
        .chat-sidebar-header {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            background-color: var(--sidebar-bg);
            color: white;
        }
        
        .chat-sidebar-content {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .conversation-item:hover {
            background-color: #e9e9e9;
        }
        
        .conversation-item.active {
            background-color: #e0f0ff;
        }
        
        .conversation-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--sidebar-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-name {
            font-weight: 600;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 0.8rem;
            color: var(--light-text);
        }
        
        .unread-badge {
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .message {
            max-width: 70%;
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 10px;
            position: relative;
        }
        
        .message.sent {
            align-self: flex-end;
            background-color: var(--sidebar-bg);
            color: white;
            border-bottom-right-radius: 0;
        }
        
        .message.received {
            align-self: flex-start;
            background-color: #f0f0f0;
            border-bottom-left-radius: 0;
        }
        
        .message-content {
            word-wrap: break-word;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: rgba(0, 0, 0, 0.5);
            margin-top: 5px;
            opacity: 0.8;
            text-align: right;
        }
        
        .message.sent .message-time {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .chat-input {
            padding: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .chat-input form {
            display: flex;
        }
        
        .chat-input textarea {
            resize: none;
            overflow: hidden;
            min-height: 38px;
            max-height: 120px;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--light-text);
            text-align: center;
            padding: 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #d0d0d0;
        }
        
        .read-status {
            font-size: 0.7rem;
            color: var(--light-text);
            margin-top: 2px;
        }
        
        @media (max-width: 992px) {
            .chat-container {
                flex-direction: column;
                height: auto;
            }
            
            .chat-sidebar {
                width: 100%;
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-heartbeat me-2"></i>
                HealthTrack Pharmacist Portal
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h2 class="text-center page-title">
            <i class="fas fa-comments me-2"></i>
            Patient Messages
        </h2>

        <div class="chat-container">
            <!-- Chat Sidebar -->
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <h5 class="mb-0">Patient Conversations</h5>
                </div>
                <div class="chat-sidebar-content">
                    <?php if (empty($conversations)): ?>
                        <div class="p-3 text-center text-muted">
                            <p>No conversations yet</p>
                            <p>Patients will appear here when they message you</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conversation): ?>
                            <a href="pharmacist_messages.php?conversation_id=<?= $conversation['id'] ?>" class="text-decoration-none">
                                <div class="conversation-item <?= ($active_conversation_id == $conversation['id']) ? 'active' : '' ?>">
                                    <div class="conversation-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-name"><?= htmlspecialchars($conversation['user_name']) ?></div>
                                        <div class="conversation-time">
                                            <?= $conversation['last_message_time'] ? date('M d, h:i A', strtotime($conversation['last_message_time'])) : 'No messages yet' ?>
                                        </div>
                                    </div>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <div class="unread-badge"><?= $conversation['unread_count'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Main Area -->
            <div class="chat-main">
                <?php if ($active_conversation_id): ?>
                    <div class="chat-header">
                        <div class="conversation-avatar me-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($active_user_name) ?></h5>
                            <small class="text-muted">Patient</small>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted my-4">
                                <p>No messages yet</p>
                                <p>Start the conversation by sending a message</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message <?= $message['sender_type'] == 'pharmacist' ? 'sent' : 'received' ?>">
                                    <div class="message-content">
                                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                                    </div>
                                    <div class="message-time">
                                        <?= date('M d, h:i A', strtotime($message['created_at'])) ?>
                                        <?php if ($message['sender_type'] == 'pharmacist' && $message['is_read']): ?>
                                            <span class="read-status">✓ Read</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="chat-input">
                        <form method="POST" class="d-flex">
                            <input type="hidden" name="conversation_id" value="<?= $active_conversation_id ?>">
                            <textarea name="message" class="form-control me-2" placeholder="Type your message..." rows="1" required></textarea>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h4>No conversation selected</h4>
                        <p>Select a conversation from the sidebar to view messages</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chatMessages');
            
            // Scroll chat to bottom
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Auto-resize textarea
            const textarea = document.querySelector('textarea[name="message"]');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
            
            // Auto-refresh the page every 30 seconds to show new messages
            setInterval(function() {
                const currentConversationId = new URLSearchParams(window.location.search).get('conversation_id');
                if (currentConversationId) {
                    window.location.href = 'pharmacist_messages.php?conversation_id=' + currentConversationId;
                } else {
                    window.location.href = 'pharmacist_messages.php';
                }
            }, 30000);
        });
    </script>
</body>
</html> 