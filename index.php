<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Management Information System</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <script src="assets/js/index.js" defer></script>
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
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Resident Portal
                </a>
                <a href="systems.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Government Systems
                </a>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Barangay Management Information System. All Rights Reserved. Sto. Rosario.
        </p>
    </footer>

</body>

</html>