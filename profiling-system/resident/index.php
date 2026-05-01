<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Barangay Sto. Rosario — Resident Information System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap"
    rel="stylesheet">
  <style>
    /* ── TOKENS ──────────────────────────────────────────────────────── */
    :root {
      --navy: #071628;
      --blue: #0f3c6e;
      --blue-md: #1f6bb8;
      --blue-lt: #2278d4;
      --gold: #c8963e;
      --gold-lt: #e8b45a;
      --gold-pale: rgba(200, 150, 62, 0.12);
      --white: #ffffff;
      --muted: rgba(255, 255, 255, 0.45);
      --muted2: rgba(255, 255, 255, 0.22);
      --border: rgba(255, 255, 255, 0.10);
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--navy);
      color: var(--white);
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ── ANIMATED BACKGROUND ────────────────────────────────────────── */
    .bg-canvas {
      position: fixed;
      inset: 0;
      z-index: 0;
      overflow: hidden;
    }

    /* Base gradient */
    .bg-canvas::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 80% 60% at 20% 10%, rgba(31, 107, 184, 0.35) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 80% 80%, rgba(15, 60, 110, 0.5) 0%, transparent 60%),
        radial-gradient(ellipse 100% 80% at 50% 50%, rgba(7, 22, 40, 0.9) 0%, transparent 100%),
        linear-gradient(160deg, #071628 0%, #0c2d54 40%, #071628 100%);
    }

    /* Dot mesh */
    .bg-canvas::after {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(rgba(255, 255, 255, 0.028) 1px, transparent 1px);
      background-size: 28px 28px;
    }

    /* Floating orbs */
    .orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.18;
      animation: drift linear infinite;
    }

    .orb-1 {
      width: 400px;
      height: 400px;
      background: #1f6bb8;
      top: -100px;
      left: -80px;
      animation-duration: 28s;
    }

    .orb-2 {
      width: 300px;
      height: 300px;
      background: #c8963e;
      bottom: -60px;
      right: 10%;
      animation-duration: 22s;
      animation-direction: reverse;
    }

    .orb-3 {
      width: 200px;
      height: 200px;
      background: #2278d4;
      top: 40%;
      left: 60%;
      animation-duration: 18s;
    }

    @keyframes drift {
      0% {
        transform: translate(0, 0) scale(1);
      }

      33% {
        transform: translate(30px, -40px) scale(1.08);
      }

      66% {
        transform: translate(-20px, 20px) scale(0.95);
      }

      100% {
        transform: translate(0, 0) scale(1);
      }
    }

    /* ── PAGE WRAPPER ───────────────────────────────────────────────── */
    .page {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── HEADER ─────────────────────────────────────────────────────── */
    .site-header {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 48px;
      background: rgba(5, 12, 30, 0.65);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      animation: slideDown .55s cubic-bezier(.22, 1, .36, 1) both;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .logo-wrap {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      overflow: hidden;
      flex-shrink: 0;
      border: 2px solid rgba(200, 150, 62, 0.4);
      box-shadow: 0 0 18px rgba(200, 150, 62, 0.25);
    }

    .logo-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .header-text h1 {
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      font-weight: 600;
      color: var(--white);
      letter-spacing: .01em;
      line-height: 1.25;
    }

    .header-text p {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      color: var(--muted);
      letter-spacing: .1em;
      text-transform: uppercase;
      margin-top: 1px;
    }

    .header-right {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .header-tag {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      color: var(--muted2);
      letter-spacing: .12em;
      text-transform: uppercase;
    }

    .btn-signin {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 22px;
      border-radius: 24px;
      background: linear-gradient(135deg, var(--gold), var(--gold-lt));
      color: #0a1f3c;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: .05em;
      text-transform: uppercase;
      text-decoration: none;
      box-shadow: 0 4px 18px rgba(200, 150, 62, .35);
      transition: transform .2s, box-shadow .2s, filter .2s;
    }

    .btn-signin:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(200, 150, 62, .45);
      filter: brightness(1.06);
    }

    /* ── HERO ───────────────────────────────────────────────────────── */
    .hero {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 64px 24px 56px;
      text-align: center;
    }

    /* Seal */
    .hero-seal-wrap {
      position: relative;
      margin-bottom: 36px;
      animation: sealIn .7s .1s cubic-bezier(.34, 1.56, .64, 1) both;
    }

    @keyframes sealIn {
      from {
        opacity: 0;
        transform: scale(.55) rotate(-8deg);
      }

      to {
        opacity: 1;
        transform: scale(1) rotate(0deg);
      }
    }

    .seal-ring-outer {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      border: 1.5px dashed rgba(200, 150, 62, .28);
      display: flex;
      align-items: center;
      justify-content: center;
      animation: spin 30s linear infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .seal-ring-inner {
      width: 110px;
      height: 110px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .05);
      border: 1.5px solid rgba(200, 150, 62, .3);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      box-shadow:
        0 0 40px rgba(200, 150, 62, .12),
        inset 0 0 20px rgba(200, 150, 62, .06);
      animation: spin 30s linear infinite reverse;
    }

    .seal-ring-inner img {
      width: 90px;
      height: 90px;
      object-fit: contain;
      animation: spin 30s linear infinite;
      /* counter-rotate to stay still */
    }

    /* Corner dots on the outer ring */
    .seal-dot {
      position: absolute;
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--gold);
      box-shadow: 0 0 8px var(--gold);
    }

    .seal-dot:nth-child(1) {
      top: 0;
      left: 50%;
      transform: translateX(-50%);
    }

    .seal-dot:nth-child(2) {
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
    }

    .seal-dot:nth-child(3) {
      left: 0;
      top: 50%;
      transform: translateY(-50%);
    }

    .seal-dot:nth-child(4) {
      right: 0;
      top: 50%;
      transform: translateY(-50%);
    }

    /* Hero text */
    .hero-eyebrow {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      letter-spacing: .22em;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 14px;
      animation: fadeUp .5s .25s ease both;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(18px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(30px, 5.5vw, 54px);
      font-weight: 800;
      line-height: 1.1;
      letter-spacing: -.02em;
      color: var(--white);
      margin-bottom: 8px;
      animation: fadeUp .5s .35s ease both;
    }

    .hero-title .accent {
      background: linear-gradient(90deg, var(--gold), var(--gold-lt), #f5c97a);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .hero-sub {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.65;
      max-width: 480px;
      margin: 0 auto 50px;
      animation: fadeUp .5s .45s ease both;
    }

    /* ── FEATURES STRIP ─────────────────────────────────────────────── */
    .features {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      justify-content: center;
      margin-bottom: 52px;
      animation: fadeUp .5s .5s ease both;
    }

    .feat {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      border-radius: 24px;
      background: rgba(255, 255, 255, .055);
      border: 1px solid rgba(255, 255, 255, .1);
      font-size: 12px;
      color: rgba(255, 255, 255, .75);
      backdrop-filter: blur(8px);
      transition: background .2s, border-color .2s, transform .2s;
    }

    .feat:hover {
      background: rgba(255, 255, 255, .09);
      border-color: rgba(200, 150, 62, .3);
      transform: translateY(-2px);
    }

    .feat-icon {
      font-size: 14px;
    }

    /* ── CTA BUTTON ─────────────────────────────────────────────────── */
    .cta-group {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      animation: fadeUp .5s .55s ease both;
    }

    .cta-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 16px 42px;
      border-radius: 50px;
      background: linear-gradient(135deg, var(--blue-md) 0%, var(--blue-lt) 100%);
      color: var(--white);
      font-family: 'DM Sans', sans-serif;
      font-size: 15px;
      font-weight: 700;
      letter-spacing: .04em;
      text-decoration: none;
      box-shadow:
        0 6px 28px rgba(31, 107, 184, .45),
        0 0 0 1px rgba(34, 120, 212, .3);
      transition: transform .22s, box-shadow .22s, filter .22s;
      position: relative;
      overflow: hidden;
    }

    .cta-btn::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255, 255, 255, .12) 0%, transparent 60%);
      opacity: 0;
      transition: opacity .22s;
    }

    .cta-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 40px rgba(31, 107, 184, .55), 0 0 0 1px rgba(34, 120, 212, .4);
      filter: brightness(1.08);
    }

    .cta-btn:hover::before {
      opacity: 1;
    }

    .cta-btn:active {
      transform: translateY(-1px);
    }

    .cta-arrow {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .2);
      font-size: 13px;
      transition: transform .2s;
    }

    .cta-btn:hover .cta-arrow {
      transform: translateX(3px);
    }

    .register-link {
      font-size: 13px;
      color: var(--muted);
    }

    .register-link a {
      color: var(--gold-lt);
      text-decoration: none;
      font-weight: 600;
      transition: color .18s;
    }

    .register-link a:hover {
      color: var(--white);
    }

    /* ── STATS ROW ──────────────────────────────────────────────────── */
    .stats-row {
      display: flex;
      gap: 0;
      flex-wrap: wrap;
      justify-content: center;
      background: rgba(255, 255, 255, .04);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      animation: fadeUp .5s .65s ease both;
    }

    .stat-item {
      flex: 1;
      min-width: 140px;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 24px 20px;
      border-right: 1px solid var(--border);
      transition: background .2s;
    }

    .stat-item:last-child {
      border-right: none;
    }

    .stat-item:hover {
      background: rgba(255, 255, 255, .04);
    }

    .stat-num {
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      font-weight: 800;
      background: linear-gradient(135deg, var(--gold), var(--gold-lt));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      line-height: 1;
      margin-bottom: 4px;
    }

    .stat-lbl {
      font-family: 'DM Mono', monospace;
      font-size: 9px;
      letter-spacing: .14em;
      text-transform: uppercase;
      color: var(--muted);
      text-align: center;
    }

    /* ── FOOTER ─────────────────────────────────────────────────────── */
    footer {
      padding: 16px 48px;
      text-align: center;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
    }

    .footer-copy {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      color: rgba(255, 255, 255, .22);
      letter-spacing: .06em;
    }

    .footer-copy strong {
      color: rgba(200, 150, 62, .5);
      font-weight: 400;
    }

    .footer-links {
      display: flex;
      gap: 20px;
    }

    .footer-links a {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      color: rgba(255, 255, 255, .25);
      text-decoration: none;
      letter-spacing: .08em;
      text-transform: uppercase;
      transition: color .18s;
    }

    .footer-links a:hover {
      color: var(--gold-lt);
    }

    /* ── RESPONSIVE ─────────────────────────────────────────────────── */
    @media (max-width: 600px) {
      .site-header {
        padding: 12px 18px;
      }

      .header-tag {
        display: none;
      }

      .hero {
        padding: 48px 20px 40px;
      }

      .stats-row {
        display: none;
      }

      footer {
        flex-direction: column;
        align-items: center;
        padding: 14px 18px;
      }

      .footer-links {
        display: none;
      }
    }
  </style>
