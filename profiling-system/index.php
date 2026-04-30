<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profiling System - Sto. Rosario</title>
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
    .circle-1 { width:550px; height:550px; top:-100px; left:-150px; }
    .circle-2 { width:250px; height:250px; bottom:10%; right:5%; }

    .hero-content { position: relative; z-index: 2; }

    .btn-hero {
      background: #fff;
      color: var(--brand);
      border: none;
      font-weight: 700;
      padding: .9rem 2.5rem;
      border-radius: 50px;
      font-size: 1.05rem;
      transition: all .2s;
      box-shadow: 0 4px 18px rgba(0,0,0,.15);
      text-decoration: none;
    }
    .btn-hero:hover {
      background: #f8fafc;
      color: var(--brand);
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(0,0,0,.2);
    }

    .stats-strip {
      background: rgba(255,255,255,.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,.15);
      border-radius: 20px;
    }

    .feature-card {
      border: none;
      border-radius: 20px;
      transition: all .3s cubic-bezier(0.4, 0, 0.2, 1);
      background: #fff;
    }
    .feature-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 15px 35px rgba(31,58,147,.13) !important;
    }
    .feature-icon {
      width: 55px; height: 55px;
      border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem;
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
    <div class="row align-items-center g-5">

      <div class="col-lg-6 text-white">
        <div class="d-flex align-items-center gap-3 mb-4">
          <img src="../assets/img/logo.png" alt="Logo"
               style="width:50px;height:50px;border-radius:50%;object-fit:contain;background:rgba(255,255,255,.15);padding:5px;">
          <div>
            <div class="fw-bold text-white lh-1" style="font-size:.9rem;letter-spacing:.5px;">Sto. Rosario</div>
            <div class="opacity-75 text-white" style="font-size:.7rem;">Resident Profiling System</div>
          </div>
        </div>

        <h1 class="fw-bold mb-3" style="font-size:clamp(2.5rem,5vw,3.8rem);line-height:1.1;">
          Data-Driven<br>
          <span style="color:#93c5fd;">Community Profiling</span>
        </h1>

        <p class="opacity-85 mb-4" style="font-size:1.15rem;max-width:520px;line-height:1.7;">
          Building a comprehensive digital census. Manage resident records, 
          household mapping, and demographic insights with our advanced profiling engine.
        </p>

        <a href="resident/index.php" class="btn btn-hero d-inline-flex align-items-center gap-2">
          <i class="bi bi-shield-lock"></i> Secure Portal
        </a>

        <div class="stats-strip mt-5 px-4 py-4 d-flex flex-wrap gap-5 justify-content-start">
          <div class="text-center text-white">
            <div class="fs-3 fw-bold">100%</div>
            <div class="small opacity-75">Digital Records</div>
          </div>
          <div class="text-center text-white">
            <div class="fs-3 fw-bold">Real-time</div>
            <div class="small opacity-75">Updates</div>
          </div>
          <div class="text-center text-white">
            <div class="fs-3 fw-bold">Secure</div>
            <div class="small opacity-75">Data Encryption</div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="row g-4">
          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#e3f2fd;">
                <i class="bi bi-person-vcard" style="color:#0b3d91;"></i>
              </div>
              <h5 class="fw-bold mb-2">Resident Records</h5>
              <p class="small text-muted mb-0">Centralized database for all resident personal information and legal status.</p>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#f3e5f5;">
                <i class="bi bi-house-door" style="color:#7b1fa2;"></i>
              </div>
              <h5 class="fw-bold mb-2">Household Mapping</h5>
              <p class="small text-muted mb-0">Organize data by household to track family structures and living conditions.</p>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#e8f5e9;">
                <i class="bi bi-graph-up-arrow" style="color:#2e7d32;"></i>
              </div>
              <h5 class="fw-bold mb-2">Demographics</h5>
              <p class="small text-muted mb-0">Instant analytics on age distribution, employment, and community health.</p>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-4">
              <div class="feature-icon mb-3" style="background:#fff3e0;">
                <i class="bi bi-file-earmark-lock" style="color:#ef6c00;"></i>
              </div>
              <h5 class="fw-bold mb-2">Privacy Control</h5>
              <p class="small text-muted mb-0">Strict access controls to ensure resident data is handled with confidentiality.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<footer class="text-center py-4 small text-muted bg-white border-top">
  &copy; <?php echo date('Y'); ?> Sto. Rosario Profiling System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
