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
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
        }
        
        .sidebar {
            background-color: rgb(18, 227, 43);
            width: 250px;
            min-height: 100vh;
            color: white;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .profile-info {
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 15px;
        }
        
        .profile-info h3 {
            margin: 0 0 5px 0;
            font-size: 1.2rem;
        }
        
        .profile-info p {
            margin: 0 0 5px 0;
            font-size: 0.9rem;
        }
        
        .menu-title {
            text-transform: uppercase;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 20px 0 10px 0;
            font-weight: bold;
        }
        
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar li {
            margin-bottom: 10px;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 10px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar i {
            margin-right: 10px;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            box-sizing: border-box;
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
            color: rgb(15, 199, 76);
            border-bottom: 2px solid rgb(23, 215, 65);
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
            background-color: rgb(46, 227, 52);
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
        
        .success {
            background-color: #e7f9e7;
            color: #4CAF50;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 5px solid #4CAF50;
        }
        
        .btn-delete {
            background-color: #ff5252;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-delete:hover {
            background-color: #ff0000;
        }
        
        .action-btns {
            white-space: nowrap;
        }
        
        /* Confirmation dialog styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .modal-buttons {
            margin-top: 20px;
            text-align: right;
        }
        
        .btn-confirm {
            background-color: #ff5252;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .btn-cancel {
            background-color: #ccc;
            color: black;
            border: none;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile-info">
            <h3>Admin User</h3>
            <p>Super Admin</p>
            <span>Online</span>
        </div>

        <div class="menu-title">Main Navigation</div>
        <ul>
            <li><a href="admindash.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="admindashori.php"><i class="fas fa-box"></i> Medicine Management</a></li>
            <li><a href="medicinedetails.php"><i class="fas fa-box"></i> Inventory</a></li>
            <li><a href="#"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        </ul>

        <div class="menu-title">System</div>
        <ul>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="staffdetail.php"><i class="fas fa-chart-bar"></i> staff details</a></li>
            <li><a href="#"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1>Medicine Inventory</h1>
            
            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <p>Are you sure you want to delete this medicine?</p>
                    <div class="modal-buttons">
                        <form id="deleteForm" method="POST" action="">
                            <input type="hidden" id="deleteId" name="delete_id">
                            <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                            <button type="submit" class="btn-confirm" name="confirm_delete">Delete</button>
                        </form>
                    </div>
                </div>
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
                
                // First, let's check the structure of the medicines table to find the primary key column
                $tableInfoQuery = "SHOW COLUMNS FROM medicines";
                $tableInfoResult = $conn->query($tableInfoQuery);
                
                $primaryKeyColumn = null;
                if ($tableInfoResult) {
                    while ($column = $tableInfoResult->fetch_assoc()) {
                        if ($column['Key'] == 'PRI') {
                            $primaryKeyColumn = $column['Field'];
                            break;
                        }
                    }
                }
                
                // If we couldn't find a primary key, default to common names
                if (!$primaryKeyColumn) {
                    $possibleIdColumns = ['id', 'medicine_id', 'med_id', 'ID', 'medicineID'];
                    
                    // Check which of these columns exists in the table
                    foreach ($possibleIdColumns as $possibleColumn) {
                        $checkColumnQuery = "SHOW COLUMNS FROM medicines LIKE '$possibleColumn'";
                        $checkResult = $conn->query($checkColumnQuery);
                        
                        if ($checkResult && $checkResult->num_rows > 0) {
                            $primaryKeyColumn = $possibleColumn;
                            break;
                        }
                    }
                }
                
                // If we still don't have a primary key column, we'll need to inform the user
                if (!$primaryKeyColumn) {
                    echo "<div class='error'>Could not determine the primary key column for the medicines table. Please check your database structure.</div>";
                    throw new Exception("Primary key column not found");
                }
                
                // Process delete request
                if (isset($_POST['confirm_delete']) && isset($_POST['delete_id'])) {
                    $id = $_POST['delete_id'];
                    
                    // Prepare DELETE statement to prevent SQL injection
                    $stmt = $conn->prepare("DELETE FROM medicines WHERE $primaryKeyColumn = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        echo "<div class='success'>Medicine has been deleted successfully.</div>";
                    } else {
                        echo "<div class='error'>Error deleting medicine: " . $stmt->error . "</div>";
                    }
                    
                    $stmt->close();
                }
                
                // Display medicine table with specific columns
                echo "<h2>Medicines Table</h2>";
                
                try {
                    // Query to get only the specified columns including the primary key
                    $dataQuery = "SELECT $primaryKeyColumn, name, category, price, quantity, expiry_date FROM medicines";
                    $dataResult = $conn->query($dataQuery);
                    
                    if (!$dataResult) {
                        throw new Exception("Error getting data: " . $conn->error);
                    }
                    
                    echo "<div style='overflow-x: auto;'>";
                    echo "<table>";
                    
                    // Display table headers for only our selected columns
                    echo "<tr>";
                    echo "<th>Name</th>";
                    echo "<th>Category</th>";
                    echo "<th>Price</th>";
                    echo "<th>Quantity</th>";
                    echo "<th>Expiry Date</th>";
                    echo "<th>Actions</th>";
                    echo "</tr>";
                    
                    if ($dataResult->num_rows > 0) {
                        while($row = $dataResult->fetch_assoc()) {
                            $idValue = $row[$primaryKeyColumn];
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['name'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['category'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['price'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['quantity'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($row['expiry_date'] ?? '') . "</td>";
                            
                            // Only show delete button - Edit button removed
                            echo "<td class='action-btns'>";
                            echo "<button class='btn-delete' onclick=\"openDeleteModal(" . $idValue . ")\">Delete</button>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='no-data'>No data found in medicines table</td></tr>";
                    }
                    
                    echo "</table>";
                    echo "</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>Error displaying table: " . $e->getMessage() . "</div>";
                }
                
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
    </div>
    
    <script>
        // JavaScript for confirmation modal
        function openDeleteModal(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>