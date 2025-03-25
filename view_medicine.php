<?php
session_start();

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "telecare+";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch all medicines
$sql = "SELECT id, name, category, price, quantity, expiry_date, manufacturer FROM medicines";
$result = $conn->query($sql);

// Check if there are results
if ($result->num_rows > 0) {
    // Output data for each row
    $medicines = [];
    while($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
} else {
    $medicines = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            background: white;
            width: 80%;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .back-link {
            margin-top: 20px;
            display: inline-block;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        .back-link:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Medicines Uploaded by Vendors</h2>

        <?php if (empty($medicines)): ?>
            <p>No medicines found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Manufacturer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $medicine): ?>
                        <tr>
                            <td><?php echo $medicine['id']; ?></td>
                            <td><?php echo $medicine['name']; ?></td>
                            <td><?php echo $medicine['category']; ?></td>
                            <td><?php echo $medicine['price']; ?></td>
                            <td><?php echo $medicine['quantity']; ?></td>
                            <td><?php echo $medicine['expiry_date']; ?></td>
                            <td><?php echo $medicine['manufacturer']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="admindash2.php" class="back-link">Back to Admin Dashboard</a>
    </div>
</body>
</html>
