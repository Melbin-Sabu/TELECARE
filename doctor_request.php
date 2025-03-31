<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is a doctor
/*if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$doctor_email = $_SESSION['email']; // Assuming email is stored in session
$success_message = "";
$error_message = "";
*/
// Handle request acceptance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_request'])) {
    $consultation_id = $_POST['consultation_id'];
    $meet_link = $_POST['meet_link'];
    
    // Update consultation status to assigned and add meet link
    $update_sql = "UPDATE consultations SET status = 'assigned', meet_link = ? WHERE id = ? AND doctor_email = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sis", $meet_link, $consultation_id, $doctor_email);
    
    if ($update_stmt->execute()) {
        $success_message = "Consultation request accepted successfully. The patient has been notified.";
    } else {
        $error_message = "Error: " . $update_stmt->error;
    }
    $update_stmt->close();
}

// Handle request completion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_consultation'])) {
    $consultation_id = $_POST['consultation_id'];
    
    // Update consultation status to completed
    $complete_sql = "UPDATE consultations SET status = 'completed' WHERE id = ? AND doctor_email = ?";
    $complete_stmt = $conn->prepare($complete_sql);
    $complete_stmt->bind_param("is", $consultation_id, $doctor_email);
    
    if ($complete_stmt->execute()) {
        $success_message = "Consultation marked as completed successfully.";
    } else {
        $error_message = "Error: " . $complete_stmt->error;
    }
    $complete_stmt->close();
}

// Fetch pending consultation requests for this doctor
$pending_sql = "SELECT c.*, s.full_name as patient_name, s.email as patient_email 
               FROM consultations c 
               JOIN signup s ON c.user_id = s.id 
               WHERE c.doctor_email = ? AND c.status = 'pending' 
               ORDER BY c.created_at DESC";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("s", $doctor_email);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_requests = [];

while ($row = $pending_result->fetch_assoc()) {
    // Format date for display
    $created_at = new DateTime($row['created_at']);
    $row['formatted_date'] = $created_at->format('F j, Y');
    $row['formatted_time'] = $created_at->format('h:i A');
    
    // Parse health data JSON
    $row['health_data_array'] = json_decode($row['health_data'], true);
    
    $pending_requests[] = $row;
}
$pending_stmt->close();

// Fetch active consultations for this doctor
$active_sql = "SELECT c.*, s.full_name as patient_name, s.email as patient_email 
              FROM consultations c 
              JOIN signup s ON c.user_id = s.id 
              WHERE c.doctor_email = ? AND c.status = 'assigned' 
              ORDER BY c.created_at DESC";
$active_stmt = $conn->prepare($active_sql);
$active_stmt->bind_param("s", $doctor_email);
$active_stmt->execute();
$active_result = $active_stmt->get_result();
$active_consultations = [];

while ($row = $active_result->fetch_assoc()) {
    // Format date for display
    $created_at = new DateTime($row['created_at']);
    $row['formatted_date'] = $created_at->format('F j, Y');
    $row['formatted_time'] = $created_at->format('h:i A');
    
    // Parse health data JSON
    $row['health_data_array'] = json_decode($row['health_data'], true);
    
    $active_consultations[] = $row;
}
$active_stmt->close();

// Fetch completed consultations for this doctor
$completed_sql = "SELECT c.*, s.full_name as patient_name, s.email as patient_email 
                 FROM consultations c 
                 JOIN signup s ON c.user_id = s.id 
                 WHERE c.doctor_email = ? AND c.status = 'completed' 
                 ORDER BY c.created_at DESC 
                 LIMIT 10";
$completed_stmt = $conn->prepare($completed_sql);
$completed_stmt->bind_param("s", $doctor_email);
$completed_stmt->execute();
$completed_result = $completed_stmt->get_result();
$completed_consultations = [];

