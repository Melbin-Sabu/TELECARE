<?php
session_start();
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "telecare+";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if vendor is logged in
if (!isset($_SESSION['vendor_name'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

$vendor_name = $_SESSION['vendor_name']; // Get vendor's name from session

// Medicine Upload Handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excel_file"])) {
    $file = $_FILES["excel_file"]["tmp_name"];

    try {
        // Load Excel file
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        array_shift($data); // Remove header row

        foreach ($data as $row) {
            $name = $conn->real_escape_string($row[0]);
            $category = $conn->real_escape_string($row[1]);
            $price = floatval($row[2]);
            $quantity = intval($row[3]);
            $expiry_date = DateTime::createFromFormat('m/d/Y', $row[4])->format('Y-m-d');
            $manufacturer = $conn->real_escape_string($row[5]);

            // Insert new medicine
            $sql = "INSERT INTO medicines (name, category, price, quantity, expiry_date, manufacturer, vendor_name) 
                    VALUES ('$name', '$category', '$price', '$quantity', '$expiry_date', '$manufacturer', '$vendor_name')";
            $conn->query($sql);
        }

        $_SESSION['success_message'] = "Medicines uploaded successfully!";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error uploading file: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #343a40;
            color: white;
            height: 100vh;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background: #007bff;
        }
        .logout {
            background: red;
        }

        /* Main Content */
        .main-content {
            margin-left: 270px;
            padding: 20px;
            width: 100%;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 600px;
            margin: auto;
        }
        h2 {
            color: #333;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        form {
            margin-top: 15px;
        }
        input[type="file"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Vendor Panel</h2>
        <p>Welcome, <?php echo htmlspecialchars($vendor_name); ?>!</p>
        <a href="vendor_dashboard.php">📊 Dashboard</a>
        <a href="vendor_upload.php">📤 Upload Medicines</a>
        <a href="vendor_logout.php" class="logout">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2>Upload Medicines</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <p class="message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <label>Select Excel file:</label><br>
                <input type="file" name="excel_file" required><br><br>
                <button type="submit">Upload & Import</button>
            </form>
        </div>
    </div>

</body>
</html>
