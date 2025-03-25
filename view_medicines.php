<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "telecare+";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM medicines");

echo "<h2>Medicine List</h2>";
echo "<table border='1'>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Expiry Date</th>
            <th>Manufacturer</th>
        </tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>".$row["id"]."</td>
            <td>".$row["name"]."</td>
            <td>".$row["category"]."</td>
            <td>".$row["price"]."</td>
            <td>".$row["quantity"]."</td>
            <td>".$row["expiry_date"]."</td>
            <td>".$row["manufacturer"]."</td>
          </tr>";
}

echo "</table>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Inventory - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="view_medicines.php" class="menu-item active">
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
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">Medicine Inventory</h2>
                    <p class="text-gray-600">Manage and monitor your medicine stock</p>
                </div>
                <div class="flex gap-4">
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-file-export mr-2"></i> Export
                    </button>
                    <button class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-pills text-blue-500 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Total Medicines</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $result->num_rows; ?></p>
                        </div>
                    </div>
                </div>
                <!-- Add more stat cards as needed -->
            </div>

            <!-- Medicine Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- ... Rest of the table code remains the same ... -->
                <?php
                // Your existing table code here
                ?>
            </div>
        </div>
    </div>

    <script>
        // Your existing JavaScript code
    </script>
</body>
</html>

<?php $conn->close(); ?>
