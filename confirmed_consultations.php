<?php
session_start();
require 'db_connect.php'; // Database connection

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    die("Error: Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

// Fetch confirmed consultations for the user
$sql = "SELECT c.id, d.full_name AS doctor_name, c.health_data, c.meet_link, c.status 
        FROM consultations c 
        JOIN signup d ON c.doctor_id = d.id 
        WHERE c.user_id = ? AND c.status = 'Accepted'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmed Consultations</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #007bff; color: white; }
        a { text-decoration: none; color: blue; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Confirmed Consultations</h2>
    <table>
        <tr>
            <th>Doctor Name</th>
            <th>Health Data</th>
            <th>Meet Link</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                <td><?php echo htmlspecialchars($row['health_data']); ?></td>
                <td>
                    <?php if (!empty($row['meet_link'])) { ?>
                        <a href="<?php echo htmlspecialchars($row['meet_link']); ?>" target="_blank">Join Meet</a>
                    <?php } else { ?>
                        Not Available
                    <?php } ?>
                </td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
