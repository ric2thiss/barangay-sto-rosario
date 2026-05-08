<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
    <title>ACCOUNT PENDING - STEAM Vladimir Lahora</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #1b2838;
            color: #c7d5e0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Header */
        header {
            background-color: #171a21;
            border-bottom: 1px solid #1b2838;
            padding: 15px 40px;
            width: 100%;
        }

        /* Main content area */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            padding: 60px;
            text-align: center;
            background: #171a21;
            border: 1px solid #3d4450;
            border-radius: 4px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .container h2 {
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .status-icon {
            font-size: 64px;
            color: #66c0f4;
            margin-bottom: 30px;
        }

        p {
            color: #8f98a0;
            margin-bottom: 40px;
            line-height: 1.6;
            font-size: 16px;
        }

        .btn {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(to right, #47bfff 5%, #1a44c2 60%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 2px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: filter 0.2s;
        }

        .btn:hover {
            filter: brightness(1.1);
        }

        /* Footer */
        footer {
            background-color: #171a21;
            padding: 40px 20px;
            text-align: center;
            margin-top: auto;
        }

        footer img {
            height: 25px;
            opacity: 0.6;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <header>
        <div style="max-width: 1400px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" style="height: 30px;">
                <h1 style="font-size: 18px; font-weight: 800; color: #fff; margin: 0; text-transform: uppercase; letter-spacing: 2px;">STEAM PORTAL</h1>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div class="container">
            <div class="status-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            <h2>ACCOUNT PENDING</h2>
            <p>Thank you for registering! Your account is currently pending approval by an administrator. You will be able to access the system once your request has been reviewed.</p>
            <a href="login.php" class="btn">RETURN TO LOGIN</a>
        </div>
    </div>

    <footer>
        <div style="max-width: 1200px; margin: 0 auto;">
            <img src="../IMAGE/footerLogo_valve_new.png" alt="Valve Logo">
            <p style="font-size: 12px; color: #8f98a0; margin: 0; line-height: 1.6;">
                &copy; 2026 STEAM Vladimir Lahora. All rights reserved. <br>
                All trademarks are property of their respective owners in the US and other countries.
            </p>
        </div>
    </footer>
</body>

</html>