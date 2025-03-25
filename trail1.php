<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'telecare+';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$prescriptions = [];
$upload_message = "";

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['prescription'])) {
    $file_name = basename($_FILES["prescription"]["name"]);
    
    // Check if this file was already uploaded by the user in the database
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE user_id = ? AND file_name = ?");
    $check_stmt->bind_param("is", $user_id, $file_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        $upload_message = '<div class="alert alert-error">This prescription already exists in the database!</div>';
    } else {
        $target_dir = "uploads/prescriptions/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_path = $target_dir . time() . "_" . uniqid() . "_" . $file_name;

        if (move_uploaded_file($_FILES["prescription"]["tmp_name"], $file_path)) {
            $stmt = $conn->prepare("INSERT INTO prescriptions (user_id, file_name, file_path, uploaded_at, status) VALUES (?, ?, ?, NOW(), ?)");
            $status = "Pending";
            $stmt->bind_param("isss", $user_id, $file_name, $file_path, $status);
            
            if ($stmt->execute()) {
                $upload_message = '<div class="alert alert-success">Prescription uploaded successfully!</div>';
            } else {
                $upload_message = '<div class="alert alert-error">Database error: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $upload_message = '<div class="alert alert-error">Error uploading file.</div>';
        }
    }
    $check_stmt->close();
}

// Fetch user's prescriptions
$stmt = $conn->prepare("SELECT file_name, file_path, uploaded_at, status FROM prescriptions WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$prescriptions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TELECARE+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            margin: 0;
            background: #f0f2f5;
            color: #333;
            min-height: 100vh;
        }

        /* Updated Sidebar Styles */
        .sidebar {
            width: 280px;
            background: rgb(14, 232, 72);
            padding: 30px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar h2 {
            color: white;
            font-size: 32px;
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            margin-bottom: 20px;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            padding: 15px 20px;
            display: block;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .sidebar ul li a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(10px);
        }

        /* Updated Main Content Styles */
        .container {
            margin-left: 300px;
            width: calc(100% - 340px);
            padding: 40px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
            margin-right: 20px;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 32px;
            margin-bottom: 30px;
            color: rgb(14, 232, 72);
        }

        .upload-box {
            border: 2px dashed rgb(14, 232, 72);
            padding: 40px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 40px;
            background: rgba(14, 232, 72, 0.05);
        }

        .upload-box input[type="file"] {
            display: block;
            margin: 20px auto;
            padding: 10px;
            font-size: 16px;
        }

        .upload-box button {
            padding: 15px 30px;
            border: none;
            cursor: pointer;
            background: rgb(14, 232, 72);
            color: white;
            border-radius: 8px;
            font-size: 18px;
            transition: 0.3s;
            font-weight: 500;
        }

        .upload-box button:hover {
            background: rgba(14, 232, 72, 0.8);
            transform: translateY(-2px);
        }

        .prescription-list {
            list-style: none;
            padding: 0;
        }

        .prescription-list li {
            padding: 20px;
            border-bottom: 1px solid rgba(14, 232, 72, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .prescription-list li:hover {
            background: rgba(14, 232, 72, 0.05);
            transform: translateX(10px);
        }

        .prescription-list a {
            text-decoration: none;
            color: rgb(14, 232, 72);
            font-weight: 500;
        }

        .status {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.verified {
            background: rgba(14, 232, 72, 0.1);
            color: rgb(14, 232, 72);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .alert-success {
            background: rgba(14, 232, 72, 0.1);
            color: rgb(14, 232, 72);
            border: 1px solid rgb(14, 232, 72);
        }

        .alert-error {
            background: #ffe0e0;
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                margin-left: 290px;
                width: calc(100% - 310px);
                padding: 30px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                padding: 20px 10px;
            }

            .sidebar h2 {
                font-size: 20px;
            }

            .sidebar ul li a {
                padding: 10px;
                font-size: 16px;
            }

            .container {
                margin-left: 90px;
                width: calc(100% - 110px);
                padding: 20px;
            }
        }

        /* Add these to your existing styles */
        .prescription-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .delete-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 5px 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            opacity: 0.7;
        }

        .delete-btn:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .prescription-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>TELECARE+</h2>
        <ul>
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Prescriptions</a></li>
            <li><a href="#">Orders</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    
    <div class="container">
        <h2>Your Prescriptions</h2>
        <?php if ($upload_message): ?>
            <?php echo $upload_message; ?>
        <?php endif; ?>
        <div class="upload-box">
            <form action="" method="post" enctype="multipart/form-data">
                <input type="file" name="prescription" required>
                <br><br>
                <button type="submit">Upload Prescription</button>
            </form>
        </div>
        <div>
            <?php if (!empty($prescriptions)): ?>
                <ul class="prescription-list">
                    <?php foreach ($prescriptions as $prescription): ?>
                        <li>
                            <div class="prescription-info">
                                <a href="<?php echo htmlspecialchars($prescription['file_path']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($prescription['file_name']); ?>
                                </a>
                            </div>
                            <div class="prescription-actions">
                                <span class="status <?php echo strtolower($prescription['status']); ?>">
                                    <?php echo $prescription['status']; ?>
                                </span>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this prescription?');">
                                    <input type="hidden" name="delete_prescription" value="<?php echo htmlspecialchars($prescription['file_path']); ?>">
                                    <button type="submit" class="delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No prescriptions uploaded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>