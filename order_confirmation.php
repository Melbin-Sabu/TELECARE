<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "telecare+";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Get cart items before clearing
$sql = "SELECT c.*, m.name, m.price FROM cart c JOIN medicines m ON c.medicine_id = m.id WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_amount = array_sum(array_map(function($item) {
    return $item['price'] * $item['quantity'];
}, $cart_items));

// Assuming payment was successful and we have a payment ID from Razorpay
$payment_id = isset($GET['payment_id']) ? $_GET['payment_id'] : 'test_payment' . time();

// Save order to database
$sql = "INSERT INTO orders (user_id, total_amount, payment_id, status) VALUES (?, ?, ?, 'completed')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ids", $user_id, $total_amount, $payment_id);
$stmt->execute();
$order_id = $conn->insert_id;
$stmt->close();

// Save order items
foreach ($cart_items as $item) {
    $sql = "INSERT INTO order_items (order_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiid", $order_id, $item['medicine_id'], $item['quantity'], $item['price']);
    $stmt->execute();
    $stmt->close();
}

// Clear the cart
$sql = "DELETE FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Telecare+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <style>
        .sidebar {
            width: 250px;
            background: #4CAF50;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px;
            color: white;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .sidebar a {
            display: block;
            padding: 10px;
            background: white;
            color: #4CAF50;
            text-align: center;
            font-weight: bold;
            border-radius: 8px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background: #388E3C;
            color: white;
        }
        .main-content {
            margin-left: 270px;
            padding: 20px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="sidebar">
        <h2 class="text-xl font-bold text-white">TELECARE+</h2>
        <a href="#">📤 Upload Prescription</a>
        <a href="healthmonito.php">📊 Health Monitoring</a>
        <a href="ordermedi.php">🛒 Order Medicines</a>
        <a href="cart.php">🛍 Cart</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="main-content">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Order Confirmation</h1>
        
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Order Placed Successfully!</strong>
            <span class="block sm:inline"> Thank you for your purchase. Your order has been confirmed.</span>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-bold text-gray-700 mb-4">Order Details</h2>
            <p><strong>Order ID:</strong> <?php echo $order_id; ?></p>
            <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($payment_id); ?></p>
            <p><strong>Total Amount:</strong> $<?php echo number_format($total_amount, 2); ?></p>
            <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

            <table class="w-full border-collapse mt-4">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-4 py-2 text-left">Medicine</th>
                        <th class="px-4 py-2 text-left">Price</th>
                        <th class="px-4 py-2 text-left">Quantity</th>
                        <th class="px-4 py-2 text-left">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr class="border-t">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="px-4 py-2">$<?php echo number_format($item['price'], 2); ?></td>
                            <td class="px-4 py-2"><?php echo $item['quantity']; ?></td>
                            <td class="px-4 py-2">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <a href="ordermedi.php" class="mt-6 inline-block bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                Continue Shopping
            </a>

            <div class="mt-4">
                <a href="generate_bill.php?order_id=<?php echo $order_id; ?>" class="inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    <i class="fas fa-file-download mr-2"></i>Download Invoice
                </a>
            </div>
        </div>
    </div>
</body>
</html>