while ($row = $completed_result->fetch_assoc()) {
    // Format date for display
    $created_at = new DateTime($row['created_at']);
    $row['formatted_date'] = $created_at->format('F j, Y');
    $row['formatted_time'] = $created_at->format('h:i A');
    
    // Parse health data JSON
    $row['health_data_array'] = json_decode($row['health_data'], true);
    
    $completed_consultations[] = $row;
}
$completed_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Consultation Requests - TeleCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .request-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .request-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-assigned {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-completed {
            background-color: #cff4fc;
            color: #055160;
        }
        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
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
        .health-data-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e9ecef;
        }
        .health-data-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
  
    
    <div class="container py-5">
        <h2 class="mb-4"><i class="fas fa-stethoscope me-2"></i>Doctor Consultation Requests</h2>
        
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
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Pending Requests -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Consultation Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                <p>No pending consultation requests at this time.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $request): ?>
                            <div class="request-card p-3 mb-3 border">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="patient-avatar me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1"><?= $request['patient_name'] ?></h5>
                                            <p class="text-muted mb-0 small">
                                                <i class="far fa-envelope me-1"></i> <?= $request['patient_email'] ?>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-hourglass-half me-1"></i> Pending
                                    </span>
                                </div>
                                
                                <div class="health-data mb-3">
                                    <h6 class="section-title">Health Information</h6>
                                    <div class="health-data-item">
                                        <strong>Health Issue:</strong> <?= $request['health_data_array']['health_issue'] ?>
                                    </div>
                                    <div class="health-data-item">
                                        <strong>Symptoms:</strong> <?= $request['health_data_array']['symptoms'] ?>
                                    </div>
                                    <?php if (!empty($request['health_data_array']['medical_history'])): ?>
                                    <div class="health-data-item">
                                        <strong>Medical History:</strong> <?= $request['health_data_array']['medical_history'] ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['health_data_array']['current_medications'])): ?>
                                    <div class="health-data-item">
                                        <strong>Current Medications:</strong> <?= $request['health_data_array']['current_medications'] ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="request-meta text-muted small mb-3">
                                    <i class="far fa-calendar-alt me-1"></i> Requested on <?= $request['formatted_date'] ?> at <?= $request['formatted_time'] ?>
                                </div>
                                
                                <div class="text-end">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#acceptModal<?= $request['id'] ?>">
                                        <i class="fas fa-check-circle me-1"></i> Accept Request
                                    </button>
                                </div>
                                
                                <!-- Accept Modal -->
                                <div class="modal fade" id="acceptModal<?= $request['id'] ?>" tabindex="-1" aria-labelledby="acceptModalLabel<?= $request['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="acceptModalLabel<?= $request['id'] ?>">Accept Consultation Request</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="">
                                                <div class="modal-body">
                                                    <p>You are accepting a consultation request from <strong><?= $request['patient_name'] ?></strong>.</p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="meet_link<?= $request['id'] ?>" class="form-label">Video Consultation Link</label>
                                                        <input type="url" class="form-control" id="meet_link<?= $request['id'] ?>" name="meet_link" 
                                                               placeholder="https://meet.google.com/xxx-xxxx-xxx" required>
                                                        <div class="form-text">Provide a Google Meet, Zoom, or other video conferencing link for the consultation.</div>
                                                    </div>
                                                    
                                                    <input type="hidden" name="consultation_id" value="<?= $request['id'] ?>">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="accept_request" class="btn btn-primary">Accept Request</button>
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
                
                <!-- Active Consultations -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-video me-2"></i>Active Consultations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($active_consultations)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <p>No active consultations at this time.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($active_consultations as $consultation): ?>
                            <div class="request-card p-3 mb-3 border">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="patient-avatar me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1"><?= $consultation['patient_name'] ?></h5>
                                            <p class="text-muted mb-0 small">
                                                <i class="far fa-envelope me-1"></i> <?= $consultation['patient_email'] ?>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="status-badge status-assigned">
                                        <i class="fas fa-check-circle me-1"></i> Active
                                    </span>
                                </div>
                                
                                <div class="health-data mb-3">
                                    <h6 class="section-title">Health Information</h6>
                                    <div class="health-data-item">
                                        <strong>Health Issue:</strong> <?= $consultation['health_data_array']['health_issue'] ?>
                                    </div>
                                    <div class="health-data-item">
                                        <strong>Symptoms:</strong> <?= $consultation['health_data_array']['symptoms'] ?>
                                    </div>
                                    <?php if (!empty($consultation['health_data_array']['medical_history'])): ?>
                                    <div class="health-data-item">
                                        <strong>Medical History:</strong> <?= $consultation['health_data_array']['medical_history'] ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($consultation['health_data_array']['current_medications'])): ?>
                                    <div class="health-data-item">
                                        <strong>Current Medications:</strong> <?= $consultation['health_data_array']['current_medications'] ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="request-meta text-muted small">
                                        <i class="far fa-calendar-alt me-1"></i> Accepted on <?= $consultation['formatted_date'] ?>
                                    </div>
                                    
                                    <div>
                                        <a href="<?= $consultation['meet_link'] ?>" target="_blank" class="btn btn-sm btn-primary me-2">
                                            <i class="fas fa-video me-1"></i> Join Meeting
                                        </a>
                                        
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="consultation_id" value="<?= $consultation['id'] ?>">
                                            <button type="submit" name="complete_consultation" class="btn btn-sm btn-success" 
                                                    onclick="return confirm('Are you sure you want to mark this consultation as completed?')">
                                                <i class="fas fa-check-double me-1"></i> Mark as Completed
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Completed Consultations -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Completed Consultations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($completed_consultations)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                                <p>No completed consultations yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Health Issue</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($completed_consultations as $completed): ?>
                                        <tr>
                                            <td><?= $completed['patient_name'] ?></td>
                                            <td><?= $completed['health_data_array']['health_issue'] ?></td>
                                            <td><?= $completed['formatted_date'] ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                        data-bs-target="#viewModal<?= $completed['id'] ?>">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- View Modals for Completed Consultations -->
                            <?php foreach ($completed_consultations as $completed): ?>
                            <div class="modal fade" id="viewModal<?= $completed['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Consultation Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="section-title">Patient Information</h6>
                                                    <p><strong>Name:</strong> <?= $completed['patient_name'] ?></p>
                                                    <p><strong>Email:</strong> <?= $completed['patient_email'] ?></p>
                                                    <p><strong>Consultation Date:</strong> <?= $completed['formatted_date'] ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="section-title">Health Information</h6>
                                                    <p><strong>Health Issue:</strong> <?= $completed['health_data_array']['health_issue'] ?></p>
                                                    <p><strong>Symptoms:</strong> <?= $completed['health_data_array']['symptoms'] ?></p>
                                                    
                                                    <?php if (!empty($completed['health_data_array']['medical_history'])): ?>
                                                    <p><strong>Medical History:</strong> <?= $completed['health_data_array']['medical_history'] ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($completed['health_data_array']['current_medications'])): ?>
                                                    <p><strong>Current Medications:</strong> <?= $completed['health_data_array']['current_medications'] ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <a href="#" class="btn btn-primary">
                                                <i class="fas fa-file-medical me-1"></i> Generate Report
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Doctor Profile Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>Doctor Profile</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="mx-auto" style="width: 100px; height: 100px; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-md fa-3x text-primary"></i>
                            </div>
                        </div>
                         <!--<h5 class="card-title">Dr. <?= $_SESSION['name'] ?? 'Doctor' ?></h5>
                        <p class="card-text text-muted"><?= $_SESSION['specialization'] ?? 'Medical Professional' ?></p>
                        <p class="card-text"><i class="fas fa-envelope me-2"></i><?= $_SESSION['email'] ?></p>-->
                        
                        <div class="d-grid gap-2 mt-3">
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Card -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Your Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="p-3">
                                    <h3 class="text-primary"><?= count($pending_requests) ?></h3>
                                    <p class="small text-muted mb-0">Pending</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3">
                                    <h3 class="text-success"><?= count($active_consultations) ?></h3>
                                    <p class="small text-muted mb-0">Active</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3">
                                    <h3 class="text-info"><?= count($completed_consultations) ?></h3>
                                    <p class="small text-muted mb-0">Completed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fas fa-calendar-alt me-2"></i>My Schedule
                            </a>
                            <a href="#" class="btn btn-outline-success">
                                <i class="fas fa-users me-2"></i>Patient Records
                            </a>
                            <a href="#" class="btn btn-outline-info">
                                <i class="fas fa-prescription me-2"></i>Prescriptions
                            </a>
                            <a href="#" class="btn btn-outline-secondary">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Help Card -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-question-circle me-2 text-primary"></i>Need Help?</h5>
                        <p class="card-text small">If you're experiencing any issues with the consultation system, please contact our support team.</p>
                        <a href="mailto:support@telecare.com" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-envelope me-1"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>