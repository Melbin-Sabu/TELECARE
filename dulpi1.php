<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Inventory</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        h2 {
            color:rgb(15, 199, 76);
            border-bottom: 2px solidrgb(23, 215, 65);
            padding-bottom: 10px;
            margin-top: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        
        th {
            background-color:rgb(46, 227, 52);
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        tr:hover {
            background-color: #e9f7fe;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #777;
        }
        
        .timestamp {
            text-align: right;
            color: #777;
            font-size: 0.8em;
            margin-top: 40px;
        }
        
        .error {
            background-color: #ffecec;
            color: #ff5252;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 5px solid #ff5252;
        }
    </style>
</head>
<body class="bg-gray-100 flex">
    <div class="container">
        <h1>Medicine Inventory</h1>
        <div class="w-64 text-white h-screen p-6" style="background-color: rgb(18, 227, 43);">

<div class="profile-info mb-8">
    <h3 class="text-xl font-bold">Admin User</h3>
    <p class="text-sm">Super Admin</p>
    <span class="text-green-700">Online</span>
</div>

<div class="menu-title text-sm uppercase text-gray-600 mb-4">Main Navigation</div>
<ul>
    <li class="mb-2"><a href="#" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-home mr-2"></i> Dashboard</a></li>
    <li class="mb-2"><a href="admindashori.php" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-box mr-2"></i> Medicine Management</a></li>
    <li class="mb-2"><a href="medicinedetails.php" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-box mr-2"></i> Inventory</a></li>
    <li class="mb-2"><a href="#" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-shopping-cart mr-2"></i> Orders</a></li>
</ul>

<div class="menu-title text-sm uppercase text-gray-600 mb-4">System</div>
<ul>
    <li class="mb-2"><a href="#" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-cog mr-2"></i> Settings</a></li>
    <li class="mb-2"><a href="#" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-chart-bar mr-2"></i> Reports</a></li>
    <li class="mb-2"><a href="#" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-bell mr-2"></i> Notifications</a></li>
    <li class="mb-2"><a href="logout.php" class="flex items-center text-white hover:text-gray-600"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a></li>
</ul>
</div>

        <?php
        // Database connection - CHANGE THESE TO MATCH YOUR DATABASE CONFIGURATION
        $servername = "localhost";
        $username = "root";  // Default XAMPP username
        $password = "";      // Default XAMPP password is empty
        $dbname = "telecare+";
        
        // Create connection - wrapping in try/catch for better error handling
        try {
            $conn = new mysqli($servername, $username, $password, $dbname);
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Function to display table data
            function displayMedicineTable($conn, $tableName, $title) {
                echo "<h2>$title</h2>";
                
                try {
                    // Get columns from the table
                    $columnsQuery = "SHOW COLUMNS FROM $tableName";
                    $columnsResult = $conn->query($columnsQuery);
                    
                    if (!$columnsResult) {
                        throw new Exception("Error getting columns: " . $conn->error);
                    }
                    
                    if ($columnsResult->num_rows > 0) {
                        echo "<div style='overflow-x: auto;'>";
                        echo "<table>";
                        
                        // Display table headers
                        echo "<tr>";
                        $columns = array();
                        while($column = $columnsResult->fetch_assoc()) {
                            $columns[] = $column['Field'];
                            echo "<th>" . ucfirst(str_replace('_', ' ', $column['Field'])) . "</th>";
                        }
                        echo "</tr>";
                        
                        // Get and display data
                        $dataQuery = "SELECT * FROM $tableName";
                        $dataResult = $conn->query($dataQuery);
                        
                        if (!$dataResult) {
                            throw new Exception("Error getting data: " . $conn->error);
                        }
                        
                        if ($dataResult->num_rows > 0) {
                            while($row = $dataResult->fetch_assoc()) {
                                echo "<tr>";
                                foreach($columns as $column) {
                                    echo "<td>" . htmlspecialchars($row[$column] ?? '') . "</td>";
                                }
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='" . count($columns) . "' class='no-data'>No data found in $tableName table</td></tr>";
                        }
                        
                        echo "</table>";
                        echo "</div>";
                    } else {
                        echo "<div class='no-data'>No columns found for table $tableName</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>Error displaying table: " . $e->getMessage() . "</div>";
                }
            }
            
            // Display medicines from both tables
            // Make sure these table names match your actual table names in the database
            displayMedicineTable($conn, "medicines", "Medicines Table");
            //displayMedicineTable($conn, "medi", "Medi Table");
            
            // Close connection
            $conn->close();
            
        } catch (Exception $e) {
            echo "<div class='error'>" . $e->getMessage() . "</div>";
            echo "<div class='error'>Check your database connection parameters at the top of this file.</div>";
        }
        ?>
        
        <div class="timestamp">
            Last updated: <?php echo date("F j, Y, g:i a"); ?>
        </div>
    </div>
</body>
</html>