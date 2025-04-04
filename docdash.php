<?php
session_start();
include 'db_connect.php';

// Assuming doctors have their own login system
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$success_message = "";
$error_message = "";

// Fetch doctor's details
$sql_doctor = "SELECT name FROM doctors WHERE id = ?";
$stmt_doctor = $conn->prepare($sql_doctor);
$stmt_doctor->bind_param("i", $doctor_id);
$stmt_doctor->execute();
$doctor = $stmt_doctor->get_result()->fetch_assoc();
$stmt_doctor->close();

// Fetch pending consultation requests
$sql_consultations = "SELECT c.id, c.user_id, c.health_data, c.created_at, u.full_name
                        FROM consultations c
                        JOIN signup u ON c.user_id = u.id
                        WHERE c.doctor_id = ? AND c.status = 'pending'
                        ORDER BY c.created_at DESC";
$stmt_consultations = $conn->prepare($sql_consultations);
$stmt_consultations->bind_param("i", $doctor_id);
$stmt_consultations->execute();
$consultations = $stmt_consultations->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_consultations->close();

// Handle consultation acceptance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_consultation'])) {
    $consultation_id = $_POST['consultation_id'];
    $meet_link = $_POST['meet_link'];
    $consultation_time = $_POST['consultation_time'];

    // Validate consultation time is in future
    $consultation_timestamp = strtotime($consultation_time);
    if ($consultation_timestamp <= time()) {
        $error_message = "Please select a future date and time for the consultation.";
    } else {
        $sql_accept = "UPDATE consultations
                      SET status = 'assigned',
                          meet_link = ?,
                          consultation_time = ?
                      WHERE id = ? AND doctor_id = ?";
        $stmt_accept = $conn->prepare($sql_accept);
        $stmt_accept->bind_param("ssii", $meet_link, $consultation_time, $consultation_id, $doctor_id);

        if ($stmt_accept->execute()) {
            $success_message = "Consultation scheduled successfully!";
            // Refresh the consultations list
            $sql_consultations_refresh = "SELECT c.id, c.user_id, c.health_data, c.created_at, u.full_name
                                        FROM consultations c
                                        JOIN signup u ON c.user_id = u.id
                                        WHERE c.doctor_id = ? AND c.status = 'pending'
                                        ORDER BY c.created_at DESC";
            $stmt_consultations_refresh = $conn->prepare($sql_consultations_refresh);
            $stmt_consultations_refresh->bind_param("i", $doctor_id);
            $stmt_consultations_refresh->execute();
            $consultations = $stmt_consultations_refresh->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_consultations_refresh->close();
        } else {
            $error_message = "Error scheduling consultation: " . $conn->error;
        }
        $stmt_accept->close();
    }
}

