<?php
$host = "localhost";    // Change this if your database is hosted elsewhere
$dbname = "telecare+"; // Your database name
$username = "root";     // Your database username (default is 'root' for XAMPP)
$password = "";         // Your database password (leave empty for XAMPP)

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character encoding (UTF-8)
$conn->set_charset("utf8");

?>
