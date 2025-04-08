<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is a pharmacist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
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

$success_message = $error_message = "";

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prescription_id'], $_POST['status'])) {
    $prescription_id = $_POST['prescription_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE prescriptions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $prescription_id);
    if ($stmt->execute()) {
        $success_message = "Prescription status updated successfully!";
    } else {
        $error_message = "Failed to update status: " . $stmt->error;
    }
    $stmt->close();
}

// Handle multiple medicine assignment to cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prescription_id'], $_POST['medicine_ids'], $_POST['quantities'])) {
    $prescription_id = $_POST['prescription_id'];
    $medicine_ids = $_POST['medicine_ids'];
    $quantities = $_POST['quantities'];

    // Fetch user_id from prescriptions
    $user_stmt = $conn->prepare("SELECT user_id FROM prescriptions WHERE id = ?");
    $user_stmt->bind_param("i", $prescription_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_result->num_rows > 0) {
        $user_id = $user_result->fetch_assoc()['user_id'];
    } else {
        $error_message = "Invalid prescription ID.";
        $user_stmt->close();
        goto end_processing;
    }
    $user_stmt->close();

    $stmt = $conn->prepare("INSERT INTO cart (user_id, medicine_id, quantity) VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
    $errors = [];

    for ($i = 0; $i < count($medicine_ids); $i++) {
        $medicine_id = (int)$medicine_ids[$i];
        $quantity = (int)$quantities[$i];

        if ($medicine_id && $quantity > 0) {
            // Check stock availability
            $stock_stmt = $conn->prepare("SELECT name, quantity FROM medicines WHERE id = ?");
            $stock_stmt->bind_param("i", $medicine_id);
            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result();
            if ($stock_row = $stock_result->fetch_assoc()) {
                if ($quantity <= $stock_row['quantity']) {
                    $stmt->bind_param("iii", $user_id, $medicine_id, $quantity);
                    if (!$stmt->execute()) {
                        $errors[] = "Failed to add " . htmlspecialchars($stock_row['name']) . ": " . $stmt->error;
                    }
                } else {
                    $errors[] = "Insufficient stock for " . htmlspecialchars($stock_row['name']) . " (Available: " . $stock_row['quantity'] . ")";
                }
            } else {
                $errors[] = "Medicine ID $medicine_id not found.";
            }
            $stock_stmt->close();
        }
    }
    $stmt->close();

    if (empty($errors)) {
        $success_message = "Medicines added to user’s cart successfully!";
    } else {
        $error_message = implode("<br>", $errors);
    }
}

end_processing:
// Fetch all prescriptions
$query = "SELECT p.id, p.file_path, p.uploaded_at, p.status, s.full_name 
          FROM prescriptions p 
          JOIN signup s ON p.user_id = s.id 
          ORDER BY p.uploaded_at DESC";
$result = $conn->query($query);

// Fetch all medicines
$medicines_result = $conn->query("SELECT id, name, price, quantity FROM medicines");

