<?php
session_start();
require 'db_connect.php'; // Database connection

// Ensure doctor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    die("Error: Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $consultation_id = $_POST['consultation_id'];
    $action = $_POST['action'];
    $doctor_id = $_SESSION['user_id'];

    if ($action === 'accept') {
        // Ensure a Google Meet link is provided
        if (!isset($_POST['meet_link']) || empty($_POST['meet_link'])) {
            die("Error: Meet link is required for acceptance.");
        }
        $meet_link = $_POST['meet_link'];

        // Update the consultation status to "Accepted" with the Meet link
        $sql = "UPDATE consultations SET status = 'Accepted', meet_link = ? WHERE id = ? AND doctor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $meet_link, $consultation_id, $doctor_id);
    } elseif ($action === 'reject') {
        // Update the consultation status to "Rejected"
        $sql = "UPDATE consultations SET status = 'Rejected' WHERE id = ? AND doctor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $consultation_id, $doctor_id);
    } else {
        die("Error: Invalid action.");
    }

    if ($stmt->execute()) {
        echo "<script>alert('Consultation updated successfully.'); window.location.href='docdash.php';</script>";
    } else {
        echo "Error updating consultation: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
} else {
    die("Error: Invalid request.");
}
?>
