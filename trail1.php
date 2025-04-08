<?php
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: index.html");
    exit();
}

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'telecare+';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$upload_message = isset($_SESSION['upload_message']) ? $_SESSION['upload_message'] : "";
unset($_SESSION['upload_message']); // Clear message after displaying

// Ensure upload directory exists
$upload_dir = 'uploads/prescriptions/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['prescription'])) {
    $file = $_FILES['prescription'];
    $file_name = basename($file['name']);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $file_size = $file['size'];
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];

    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['upload_message'] = "Invalid file type. Allowed types: JPG, PNG, PDF.";
    } elseif ($file_size > 5000000) { // 5MB limit
        $_SESSION['upload_message'] = "File size exceeds 5MB.";
    } else {
        $unique_name = time() . "" . uniqid() . "" . $file_name;
        $file_path = $upload_dir . $unique_name;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $stmt = $conn->prepare("INSERT INTO prescriptions (user_id, file_name, file_path, file_type, file_size, uploaded_at, status) 
                                    VALUES (?, ?, ?, ?, ?, NOW(), 'Pending')");
            if ($stmt) {
                $stmt->bind_param("isssi", $user_id, $file_name, $file_path, $file_type, $file_size);
                if ($stmt->execute()) {
                    $_SESSION['upload_message'] = "Prescription uploaded successfully!";
                } else {
                    $_SESSION['upload_message'] = "Database error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['upload_message'] = "Prepare statement failed: " . $conn->error;
            }
        } else {
            $_SESSION['upload_message'] = "Error uploading file. Check directory permissions.";
        }
    }
    // Redirect to prevent resubmission
    header("Location: trail1.php");
    exit();
}

// Fetch user's prescriptions
$query = "SELECT id, file_name, file_path, uploaded_at, status 
          FROM prescriptions 
          WHERE user_id = ? 
          ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Query prepare failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Prescription - Telecare+</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #4CAF50 0%, #2E7D32 100%);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px;
            color: white;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 10px;
            background: white;
            color: #4CAF50;
            font-weight: bold;
            border-radius: 8px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background: #388E3C;
            color: white;
        }
        .main-content {
            margin-left: 270px;
            padding: 20px;
            min-height: 100vh;
            background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
        }
        .message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }
        .success { background-color: #D4EDDA; color: #155724; }
        .error { background-color: #F8D7DA; color: #721C24; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 class="text-xl font-bold text-white">TELECARE+</h2>
        <a href="trail1.php"><i class="fas fa-upload mr-2"></i> Upload Prescription</a>
        <a href="healthmonito.php"><i class="fas fa-chart-line mr-2"></i> Health Monitoring</a>
        <a href="ordermedi.php"><i class="fas fa-shopping-cart mr-2"></i> Order Medicines</a>
        <a href="cart.php"><i class="fas fa-shopping-bag mr-2"></i> Cart</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>

    <div class="main-content">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Upload Prescription</h1>

        <?php if ($upload_message): ?>
            <div class="message <?php echo strpos($upload_message, 'success') !== false ? 'success' : 'error'; ?>">
                <?php echo $upload_message; ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <form method="POST" enctype="multipart/form-data">
                <label class="block text-gray-700 mb-2" for="prescription">Upload Prescription (JPG, PNG, PDF, max 5MB):</label>
                <input type="file" name="prescription" id="prescription" accept=".jpg,.jpeg,.png,.pdf" 
                       class="w-full px-3 py-2 border rounded-lg" required>
                <button type="submit" 
                        class="mt-4 bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600">
                    Upload
                </button>
            </form>
        </div>

        <!-- Prescription List -->
        <?php if (!empty($prescriptions)): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Your Prescriptions</h2>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="px-4 py-2 text-left">File Name</th>
                            <th class="px-4 py-2 text-left">Uploaded Date</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prescriptions as $prescription): ?>
                            <tr class="border-t hover:bg-green-50">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($prescription['file_name']); ?></td>
                                <td class="px-4 py-2"><?php echo date('M d, Y', strtotime($prescription['uploaded_at'])); ?></td>
                                <td class="px-4 py-2">
                                    <span class="px-3 py-1 rounded-full text-sm <?php 
                                        echo $prescription['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-700' : 
                                            ($prescription['status'] == 'Verified' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                                        <?php echo htmlspecialchars($prescription['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <a href="<?php echo htmlspecialchars($prescription['file_path']); ?>" 
                                       target="_blank" class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-eye mr-2"></i>View
                                    </a>
                                    <?php if ($prescription['status'] == 'Verified'): ?>
                                        <a href="cart.php" class="ml-4 text-green-500 hover:text-green-700">
                                            <i class="fas fa-shopping-cart mr-2"></i>Go to Cart
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <strong class="font-bold">No Prescriptions!</strong>
                <span class="block sm:inline"> You haven’t uploaded any prescriptions yet.</span>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?>
