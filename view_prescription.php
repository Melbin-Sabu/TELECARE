<?php
session_start();
require 'db_connect.php'; // Ensure `$conn` is initialized

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$userId = $_SESSION['user_id']; // Get the logged-in user's ID

// Fetch uploaded prescriptions for the user
$stmt = $conn->prepare("SELECT id, file_name, file_path, uploaded_at FROM prescriptions WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>View Prescriptions</title>
</head>
<body>
    <h2>Your Uploaded Prescriptions</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>File Name</th>
            <th>Uploaded At</th>
            <th>View</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['id']); ?></td>
            <td><?= htmlspecialchars($row['file_name']); ?></td>
            <td><?= htmlspecialchars($row['uploaded_at']); ?></td>
            <td>
                <a href="download_prescription.php?id=<?= $row['id']; ?>" target="_blank">View</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php $conn->close(); ?>
