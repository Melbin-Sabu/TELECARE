<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Fetch user's recent vital signs for consultation
$sql = "SELECT * FROM vital_signs WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$vital_signs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch available doctors
$sql = "SELECT id, name, qualification, experience, consultation_fee 
        FROM doctors 
        WHERE status = 'active'";
$doctors_result = $conn->query($sql);
$doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);

// Fetch accepted consultations
$sql = "SELECT c.id, c.doctor_id, c.consultation_time, c.meet_link, d.name as doctor_name
        FROM consultations c
        JOIN doctors d ON c.doctor_id = d.id
        WHERE c.user_id = ? AND c.status = 'assigned'
        ORDER BY c.consultation_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$accepted_consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Add this new query to fetch pending consultations
$sql = "SELECT c.id, c.created_at, d.name as doctor_name, d.qualification
        FROM consultations c
        JOIN doctors d ON c.doctor_id = d.id
        WHERE c.user_id = ? AND c.status = 'pending'
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle consultation request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_consultation'])) {
    $doctor_id = $_POST['doctor_id'];
    
    // Prepare health data from recent vital signs
    $health_data = json_encode($vital_signs);
    
    $sql = "INSERT INTO consultations (user_id, doctor_id, health_data, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $user_id, $doctor_id, $health_data);
    
    if ($stmt->execute()) {
        $success_message = "Consultation request submitted successfully! You'll be notified when a doctor is assigned.";
    } else {
        $error_message = "Error submitting request: " . $conn->error;
    }
    $stmt->close();
}

