<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance System - Sto. Rosario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root { --brand: #1f3a93; }
    body  { background: #f4f6fb; font-family: 'Inter', sans-serif; }

    .hero-section {
      min-height: 100vh;
      background: linear-gradient(135deg, #1f3a93 0%, #2e4fc7 55%, #1a56db 100%);
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
    }
    .hero-section::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse at 20% 50%, rgba(255,255,255,.07) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(255,255,255,.05) 0%, transparent 50%);
      pointer-events: none;
    }
    .deco-circle {
      position: absolute;
      border-radius: 50%;
      background: rgba(255,255,255,.05);
    }
    .circle-1 { width:500px; height:500px; top:-150px; right:-100px; }
    .circle-2 { width:300px; height:300px; bottom:-80px; left:-60px; }

    .hero-content { position: relative; z-index: 2; }

    .btn-hero {
      background: #fff;
      color: var(--brand);
      border: none;
      font-weight: 700;
      padding: .8rem 2.2rem;
      border-radius: 50px;
      font-size: 1rem;
      transition: transform .15s, box-shadow .15s;
      box-shadow: 0 4px 18px rgba(0,0,0,.18);
      text-decoration: none;
    }
    .btn-hero:hover {
      background: #eef2ff;
      color: var(--brand);
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(0,0,0,.22);
    }

    .stats-strip {
      background: rgba(255,255,255,.13);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 16px;
    }

    .feature-card {
      border: none;
      border-radius: 16px;
      transition: transform .2s, box-shadow .2s;
    }
    .feature-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(31,58,147,.13) !important;
    }
    .feature-icon {
      width: 50px; height: 50px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.35rem;
      flex-shrink: 0;
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<section class="hero-section">
  <div class="deco-circle circle-1"></div>
  <div class="deco-circle circle-2"></div>

  <div class="container py-5 hero-content">
    <a href="../index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4 opacity-75 hover-opacity-100 transition-all">
      <i class="bi bi-arrow-left-circle"></i> Back to Main Systems
    </a>
    <div class="row align-items-center g-5">

      <div class="col-lg-6 text-white">
        <div class="d-flex align-items-center gap-3 mb-4">
          <img src="../assets/img/logo.png" alt="Logo"
               style="width:50px;height:50px;border-radius:50%;object-fit:contain;background:rgba(255,255,255,.15);padding:5px;">
          <div>
            <div class="fw-bold text-white lh-1" style="font-size:.9rem;letter-spacing:.5px;">Sto. Rosario</div>
            <div class="opacity-75 text-white" style="font-size:.7rem;">Attendance Management System</div>
          </div>
        </div>

        <h1 class="fw-bold mb-3" style="font-size:clamp(2rem,4vw,3.5rem);line-height:1.1;">
          Smart Attendance &amp;<br>
          <span style="color:#93c5fd;">Biometric Tracking</span>
        </h1>

        <p class="opacity-85 mb-4" style="font-size:1.1rem;max-width:500px;line-height:1.7;">
          Modernizing community monitoring with secure biometric verification, 
          real-time staff logs, and automated attendance reporting for all barangay activities.
        </p>

        <a href="auth/login.php" class="btn btn-hero d-inline-flex align-items-center gap-2">
          <i class="bi bi-box-arrow-in-right"></i> System Access
        </a>

        <div class="stats-strip mt-5 px-4 py-3 d-flex flex-wrap gap-4 justify-content-start">
          <div class="text-center text-white">
            <i class="bi bi-fingerprint fs-4"></i>
            <div class="small opacity-80 mt-1">Biometrics</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-clock-history fs-4"></i>
            <div class="small opacity-80 mt-1">Real-time Logs</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-people fs-4"></i>
            <div class="small opacity-80 mt-1">Staff Monitor</div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="row g-3">
          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#e1f5fe;">
                <i class="bi bi-fingerprint" style="color:#0288d1;"></i>
              </div>
              <h5 class="fw-bold mb-2">Biometric Registry</h5>
              <p class="small text-muted mb-0">Securely enroll and manage fingerprint data for residents and staff members.</p>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#e8f5e9;">
                <i class="bi bi-shield-check" style="color:#2e7d32;"></i>
              </div>
              <h5 class="fw-bold mb-2">Secure Verification</h5>
              <p class="small text-muted mb-0">High-accuracy identity verification to prevent fraud and unauthorized access.</p>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#fff3e0;">
                <i class="bi bi-calendar2-check" style="color:#ef6c00;"></i>
              </div>
              <h5 class="fw-bold mb-2">Daily Attendance</h5>
              <p class="small text-muted mb-0">Automatically track time-in and time-out with precision timestamps.</p>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#f3e5f5;">
                <i class="bi bi-bar-chart-steps" style="color:#7b1fa2;"></i>
              </div>
              <h5 class="fw-bold mb-2">Analytics Reports</h5>
              <p class="small text-muted mb-0">Generate comprehensive attendance summaries and behavioral patterns.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<footer class="text-center py-4 small text-muted bg-white border-top">
  &copy; <?php echo date('Y'); ?> Sto. Rosario Attendance System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
