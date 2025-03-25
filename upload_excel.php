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

$duplicateMedicines = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excel_file"])) {
    $file = $_FILES["excel_file"]["tmp_name"];

    try {
        // Load the Excel file
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Remove header row
        array_shift($data);

        foreach ($data as $row) {
            $name = $conn->real_escape_string($row[0]);
            $category = $conn->real_escape_string($row[1]);
            $price = floatval($row[2]);
            $quantity = intval($row[3]);

            // Convert the expiry_date to 'Y-m-d' format
            $expiry_date = DateTime::createFromFormat('m/d/Y', $row[4])->format('Y-m-d');
            $manufacturer = $conn->real_escape_string($row[5]);

            // Check if medicine exists
            $checkQuery = "SELECT id FROM medicines WHERE name='$name' AND category='$category'";
            $result = $conn->query($checkQuery);

            if ($result->num_rows > 0) {
                // Medicine already exists, store in duplicate list
                $duplicateMedicines[] = $name;
            } else {
                // Insert new medicine record
                $sql = "INSERT INTO medicines (name, category, price, quantity, expiry_date, manufacturer) 
                        VALUES ('$name', '$category', '$price', '$quantity', '$expiry_date', '$manufacturer')";

                if (!$conn->query($sql)) {
                    echo "Error inserting record: " . $conn->error . "<br>";
                }
            }
        }

        $_SESSION['success_message'] = "Data successfully imported!";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error loading file: " . $e->getMessage();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeleCare+ Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2E7D32;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
            --accent: #FF9800;
            --text-primary: #212121;
            --text-secondary: #757575;
            --background: #F5F5F5;
            --card-bg: #FFFFFF;
            --border: #E0E0E0;
            --success: #4CAF50;
            --warning: #FFC107;
            --error: #F44336;
            --info: #2196F3;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--background);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(to bottom, var(--primary), var(--primary-dark));
            height: 100vh;
            position: fixed;
            color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            opacity: 0.8;
            font-size: 14px;
        }
        
        .sidebar-menu {
            padding: 15px 0;
        }
        
        .menu-label {
            padding: 12px 20px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            opacity: 0.7;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: var(--accent);
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            min-width: 25px;
            text-align: center;
        }
        
        .user-area {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: var(--primary);
        }
        
        .user-info {
            flex-grow: 1;
        }
        
        .user-name {
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .user-role {
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
            transition: all 0.3s ease;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 24px;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }
        
        .breadcrumb {
            display: flex;
            list-style: none;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .breadcrumb li:not(:last-child)::after {
            content: '/';
            margin: 0 8px;
        }
        
        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .message i {
            margin-right: 12px;
            font-size: 20px;
        }
        
        .success {
            background-color: rgba(76, 175, 80, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .error {
            background-color: rgba(244, 67, 54, 0.1);
            border-left: 4px solid var(--error);
            color: var(--error);
        }
        
        .warning {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid var(--warning);
            color: var(--warning);
        }
        
        .duplicate-list {
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 15px;
        }
        
        .duplicate-list h3 {
            color: var(--warning);
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .duplicate-list ul {
            padding-left: 20px;
        }
        
        .duplicate-list li {
            margin-bottom: 5px;
            color: var(--text-secondary);
        }
        
        .file-upload-container {
            border: 2px dashed var(--border);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background-color: rgba(0, 0, 0, 0.01);
            margin-bottom: 20px;
        }
        
        .file-upload-container:hover {
            border-color: var(--primary-light);
            background-color: rgba(0, 128, 0, 0.02);
        }
        
        .file-upload-container i {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .file-upload-container h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .file-upload-container p {
            color: var(--text-secondary);
            margin-bottom: 10px;
        }
        
        .file-input {
            display: none;
        }
        
        .file-name {
            display: inline-block;
            padding: 8px 15px;
            background-color: rgba(76, 175, 80, 0.1);
            border-radius: 20px;
            color: var(--primary);
            font-size: 14px;
            margin-top: 10px;
            display: none;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: #f0f0f0;
            color: var(--text-secondary);
        }
        
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        
        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background-color: rgba(76, 175, 80, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 24px;
            margin-right: 15px;
        }
        
        .stat-details {
            flex-grow: 1;
        }
        
        .stat-title {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .sidebar.active {
                width: 260px;
                padding: initial;
            }
            
            .menu-toggle {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                padding: 10px;
                border-radius: 5px;
                background-color: var(--primary);
                color: white;
                cursor: pointer;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>TeleCare+</h2>
            <p>Pharmacy Management System</p>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-label">Main</div>
            <ul>
                <li><a href="upload_excel.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="logout.php" class="Logout.php"><i class="fas fa-pills"></i>Logout</a></li>
                
            
            
        </div>
        
        <div class="user-area">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <div class="user-name">Vendor </div>
                
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Vendor Inventory Management</h1>
            <ul class="breadcrumb">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="#">Medicines</a></li>
                <li>Import Data</li>
            </ul>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-pills"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-title">Total Medicines</div>
                    <div class="stat-value">1,427</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-title">Low Stock Items</div>
                    <div class="stat-value">24</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-title">Expiring Soon</div>
                    <div class="stat-value">18</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-title">Categories</div>
                    <div class="stat-value">32</div>
                </div>
            </div>
        </div>
        
        <!-- Main Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Import Medicines from Excel</h2>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($duplicateMedicines)): ?>
                <div class="duplicate-list">
                    <h3><i class="fas fa-exclamation-triangle"></i> Duplicate Medicines Found:</h3>
                    <ul>
                        <?php foreach ($duplicateMedicines as $medicine): ?>
                            <li><?php echo htmlspecialchars($medicine); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" enctype="multipart/form-data" id="uploadForm">
                <div class="file-upload-container" id="dropArea">
                    <i class="fas fa-file-excel"></i>
                    <h3>Drag & Drop Excel File</h3>
                    <p>or click to browse files</p>
                    <span id="fileName" class="file-name"></span>
                    <input type="file" name="excel_file" id="fileInput" class="file-input" accept=".xlsx, .xls" required>
                </div>
                
                <div class="actions">
                    <button type="button" class="btn btn-secondary" onclick="location.href='dashboard.php'">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // File upload interactivity
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        
        dropArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                fileName.textContent = fileInput.files[0].name;
                fileName.style.display = 'inline-block';
            }
        });
        
        // Drag and drop functionality
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.style.borderColor = '#4CAF50';
            dropArea.style.backgroundColor = 'rgba(0, 128, 0, 0.05)';
        });
        
        dropArea.addEventListener('dragleave', () => {
            dropArea.style.borderColor = '#E0E0E0';
            dropArea.style.backgroundColor = 'rgba(0, 0, 0, 0.01)';
        });
        
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.style.borderColor = '#E0E0E0';
            dropArea.style.backgroundColor = 'rgba(0, 0, 0, 0.01)';
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileName.textContent = e.dataTransfer.files[0].name;
                fileName.style.display = 'inline-block';
                
                // Trigger the change event manually
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        });
        
        // For small screen sidebar toggle
        function addMenuToggle() {
            if (window.innerWidth <= 768) {
                const toggle = document.createElement('div');
                toggle.className = 'menu-toggle';
                toggle.innerHTML = '<i class="fas fa-bars"></i>';
                document.body.appendChild(toggle);
                
                toggle.addEventListener('click', () => {
                    document.querySelector('.sidebar').classList.toggle('active');
                });
            }
        }
        
        // Call function on page load
        window.addEventListener('load', addMenuToggle);
        window.addEventListener('resize', addMenuToggle);
    </script>
</body>
</html>