<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Barangay PSS — Planning &amp; Scheduling System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="frontend/css/app.css" rel="stylesheet">
  <style>
    :root { --brand: #1f3a93; }
    body  { background: #f4f6fb; }

    .bpss-hero {
      min-height: 100vh;
      background: linear-gradient(135deg, #1f3a93 0%, #2e4fc7 55%, #1a56db 100%);
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
    }
    .bpss-hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse at 20% 50%, rgba(255,255,255,.07) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(255,255,255,.05) 0%, transparent 50%);
      pointer-events: none;
    }
    .bpss-circle {
      position: absolute;
      border-radius: 50%;
      background: rgba(255,255,255,.05);
    }
    .bpss-circle-1 { width:500px; height:500px; top:-150px; right:-100px; }
    .bpss-circle-2 { width:300px; height:300px; bottom:-80px; left:-60px; }
    .bpss-circle-3 { width:180px; height:180px; top:40%; right:15%; }

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
</head>
<body>

<section class="bpss-hero">
  <div class="bpss-circle bpss-circle-1"></div>
  <div class="bpss-circle bpss-circle-2"></div>
  <div class="bpss-circle bpss-circle-3"></div>

  <div class="container py-5 hero-content">
    <div class="row align-items-center g-5">

      <!-- LEFT: headline + CTA -->
      <div class="col-lg-6 text-white">

        <div class="d-flex align-items-center gap-3 mb-4">
          <img src="frontend/assets/images/Magallanes-Logo.jpg" alt="Municipality Logo"
               style="width:44px;height:44px;border-radius:50%;object-fit:cover;background:rgba(255,255,255,.15);padding:2px;">
          <img src="frontend/assets/images/seal-nobg.png" alt="Barangay Seal"
               style="width:44px;height:44px;border-radius:50%;object-fit:contain;background:rgba(255,255,255,.15);padding:2px;">
          <div>
            <div class="fw-bold text-white lh-1" style="font-size:.9rem;letter-spacing:.5px;">Barangay PSS</div>
            <div class="opacity-65 text-white" style="font-size:.7rem;">Planning &amp; Scheduling System</div>
          </div>
        </div>

        <h1 class="fw-bold mb-3" style="font-size:clamp(2rem,4vw,3rem);line-height:1.2;">
          Planning &amp; Scheduling<br>
          <span style="color:#93c5fd;">Made Simple</span>
        </h1>

        <p class="opacity-80 mb-4" style="font-size:1.05rem;max-width:480px;line-height:1.7;">
          Organize barangay events, manage agendas, send SMS notifications,
          generate reports, and keep meeting minutes — all in one place.
        </p>

        <a href="index.php?page=login" class="btn btn-hero d-inline-flex align-items-center gap-2">
          <i class="bi bi-box-arrow-in-right"></i> Get Started
        </a>

        <!-- Stats strip -->
        <div class="stats-strip mt-4 px-3 px-sm-4 py-3 d-flex flex-wrap gap-3 gap-sm-4 justify-content-center justify-content-sm-start">
          <div class="text-center text-white">
            <i class="bi bi-calendar-check fs-5"></i>
            <div class="small opacity-75 mt-1">Event Scheduling</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-chat-dots fs-5"></i>
            <div class="small opacity-75 mt-1">SMS Notifications</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-journal-text fs-5"></i>
            <div class="small opacity-75 mt-1">Meeting Minutes</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-bar-chart-line fs-5"></i>
            <div class="small opacity-75 mt-1">Reports</div>
          </div>
        </div>
      </div>

      <!-- RIGHT: feature cards -->
      <div class="col-lg-6">
        <div class="row g-3">

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-3">
              <div class="d-flex align-items-start gap-3">
                <div class="feature-icon" style="background:#e8f0fe;">
                  <i class="bi bi-calendar-check" style="color:#1f3a93;"></i>
                </div>
                <div>
                  <div class="fw-semibold mb-1">Schedule Events</div>
                  <div class="small text-muted">Create meetings, events, and appointments with full agenda management.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-3">
              <div class="d-flex align-items-start gap-3">
                <div class="feature-icon" style="background:#e6f9f0;">
                  <i class="bi bi-chat-dots" style="color:#198754;"></i>
                </div>
                <div>
                  <div class="fw-semibold mb-1">SMS Notify</div>
                  <div class="small text-muted">Auto-notify contacts via SMS before events start.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-3">
              <div class="d-flex align-items-start gap-3">
                <div class="feature-icon" style="background:#fff3e0;">
                  <i class="bi bi-file-earmark-text" style="color:#fd7e14;"></i>
                </div>
                <div>
                  <div class="fw-semibold mb-1">Meeting Minutes</div>
                  <div class="small text-muted">Upload, extract, and manage minutes of the meeting.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-3">
              <div class="d-flex align-items-start gap-3">
                <div class="feature-icon" style="background:#fce4ec;">
                  <i class="bi bi-bar-chart-line" style="color:#dc3545;"></i>
                </div>
                <div>
                  <div class="fw-semibold mb-1">Reports</div>
                  <div class="small text-muted">View agenda completion rates and event summaries.</div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<footer class="text-center py-3 small text-muted bg-white border-top">
  &copy; <?php echo date('Y'); ?> Barangay Planning &amp; Scheduling System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
