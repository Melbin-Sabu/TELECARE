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

// Fetch pharmacists from the database
$pharmacist_query = "SELECT name, Qualification, license FROM pharmacists";
$pharmacist_result = $conn->query($pharmacist_query);

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
            width: 200px; /* Reduced from 280px */
            background: linear-gradient(180deg, rgb(5, 220, 30) 0%, rgb(13, 227, 49) 100%);
            color: white;
            padding: 0;
            height: 100vh;
            position: fixed;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .sidebar h2 {
            padding: 15px;
            margin: 0;
            text-align: center;
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .sidebar li {
            padding: 10px 15px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .sidebar i {
            margin-right: 10px;
            width: 20px;
        }
        .main-content {
            margin-left: 200px; /* Match sidebar width */
            padding: 20px;
            flex: 1;
            width: calc(100% - 200px); /* Ensure content uses available space */
        }
        .container {
            margin-left: 200px; /* Match sidebar width */
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admindash.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="admindashori.php"><i class="fas fa-box"></i> Medicine</a></li>
            <li><a href="medicinedetails.php"><i class="fas fa-box"></i> Inventory</a></li>
            <li><a href="#"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="staffdetail.php"><i class="fas fa-chart-bar"></i> Staff</a></li>
            <li><a href="#"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="container">
        <h2>Pharmacists List</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Qualification</th>
                <th>License</th>
            </tr>
            <?php
            if ($pharmacist_result && $pharmacist_result->num_rows > 0) {
                while ($row = $pharmacist_result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['name']}</td>
                            <td>{$row['Qualification']}</td>
                            <td>{$row['license']}</td>
                        </tr>";
                }
            } else {
                echo "<tr><td colspan='3' style='text-align:center;'>No pharmacists found</td></tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>