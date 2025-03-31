<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Debug to check session variables
// echo "<pre>"; print_r($_SESSION); echo "</pre>";

// Handle consultation request submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_consultation'])) {
    $doctor_email = $_POST['doctor_email'];
    $health_issue = $_POST['health_issue'];
    $symptoms = $_POST['symptoms'];
    $medical_history = $_POST['medical_history'] ?? '';
    $current_medications = $_POST['current_medications'] ?? '';
    $share_vitals = isset($_POST['share_vitals']) ? 1 : 0;
    
    // Create health data array and convert to JSON
    $health_data = [
        'health_issue' => $health_issue,
        'symptoms' => $symptoms,
        'medical_history' => $medical_history,
        'current_medications' => $current_medications,
        'share_vitals' => $share_vitals
    ];
    
    $health_data_json = json_encode($health_data);
    
    // Insert consultation request into database
    $insert_sql = "INSERT INTO consultations (user_id, doctor_email, health_data, status, created_at) 
                  VALUES (?, ?, ?, 'pending', NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $status = 'pending';
    $insert_stmt->bind_param("iss", $user_id, $doctor_email, $health_data_json);
    
    if ($insert_stmt->execute()) {
        $success_message = "Your consultation request has been submitted successfully. A doctor will review it shortly.";
    } else {
        $error_message = "Error: " . $insert_stmt->error;
    }
    $insert_stmt->close();
}

// Fetch all doctors
$doctors_sql = "SELECT * FROM doctors WHERE status = 'active' ORDER BY name";
$doctors_result = $conn->query($doctors_sql);
$doctors = [];

