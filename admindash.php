// Database connection
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "telecare+";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch total number of users
$user_count_query = "SELECT COUNT(*) as total_users FROM signup";
$user_result = $conn->query($user_count_query);
$user_count = 0;

if ($user_result && $row = $user_result->fetch_assoc()) {
    $user_count = $row['total_users'];
}

// Fetch total number of medicines
$medicine_count_query = "SELECT COUNT(*) as total_medicines FROM medicines";
$medicine_result = $conn->query($medicine_count_query);
$medicine_count = 0;

if ($medicine_result && $row = $medicine_result->fetch_assoc()) {
    $medicine_count = $row['total_medicines'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
        }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, rgb(5, 220, 30) 0%, rgb(13, 227, 49) 100%);
            color: white;
            padding: 0;
            height: 100vh;
            position: fixed;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }
        .main-content {
            margin-left: 280px;
            padding: 30px;
            flex: 1;
        }
        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: inline-block;
            width: 30%;
            margin-right: 10px;
        }
        .card i {
            font-size: 30px;
            color: green;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="#"><i class="fas fa-home"></i> Dashboard</a></li><br>
            <li><a href="admindashori.php"><i class="fas fa-box"></i> Medicine Management</a></li><br>
            <li><a href="medicinedetails.php"><i class="fas fa-box"></i> Inventory</a></li><br>
            <li><a href="#"><i class="fas fa-shopping-cart"></i> Orders</a></li><br>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li><br>
            <li><a href="staffdetail.php"><i class="fas fa-chart-bar"></i> staff details</a></li><br>
            <li><a href="customerdetail.php"><i class="fas fa-bell"></i>customer details</a></li><br>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li><br>
        </ul>
    </div>
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Welcome, Admin</h1>
            <p>Manage and monitor TELECARE+ from here.</p>
        </div>
        <div class="card">
            <i class="fas fa-users"></i>
            <h3>Total Users</h3>
            <p><?php echo $user_count; ?></p>
        </div>
        <div class="card">
            <i class="fas fa-box"></i>
            <h3>Total Medicines</h3>
            <p>120</p>
        </div>
        <div class="card">
            <i class="fas fa-shopping-cart"></i>
            <h3>New Orders</h3>
            <p>15</p>
        </div>
    </div>
</body>
</html>
