<?php
// Place this at the very top of your file, before any HTML or whitespace
if (isset($_POST['download_vital_signs'])) {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Include database connection
    include 'db_connect.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Fetch user's name
    $user_sql = "SELECT full_name FROM signup WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_name = ($user_result->num_rows > 0) ? $user_result->fetch_assoc()['full_name'] : 'Unknown';
    $user_stmt->close();
    
    // Define normal ranges for vital signs
    $normal_ranges = [
        'blood_sugar' => [70, 140], // mg/dL
        'blood_pressure_systolic' => [90, 120], // mmHg
        'blood_pressure_diastolic' => [60, 80], // mmHg
        'oxygen_level' => [95, 100], // %
        'heart_rate' => [60, 100] // bpm
    ];
    
    // Fetch user's vital signs data
    $sql = "SELECT *, DATE_FORMAT(recorded_at, '%M %d, %Y %h:%i %p') AS formatted_time 
            FROM vital_signs WHERE user_id = ? ORDER BY recorded_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $readings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Check if TCPDF exists before trying to use it
    $tcpdf_path = 'TCPDF-main/tcpdf.php';
    if (!file_exists($tcpdf_path)) {
        // Store error message to display later
        $pdf_error = 'TCPDF library not found. Please check the path: ' . $tcpdf_path;
    } else {
        // Make sure no output has been sent yet
        if (headers_sent()) {
            $pdf_error = 'Cannot generate PDF - headers already sent. Please try again.';
        } else {
            // Buffer all output
            ob_start();
            
            require_once($tcpdf_path);
            
            // Create new TCPDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('TeleCare+');
            $pdf->SetAuthor('TeleCare+ System');
            $pdf->SetTitle('Vital Signs Report');
            $pdf->SetSubject('Patient Vital Signs');
            
            // Set default header data
            $pdf->SetHeaderData('', 0, 'Telecare+', 'Generated on: ' . date('Y-m-d H:i:s'));
            
            // Set header and footer fonts
            $pdf->setHeaderFont(Array('helvetica', '', 12));
            $pdf->setFooterFont(Array('helvetica', '', 8));
            
            // Set default monospaced font
            $pdf->SetDefaultMonospacedFont('courier');
            
            // Set margins
            $pdf->SetMargins(15, 20, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            
            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', 'B', 14);
            
            // Title
            $pdf->Cell(0, 10, 'Patient Vital Signs History', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Patient Info
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Patient Information', 0, 1);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(40, 7, 'Patient Name:', 0);
            $pdf->Cell(0, 7, $user_name, 0, 1);
            $pdf->Cell(40, 7, 'Patient ID:', 0);
            $pdf->Cell(0, 7, $_SESSION['user_id'] ?? 'Unknown', 0, 1);
            $pdf->Ln(5);
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(45, 7, 'Date & Time', 1, 0, 'C', 1);
            $pdf->Cell(30, 7, 'Blood Sugar', 1, 0, 'C', 1);
            $pdf->Cell(30, 7, 'BP (Systolic)', 1, 0, 'C', 1);
            $pdf->Cell(30, 7, 'BP (Diastolic)', 1, 0, 'C', 1);
            $pdf->Cell(25, 7, 'Oxygen', 1, 0, 'C', 1);
            $pdf->Cell(30, 7, 'Heart Rate', 1, 1, 'C', 1);
            
            // Table data
            $pdf->SetFont('helvetica', '', 10);
            foreach ($readings as $reading) {
                $pdf->Cell(45, 7, $reading['formatted_time'], 1, 0, 'C');
                
                // Blood Sugar
                $is_abnormal = ($reading['blood_sugar'] !== null && 
                               ($reading['blood_sugar'] < $normal_ranges['blood_sugar'][0] || 
                                $reading['blood_sugar'] > $normal_ranges['blood_sugar'][1]));
                if ($is_abnormal) {
                    $pdf->SetTextColor(255, 0, 0);
                }
                $pdf->Cell(30, 7, $reading['blood_sugar'] !== null ? $reading['blood_sugar'] . ' mg/dL' : '-', 1, 0, 'C');
                $pdf->SetTextColor(0, 0, 0);
                
                // BP Systolic
                $is_abnormal = ($reading['blood_pressure_systolic'] !== null && 
                               ($reading['blood_pressure_systolic'] < $normal_ranges['blood_pressure_systolic'][0] || 
                                $reading['blood_pressure_systolic'] > $normal_ranges['blood_pressure_systolic'][1]));
                if ($is_abnormal) {
                    $pdf->SetTextColor(255, 0, 0);
                }
                $pdf->Cell(30, 7, $reading['blood_pressure_systolic'] !== null ? $reading['blood_pressure_systolic'] . ' mmHg' : '-', 1, 0, 'C');
                $pdf->SetTextColor(0, 0, 0);
                
                // BP Diastolic
                $is_abnormal = ($reading['blood_pressure_diastolic'] !== null && 
                               ($reading['blood_pressure_diastolic'] < $normal_ranges['blood_pressure_diastolic'][0] || 
                                $reading['blood_pressure_diastolic'] > $normal_ranges['blood_pressure_diastolic'][1]));
                if ($is_abnormal) {
                    $pdf->SetTextColor(255, 0, 0);
                }
                $pdf->Cell(30, 7, $reading['blood_pressure_diastolic'] !== null ? $reading['blood_pressure_diastolic'] . ' mmHg' : '-', 1, 0, 'C');
                $pdf->SetTextColor(0, 0, 0);
                
                // Oxygen Level
                $is_abnormal = ($reading['oxygen_level'] !== null && 
                               $reading['oxygen_level'] < $normal_ranges['oxygen_level'][0]);
                if ($is_abnormal) {
                    $pdf->SetTextColor(255, 0, 0);
                }
                $pdf->Cell(25, 7, $reading['oxygen_level'] !== null ? $reading['oxygen_level'] . '%' : '-', 1, 0, 'C');
                $pdf->SetTextColor(0, 0, 0);
                
                // Heart Rate
                $is_abnormal = ($reading['heart_rate'] !== null && 
                               ($reading['heart_rate'] < $normal_ranges['heart_rate'][0] || 
                                $reading['heart_rate'] > $normal_ranges['heart_rate'][1]));
                if ($is_abnormal) {
                    $pdf->SetTextColor(255, 0, 0);
                }
                $pdf->Cell(30, 7, $reading['heart_rate'] !== null ? $reading['heart_rate'] . ' bpm' : '-', 1, 1, 'C');
                $pdf->SetTextColor(0, 0, 0);
            }
            
            // Normal ranges reference
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Normal Ranges Reference', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 7, 'Blood Sugar: ' . $normal_ranges['blood_sugar'][0] . '-' . $normal_ranges['blood_sugar'][1] . ' mg/dL', 0, 1);
            $pdf->Cell(0, 7, 'Blood Pressure (Systolic): ' . $normal_ranges['blood_pressure_systolic'][0] . '-' . $normal_ranges['blood_pressure_systolic'][1] . ' mmHg', 0, 1);
            $pdf->Cell(0, 7, 'Blood Pressure (Diastolic): ' . $normal_ranges['blood_pressure_diastolic'][0] . '-' . $normal_ranges['blood_pressure_diastolic'][1] . ' mmHg', 0, 1);
            $pdf->Cell(0, 7, 'Oxygen Level: ≥' . $normal_ranges['oxygen_level'][0] . '%', 0, 1);
            $pdf->Cell(0, 7, 'Heart Rate: ' . $normal_ranges['heart_rate'][0] . '-' . $normal_ranges['heart_rate'][1] . ' bpm', 0, 1);
            
            // Clear any output buffered
            ob_end_clean();
            
            // Output PDF
            $pdf->Output('Vital_Signs_Report_' . date('Y-m-d') . '.pdf', 'D');
            exit;
        }
    }
    
    // Close the database connection
    $conn->close();
}
?>

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
$show_timer = false;
$timer_end_time = 0;
$show_doctor_alert = false;
$doctor_message = "";

// Define normal ranges for vital signs
$normal_ranges = [
    'blood_sugar' => [70, 140], // mg/dL
    'blood_pressure_systolic' => [90, 120], // mmHg
    'blood_pressure_diastolic' => [60, 80], // mmHg
    'oxygen_level' => [95, 100], // %
    'heart_rate' => [60, 100] // bpm
];

// Check if stop timer button was pressed
if (isset($_POST['stop_timer'])) {
    unset($_SESSION['abnormal_reading'], $_SESSION['timer_start'], $_SESSION['timer_end']);
    $show_timer = false;
    $success_message = "Timer has been stopped.";
}
// Check if a timer needs to start for abnormal readings
else if (isset($_SESSION['abnormal_reading']) && $_SESSION['abnormal_reading'] === true) {
    $show_timer = true;
    if (!isset($_SESSION['timer_start']) && isset($_POST['start_timer'])) {
        $_SESSION['timer_start'] = time();
        $_SESSION['timer_end'] = time() + 60; // 1 minute
    }
    if (isset($_SESSION['timer_start'])) {
        $timer_end_time = $_SESSION['timer_end'];
        if (time() >= $_SESSION['timer_end']) {
            $show_timer = false;
            unset($_SESSION['timer_start'], $_SESSION['timer_end']);
            // Keep abnormal_reading flag for comparison with next reading
        }
    }
}

// Handling vital sign submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_vital'])) {
    $vital_type = $_POST["vital_type"];
    $value = $_POST["value"];

    if (empty($vital_type) || empty($value)) {
        $error_message = "All fields are required.";
    } elseif ($value < 0) {
        $error_message = "Error: Negative values are not allowed for vital signs.";
    } else {
        $column_map = [
            "blood_sugar" => "blood_sugar",
            "blood_pressure_systolic" => "blood_pressure_systolic",
            "blood_pressure_diastolic" => "blood_pressure_diastolic",
            "oxygen_level" => "oxygen_level",
            "heart_rate" => "heart_rate"
        ];

        if (!isset($column_map[$vital_type])) {
            $error_message = "Invalid vital type!";
        } else {
            $column = $column_map[$vital_type];

            $sql = "INSERT INTO vital_signs (user_id, $column, recorded_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("id", $user_id, $value);

            if ($stmt->execute()) {
                $success_message = "Vital sign recorded successfully!";
                
                // Check if reading is abnormal
                $is_abnormal = false;
                
                if ($vital_type == 'blood_sugar' && ($value < $normal_ranges['blood_sugar'][0] || $value > $normal_ranges['blood_sugar'][1])) {
                    $is_abnormal = true;
                } else if ($vital_type == 'blood_pressure_systolic' && ($value < $normal_ranges['blood_pressure_systolic'][0] || $value > $normal_ranges['blood_pressure_systolic'][1])) {
                    $is_abnormal = true;
                } else if ($vital_type == 'blood_pressure_diastolic' && ($value < $normal_ranges['blood_pressure_diastolic'][0] || $value > $normal_ranges['blood_pressure_diastolic'][1])) {
                    $is_abnormal = true;
                } else if ($vital_type == 'oxygen_level' && $value < $normal_ranges['oxygen_level'][0]) {
                    $is_abnormal = true;
                } else if ($vital_type == 'heart_rate' && ($value < $normal_ranges['heart_rate'][0] || $value > $normal_ranges['heart_rate'][1])) {
                    $is_abnormal = true;
                }
                
                // Check if this is a follow-up reading after a previous abnormal reading
                if (isset($_SESSION['abnormal_reading'])) {
                    if ($is_abnormal) {
                        // Second abnormal reading - show doctor consultation alert
                        $show_doctor_alert = true;
                        $doctor_message = "You have multiple abnormal readings. Please consult a doctor as soon as possible.";
                    } else {
                        // Second reading is normal
                        $success_message .= " Your reading has returned to normal range.";
                    }
                    // Clear abnormal reading flags
                    unset($_SESSION['abnormal_reading']);
                } 
                else if ($is_abnormal) {
                    // This is the first abnormal reading
                    $_SESSION['abnormal_reading'] = true;
                    $show_timer = true;
                }
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch user's last 10 readings
$sql = "SELECT *, DATE_FORMAT(recorded_at, '%M %d, %Y %h:%i %p') AS formatted_time 
        FROM vital_signs WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$readings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Monitoring Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --normal-color: rgb(62, 237, 43);
            --abnormal-color: #dc3545;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: #333;
            border-bottom: 2px solid var(--normal-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .readings-scroll {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding: 10px 0;
            margin-bottom: 20px;
        }
        
        .reading-card {
            min-width: 200px;
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid var(--normal-color);
        }
        
        .abnormal-card {
            border-left: 4px solid var(--abnormal-color);
        }
        
        .vital-value {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .normal {
            color: var(--normal-color);
        }
        
        .abnormal {
            color: var(--abnormal-color);
            font-weight: bold;
        }
        
        .abnormal-icon {
            animation: pulse 1s infinite;
            margin-left: 5px;
        }
        
        .vital-date {
            font-size: 0.8em;
            color: #666;
            margin-top: 10px;
        }
        
        table th {
            background-color: #f1f1f1;
        }
        
        table td {
            vertical-align: middle;
        }
        
        .timer-container {
            background: linear-gradient(to right, rgba(62, 237, 43, 0.1), rgba(62, 237, 43, 0.2));
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border: 1px solid rgba(62, 237, 43, 0.3);
        }
        
        .timer-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        #countdown {
            font-size: 2.5em;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
            color: #333;
        }
        
        .timer-text {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .timer-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn-stop {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-stop:hover {
            background-color: #c82333;
            color: white;
        }
        
        .doctor-alert {
            background-color: #ffebee;
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
            animation: pulse 2s infinite;
        }
        
        .doctor-alert-title {
            display: flex;
            align-items: center;
            font-size: 1.4em;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .doctor-alert-title i {
            margin-right: 10px;
            font-size: 1.5em;
        }
        
        .doctor-alert-message {
            font-size: 1.1em;
            color: #333;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .btn-primary {
            background-color: var(--normal-color);
            border-color: var(--normal-color);
            color: #333;
            font-weight: bold;
        }
        
        .btn-primary:hover {
            background-color: rgb(42, 217, 23);
            border-color: rgb(42, 217, 23);
            color: #333;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, rgb(23, 205, 44) 0%, rgb(73, 174, 93) 100%);
            color: white;
            position: fixed;
            padding-top: 20px;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 15px 20px;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 16px;
        }

        .sidebar ul li a i {
            margin-right: 10px;
        }

        .sidebar ul li:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>TeleCare+</h2>
        <ul>
            <li>
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="vital_signs.php">
                    <i class="fas fa-heartbeat"></i>
                    Vital Signs
                </a>
            </li>
            <li>
                <a href="appointments.php">
                    <i class="fas fa-calendar-check"></i>
                    Appointments
                </a>
            </li>
            <li>
                <a href="health_records.php">
                    <i class="fas fa-file-medical"></i>
                    Health Records
                </a>
            </li>
            <li>
                <a href="medications.php">
                    <i class="fas fa-pills"></i>
                    Medications
                </a>
            </li>
            <li>
                <a href="book_consultation.php">
                    <i class="fas fa-user-md"></i>
                    Doctor Consultation
                </a>
            </li>
            <li>
                <a href="profile.php">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
            </li>
            <li>
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="mt-4 mb-4">Health Monitoring Dashboard</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?= $success_message ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?= $error_message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_doctor_alert): ?>
            <div class="doctor-alert">
                <div class="doctor-alert-title">
                    <i class="fas fa-user-md"></i> Medical Attention Required
                </div>
                <div class="doctor-alert-message">
                    <?= $doctor_message ?>
                </div>
                <div class="mt-3">
                    <a href="book_consultation.php" class="btn btn-danger">
                        <i class="fas fa-video me-2"></i>Request Doctor Consultation
                    </a>
                    <a href="tel:+1234567890" class="btn btn-outline-danger ms-2">
                        <i class="fas fa-phone-alt me-2"></i>Emergency Call
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($show_timer): ?>
            <div class="timer-container">
                <?php if (!isset($_SESSION['timer_start'])): ?>
                    <div class="timer-title text-center text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Abnormal Reading Detected
                    </div>
                    <p>Please start the timer and take another reading in 1 minute.</p>
                    <div class="timer-buttons">
                        <form method="POST">
                            <button type="submit" name="start_timer" class="btn btn-primary">
                                <i class="fas fa-clock me-2"></i>Start 1-Minute Timer
                            </button>
                        </form>
                        <form method="POST">
                            <button type="submit" name="stop_timer" class="btn btn-stop">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="timer-title">
                        <i class="fas fa-clock me-2"></i>
                        Follow-up Reading Timer
                    </div>
                    <div id="countdown">00:01:00</div>
                    <p class="timer-text">Please take another reading when the timer reaches zero.</p>
                    <div class="timer-buttons">
                        <form method="POST">
                            <button type="submit" name="stop_timer" class="btn btn-stop">
                                <i class="fas fa-stop-circle me-2"></i>Stop Timer
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($readings)): ?>
            <h4>Recent Readings</h4>
            <div class="readings-scroll">
                <?php foreach (array_slice($readings, 0, 5) as $reading): ?>
                    <?php 
                    $has_abnormal = false;
                    if (($reading['blood_sugar'] !== null && ($reading['blood_sugar'] < $normal_ranges['blood_sugar'][0] || $reading['blood_sugar'] > $normal_ranges['blood_sugar'][1])) ||
                        ($reading['blood_pressure_systolic'] !== null && ($reading['blood_pressure_systolic'] < $normal_ranges['blood_pressure_systolic'][0] || $reading['blood_pressure_systolic'] > $normal_ranges['blood_pressure_systolic'][1])) ||
                        ($reading['blood_pressure_diastolic'] !== null && ($reading['blood_pressure_diastolic'] < $normal_ranges['blood_pressure_diastolic'][0] || $reading['blood_pressure_diastolic'] > $normal_ranges['blood_pressure_diastolic'][1])) ||
                        ($reading['oxygen_level'] !== null && $reading['oxygen_level'] < $normal_ranges['oxygen_level'][0]) ||
                        ($reading['heart_rate'] !== null && ($reading['heart_rate'] < $normal_ranges['heart_rate'][0] || $reading['heart_rate'] > $normal_ranges['heart_rate'][1]))) {
                        $has_abnormal = true;
                    }
                    ?>
                    <div class="reading-card <?= $has_abnormal ? 'abnormal-card' : '' ?>">
                        <?php if ($reading['blood_sugar'] !== null): ?>
                            <?php $is_abnormal = ($reading['blood_sugar'] < $normal_ranges['blood_sugar'][0] || $reading['blood_sugar'] > $normal_ranges['blood_sugar'][1]); ?>
                            <div class="mb-2">
                                <div>Blood Sugar</div>
                                <div class="vital-value <?= $is_abnormal ? 'abnormal' : 'normal' ?>">
                                    <?= $reading['blood_sugar'] ?> mg/dL
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($reading['blood_pressure_systolic'] !== null): ?>
                            <?php $is_abnormal = ($reading['blood_pressure_systolic'] < $normal_ranges['blood_pressure_systolic'][0] || $reading['blood_pressure_systolic'] > $normal_ranges['blood_pressure_systolic'][1]); ?>
                            <div class="mb-2">
                                <div>BP (Systolic)</div>
                                <div class="vital-value <?= $is_abnormal ? 'abnormal' : 'normal' ?>">
                                    <?= $reading['blood_pressure_systolic'] ?> mmHg
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($reading['blood_pressure_diastolic'] !== null): ?>
                            <?php $is_abnormal = ($reading['blood_pressure_diastolic'] < $normal_ranges['blood_pressure_diastolic'][0] || $reading['blood_pressure_diastolic'] > $normal_ranges['blood_pressure_diastolic'][1]); ?>
                            <div class="mb-2">
                                <div>BP (Diastolic)</div>
                                <div class="vital-value <?= $is_abnormal ? 'abnormal' : 'normal' ?>">
                                    <?= $reading['blood_pressure_diastolic'] ?> mmHg
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($reading['oxygen_level'] !== null): ?>
                            <?php $is_abnormal = ($reading['oxygen_level'] < $normal_ranges['oxygen_level'][0]); ?>
                            <div class="mb-2">
                                <div>Oxygen Level</div>
                                <div class="vital-value <?= $is_abnormal ? 'abnormal' : 'normal' ?>">
                                    <?= $reading['oxygen_level'] ?>%
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($reading['heart_rate'] !== null): ?>
                            <?php $is_abnormal = ($reading['heart_rate'] < $normal_ranges['heart_rate'][0] || $reading['heart_rate'] > $normal_ranges['heart_rate'][1]); ?>
                            <div class="mb-2">
                                <div>Heart Rate</div>
                                <div class="vital-value <?= $is_abnormal ? 'abnormal' : 'normal' ?>">
                                    <?= $reading['heart_rate'] ?> bpm
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="vital-date">
                            <i class="far fa-calendar-alt me-1"></i> <?= $reading['formatted_time'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="mb-4 p-3 bg-light rounded">
            <h4 class="mb-3" style="border-bottom: none;">Record New Vital Sign</h4>
            <div class="mb-3">
                <label class="form-label">Select Vital Sign:</label>
                <select name="vital_type" class="form-select">
                    <option value="blood_sugar">Blood Sugar</option>
                    <option value="blood_pressure_systolic">Blood Pressure (Systolic)</option>
                    <option value="blood_pressure_diastolic">Blood Pressure (Diastolic)</option>
                    <option value="oxygen_level">Oxygen Level</option>
                    <option value="heart_rate">Heart Rate</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Value:</label>
                <input type="number" name="value" class="form-control" step="0.1" placeholder="Enter value" required>
            </div>
            <button type="submit" name="submit_vital" class="btn btn-primary">Submit</button>
        </form>

        <h4>Vital Signs History</h4>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Blood Sugar</th>
                        <th>BP (Systolic)</th>
                        <th>BP (Diastolic)</th>
                        <th>Oxygen</th>
                        <th>Heart Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($readings)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No vital sign records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($readings as $reading): ?>
                            <tr>
                                <td><strong><?= $reading['formatted_time'] ?></strong></td>
                                
                                <?php $is_abnormal = ($reading['blood_sugar'] !== null && 
                                                     ($reading['blood_sugar'] < $normal_ranges['blood_sugar'][0] || 
                                                      $reading['blood_sugar'] > $normal_ranges['blood_sugar'][1])); ?>
                                <td class="<?= $is_abnormal ? 'abnormal' : ($reading['blood_sugar'] !== null ? 'normal' : '') ?>">
                                    <?php if ($reading['blood_sugar'] !== null): ?>
                                        <span class="badge <?= $is_abnormal ? 'bg-danger' : 'bg-success' ?> p-2">
                                            <?= $reading['blood_sugar'] ?> mg/dL
                                            <?php if ($is_abnormal): ?>
                                                <i class="fas fa-exclamation-circle ms-1"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <?php $is_abnormal = ($reading['blood_pressure_systolic'] !== null && 
                                                     ($reading['blood_pressure_systolic'] < $normal_ranges['blood_pressure_systolic'][0] || 
                                                      $reading['blood_pressure_systolic'] > $normal_ranges['blood_pressure_systolic'][1])); ?>
                                <td class="<?= $is_abnormal ? 'abnormal' : ($reading['blood_pressure_systolic'] !== null ? 'normal' : '') ?>">
                                    <?php if ($reading['blood_pressure_systolic'] !== null): ?>
                                        <span class="badge <?= $is_abnormal ? 'bg-danger' : 'bg-success' ?> p-2">
                                            <?= $reading['blood_pressure_systolic'] ?> mmHg
                                            <?php if ($is_abnormal): ?>
                                                <i class="fas fa-exclamation-circle ms-1"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <?php $is_abnormal = ($reading['blood_pressure_diastolic'] !== null && 
                                                     ($reading['blood_pressure_diastolic'] < $normal_ranges['blood_pressure_diastolic'][0] || 
                                                      $reading['blood_pressure_diastolic'] > $normal_ranges['blood_pressure_diastolic'][1])); ?>
                                <td class="<?= $is_abnormal ? 'abnormal' : ($reading['blood_pressure_diastolic'] !== null ? 'normal' : '') ?>">
                                    <?php if ($reading['blood_pressure_diastolic'] !== null): ?>
                                        <span class="badge <?= $is_abnormal ? 'bg-danger' : 'bg-success' ?> p-2">
                                            <?= $reading['blood_pressure_diastolic'] ?> mmHg
                                            <?php if ($is_abnormal): ?>
                                                <i class="fas fa-exclamation-circle ms-1"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <?php $is_abnormal = ($reading['oxygen_level'] !== null && 
                                                     $reading['oxygen_level'] < $normal_ranges['oxygen_level'][0]); ?>
                                <td class="<?= $is_abnormal ? 'abnormal' : ($reading['oxygen_level'] !== null ? 'normal' : '') ?>">
                                    <?php if ($reading['oxygen_level'] !== null): ?>
                                        <span class="badge <?= $is_abnormal ? 'bg-danger' : 'bg-success' ?> p-2">
                                            <?= $reading['oxygen_level'] ?>%
                                            <?php if ($is_abnormal): ?>
                                                <i class="fas fa-exclamation-circle ms-1"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <?php $is_abnormal = ($reading['heart_rate'] !== null && 
                                                     ($reading['heart_rate'] < $normal_ranges['heart_rate'][0] || 
                                                      $reading['heart_rate'] > $normal_ranges['heart_rate'][1])); ?>
                                <td class="<?= $is_abnormal ? 'abnormal' : ($reading['heart_rate'] !== null ? 'normal' : '') ?>">
                                    <?php if ($reading['heart_rate'] !== null): ?>
                                        <span class="badge <?= $is_abnormal ? 'bg-danger' : 'bg-success' ?> p-2">
                                            <?= $reading['heart_rate'] ?> bpm
                                            <?php if ($is_abnormal): ?>
                                                <i class="fas fa-exclamation-circle ms-1"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 mb-4">
            <form method="POST">
                <button type="submit" name="download_vital_signs" class="btn btn-success">
                    <i class="fas fa-file-download me-2"></i>Download Vital Signs PDF
                </button>
            </form>
        </div>

        <?php if (isset($pdf_error)): ?>
            <div class="alert alert-danger">
                <?php echo $pdf_error; ?>
            </div>
        <?php endif; ?>

        
    </div>

    <script>
        <?php if (isset($_SESSION['timer_start'])): ?>
        // Timer functionality
        let timerEnd = <?= $timer_end_time ?>;
        
        function updateCountdown() {
            let now = Math.floor(Date.now() / 1000);
            let diff = timerEnd - now;
            
            if (diff > 0) {
                let hours = Math.floor(diff / 3600);
                let minutes = Math.floor((diff % 3600) / 60);
                let seconds = diff % 60;
                
                document.getElementById('countdown').textContent = 
                    (hours < 10 ? "0" + hours : hours) + ":" +
                    (minutes < 10 ? "0" + minutes : minutes) + ":" +
                    (seconds < 10 ? "0" + seconds : seconds);
                    
                setTimeout(updateCountdown, 1000);
            } else {
                document.getElementById('countdown').textContent = "00:00:00";
                alert("Time to take your follow-up reading!");
            }
        }
        
        updateCountdown();
        <?php endif; ?>
    </script>
</body>
</html>


