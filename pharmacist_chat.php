<?php
// Start session for user authentication
session_start();

// Check if user is logged in and is a pharmacist
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'pharmacist') {
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'db_connect.php';

// Get pharmacist ID from session
$pharmacist_id = $_SESSION['user_id'];

// Check if customer ID is provided
if (!isset($_GET['customer_id'])) {
    header("Location: dashboard.php");
    exit();
}

$customer_id = $_GET['customer_id'];

// Fetch customer details
$stmt = $conn->prepare("SELECT username, full_name FROM users WHERE user_id = ? AND user_type = 'customer'");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$customer = $result->fetch_assoc();

// Fetch messages sent from customer to pharmacist
$stmt = $conn->prepare("SELECT * FROM messages 
                        WHERE sender = ? AND receiver = ? 
                        ORDER BY timestamp DESC");
$stmt->bind_param("ss", $customer_id, $pharmacist_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mark unread messages as read
$update_stmt = $conn->prepare("UPDATE messages SET status = 'read' 
                              WHERE sender = ? AND receiver = ? AND status != 'read'");
$update_stmt->bind_param("ss", $customer_id, $pharmacist_id);
$update_stmt->execute();

// Close the connection
$stmt->close();
$update_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages from <?= htmlspecialchars($customer['full_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #3eed2b;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
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
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .message-item {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-content {
            background-color: #f0f7ff;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
        }
        
        .message-time {
            font-size: 0.8rem;
            color: #777;
        }
        
        .message-type-badge {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 10px;
            background-color: #e9ecef;
        }
        
        .message-type-image {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .message-type-file {
            background-color: #cfe2ff;
            color: #084298;
        }
        
        .no-messages {
            text-align: center;
            padding: 40px 0;
            color: #777;
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .customer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 1.2rem;
        }
        
        .back-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-btn">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="customer-info">
            <div class="customer-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <h2 class="page-title m-0">Messages from <?= htmlspecialchars($customer['full_name']) ?></h2>
                <p class="text-muted"><?= htmlspecialchars($customer['username']) ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-comments me-2 text-primary"></i>
                    Message History
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                        <h5>No messages received</h5>
                        <p>This customer hasn't sent you any messages yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="message-time">
                                        <i class="far fa-clock me-1"></i>
                                        <?= date('F j, Y, g:i a', strtotime($message['timestamp'])) ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="message-type-badge 
                                           <?= $message['message_type'] === 'image' ? 'message-type-image' : '' ?>
                                           <?= $message['message_type'] === 'file' ? 'message-type-file' : '' ?>">
                                        <?php if ($message['message_type'] === 'text'): ?>
                                            <i class="fas fa-comment me-1"></i> Text
                                        <?php elseif ($message['message_type'] === 'image'): ?>
                                            <i class="fas fa-image me-1"></i> Image
                                        <?php elseif ($message['message_type'] === 'file'): ?>
                                            <i class="fas fa-file me-1"></i> File
                                        <?php endif; ?>
                                    </span>
                                    <span class="badge bg-<?= $message['status'] === 'read' ? 'success' : 
                                                            ($message['status'] === 'delivered' ? 'info' : 'secondary') ?> ms-2">
                                        <?= ucfirst($message['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="message-content">
                                <?php if ($message['message_type'] === 'text'): ?>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                <?php elseif ($message['message_type'] === 'image'): ?>
                                    <p class="mb-2"><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                    <div class="text-center">
                                        <img src="<?= htmlspecialchars($message['message']) ?>" class="img-fluid rounded" style="max-height: 300px;">
                                    </div>
                                <?php elseif ($message['message_type'] === 'file'): ?>
                                    <p class="mb-2"><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                    <div>
                                        <a href="<?= htmlspecialchars($message['message']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-download me-1"></i> Download File
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="reply.php?customer_id=<?= $customer_id ?>" class="btn btn-primary">
                <i class="fas fa-reply me-2"></i> Reply to Customer
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>