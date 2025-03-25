<?php
session_start();
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "telecare+";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$uploadedData = [];
$dbMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excel_file"])) {
    $file = $_FILES["excel_file"]["tmp_name"];
    
    // Load the Excel file
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        
        // Store uploaded data in session for display
        $_SESSION['uploaded_data'] = [];
        
        // Begin transaction for database operations
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO medicines (name, category, price, quantity, expiry_date, manufacturer) VALUES (?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            
            $stmt->bind_param("ssidss", $name, $category, $price, $quantity, $expiry, $manufacturer);
            
            $successCount = 0;
            
            for ($i = 1; $i < count($data); $i++) {  // Skip header row
                $name = $data[$i][0];
                $category = $data[$i][1];
                $price = $data[$i][2];
                $quantity = $data[$i][3];
                $expiry = $data[$i][4];
                $manufacturer = $data[$i][5];
                
                // Save to session for display
                $_SESSION['uploaded_data'][] = [
                    'name' => $name,
                    'category' => $category,
                    'price' => $price,
                    'quantity' => $quantity,
                    'expiry' => $expiry,
                    'manufacturer' => $manufacturer
                ];
                
                // Execute the insert statement
                if ($stmt->execute()) {
                    $successCount++;
                }
            }
            
            // Commit the transaction
            $conn->commit();
            
            $dbMessage = "<p style='color: green; font-weight: bold;'>File uploaded successfully! $successCount records added to the database.</p>";
            
        } catch (Exception $e) {
            // Roll back the transaction in case of error
            $conn->rollback();
            $dbMessage = "<p style='color: red; font-weight: bold;'>Database error: " . $e->getMessage() . "</p>";
        }
        
    } catch (Exception $e) {
        $dbMessage = "<p style='color: red; font-weight: bold;'>Error loading file: " . $e->getMessage() . "</p>";
    }
}

// Retrieve uploaded data from session for display
if (isset($_SESSION['uploaded_data'])) {
    $uploadedData = $_SESSION['uploaded_data'];
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload & Store Medicine Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .upload-form {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Medicines Excel File</h1>
        
        <div class="upload-form">
            <form action="" method="post" enctype="multipart/form-data">
                <label for="excel_file">Select Excel file:</label>
                <input type="file" name="excel_file" id="excel_file" required>
                <button type="submit" class="btn">Upload & Store</button>
            </form>
        </div>
        
        <?php if (!empty($dbMessage)): ?>
            <?php echo $dbMessage; ?>
        <?php endif; ?>
        
        <?php if (!empty($uploadedData)): ?>
            <h2>Uploaded Data (Saved to Database):</h2>
            <table>
                <tr>
                    <th>Medicine Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Expiry Date</th>
                    <th>Manufacturer</th>
                </tr>
                <?php foreach ($uploadedData as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo htmlspecialchars($row['price']); ?></td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($row['expiry']); ?></td>
                        <td><?php echo htmlspecialchars($row['manufacturer']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>