<?php
session_start();
include 'db_connect.php';

$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $qualification = trim($_POST['qualification']);
    $experience = (int)$_POST['experience'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $consultation_fee = (float)$_POST['consultation_fee'];
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($qualification)) {
        $errors[] = "Qualification is required";
    }
    
    if ($experience < 0) {
        $errors[] = "Experience cannot be negative";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Check if email already exists
    $check_email = "SELECT id FROM doctors WHERE email = ?";
    $check_stmt = $conn->prepare($check_email);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = "Email already registered. Please use a different email.";
    }
    $check_stmt->close();
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Handle profile image upload
        $profile_image = "";
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                $new_filename = uniqid() . '.' . $filetype;
                $upload_dir = 'uploads/doctor_profiles/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $profile_image = $upload_path;
                } else {
                    $errors[] = "Failed to upload image";
                }
            } else {
                $errors[] = "Invalid file type. Only JPG, JPEG, PNG and GIF are allowed.";
            }
        }
        
        if (empty($errors)) {
            // Insert into database
            $insert_sql = "INSERT INTO doctors (name, email, password, qualification, experience, phone, address, profile_image, consultation_fee) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssisssd", $name, $email, $hashed_password, $qualification, $experience, $phone, $address, $profile_image, $consultation_fee);
            
            if ($insert_stmt->execute()) {
                $success_message = "Registration successful! You can now login.";
                // Redirect to login page after 3 seconds
                header("refresh:3;url=login.php");
            } else {
                $error_message = "Error: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        } else {
            $error_message = implode("<br>", $errors);
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration - TeleCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .registration-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 500;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
        .form-section {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .form-section:last-child {
            border-bottom: none;
        }
        .preview-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <h2 class="text-center mb-4"><i class="fas fa-user-md me-2"></i>Doctor Registration</h2>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Personal Information -->
                <div class="form-section">
                    <h5 class="mb-3">Personal Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label required-field">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label required-field">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label required-field">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label required-field">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label required-field">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            <img id="image_preview" class="mt-2 preview-image" src="#" alt="Profile Preview">
                        </div>
                    </div>
                </div>
                
                <!-- Professional Information -->
                <div class="form-section">
                    <h5 class="mb-3">Professional Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="qualification" class="form-label required-field">Qualification</label>
                            <input type="text" class="form-control" id="qualification" name="qualification" required 
                                   placeholder="MD, University of California" value="<?= isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="experience" class="form-label required-field">Years of Experience</label>
                            <input type="number" class="form-control" id="experience" name="experience" min="0" required value="<?= isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : '' ?>">
                        </div>

                    </div>
                    <div class="mb-3">
                        <label for="consultation_fee" class="form-label required-field">Consultation Fee ($)</label>
                        <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" min="0" step="0.01" required value="<?= isset($_POST['consultation_fee']) ? htmlspecialchars($_POST['consultation_fee']) : '' ?>">
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div class="form-section">
                    <h5 class="mb-3">Additional Information</h5>
                    <div class="mb-3">
                        <label for="address" class="form-label">Office Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Register as Doctor</button>
                </div>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="doctor_login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const preview = document.getElementById('image_preview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html> 