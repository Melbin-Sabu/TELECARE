<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

// Check if necessary tables exist and create them if they don't
function create_tables_if_not_exist($conn) {
    // Check if chat_conversations table exists
    $result = $conn->query("SHOW TABLES LIKE 'chat_conversations'");
    if ($result->num_rows == 0) {
        // Create the chat_conversations table
        $sql = "CREATE TABLE chat_conversations (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            pharmacist_id INT(11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        
        if (!$conn->query($sql)) {
            die("Error creating chat_conversations table: " . $conn->error);
        }
    }
    
    // Check if chat_messages table exists
    $result = $conn->query("SHOW TABLES LIKE 'chat_messages'");
    if ($result->num_rows == 0) {
        // Create the chat_messages table
        $sql = "CREATE TABLE chat_messages (
            id INT(11) NOT NULL AUTO_INCREMENT,
            conversation_id INT(11) NOT NULL,
            sender_type ENUM('user', 'pharmacist') NOT NULL,
            sender_id INT(11) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        
        if (!$conn->query($sql)) {
            die("Error creating chat_messages table: " . $conn->error);
        }
    }
}

// Create tables if they don't exist
create_tables_if_not_exist($conn);

// Fetch all pharmacists - Removed the status filter
$sql = "SELECT id, name, qualification FROM pharmacists";
$pharmacists_result = $conn->query($sql);
$pharmacists = $pharmacists_result->fetch_all(MYSQLI_ASSOC);

// Get active conversation or create new one
$active_conversation_id = null;
$active_pharmacist_id = null;
$active_pharmacist_name = "";

if (isset($_GET['pharmacist_id'])) {
    $pharmacist_id = $_GET['pharmacist_id'];
    
    // Check if conversation already exists
    $sql = "SELECT id FROM chat_conversations 
            WHERE user_id = ? AND pharmacist_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $pharmacist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $active_conversation_id = $result->fetch_assoc()['id'];
    } else {
        // Create new conversation
        $sql = "INSERT INTO chat_conversations (user_id, pharmacist_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $pharmacist_id);
        
        if ($stmt->execute()) {
            $active_conversation_id = $conn->insert_id;
        } else {
            $error_message = "Error creating conversation: " . $conn->error;
        }
    }
    
    // Get pharmacist name
    $sql = "SELECT name FROM pharmacists WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pharmacist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $active_pharmacist_name = $result->fetch_assoc()['name'];
    }
    
    $active_pharmacist_id = $pharmacist_id;
}

// Fetch user's conversations
$sql = "SELECT c.id, c.pharmacist_id, p.name as pharmacist_name, 
        MAX(m.created_at) as last_message_time,
        (SELECT COUNT(*) FROM chat_messages 
         WHERE conversation_id = c.id AND sender_type = 'pharmacist' AND is_read = 0) as unread_count
        FROM chat_conversations c
        JOIN pharmacists p ON c.pharmacist_id = p.id
        LEFT JOIN chat_messages m ON c.id = m.conversation_id
        WHERE c.user_id = ?
        GROUP BY c.id
        ORDER BY last_message_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle sending message
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $conversation_id = $_POST['conversation_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $sql = "INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message) 
                VALUES (?, 'user', ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $conversation_id, $user_id, $message);
        
        if ($stmt->execute()) {
            // Update conversation timestamp
            $sql = "UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $conversation_id);
            $stmt->execute();
            
            // Redirect to avoid form resubmission
            header("Location: chat.php?pharmacist_id=" . $active_pharmacist_id);
            exit();
        } else {
            $error_message = "Error sending message: " . $conn->error;
        }
    }
}

