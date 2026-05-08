<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    @include('partials.head-auth')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand-primary: #1f3a93; --brand-secondary: #2e4fc7; }
        body { background: #f4f6fb; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }

        /* ── Split-screen wrapper ─────────────────────────────── */
        .login-wrap { min-height: 100vh; display: flex; }

        /* ── Left brand panel ────────────────────────────────── */
        .login-brand {
            flex: 0 0 42%;
            background: linear-gradient(155deg, var(--brand-primary) 0%, var(--brand-secondary) 55%, #1a56db 100%);
            display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
            padding: 3rem 3.5rem; position: relative; overflow: hidden;
        }
        .login-brand::before {
            content: ""; position: absolute; inset: 0;
            background: radial-gradient(ellipse at 10% 80%, rgba(255,255,255,.08) 0%, transparent 55%),
                        radial-gradient(ellipse at 90% 10%, rgba(255,255,255,.05) 0%, transparent 45%);
            pointer-events: none;
        }
        .login-brand-circle { position: absolute; border-radius: 50%; background: rgba(255,255,255,.06); }
        .lbc-1 { width: 380px; height: 380px; bottom: -120px; right: -100px; }
        .lbc-2 { width: 200px; height: 200px; top: -50px; right: 20px; }
        .login-brand-content { position: relative; z-index: 2; }
        .login-feature-item { display: flex; align-items: center; gap: .75rem; margin-bottom: .85rem; opacity: .85; color: white; font-size: 0.9rem; }
        .login-feature-dot { width: 8px; height: 8px; border-radius: 50%; background: #93c5fd; flex-shrink: 0; }

        /* ── Right form panel ────────────────────────────────── */
        .login-form-panel {
            flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: 2.5rem 2rem; background: #f4f6fb;
        }
        .login-card {
            width: 100%; max-width: 450px; background: #fff;
            border-radius: 20px; box-shadow: 0 8px 40px rgba(31,58,147,.1); padding: 2.5rem;
        }
        .login-card-wide { max-width: 580px; padding: 1.75rem 2.5rem; }

        /* ── Form controls ───────────────────────────────────── */
        .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 .2rem rgba(31,58,147,.18); }
        .btn-login {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: #fff; border: none; font-weight: 600; letter-spacing: .3px; transition: all .15s;
        }
        .btn-login:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); color: #fff; }
        .btn-login:disabled { opacity: .6; cursor: not-allowed; color: #fff; }

        /* ── Livewire / Flux overrides ────────────────────────── */
        [data-flux-input] input,
        [data-flux-input] input[type="email"],
        [data-flux-input] input[type="password"],
        [data-flux-input] input[type="text"] {
            border-radius: .375rem !important;
            padding: .375rem .75rem !important;
            border: 1px solid #dee2e6 !important;
            background: #fff !important;
            color: #212529 !important;
            font-size: 1rem !important;
            line-height: 1.5 !important;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out !important;
        }
        [data-flux-input] input:focus {
            border-color: var(--brand-primary) !important;
            box-shadow: 0 0 0 .2rem rgba(31,58,147,.18) !important;
            outline: none !important;
        }
        [data-flux-input] { position: relative; }

        /* Checkbox overrides */
        [data-flux-checkbox] { accent-color: var(--brand-primary); }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 767px) {
            .login-brand { display: none; }
            .login-form-panel {
                background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
                padding: 1.5rem 1rem;
            }
            .login-card { box-shadow: 0 12px 48px rgba(0,0,0,.25); }
        }
    </style>
</head>
<body class="antialiased">

    <div class="login-wrap">
        {{-- ── LEFT: Brand Panel ─────────────────────────────── --}}
        <div class="login-brand">
            <div class="login-brand-circle lbc-1"></div>
            <div class="login-brand-circle lbc-2"></div>
            <div class="login-brand-content">
                <a href="http://localhost/" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4 opacity-75">
                    <i class="bi bi-arrow-left-circle"></i> Back to Main Systems
                </a>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo" style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.15);padding:2px;">
                    <div class="text-white">
                        <div class="fw-bold lh-1" style="font-size:1.1rem;">Services System</div>
                        <div class="opacity-75" style="font-size:.8rem;">Management Information System</div>
                    </div>
                </div>
                <h2 class="text-white fw-bold mb-2" style="font-size:clamp(1.5rem,2.5vw,2.2rem);line-height:1.25;">
                    Digital Services,<br><span style="color:#93c5fd;">Trusted Records</span>
                </h2>
                <p class="text-white opacity-75 mb-4" style="font-size:.9rem;max-width:350px;">
                    Access barangay certificates, clearances, and blotter records through a unified digital platform.
                </p>
                <div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Automated Certificate Issuance</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Secure Blotter Management</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Integrated Resident Verification</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Real-time Official Reporting</div>
                </div>
            </div>
        </div>

        {{-- ── RIGHT: Form Panel ─────────────────────────────── --}}
        <div class="login-form-panel">
            {{-- Mobile back-link (shown only on mobile when brand is hidden) --}}
            <div class="d-md-none mb-3 w-100 text-center" style="max-width:400px;">
                <a href="http://localhost/" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none small opacity-75">
                    <i class="bi bi-arrow-left-circle"></i> Back to Main Systems
                </a>
            </div>

            <div class="login-card {{ $cardWidth ?? '' }}">
                {{ $slot }}
            </div>

            <div class="text-muted small mt-4" style="opacity:.6;">
                &copy; {{ date('Y') }} Services System. All rights reserved.
            </div>
        </div>
    </div>

    <script>
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @fluxScripts
</body>
</html>
