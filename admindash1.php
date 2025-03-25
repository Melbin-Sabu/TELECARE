<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ... existing card and activity styles ... */

        /* Updated Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg,rgb(23, 205, 44) 0%,rgb(73, 174, 93) 100%);
            color: white;
            padding: 0;
            height: 100vh;
            position: fixed;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }

        .brand-wrapper {
            padding: 20px;
            text-align: center;
            background: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .brand-wrapper h2 {
            font-size: 24px;
            margin: 0;
            color: white;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .profile-section {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 10px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            padding: 3px;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-info {
            margin-bottom: 15px;
        }

        .profile-info h3 {
            margin: 0;
            font-size: 18px;
            color: white;
        }

        .profile-info p {
            margin: 5px 0 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            padding: 0;
            transition: all 0.3s ease;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 15px 25px;
            transition: all 0.3s ease;
        }

        .sidebar ul li a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(0);
        }

        .sidebar ul li.active a {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #fff;
        }

        .sidebar ul li i {
            width: 25px;
            margin-right: 10px;
            font-size: 18px;
        }

        .menu-title {
            padding: 15px 25px;
            font-size: 12px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 1px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        /* Update main content margin */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand-wrapper">
            <h2>Admin Panel</h2>
        </div>
        
        <div class="profile-section">
            <div class="profile-image">
                <img src="https://ui-avatars.com/api/?name=Admin+User&background=random" alt="Admin">
            </div>
            <div class="profile-info">
                <h3>Admin User</h3>
                <p>Super Admin</p>
                <span class="status-badge">Online</span>
            </div>
        </div>

        <div class="menu-title">Main Navigation</div>
        <ul>
            <li class="active"><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>

            <li><a href="adminmedicineoperation.php"><i class="fas fa-box"></i>Medicine Management</a></li>
            <li><a href="#"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        </ul>

        <div class="menu-title">System</div>
        <ul>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="#"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
   
                    <div class="activity-action">
                        <button class="btn-view">View</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update current date
        function updateDate() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDate();
    </script>
</body>
</html>
