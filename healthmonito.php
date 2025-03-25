<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Monitoring Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --accent-color: #00BCD4;
            --warning-color: #FFC107;
            --danger-color: #F44336;
            --success-color: #8BC34A;
            --text-dark: #333;
            --text-light: #f8f8f8;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f8f9;
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-dark));
            color: var(--text-light);
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 100;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid var(--text-light);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: all 0.3s ease;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 24px;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }
        
        .header p {
            color: #777;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            margin-top: auto;
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 500;
        }
        
        .user-role {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .vital-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .vital-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .vital-box:hover {
            transform: translateY(-5px);
        }
        
        .vital-icon {
            font-size: 30px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .vital-title {
            font-size: 14px;
            color: #777;
            margin-bottom: 5px;
        }
        
        .vital-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        #result-container {
            display: none;
            margin-top: 30px;
            padding: 20px;
            border-radius: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid var(--primary-color);
        }
        
        .normal {
            border-left-color: var(--success-color);
        }
        
        .warning {
            border-left-color: var(--warning-color);
        }
        
        .danger {
            border-left-color: var(--danger-color);
        }
        
        .result-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }
        
        .result-message {
            line-height: 1.6;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .vital-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .sidebar.active {
                width: var(--sidebar-width);
                padding: 20px 0;
            }
            
            .vital-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>HealthTrack</h2>
            <p>Your Personal Health Monitor</p>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="#"><i class="fas fa-heartbeat"></i>upload prescription</a></li>
            <li><a href="healthmonito.php"><i class="fas fa-chart-line"></i> Health Monitoring</a></li>
            <li><a href="ordermedi.php"><i class="fas fa-pills"></i>order Medicine</a></li>
            <li><a href="Logout.php"><i class="fas fa-calendar-alt"></i>Logout</a></li>
           
        </ul>
        
        
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Health Monitoring Dashboard</h1>
            <p>Monitor and track your vital health parameters</p>
        </div>
        
        <div class="vital-stats">
            <div class="vital-box">
                <div class="vital-icon"><i class="fas fa-tint"></i></div>
                <div class="vital-title">Blood Sugar</div>
                <div class="vital-value" id="sugar-display">
                    <?php echo isset($latest_data) ? $latest_data['blood_sugar'] . ' mg/dL' : '-- mg/dL'; ?>
                </div>
            </div>
            
            <div class="vital-box">
                <div class="vital-icon"><i class="fas fa-heart"></i></div>
                <div class="vital-title">Blood Pressure</div>
                <div class="vital-value" id="pressure-display">
                    <?php echo isset($latest_data) ? $latest_data['blood_pressure'] . ' mmHg' : '--/-- mmHg'; ?>
                </div>
            </div>
            
            <div class="vital-box">
                <div class="vital-icon"><i class="fas fa-wind"></i></div>
                <div class="vital-title">Oxygen Level</div>
                <div class="vital-value" id="oxygen-display">
                    <?php echo isset($latest_data) ? $latest_data['oxygen_level'] . '%' : '--%'; ?>
                </div>
            </div>
            
            <div class="vital-box">
                <div class="vital-icon"><i class="fas fa-thermometer-half"></i></div>
                <div class="vital-title">Temperature</div>
                <div class="vital-value" id="temp-display">
                    <?php echo isset($latest_data) ? $latest_data['temperature'] . ' °C' : '-- °C'; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Enter Your Health Parameters</h3>
            </div>
            
            <form id="healthForm">
                <div class="form-group">
                    <label for="sugar">Blood Sugar (mg/dL)</label>
                    <input type="number" id="sugar" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="pressure">Blood Pressure (mmHg)</label>
                    <input type="text" id="pressure" class="form-control" placeholder="e.g., 120/80" required>
                </div>
                
                <div class="form-group">
                    <label for="oxygen">Oxygen Level (%)</label>
                    <input type="number" id="oxygen" class="form-control" min="0" max="100" required>
                </div>
                
                <div class="form-group">
                    <label for="temperature">Temperature (°C)</label>
                    <input type="number" id="temperature" class="form-control" step="0.1" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Check Health Status</button>
            </form>
        </div>
        
        <div id="result-container">
            <h3 class="result-title">Health Status Assessment</h3>
            <p class="result-message" id="result-message"></p>
        </div>
    </main>
    
    <script>
        document.getElementById("healthForm").addEventListener("submit", function(event) {
            event.preventDefault();
            
            // Get form values
            let sugar = parseFloat(document.getElementById("sugar").value);
            let pressure = document.getElementById("pressure").value.trim();
            let oxygen = parseFloat(document.getElementById("oxygen").value);
            let temperature = parseFloat(document.getElementById("temperature").value);
            
            // Update the vital displays
            document.getElementById("sugar-display").textContent = sugar + " mg/dL";
            document.getElementById("pressure-display").textContent = pressure + " mmHg";
            document.getElementById("oxygen-display").textContent = oxygen + "%";
            document.getElementById("temp-display").textContent = temperature + " °C";
            
            let messages = [];
            let resultStatus = "normal";
            
            // Blood Sugar check
            if (sugar < 70) {
                messages.push("⚠️ Low blood sugar detected. Consider having a quick source of glucose.");
                resultStatus = resultStatus === "normal" ? "warning" : resultStatus;
            } else if (sugar > 140) {
                messages.push("⚠️ High blood sugar detected. Monitor and consult your doctor.");
                resultStatus = "danger";
            }
            
            // Blood Pressure check
            if (pressure.includes("/")) {
                let bpValues = pressure.split("/");
                let systolic = parseInt(bpValues[0]);
                let diastolic = parseInt(bpValues[1]);
                
                if (systolic > 140 || diastolic > 90) {
                    messages.push("⚠️ High blood pressure (hypertension) detected. Consult your doctor soon.");
                    resultStatus = "danger";
                } else if (systolic > 130 || diastolic > 80) {
                    messages.push("⚠️ Elevated blood pressure detected. Consider lifestyle modifications.");
                    resultStatus = resultStatus === "normal" ? "warning" : resultStatus;
                } else if (systolic < 90 || diastolic < 60) {
                    messages.push("⚠️ Low blood pressure (hypotension) detected. Monitor for symptoms like dizziness.");
                    resultStatus = resultStatus === "normal" ? "warning" : resultStatus;
                }
            } else {
                alert("Invalid blood pressure format. Use systolic/diastolic format (e.g., 120/80).");
                return;
            }
            
            // Oxygen Level check
            if (oxygen < 90) {
                messages.push("🚨 Critically low oxygen level detected! Seek immediate medical attention.");
                resultStatus = "danger";
            } else if (oxygen < 95) {
                messages.push("⚠️ Low oxygen level detected. Monitor and consult your doctor if it persists.");
                resultStatus = resultStatus === "normal" ? "warning" : resultStatus;
            }
            
            // Temperature check
            if (temperature > 38) {
                messages.push("⚠️ High fever detected! Take fever-reducing medication and consider medical attention.");
                resultStatus = "danger";
            } else if (temperature > 37.5) {
                messages.push("⚠️ Mild fever detected. Rest and monitor your temperature.");
                resultStatus = resultStatus === "normal" ? "warning" : resultStatus;
            } else if (temperature < 36) {
                messages.push("⚠️ Low body temperature detected. Warm up and monitor.");
                resultStatus = resultStatus === "normal" ? "warning" : resultStatus;
            }
            
            // Display results
            let resultContainer = document.getElementById("result-container");
            let resultMessage = document.getElementById("result-message");
            
            resultContainer.style.display = "block";
            resultContainer.className = resultStatus;
            
            if (messages.length === 0) {
                resultMessage.innerHTML = "✅ All vitals are within normal range. Keep up the good work!";
                resultContainer.className = "normal";
            } else {
                resultMessage.innerHTML = messages.join("<br><br>");
            }
            

            
            // Scroll to results
            resultContainer.scrollIntoView({ behavior: 'smooth' });
        });
    </script>
</body>
</html>