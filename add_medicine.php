<?php
include 'db.php'; // Include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $quantity = $_POST["quantity"];
    $price = $_POST["price"];
    $vendor_id = 1; // Replace with the actual logged-in vendor ID

    $sql = "INSERT INTO medicines (name, quantity, price, vendor_id) 
            VALUES ('$name', '$quantity', '$price', '$vendor_id')";
    
    if (mysqli_query($conn, $sql)) {
        echo "Medicine added successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<form method="POST">
    Medicine Name: <input type="text" name="name" required><br>
    Quantity: <input type="number" name="quantity" required><br>
    Price: <input type="number" name="price" step="0.01" required><br>
    <button type="submit">Add Medicine</button>
</form>
