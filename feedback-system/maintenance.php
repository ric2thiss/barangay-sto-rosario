<?php
session_start();
require_once 'config/config.php';

// Get maintenance mode status
$sql = "SELECT value FROM settings WHERE name = 'maintenance_mode'";
$result = $conn->query($sql);
$maintenance_mode = '0';

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $maintenance_mode = $row['value'];
}

// If maintenance mode is off, redirect to home
if ($maintenance_mode == '0') {
    header('Location: index.php');
    exit();
}

// Allow administrators to bypass maintenance mode
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin/index.php');
    exit();
}

// Get admin email for contact
$admin_email = 'admin@example.com'; // Default
$email_sql = "SELECT value FROM settings WHERE name = 'admin_email'";
$email_result = $conn->query($email_sql);
if ($email_result && $email_result->num_rows > 0) {
    $email_row = $email_result->fetch_assoc();
    $admin_email = $email_row['value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - Resident Feedback and Survey System</title>
    <link rel="icon" href="img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #2c3e50 0%, #4a235a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .maintenance-container {
            width: 100%;
            max-width: 600px;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .maintenance-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .maintenance-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
        }
        
        .maintenance-icon {
            font-size: 5rem;
            color: #1F3A93;
            margin-bottom: 20px;
            background: #f0f7ff;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 2px solid #bae6fd;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .status-badge {
            display: inline-block;
            background: #1F3A93;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(31, 58, 147, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(31, 58, 147, 0); }
            100% { box-shadow: 0 0 0 0 rgba(31, 58, 147, 0); }
        }
        
        h1 {
            color: #1a317d;
            margin-bottom: 15px;
            font-size: 2.2rem;
            font-weight: 700;
        }
        
        .subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .info-box h3 {
            color: #1a317d;
            margin-bottom: 10px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box p {
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .admin-section {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
        }
        
        .admin-section h3 {
            color: #92400e;
            margin-bottom: 10px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-section p {
            color: #92400e;
            font-size: 0.95rem;
            margin-bottom: 15px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            background: linear-gradient(90deg, #1F3A93, #3a56b5);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: linear-gradient(90deg, #1a317d, #1F3A93);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(31, 58, 147, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .contact-info {
            margin-top: 20px;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .contact-info a {
            color: #1F3A93;
            text-decoration: none;
            font-weight: 500;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }
        
        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .maintenance-box {
                padding: 30px 20px;
            }
            
            .maintenance-icon {
                width: 90px;
                height: 90px;
                font-size: 3.5rem;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .maintenance-icon {
                width: 80px;
                height: 80px;
                font-size: 3rem;
            }
            
            h1 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-box">
            <div class="maintenance-icon">
                <i class="fas fa-tools"></i>
            </div>
            
            <div class="status-badge">
                <i class="fas fa-cog fa-spin"></i> MAINTENANCE MODE
            </div>
            
            <h1>System Under Maintenance</h1>
            <p class="subtitle">
                We're currently performing scheduled maintenance to improve your experience. 
                The system will be back online shortly. Thank you for your patience.
            </p>
            
            <div class="info-box">
                <h3><i class="fas fa-calendar-alt"></i> Maintenance Information</h3>
                <p>
                    <strong>Date:</strong> <?php echo date('F j, Y'); ?><br>
                    <strong>Status:</strong> System maintenance in progress
                </p>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-info-circle"></i> What's Happening?</h3>
                <p>
                    We're currently upgrading our systems to provide you with a better experience. 
                    During this time, the feedback system will be temporarily unavailable.
                </p>
            </div>
            
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
            <div class="admin-section">
                <h3><i class="fas fa-user-shield"></i> Administrator Access</h3>
                <p>You are logged in as an administrator. You can continue to access the admin panel.</p>
                <a href="admin/index.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Go to Admin Panel
                </a>
            </div>
            <?php endif; ?>
            
            <div class="contact-info">
                <p>Need immediate assistance? Contact us at: 
                    <a href="mailto:<?php echo htmlspecialchars($admin_email); ?>">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin_email); ?>
                    </a>
                </p>
            </div>
            

        </div>
    </div>
    
    <script>
        // Check if maintenance is over - Removed visual check
        // Auto-check status every 5 minutes
        setInterval(() => {
            fetch('check_maintenance.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.maintenance) {
                        window.location.href = 'index.php';
                    }
                })
                .catch(e => console.error(e));
        }, 5 * 60 * 1000);
        
        // Try to go back to previous page when back button is pressed
        window.onpopstate = function(event) {
            // Allow navigation back
            window.history.forward();
        };
        
        // Prevent being trapped in maintenance page
        document.addEventListener('keydown', function(e) {
            // Allow F5 refresh
            if (e.key === 'F5') {
                window.location.reload();
            }
            // Allow browser back navigation with Alt+Left Arrow
            if (e.altKey && e.key === 'ArrowLeft') {
                window.history.back();
            }
        });
        
        // Add typing animation effect
        const title = document.querySelector('h1');
        const subtitle = document.querySelector('.subtitle');
        
        title.style.animation = 'fadeInUp 0.8s ease-out';
        subtitle.style.animation = 'fadeInUp 1s ease-out';
    </script>
</body>
</html>