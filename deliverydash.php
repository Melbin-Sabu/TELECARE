<?php
session_start();
include 'db_connection.php';

// Check if delivery boy is logged in
if (!isset($_SESSION['delivery_boy_id'])) {
    header('Location: login.php');
    exit();
}

$boyId = $_SESSION['delivery_boy_id'];

// If clicking 'Mark as Delivered'
if (isset($_GET['deliver'])) {
    $orderId = intval($_GET['deliver']);
    $updateQuery = "UPDATE orders SET status='Delivered', delivery_date=NOW() WHERE id=? AND delivery_boy_id=?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $orderId, $boyId);
    $stmt->execute();
    header('Location: deliverydash.php');
    exit();
}

// Fetch delivery boy's details
$deliveryBoyQuery = "SELECT name FROM delivery_boys WHERE id = ?";
$stmt = $conn->prepare($deliveryBoyQuery);
$stmt->bind_param("i", $boyId);
$stmt->execute();
$deliveryBoy = $stmt->get_result()->fetch_assoc();

// Fetch orders assigned to this delivery boy
$ordersQuery = "SELECT o.*, u.full_name as customer_name, u.permanent_address as customer_address 
                FROM orders o 
                JOIN signup u ON o.user_id = u.id 
                WHERE o.delivery_boy_id = ? 
                ORDER BY o.id DESC";
$stmt = $conn->prepare($ordersQuery);
$stmt->bind_param("i", $boyId);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Boy Dashboard - TELECARE+</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .welcome-message {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e8f4f8;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #2c3e50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status-pending {
            color: #e67e22;
            font-weight: bold;
        }
        .status-delivered {
            color: #27ae60;
            font-weight: bold;
        }
        .action-btn {
            padding: 6px 12px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .action-btn:hover {
            background-color: #219a52;
        }
        .logout-btn {
            padding: 8px 16px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .logout-btn:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-message">
            <h1>Welcome, <?php echo htmlspecialchars($deliveryBoy['name']); ?>!</h1>
            <p>Here are your assigned deliveries for today.</p>
        </div>

        <table>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Address</th>
                <th>Medicines</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td><?= $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td><?= nl2br(htmlspecialchars($order['customer_address'])) ?></td>
                    <td>
                        <?php
                        $orderId = $order['id'];
                        $itemsQuery = "SELECT m.name, oi.quantity, oi.price
                                     FROM order_items oi
                                     INNER JOIN medicines m ON oi.medicine_id = m.id
                                     WHERE oi.order_id = ?";
                        $stmt = $conn->prepare($itemsQuery);
                        $stmt->bind_param("i", $orderId);
                        $stmt->execute();
                        $items = $stmt->get_result();
                        
                        while ($item = $items->fetch_assoc()) {
                            echo htmlspecialchars($item['name']) . " (" . $item['quantity'] . " pcs) - ₹" . $item['price'] . "<br>";
                        }
                        ?>
                    </td>
                    <td class="<?= $order['status'] === 'Delivered' ? 'status-delivered' : 'status-pending' ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </td>
                    <td>
                        <?php if ($order['status'] !== 'Delivered'): ?>
                            <a href="?deliver=<?= $order['id'] ?>" 
                               class="action-btn" 
                               onclick="return confirm('Are you sure you want to mark this order as delivered?')">
                                Mark Delivered
                            </a>
                        <?php else: ?>
                            <span class="status-delivered">✅ Delivered</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</body>
</html>
