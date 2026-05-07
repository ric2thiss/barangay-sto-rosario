<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
        
        <!-- Livewire Styles -->
        @livewireStyles
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            :root { --brand-primary: #1f3a93; --brand-secondary: #2e4fc7; }
            body { background: #f4f6fb; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
            .login-wrap { min-height: 100vh; display: flex; }
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
            .lbc-1 { width:380px; height:380px; bottom:-120px; right:-100px; }
            .lbc-2 { width:200px; height:200px; top:-50px; right:20px; }
            .login-brand-content { position: relative; z-index: 2; }
            .login-feature-item { display: flex; align-items: center; gap: .75rem; margin-bottom: .85rem; opacity: .85; color: white; font-size: 0.9rem; }
            .login-feature-dot { width: 8px; height: 8px; border-radius: 50%; background: #93c5fd; flex-shrink: 0; }
            .login-form-panel { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 2.5rem 2rem; background: #f4f6fb; }
            .login-card { width: 100%; max-width: 400px; background: #fff; border-radius: 20px; box-shadow: 0 8px 40px rgba(31,58,147,.1); padding: 2.5rem; }
            .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 .2rem rgba(31,58,147,.18); }
            .btn-login { background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary)); color: #fff; border: none; font-weight: 600; letter-spacing: .3px; transition: all .15s; }
            .btn-login:hover { opacity: .92; transform: translateY(-1px); color: #fff; }
            @media (max-width: 767px) {
                .login-brand { display: none; }
                .login-form-panel { background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%); padding: 1.5rem 1rem; }
                .login-card { box-shadow: 0 12px 48px rgba(0,0,0,.25); }
            }
        </style>
    </head>
    <body>
        {{ $slot }}
        
        <!-- Livewire Scripts -->
        @livewireScripts
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
