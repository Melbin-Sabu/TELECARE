<?php
session_start();
require 'db_connect.php';

//if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
  //  die("Unauthorized access.");
//}

$userId = $_SESSION['user_id'];
$prescriptionId = intval($_GET['id']);

// Get file details from DB
$stmt = $conn->prepare("SELECT file_name, file_path FROM prescriptions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $prescriptionId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $filePath = __DIR__ . '/' . $row['file_path'];

    if (file_exists($filePath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    } else {
        die("File not found.");
    }
} else {
    die("No record found.");
}

$conn->close();
?>