// Fetch messages for active conversation
$messages = [];
if ($active_conversation_id) {
    // Mark messages as read
    $sql = "UPDATE chat_messages 
            SET is_read = 1 
            WHERE conversation_id = ? AND sender_type = 'pharmacist'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $active_conversation_id);
    $stmt->execute();
    
    // Get messages
    $sql = "SELECT id, sender_type, message, created_at 
            FROM chat_messages 
            WHERE conversation_id = ? 
            ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $active_conversation_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Pharmacist</title>
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
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --sidebar-bg: rgb(17, 172, 37);
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(62, 237, 43, 0.2);
            color: var(--dark-text);
            border-left: 4px solid var(--secondary-color);
        }
        
        .sidebar-menu i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-menu .menu-text {
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .menu-text {
            opacity: 0;
            display: none;
        }
        
        .sidebar-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            color: var(--secondary-color);
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0;
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-bg);
        }
        
        /* Chat specific styles */
        .chat-container {
            display: flex;
            height: calc(100vh - 150px);
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .chat-sidebar {
            width: 300px;
            background-color: #f5f5f5;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }
        
        .chat-sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .chat-sidebar-content {
            flex: 1;
            overflow-y: auto;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f9f9f9;
        }
        
        .chat-input {
            padding: 15px;
            border-top: 1px solid #e0e0e0;
            background-color: white;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 70%;
        }
        
        .message-content {
            padding: 10px 15px;
            border-radius: 18px;
            display: inline-block;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #888;
            margin-top: 5px;
        }
        
        .message.sent {
            margin-left: auto;
        }
        
        .message.sent .message-content {
            background-color: var(--primary-color);
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .message.received {
            margin-right: auto;
        }
        
        .message.received .message-content {
            background-color: #e0e0e0;
            color: var(--dark-text);
            border-bottom-left-radius: 5px;
        }
        
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
        }
        
        .conversation-item:hover, .conversation-item.active {
            background-color: #e9e9e9;
        }
        
        .conversation-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--sidebar-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 1.2rem;
        }
        
        .conversation-info {
            flex: 1;
        }
        
        .conversation-name {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .conversation-time {
            font-size: 0.75rem;
            color: #888;
        }
        
        .unread-badge {
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }
        
        .pharmacist-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .pharmacist-item {
            padding: 10px 15px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .pharmacist-item:hover {
            background-color: #e9e9e9;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #888;
            text-align: center;
            padding: 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ccc;
        }
        
        /* Responsive adjustments */
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
        
        @media (max-width: 768px) {
            .sidebar {
                width: var(--sidebar-collapsed-width);
            }
            
            .sidebar.expanded {
                width: var(--sidebar-width);
            }
            
            .main-content {
                margin-left: var(--sidebar-collapsed-width);
            }
            
            .sidebar .menu-text {
                opacity: 0;
                display: none;
            }
            
            .sidebar.expanded .menu-text {
                opacity: 1;
                display: inline;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-heartbeat me-2"></i> <span class="menu-text">Telecare+</span></h3>
            <button class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="t1.php" class="<?= basename($_SERVER['PHP_SELF']) == 't1.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="vital_signs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'vital_signs.php' ? 'active' : '' ?>">
                    <i class="fas fa-heartbeat"></i>
                    <span class="menu-text">Vital Signs</span>
                </a>
            </li>
            <li>
                <a href="book_consultation.php" class="<?= basename($_SERVER['PHP_SELF']) == 'book_consultation.php' ? 'active' : '' ?>">
                    <i class="fas fa-stethoscope"></i>
                    <span class="menu-text">Consultations</span>
                </a>
            </li>
            <li>
                <a href="chat.php" class="<?= basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i>
                    <span class="menu-text">Chat with Pharmacist</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="container">
            <h2 class="text-center page-title">
                <i class="fas fa-comments me-2"></i>
                Chat with Pharmacist
            </h2>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>

            <div class="chat-container">
                <!-- Chat Sidebar -->
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        <h5 class="mb-3">Your Conversations</h5>
                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#newChatModal">
                            <i class="fas fa-plus me-2"></i> New Chat
                        </button>
                    </div>
                    <div class="chat-sidebar-content">
                        <?php if (empty($conversations)): ?>
                            <div class="p-3 text-center text-muted">
                                <p>No conversations yet</p>
                                <p>Start a new chat with a pharmacist</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conversation): ?>
                                <a href="chat.php?pharmacist_id=<?= $conversation['pharmacist_id'] ?>" class="text-decoration-none">
                                    <div class="conversation-item <?= ($active_pharmacist_id == $conversation['pharmacist_id']) ? 'active' : '' ?>">
                                        <div class="conversation-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="conversation-info">
                                            <div class="conversation-name">Dr. <?= htmlspecialchars($conversation['pharmacist_name']) ?></div>
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
                                <h5 class="mb-0">Dr. <?= htmlspecialchars($active_pharmacist_name) ?></h5>
                                <small class="text-muted">Pharmacist</small>
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
                                    <div class="message <?= $message['sender_type'] == 'user' ? 'sent' : 'received' ?>">
                                        <div class="message-content">
                                            <?= nl2br(htmlspecialchars($message['message'])) ?>
                                        </div>
                                        <div class="message-time">
                                            <?= date('M d, h:i A', strtotime($message['created_at'])) ?>
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
                            <p>Select an existing conversation or start a new one</p>
                            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#newChatModal">
                                <i class="fas fa-plus me-2"></i> Start New Chat
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Chat Modal -->
    <div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newChatModalLabel">Start a New Chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Select a Pharmacist</h6>
                    <div class="pharmacist-list">
                        <?php if (empty($pharmacists)): ?>
                            <p class="text-muted">No pharmacists available at this time.</p>
                        <?php else: ?>
                            <?php foreach ($pharmacists as $pharmacist): ?>
                                <a href="chat.php?pharmacist_id=<?= $pharmacist['id'] ?>" class="text-decoration-none">
                                    <div class="pharmacist-item">
                                        <div class="d-flex align-items-center">
                                            <div class="conversation-avatar me-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">Dr. <?= htmlspecialchars($pharmacist['name']) ?></div>
                                                <?php if (!empty($pharmacist['qualification'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($pharmacist['qualification']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const chatMessages = document.getElementById('chatMessages');
            
            // Check for saved state
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            // Apply initial state
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Save state
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
            
            // For mobile
            if (window.innerWidth <= 768) {
                const menuItems = document.querySelectorAll('.sidebar-menu a');
                menuItems.forEach(item => {
                    item.addEventListener('click', function() {
                        if (window.innerWidth <= 768 && sidebar.classList.contains('expanded')) {
                            sidebar.classList.remove('expanded');
                        }
                    });
                });
            }
            
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
        });
    </script>
</body>
</html>