// Get statistics
$total_prescriptions = $result->num_rows;
$pending = 0;
$verified = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] == 'Pending') $pending++;
        if ($row['status'] == 'Verified') $verified++;
    }
    $result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist Dashboard - Telecare+</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #4CAF50;
            --light-green: #E8F5E9;
            --dark-green: #2E7D32;
        }
        body {
            background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(180deg, var(--dark-green) 0%, #1B5E20 100%);
        }
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .status-pending { background-color: #FFF3E0; color: #F57C00; }
        .status-verified { background-color: #E8F5E9; color: #2E7D32; }
        .status-rejected { background-color: #FEE2E2; color: #B91C1C; }
        .medicine-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .message { margin-bottom: 20px; padding: 10px; border-radius: 5px; }
        .success { background-color: #D4EDDA; color: #155724; }
        .error { background-color: #F8D7DA; color: #721C24; }
    </style>
</head>
<body>
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar w-64 h-screen fixed left-0 top-0 text-white p-4">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold">Telecare+</h1>
                <p class="text-sm">Pharmacy Dashboard</p>
            </div>
            <div class="space-y-4">
                <a href="#" class="flex items-center space-x-2 p-3 rounded-lg bg-white bg-opacity-10 hover:bg-opacity-20">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
                <a href="#" class="flex items-center space-x-2 p-3 rounded-lg hover:bg-white hover:bg-opacity-10">
                    <i class="fas fa-pills"></i><span>Medicines</span>
                </a>
                <a href="#" class="flex items-center space-x-2 p-3 rounded-lg hover:bg-white hover:bg-opacity-10">
                    <i class="fas fa-users"></i><span>Customers</span>
                </a>
                <a href="logout.php" class="flex items-center space-x-2 p-3 rounded-lg hover:bg-white hover:bg-opacity-10">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="ml-64 p-8 w-full">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Prescription Management</h2>
                <div class="relative">
                    <input type="text" placeholder="Search prescriptions..." 
                           class="px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-green-500">
                    <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="card p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-file-medical text-blue-500 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Total Prescriptions</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $total_prescriptions; ?></p>
                        </div>
                    </div>
                </div>
                <div class="card p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-clock text-yellow-500 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Pending Review</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $pending; ?></p>
                        </div>
                    </div>
                </div>
                <div class="card p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-gray-500 text-sm">Verified</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $verified; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prescriptions Table -->
            <div class="card overflow-hidden">
                <div class="p-6">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left bg-gray-50">
                                <th class="px-6 py-3 text-gray-600">Customer</th>
                                <th class="px-6 py-3 text-gray-600">Uploaded Date</th>
                                <th class="px-6 py-3 text-gray-600">Prescription</th>
                                <th class="px-6 py-3 text-gray-600">Status</th>
                                <th class="px-6 py-3 text-gray-600">Assign Medicines</th>
                                <th class="px-6 py-3 text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-500"></i>
                                        </div>
                                        <span class="ml-3"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?php echo date('M d, Y', strtotime($row['uploaded_at'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?php echo htmlspecialchars($row['file_path']); ?>" 
                                       class="text-blue-500 hover:text-blue-700" target="_blank">
                                        <i class="fas fa-eye mr-2"></i>View
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-sm <?php 
                                        echo $row['status'] == 'Pending' ? 'status-pending' : 
                                            ($row['status'] == 'Verified' ? 'status-verified' : 'status-rejected'); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <form method="post" class="space-y-2" id="form-<?php echo $row['id']; ?>">
                                        <input type="hidden" name="prescription_id" value="<?php echo $row['id']; ?>">
                                        <div id="medicine-rows-<?php echo $row['id']; ?>">
                                            <div class="medicine-row">
                                                <select name="medicine_ids[]" 
                                                        class="rounded-lg border px-3 py-1">
                                                    <option value="">Select Medicine</option>
                                                    <?php 
                                                    $medicines_result->data_seek(0);
                                                    while ($med = $medicines_result->fetch_assoc()): ?>
                                                        <option value="<?php echo $med['id']; ?>">
                                                            <?php echo htmlspecialchars($med['name']) . " (₹" . $med['price'] . ", Stock: " . $med['quantity'] . ")"; ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <input type="number" name="quantities[]" 
                                                       placeholder="Qty" min="1" class="rounded-lg border px-3 py-1 w-16">
                                                <button type="button" class="remove-row text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" 
                                                class="add-medicine bg-gray-200 text-gray-700 px-2 py-1 rounded-lg hover:bg-gray-300"
                                                data-prescription-id="<?php echo $row['id']; ?>">
                                            Add More
                                        </button>
                                        <button type="submit" 
                                                class="bg-blue-500 text-white px-2 py-1 rounded-lg hover:bg-blue-600">
                                            Add to Cart
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4">
                                    <form method="post" class="flex items-center space-x-2">
                                        <input type="hidden" name="prescription_id" value="<?php echo $row['id']; ?>">
                                        <select name="status" 
                                                class="rounded-lg border px-3 py-1 focus:outline-none focus:ring-2 focus:ring-green-500">
                                            <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Verified" <?php echo $row['status'] == 'Verified' ? 'selected' : ''; ?>>Verified</option>
                                            <option value="Rejected" <?php echo $row['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                        <button type="submit" 
                                                class="bg-green-500 text-white px-4 py-1 rounded-lg hover:bg-green-600">
                                            Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.add-medicine').forEach(button => {
            button.addEventListener('click', function() {
                const prescriptionId = this.getAttribute('data-prescription-id');
                const container = document.getElementById(medicine-rows-${prescriptionId});
                const newRow = document.createElement('div');
                newRow.className = 'medicine-row';
                newRow.innerHTML = `
                    <select name="medicine_ids[]" class="rounded-lg border px-3 py-1">
                        <option value="">Select Medicine</option>
                        <?php 
                        $medicines_result->data_seek(0);
                        while ($med = $medicines_result->fetch_assoc()): ?>
                            <option value="<?php echo $med['id']; ?>">
                                <?php echo htmlspecialchars($med['name']) . " (₹" . $med['price'] . ", Stock: " . $med['quantity'] . ")"; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <input type="number" name="quantities[]" placeholder="Qty" min="1" class="rounded-lg border px-3 py-1 w-16">
                    <button type="button" class="remove-row text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                container.appendChild(newRow);
                attachRemoveListeners();
            });
        });

        function attachRemoveListeners() {
            document.querySelectorAll('.remove-row').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.parentElement;
                    if (row.parentElement.children.length > 1) {
                        row.remove();
                    }
                });
            });
        }

        attachRemoveListeners();
    </script>
</body>
</html>

<?php $conn->close(); ?>
