<?php
session_start();
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create database connection
    $conn = new mysqli('localhost', 'root', '', 'telecare+');

    // Check for connection error
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        $error_message = "An error occurred during login. Please try again later.";
    } else {
        // Sanitize user inputs
        $email = $conn->real_escape_string(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'];

        // Query to fetch vendor data
        $sql = "SELECT * FROM vendor WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows === 1) {
            $vendor = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $vendor['password'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id();
                $_SESSION['vendor_id'] = $vendor['id'];
                $_SESSION['role'] = $vendor['role'];

                // Redirect to the desired page after successful login
                header('Location: upload_medicine.php');
                exit();
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "Invalid email or password.";
        }

        // Close the database connection
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<header>
    <img src="logo.png" alt="TELECARE+ Logo">
    <h1>Vendor Login - TELECARE+</h1>
</header>

    <style>
       {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('https://img.freepik.com/free-vector/white-abstract-background_23-2148806276.jpg');
            background-size: cover;
            background-attachment: fixed;
            color: #333;
            line-height: 1.6;
        }

        header {
    display: flex;
    align-items: center;
    justify-content: center;
    background-image: linear-gradient(135deg, rgba(40, 167, 69, 0.9) 0%, rgba(32, 201, 151, 0.9) 100%);
    padding: 2rem;
    position: relative;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    text-align: center;
}

header img {
    position: absolute;
    left: 2rem;
    height: 90px;
    width: 90px;
    border: 3px solid white;
    border-radius: 50%;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

header h1 {
    margin: 0 auto;
    color: #fff;
    font-size: 2.5rem;
    text-shadow: 2px 2px 4px rgba(17, 17, 17, 0.2);
}

        .modern-nav {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 1.2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
        }

        .auth-links {
            display: flex;
            gap: 1rem;
            margin-right: 2rem;
        }

        .nav-link {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .auth-btn {
            padding: 0.8rem 1.8rem;
            border-radius: 25px;
            font-weight: 600;
        }

        .highlight {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }

        .highlight-secondary {
            background: white;
            color: #28a745;
            border: 2px solid #28a745;
        }

        .login-container {
            max-width: 400px;
            margin: 3rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.5s ease;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            color: #28a745;
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }

        .login-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #444;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #28a745;
            outline: none;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }

        .form-group.error input {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
        }

        .form-group.success input {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }

        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
        }

        .form-group.error .error-message {
            display: block;
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .register-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #218838;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        footer {
            background-image: linear-gradient(135deg, rgba(52, 58, 64, 0.95) 0%, rgba(33, 37, 41, 0.95) 100%);
            color: #fff;
            text-align: center;
            padding: 2rem 0;
            margin-top: 3rem;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<div class="nav-links">
            <a href="index.html" class="nav-link">Home</a>
            <a href="feature.html" class="nav-link">Features</a>
            <a href="about as.html" class="nav-link">About Us</a>
            <a href="contact.html" class="nav-link">Contact</a>
        </div>

        
<body>
    <div class="login-container">
        <h2>Vendor Login</h2>

        <!-- Display error message if any -->
        <?php if (!empty($error_message)) { ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php } ?>

        <form method="POST" action="upload_excel.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-button">Sign In</button>
           
        </form>
    </div>
</body>
</html>
