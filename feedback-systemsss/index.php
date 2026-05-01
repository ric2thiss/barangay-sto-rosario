<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check maintenance mode
checkMaintenanceMode();
// Redirect based on user type or show landing page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: user/index.php');
    }
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Feedback System - Sto. Rosario</title>
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
    .circle-1 { width:500px; height:500px; top:-100px; right:-100px; }
    .circle-2 { width:400px; height:400px; bottom:-150px; left:-100px; }

    .hero-content { position: relative; z-index: 2; }

    .btn-hero {
      background: #fff;
      color: var(--brand);
      border: none;
      font-weight: 700;
      padding: .8rem 2.2rem;
      border-radius: 50px;
      font-size: 1rem;
      transition: all .2s;
      box-shadow: 0 4px 18px rgba(0,0,0,.18);
      text-decoration: none;
    }
    .btn-hero:hover {
      background: #f3e5f5;
      color: var(--brand);
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(0,0,0,.22);
    }

    .stats-strip {
      background: rgba(255,255,255,.15);
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
          <img src="img/logo.png" alt="Logo"
               style="width:50px;height:50px;border-radius:50%;object-fit:contain;background:rgba(255,255,255,.15);padding:5px;">
          <div>
            <div class="fw-bold text-white lh-1" style="font-size:.9rem;letter-spacing:.5px;">Sto. Rosario</div>
            <div class="opacity-75 text-white" style="font-size:.7rem;">Feedback Management System</div>
          </div>
        </div>

        <h1 class="fw-bold mb-3" style="font-size:clamp(2.5rem,4vw,3.5rem);line-height:1.2;">
          Your Voice Matters,<br>
          <span style="color:#93c5fd;">We Listen.</span>
        </h1>

        <p class="opacity-85 mb-4" style="font-size:1.1rem;max-width:500px;line-height:1.7;">
          Help us build a better community. Submit your feedback, suggestions, 
          and grievances securely through our integrated management portal.
        </p>

        <div class="d-flex flex-wrap gap-3">
          <a href="user/login.php" class="btn btn-hero d-inline-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i> User Login
          </a>
          <a href="admin/login.php" class="btn btn-hero d-inline-flex align-items-center gap-2" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3); color: #fff;">
            <i class="bi bi-shield-lock"></i> Admin
          </a>
        </div>

        <div class="stats-strip mt-5 px-4 py-3 d-flex flex-wrap gap-4 justify-content-start">
          <div class="text-center text-white">
            <i class="bi bi-chat-square-heart fs-4"></i>
            <div class="small opacity-80 mt-1">Easy Submission</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-activity fs-4"></i>
            <div class="small opacity-80 mt-1">Real-time Tracking</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-award fs-4"></i>
            <div class="small opacity-80 mt-1">Direct Impact</div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="row g-3">
          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#f3e5f5;">
                <i class="bi bi-send-plus" style="color:#6a1b9a;"></i>
              </div>
              <h5 class="fw-bold mb-2">Submit Feedback</h5>
              <p class="small text-muted mb-0">Quickly submit your suggestions or report issues with our easy-to-use forms.</p>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#e3f2fd;">
                <i class="bi bi-search" style="color:#0288d1;"></i>
              </div>
              <h5 class="fw-bold mb-2">Track Progress</h5>
              <p class="small text-muted mb-0">Stay informed with real-time status updates on your submitted feedback.</p>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#e8f5e9;">
                <i class="bi bi-graph-up" style="color:#2e7d32;"></i>
              </div>
              <h5 class="fw-bold mb-2">Data Analytics</h5>
              <p class="small text-muted mb-0">Admins can visualize community sentiment and identify key areas for improvement.</p>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#fff3e0;">
                <i class="bi bi-megaphone" style="color:#ef6c00;"></i>
              </div>
              <h5 class="fw-bold mb-2">Announcements</h5>
              <p class="small text-muted mb-0">Receive direct updates and responses from the barangay administration.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<footer class="text-center py-4 small text-muted bg-white border-top">
  &copy; <?php echo date('Y'); ?> Sto. Rosario Feedback System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>