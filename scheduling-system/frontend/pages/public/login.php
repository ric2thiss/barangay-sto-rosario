<?php // frontend/pages/public/login.php ?>
<style>
  body.bpss-body { background: #f4f6fb !important; padding: 0 !important; }

  .login-wrap { min-height: 100vh; display: flex; }

  /* ── Left brand panel ── */
  .login-brand {
    flex: 0 0 42%;
    background: linear-gradient(155deg, #1f3a93 0%, #2e4fc7 55%, #1a56db 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 3rem 3.5rem;
    position: relative;
    overflow: hidden;
  }
  .login-brand::before {
    content: "";
    position: absolute; inset: 0;
    background:
      radial-gradient(ellipse at 10% 80%, rgba(255,255,255,.08) 0%, transparent 55%),
      radial-gradient(ellipse at 90% 10%, rgba(255,255,255,.05) 0%, transparent 45%);
    pointer-events: none;
  }
  .login-brand-circle {
    position: absolute; border-radius: 50%;
    background: rgba(255,255,255,.06);
  }
  .lbc-1 { width:380px; height:380px; bottom:-120px; right:-100px; }
  .lbc-2 { width:200px; height:200px; top:-50px;    right:20px; }
  .login-brand-content { position: relative; z-index: 2; }

  .login-feature-item {
    display: flex; align-items: center; gap: .75rem;
    margin-bottom: .85rem; opacity: .85;
  }
  .login-feature-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #93c5fd; flex-shrink: 0;
  }

  /* ── Right form panel ── */
  .login-form-panel {
    flex: 1;
    display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    padding: 2.5rem 2rem;
    background: #f4f6fb;
  }
  .login-card {
    width: 100%; max-width: 400px;
    background: #fff; border-radius: 20px;
    box-shadow: 0 8px 40px rgba(31,58,147,.1);
    padding: 2.5rem;
  }

  .form-control:focus { border-color: #1f3a93; box-shadow: 0 0 0 .2rem rgba(31,58,147,.18); }
  .input-group .form-control:focus { z-index: 3; }
  .invalid-feedback { font-size: .825rem; color: #dc3545; margin-top: .25rem; }

  .btn-login {
    background: linear-gradient(135deg, #1f3a93, #2e4fc7);
    color: #fff; border: none; font-weight: 600; letter-spacing: .3px;
    transition: opacity .15s, transform .15s;
  }
  .btn-login:hover { opacity: .92; transform: translateY(-1px); color: #fff; }
  .btn-login:disabled { opacity: .7; transform: none; }

  /* Mobile: hide brand, style form on gradient */
  @media (max-width: 767px) {
    .login-brand { display: none; }
    .login-form-panel {
      background: linear-gradient(135deg, #1f3a93 0%, #2e4fc7 100%);
      padding: 1.5rem 1rem;
    }
    .login-card { box-shadow: 0 12px 48px rgba(0,0,0,.25); }
  }

  /* Extra-small phones */
  @media (max-width: 399px) {
    .login-card { padding: 1.5rem 1.25rem; }
    .login-card .d-flex img { width: 42px !important; height: 42px !important; }
  }

  /* Tablet: show brand panel at reduced width */
  @media (min-width: 768px) and (max-width: 1023px) {
    .login-brand { flex: 0 0 38%; padding: 2rem 2rem; }
  }
</style>

<div class="login-wrap">

  <!-- ── Brand panel ── -->
  <div class="login-brand">
    <div class="login-brand-circle lbc-1"></div>
    <div class="login-brand-circle lbc-2"></div>

    <div class="login-brand-content">
      <a href="../index.php"
         class="d-inline-flex align-items-center gap-1 mb-5 text-white text-decoration-none small"
         style="opacity:.75;transition:opacity .15s;"
         onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.75">
        <i class="bi bi-arrow-left"></i> Back to Home
      </a>

      <div class="d-flex align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
          <img src="frontend/assets/images/Magallanes-Logo.jpg" alt="Municipality Logo"
               style="width:48px;height:48px;border-radius:50%;object-fit:cover;background:rgba(255,255,255,.15);padding:2px;box-shadow:0 4px 14px rgba(0,0,0,.2);">
          <img src="frontend/assets/images/seal-nobg.png" alt="Barangay Seal"
               style="width:48px;height:48px;border-radius:50%;object-fit:contain;background:rgba(255,255,255,.15);padding:2px;box-shadow:0 4px 14px rgba(0,0,0,.2);">
        </div>
        <div class="text-white">
          <div class="fw-bold lh-1" style="font-size:1rem;">Barangay PSS</div>
          <div class="opacity-75" style="font-size:.78rem;">Planning &amp; Scheduling System</div>
        </div>
      </div>

      <h2 class="text-white fw-bold mb-2" style="font-size:clamp(1.5rem,2.5vw,2rem);line-height:1.25;">
        Welcome back,<br>
        <span style="color:#93c5fd;">Administrator</span>
      </h2>
      <p class="text-white opacity-75 mb-4" style="font-size:.9rem;max-width:300px;line-height:1.6;">
        Sign in to manage barangay events, agendas, contacts, and SMS notifications.
      </p>

      <div>
        <div class="login-feature-item text-white small">
          <span class="login-feature-dot"></span> Event &amp; Agenda Management
        </div>
        <div class="login-feature-item text-white small">
          <span class="login-feature-dot"></span> Automated SMS Notifications
        </div>
        <div class="login-feature-item text-white small">
          <span class="login-feature-dot"></span> Meeting Minutes &amp; Reports
        </div>
        <div class="login-feature-item text-white small">
          <span class="login-feature-dot"></span> Contacts &amp; Officials Directory
        </div>
      </div>
    </div>
  </div>

  <!-- ── Form panel ── -->
  <div class="login-form-panel">

    <!-- Mobile back link -->
    <div class="d-md-none mb-3 w-100 text-center" style="max-width:400px;">
      <a href="../index.php"
         class="d-inline-flex align-items-center gap-1 text-white text-decoration-none small opacity-75">
        <i class="bi bi-arrow-left"></i> Back to Home
      </a>
    </div>

    <div class="login-card">

      <div class="text-center mb-4">
        <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
          <img src="frontend/assets/images/Magallanes-Logo.jpg" alt="Municipality Logo"
               style="width:52px;height:52px;border-radius:50%;object-fit:cover;box-shadow:0 4px 16px rgba(31,58,147,.25);">
          <img src="frontend/assets/images/seal-nobg.png" alt="Barangay Seal"
               style="width:52px;height:52px;border-radius:50%;object-fit:contain;box-shadow:0 4px 16px rgba(31,58,147,.25);">
        </div>
        <h5 class="fw-bold mb-1">Sign In</h5>
        <div class="text-muted small">Enter your credentials to continue</div>
      </div>

      <div id="loginMsg" class="mb-3"></div>

      <form id="loginForm" autocomplete="off" novalidate>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Username</label>
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" name="username" id="username"
                   placeholder="Enter your username" autocomplete="username">
          </div>
          <div class="invalid-feedback" style="display:none;" id="usernameError">Please enter your username.</div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" name="password" id="password" required
                   placeholder="Enter your password" autocomplete="current-password">
            <button class="btn btn-outline-secondary" type="button" id="togglePw" title="Toggle password">
              <i class="bi bi-eye" id="togglePwIcon"></i>
            </button>
          </div>
          <div class="invalid-feedback" style="display:none;" id="passwordError">Please enter your password.</div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me" value="1">
            <label class="form-check-label small" for="rememberMe">Remember me</label>
          </div>
        </div>

        <button class="btn btn-login w-100 py-2 rounded-3" type="submit" id="btnLogin">
          <span id="btnLoginLabel">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
          </span>
          <span id="btnLoginSpinner" class="d-none">
            <span class="spinner-border spinner-border-sm me-1"></span> Signing in...
          </span>
        </button>

      </form>

      <div class="text-center text-muted small mt-4 pt-3 border-top">
        <i class="bi bi-shield-lock me-1"></i> Authorized personnel only
      </div>

    </div>

    <div class="text-muted small mt-4" style="opacity:.6;">
      &copy; <?php echo date('Y'); ?> Barangay PSS
    </div>
  </div>

</div>

<script src="frontend/js/login.js"></script>
<script>
  // Eye icon password toggle
  document.getElementById('togglePw').addEventListener('click', function () {
    const pw   = document.getElementById('password');
    const icon = document.getElementById('togglePwIcon');
    const show = pw.type === 'password';
    pw.type        = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
</script>
