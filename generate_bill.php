<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "telecare+";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get order ID from URL parameter
if (!isset($_GET['order_id'])) {
    die("Order ID is required");
}
$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Verify this order belongs to the logged-in user
$sql = "SELECT o.*, s.full_name, s.email, s.permanent_address, s.place 
        FROM orders o 
        JOIN signup s ON o.user_id = s.id 
        WHERE o.id = ? AND o.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found or access denied");
}

$order = $result->fetch_assoc();

// Get order items
$sql = "SELECT oi.*, m.name 
        FROM order_items oi 
        JOIN medicines m ON oi.medicine_id = m.id 
        WHERE oi.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Format order date
$orderDate = isset($order['created_at']) ? date('F d, Y', strtotime($order['created_at'])) : date('F d, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_id; ?> - Telecare+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
        }
        .invoice-container {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .invoice-header h1 {
            color: #4CAF50;
            margin-bottom: 5px;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        .col-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding-right: 15px;
            padding-left: 15px;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        .invoice-details h5 {
            color: #4CAF50;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .invoice-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 0.9em;
        }
        .btn-container {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 0 10px;
            cursor: pointer;
            border: none;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn i {
            margin-right: 5px;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .invoice-container {
                box-shadow: none;
                margin: 0;
                padding: 15px;
            }
            .btn-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1>INVOICE</h1>
            <p>Invoice #: <?php echo $order_id; ?></p>
            <p>Date: <?php echo $orderDate; ?></p>
        </div>
        
        <div class="row">
            <div class="col-6">
                <div class="invoice-details">
                    <h5>From</h5>
                    <p>
                        <strong>Telecare+ Pharmacy</strong><br>
                        123 Health Street<br>
                        City, State, ZIP<br>
                        Phone: (123) 456-7890<br>
                        Email: pharmacy@telecare.com
                    </p>
                </div>
            </div>
            <div class="col-6">
                <div class="invoice-details">
                    <h5>Bill To</h5>
                    <p>
                        <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                        <?php echo htmlspecialchars($order['permanent_address']); ?><br>
                        <?php echo htmlspecialchars($order['place']); ?><br>
                        Email: <?php echo htmlspecialchars($order['email']); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($items as $item): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4" class="text-right">Total:</td>
                    <td>$<?php echo number_format($total, 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="invoice-details">
            <h5>Payment Information</h5>
            <p>
                <strong>Payment Method:</strong> Online Payment<br>
                <strong>Payment ID:</strong> <?php echo isset($order['payment_id']) ? htmlspecialchars($order['payment_id']) : 'N/A'; ?><br>
                <strong>Status:</strong> <?php echo isset($order['status']) ? htmlspecialchars($order['status']) : 'Completed'; ?>
            </p>
        </div>
        
        <div class="invoice-footer">
            <p>Thank you for your purchase!</p>
            <p>For any questions regarding this invoice, please contact our customer service.</p>
            <p>Telecare+ Pharmacy | 123 Health Street, City, State | Phone: (123) 456-7890</p>
            <p>Email: pharmacy@telecare.com | Website: www.telecareplus.com</p>
        </div>
        
        <div class="btn-container">
            <button onclick="window.print()" class="btn">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?> 