<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'telecare+';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email FROM signup WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email);
$stmt->fetch();
$stmt->close();

// Count total users
$user_count_query = "SELECT COUNT(*) AS total_users FROM signup";
$user_count_result = $conn->query($user_count_query);
$total_users = 0;
if ($user_count_result->num_rows > 0) {
    $row = $user_count_result->fetch_assoc();
    $total_users = $row['total_users'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Dashboard - TELECARE+</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* Previous styles remain the same until dashboard class */
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      background: #f0f2f5;
    }
    .header {
      background: linear-gradient(135deg,rgb(17, 172, 37), #11101d);
      color: #fff;
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo-section {
      display: flex;
      align-items: center;
    }
    .logo-section img {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 10px;
    }
    .logo-section h1 {
      margin: 0;
      font-size: 24px;
    }
    .user-section {
      display: flex;
      align-items: center;
    }
    .user-name {
      margin-right: 20px;
      font-size: 16px;
    }
    .logout {
      background: #1d1b31;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
      color: #fff;
      transition: background 0.3s ease;
    }
    .logout:hover {
      background: #11101d;
    }
    .dashboard {
      max-width: 1100px;
      margin: 30px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    /* New styles for users stats card */
    .stats-card {
      background: linear-gradient(135deg, #4CAF50, #2E7D32);
      color: white;
      padding: 25px;
      border-radius: 15px;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 8px 20px rgba(46, 125, 50, 0.2);
    }
    .stats-info {
      flex: 1;
    }
    .stats-info h2 {
      margin: 0;
      font-size: 32px;
      color: white;
    }
    .stats-info p {
      margin: 5px 0 0;
      font-size: 16px;
      opacity: 0.9;
    }
    .stats-icon {
      font-size: 48px;
      margin-left: 20px;
      opacity: 0.8;
    }
    /* Rest of the existing styles remain the same */
    .services {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 30px;
    }
    .service-card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: all 0.3s ease;
      border-top: 4px solid #4CAF50;
      flex: 1;
      min-width: 250px;
    }
    .service-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 15px rgba(76, 175, 80, 0.2);
    }
    .service-card i {
      font-size: 50px;
      color: #4CAF50;
      margin-bottom: 15px;
    }
    .service-card h3 {
      color: #2E7D32;
      font-size: 20px;
      margin-bottom: 10px;
    }
    .service-card p {
      color: #707070;
      font-size: 15px;
      margin-bottom: 15px;
    }
    .service-card a {
      display: inline-block;
      padding: 10px 20px;
      background: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      transition: all 0.3s ease;
    }
    .service-card a:hover {
      background: #2E7D32;
    }
    @media (max-width: 768px) {
      .services {
        flex-direction: column;
      }
      .stats-card {
        flex-direction: column;
        text-align: center;
      }
      .stats-icon {
        margin: 20px 0 0;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo-section">
      <img src="logo.png" alt="TELECARE+ Logo" />
      <h1><marquee>TELECARE+</marquee></h1>
    </div>
    <div class="user-section">
      <p class="user-name">Welcome, <?php echo htmlspecialchars($name); ?>!</p>
      <a href="logout.php" class="logout">Logout</a>
    </div>
  </div>
  
  <div class="dashboard">
    <div class="stats-card">
      <div class="stats-info">
        <h2><?php echo number_format($total_users); ?></h2>
        <p>Total Registered Users</p>
      </div>
      <div class="stats-icon">
        <i class="fas fa-users"></i>
      </div>
    </div>

    <h2>Available Services</h2>
    <div class="services">
      <div class="service-card">
        <i class="fa-solid fa-clipboard-list"></i>
        <h3>upload Prescriptions</h3>
        <p>Access and manage all your medical prescriptions in one place.</p>
        <a href="trail1.php"><i class="fa-solid fa-arrow-right"></i> View Details</a>
      </div>
      
      <div class="service-card">
        <i class="fa-solid fa-capsules"></i>
        <h3>Order Medicines</h3>
        <p>Order your prescribed medications with easy home delivery.</p>
        <a href="ordermedi.php"><i class="fa-solid fa-cart-shopping"></i> Order Now</a>
      </div>
      
      <div class="service-card">
        <i class="fa-solid fa-heart-pulse"></i>
        <h3>Health Monitoring</h3>
        <p>Track your vital signs and health metrics for better wellness.</p>
        <a href="t1.php"><i class="fa-solid fa-chart-line"></i> Monitor Now</a>
      </div>
    </div>
  </div>
</body>
</html>