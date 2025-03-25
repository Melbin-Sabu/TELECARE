<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "telecare+";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Default SQL query
$sql = "SELECT * FROM medicines";

// Search & Filter Logic
$conditions = [];
if (!empty($_GET['search_name'])) {
    $search_name = $conn->real_escape_string($_GET['search_name']);
    $conditions[] = "name LIKE '%$search_name%'";
}
if (!empty($_GET['category'])) {
    $category = $conn->real_escape_string($_GET['category']);
    $conditions[] = "category = '$category'";
}
if (!empty($_GET['expiry_from']) && !empty($_GET['expiry_to'])) {
    $expiry_from = $conn->real_escape_string($_GET['expiry_from']);
    $expiry_to = $conn->real_escape_string($_GET['expiry_to']);
    $conditions[] = "expiry_date BETWEEN '$expiry_from' AND '$expiry_to'";
}

// Apply conditions if any
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$result = $conn->query($sql);

// Fetch categories for the filter dropdown
$categoryResult = $conn->query("SELECT DISTINCT category FROM medicines");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Medicines</title>
</head>
<body>
    <h2>Uploaded Medicines</h2>

    <!-- Search & Filter Form -->
    <form method="GET" action="">
        <input type="text" name="search_name" placeholder="Search by name" value="<?php echo isset($_GET['search_name']) ? $_GET['search_name'] : ''; ?>">
        
        <select name="category">
            <option value="">Filter by Category</option>
            <?php while ($row = $categoryResult->fetch_assoc()): ?>
                <option value="<?php echo $row['category']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $row['category']) ? 'selected' : ''; ?>>
                    <?php echo $row['category']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Expiry From:</label>
        <input type="date" name="expiry_from" value="<?php echo isset($_GET['expiry_from']) ? $_GET['expiry_from'] : ''; ?>">
        
        <label>Expiry To:</label>
        <input type="date" name="expiry_to" value="<?php echo isset($_GET['expiry_to']) ? $_GET['expiry_to'] : ''; ?>">

        <button type="submit">Search</button>
        <a href="admindash.php"><button type="button">Reset</button></a>
    </form>

    <table border='1'>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Expiry Date</th>
            <th>Manufacturer</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row["id"]; ?></td>
                <td><?php echo $row["name"]; ?></td>
                <td><?php echo $row["category"]; ?></td>
                <td><?php echo $row["price"]; ?></td>
                <td><?php echo $row["quantity"]; ?></td>
                <td><?php echo $row["expiry_date"]; ?></td>
                <td><?php echo $row["manufacturer"]; ?></td>
            </tr>
        <?php endwhile; ?>

    </table>

    <p><a href="upload_medicines.php">Upload More Medicines</a></p>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>
