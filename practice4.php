<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Monitoring Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .metric-title {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }

        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .metric-status {
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 15px;
            display: inline-block;
        }

        .status-normal {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-warning {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-alert {
            background: #ffebee;
            color: #c62828;
        }

        .input-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52,152,219,0.3);
        }

        .submit-btn {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .submit-btn:hover {
            background: #2980b9;
        }

        .history-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .history-table tr:hover {
            background: #f8f9fa;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .metric-card {
                padding: 15px;
            }

            .history-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Health Monitoring Dashboard</h1>
            <p>Track and monitor your vital health metrics</p>
        </div>

        <div class="dashboard-grid">
            <div class="metric-card">
                <div class="metric-title">Blood Pressure</div>
                <div class="metric-value">120/80</div>
                <span class="metric-status status-normal">Normal</span>
            </div>

            <div class="metric-card">
                <div class="metric-title">Heart Rate</div>
                <div class="metric-value">75 bpm</div>
                <span class="metric-status status-normal">Normal</span>
            </div>

            <div class="metric-card">
                <div class="metric-title">Temperature</div>
                <div class="metric-value">37.2°C</div>
                <span class="metric-status status-normal">Normal</span>
            </div>

            <div class="metric-card">
                <div class="metric-title">Oxygen Level</div>
                <div class="metric-value">98%</div>
                <span class="metric-status status-normal">Normal</span>
            </div>
        </div>

        <div class="input-form">
            <h2>Record New Measurements</h2>
            <form class="form-grid">
                <div class="form-group">
                    <label>Blood Pressure (mmHg)</label>
                    <input type="text" placeholder="e.g., 120/80">
                </div>

                <div class="form-group">
                    <label>Heart Rate (bpm)</label>
                    <input type="number" placeholder="e.g., 75">
                </div>

                <div class="form-group">
                    <label>Temperature (°C)</label>
                    <input type="number" step="0.1" placeholder="e.g., 37.0">
                </div>

                <div class="form-group">
                    <label>Oxygen Level (%)</label>
                    <input type="number" placeholder="e.g., 98">
                </div>

                <div class="form-group">
                    <button type="submit" class="submit-btn">Save Measurements</button>
                </div>
            </form>
        </div>

        <div class="history-section">
            <h2>Recent History</h2>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Blood Pressure</th>
                        <th>Heart Rate</th>
                        <th>Temperature</th>
                        <th>Oxygen Level</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2024-01-20 09:30</td>
                        <td>120/80</td>
                        <td>75 bpm</td>
                        <td>37.2°C</td>
                        <td>98%</td>
                    </tr>
                    <tr>
                        <td>2024-01-19 10:15</td>
                        <td>118/78</td>
                        <td>72 bpm</td>
                        <td>36.9°C</td>
                        <td>99%</td>
                    </tr>
                    <tr>
                        <td>2024-01-18 08:45</td>
                        <td>122/82</td>
                        <td>78 bpm</td>
                        <td>37.0°C</td>
                        <td>97%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>