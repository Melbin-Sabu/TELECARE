<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$servername = "localhost"; // Change if needed
$username = "root"; // Your MySQL username
$password = ""; // Your MySQL password
$database = "telecare+"; // Your database name

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";
$success_message = "";
$sql1 = "SELECT * FROM pharmacists WHERE id = '" . $_SESSION['user_id'] . "'";
$result = mysqli_query($conn,$sql1);
$user = $result->fetch_assoc();
if ($result && $result->num_rows === 1) {
    header('Location:pharmasistdash.php');
    exit();

}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $qualification = trim($_POST['qualification']);
    $license = trim($_POST['license']);
    $upload_dir = "uploads/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = basename($_FILES["file"]["name"]);
    $file_path = $upload_dir . $file_name;
    $file_type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $allowed_types = ["pdf", "jpg", "jpeg", "png"];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (empty($name) || empty($qualification) || empty($license)) {
        $error_message = "All fields are required.";
    } elseif (!preg_match("/^[a-zA-Z0-9]+$/", $license)) {
        $error_message = "License number should be alphanumeric.";
    } elseif ($_FILES["file"]["size"] > $max_size) {
        $error_message = "File must be smaller than 5MB.";
    } elseif (!in_array($file_type, $allowed_types)) {
        $error_message = "Only PDF, JPG, and PNG files are allowed.";
    } else {
        if (!isset($_SESSION['user_id'])) {
            $error_message = "You must be logged in to register as a pharmacist.";
        } else {
            $signup_id = $_SESSION['user_id'];
          

            $check_signup = $conn->prepare("SELECT id FROM signup WHERE id = ?");
            $check_signup->bind_param("i", $signup_id);
            $check_signup->execute();
            $check_signup->store_result();

            if ($check_signup->num_rows > 0) {
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $file_path)) {
                    $stmt = $conn->prepare("INSERT INTO pharmacists (name, qualification, license, file_path, id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssi", $name, $qualification, $license, $file_path, $signup_id);

                    if ($stmt->execute()) {
                        $success_message = "Registration successful!";
                        header('Location:pharmasistdash.php');
                    } else {
                        $error_message = "Error: " . $stmt->error;
                        // echo $_SESSION['user_id'];
                    }

                    $stmt->close();
                } else {
                    $error_message = "File upload failed.";
                }
            } else {
                $error_message = "No matching signup ID found.";
            }

            $check_signup->close();
        }
    }
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('pharmasist.avif') no-repeat center center fixed;
            background-size: cover;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .form-group input[type="file"] {
            padding: 0;
        }
        .form-group button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #45a049;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            background-color: #f2f2f2;
            border: 1px solid #ccc;
            border-radius: 5px;
            color: green;
        }
        .message.error {
            color: red;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Pharmacist Registration Form</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php elseif (!empty($success_message)): ?>
            <div class="message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="qualification">Qualification</label>
                <input type="text" id="qualification" name="qualification" required>
            </div>

            <div class="form-group">
                <label for="license">License Number</label>
                <input type="text" id="license" name="license" required>
            </div>

            <div class="form-group">
                <label for="file">Upload File</label>
                <input type="file" id="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <div class="form-group">
                <button type="submit">Register</button>
            </div>
        </form>
    </div>

</body>
</html>
