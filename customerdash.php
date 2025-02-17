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

// Search functionality
$search_results = [];
if (isset($_POST['search'])) {
    $search_term = '%' . $_POST['search_term'] . '%';
    $search_stmt = $conn->prepare("SELECT * FROM medicines WHERE name LIKE ? OR description LIKE ?");
    $search_stmt->bind_param("ss", $search_term, $search_term);
    $search_stmt->execute();
    $result = $search_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $search_results[] = $row;
    }
    $search_stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Dashboard - TELECARE+</title>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <!-- Add Font Awesome in the head section -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
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
    /* Updated logo style for a circular shape */
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
    .search-section {
      margin: 20px 0;
      text-align: center;
    }
    .search-section input[type="text"] {
      width: 60%;
      padding: 15px 20px;
      font-size: 16px;
      border: 2px solid #4CAF50;  /* Green border */
      border-radius: 8px;
      margin-right: 15px;
      transition: all 0.3s ease;
    }
    .search-section input[type="text"]:focus {
      outline: none;
      border-color: #2E7D32;  /* Darker green on focus */
      box-shadow: 0 0 10px rgba(76, 175, 80, 0.2);  /* Green shadow */
    }
    .search-section button {
      padding: 15px 30px;
      font-size: 16px;
      background: #4CAF50;  /* Green button */
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .search-section button:hover {
      background: #2E7D32;  /* Darker green on hover */
      transform: translateY(-2px);
    }
    .search-results {
      background: #f9f9f9;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
    }
    .search-results h3 {
      margin-top: 0;
      font-size: 22px;
      color: #333;
    }
    .medicine-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .medicine-list li {
      padding: 20px;
      border: 1px solid #e0e0e0;
      border-radius: 5px;
      background: #fff;
      margin-bottom: 15px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .medicine-list li:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .medicine-list h4 {
      margin: 0 0 10px;
      color: #1d1b31;
    }
    .medicine-list p {
      margin: 5px 0;
      color: #555;
      font-size: 15px;
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
      border-top: 4px solid #4CAF50;  /* Green accent */
    }
    .service-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 15px rgba(76, 175, 80, 0.2);  /* Green shadow on hover */
    }
    .service-card i {
      font-size: 50px;
      color: #4CAF50;  /* Green icon */
      margin-bottom: 15px;
    }
    .service-card h3 {
      color: #2E7D32;  /* Darker green for headings */
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
      background: #4CAF50;  /* Green button */
      color: white;
      text-decoration: none;
      border-radius: 6px;
      transition: all 0.3s ease;
    }
    .service-card a:hover {
      background: #2E7D32;  /* Darker green on hover */
    }
    @media (max-width: 768px) {
      .services {
        flex-direction: column;
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
    <h2>Search Medicines</h2>
    <div class="search-section">
      <form method="POST" action="">
        <input type="text" name="search_term" placeholder="Search for medicines..." required>
        <button type="submit" name="search"><i class="fas fa-search"></i> Search</button>
      </form>
    </div>

    <?php if (isset($_POST['search'])): ?>
      <div class="search-results">
        <h3>Search Results</h3>
        <?php if (empty($search_results)): ?>
          <p>No medicines found.</p>
        <?php else: ?>
          <ul class="medicine-list">
            <?php foreach ($search_results as $medicine): ?>
              <li>
                <h4><?php echo htmlspecialchars($medicine['name']); ?></h4>
                <p><?php echo htmlspecialchars($medicine['description']); ?></p>
                <p><strong>Price:</strong> $<?php echo htmlspecialchars($medicine['price']); ?></p>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <h2>Available Services</h2>
    <div class="services">
      <div class="service-card">
        <i class="fa-solid fa-clipboard-list"></i>
        <h3>View Prescriptions</h3>
        <p>Access and manage all your medical prescriptions in one place.</p>
        <a href="#"><i class="fa-solid fa-arrow-right"></i> View Details</a>
      </div>
      
      <div class="service-card">
        <i class="fa-solid fa-capsules"></i>
        <h3>Order Medicines</h3>
        <p>Order your prescribed medications with easy home delivery.</p>
        <a href="#"><i class="fa-solid fa-cart-shopping"></i> Order Now</a>
      </div>
      
      <div class="service-card">
        <i class="fa-solid fa-heart-pulse"></i>
        <h3>Health Monitoring</h3>
        <p>Track your vital signs and health metrics for better wellness.</p>
        <a href="#"><i class="fa-solid fa-chart-line"></i> Monitor Now</a>
      </div>
    </div>
  </div>
  <!-- FontAwesome for icons -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
