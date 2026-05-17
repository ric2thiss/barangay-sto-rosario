<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Management Information System</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <style>
        :root {
            --primary-color: #0b3d91;
            /* Deep government blue */
            --secondary-color: #fce300;
            /* Sun yellow */
            --accent-color: #c8102e;
            /* Red */
            --text-color: #333;
            --light-bg: #f4f6f9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
        }

        img,
        svg {
            max-width: 100%;
            height: auto;
        }

        /* Navigation */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand-logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-text h1 {
            font-size: 1.5rem;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .brand-text p {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: url('./assets/img/hero-section.jpg') no-repeat center center/cover;
            background-attachment: fixed;
            flex: 1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(11, 61, 145, 0.9) 0%, rgba(0, 0, 0, 0.7) 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
            max-width: 900px;
            padding: 0 20px;
            animation: fadeIn 1s ease-out forwards;
        }

        .gov-seal {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .hero-content h2 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .hero-content p {
            font-size: 1.25rem;
            margin-bottom: 40px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            font-weight: 300;
            color: rgba(255, 255, 255, 0.9);
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn {
            display: inline-block;
            padding: 16px 45px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(252, 227, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: all 0.5s ease;
        }

        .btn-primary:hover {
            background-color: #ffd700;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(252, 227, 0, 0.5);
        }

        .btn-primary:hover::after {
            left: 100%;
        }

        .cta-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid white;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background-color: white;
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
        }

        .btn svg {
            vertical-align: middle;
            margin-right: 8px;
            margin-top: -3px;
        }

        /* Footer */
        footer {
            background-color: var(--primary-color);
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
            padding: 25px;
            width: 100%;
            z-index: 10;
            font-size: 0.9rem;
            border-top: 3px solid var(--accent-color);
            margin-top: auto;
        }

        @keyframes fadeIn {
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
            .hero-content h2 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.1rem;
            }

            .brand-text h1 {
                font-size: 1.2rem;
            }

            .brand-logo {
                width: 40px;
                height: 40px;
            }
        }

        @media (max-width: 520px) {
            .navbar {
                flex-wrap: wrap;
                gap: 12px;
                padding: 0.85rem 5%;
            }

            .brand {
                gap: 10px;
                min-width: 0;
            }

            .brand-text h1 {
                font-size: 1.05rem;
                letter-spacing: 0.5px;
            }

            .brand-text p {
                font-size: 0.75rem;
                letter-spacing: 0.3px;
            }

            .hero {
                background-attachment: scroll;
                /* fixed backgrounds cause jank on mobile */
                height: auto;
                min-height: 100svh;
            }

            .hero-content {
                padding: 0 16px;
            }

            .hero-content h2 {
                font-size: 2.0rem;
            }

            .hero-content p {
                font-size: 1rem;
                margin-bottom: 26px;
            }

            .cta-group {
                width: 100%;
                gap: 12px;
            }

            .btn {
                width: 100%;
                max-width: 420px;
                padding: 14px 18px;
                font-size: 1rem;
                letter-spacing: 0.7px;
            }

            footer {
                padding: 18px 16px;
            }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transform: translateY(30px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0) scale(1);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #f1f5f9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
            transition: all 0.2s;
            z-index: 10;
        }

        .modal-close:hover {
            background: #e2e8f0;
            color: #0f172a;
            transform: rotate(90deg);
        }

        .modal-header {
            margin-bottom: 25px;
        }

        .modal-header h3 {
            color: var(--primary-color);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .modal-view {
            display: none;
            animation: slideIn 0.3s ease-out;
        }

        .modal-view.active {
            display: block;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .selection-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .type-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 25px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-color);
        }

        .type-btn:hover {
            border-color: var(--primary-color);
            background: #eff6ff;
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .type-btn svg {
            color: var(--primary-color);
            width: 40px;
            height: 40px;
        }

        .type-btn span {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .login-form {
            text-align: left;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }

        .modal-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .modal-input:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(11, 61, 145, 0.1);
        }

        .modal-btn {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .modal-btn:hover {
            background-color: #082d6b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 61, 145, 0.3);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        .modal-error {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-top: 15px;
            display: none;
            border: 1px solid #fee2e2;
        }
    </style>
    <script>
        // Custom JavaScript for index.php
        let currentLoginType = '';

        function checkSecurityKey(event) {
            event.preventDefault();
            const modal = document.getElementById('securityModal');
            resetModal();
            modal.classList.add('active');
            return false;
        }

        function resetModal() {
            document.getElementById('selectionView').classList.add('active');
            document.getElementById('loginView').classList.remove('active');
            document.getElementById('modalError').style.display = 'none';
            document.getElementById('usernameInput').value = '';
            document.getElementById('passwordInput').value = '';
            currentLoginType = '';
        }

        function closeSecurityModal() {
            document.getElementById('securityModal').classList.remove('active');
        }

        function selectLoginType(type) {
            currentLoginType = type;
            document.getElementById('selectionView').classList.remove('active');
            document.getElementById('loginView').classList.add('active');
            document.getElementById('loginTypeTitle').textContent = type === 'admin' ? 'Admin Login' : 'Purok President Login';
            setTimeout(() => document.getElementById('usernameInput').focus(), 100);
        }

        function showSelection() {
            document.getElementById('loginView').classList.remove('active');
            document.getElementById('selectionView').classList.add('active');
            document.getElementById('modalError').style.display = 'none';
        }

        async function submitLogin() {
            const username = document.getElementById('usernameInput').value;
            const password = document.getElementById('passwordInput').value;
            const error = document.getElementById('modalError');
            const btn = document.getElementById('submitLoginBtn');

            if (!username || !password) {
                error.textContent = 'Please enter both username and password.';
                error.style.display = 'block';
                return;
            }

            btn.textContent = 'Authenticating...';
            btn.disabled = true;

            try {
                const response = await fetch('verify_key.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: currentLoginType,
                        username: username,
                        password: password
                    })
                });
                const result = await response.json();

                if (result.success) {
                    window.location.href = 'systems.php';
                } else {
                    error.textContent = result.message || 'Invalid credentials.';
                    error.style.display = 'block';
                    btn.textContent = 'Login to System';
                    btn.disabled = false;
                }
            } catch (e) {
                error.textContent = 'Error connecting to server. Try again later.';
                error.style.display = 'block';
                btn.textContent = 'Login to System';
                btn.disabled = false;
            }
        }
    </script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
</head>

<body>

    <header>
        <div class="navbar">
            <div class="brand">
                <div class="brand-logo">
                    <img src="assets/img/logo.png" alt="Logo">
                </div>
                <div class="brand-text">
                    <h1>Sto. Rosario</h1>
                    <p>Barangay Management Information System</p>
                </div>
            </div>
            <nav>
                <a href="index.php"
                    style="color: var(--primary-color); text-decoration: none; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;">Portal
                    Home</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="gov-seal">
                <!-- Decorative element for government feel -->
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--secondary-color)"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.9;">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>
            </div>
            <h2>Digital Governance at Your Fingertips</h2>
            <p>Empowering our community through a unified, efficient, and transparent information management system.
                Access essential barangay services securely.</p>
            <div class="cta-group">
                <a href="user-portal.php" class="btn btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    Resident Portal
                </a>
                <a href="systems.php" class="btn btn-primary" onclick="return checkSecurityKey(event)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    <span style="text-transform: lowercase;">e</span>-Barangay System
                </a>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Barangay Management Information System. All Rights Reserved. Sto. Rosario.
        </p>
    </footer>

    <!-- Security Key Modal -->
    <div class="modal-overlay" id="securityModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeSecurityModal()">&times;</button>

            <!-- Step 1: Selection -->
            <div id="selectionView" class="modal-view active">
                <div class="modal-header">
                    <h3>Select Login Type</h3>
                    <p style="color: #64748b; margin-top: 8px;">Choose your role to access the e-Barangay System</p>
                </div>
                <div class="selection-grid">
                    <div class="type-btn" onclick="selectLoginType('admin')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            <path d="M12 8v4" />
                            <path d="M12 16h.01" />
                        </svg>
                        <span>Admin</span>
                    </div>
                    <div class="type-btn" onclick="selectLoginType('purok_president')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        <span>Purok President</span>
                    </div>
                </div>
            </div>

            <!-- Step 2: Login Form -->
            <div id="loginView" class="modal-view">
                <div class="modal-header">
                    <h3 id="loginTypeTitle">Login</h3>
                </div>
                <div class="login-form">
                    <div class="form-group">
                        <label for="usernameInput">Username</label>
                        <input type="text" id="usernameInput" class="modal-input" placeholder="Enter Username">
                    </div>
                    <div class="form-group">
                        <label for="passwordInput">Password</label>
                        <input type="password" id="passwordInput" class="modal-input" placeholder="Enter Password"
                            onkeyup="if(event.key === 'Enter') submitLogin()">
                    </div>
                    <button class="modal-btn" onclick="submitLogin()" id="submitLoginBtn">Login to System</button>
                    <div style="text-align: center;">
                        <span class="back-link" onclick="showSelection()">← Back to selection</span>
                    </div>
                </div>
                <div class="modal-error" id="modalError">Invalid credentials.</div>
            </div>
        </div>
    </div>

</body>

</html>