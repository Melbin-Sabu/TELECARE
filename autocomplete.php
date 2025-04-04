<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "telecare+";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['search'])) {
    $search_term = trim($_GET['search']);
    $sql = "SELECT name FROM medicines WHERE name LIKE ? ORDER BY name LIMIT 5";
    $stmt = $conn->prepare($sql);
    $search_param = "%" . $search_term . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode($suggestions);
}

$conn->close();
?>
