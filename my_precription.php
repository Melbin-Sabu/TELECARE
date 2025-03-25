<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$userId = $_SESSION['user_id'];

// Fetch user's prescriptions
$stmt = $conn->prepare("SELECT id, file_name, uploaded_at FROM prescriptions WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Prescriptions</title>
</head>
<body>
    <h2>Your Uploaded Prescriptions</h2>
    <table border="1">
        <tr>
            <th>File Name</th>
            <th>Uploaded At</th>
            <th>Download</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['file_name']); ?></td>
            <td><?php echo $row['uploaded_at']; ?></td>
            <td><a href="download_prescription.php?id=<?php echo $row['id']; ?>">Download</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
