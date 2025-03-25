<?php
include 'db.php';

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $company = htmlspecialchars(trim($_POST['company']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']);

    // Validate input
    if (empty($name) || strlen($name) < 3) $errors['name'] = "Full name must be at least 3 characters.";
    if (empty($company) || strlen($company) < 3) $errors['company'] = "Company name must be at least 3 characters.";
    if (!preg_match("/^\d{10}$/", $phone)) $errors['phone'] = "Phone number must be exactly 10 digits.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format.";
    
    // Enhanced password validation matching JavaScript checks
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
    
    if (strlen($password) < 8 || !$hasUppercase || !$hasNumber || !$hasSpecial) {
        $requirements = [];
        if (strlen($password) < 8) $requirements[] = "at least 8 characters";
        if (!$hasUppercase) $requirements[] = "1 uppercase letter";
        if (!$hasNumber) $requirements[] = "1 number";
        if (!$hasSpecial) $requirements[] = "1 special character";
        
        $errors['password'] = "Password requires: " . implode(", ", $requirements);
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM vendor WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors['email'] = "Email is already registered.";
    }
    $stmt->close();

    // If no errors, insert into DB
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO vendor (name, company, phone, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $company, $phone, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $success = "Registration successful! You can now <a href='login.php'>login</a>.";
        } else {
            $errors['general'] = "Something went wrong. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Registration</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            position: absolute; top: 50%; left: 2rem; transform: translateY(-50%);
            height: 90px; width: 90px; border: 3px solid white; border-radius: 50%;
        }
        header h1 {
            color: #fff; font-size: 2.5rem; margin-left: 60px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        .modern-nav {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 1.2rem; display: flex; justify-content: center;
            gap: 2.5rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px);
        }
        .nav-link {
            color: #333; text-decoration: none; font-weight: 500;
            padding: 0.8rem 1.5rem; border-radius: 25px;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: #fff; background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            transform: translateY(-2px);
        }
        .register-container {
            max-width: 500px; margin: 3rem auto; padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95); border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 1.5rem; position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%; padding: 1rem; border: 2px solid #e0e0e0;
            border-radius: 10px; font-size: 1rem; transition: all 0.3s ease;
        }
        .form-group input:focus {
            border-color: #28a745;
            outline: none;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }
        .error-message {
            color: #dc3545; font-size: 0.85rem; margin-top: 0.3rem;
        }
        .success-message {
            color: #28a745; font-size: 1rem; margin-bottom: 1.5rem;
            padding: 0.8rem; background-color: rgba(40, 167, 69, 0.1);
            border-radius: 10px; text-align: center;
        }
        .register-button {
            width: 100%; padding: 1rem;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none; border-radius: 10px; color: white;
            font-size: 1.1rem; font-weight: 600; cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        .form-group.invalid input {
            border-color: #dc3545;
        }
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
        <a href="about.html" class="nav-link">About Us</a>
        <a href="contact.html" class="nav-link">Contact</a>
        <a href="login.php" class="nav-link">Login</a>
        <a href="registration.html" class="nav-link">Register</a>
    </nav>

    <div class="register-container">
        <h2 style="margin-bottom: 1.5rem; text-align: center;">Vendor Registration</h2>
        
        <?php if (!empty($success)): ?>
        <div class="success-message">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
        <div class="error-message" style="text-align: center; margin-bottom: 1.5rem;">
            <?php echo $errors['general']; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="registrationForm">
            <div class="form-group <?php echo isset($errors['name']) ? 'invalid' : ''; ?>">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                <div class="error-message" id="name-error" <?php echo isset($errors['name']) ? 'style="display:block"' : ''; ?>>
                    <?php echo isset($errors['name']) ? $errors['name'] : ''; ?>
                </div>
            </div>
            <div class="form-group <?php echo isset($errors['company']) ? 'invalid' : ''; ?>">
                <label for="company">Company Name</label>
                <input type="text" id="company" name="company" placeholder="Enter your company name" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                <div class="error-message" id="company-error" <?php echo isset($errors['company']) ? 'style="display:block"' : ''; ?>>
                    <?php echo isset($errors['company']) ? $errors['company'] : ''; ?>
                </div>
            </div>
            <div class="form-group <?php echo isset($errors['phone']) ? 'invalid' : ''; ?>">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="Enter 10-digit phone number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                <div class="error-message" id="phone-error" <?php echo isset($errors['phone']) ? 'style="display:block"' : ''; ?>>
                    <?php echo isset($errors['phone']) ? $errors['phone'] : ''; ?>
                </div>
            </div>
            <div class="form-group <?php echo isset($errors['email']) ? 'invalid' : ''; ?>">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <div class="error-message" id="email-error" <?php echo isset($errors['email']) ? 'style="display:block"' : ''; ?>>
                    <?php echo isset($errors['email']) ? $errors['email'] : ''; ?>
                </div>
            </div>
            <div class="form-group <?php echo isset($errors['password']) ? 'invalid' : ''; ?>">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a strong password">
                <div class="error-message" id="password-error" <?php echo isset($errors['password']) ? 'style="display:block"' : ''; ?>>
                    <?php echo isset($errors['password']) ? $errors['password'] : ''; ?>
                </div>
            </div>
            <button type="submit" class="register-button">Register</button>
            <p>Already have an account? <a href="vendorlogin.php">Login here</a></p>
        </form>
    </div>

    <script>
        // Enhanced form validation functions
        function validateField(id, minLength, errorMessage) {
            let value = document.getElementById(id).value.trim();
            let error = document.getElementById(id + "-error");
            
            if (value.length < minLength) {
                error.textContent = errorMessage || `${id.charAt(0).toUpperCase() + id.slice(1)} must be at least ${minLength} characters.`;
                error.style.display = "block";
                return false;
            } else {
                error.style.display = "none";
                return true;
            }
        }

        function validatePhone() {
            let phone = document.getElementById("phone").value.trim();
            let error = document.getElementById("phone-error");
            let phoneRegex = /^\d{10}$/;
            
            if (!phoneRegex.test(phone)) {
                error.textContent = "Please enter a valid 10-digit phone number.";
                error.style.display = "block";
                return false;
            } else {
                error.style.display = "none";
                return true;
            }
        }

        function validateEmail() {
            let email = document.getElementById("email").value.trim();
            let error = document.getElementById("email-error");
            let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                error.textContent = "Please enter a valid email address.";
                error.style.display = "block";
                return false;
            } else {
                error.style.display = "none";
                return true;
            }
        }

        function validatePassword() {
            let password = document.getElementById("password").value;
            let error = document.getElementById("password-error");
            
            let hasLength = password.length >= 8;
            let hasUppercase = /[A-Z]/.test(password);
            let hasNumber = /[0-9]/.test(password);
            let hasSpecial = /[^a-zA-Z0-9]/.test(password);
            
            if (!hasLength || !hasUppercase || !hasNumber || !hasSpecial) {
                let requirements = [];
                if (!hasLength) requirements.push("at least 8 characters");
                if (!hasUppercase) requirements.push("1 uppercase letter");
                if (!hasNumber) requirements.push("1 number");
                if (!hasSpecial) requirements.push("1 special character");
                
                error.textContent = "Password requires: " + requirements.join(", ");
                error.style.display = "block";
                return false;
            } else {
                error.style.display = "none";
                return true;
            }
        }

        function validateForm() {
            let isNameValid = validateField('name', 3, "Full name must be at least 3 characters.");
            let isCompanyValid = validateField('company', 3, "Company name must be at least 3 characters.");
            let isPhoneValid = validatePhone();
            let isEmailValid = validateEmail();
            let isPasswordValid = validatePassword();
            
            return isNameValid && isCompanyValid && isPhoneValid && isEmailValid && isPasswordValid;
        }

        // Add event listeners to form elements
        document.addEventListener('DOMContentLoaded', function() {
            // Set up field validation events
            document.getElementById('name').addEventListener('input', function() {
                validateField('name', 3, "Full name must be at least 3 characters.");
            });
            
            document.getElementById('company').addEventListener('input', function() {
                validateField('company', 3, "Company name must be at least 3 characters.");
            });
            
            document.getElementById('phone').addEventListener('input', validatePhone);
            document.getElementById('email').addEventListener('input', validateEmail);
            document.getElementById('password').addEventListener('input', validatePassword);
            
            // Set up form submission validation
            document.getElementById('registrationForm').addEventListener('submit', function(event) {
                if (!validateForm()) {
                    event.preventDefault();
                }
            });
            
            // Initialize error messages (for when PHP returns errors)
            let errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(function(element) {
                if (element.textContent.trim() !== '') {
                    element.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>