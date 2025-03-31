<?php
session_start();
include("db_connection.php");

/*if (!isset($_SESSION['user_email'])) {
    die("Error: User not logged in. Please log in first.");
}*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $doctor_email = $_POST['doctor_email'];  // Ensure the form input has name="doctor_email"
    $health_data = $_POST['health_data'];
    
    // Check if doctor_email is set properly
    if (empty($doctor_email)) {
        die("Error: Doctor selection is required.");
    }

    $sql = "INSERT INTO consultations (user_id, doctor_id, health_data, status) VALUES (?, ?, ?, 'pending')";
$stmt->bind_param("iis", $user_id, $doctor_id, $health_data);

    $stmt->bind_param("iss", $user_id, $doctor_email, $health_data);
    
    if ($stmt->execute()) {
        echo "Consultation request sent!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<html>
    <body>
<form method="post">
    <label>Select Doctor:</label>
    <select name="doctor_email">
    <?php
    $doctors = $conn->query("SELECT email, full_name FROM signup WHERE role = 'doctor'");
    while ($doctor = $doctors->fetch_assoc()) {
        echo "<option value='{$doctor['email']}'>{$doctor['full_name']}</option>";
    }
    ?>
</select>

    <label>Describe Your Health Issue:</label>
    <textarea name="health_data"></textarea>
    <button type="submit">Request Consultation</button>
</form>
    </body>
    </html>
