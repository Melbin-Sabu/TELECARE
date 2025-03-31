<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "telecare+";

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Update status to 'assigned'
    $sql = "UPDATE consultations SET status = 'assigned' WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Consultation approved successfully!'); window.location.href='pending_consultations.php';</script>";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>