// Handle consultation removal (rejection)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_consultation'])) {
    $consultation_id_to_remove = $_POST['consultation_id'];

    $sql_remove = "UPDATE consultations SET status = 'rejected' WHERE id = ? AND doctor_id = ?";
    $stmt_remove = $conn->prepare($sql_remove);
    $stmt_remove->bind_param("ii", $consultation_id_to_remove, $doctor_id);

    if ($stmt_remove->execute()) {
        $success_message = "Consultation request removed successfully!";
        $stmt_remove->close();

        // Refresh the consultations list using a new statement
        $sql_consultations_refresh = "SELECT c.id, c.user_id, c.health_data, c.created_at, u.full_name
                                    FROM consultations c
                                    JOIN signup u ON c.user_id = u.id
                                    WHERE c.doctor_id = ? AND c.status = 'pending'
                                    ORDER BY c.created_at DESC";
        $stmt_consultations_refresh = $conn->prepare($sql_consultations_refresh);
        $stmt_consultations_refresh->bind_param("i", $doctor_id);
        $stmt_consultations_refresh->execute();
        $consultations = $stmt_consultations_refresh->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_consultations_refresh->close();
    } else {
        $error_message = "Error removing consultation request: " . $conn->error;
        $stmt_remove->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3eed2b;
            --primary-dark: #32c825;
            --secondary-color: #3498db;
            --light-bg: #f4fcf4;
            --sidebar-bg: #e8f5e9;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --card-shadow: 0 4px 8px rgba(0,0,0,0.1);
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            line-height: 1.6;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: var(--dark-text);
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding-top: 20px;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-menu {
            padding: 0;
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
            color: var(--dark-text);
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(62, 237, 43, 0.2);
            color: var(--dark-text);
            border-left: 4px solid var(--primary-color);
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
            color: var(--dark-text);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - var(--sidebar-width));
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }
        
        .dashboard-header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-text h2 {
            margin: 0;
            color: var(--dark-text);
            font-weight: 600;
        }
        
        .welcome-text p {
            margin: 5px 0 0;
            color: var(--light-text);
        }
        
        .dashboard-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            flex: 1;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
        }
        
        .stat-icon.pending {
            background-color: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }
        
        .stat-icon.scheduled {
            background-color: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }
        
        .stat-icon.completed {
            background-color: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .stat-info h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .stat-info p {
            margin: 5px 0 0;
            color: var(--light-text);
        }
        
        .consultation-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .section-header h4 {
            margin: 0;
            font-weight: 600;
            color: var(--dark-text);
            display: flex;
            align-items: center;
        }
        
        .section-header h4 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .consultation-card {
            border: none;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9fdf9;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .consultation-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow);
        }
        
        .patient-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: rgba(62, 237, 43, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-dark);
            font-size: 1.5rem;
        }
        
        .patient-details h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .patient-details p {
            margin: 5px 0 0;
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .health-data {
            background-color: rgba(62, 237, 43, 0.05);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid var(--primary-color);
        }
        
        .health-data ul {
            margin: 10px 0 0;
            padding-left: 20px;
        }
        
        .health-data li {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .consultation-form {
            background-color: #f9fdf9;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-text);
        }
        
        .btn-accept {
            background-color: var(--primary-color);
            border: none;
            color: #000;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-accept:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(62, 237, 43, 0.3);
        }
        
        .btn-remove {
            background-color: #e74c3c;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-remove:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #d5d5d5;
            margin-bottom: 20px;
        }
        
        .empty-state p {
            font-size: 1.2rem;
            color: var(--light-text);
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: var(--sidebar-collapsed-width);
                transform: translateX(-100%);
            }
            
            .sidebar.expanded {
                transform: translateX(0);
                width: var(--sidebar-width);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .dashboard-stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <button id="sidebar-toggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="sidebar-header">
            <h3>Doctor Portal</h3>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="#" class="active">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="menu-text">Appointments</span>
                </a>
            </li>
            <li>
                <a href="patient_records.php">
                    <i class="fas fa-user-injured"></i>
                    <span class="menu-text">Patient Records</span>
                </a>
            </li>
            <li>
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
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="welcome-text">
                <h2>Welcome, Dr. <?= htmlspecialchars($doctor['name']) ?></h2>
                <p><?= date('l, F j, Y') ?></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline-secondary">
                    <i class="fas fa-bell me-2"></i>
                    Notifications
                </button>
            </div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= count($consultations) ?></h3>
                    <p>Pending Requests</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon scheduled">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3>0</h3>
                    <p>Today's Appointments</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>0</h3>
                    <p>Completed Consultations</p>
                </div>
            </div>
        </div>
        
        <!-- Consultation Requests Section -->
        <div class="consultation-section">
            <div class="section-header">
                <h4>
                    <i class="fas fa-stethoscope"></i>
                    Pending Consultation Requests
                </h4>
            </div>
            
            <?php if (empty($consultations)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <p>No pending consultation requests at this time.</p>
                </div>
            <?php else: ?>
                <?php foreach ($consultations as $consultation): ?>
                    <div class="consultation-card">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="patient-info">
                                    <div class="patient-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="patient-details">
                                        <h5><?= htmlspecialchars($consultation['full_name']) ?></h5>
                                        <p>
                                            <i class="far fa-clock me-1"></i>
                                            Requested: <?= date('M d, Y h:i A', strtotime($consultation['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="health-data">
                                    <strong>
                                        <i class="fas fa-heartbeat me-2"></i>
                                        Health Data:
                                    </strong>
                                    <ul>
                                        <?php
                                        $health_data = json_decode($consultation['health_data'], true);
                                        foreach ($health_data as $vital):
                                            $recorded_at = date('M d, Y h:i A', strtotime($vital['recorded_at']));
                                            $vital_str = [];
                                            if ($vital['blood_sugar']) $vital_str[] = "Blood Sugar: {$vital['blood_sugar']} mg/dL";
                                            if ($vital['blood_pressure_systolic']) $vital_str[] = "Systolic BP: {$vital['blood_pressure_systolic']} mmHg";
                                            if ($vital['blood_pressure_diastolic']) $vital_str[] = "Diastolic BP: {$vital['blood_pressure_diastolic']} mmHg";
                                            if ($vital['oxygen_level']) $vital_str[] = "Oxygen: {$vital['oxygen_level']}%";
                                            if ($vital['heart_rate']) $vital_str[] = "Heart Rate: {$vital['heart_rate']} bpm";
                                        ?>
                                            <li><?= "$recorded_at - " . implode(', ', $vital_str) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="consultation-form">
                                    <form method="POST">
                                        <input type="hidden" name="consultation_id" value="<?= $consultation['id'] ?>">
                                        <div class="mb-3">
                                            <label for="consultation_time_<?= $consultation['id'] ?>" class="form-label">
                                                <i class="far fa-calendar-alt me-1"></i>
                                                Consultation Date & Time
                                            </label>
                                            <input type="datetime-local"
                                                name="consultation_time"
                                                id="consultation_time_<?= $consultation['id'] ?>"
                                                class="form-control"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="meet_link_<?= $consultation['id'] ?>" class="form-label">
                                                <i class="fas fa-video me-1"></i>
                                                Meeting Link
                                            </label>
                                            <input type="url"
                                                name="meet_link"
                                                id="meet_link_<?= $consultation['id'] ?>"
                                                class="form-control"
                                                placeholder="Enter meeting link (e.g., Zoom)"
                                                required>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button type="submit"
                                                    name="accept_consultation"
                                                    class="btn btn-accept">
                                                <i class="fas fa-check me-2"></i>
                                                Schedule Consultation
                                            </button>
                                            <button type="submit"
                                                    name="remove_consultation"
                                                    class="btn btn-remove"
                                                    formnovalidate>
                                                <i class="fas fa-times me-2"></i>
                                                Decline Request
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
        });
    </script>
</body>
</html>