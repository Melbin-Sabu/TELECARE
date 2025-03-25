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

// Get statistics for admin dashboard
$total_items = $result->num_rows;
            
$low_stock_sql = "SELECT COUNT(*) as count FROM Medicines WHERE stock_quantity <= 100";
$low_stock_result = $conn->query($low_stock_sql);
$low_stock = $low_stock_result->fetch_assoc()['count'];

$expired_sql = "SELECT COUNT(*) as count FROM Medicines WHERE expiry_date <= CURDATE()";
$expired_result = $conn->query($expired_sql);
$expired = $expired_result->fetch_assoc()['count'];

// Calculate total inventory value
$value_sql = "SELECT SUM(stock_quantity * price_per_unit) as total_value FROM Medicines";
$value_result = $conn->query($value_sql);
$total_value = $value_result->fetch_assoc()['total_value'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telecare+ | Pharmacy Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Custom Creative Style */
        :root {
            --primary-color:rgb(34, 207, 60);
            --secondary-color: #2ecc71;
            --accent-color: #9b59b6;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --dark-color: #34495e;
            --light-color: #ecf0f1;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .sidebar {
            background: var(--dark-color);
            background-image: linear-gradient(to bottom,rgb(60, 175, 45), #34495e);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            height: 100vh;
            position: fixed;
            transition: all 0.3s;
        }
        
        .sidebar-link {
            color: var(--light-color);
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(42, 197, 53, 0.1);
            border-left: 3px solid var(--secondary-color);
        }
        
        .logo-text {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            border-top: 4px solid var(--primary-color);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-stock {
            border-top-color: var(--secondary-color);
        }
        
        .card-low {
            border-top-color: var(--warning-color);
        }
        
        .card-expired {
            border-top-color: var(--danger-color);
        }
        
        .card-value {
            border-top-color: var(--accent-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .table-container {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border-radius: 10px;
        }
        
        table thead {
            background: linear-gradient(90deg, var(--primary-color), #4aa3df);
            color: white;
        }
        
        table tbody tr {
            transition: background-color 0.2s;
        }
        
        table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .form-input {
            transition: all 0.3s;
            border: 1px solid #ddd;
        }
        
        .form-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .alert {
            border-radius: 8px;
            border-left: 4px solid;
            animation: fadeIn 0.5s;
        }
        
        .alert-success {
            border-left-color: var(--secondary-color);
        }
        
        .alert-error {
            border-left-color: var(--danger-color);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Pulse animation for low stock */
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #2980b9;
        }
        
        /* Add responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-text {
                display: none;
            }
            
            .content-container {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="flex">
        <!-- Admin Sidebar -->
        <div class="sidebar w-64 text-white">
            <div class="p-4 flex items-center border-b border-gray-700 pb-6">
                <i class="fas fa-clinic-medical text-3xl mr-2 text-green-900"></i>
                <h1 class="text-xl font-bold logo-text">Telecare+</h1>
            </div>
            <div class="py-4">
                <div class="px-4 py-2 mb-3">
                    <div class="text-gray-400 text-xs uppercase tracking-wider">Main</div>
                </div>
                <a href="admindash1.php" class="sidebar-link flex items-center px-4 py-3 active">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="#inventory" class="sidebar-link flex items-center px-4 py-3">
                    <i class="fas fa-pills mr-3"></i>
                    <span class="sidebar-text">Inventory</span>
                </a>
                <a href="#" class="sidebar-link flex items-center px-4 py-3">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    <span class="sidebar-text">Sales</span>
                </a>
                <a href="#" class="sidebar-link flex items-center px-4 py-3">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span class="sidebar-text">Reports</span>
                </a>
                
                <div class="px-4 py-2 mt-6 mb-3">
                    <div class="text-gray-400 text-xs uppercase tracking-wider">Management</div>
                </div>
                <a href="#" class="sidebar-link flex items-center px-4 py-3">
                    <i class="fas fa-users mr-3"></i>
                    <span class="sidebar-text">Staff</span>
                </a>
                
                <a href="#" class="sidebar-link flex items-center px-4 py-3">
                    <i class="fas fa-cog mr-3"></i>
                    <span class="sidebar-text">Settings</span>
                </a>
                
                
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-container flex-1 ml-64 p-8">
            <header class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Pharmacy Management</h1>
                    <p class="text-gray-600 mt-2">Monitor and manage medicine inventory</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="bg-white p-2 rounded-full shadow">
                        <i class="fas fa-bell text-gray-600"></i>
                    </button>
                    
                </div>
            </header>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo $success_message; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error bg-red-100 border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo $error_message; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="card bg-white p-6 rounded-lg shadow card-stock">
                    <div class="flex items-center">
                        <div class="rounded-full bg-blue-100 p-3 mr-4">
                            <i class="fas fa-capsules text-blue-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Total Medicines</h3>
                            <p class="text-3xl font-bold text-blue-600 mt-1"><?php echo $total_items; ?></p>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i> Total items in inventory
                    </div>
                </div>
                
                <div class="card bg-white p-6 rounded-lg shadow card-low">
                    <div class="flex items-center">
                        <div class="rounded-full bg-yellow-100 p-3 mr-4">
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Low Stock Items</h3>
                            <p class="text-3xl font-bold text-yellow-600 mt-1 <?php echo $low_stock > 0 ? 'pulse' : ''; ?>"><?php echo $low_stock; ?></p>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i> Items with stock <= 100
                    </div>
                </div>
                
                <div class="card bg-white p-6 rounded-lg shadow card-expired">
                    <div class="flex items-center">
                        <div class="rounded-full bg-red-100 p-3 mr-4">
                            <i class="fas fa-calendar-times text-red-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Expired Items</h3>
                            <p class="text-3xl font-bold text-red-600 mt-1 <?php echo $expired > 0 ? 'pulse' : ''; ?>"><?php echo $expired; ?></p>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i> Items past expiration date
                    </div>
                </div>
                
                <div class="card bg-white p-6 rounded-lg shadow card-value">
                    <div class="flex items-center">
                        <div class="rounded-full bg-purple-100 p-3 mr-4">
                            <i class="fas fa-dollar-sign text-purple-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Inventory Value</h3>
                            <p class="text-3xl font-bold text-purple-600 mt-1">$<?php echo number_format($total_value, 2); ?></p>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i> Total value of current stock
                    </div>
                </div>
            </div>

            <!-- Add New Medicine Form -->
            <div class="card bg-white rounded-lg shadow p-6 mb-8">
                <div class="flex items-center mb-4">
                    <i class="fas fa-plus-circle text-blue-500 mr-2 text-xl"></i>
                    <h2 class="text-xl font-bold text-gray-800">Add New Medicine</h2>
                </div>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">
                            <i class="fas fa-prescription-bottle-alt mr-2 text-blue-500 opacity-70"></i>
                            Medicine Name
                        </label>
                        <input type="text" name="name" required
                               class="form-input w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">
                            <i class="fas fa-building mr-2 text-blue-500 opacity-70"></i>
                            Company
                        </label>
                        <input type="text" name="company" required
                               class="form-input w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">
                            <i class="fas fa-barcode mr-2 text-blue-500 opacity-70"></i>
                            Batch Number
                        </label>
                        <input type="text" name="batch_number" required
                               class="form-input w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-2 text-blue-500 opacity-70"></i>
                            Expiry Date
                        </label>
                        <input type="date" name="expiry_date" required
                               class="form-input w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">
                            <i class="fas fa-cubes mr-2 text-blue-500 opacity-70"></i>
                            Stock Quantity
                        </label>
                        <input type="number" name="stock_quantity" required min="0"
                               class="form-input w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">
                            <i class="fas fa-tag mr-2 text-blue-500 opacity-70"></i>
                            Price per Unit
                        </label>
                        <input type="number" name="price_per_unit" required min="0" step="0.01"
                               class="form-input w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" name="add_medicine"
                                class="btn-primary flex items-center text-white px-6 py-2 rounded hover:bg-blue-600 transition-all">
                            <i class="fas fa-plus mr-2"></i> Add Medicine
                        </button>
                    </div>
                </form>
            </div>

            <!-- Inventory Table -->
            <div id="inventory" class="card bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-pills text-blue-500 mr-2 text-xl"></i>
                        <h2 class="text-xl font-bold text-gray-800">Current Inventory</h2>
                    </div>
                    <div class="flex items-center">
                        <div class="relative mr-4">
                            <input type="text" placeholder="Search inventory..." 
                                   class="form-input pl-10 pr-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <div class="flex">
                            <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1 rounded-l">
                                <i class="fas fa-filter"></i>
                            </button>
                            <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1 rounded-r border-l border-gray-300">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto table-container">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left">Medicine Name</th>
                                <th class="px-4 py-3 text-left">Company</th>
                                <th class="px-4 py-3 text-left">Batch Number</th>
                                <th class="px-4 py-3 text-left">Expiry Date</th>
                                <th class="px-4 py-3 text-left">Current Stock</th>
                                <th class="px-4 py-3 text-left">Price/Unit</th>
                                <th class="px-4 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset result pointer
                            $result->data_seek(0);
                            while($row = $result->fetch_assoc()): 
                            ?>
                                <tr class="border-t hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-prescription-bottle text-blue-500 mr-2"></i>
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($row['company']); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                                            <?php echo htmlspecialchars($row['batch_number']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php 
                                        $expiry_date = new DateTime($row['expiry_date']);
                                        $today = new DateTime();
                                        $expired = $expiry_date < $today;
                                        $days_until = $today->diff($expiry_date)->days;
                                        $warning = $days_until <= 30 && !$expired;
                                        
                                        if ($expired) {
                                            $class = 'bg-red-100 text-red-700';
                                            $icon = '<i class="fas fa-exclamation-circle mr-1"></i>';
                                        } elseif ($warning) {
                                            $class = 'bg-yellow-100 text-yellow-700';
                                            $icon = '<i class="fas fa-exclamation-triangle mr-1"></i>';
                                        } else {
                                            $class = 'bg-green-100 text-green-700';
                                            $icon = '<i class="fas fa-check-circle mr-1"></i>';
                                        }
                                        
                                        echo "<span class='$class px-2 py-1 rounded text-xs'>" . 
                                             $icon . $expiry_date->format('Y-m-d') . "</span>";
                                        ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="POST" class="flex gap-2 items-center">
                                            <input type="hidden" name="medicine_id" value="<?php echo $row['medicine_id']; ?>">
                                            <input type="number" name="new_quantity" 
                                                   class="form-input w-20 px-2 py-1 border rounded"
                                                   min="0" value="<?php echo $row['stock_quantity']; ?>">
                                            <button type="submit" name="update_stock"
                                                    class="btn-primary bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                                                <i class="fas fa-sync-alt"></i>
                                                </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium">$<?php echo number_format($row['price_per_unit'], 2); ?></span>
                                        <span class="text-xs text-gray-500 block">
                                            Value: $<?php echo number_format($row['stock_quantity'] * $row['price_per_unit'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            <a href="#" class="bg-blue-100 text-blue-600 p-1 rounded-full hover:bg-blue-200" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" class="bg-green-100 text-green-600 p-1 rounded-full hover:bg-green-200" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this medicine?');">
                                                <input type="hidden" name="medicine_id" value="<?php echo $row['medicine_id']; ?>">
                                                <button type="submit" name="remove_medicine" 
                                                       class="bg-red-100 text-red-600 p-1 rounded-full hover:bg-red-200" title="Remove">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex justify-between items-center mt-6">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-medium">1</span> to <span class="font-medium"><?php echo $total_items; ?></span> of <span class="font-medium"><?php echo $total_items; ?></span> entries
                    </div>
                    <div class="flex space-x-1">
                        <button class="px-3 py-1 rounded border bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-50" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-1 rounded border bg-blue-500 text-white hover:bg-blue-600">1</button>
                        <button class="px-3 py-1 rounded border bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-50" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Additional Analytics Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                <div class="card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-chart-pie text-blue-500 mr-2 text-xl"></i>
                        <h2 class="text-xl font-bold text-gray-800">Stock Status</h2>
                    </div>
                    <div class="flex flex-col md:flex-row items-center justify-center space-y-6 md:space-y-0 md:space-x-8 p-4">
                        <div class="w-40 h-40 relative flex items-center justify-center">
                            <div class="absolute inset-0 border-8 border-blue-100 rounded-full"></div>
                            <div class="absolute inset-0 border-8 border-transparent border-t-blue-500 rounded-full animate-spin" style="animation-duration: 3s;"></div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-500"><?php echo $total_items; ?></div>
                                <div class="text-sm text-gray-500">Total Items</div>
                            </div>
                        </div>
                        <div class="space-y-4 flex-1">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>In Stock</span>
                                    <span class="font-medium"><?php echo $total_items - $low_stock - $expired; ?></span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full" style="width: <?php echo ($total_items > 0) ? (($total_items - $low_stock - $expired) / $total_items * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Low Stock</span>
                                    <span class="font-medium"><?php echo $low_stock; ?></span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-yellow-500 rounded-full" style="width: <?php echo ($total_items > 0) ? ($low_stock / $total_items * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Expired</span>
                                    <span class="font-medium"><?php echo $expired; ?></span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-500 rounded-full" style="width: <?php echo ($total_items > 0) ? ($expired / $total_items * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-calendar-day text-blue-500 mr-2 text-xl"></i>
                        <h2 class="text-xl font-bold text-gray-800">Recent Activity</h2>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-green-100 rounded-full p-2 mr-3">
                                <i class="fas fa-plus text-green-500"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <p class="font-medium">New medicine added</p>
                                    <span class="text-xs text-gray-500">2 hours ago</span>
                                </div>
                                <p class="text-sm text-gray-600">Amoxicillin 500mg - Batch #AMX12345</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-blue-100 rounded-full p-2 mr-3">
                                <i class="fas fa-sync-alt text-blue-500"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <p class="font-medium">Stock updated</p>
                                    <span class="text-xs text-gray-500">5 hours ago</span>
                                </div>
                                <p class="text-sm text-gray-600">Paracetamol 500mg - Quantity: +200</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-red-100 rounded-full p-2 mr-3">
                                <i class="fas fa-trash-alt text-red-500"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <p class="font-medium">Medicine removed</p>
                                    <span class="text-xs text-gray-500">Yesterday</span>
                                </div>
                                <p class="text-sm text-gray-600">Expired Ciprofloxacin - Batch #CIP45678</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-yellow-100 rounded-full p-2 mr-3">
                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <p class="font-medium">Low stock alert</p>
                                    <span class="text-xs text-gray-500">2 days ago</span>
                                </div>
                                <p class="text-sm text-gray-600">Metformin 850mg - Only 35 units left</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="#" class="text-blue-500 text-sm hover:underline">View all activity</a>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="mt-12 text-center text-gray-500 text-sm pb-8">
                <p>© <?php echo date('Y'); ?> Telecare+ Pharmacy Management System. All rights reserved.</p>
                <p class="mt-1">Version 1.2.0</p>
            </footer>
        </div>
    </div>

    <!-- JavaScript for interactions -->
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content-container');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('hidden');
                    content.classList.toggle('ml-0');
                    content.classList.toggle('ml-64');
                });
            }
            
            // Add animation to alerts
            const alerts = document.querySelectorAll('.alert');
            setTimeout(() => {
                alerts.forEach(alert => {
                    alert.classList.add('fadeOut');
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>