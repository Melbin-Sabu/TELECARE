<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "telecare+";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Add New Medicine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_medicine'])) {
    $errors = [];
    
    // Validate Medicine Name
    $name = trim($_POST['name']);
    if (empty($name)) {
        $errors[] = "Medicine name is required";
    } elseif (strlen($name) > 100) {
        $errors[] = "Medicine name cannot exceed 100 characters";
    }
    
    // Validate Batch Number
    $batch_number = trim($_POST['batch_number']);
    if (empty($batch_number)) {
        $errors[] = "Batch number is required";
    } elseif (!preg_match('/^[A-Za-z0-9-]+$/', $batch_number)) {
        $errors[] = "Batch number can only contain letters, numbers, and hyphens";
    }
    
    // Validate Expiry Date
    $expiry_date = $_POST['expiry_date'];
    $current_date = date('Y-m-d');
    if (empty($expiry_date)) {
        $errors[] = "Expiry date is required";
    } elseif ($expiry_date <= $current_date) {
        $errors[] = "Expiry date must be in the future";
    }
    
    // Validate Stock Quantity
    $stock_quantity = filter_var($_POST['stock_quantity'], FILTER_VALIDATE_INT);
    if ($stock_quantity === false || $stock_quantity < 0) {
        $errors[] = "Stock quantity must be a positive number";
    }
    
    // Validate Price Per Unit
    $price_per_unit = filter_var($_POST['price_per_unit'], FILTER_VALIDATE_FLOAT);
    if ($price_per_unit === false || $price_per_unit <= 0) {
        $errors[] = "Price per unit must be a positive number";
    }
    
    // Validate Company
    $company = trim($_POST['company']);
    if (empty($company)) {
        $errors[] = "Company name is required";
    } elseif (strlen($company) > 100) {
        $errors[] = "Company name cannot exceed 100 characters";
    }
    
    // Check for duplicate batch number
    $check_batch_sql = "SELECT COUNT(*) as count FROM Medicines WHERE batch_number = ?";
    $check_stmt = $conn->prepare($check_batch_sql);
    $check_stmt->bind_param("s", $batch_number);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        $errors[] = "A medicine with this batch number already exists";
    }
    $check_stmt->close();
    
    if (empty($errors)) {
        $insert_sql = "INSERT INTO Medicines (name, batch_number, expiry_date, stock_quantity, price_per_unit, company) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sssids", $name, $batch_number, $expiry_date, $stock_quantity, $price_per_unit, $company);
        
        if ($stmt->execute()) {
            $success_message = "Medicine added successfully!";
        } else {
            $error_message = "Error adding medicine: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Handle Remove Medicine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_medicine'])) {
    $medicine_id = $_POST['medicine_id'];
    
    $delete_sql = "DELETE FROM Medicines WHERE medicine_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $medicine_id);
    
    if ($stmt->execute()) {
        $success_message = "Medicine removed successfully!";
    } else {
        $error_message = "Error removing medicine: " . $conn->error;
    }
    $stmt->close();
}

