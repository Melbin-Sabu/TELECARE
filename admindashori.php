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
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    if (empty($name)) {
        $errors[] = "Medicine name is required";
    } elseif (strlen($name) > 100) {
        $errors[] = "Medicine name cannot exceed 100 characters";
    }

    // Validate Company
    $company = isset($_POST['company']) ? trim($_POST['company']) : '';
    if (empty($company)) {
        $errors[] = "Company name is required";
    } elseif (strlen($company) > 100) {
        $errors[] = "Company name cannot exceed 100 characters";
    }

    // Validate Batch Number
    $batch_number = isset($_POST['batch_number']) ? trim($_POST['batch_number']) : '';
    if (empty($batch_number)) {
        $errors[] = "Batch number is required";
    } elseif (!preg_match('/^[A-Za-z0-9-]+$/', $batch_number)) {
        $errors[] = "Batch number can only contain letters, numbers, and hyphens";
    }

    // Check for duplicate batch number
    // After checking for duplicate batch number and before the expiry date validation,
// add this code to check for duplicate medicine name:

// Check for duplicate medicine name
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

// Check for duplicate medicine name
$check_name_sql = "SELECT COUNT(*) as count FROM Medicines WHERE name = ?";
$check_name_stmt = $conn->prepare($check_name_sql);
$check_name_stmt->bind_param("s", $name);
$check_name_stmt->execute();
$name_result = $check_name_stmt->get_result();
$name_row = $name_result->fetch_assoc();
if ($name_row['count'] > 0) {
    $errors[] = "A medicine with this name already exists";
}
$check_name_stmt->close();
    
// Validate Expiry Date
$expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    // Validate Expiry Date
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    $current_date = date('Y-m-d');
    if (empty($expiry_date)) {
        $errors[] = "Expiry date is required";
    } elseif ($expiry_date <= $current_date) {
        $errors[] = "Expiry date must be in the future";
    }

    // Validate Stock Quantity
    $stock_quantity = isset($_POST['stock_quantity']) ? filter_var($_POST['stock_quantity'], FILTER_VALIDATE_INT) : 0;
    if ($stock_quantity === false || $stock_quantity < 0) {
        $errors[] = "Stock quantity must be a positive number";
    }

    // Validate Price Per Unit
    $price_per_unit = isset($_POST['price_per_unit']) ? filter_var($_POST['price_per_unit'], FILTER_VALIDATE_FLOAT) : 0;
    if ($price_per_unit === false || $price_per_unit <= 0) {
        $errors[] = "Price per unit must be a positive number";
    }

    // If no errors, insert the medicine into the database
    if (empty($errors)) {
        // Proceed to insert into the database
        $insert_sql = "INSERT INTO Medicines (name, batch_number, expiry_date, stock_quantity, price_per_unit, manufacturer) 
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

// Fetch all medicines (remove this part to exclude inventory)
$sql = "SELECT * FROM Medicines ORDER BY name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telecare+</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-gray-100 flex">
    <!-- Sidebar with light green background -->
    <div class="w-64 text-white h-screen p-6" style="background-color: rgb(18, 227, 43);">

        <div class="profile-info mb-8">
            <h3 class="text-xl font-bold">Admin User</h3>
            <p class="text-sm">Super Admin</p>
            <span class="text-green-700">Online</span>
        </div>

        <div class="menu-title text-sm uppercase text-gray-600 mb-4">Main Navigation</div>
        <ul>
            <li class="mb-2"><a href="admindash.php" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-home mr-2"></i> Dashboard</a></li>
            <li class="mb-2"><a href="admindashori.php" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-box mr-2"></i> Medicine Management</a></li>
            <li class="mb-2"><a href="medicinedetails.php" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-box mr-2"></i> Inventory</a></li>
            <li class="mb-2"><a href="#" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-shopping-cart mr-2"></i> Orders</a></li>
        </ul>

        <div class="menu-title text-sm uppercase text-gray-600 mb-4">System</div>
        <ul>
            <li class="mb-2"><a href="#" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-cog mr-2"></i> Settings</a></li>
            <li class="mb-2"><a href="staffdetail.php" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-chart-bar mr-2"></i> staff details</a></li>
            <li class="mb-2"><a href="customerdetail.php" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-bell mr-2"></i>customer Details</a></li>
            <li class="mb-2"><a href="logout.php" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Telecare+</h1>
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
    <!-- Medicine Name -->
    <div>
        <label class="block text-gray-700">Medicine Name</label>
        <input type="text" name="name" required class="w-full px-4 py-2 border rounded">
    </div>

    <!-- Company -->
    <div>
        <label class="block text-gray-700">Company</label>
        <input type="text" name="company" required class="w-full px-4 py-2 border rounded">
    </div>

    <!-- Batch Number -->
    <div>
        <label class="block text-gray-700">Batch Number</label>
        <input type="text" name="batch_number" required class="w-full px-4 py-2 border rounded">
    </div>

    <!-- Expiry Date -->
    <div>
        <label class="block text-gray-700">Expiry Date</label>
        <input type="date" name="expiry_date" required class="w-full px-4 py-2 border rounded">
    </div>

    <!-- Stock Quantity -->
    <div>
        <label class="block text-gray-700">Stock Quantity</label>
        <input type="number" name="stock_quantity" min="0" required class="w-full px-4 py-2 border rounded">
    </div>

    <!-- Price Per Unit -->
    <div>
        <label class="block text-gray-700">Price Per Unit ($)</label>
        <input type="number" step="0.01" name="price_per_unit" min="0" required class="w-full px-4 py-2 border rounded">
    </div>

    <!-- Submit Button -->
    <div class="col-span-3 text-right">
        <button type="submit" name="add_medicine" class="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Add Medicine
        </button>
    </div>
</form>

        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
