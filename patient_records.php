<?php
session_start();
include 'db_connect.php';

// Authenticate doctor
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

// Check if a specific patient is selected
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$patient_details = null;
$vital_signs = [];

// If patient is selected, fetch their details and vital signs
if ($patient_id > 0) {
    // Fetch patient details
    $sql_patient = "SELECT id, full_name, email 
                    FROM signup 
                    WHERE id = ?";
    $stmt_patient = $conn->prepare($sql_patient);
    $stmt_patient->bind_param("i", $patient_id);
    $stmt_patient->execute();
    $patient_details = $stmt_patient->get_result()->fetch_assoc();
    $stmt_patient->close();
    
    // Fetch patient's vital signs
    $sql_vitals = "SELECT * FROM vital_signs 
                   WHERE user_id = ? 
                   ORDER BY recorded_at DESC";
    $stmt_vitals = $conn->prepare($sql_vitals);
    $stmt_vitals->bind_param("i", $patient_id);
    $stmt_vitals->execute();
    $vital_signs = $stmt_vitals->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_vitals->close();
    
    // Fetch consultation history
    $sql_consultations = "SELECT c.*, d.name as doctor_name 
                          FROM consultations c
                          JOIN doctors d ON c.doctor_id = d.id
                          WHERE c.user_id = ? 
                          ORDER BY c.consultation_time DESC";
    $stmt_consultations = $conn->prepare($sql_consultations);
    $stmt_consultations->bind_param("i", $patient_id);
    $stmt_consultations->execute();
    $consultations = $stmt_consultations->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_consultations->close();
} else {
    // Fetch all patients who have had consultations with this doctor
    $sql_patients = "SELECT DISTINCT s.id, s.full_name, s.email, 
                    (SELECT MAX(c2.consultation_time) FROM consultations c2 WHERE c2.user_id = s.id AND c2.doctor_id = ?) as last_visit
                    FROM signup s
                    JOIN consultations c ON s.id = c.user_id
                    WHERE c.doctor_id = ?
                    ORDER BY last_visit DESC";
    $stmt_patients = $conn->prepare($sql_patients);
    $stmt_patients->bind_param("ii", $doctor_id, $doctor_id);
    $stmt_patients->execute();
    $patients = $stmt_patients->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_patients->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Records - Doctor Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3eed2b;
            --primary-dark: #32c825;
            --secondary-color: #3498db;
            --light-bg: #f4fcf4;
            --sidebar-bg: rgb(17, 172, 37); /* Updated sidebar background color */
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
            color: white; /* Changed text color to white for better contrast */
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
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
            margin-top: 20px;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.2);
            border-left: 4px solid white;
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
        
        .page-header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title h2 {
            margin: 0;
            color: var(--dark-text);
            font-weight: 600;
        }
        
        .page-title p {
            margin: 5px 0 0;
            color: var(--light-text);
        }
        
        .search-container {
            position: relative;
            max-width: 300px;
        }
        
        .search-container input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border-radius: 50px;
            border: 1px solid #ddd;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-container input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(62, 237, 43, 0.2);
        }
        
        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
        }
        
        .content-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
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
        
        .patient-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .patient-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            background-color: #f9fdf9;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .patient-item:hover {
            background-color: rgba(62, 237, 43, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .patient-info {
            display: flex;
            align-items: center;
        }
        
        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--sidebar-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 1.2rem;
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
        
        .patient-actions a {
            padding: 8px 15px;
            border-radius: 50px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .patient-actions a i {
            margin-right: 5px;
        }
        
        .patient-actions a:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .patient-profile {
            display: flex;
            margin-bottom: 20px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--sidebar-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: white;
            font-size: 2.5rem;
        }
        
        .profile-details {
            flex: 1;
        }
        
        .profile-details h3 {
            margin: 0 0 10px 0;
            font-weight: 600;
        }
        
        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .profile-meta-item i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .profile-actions {
            margin-top: 15px;
        }
        
        .profile-actions a {
            padding: 8px 15px;
            border-radius: 50px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            margin-right: 10px;
        }
        
        .profile-actions a i {
            margin-right: 5px;
        }
        
        .profile-actions a:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .vital-card {
            background-color: #f9fdf9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .vital-card:hover {
            background-color: rgba(62, 237, 43, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .vital-date {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-text);
        }
        
        .vital-readings {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .vital-reading {
            background-color: white;
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .vital-reading i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .consultation-card {
            background-color: #f9fdf9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .consultation-card:hover {
            background-color: rgba(62, 237, 43, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .consultation-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .consultation-date {
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .consultation-status {
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-completed {
            background-color: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }
        
        .status-scheduled {
            background-color: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }
        
        .status-pending {
            background-color: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }
        
        .consultation-details {
            margin-top: 10px;
        }
        
        .consultation-details p {
            margin: 5px 0;
            font-size: 0.9rem;
        }
        
        .consultation-details strong {
            color: var(--dark-text);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--dark-text);
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .back-link:hover {
            color: var(--primary-color);
        }
        
        .tab-navigation {
            display: flex;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .tab-item {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            color: var(--light-text);
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab-item.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-item:hover {
            color: var(--primary-dark);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--light-text);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        
        .chart-container {
            margin-bottom: 20px;
        }
        
        .chart-controls {
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
            }
            
            .sidebar.expanded {
                transform: translateX(0);
                width: var(--sidebar-width);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .main-content.expanded {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background-color: var(--primary-color);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                box-shadow: 0 4px 10px rgba(0,0,0,0.2);
                z-index: 1001;
                cursor: pointer;
            }
            
            .patient-profile {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .profile-meta {
                justify-content: center;
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
                <a href="docdash.php">
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
                <a href="patient_records.php" class="active">
                    <i class="fas fa-user-injured"></i>
                    <span class="menu-text">Patient Records</span>
                </a>
            
            
        
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Mobile menu toggle for responsive design -->
    <div class="mobile-menu-toggle" id="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h2>Patient Records</h2>
                <p>View and manage your patients' health records</p>
            </div>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="patientSearch" placeholder="Search patients..." onkeyup="searchPatients()">
            </div>
        </div>
        
        <?php if ($patient_id > 0 && $patient_details): ?>
            <!-- Patient Detail View -->
            <a href="patient_records.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to All Patients
            </a>
            
            <div class="content-card">
                <div class="patient-profile">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-details">
                        <h3><?= htmlspecialchars($patient_details['full_name']) ?></h3>
                        <div class="profile-meta">
                            <span class="profile-meta-item">
                                <i class="fas fa-envelope"></i>
                                <?= htmlspecialchars($patient_details['email']) ?>
                            </span>
                            
                            <?php if (!empty($patient_details['gender'])): ?>
                                <span class="profile-meta-item">
                                    <i class="fas fa-venus-mars"></i>
                                    <?= htmlspecialchars($patient_details['gender']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($patient_details['date_of_birth'])): ?>
                                <span class="profile-meta-item">
                                    <i class="fas fa-birthday-cake"></i>
                                    <?= htmlspecialchars($patient_details['date_of_birth']) ?>
                                    (<?= date_diff(date_create($patient_details['date_of_birth']), date_create('today'))->y ?> years)
                                </span>
                            <?php endif; ?>
                        </div>
                       
                        <div class="profile-actions">
                            <a href="#" onclick="showTab('vitals')">
                                <i class="fas fa-heartbeat"></i> Vital Signs
                            </a>
                            <a href="#" onclick="showTab('consultations')">
                                <i class="fas fa-stethoscope"></i> Consultation History
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="tab-navigation">
                    <div class="tab-item active" onclick="showTab('vitals')">Vital Signs</div>
                    <div class="tab-item" onclick="showTab('consultations')">Consultation History</div>
                </div>
                
                <!-- Vital Signs Tab -->
                <div id="vitals-tab" class="tab-content active">
                    <div class="section-header">
                        <h4>
                            <i class="fas fa-heartbeat"></i>
                            Vital Signs History
                        </h4>
                    </div>
                    
                    <?php if (empty($vital_signs)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <p>No vital signs recorded for this patient yet.</p>
                        </div>
                    <?php else: ?>
                        <!-- Add chart container -->
                        <div class="chart-container mb-4">
                            <canvas id="vitalsChart"></canvas>
                        </div>
                        
                        <!-- Chart type selector -->
                        <div class="chart-controls mb-4">
                            <label for="chartType" class="me-2">Chart Type:</label>
                            <select id="chartType" class="form-select" style="width: auto; display: inline-block;" onchange="updateChartType()">
                                <option value="line">Line Chart</option>
                                <option value="bar">Bar Chart</option>
                            </select>
                            
                            <label for="vitalType" class="ms-4 me-2">Vital Sign:</label>
                            <select id="vitalType" class="form-select" style="width: auto; display: inline-block;" onchange="updateVitalType()">
                                <option value="blood_pressure">Blood Pressure</option>
                                <option value="blood_sugar">Blood Sugar</option>
                                <option value="heart_rate">Heart Rate</option>
                                <option value="oxygen_level">Oxygen Level</option>
                            </select>
                        </div>
                        
                        <!-- Existing vital signs list -->
                        <?php foreach ($vital_signs as $vital): ?>
                            <div class="vital-card">
                                <div class="vital-date">
                                    <i class="far fa-calendar-alt me-2"></i>
                                    <?= date('F j, Y h:i A', strtotime($vital['recorded_at'])) ?>
                                </div>
                                <div class="vital-readings">
                                    <?php if ($vital['blood_pressure_systolic'] && $vital['blood_pressure_diastolic']): ?>
                                        <div class="vital-reading">
                                            <i class="fas fa-heart"></i>
                                            BP: <?= $vital['blood_pressure_systolic'] ?>/<?= $vital['blood_pressure_diastolic'] ?> mmHg
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($vital['blood_sugar']): ?>
                                        <div class="vital-reading">
                                            <i class="fas fa-tint"></i>
                                            Blood Sugar: <?= $vital['blood_sugar'] ?> mg/dL
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($vital['heart_rate']): ?>
                                        <div class="vital-reading">
                                            <i class="fas fa-heartbeat"></i>
                                            Heart Rate: <?= $vital['heart_rate'] ?> bpm
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($vital['oxygen_level']): ?>
                                        <div class="vital-reading">
                                            <i class="fas fa-lungs"></i>
                                            Oxygen: <?= $vital['oxygen_level'] ?>%
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Consultation History Tab -->
                <div id="consultations-tab" class="tab-content">
                    <div class="section-header">
                        <h4>
                            <i class="fas fa-stethoscope"></i>
                            Consultation History
                        </h4>
                    </div>
                    
                    <?php if (empty($consultations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No consultation history for this patient yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($consultations as $consultation): ?>
                            <div class="consultation-card">
                                <div class="consultation-header">
                                    <div class="consultation-date">
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?= date('F j, Y h:i A', strtotime($consultation['consultation_time'])) ?>
                                    </div>
                                    <div class                                    <div class="consultation-status">
                                        <span class="status-badge <?= $consultation['status'] ?>">
                                            <?= ucfirst($consultation['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="consultation-details">
                                    <div class="consultation-info">
                                        <span>
                                            <i class="fas fa-user-md me-2"></i>
                                            Doctor: <?= htmlspecialchars($consultation['doctor_name']) ?>
                                        </span>
                                        <?php if (!empty($consultation['meet_link'])): ?>
                                            <span>
                                                <i class="fas fa-video me-2"></i>
                                                <a href="<?= htmlspecialchars($consultation['meet_link']) ?>" target="_blank">
                                                    Meeting Link
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($consultation['notes'])): ?>
                                        <div class="consultation-notes">
                                            <strong>Notes:</strong>
                                            <p><?= nl2br(htmlspecialchars($consultation['notes'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Patients List View -->
            <div class="content-card">
                <div class="section-header">
                    <h4>
                        <i class="fas fa-users"></i>
                        Your Patients
                    </h4>
                </div>
                
                <?php if (empty($patients)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-injured"></i>
                        <p>You haven't seen any patients yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="patientsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Date</th>
                                    <th>Last Visit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($patient['full_name']) ?></td>
                                        <td><?= htmlspecialchars($patient['email']) ?></td>
                                       
                                        <td>
                                            <?= !empty($patient['last_visit']) ? date('M d, Y', strtotime($patient['last_visit'])) : 'Never' ?>
                                        </td>
                                        <td>
                                            <a href="patient_records.php?patient_id=<?= $patient['id'] ?>" class="btn btn-sm btn-view">
                                                <i class="fas fa-eye me-1"></i> View Records
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            
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
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-expanded');
                });
            }
            
            // Tab functionality
            window.showTab = function(tabId) {
                // Hide all content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Remove active class from all tabs
                document.querySelectorAll('.tab-item').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Show selected content
                document.getElementById(tabId + '-tab').classList.add('active');
                
                // Add active class to selected tab
                document.querySelector(`.tab-item:nth-child(${tabId === 'vitals' ? 1 : 2})`).classList.add('active');
            };
        });
        
        // Patient search functionality
        function searchPatients() {
            const input = document.getElementById('patientSearch');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('patientsTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName('td')[0];
                const tdEmail = tr[i].getElementsByTagName('td')[1];
                const tdPhone = tr[i].getElementsByTagName('td')[2];
                
                if (tdName || tdEmail || tdPhone) {
                    const nameValue = tdName.textContent || tdName.innerText;
                    const emailValue = tdEmail.textContent || tdEmail.innerText;
                    const phoneValue = tdPhone.textContent || tdPhone.innerText;
                    
                    if (nameValue.toUpperCase().indexOf(filter) > -1 || 
                        emailValue.toUpperCase().indexOf(filter) > -1 || 
                        phoneValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
        
        // Chart initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Only initialize chart if vital signs exist
            <?php if (!empty($vital_signs)): ?>
                initializeChart();
            <?php endif; ?>
        });
        
        let vitalsChart;
        
        function initializeChart() {
            const ctx = document.getElementById('vitalsChart').getContext('2d');
            
            // Prepare data from PHP
            const vitalData = <?php echo json_encode($vital_signs); ?>;
            
            // Format dates and extract vital signs
            const dates = vitalData.map(item => {
                const date = new Date(item.recorded_at);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }).reverse();
            
            const bloodPressureSystolic = vitalData.map(item => item.blood_pressure_systolic).reverse();
            const bloodPressureDiastolic = vitalData.map(item => item.blood_pressure_diastolic).reverse();
            const bloodSugar = vitalData.map(item => item.blood_sugar).reverse();
            const heartRate = vitalData.map(item => item.heart_rate).reverse();
            const oxygenLevel = vitalData.map(item => item.oxygen_level).reverse();
            
            // Create initial chart (Blood Pressure)
            vitalsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Systolic BP (mmHg)',
                            data: bloodPressureSystolic,
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.1
                        },
                        {
                            label: 'Diastolic BP (mmHg)',
                            data: bloodPressureDiastolic,
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Blood Pressure Over Time',
                            font: {
                                size: 16
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'mmHg'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date & Time'
                            }
                        }
                    }
                }
            });
        }
        
        function updateChartType() {
            const chartType = document.getElementById('chartType').value;
            const vitalType = document.getElementById('vitalType').value;
            
            if (vitalsChart) {
                vitalsChart.config.type = chartType;
                vitalsChart.update();
            }
        }
        
        function updateVitalType() {
            const vitalType = document.getElementById('vitalType').value;
            const chartType = document.getElementById('chartType').value;
            
            if (!vitalsChart) return;
            
            // Prepare data from PHP
            const vitalData = <?php echo json_encode($vital_signs); ?>;
            
            // Format dates
            const dates = vitalData.map(item => {
                const date = new Date(item.recorded_at);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }).reverse();
            
            // Update chart based on selected vital type
            switch(vitalType) {
                case 'blood_pressure':
                    vitalsChart.data.datasets = [
                        {
                            label: 'Systolic BP (mmHg)',
                            data: vitalData.map(item => item.blood_pressure_systolic).reverse(),
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.1
                        },
                        {
                            label: 'Diastolic BP (mmHg)',
                            data: vitalData.map(item => item.blood_pressure_diastolic).reverse(),
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.1
                        }
                    ];
                    vitalsChart.options.plugins.title.text = 'Blood Pressure Over Time';
                    vitalsChart.options.scales.y.title.text = 'mmHg';
                    break;
                    
                case 'blood_sugar':
                    vitalsChart.data.datasets = [
                        {
                            label: 'Blood Sugar (mg/dL)',
                            data: vitalData.map(item => item.blood_sugar).reverse(),
                            borderColor: 'rgb(255, 159, 64)',
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            tension: 0.1
                        }
                    ];
                    vitalsChart.options.plugins.title.text = 'Blood Sugar Over Time';
                    vitalsChart.options.scales.y.title.text = 'mg/dL';
                    break;
                    
                case 'heart_rate':
                    vitalsChart.data.datasets = [
                        {
                            label: 'Heart Rate (bpm)',
                            data: vitalData.map(item => item.heart_rate).reverse(),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        }
                    ];
                    vitalsChart.options.plugins.title.text = 'Heart Rate Over Time';
                    vitalsChart.options.scales.y.title.text = 'bpm';
                    break;
                    
                case 'oxygen_level':
                    vitalsChart.data.datasets = [
                        {
                            label: 'Oxygen Level (%)',
                            data: vitalData.map(item => item.oxygen_level).reverse(),
                            borderColor: 'rgb(153, 102, 255)',
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            tension: 0.1
                        }
                    ];
                    vitalsChart.options.plugins.title.text = 'Oxygen Level Over Time';
                    vitalsChart.options.scales.y.title.text = '%';
                    break;
            }
            
            vitalsChart.update();
        }
    </script>
</body>
</html>