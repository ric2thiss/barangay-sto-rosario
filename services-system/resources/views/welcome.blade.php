<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Services System — Sto. Rosario</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/logos/logo_left.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #1f3a93; }
        body { background: #f4f6fb; font-family: 'Inter', sans-serif; }

        .hero-section {
            min-height: 100vh;
            min-height: 100svh;
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
        .circle-1 { width: 500px; height: 500px; top: -150px; right: -100px; }
        .circle-2 { width: 300px; height: 300px; bottom: -80px; left: -60px; }

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
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }
        .btn-hero:hover {
            background: #eef2ff;
            color: var(--brand);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0,0,0,.22);
        }

        .btn-hero-outline {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,.85);
            font-weight: 700;
            padding: .75rem 2rem;
            border-radius: 50px;
            font-size: .95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            transition: background .15s, color .15s;
        }
        .btn-hero-outline:hover {
            background: rgba(255,255,255,.12);
            color: #fff;
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

        .age-btn-primary {
            background: linear-gradient(135deg, #1f3a93, #2e4fc7);
            border: none;
            color: #fff;
        }
        .age-btn-primary:hover { opacity: .92; color: #fff; }
    </style>
</head>
<body>

<section class="hero-section">
    <div class="deco-circle circle-1"></div>
    <div class="deco-circle circle-2"></div>

    <div class="container py-5 hero-content">
        <a href="http://localhost/" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4 opacity-75 hover-opacity-100">
            <i class="bi bi-arrow-left-circle"></i> Back to Main Systems
        </a>
        <div class="row align-items-center g-5">

            <div class="col-lg-6 text-white">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo"
                         class="rounded-circle object-fit-contain"
                         style="width:50px;height:50px;background:rgba(255,255,255,.15);padding:5px;">
                    <div>
                        <div class="fw-bold text-white lh-1" style="font-size:.9rem;letter-spacing:.5px;">Sto. Rosario</div>
                        <div class="opacity-75 text-white" style="font-size:.7rem;">Barangay Services Management System</div>
                    </div>
                </div>

                <h1 class="fw-bold mb-3" style="font-size:clamp(2rem,4vw,3.5rem);line-height:1.1;">
                    Digital Services &amp;<br>
                    <span style="color:#93c5fd;">Certificates at Your Fingertips</span>
                </h1>

                <p class="opacity-85 mb-4" style="font-size:1.1rem;max-width:500px;line-height:1.7;">
                    Request barangay certificates and clearances, file blotter reports, and track your documents—built for transparent,
                    efficient barangay operations aligned with the wider MIS portal.
                </p>

                <div class="d-flex flex-wrap gap-3 align-items-center">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-hero">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-hero">
                            <i class="bi bi-box-arrow-in-right"></i> System Access
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-hero-outline">
                                <i class="bi bi-person-plus"></i> Register
                            </a>
                        @endif
                    @endauth
                </div>

                <div class="stats-strip mt-5 px-4 py-3 d-flex flex-wrap gap-4 justify-content-start">
                    <div class="text-center text-white">
                        <i class="bi bi-file-earmark-text fs-4"></i>
                        <div class="small opacity-80 mt-1">Certificates</div>
                    </div>
                    <div class="text-center text-white">
                        <i class="bi bi-journal-text fs-4"></i>
                        <div class="small opacity-80 mt-1">Blotter</div>
                    </div>
                    <div class="text-center text-white">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                        <div class="small opacity-80 mt-1">Analytics</div>
                    </div>
                    <div class="text-center text-white">
                        <i class="bi bi-shield-lock fs-4"></i>
                        <div class="small opacity-80 mt-1">Secure Roles</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="card feature-card shadow-sm h-100 p-4">
                            <div class="feature-icon mb-3" style="background:#e3f2fd;">
                                <i class="bi bi-patch-check" style="color:#1565c0;"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Certificates &amp; Clearances</h5>
                            <p class="small text-muted mb-0">Barangay clearance, residency, indigency, permits, and related documents in one workflow.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card feature-card shadow-sm h-100 p-4">
                            <div class="feature-icon mb-3" style="background:#fff3e0;">
                                <i class="bi bi-exclamation-octagon" style="color:#ef6c00;"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Blotter &amp; Incidents</h5>
                            <p class="small text-muted mb-0">File and track incident reports with structured records for barangay officials.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card feature-card shadow-sm h-100 p-4">
                            <div class="feature-icon mb-3" style="background:#e8f5e9;">
                                <i class="bi bi-people" style="color:#2e7d32;"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Residents &amp; Records</h5>
                            <p class="small text-muted mb-0">Accurate resident data supports faster approvals and better service delivery.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card feature-card shadow-sm h-100 p-4">
                            <div class="feature-icon mb-3" style="background:#f3e5f5;">
                                <i class="bi bi-bar-chart-line" style="color:#7b1fa2;"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Reports &amp; Analytics</h5>
                            <p class="small text-muted mb-0">Dashboards and exports help leadership monitor workloads and outcomes.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<footer class="text-center py-4 small text-muted bg-white border-top">
    &copy; {{ date('Y') }} Sto. Rosario Services System. All rights reserved.
</footer>

{{-- Age Gate Modal --}}
<div id="ageGateOverlay"
     class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center"
     style="display: none; background: rgba(0,0,0,0.55); z-index: 1050;">
    <div class="bg-white rounded-4 border p-4 p-md-5 mx-3 text-center shadow" style="max-width: 420px;">
        <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo"
             class="rounded-circle mx-auto mb-4 object-fit-contain"
             style="width: 80px; height: 80px;">
        <h2 class="h5 fw-semibold text-dark mb-2">Age verification required</h2>
        <p class="small text-muted mb-4 lh-lg">
            This site contains services intended for adults.<br>
            Please confirm your age to continue.
        </p>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
            <button type="button" onclick="ageVerified(true)"
                    class="btn age-btn-primary px-4 py-2 rounded-3 flex-grow-1">
                I am 18 or older
            </button>
            <button type="button" onclick="ageVerified(false)"
                    class="btn btn-light border px-4 py-2 rounded-3 flex-grow-1">
                I am under 18
            </button>
        </div>
    </div>
</div>

<div id="blockedScreen"
     class="position-fixed top-0 start-0 w-100 h-100 d-none flex-column align-items-center justify-content-center px-4 text-center bg-white"
     style="z-index: 1060;">
    <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo"
         class="rounded-circle mx-auto mb-4 object-fit-contain"
         style="width: 112px; height: 112px;">
    <h2 class="h4 fw-semibold text-dark mb-3">Adults only</h2>
    <p class="small text-muted mx-auto" style="max-width: 22rem;">
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
            document.getElementById('blockedScreen').classList.remove('d-none');
            document.getElementById('blockedScreen').classList.add('d-flex');
        }
    }

    if (!sessionStorage.getItem('ageVerified')) {
        var ov = document.getElementById('ageGateOverlay');
        ov.style.display = 'flex';
    }
</script>
</body>
</html>