if ($doctors_result && $doctors_result->num_rows > 0) {
    while ($row = $doctors_result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Fetch user's consultations
$consultations_sql = "SELECT c.*, d.name as doctor_name 
                     FROM consultations c 
                     LEFT JOIN doctors d ON c.doctor_email = d.email 
                     WHERE c.user_id = ? 
                     ORDER BY c.created_at DESC";
$consultations_stmt = $conn->prepare($consultations_sql);
$consultations_stmt->bind_param("i", $user_id);
$consultations_stmt->execute();
$consultations_result = $consultations_stmt->get_result();

$active_consultations = [];
$pending_consultations = [];
$past_consultations = [];

while ($row = $consultations_result->fetch_assoc()) {
    // Format date for display
    $created_at = new DateTime($row['created_at']);
    $row['formatted_date'] = $created_at->format('F j, Y');
    $row['formatted_time'] = $created_at->format('h:i A');
    
    // Parse health data JSON
    $row['health_data_array'] = json_decode($row['health_data'], true);
    
    // Categorize consultations
    if ($row['status'] === 'assigned') {
        $active_consultations[] = $row;
    } elseif ($row['status'] === 'pending') {
        $pending_consultations[] = $row;
    } elseif ($row['status'] === 'completed') {
        $past_consultations[] = $row;
    }
}
$consultations_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Consultation - TELECARE+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .consultation-card {
            border-radius: 10px;
            transition: all 0.3s;
        }
        .consultation-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-assigned {
            background-color: #d4edda;
            color: #155724;
        }
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        .doctor-card {
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 15px;
        }
        .doctor-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .doctor-card.selected {
            background-color: #e7f1ff;
            border-color: #0d6efd;
            border-width: 2px;
        }
        .doctor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6c757d;
        }
        .meet-button {
            background-color: #0d6efd;
            color: white;
            border-radius: 20px;
            padding: 8px 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
        }
        .meet-button:hover {
            background-color: #0b5ed7;
            color: white;
            transform: translateY(-2px);
        }
        .section-title {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h2 class="mb-4"><i class="fas fa-user-md me-2"></i>Doctor Consultation</h2>
        
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
        
        <div class="row">
            <!-- Left Column - Consultation Form and History -->
            <div class="col-lg-8">
                <!-- Active Consultations -->
                <?php if (!empty($active_consultations)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-video me-2"></i>Active Consultations</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($active_consultations as $consultation): ?>
                        <div class="consultation-card p-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1">Consultation with <?= $consultation['doctor_name'] ?? 'Assigned Doctor' ?></h5>
                                    <p class="text-muted mb-2">
                                        <i class="far fa-calendar-alt me-1"></i> <?= $consultation['formatted_date'] ?> at <?= $consultation['formatted_time'] ?>
                                    </p>
                                    
                                </div>
                                <span class="status-badge status-assigned">
                                    <i class="fas fa-check-circle me-1"></i> Assigned
                                </span>
                            </div>
                            
                            <?php if (!empty($consultation['meet_link'])): ?>
                            <div class="mt-3 text-center">
                                <a href="<?= $consultation['meet_link'] ?>" target="_blank" class="meet-button">
                                    <i class="fas fa-video me-2"></i> Join Video Consultation
                                </a>
                                <p class="text-muted mt-2 small">Click to join your scheduled video consultation</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Pending Consultations -->
                <?php if (!empty($pending_consultations)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Consultations</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($pending_consultations as $consultation): ?>
                        <div class="consultation-card p-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1">Consultation Request</h5>
                                    <p class="text-muted mb-2">
                                        <i class="far fa-calendar-alt me-1"></i> Submitted on <?= $consultation['formatted_date'] ?> at <?= $consultation['formatted_time'] ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Health Issue:</strong> <?= $consultation['health_data_array']['health_issue'] ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Requested Doctor:</strong> <?= $consultation['doctor_name'] ?? 'Any Available Doctor' ?>
                                    </p>
                                </div>
                                <span class="status-badge status-pending">
                                    <i class="fas fa-hourglass-half me-1"></i> Pending
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Request Consultation Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Request a New Consultation</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="doctor_email" class="form-label">Select Doctor</label>
                                <select class="form-select" id="doctor_email" name="doctor_email" required>
                                    <option value="">-- Choose a doctor --</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?= $doctor['email'] ?>"><?= $doctor['name'] ?> (<?= $doctor['qualification'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="health_issue" class="form-label">Health Issue</label>
                                <input type="text" class="form-control" id="health_issue" name="health_issue" 
                                       placeholder="Briefly describe your main health concern" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="symptoms" class="form-label">Symptoms</label>
                                <textarea class="form-control" id="symptoms" name="symptoms" rows="3" 
                                          placeholder="Describe your symptoms in detail" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="medical_history" class="form-label">Medical History (Optional)</label>
                                <textarea class="form-control" id="medical_history" name="medical_history" rows="2" 
                                          placeholder="Any relevant medical history"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="current_medications" class="form-label">Current Medications (Optional)</label>
                                <textarea class="form-control" id="current_medications" name="current_medications" rows="2" 
                                          placeholder="List any medications you're currently taking"></textarea>
                            </div>
                            
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="share_vitals" name="share_vitals" checked>
                                <label class="form-check-label" for="share_vitals">
                                    Share my recent vital signs with the doctor
                                </label>
                            </div>
                            
                            <button type="submit" name="request_consultation" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Consultation Request
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Past Consultations -->
                <?php if (!empty($past_consultations)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Past Consultations</h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="pastConsultationsAccordion">
                            <?php foreach ($past_consultations as $index => $consultation): ?>
                            <div class="accordion-item mb-2">
                                <h2 class="accordion-header" id="heading<?= $index ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                                        <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                            <div>
                                                <strong><?= $consultation['doctor_name'] ?? 'Consulting Doctor' ?></strong> - 
                                                <?= $consultation['health_data_array']['health_issue'] ?>
                                            </div>
                                            <div class="text-muted small"><?= $consultation['formatted_date'] ?></div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" 
                                     data-bs-parent="#pastConsultationsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Health Information</h6>
                                                <p><strong>Health Issue:</strong> <?= $consultation['health_data_array']['health_issue'] ?></p>
                                                <p><strong>Symptoms:</strong> <?= $consultation['health_data_array']['symptoms'] ?></p>
                                                
                                                <?php if (!empty($consultation['health_data_array']['medical_history'])): ?>
                                                <p><strong>Medical History:</strong> <?= $consultation['health_data_array']['medical_history'] ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($consultation['health_data_array']['current_medications'])): ?>
                                                <p><strong>Medications:</strong> <?= $consultation['health_data_array']['current_medications'] ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Consultation Details</h6>
                                                <p><strong>Doctor:</strong> <?= $consultation['doctor_name'] ?? 'Consulting Doctor' ?></p>
                                                <p><strong>Date:</strong> <?= $consultation['formatted_date'] ?> at <?= $consultation['formatted_time'] ?></p>
                                                <p><strong>Status:</strong> <span class="status-badge status-completed">Completed</span></p>
                                                
                                                <div class="mt-3">
                                                    <a href="#" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-file-medical me-1"></i> View Full Report
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column - Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Access Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Quick Access</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fas fa-calendar-alt me-2"></i>My Appointments
                            </a>
                            <a href="vital_signs.php" class="btn btn-outline-success">
                                <i class="fas fa-heartbeat me-2"></i>Record Vital Signs
                            </a>
                            <a href="#" class="btn btn-outline-info">
                                <i class="fas fa-pills me-2"></i>My Medications
                            </a>
                            <a href="#" class="btn btn-outline-warning">
                                <i class="fas fa-file-medical me-2"></i>Medical Records
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Featured Doctors -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>Our Doctors</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($doctors, 0, 3) as $doctor): ?>
                            <div class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <div class="doctor-avatar me-3">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?= $doctor['name'] ?></h6>
                                        <div class="small text-muted"><?= $doctor['qualification'] ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer bg-light text-center">
                            <a href="#" class="btn btn-sm btn-outline-primary">View All Doctors</a>
                        </div>
                    </div>
                </div>
                
                <!-- Health Tips -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Health Tips</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><i class="fas fa-heart text-danger me-2"></i>Heart Health</h6>
                            <p class="small">Regular exercise, even just 30 minutes of walking daily, can significantly improve your heart health.</p>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-brain text-primary me-2"></i>Mental Wellness</h6>
                            <p class="small">Practice mindfulness or meditation for 10 minutes daily to reduce stress and improve mental clarity.</p>
                        </div>
                        <div>
                            <h6><i class="fas fa-apple-alt text-success me-2"></i>Nutrition</h6>
                            <p class="small">Incorporate colorful fruits and vegetables into your diet for a wide range of essential nutrients.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Emergency Contact -->
                <div class="card mb-4 bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-ambulance me-2"></i>Emergency?</h5>
                        <p class="card-text">If you're experiencing a medical emergency, please call emergency services immediately.</p>
                        <a href="tel:911" class="btn btn-light w-100">
                            <i class="fas fa-phone-alt me-2"></i>Call Emergency Services
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>