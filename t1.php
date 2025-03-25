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
    unset($_SESSION['abnormal_reading'], $_SESSION['timer_start'], $_SESSION['timer_end'], $_SESSION['recheck_type']);
    $show_timer = false;
    $success_message = "Timer has been stopped.";
}
// Check if a timer needs to start for abnormal readings
else if (isset($_SESSION['abnormal_reading']) && $_SESSION['abnormal_reading'] === true) {
    $show_timer = true;
    if (!isset($_SESSION['timer_start']) && isset($_POST['start_timer'])) {
        $_SESSION['timer_start'] = time();
        $_SESSION['timer_end'] = time() + 1800; // 30 minutes
    }
    if (isset($_SESSION['timer_start'])) {
        $timer_end_time = $_SESSION['timer_end'];
        if (time() >= $_SESSION['timer_end']) {
            $show_timer = false;
            unset($_SESSION['abnormal_reading'], $_SESSION['timer_start'], $_SESSION['timer_end']);
        }
    }
}

// Handling vital sign submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_vital'])) {
    $vital_type = $_POST["vital_type"];
    $value = $_POST["value"];

    if (empty($vital_type) || empty($value)) {
        $error_message = "All fields are required.";
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
                
                if ($is_abnormal) {
                    $_SESSION['abnormal_reading'] = true;
                    $_SESSION['recheck_time'] = time() + 1800; // 30 minutes
                    $_SESSION['recheck_type'] = $vital_type;
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
    <title>Health Monitoring</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --normal-color: rgb(62, 237, 43);
            --abnormal-color: #dc3545;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        h2, h4 {
            color: #333;
            border-bottom: 2px solid var(--normal-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .abnormal {
            color: var(--abnormal-color);
            font-weight: bold;
            position: relative;
        }
        
        .normal {
            color: var(--normal-color);
            font-weight: bold;
        }
        
        .abnormal-icon {
            margin-left: 5px;
            animation: vibrate 0.3s infinite alternate;
        }
        
        @keyframes vibrate {
            0% { transform: translateX(0); }
            100% { transform: translateX(3px); }
        }
        
        .timer-container {
            background: linear-gradient(to right, rgba(62, 237, 43, 0.2), rgba(62, 237, 43, 0.4));
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .timer-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        #countdown {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            font-family: monospace;
        }
        
        .timer-text {
            font-size: 0.9rem;
            margin-top: 5px;
            color: #666;
        }
        
        .timer-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-stop {
            background-color: #f8f9fa;
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .btn-stop:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .readings-scroll {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding: 10px 0;
            margin-bottom: 20px;
        }
        
        .reading-card {
            min-width: 250px;
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid var(--normal-color);
        }
        
        .reading-card.abnormal-card {
            border-left: 4px solid var(--abnormal-color);
        }
        
        .vital-value {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .vital-date {
            font-size: 0.8rem;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mt-4">Health Monitoring Dashboard</h2>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if ($show_timer): ?>
            <div class="timer-container">
                <?php if (!isset($_SESSION['timer_start'])): ?>
                    <div class="timer-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Abnormal Reading Detected
                    </div>
                    <p>Please start the timer and take another reading in 30 minutes.</p>
                    <div class="timer-buttons">
                        <form method="POST">
                            <button type="submit" name="start_timer" class="btn btn-primary">
                                <i class="fas fa-clock me-2"></i>Start 30-Minute Timer
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
                    <div id="countdown">00:30:00</div>
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
                <thead>
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
                    <?php foreach ($readings as $reading): ?>
                        <tr>
                            <td><?= $reading['formatted_time'] ?></td>
                            
                            <?php $is_abnormal = ($reading['blood_sugar'] !== null && 
                                                 ($reading['blood_sugar'] < $normal_ranges['blood_sugar'][0] || 
                                                  $reading['blood_sugar'] > $normal_ranges['blood_sugar'][1])); ?>
                            <td class="<?= $is_abnormal ? 'abnormal' : ($reading['blood_sugar'] !== null ? 'normal' : '') ?>">
                                <?php if ($reading['blood_sugar'] !== null): ?>
                                    <?= $reading['blood_sugar'] ?> mg/dL
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            
                            <?php $is_abnormal = ($reading['blood_pressure_systolic'] !== null && 
                                                 ($reading['blood_pressure_systolic'] < $normal_ranges['blood_pressure_systolic'][0] || 
                                                  $reading['blood_pressure_systolic'] > $normal_ranges['blood_pressure_systolic'][1])); ?>
                            <td class="<?= $is_abnormal ? 'abnormal' : ($reading['blood_pressure_systolic'] !== null ? 'normal' : '') ?>">
                                <?php if ($reading['blood_pressure_systolic'] !== null): ?>
                                    <?= $reading['blood_pressure_systolic'] ?> mmHg
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            
                            <?php $is_abnormal = ($reading['blood_pressure_diastolic'] !== null && 
                                                 ($reading['blood_pressure_diastolic'] < $normal_ranges['blood_pressure_diastolic'][0] || 
                                                  $reading['blood_pressure_diastolic'] > $normal_ranges['blood_pressure_diastolic'][1])); ?>
                            <td class="<?= $is_abnormal ? 'abnormal' : ($reading['blood_pressure_diastolic'] !== null ? 'normal' : '') ?>">
                                <?php if ($reading['blood_pressure_diastolic'] !== null): ?>
                                    <?= $reading['blood_pressure_diastolic'] ?> mmHg
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            
                            <?php $is_abnormal = ($reading['oxygen_level'] !== null && 
                                                 $reading['oxygen_level'] < $normal_ranges['oxygen_level'][0]); ?>
                            <td class="<?= $is_abnormal ? 'abnormal' : ($reading['oxygen_level'] !== null ? 'normal' : '') ?>">
                                <?php if ($reading['oxygen_level'] !== null): ?>
                                    <?= $reading['oxygen_level'] ?>%
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            
                            <?php $is_abnormal = ($reading['heart_rate'] !== null && 
                                                 ($reading['heart_rate'] < $normal_ranges['heart_rate'][0] || 
                                                  $reading['heart_rate'] > $normal_ranges['heart_rate'][1])); ?>
                            <td class="<?= $is_abnormal ? 'abnormal' : ($reading['heart_rate'] !== null ? 'normal' : '') ?>">
                                <?php if ($reading['heart_rate'] !== null): ?>
                                    <?= $reading['heart_rate'] ?> bpm
                                    <?php if ($is_abnormal): ?>
                                        <i class="fas fa-exclamation-circle abnormal-icon"></i>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
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
