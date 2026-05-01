<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Portal - Barangay MIS</title>
    <link rel="stylesheet" href="assets/css/user-portal.css">
    <script src="assets/js/user-portal.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
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
                    <p>Resident Portal</p>
                </div>
            </div>
            <a href="index.php" class="back-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                Back to Home
            </a>
        </div>
    </header>

    <main>
        <div class="page-header">
            <h2>Resident Services</h2>
            <p>Welcome! Select a service below to access barangay modules available to residents.</p>
        </div>

        <div class="systems-grid">
            <!-- Feedback System -->
            <a href="feedback-system/user/login.php" class="system-card" style="border-top-color: #6a1b9a;">
                <div class="card-body">
                    <div class="icon-wrapper" style="color: #6a1b9a; background-color: rgba(106, 27, 154, 0.1);">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path
                                d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 12h-2v-2h2v2zm0-4h-2V6h2v4z" />
                        </svg>
                    </div>
                    <h3 style="color: #6a1b9a;">Citizen Feedback & Grievance</h3>
                    <p>Submit feedback, file complaints, participate in surveys, and track the status of your submitted
                        concerns.</p>
                    <div class="card-footer" style="color: #6a1b9a;">Login to Module <svg width="18" height="18"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg></div>
                </div>
            </a>

            <!-- Treasury System -->
            <a href="treasury-system/resident/login.php" class="system-card" style="border-top-color: #c62828;">
                <div class="card-body">
                    <div class="icon-wrapper" style="color: #c62828; background-color: rgba(198, 40, 40, 0.1);">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path
                                d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                        </svg>
                    </div>
                    <h3 style="color: #c62828;">Revenue & Payments</h3>
                    <p>Request cedula, manage payments, view payment history, and handle garbage and rental transactions
                        online.</p>
                    <div class="card-footer" style="color: #c62828;">Login to Module <svg width="18" height="18"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg></div>
                </div>
            </a>

            <!-- Scheduling System -->
            <a href="scheduling-system/frontend/pages/public/login.php" class="system-card"
                style="border-top-color: #2e7d32;">
                <div class="card-body">
                    <div class="icon-wrapper" style="color: #2e7d32; background-color: rgba(46, 125, 50, 0.1);">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path
                                d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" />
                        </svg>
                    </div>
                    <h3 style="color: #2e7d32;">Event & Facility Scheduling</h3>
                    <p>View upcoming barangay events, book facility reservations, and stay updated on community
                        activities and meetings.</p>
                    <div class="card-footer" style="color: #2e7d32;">Login to Module <svg width="18" height="18"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg></div>
                </div>
            </a>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Barangay Management Information System. All Rights Reserved. Sto. Rosario.
        </p>
    </footer>

</body>

</html>