// Handle consultation cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_consultation'])) {
    $consultation_id = $_POST['consultation_id'];
    
    $sql = "DELETE FROM consultations WHERE id = ? AND user_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $consultation_id, $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Consultation request cancelled successfully!";
        // Refresh the pending consultations list
        $sql = "SELECT c.id, c.created_at, d.name as doctor_name, d.qualification
                FROM consultations c
                JOIN doctors d ON c.doctor_id = d.id
                WHERE c.user_id = ? AND c.status = 'pending'
                ORDER BY c.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $pending_consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $error_message = "Error cancelling request: " . $conn->error;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Consultation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #3eed2b;
            --accent-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --card-shadow: 0 4px 8px rgba(0,0,0,0.1);
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --sidebar-bg: rgb(17, 172, 37); /* Updated to the new green color */
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: white; /* Changed to white for better contrast with the darker green */
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: white; /* Changed to white for better contrast */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white; /* Changed to white for better contrast */
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(62, 237, 43, 0.2);
            color: var(--dark-text);
            border-left: 4px solid var(--secondary-color);
        }
        
        .sidebar-menu i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-menu .menu-text {
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .menu-text {
            opacity: 0;
            display: none;
        }
        
        .sidebar-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            color: white; /* Changed to white for better contrast */
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            color: var(--secondary-color);
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        }
        
        /* Existing styles */
        .page-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-bg);
        }
        
        .doctor-card {
            border: none;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.15);
        }
        
        .vital-summary, .booking-summary, .pending-summary {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }
        
        .vital-summary {
            border-left: 5px solid var(--primary-color);
        }
        
        .booking-summary {
            border-left: 5px solid var(--secondary-color);
        }
        
        .pending-summary {
            border-left: 5px solid var(--accent-color);
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .btn-request {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #000;
        }
        
        .btn-request:hover {
            background-color: #32c825;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(59, 185, 45, 0.3);
        }
        
        .btn-cancel {
            background-color: var(--danger-color);
            border: none;
            padding: 8px 15px;
            border-radius: 50px;
            transition: all 0.3s ease;
            font-weight: 500;
            color: white;
            font-size: 0.9rem;
        }
        
        .btn-cancel:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
        }
        
        .booking-card, .pending-card {
            border: none;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            transition: transform 0.3s ease;
            position: relative;
        }
        
        .booking-card {
            border-left: 4px solid var(--secondary-color);
        }
        
        .pending-card {
            border-left: 4px solid var(--accent-color);
        }
        
        .booking-card:hover, .pending-card:hover {
            transform: translateX(5px);
        }
        
        .vital-item {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 3px solid var(--accent-color);
        }
        
        .doctor-info {
            display: flex;
            align-items: center;
        }
        
        .doctor-avatar {
            width: 60px;
            height: 60px;
            background-color: var(--light-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
            font-size: 24px;
        }
        
        .doctor-details h5 {
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .doctor-meta {
            display: flex;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .doctor-meta-item {
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: var(--light-text);
        }
        
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-badge.pending {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--accent-color);
        }
        
        .meet-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .meet-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .consultation-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--light-bg);
            padding-bottom: 10px;
        }
        
        .consultation-tab {
            padding: 10px 20px;
            margin-right: 10px;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .consultation-tab.active {
            background-color: var(--light-bg);
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }
        
        .consultation-tab:hover:not(.active) {
            background-color: rgba(248, 249, 250, 0.5);
        }
        
        .consultation-content {
            display: none;
        }
        
        .consultation-content.active {
            display: block;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--light-text);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: var(--sidebar-collapsed-width);
            }
            
            .sidebar.expanded {
                width: var(--sidebar-width);
            }
            
            .main-content {
                margin-left: var(--sidebar-collapsed-width);
            }
            
            .sidebar .menu-text {
                opacity: 0;
                display: none;
            }
            
            .sidebar.expanded .menu-text {
                opacity: 1;
                display: inline;
            }
            
            .container {
                margin: 20px auto;
                padding: 20px;
            }
            
            .doctor-meta {
                flex-direction: column;
            }
            
            .doctor-meta-item {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-heartbeat me-2"></i> <span class="menu-text">HealthTrack</span></h3>
            <button class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="t1.php" class="<?= basename($_SERVER['PHP_SELF']) == 't1.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="vital_signs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'vital_signs.php' ? 'active' : '' ?>">
                    <i class="fas fa-heartbeat"></i>
                    <span class="menu-text">Vital Signs</span>
                </a>
            </li>
            <li>
                <a href="book_consultation.php" class="<?= basename($_SERVER['PHP_SELF']) == 'book_consultation.php' ? 'active' : '' ?>">
                    <i class="fas fa-stethoscope"></i>
                    <span class="menu-text">Consultations</span>
                </a>
            </li>
            <li>
                <a href="chat.php" class="<?= basename($_SERVER['PHP_SELF']) == 'book_consultation.php' ? 'active' : '' ?>">
                    <i class="fas fa-stethoscope"></i>
                    <span class="menu-text">chat with pharmacist</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="container">
            <h2 class="text-center page-title">
                <i class="fas fa-stethoscope me-2"></i>
                Doctor Consultations
            </h2>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>

            <!-- Consultation Tabs -->
            <div class="consultation-tabs">
                <div class="consultation-tab active" data-tab="scheduled">Scheduled</div>
                <div class="consultation-tab" data-tab="pending">Pending Requests</div>
                <div class="consultation-tab" data-tab="new">New Consultation</div>
            </div>

            <!-- Scheduled Consultations Tab -->
            <div class="consultation-content active" id="scheduled-content">
                <?php if (!empty($accepted_consultations)): ?>
                    <div class="booking-summary">
                        <h4 class="section-title">
                            <i class="fas fa-calendar-check"></i>
                            Your Scheduled Consultations
                        </h4>
                        <?php foreach ($accepted_consultations as $consultation): ?>
                            <div class="booking-card">
                                <p>
                                    <strong>Doctor:</strong> Dr. <?= htmlspecialchars($consultation['doctor_name']) ?><br>
                                    <strong>Date & Time:</strong> <?= date('M d, Y h:i A', strtotime($consultation['consultation_time'])) ?><br>
                                    <strong>Meeting Link:</strong> 
                                    <a href="<?= htmlspecialchars($consultation['meet_link']) ?>" target="_blank" class="meet-link">
                                        Join Meeting <i class="fas fa-external-link-alt ms-1"></i>
                                    </a>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>You don't have any scheduled consultations yet.</p>
                        <button class="btn btn-request mt-3" onclick="showTab('new')">
                            <i class="fas fa-plus-circle me-2"></i>
                            Request a Consultation
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pending Requests Tab -->
            <div class="consultation-content" id="pending-content">
                <?php if (!empty($pending_consultations)): ?>
                    <div class="pending-summary">
                        <h4 class="section-title">
                            <i class="fas fa-clock"></i>
                            Your Pending Consultation Requests
                        </h4>
                        <?php foreach ($pending_consultations as $consultation): ?>
                            <div class="pending-card">
                                <span class="status-badge pending">Pending</span>
                                <p>
                                    <strong>Doctor:</strong> Dr. <?= htmlspecialchars($consultation['doctor_name']) ?><br>
                                    <strong>Qualification:</strong> <?= htmlspecialchars($consultation['qualification']) ?><br>
                                    <strong>Requested on:</strong> <?= date('M d, Y h:i A', strtotime($consultation['created_at'])) ?>
                                </p>
                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="consultation_id" value="<?= $consultation['id'] ?>">
                                    <button type="submit" name="cancel_consultation" class="btn btn-cancel">
                                        <i class="fas fa-times-circle me-1"></i>
                                        Cancel Request
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-hourglass-end"></i>
                        <p>You don't have any pending consultation requests.</p>
                        <button class="btn btn-request mt-3" onclick="showTab('new')">
                            <i class="fas fa-plus-circle me-2"></i>
                            Request a Consultation
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- New Consultation Tab -->
            <div class="consultation-content" id="new-content">
                <!-- Vital Signs Summary -->
                <div class="vital-summary">
                    <h4 class="section-title">
                        <i class="fas fa-heartbeat"></i>
                        Your Recent Health Data
                    </h4>
                    <p>This information will be shared with the doctor:</p>
                    
                    <?php if (!empty($vital_signs)): ?>
                        <?php foreach ($vital_signs as $vital): ?>
                            <div class="vital-item">
                                <?php 
                                $recorded_at = date('M d, Y h:i A', strtotime($vital['recorded_at']));
                                $vital_str = [];
                                if ($vital['blood_sugar']) $vital_str[] = "Blood Sugar: {$vital['blood_sugar']} mg/dL";
                                if ($vital['blood_pressure_systolic']) $vital_str[] = "Systolic BP: {$vital['blood_pressure_systolic']} mmHg";
                                if ($vital['blood_pressure_diastolic']) $vital_str[] = "Diastolic BP: {$vital['blood_pressure_diastolic']} mmHg";
                                if ($vital['oxygen_level']) $vital_str[] = "Oxygen: {$vital['oxygen_level']}%";
                                if ($vital['heart_rate']) $vital_str[] = "Heart Rate: {$vital['heart_rate']} bpm";
                                echo "<strong>$recorded_at</strong><br>" . implode(' • ', $vital_str);
                                ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No vital signs recorded yet. <a href="vital_signs.php" class="alert-link">Record your vitals</a> before requesting a consultation.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Available Doctors -->
                <h4 class="section-title">
                    <i class="fas fa-user-md"></i>
                    Select a Doctor
                </h4>
                <?php if (empty($doctors)): ?>
                    <div class="alert alert-info">No doctors available at this time.</div>
                <?php else: ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="doctor-card">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="doctor-info">
                                        <div class="doctor-avatar">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                        <div class="doctor-details">
                                            <h5>Dr. <?= htmlspecialchars($doctor['name']) ?></h5>
                                            <div class="doctor-meta">
                                                <span class="doctor-meta-item">
                                                    <i class="fas fa-graduation-cap me-1"></i>
                                                    <?= htmlspecialchars($doctor['qualification']) ?>
                                                </span>
                                                <span class="doctor-meta-item">
                                                    <i class="fas fa-history me-1"></i>
                                                    <?= $doctor['experience'] ?> years
                                                </span>
                                                <span class="doctor-meta-item">
                                                    <i class="fas fa-dollar-sign me-1"></i>
                                                    <?= number_format($doctor['consultation_fee'], 2) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-center justify-content-end">
                                    <form method="POST">
                                        <input type="hidden" name="doctor_id" value="<?= $doctor['id'] ?>">
                                        <button type="submit" name="request_consultation" class="btn btn-request" <?= empty($vital_signs) ? 'disabled' : '' ?>>
                                            <i class="fas fa-stethoscope me-2"></i>
                                            Request Consultation
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            
            // Check for saved state
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            // Apply initial state
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Save state
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });
            
            // For mobile
            if (window.innerWidth <= 768) {
                const menuItems = document.querySelectorAll('.sidebar-menu a');
                menuItems.forEach(item => {
                    item.addEventListener('click', function() {
                        if (window.innerWidth <= 768 && sidebar.classList.contains('expanded')) {
                            sidebar.classList.remove('expanded');
                        }
                    });
                });
            }
            
            // Tab functionality
            const tabs = document.querySelectorAll('.consultation-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    showTab(tabId);
                });
            });
        });
        
        function showTab(tabId) {
            // Hide all content
            document.querySelectorAll('.consultation-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.consultation-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected content
            document.getElementById(tabId + '-content').classList.add('active');
            
            // Add active class to selected tab
            document.querySelector(`.consultation-tab[data-tab="${tabId}"]`).classList.add('active');
        }
    </script>
</body>
</html>