</head>

<body>

  <!-- Background -->
  <div class="bg-canvas">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
  </div>

  <div class="page">

    <!-- ── HEADER ──────────────────────────────────────────────────── -->
    <header class="site-header">
      <div class="logo-wrap">
        <img src="../image/logo.jpg" alt="Barangay Sto. Rosario">
      </div>
      <div class="header-text">
        <h1>Barangay Sto. Rosario</h1>
        <p>Magallanes · Agusan del Norte</p>
      </div>
      <div class="header-right">
        <span class="header-tag">RPRMS · 2026</span>
        <a href="../officials/login.php" class="btn-signin">
          🔐 Sign In
        </a>
      </div>
    </header>

    <!-- ── HERO ────────────────────────────────────────────────────── -->
    <main class="hero">

      <!-- Animated seal -->
      <div class="hero-seal-wrap">
        <div class="seal-dot"></div>
        <div class="seal-dot"></div>
        <div class="seal-dot"></div>
        <div class="seal-dot"></div>
        <div class="seal-ring-outer">
          <div class="seal-ring-inner">
            <img src="../image/logo.jpg" alt="Barangay Seal">
          </div>
        </div>
      </div>

      <p class="hero-eyebrow">Official Digital Portal &mdash; Republic of the Philippines</p>

      <h1 class="hero-title">
        Resident Profiling<br>
        &amp; Records <span class="accent">Management</span>
      </h1>

      <p class="hero-sub">
        A centralized web-based system for Barangay Sto. Rosario — managing
        resident profiles, household records, and barangay-issued documents
        with real-time analytics.
      </p>

      <!-- Feature pills -->
      <div class="features">
        <div class="feat"><span class="feat-icon">📋</span> Resident Profiling</div>
        <div class="feat"><span class="feat-icon">📄</span> Document Issuance</div>
        <div class="feat"><span class="feat-icon">📊</span> Analytics Dashboard</div>
        <div class="feat"><span class="feat-icon">🏠</span> Household Records</div>
        <div class="feat"><span class="feat-icon">🔐</span> Secure Access</div>
        <div class="feat"><span class="feat-icon">📥</span> CSV Export</div>
      </div>

      <!-- Single CTA -->
      <div class="cta-group">
        <a href="../officials/login.php" class="cta-btn">
          Access the Portal
          <span class="cta-arrow">→</span>
        </a>
        <p class="register-link">
          New resident? <a href="resident/register_residents.php">Create an account →</a>
        </p>
      </div>

    </main>

    <!-- ── STATS ROW ──────────────────────────────────────────────── -->
    <div class="stats-row">
      <div class="stat-item">
        <div class="stat-num">100%</div>
        <div class="stat-lbl">Digital Records</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">3</div>
        <div class="stat-lbl">Document Types</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">24/7</div>
        <div class="stat-lbl">System Access</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">1</div>
        <div class="stat-lbl">Barangay Served</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">10+</div>
        <div class="stat-lbl">Analytics Charts</div>
      </div>
    </div>

    <!-- ── FOOTER ─────────────────────────────────────────────────── -->
    <footer>
      <p class="footer-copy">
        &copy; 2026 &nbsp;
        <strong>Barangay Sto. Rosario Resident Information System</strong>
        &nbsp;&middot;&nbsp; All rights reserved.
      </p>
      <nav class="footer-links">
        <a href="../officials/login.php">Login</a>
        <a href="resident/register_residents.php">Register</a>
      </nav>
    </footer>

  </div><!-- /.page -->

</body>

</html>