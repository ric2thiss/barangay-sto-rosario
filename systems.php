<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Systems & Modules - Barangay MIS</title>
    <link rel="stylesheet" href="assets/css/systems.css">
    <script src="assets/js/systems.js" defer></script>
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
                    <p>Integrated Modules</p>
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
            <h2>System Modules</h2>
            <p>Select a module below to access specific administrative functions and services within the Barangay
                Management Information System.</p>
        </div>

        <div class="systems-grid">
            <!-- Profiling System -->
            <a href="profiling-system/" class="system-card" style="border-top-color: #0b3d91;">
                <div class="card-body">
                    <div class="icon-wrapper" style="color: #0b3d91; background-color: rgba(11, 61, 145, 0.1);">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path
                                d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                        </svg>
                    </div>
                    <h3 style="color: #0b3d91;">Profiling System</h3>
                    <p>Manage and maintain comprehensive records of barangay residents, households, and demographics
                        efficiently.</p>
                    <div class="card-footer" style="color: #0b3d91;">Enter Module <svg width="18" height="18"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg></div>
                </div>
            </a>

            <!-- Services System -->
            <a href="http://127.0.0.1:8000/" class="system-card" style="border-top-color: #f57c00;">
                <div class="card-body">
                    <div class="icon-wrapper" style="color: #f57c00; background-color: rgba(245, 124, 0, 0.1);">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path
                                d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                        </svg>
                    </div>
                    <h3 style="color: #f57c00;">Services System</h3>
                    <p>Process document requests such as barangay clearances, certificates, permits, and other
                        constituent forms.</p>
                    <div class="card-footer" style="color: #f57c00;">Enter Module <svg width="18" height="18"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg></div>
                </div>
            </a>

            <!-- Scheduling System -->
            <a href="scheduling-system/" class="system-card" style="border-top-color: #2e7d32;">
                <div class="card-body">
                    <div class="icon-wrapper" style="color: #2e7d32; background-color: rgba(46, 125, 50, 0.1);">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path
                                d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" />
                        </svg>
                    </div>
                    <h3 style="color: #2e7d32;">Scheduling System</h3>
                    <p>Organize barangay events, manage facility reservations, schedule meetings, and coordinate local
                        activities.</p>
                    <div class="card-footer" style="color: #2e7d32;">Enter Module <svg width="18" height="18"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg></div>
                </div>
            </a>

            <!-- Attendance System -->
            <a href="attendance-system/" class="system-card" style="border-top-color: #0288d1;">
                <div class="card-body">
                    <div class="icon-wrapper" style="color: #0288d1; background-color: rgba(2, 136, 209, 0.1);">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path
                                d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                        </svg>
                    </div>
                    <h3 style="color: #0288d1;">Attendance System</h3>
                    <p>Monitor and track attendance logs for barangay officials, staff, patrols, and community assembly
                        meetings.</p>
                    <div class="card-footer" style="color: #0288d1;">Enter Module <svg width="18" height="18"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg></div>
                </div>
            </a>

            <!-- Treasury System -->
            <a href="treasury-system/" class="system-card" style="border-top-color: #c62828;">
                <div class="card-body">
                    <div class="icon-wrapper" style="color: #c62828; background-color: rgba(198, 40, 40, 0.1);">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path
                                d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                        </svg>
                    </div>
                    <h3 style="color: #c62828;">Treasury System</h3>
                    <p>Handle financial transactions, manage collection receipts, track budget allocations and monitor
                        expenses.</p>
                    <div class="card-footer" style="color: #c62828;">Enter Module <svg width="18" height="18"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg></div>
                </div>
            </a>

            <!-- Feedback System -->
            <a href="feedback-system/" class="system-card" style="border-top-color: #6a1b9a;">
                <div class="card-body">
                    <div class="icon-wrapper" style="color: #6a1b9a; background-color: rgba(106, 27, 154, 0.1);">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path
                                d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 12h-2v-2h2v2zm0-4h-2V6h2v4z" />
                        </svg>
                    </div>
                    <h3 style="color: #6a1b9a;">Feedback System</h3>
                    <p>Receive, process, and act on constituent feedback, grievances, complaints, and suggestions
                        effectively.</p>
                    <div class="card-footer" style="color: #6a1b9a;">Enter Module <svg width="18" height="18"
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