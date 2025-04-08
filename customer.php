<?php
session_start();
// Start the session at the very top of the file, before any HTML

$role = "customer";  // Assuming the user role is customer. Modify if needed.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['fullname']);
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];
    $address = trim($_POST['Address']);
    $place = $_POST['place'];
   
$errors = [];

    // Validate Full Name
    if (empty($name)) {
        $errors['fullname'] = "Please enter your full name";
    } elseif (strlen($name) < 3) {
        $errors['fullname'] = "Name must be at least 3 characters long";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $name)) {
        $errors['fullname'] = "Name should contain only letters and spaces";
    }

    // Validate Email
    if (empty($email)) {
        $errors['email'] = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address";
    }

    // Validate Password
    if (empty($password)) {
        $errors['password'] = "Please enter your password";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters long";
    }

    // Validate Confirm Password
    if ($password !== $confirm_password) {
        $errors['confirm-password'] = "Passwords do not match";
    }

    // Validate Permanent Address
    if (empty($address)) {
        $errors['Address'] = "Please enter your address";
    }

    // Validate Place Selection
    if (empty($place)) {
        $errors['place'] = "Please select a place";
    }

    // If there are no errors, proceed to insert data into the database
    if (empty($errors)) {
        $_SESSION['fullname']=$name;
        $_SESSION['email']=$email;
        $_SESSION['password']=$password;
        $_SESSION['address']=$address;
        $_SESSION['place']=$place;
        $_SESSION['role']=$role;+-
        header('Location: http://localhost/miniproject/otp_email.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TELECARE+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
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
            background-image: linear-gradient(135deg, rgba(40, 167, 69, 0.9) 0%, rgba(32, 201, 151, 0.9) 100%);
            padding: 2rem;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        header img {
            position: absolute;
            top: 50%;
            left: 2rem;
            transform: translateY(-50%);
            height: 90px;
            width: 90px;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        header h1 {
            color: #fff;
            font-size: 2.5rem;
            margin-left: 60px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .modern-nav {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 1.2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .nav-link {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #fff;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }

        .register-container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.5s ease;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h2 {
            color: #28a745;
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }

        .register-header p {
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

        .form-group input, .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: #28a745;
            outline: none;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }

        .register-button {
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

        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
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

        /* Add animation for error message */
        .error-message {
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .form-group.error .error-message {
            opacity: 1;
            transform: translateY(0);
        }
    </style>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TELECARE+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Your existing CSS code remains here, with no changes needed */
    </style>
</head>
<body>
    <header>
        <img src="logo.png" alt="Logo">
        <h1>TELECARE+</h1>
    </header>

    <nav class="modern-nav">
        <a href="index.html" class="nav-link">Home</a>
        <a href="feature.html" class="nav-link">Features</a>
        <a href="about as.html" class="nav-link">About Us</a>
        <a href="contact.html" class="nav-link">Contact</a>
        <a href="login.php" class="nav-link">Login</a>
        <a href="registration.html" class="nav-link">Register</a>
    </nav>

    <div class="register-container">
        <div class="register-header">
            <h2>Create Your Account</h2>
            <p>Join TELECARE+ today</p>
        </div>

        <form id="registerForm" method="POST" novalidate>
            <div class="form-group <?php echo isset($errors['fullname']) ? 'error' : ''; ?>">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" value="<?php echo isset($name) ? $name : ''; ?>" required>
                <div class="error-message"><?php echo isset($errors['fullname']) ? $errors['fullname'] : ''; ?></div>
            </div>
            <div class="form-group <?php echo isset($errors['email']) ? 'error' : ''; ?>">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($email) ? $email : ''; ?>" required>
                <div class="error-message"><?php echo isset($errors['email']) ? $errors['email'] : ''; ?></div>
            </div>
            <div class="form-group <?php echo isset($errors['password']) ? 'error' : ''; ?>">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <div class="error-message"><?php echo isset($errors['password']) ? $errors['password'] : ''; ?></div>
            </div>
            <div class="form-group <?php echo isset($errors['confirm-password']) ? 'error' : ''; ?>">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
                <div class="error-message"><?php echo isset($errors['confirm-password']) ? $errors['confirm-password'] : ''; ?></div>
            </div>
            <div class="form-group <?php echo isset($errors['Address']) ? 'error' : ''; ?>">
                <label for="Address">Permanent Address</label>
                <input type="text" id="Address" name="Address" placeholder="Enter your address" value="<?php echo isset($address) ? $address : ''; ?>" required>
                <div class="error-message"><?php echo isset($errors['Address']) ? $errors['Address'] : ''; ?></div>
            </div>
           
        
            <div class="form-group <?php echo isset($errors['place']) ? 'error' : ''; ?>">
                <label for="place">Select Place</label>
                <select id="place" name="place" required>
                    <option value="" disabled selected>Select your place</option>
                    <option value="Kanjirappally" <?php echo (isset($place) && $place == 'Kanjirappally') ? 'selected' : ''; ?>>Kanjirappally</option>
                    <option value="Mundakkayam" <?php echo (isset($place) && $place == 'Mundakkayam') ? 'selected' : ''; ?>>Mundakkayam</option>
                    <option value="Ponkunnum" <?php echo (isset($place) && $place == 'Ponkunnum') ? 'selected' : ''; ?>>Ponkunnum</option>
                    <option value="Podimattom" <?php echo (isset($place) && $place == 'Podimattom') ? 'selected' : ''; ?>>Podimattom</option>
                    <option value="Koovappally" <?php echo (isset($place) && $place == 'Koovappally') ? 'selected' : ''; ?>>Koovappally</option>
                </select>
                <div class="error-message"><?php echo isset($errors['place']) ? $errors['place'] : ''; ?></div>
            </div>
            
            <button type="submit" class="register-button">Register</button>
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>

    <footer>
        <p>&copy; 2025 TELECARE+ Medicine Distribution System.</p>
    </footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fullname = document.getElementById('fullname');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm-password');
        const address = document.getElementById('Address');
        const place = document.getElementById('place');
        const form = document.getElementById('registerForm');

        const validateFullName = () => {
            const fullNameValue = fullname.value.trim();
            const nameRegex = /^[a-zA-Z]+(?: [a-zA-Z]+)*$/;  // Allows only letters and spaces between words
            
            if (fullNameValue === '') {
                setError(fullname, 'Please enter your full name');
            } else if (fullNameValue.length < 3) {
                setError(fullname, 'Name must be at least 3 characters long');
            } else if (fullNameValue.length > 50) {
                setError(fullname, 'Name must not exceed 50 characters');
            } else if (!nameRegex.test(fullNameValue)) {
                setError(fullname, 'Name should contain only letters and spaces');
            } else if (fullNameValue.split(' ').length < 2) {
                setError(fullname, 'Please enter your full name (first & last name)');
            } else if (/\s{2,}/.test(fullNameValue)) {
                setError(fullname, 'Multiple spaces are not allowed');
            } else if (/[^a-zA-Z\s]/.test(fullNameValue)) {
                setError(fullname, 'Special characters and numbers are not allowed');
            } else {
                setSuccess(fullname);
            }
        };

        const validateEmail = () => {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (emailRegex.test(email.value.trim())) {
                setSuccess(email);
            } else {
                setError(email, 'Please enter a valid email address');
            }
        };

        const validatePassword = () => {
            if (password.value.trim().length < 6) {
                setError(password, 'Password must be at least 6 characters long');
            } else {
                setSuccess(password);
            }
        };

        const validateConfirmPassword = () => {
            if (confirmPassword.value !== password.value) {
                setError(confirmPassword, 'Passwords do not match');
            } else {
                setSuccess(confirmPassword);
            }
        };

        const validateAddress = () => {
            if (address.value.trim() === '') {
                setError(address, 'Please enter your Address');
            } else {
                setSuccess(address);
            }
        };

        const validatePlace = () => {
            if (place.value === '') {
                setError(place, 'Please select a place');
            } else {
                setSuccess(place);
            }
        };

        const setError = (input, message) => {
            const formGroup = input.closest('.form-group');
            formGroup.classList.add('error');
            formGroup.classList.remove('success');
            formGroup.querySelector('.error-message').textContent = message;
        };

        const setSuccess = (input) => {
            const formGroup = input.closest('.form-group');
            formGroup.classList.add('success');
            formGroup.classList.remove('error');
        };

        // Live validation with debounce
        let timeout;
        fullname.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(validateFullName, 500); // Validate after 500ms of user stopping typing
        });

        // Validate on blur (when user leaves the field)
        fullname.addEventListener('blur', validateFullName);

        // Live validation
        email.addEventListener('input', validateEmail);
        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validateConfirmPassword);
        address.addEventListener('input', validateAddress);
        place.addEventListener('change', validatePlace);

        // Submit validation
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            validateFullName();
            validateEmail();
            validatePassword();
            validateConfirmPassword();
            validateAddress();
            validatePlace();

            if (document.querySelectorAll('.error').length === 0) {
                console.log('Form is valid! Submitting...');
                form.submit(); // Actually submit the form if everything is valid
            }
        });
    });
</script>
</body>
</html>
