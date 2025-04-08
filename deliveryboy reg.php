<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "telecare+");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Registration handling
if (isset($_POST['register'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password
    $address = $conn->real_escape_string($_POST['address']);
    $vehicle = $conn->real_escape_string($_POST['vehicle']);

    $sql = "INSERT INTO delivery_boys (name, phone, email, password, address, vehicle)
            VALUES ('$name', '$phone', '$email', '$password', '$address', '$vehicle')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Registration successful!'); window.location='deliveryboy_login.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delivery Boy Registration - TELECARE+</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; }
        .container { width: 400px; padding: 20px; background: white; margin: 100px auto; border-radius: 10px; box-shadow: 0 0 10px gray; }
        input[type=text], input[type=email], input[type=password] {
            width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 5px;
        }
        button { background-color: #4CAF50; color: white; padding: 10px; width: 100%; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #45a049; }
    </style>
</head>
<body>

<div class="container">
    <h2>Delivery Boy Registration</h2>
    <form method="POST" action="">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="address" placeholder="Address" required>
        <input type="text" name="vehicle" placeholder="Vehicle Info (Optional)">
        <button type="submit" name="register">Register</button>
    </form>
</div>

</body>
</html>
