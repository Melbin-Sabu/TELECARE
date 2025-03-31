<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "telecare+";

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch pending consultations
$sql = "SELECT * FROM consultations WHERE status = 'pending'";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Consultations</title>
</head>
<body>
    <h2>Pending Consultations</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>Doctor Email</th>
            <th>Health Data</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['user_id'] ?></td>
            <td><?= $row['doctor_email'] ?></td>
            <td><?= $row['health_data'] ?></td>
            <td><?= $row['status'] ?></td>
            <td>
                <form method="POST" action="approve_consultation.php">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit">Approve</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>

<?php mysqli_close($conn); ?>
