<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pharmacist Details</title>
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
        
        .btn-view {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-view:hover {
            background-color: #45a049;
        }
        
        .btn-edit {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
            transition: background-color 0.2s;
        }
        
        .btn-edit:hover {
            background-color: #0b7dda;
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
        
        .status-active {
            color: #4CAF50;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #ff5252;
            font-weight: bold;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-box {
            padding: 8px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-search {
            background-color: rgb(46, 227, 52);
            color: white;
            border: none;
            padding: 9px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .btn-add-new {
            background-color: rgb(46, 227, 52);
            color: white;
            border: none;
            padding: 9px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        /* Modal for view details */
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
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .pharmacist-detail {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 150px;
            display: inline-block;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            margin-right: 5px;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            background-color: #f9f9f9;
        }
        
        .tab.active {
            background-color: rgb(46, 227, 52);
            color: white;
            border-color: rgb(46, 227, 52);
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
            <li><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="admindashori.php"><i class="fas fa-box"></i> Medicine Management</a></li>
            <li><a href="medicinedetails.php"><i class="fas fa-box"></i> Inventory</a></li>
            <li><a href="pharmacists.php"><i class="fas fa-user-md"></i> Pharmacists</a></li>
            <li><a href="#"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        </ul>

        <div class="menu-title">System</div>
        <ul>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="#"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1>Pharmacist Management</h1>
            
            <div class="tabs">
                <div class="tab active" onclick="changeTab('all')">All Pharmacists</div>
                <div class="tab" onclick="changeTab('active')">Active</div>
                <div class="tab" onclick="changeTab('inactive')">Inactive</div>
            </div>
            
            <div class="search-container">
                <div>
                    <input type="text" class="search-box" id="searchInput" placeholder="Search pharmacists...">
                    <button class="btn-search" onclick="searchPharmacists()"><i class="fas fa-search"></i> Search</button>
                </div>
                <a href="add_pharmacist.php" class="btn-add-new"><i class="fas fa-plus"></i> Add New Pharmacist</a>
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
                
                // Get pharmacists data
                $query = "SELECT * FROM pharmacists ORDER BY id DESC";
                $result = $conn->query($query);
                
                if (!$result) {
                    throw new Exception("Error retrieving pharmacists: " . $conn->error);
                }
                
                echo "<div style='overflow-x: auto;'>";
                echo "<table id='pharmacistsTable'>";
                echo "<tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>qualification</th>
                       
                        <th>License</th>
                        <th>filepath</th>
                       
                      </tr>";
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        //$status_class = ($row['status'] == 'active') ? 'status-active' : 'status-inactive';
                        
                        echo "<tr class='pharmacist-row' data-status='{$row['status']}'>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td class='user-info'>";
                        // If you have profile images, uncomment the next line
                        // echo "<img src='images/profiles/" . $row['profile_image'] . "' alt='Profile' class='user-avatar'>";
                        echo $row['name'];
                        echo "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['phone'] . "</td>";
                        echo "<td>" . $row['license_number'] . "</td>";
                        echo "<td class='" . $status_class . "'>" . ucfirst($row['status']) . "</td>";
                        echo "<td class='action-btns'>";
                        echo "<button class='btn-view' onclick='viewPharmacist(" . $row['id'] . ")'>View</button> ";
                        echo "<button class='btn-edit' onclick=\"location.href='edit_pharmacist.php?id=" . $row['id'] . "'\">Edit</button> ";
                        echo "<button class='btn-delete' onclick=\"confirmDelete(" . $row['id'] . ")\">Delete</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='no-data'>No pharmacists found</td></tr>";
                }
                
                echo "</table>";
                echo "</div>";
                
                // Close connection
                $conn->close();
                
            } catch (Exception $e) {
                echo "<div class='error'>" . $e->getMessage() . "</div>";
            }
            ?>
            
            <!-- Pharmacist Details Modal -->
            <div id="pharmacistModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Pharmacist Details</h2>
                    <div id="pharmacistDetails">
                        <!-- Details will be loaded here via AJAX -->
                        <div class="pharmacist-detail">
                            <span class="detail-label">Name:</span>
                            <span id="modal-name">Loading...</span>
                        </div>
                        <div class="pharmacist-detail">
                            <span class="detail-label">Email:</span>
                            <span id="modal-email">Loading...</span>
                        </div>
                        <div class="pharmacist-detail">
                            <span class="detail-label">Phone:</span>
                            <span id="modal-phone">Loading...</span>
                        </div>
                        <div class="pharmacist-detail">
                            <span class="detail-label">License Number:</span>
                            <span id="modal-license">Loading...</span>
                        </div>
                        <div class="pharmacist-detail">
                            <span class="detail-label">Address:</span>
                            <span id="modal-address">Loading...</span>
                        </div>
                        <div class="pharmacist-detail">
                            <span class="detail-label">Specialization:</span>
                            <span id="modal-specialization">Loading...</span>
                        </div>
                        <div class="pharmacist-detail">
                            <span class="detail-label">Experience:</span>
                            <span id="modal-experience">Loading...</span>
                        </div>
                        <div class="pharmacist-detail">
                            <span class="detail-label">Status:</span>
                            <span id="modal-status">Loading...</span>
                        </div>
                        <div class="pharmacist-detail">
                            <span class="detail-label">Joined Date:</span>
                            <span id="modal-joined">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="timestamp">
                Last updated: <?php echo date("F j, Y, g:i a"); ?>
            </div>
        </div>
    </div>
    
    <script>
        // Function to view pharmacist details
        function viewPharmacist(id) {
            // In a real application, you would use AJAX to fetch the details
            // For this example, we'll simulate it
            
            // Get the modal
            var modal = document.getElementById("pharmacistModal");
            
            // Display the modal
            modal.style.display = "block";
            
            // In a real application, you would do something like this:
            /*
            fetch('get_pharmacist.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modal-name').textContent = data.name;
                    document.getElementById('modal-email').textContent = data.email;
                    document.getElementById('modal-phone').textContent = data.phone;
                    document.getElementById('modal-license').textContent = data.license_number;
                    document.getElementById('modal-address').textContent = data.address;
                    document.getElementById('modal-specialization').textContent = data.specialization;
                    document.getElementById('modal-experience').textContent = data.experience + ' years';
                    document.getElementById('modal-status').textContent = data.status;
                    document.getElementById('modal-joined').textContent = data.joined_date;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load pharmacist details');
                });
            */
            
            // For demo purposes, we'll just populate with dummy data
            // Replace this with actual AJAX in your implementation
            setTimeout(function() {
                document.getElementById('modal-name').textContent = "John Doe";
                document.getElementById('modal-email').textContent = "john.doe@example.com";
                document.getElementById('modal-phone').textContent = "+1 (123) 456-7890";
                document.getElementById('modal-license').textContent = "PHR-12345";
                document.getElementById('modal-address').textContent = "123 Medical Center, City";
                document.getElementById('modal-specialization').textContent = "Clinical Pharmacy";
                document.getElementById('modal-experience').textContent = "5 years";
                document.getElementById('modal-status').textContent = "Active";
                document.getElementById('modal-joined').textContent = "January 15, 2023";
            }, 500);
        }
        
        // Function to close the modal
        function closeModal() {
            document.getElementById("pharmacistModal").style.display = "none";
        }
        
        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            var modal = document.getElementById("pharmacistModal");
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Function to confirm delete
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this pharmacist?")) {
                // In a real application, you would redirect to a delete script or use AJAX
                // For example: window.location.href = "delete_pharmacist.php?id=" + id;
                alert("Pharmacist deleted successfully!");
            }
        }
        
        // Function to change tab
        function changeTab(status) {
            // Update active tab UI
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            event.target.classList.add("active");
            
            // Filter table rows based on status
            var rows = document.getElementsByClassName("pharmacist-row");
            for (var i = 0; i < rows.length; i++) {
                if (status === 'all' || rows[i].getAttribute('data-status') === status) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
        
        // Function to search pharmacists
        function searchPharmacists() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toUpperCase();
            var table = document.getElementById("pharmacistsTable");
            var tr = table.getElementsByTagName("tr");
            
            for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header row
                var found = false;
                var td = tr[i].getElementsByTagName("td");
                
                for (var j = 0; j < td.length; j++) {
                    if (td[j]) {
                        var txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                if (found) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>