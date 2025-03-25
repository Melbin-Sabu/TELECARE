<?php
include 'db.php'; // Include your database connection

function getPharmacists($conn) {
    $sql = "SELECT * FROM pharmacists ORDER BY last_name ASC";
    $result = $conn->query($sql);

    $pharmacists = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pharmacists[] = $row;
        }
    }
    return $pharmacists;
}

$pharmacists = getPharmacists($conn);
?>
