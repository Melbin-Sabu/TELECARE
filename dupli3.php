<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'telecare+');

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$sql = "SELECT * FROM pharmacists WHERE id='" . $_SESSION['user_id'] . "'";
$result = $conn->query($sql);

if ($result && $result->num_rows <1) {
  header('Location:pharinnerdash.php');    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pharmacist Dashboard - TELECARE+</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      background: #f0f2f5;
    }
    .header {
      background: linear-gradient(135deg, rgb(17, 172, 37), #11101d);
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
    .dashboard h2 {
      color: #11101d;
      margin-bottom: 20px;
      font-size: 28px;
    }
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
  </style>
</head>
<body>
  <div class="header">
    <div class="logo-section">
      <img src="logo.png" alt="TELECARE+ Logo" />
      <h1>TELECARE+ Pharmacist Dashboard</h1>
    </div>
    <div class="user-section">
    <p class="user-name">Welcome,</p>
<a href="logout.php" class="logout">Logout</a>
    </div>
  </div>
  <div class="dashboard">
    <h2>Pharmacist Services</h2>
    <div class="services">
      <div class="service-card">
        <i class="fa-solid fa-pills"></i>
        <h3>Manage Medicines</h3>
        <p>Add, update, and manage the inventory of medicines.</p>
        <a href="#">Manage Now</a>
      </div>
      <div class="service-card">
        <i class="fa-solid fa-user-injured"></i>
        <h3>View Prescriptions</h3>
        <p>Check and verify customer prescriptions before dispensing.</p>
        <a href="#">View Now</a>
      </div>
      <div class="service-card">
        <i class="fa-solid fa-truck-medical"></i>
        <h3>Process Orders</h3>
        <p>Handle and manage medicine orders efficiently.</p>
        <a href="#">Process Now</a>
      </div>
    </div>
  </div>
</body>
</html>
