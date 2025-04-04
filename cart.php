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

// Update quantity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = $_POST['quantity'];
    if ($quantity > 0) {
        $sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Delete item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_item'])) {
    $cart_id = $_POST['cart_id'];
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Get cart items
$sql = "SELECT c.*, m.name, m.price FROM cart c JOIN medicines m ON c.medicine_id = m.id WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_amount = array_sum(array_map(function($item) {
    return $item['price'] * $item['quantity'];
}, $cart_items));

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Telecare+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Your Cart</h1>

        <?php if (!empty($cart_items)): ?>
            <div class="cart-container p-6 bg-white rounded-lg shadow-md">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="px-4 py-2 text-left">Medicine</th>
                            <th class="px-4 py-2 text-left">Price</th>
                            <th class="px-4 py-2 text-left">Quantity</th>
                            <th class="px-4 py-2 text-left">Total</th>
                            <th class="px-4 py-2 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr class="border-t hover:bg-green-50">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="px-4 py-2">$<?php echo number_format($item['price'], 2); ?></td>
                                <td class="px-4 py-2">
                                    <form method="POST" class="flex items-center">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                            min="0" class="w-16 px-2 py-1 border rounded mr-2">
                                        <button type="submit" name="update_quantity" 
                                            class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">
                                            Update
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-2">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td class="px-4 py-2">
                                    <form method="POST">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_item" 
                                            class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600"
                                            onclick="return confirm('Are you sure you want to remove this item?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="border-t">
                            <td colspan="4" class="px-4 py-2 font-bold">Total Amount:</td>
                            <td class="px-4 py-2 font-bold">$<?php echo number_format($total_amount, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
                <button id="rzp-button" class="mt-4 bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                    Pay with Razorpay
                </button>
            </div>
        <?php else: ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <strong class="font-bold">Empty Cart!</strong>
                <span class="block sm:inline"> Your cart is empty. <a href="ordermedi.php" class="underline">Continue shopping</a></span>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('rzp-button')?.addEventListener('click', function(e) {
            var options = {
                "key": "rzp_test_R6h0atxxQ4WsUU",
                "amount": <?php echo $total_amount * 100; ?>,
                "currency": "INR",
                "name": "Telecare+",
                "description": "Medicine Purchase",
                "handler": function (response){
                    alert('Payment successful! Payment ID: ' + response.razorpay_payment_id);
                    window.location.href = 'order_confirmation.php';
                },
                "prefill": {
                    "name": "Customer Name",
                    "email": "customer@example.com",
                    "contact": "9999999999"
                },
                "theme": {
                    "color": "#4CAF50"
                }
            };
            var rzp1 = new Razorpay(options);
            rzp1.open();
            e.preventDefault();
        });
    </script>
</body>
</html>
