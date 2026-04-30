<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Services System - Sto. Rosario</title>
  <link rel="icon" type="image/jpeg" href="{{ asset('storage/logos/logo_left.jpg') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root { --brand: #1f3a93; }
    body  { background: #f4f6fb; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }

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
</head>
<body>

<section class="bpss-hero">
  <div class="bpss-circle bpss-circle-1"></div>
  <div class="bpss-circle bpss-circle-2"></div>
  <div class="bpss-circle bpss-circle-3"></div>

  <div class="container py-5 hero-content">
    <!-- Optional Global Home Navigation -->
    <div class="mb-4">
        <a href="http://localhost/index.php" class="btn btn-outline-light btn-sm rounded-pill" style="backdrop-filter: blur(5px); background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3);">
            <i class="bi bi-house-door-fill me-1"></i> Back to Home
        </a>
    </div>

    <div class="row align-items-center g-5">

      <!-- LEFT: headline + CTA -->
      <div class="col-lg-6 text-white">

        <div class="d-flex align-items-center gap-3 mb-4">
          <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Municipality Logo"
               style="width:44px;height:44px;border-radius:50%;object-fit:cover;background:rgba(255,255,255,.15);padding:2px;">
          <img src="{{ asset('storage/logos/LOGO_right.png') }}" alt="Barangay Seal"
               style="width:44px;height:44px;border-radius:50%;object-fit:contain;background:rgba(255,255,255,.15);padding:2px;">
          <div>
            <div class="fw-bold text-white lh-1" style="font-size:.9rem;letter-spacing:.5px;">Services System</div>
            <div class="opacity-65 text-white" style="font-size:.7rem;">Barangay Sto. Rosario</div>
          </div>
        </div>

        <h1 class="fw-bold mb-3" style="font-size:clamp(2rem,4vw,3rem);line-height:1.2;">
          Barangay Services<br>
          <span style="color:#93c5fd;">Management System</span>
        </h1>

        <p class="opacity-80 mb-4" style="font-size:1.05rem;max-width:480px;line-height:1.7;">
          Manage certificates, resident records, incident reports, and access barangay statistics all in one place.
        </p>

        @auth
            <a href="{{ url('/dashboard') }}" class="btn btn-hero d-inline-flex align-items-center gap-2">
              <i class="bi bi-speedometer2"></i> Go to Dashboard
            </a>
        @else
            <a href="{{ route('login') }}" class="btn btn-hero d-inline-flex align-items-center gap-2">
              <i class="bi bi-box-arrow-in-right"></i> Log In
            </a>
        @endauth

        <!-- Stats strip -->
        <div class="stats-strip mt-4 px-3 px-sm-4 py-3 d-flex flex-wrap gap-3 gap-sm-4 justify-content-center justify-content-sm-start">
          <div class="text-center text-white">
            <i class="bi bi-file-earmark-text fs-5"></i>
            <div class="small opacity-75 mt-1">Certificates</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-shield-exclamation fs-5"></i>
            <div class="small opacity-75 mt-1">Blotter</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-bar-chart-line fs-5"></i>
            <div class="small opacity-75 mt-1">Analytics</div>
          </div>
          <div class="text-center text-white">
            <i class="bi bi-person-badge fs-5"></i>
            <div class="small opacity-75 mt-1">User Roles</div>
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
                  <i class="bi bi-file-earmark-text" style="color:#1f3a93;"></i>
                </div>
                <div>
                  <div class="fw-semibold mb-1">Certificates</div>
                  <div class="small text-muted">Clearance, Residency, Indigency & more</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-3">
              <div class="d-flex align-items-start gap-3">
                <div class="feature-icon" style="background:#fff3e0;">
                  <i class="bi bi-shield-exclamation" style="color:#fd7e14;"></i>
                </div>
                <div>
                  <div class="fw-semibold mb-1">Blotter</div>
                  <div class="small text-muted">File & track incident reports</div>
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
                  <div class="fw-semibold mb-1">Analytics</div>
                  <div class="small text-muted">Reports & barangay statistics</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="card feature-card shadow-sm h-100 p-3">
              <div class="d-flex align-items-start gap-3">
                <div class="feature-icon" style="background:#e6f9f0;">
                  <i class="bi bi-person-badge" style="color:#198754;"></i>
                </div>
                <div>
                  <div class="fw-semibold mb-1">User Roles</div>
                  <div class="small text-muted">Admin & staff access management</div>
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
  &copy; {{ date('Y') }} Barangay Services System. All rights reserved.
</footer>

{{-- Age Gate Modal --}}
<div id="ageGateOverlay" style="display:none; position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,0.55); align-items:center; justify-content:center;">
    <div class="bg-white rounded p-4 text-center mx-3" style="max-width: 400px; width: 100%;">
        <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo" class="rounded-circle mb-3 border" style="width:80px;height:80px;object-fit:contain;padding:4px;">
        <h4 class="mb-2">Age verification required</h4>
        <p class="text-muted small mb-4">
            This site contains services intended for adults.<br>
            Please confirm your age to continue.
        </p>
        <div class="d-flex justify-content-center gap-2">
            <button onclick="ageVerified(true)" class="btn btn-primary px-4" style="background-color: var(--brand); border-color: var(--brand);">I am 18 or older</button>
            <button onclick="ageVerified(false)" class="btn btn-light px-4 border">I am under 18</button>
        </div>
    </div>
</div>

{{-- Blocked Screen --}}
<div id="blockedScreen" style="display:none; position:fixed; inset:0; z-index:1060; background:white; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
    <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo" class="rounded-circle mb-4 border" style="width:112px;height:112px;object-fit:contain;padding:4px;">
    <h2 class="mb-3">Adults only</h2>
    <p class="text-muted" style="max-width:400px; margin:0 auto;">
        This site is intended for users <strong>18 years of age or older</strong>.
        You are not permitted to access this content.
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function ageVerified(isAdult) {
        document.getElementById('ageGateOverlay').style.display = 'none';
        if (isAdult) {
            sessionStorage.setItem('ageVerified', '1');
        } else {
            document.getElementById('blockedScreen').style.display = 'flex';
        }
    }

    if (!sessionStorage.getItem('ageVerified')) {
        document.getElementById('ageGateOverlay').style.display = 'flex';
    } else {
        document.getElementById('ageGateOverlay').style.display = 'none';
    }
</script>
</body>
</html>