// Handle stock updates (from previous code)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $medicine_id = $_POST['medicine_id'];
    $new_quantity = $_POST['new_quantity'];
    
    $update_sql = "UPDATE Medicines SET stock_quantity = ? WHERE medicine_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $new_quantity, $medicine_id);
    
    if ($stmt->execute()) {
        $success_message = "Stock updated successfully!";
    } else {
        $error_message = "Error updating stock: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all medicines
$sql = "SELECT * FROM Medicines ORDER BY name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Telecare+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            width: 250px;
            background: #2c3e50;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .sidebar-brand {
            padding: 20px;
            color: white;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 15px 20px;
            color: #ecf0f1;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .menu-item:hover {
            background: #34495e;
            cursor: pointer;
        }

        .menu-item.active {
            background: #3498db;
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .profile-section {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            color: white;
        }

        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 10px;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2 class="text-2xl font-bold">Telecare+</h2>
        </div>

        <div class="profile-section">
            <div class="profile-image">
                <i class="fas fa-user"></i>
            </div>
            <h3 class="font-semibold">Admin User</h3>
            <p class="text-sm text-gray-400">Administrator</p>
        </div>

        <div class="sidebar-menu">
            <a href="admindash.php" class="menu-item">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="adminmedicineoperation.php" class="menu-item active">
                <i class="fas fa-pills"></i>
                Medicine Management
            </a>
            <a href="users.php" class="menu-item">
                <i class="fas fa-users"></i>
                User Management
            </a>
            <a href="orders.php" class="menu-item">
                <i class="fas fa-shopping-cart"></i>
                Orders
            </a>
            <a href="reports.php" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                Reports
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i>
                Settings
            </a>
            <a href="logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container mx-auto px-4 py-8">
            <header class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Medicine Management</h1>
                <p class="text-gray-600 mt-2">Monitor and manage medicine inventory</p>
            </header>

            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Add New Medicine Form -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Add New Medicine</h2>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Medicine Name</label>
                        <input type="text" name="name" required
                               class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Company</label>
                        <input type="text" name="company" required
                               class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Batch Number</label>
                        <input type="text" name="batch_number" required
                               class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Expiry Date</label>
                        <input type="date" name="expiry_date" required
                               class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Stock Quantity</label>
                        <input type="number" name="stock_quantity" required min="0"
                               class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Price per Unit</label>
                        <input type="number" name="price_per_unit" required min="0" step="0.01"
                               class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" name="add_medicine"
                                class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                            Add Medicine
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stock Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <?php
                $total_items = $result->num_rows;
                
                $low_stock_sql = "SELECT COUNT(*) as count FROM Medicines WHERE stock_quantity <= 100";
                $low_stock_result = $conn->query($low_stock_sql);
                $low_stock = $low_stock_result->fetch_assoc()['count'];
                
                $expired_sql = "SELECT COUNT(*) as count FROM Medicines WHERE expiry_date <= CURDATE()";
                $expired_result = $conn->query($expired_sql);
                $expired = $expired_result->fetch_assoc()['count'];
                ?>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Total Medicines</h3>
                    <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $total_items; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Low Stock Items</h3>
                    <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo $low_stock; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Expired Items</h3>
                    <p class="text-3xl font-bold text-red-600 mt-2"><?php echo $expired; ?></p>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Current Inventory</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2 text-left">Medicine Name</th>
                                <th class="px-4 py-2 text-left">Company</th>
                                <th class="px-4 py-2 text-left">Batch Number</th>
                                <th class="px-4 py-2 text-left">Expiry Date</th>
                                <th class="px-4 py-2 text-left">Current Stock</th>
                                <th class="px-4 py-2 text-left">Price/Unit</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($row['company']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                    <td class="px-4 py-2">
                                        <?php 
                                        $expiry_date = new DateTime($row['expiry_date']);
                                        $today = new DateTime();
                                        $expired = $expiry_date < $today;
                                        $class = $expired ? 'text-red-600' : 'text-gray-800';
                                        echo "<span class='$class'>" . $expiry_date->format('Y-m-d') . "</span>";
                                        ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <form method="POST" class="flex gap-2 items-center">
                                            <input type="hidden" name="medicine_id" value="<?php echo $row['medicine_id']; ?>">
                                            <input type="number" name="new_quantity" 
                                                   class="w-20 px-2 py-1 border rounded"
                                                   min="0" value="<?php echo $row['stock_quantity']; ?>">
                                            <button type="submit" name="update_stock"
                                                    class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                                                Update
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-2">$<?php echo number_format($row['price_per_unit'], 2); ?></td>
                                    <td class="px-4 py-2">
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this medicine?');">
                                            <input type="hidden" name="medicine_id" value="<?php echo $row['medicine_id']; ?>">
                                            <button type="submit" name="remove_medicine"
                                                    class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>