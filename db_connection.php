<?php
$servername = "localhost";  // Change if your database is hosted elsewhere
$username = "root";         // Default XAMPP username
$password = "";             // Default XAMPP password (empty)
$database = "telecare+";     // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
