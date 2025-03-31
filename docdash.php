<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo "Error: Doctor not logged in. Please log in first.";
    exit();
}

$doctor_id = $_SESSION['user_id']; // ✅ Use user_id since it's a doctor

include("db_connection.php");

// Fetch pending consultations
$sql = "SELECT c.id, c.user_id, s.full_name, c.health_data, c.status, c.meet_link
        FROM consultations c
        JOIN signup s ON c.user_id = s.id
        WHERE c.doctor_id = ? AND c.status = 'pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Doctor Dashboard</title>
</head>
<body>
    <h2>Consultation Requests</h2>
    <table border="1">
        <tr>
            <th>Patient Name</th>
            <th>Health Data</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['full_name']; ?></td>
                <td><?php echo $row['health_data']; ?></td>
                <td><?php echo $row['status']; ?></td>
                <td>
                    <form method="post" action="update_consultation.php">
                        <input type="hidden" name="consultation_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="accept">
                        <input type="text" name="meet_link" placeholder="Enter Google Meet Link" required>
                        <button type="submit">Accept</button>
                    </form>
                    <form method="post" action="update_consultation.php">
                        <input type="hidden" name="consultation_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit">Reject</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
