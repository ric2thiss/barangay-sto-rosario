<?php
/**
 * admin_logs.php — Activity Logs Dashboard (Admin Only)
 * Sidebar matches dashboard.php exactly.
 */
session_start();
include("connection.php");
include('sidebar_counts.php');
// ── Admin guard ───────────────────────────────────────────────────
if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php'); exit();
}

// ── Mark stale sessions expired (>8 hrs) ─────────────────────────
$conn->query("
    UPDATE activity_logs
    SET status='expired', action='session_expired',
        logout_at=NOW(),
        duration_sec=TIMESTAMPDIFF(SECOND,login_at,NOW())
    WHERE status='online'
      AND action='login'
      AND login_at < DATE_SUB(NOW(), INTERVAL 480 MINUTE)
");

// ── Pending count for sidebar badge ──────────────────────────────
$__pr = $conn->query("SELECT COUNT(*) c FROM pending_registrations WHERE status='Pending'");
$pending_count = $__pr ? (int)$__pr->fetch_assoc()['c'] : 0;

// ── Filters ───────────────────────────────────────────────────────
$filter_status    = $_GET['status']    ?? 'all';
$filter_role      = $_GET['role']      ?? 'all';
$filter_action    = $_GET['action']    ?? 'all';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to   = $_GET['date_to']   ?? '';
$filter_search    = trim($_GET['search'] ?? '');
$page             = max(1, (int)($_GET['page'] ?? 1));
$per_page         = 20;
$offset           = ($page - 1) * $per_page;

// ── Build WHERE ───────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];
$types  = '';

if ($filter_status !== 'all') {
    $where[] = 'status = ?'; $params[] = $filter_status; $types .= 's';
}
if ($filter_role !== 'all') {
    $where[] = 'user_type = ?'; $params[] = $filter_role; $types .= 's';
}
if ($filter_action !== 'all') {
    $where[] = 'action = ?'; $params[] = $filter_action; $types .= 's';
}
if ($filter_date_from) {
    $where[] = 'DATE(login_at) >= ?'; $params[] = $filter_date_from; $types .= 's';
}
if ($filter_date_to) {
    $where[] = 'DATE(login_at) <= ?'; $params[] = $filter_date_to; $types .= 's';
}
if ($filter_search) {
    $like    = '%'.$filter_search.'%';
    $where[] = '(username LIKE ? OR full_name LIKE ? OR ip_address LIKE ? OR city LIKE ?)';
    $params  = array_merge($params, [$like,$like,$like,$like]);
    $types  .= 'ssss';
}
$where_sql = implode(' AND ', $where);

// ── Count ─────────────────────────────────────────────────────────
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM activity_logs WHERE $where_sql");
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->get_result()->fetch_row()[0];
$count_stmt->close();
$total_pages = max(1, (int)ceil($total_rows / $per_page));

// ── Fetch log rows ────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM activity_logs WHERE $where_sql ORDER BY login_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param($types.'ii', ...array_merge($params, [$per_page, $offset]));
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Summary stats ─────────────────────────────────────────────────
$stats = $conn->query("
    SELECT
        COUNT(*)                  AS total,
        SUM(status='online' AND action='login') AS online_now,
        SUM(status='offline')     AS total_offline,
        SUM(status='expired')     AS total_expired,
        SUM(user_type='admin')    AS admin_count,
        SUM(user_type='resident') AS resident_count,
        SUM(device_type='Mobile')  AS mobile_count,
        SUM(device_type='Desktop') AS desktop_count,
        SUM(device_type='Tablet')  AS tablet_count
    FROM activity_logs
")->fetch_assoc();

// ── Online users ──────────────────────────────────────────────────
$online_users = $conn->query(
    "SELECT * FROM activity_logs WHERE status='online' AND action='login' ORDER BY login_at DESC"
)->fetch_all(MYSQLI_ASSOC);



// ── Helpers ───────────────────────────────────────────────────────
function fmt_duration(?int $s): string {
    if ($s === null) return '—';
    if ($s < 60)    return $s.'s';
    if ($s < 3600)  return floor($s/60).'m '.($s%60).'s';
    return floor($s/3600).'h '.floor(($s%3600)/60).'m';
}
function status_badge(string $st): string {
    return match($st) {
        'online'  => '<span class="badge b-online">● Online</span>',
        'offline' => '<span class="badge b-offline">○ Offline</span>',
        'expired' => '<span class="badge b-expired">⊘ Expired</span>',
        default   => '<span class="badge b-offline">'.htmlspecialchars($st).'</span>',
    };
}
function role_badge(string $r): string {
    return match($r) {
        'admin'    => '<span class="badge b-admin">Admin</span>',
        'staff'    => '<span class="badge b-staff">Staff</span>',
        'official' => '<span class="badge b-staff">Official</span>',
        'resident' => '<span class="badge b-resident">Resident</span>',
        default    => '<span class="badge b-resident">'.htmlspecialchars($r).'</span>',
    };
}
function action_badge(string $a): string {
    return match($a) {
        'login'    => '<span class="badge b-online">Login</span>',
        'logout'   => '<span class="badge b-offline">Logout</span>',
        'session_expired' => '<span class="badge b-expired">Session Expired</span>',
        'approve_registration' => '<span class="badge" style="background:#ecfdf5;color:#065f46">✓ Approve Reg</span>',
        'reject_registration'  => '<span class="badge" style="background:#fef2f2;color:#991b1b">✗ Reject Reg</span>',
        'undo_registration'    => '<span class="badge" style="background:#fff7ed;color:#c2410c">↩ Undo Reg</span>',
        'approve_profile_update' => '<span class="badge" style="background:#ecfdf5;color:#065f46">✓ Profile Update</span>',
        'edit_resident'   => '<span class="badge" style="background:#eff6ff;color:#1d4ed8">✎ Edit Resident</span>',
        'delete_resident' => '<span class="badge" style="background:#fef2f2;color:#991b1b">✗ Delete Resident</span>',
        'restore_resident' => '<span class="badge" style="background:#ecfdf5;color:#065f46">↩ Restore Resident</span>',
        default => '<span class="badge b-offline">'.htmlspecialchars($a).'</span>',
    };
}
function device_icon(string $t): string {
    return match($t) { 'Mobile'=>'📱', 'Tablet'=>'🪙', 'Desktop'=>'🖥️', default=>'❓' };
}
function safe(mixed $v, string $fb='—'): string {
    return $v ? htmlspecialchars((string)$v) : $fb;
}
function qp(array $ov=[]): string {
    $p = array_merge($_GET, $ov);
    return '?'.http_build_query(array_filter($p, fn($v)=>$v!==''&&$v!=='all'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs — Barangay Sto. Rosario</title>

<?php include 'hybrid_assets.php'; ?>

<style>
/* ── Bootstrap sidebar fix (hybrid_assets loads Bootstrap BEFORE this style block,
      so Bootstrap's reboot can override body margin/padding — these 3 lines win) ── */
html, body    { padding: 0 !important; margin: 0 !important; overflow-x: hidden; }
.sidebar      { left: 0 !important; }
.main-content { margin-left: var(--sidebar-w) !important; }

/* ── SHARED TOKENS (matches dashboard.php exactly) ───────────────── */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
:root {
  --primary:       #1a56db;
  --primary-light: #e8f0fe;
  --success:       #0e9f6e;
  --danger:        #e02424;
  --warning:       #ff8a00;
  --info:          #0891b2;
  --dark:          #111827;
  --sidebar-w:     250px;
  --sidebar-bg:    #0f172a;
  --body-bg:       #f1f5f9;
  --card-bg:       #ffffff;
  --border:        #e2e8f0;
  --text:          #1e293b;
  --muted:         #64748b;
  --radius:        12px;
  --shadow:        0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
  --purple:        #7c3aed;
  --teal:          #0d9488;
  --rose:          #be185d;
  --amber:         #d97706;

  /* logs-specific */
  --online:  #059669;
  --offline: #64748b;
  --expired: #d97706;
}

*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--body-bg);
  color: var(--text);
  display: flex;
  min-height: 100vh;
  font-size: 14px;
}

/* ── SIDEBAR — identical to dashboard.php ────────────────────────── */
.sidebar {
  width: var(--sidebar-w);
  min-height: 100vh;
  background: var(--sidebar-bg);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  z-index: 100;
  overflow-y: auto;
}

.sidebar-brand {
  padding: 28px 20px 20px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}
.sidebar-brand img {
  width: 72px; height: 72px;
  border-radius: 50%; object-fit: cover;
  border: 3px solid rgba(255,255,255,.15);
}
.sidebar-brand h2 {
  color: #fff;
  font-size: .95rem;
  font-weight: 700;
  text-align: center;
}

.sidebar nav { padding: 16px 12px; flex: 1; }
.sidebar nav ul { list-style: none; display: flex; flex-direction: column; gap: 4px; }
.sidebar nav a {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px;
  border-radius: 8px;
  color: rgba(255,255,255,.65) !important;
  text-decoration: none !important;
  font-size: .875rem !important;
  font-weight: 500 !important;
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  transition: background .15s, color .15s;
}
.sidebar nav a:hover,
.sidebar nav a.active { background: rgba(255,255,255,.1); color: #fff !important; }
.sidebar nav a.active  { background: var(--primary); }
.sidebar nav a i       { width: 18px; text-align: center; }
.nav-badge {
  margin-left: auto;
  background: #e02424;
  color: #fff !important;
  font-size: .68rem;
  font-weight: 800;
  padding: 1px 7px;
  border-radius: 20px;
}
/* notification badge */
.nav-badge {
  margin-left: auto;
  background: #e02424;
  color: #fff;
  font-size: .68rem;
  font-weight: 800;
  padding: 1px 7px;
  border-radius: 20px;
  line-height: 1.4;
}
/* Pending badge */
.nav-badge {
  margin-left: auto;
  background: var(--danger);
  color: #fff;
  font-size: .68rem;
  font-weight: 800;
  padding: 1px 7px;
  border-radius: 20px;
}

/* ── MAIN ────────────────────────────────────────────────────────── */
.main-content {
  margin-left: var(--sidebar-w);
  flex: 1;
  padding: 0;
  max-width: calc(100% - var(--sidebar-w));
  display: flex;
  flex-direction: column;
}

/* ── TOPBAR (replaces old full-width topbar) ─────────────────────── */
.topbar {
  background: var(--primary);
  color: #fff;
  padding: 0 28px;
  display: flex;
  align-items: center;
  gap: 16px;
  height: 56px;
  box-shadow: 0 2px 8px rgba(0,0,0,.18);
  position: sticky; top: 0; z-index: 99;
  flex-shrink: 0;
}
.topbar-title { font-size: 16px; font-weight: 700; letter-spacing: .02em; }
.topbar-sub   { font-size: 11px; opacity: .65; margin-top: 1px; }
.spacer { flex: 1; }

.live-badge {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: rgba(255,255,255,.8);
}
.live-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #4ade80;
  box-shadow: 0 0 0 2px rgba(74,222,128,.3);
  animation: pulse 2s infinite;
  flex-shrink: 0;
}
@keyframes pulse {
  0%,100% { box-shadow: 0 0 0 2px rgba(74,222,128,.3); }
  50%      { box-shadow: 0 0 0 5px rgba(74,222,128,.1); }
}

/* ── PAGE BODY ───────────────────────────────────────────────────── */
.page { padding: 24px 28px 56px; }

/* ── STAT CARDS ──────────────────────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 14px;
  margin-bottom: 22px;
}
.stat-card {
  background: var(--card-bg);
  border-radius: var(--radius);
  padding: 18px 20px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  border-left: 4px solid var(--border);
  display: flex; flex-direction: column; gap: 4px;
  transition: transform .18s;
}
.stat-card:hover { transform: translateY(-2px); }
.stat-card.s-online  { border-left-color: var(--online); }
.stat-card.s-offline { border-left-color: var(--offline); }
.stat-card.s-expired { border-left-color: var(--expired); }
.stat-card.s-admin   { border-left-color: var(--purple); }
.stat-card.s-res     { border-left-color: var(--primary); }
.stat-card.s-mobile  { border-left-color: var(--amber); }
.stat-card .s-icon { font-size: 20px; margin-bottom: 4px; }
.stat-card .s-val  { font-size: 28px; font-weight: 800; color: var(--dark); line-height: 1; }
.stat-card .s-lbl  { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; }

/* ── SECTION CARD ────────────────────────────────────────────────── */
.section-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  margin-bottom: 20px;
}
.section-header {
  padding: 14px 20px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid var(--border);
  gap: 10px;
}
.section-header h2 {
  font-size: 14px; font-weight: 700; color: var(--dark);
  display: flex; align-items: center; gap: 8px;
}
.section-header .count { font-size: 12px; color: var(--muted); }

/* ── ONLINE PANEL ────────────────────────────────────────────────── */
.online-panel {
  background: var(--card-bg);
  border: 1.5px solid #bbf7d0;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  margin-bottom: 20px;
  overflow: hidden;
}
.online-panel-hdr {
  background: linear-gradient(90deg, #f0fdf4, #dcfce7);
  padding: 12px 20px;
  display: flex; align-items: center; gap: 10px;
  border-bottom: 1px solid #bbf7d0;
}
.online-panel-hdr h2 { font-size: 14px; font-weight: 700; color: #166534; display:flex;align-items:center;gap:7px; }
.online-panel-hdr .cnt { font-size: 11px; color: #16a34a; font-weight: 600; }
.online-tiles { display: flex; flex-wrap: wrap; gap: 10px; padding: 14px 20px; }
.online-tile {
  display: flex; align-items: center; gap: 10px;
  background: #f0fdf4; border: 1px solid #86efac;
  border-radius: 8px; padding: 10px 14px; min-width: 220px;
}
.online-tile .info { display:flex; flex-direction:column; gap:2px; }
.online-tile .name { font-size: 13px; font-weight: 700; color: #166534; }
.online-tile .meta { font-size: 11px; color: #16a34a; }
.no-online { padding: 24px 20px; text-align: center; color: var(--muted); font-size: 13px; }

/* ── FILTER BAR ──────────────────────────────────────────────────── */
.filter-bar {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px 20px;
  box-shadow: var(--shadow);
  margin-bottom: 18px;
  display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;
}
.filter-group { display: flex; flex-direction: column; gap: 4px; }
.filter-group label {
  font-size: 10px; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--muted);
}
.filter-group select,
.filter-group input {
  padding: 7px 10px;
  border: 1.5px solid var(--border);
  border-radius: 7px;
  font-size: 13px; font-family: inherit;
  color: var(--text); background: #f8fafc; outline: none;
  transition: border-color .18s;
}
.filter-group select:focus,
.filter-group input:focus { border-color: var(--primary); background: #fff; }
.filter-group.grow { flex: 1; min-width: 200px; }
.filter-group.grow input { width: 100%; }

.btn {
  padding: 8px 16px; border-radius: 7px; border: none;
  font-size: 13px; font-weight: 600; font-family: inherit;
  cursor: pointer; transition: all .18s;
  display: flex; align-items: center; gap: 5px;
}
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: #1648c7; }
.btn-ghost { background: #f1f5f9; color: var(--text); border: 1.5px solid var(--border); }
.btn-ghost:hover { background: var(--border); }
.btn-success-sm {
  background: #f0fdf4; color: #166534;
  border: 1px solid #86efac;
  padding: 6px 12px; font-size: 12px;
  border-radius: 6px; cursor: pointer;
  font-family: inherit; font-weight: 600;
}
.btn-success-sm:hover { background: #dcfce7; }

/* ── TABLE ───────────────────────────────────────────────────────── */
.tbl { width: 100%; border-collapse: collapse; }
.tbl thead { background: #f8fafc; }
.tbl th {
  padding: 10px 14px; text-align: left;
  font-size: 10px; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--muted);
  border-bottom: 1.5px solid var(--border);
  white-space: nowrap;
}
.tbl td {
  padding: 11px 14px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  font-size: 13px;
}
.tbl tbody tr:last-child td { border-bottom: none; }
.tbl tbody tr:hover td { background: #f8fafc; }
.tbl tbody tr.row-online td { background: #f0fdf4; }
.tbl tbody tr.row-online:hover td { background: #dcfce7; }

/* ── BADGES ──────────────────────────────────────────────────────── */
.badge {
  display: inline-flex; align-items: center;
  padding: 3px 9px; border-radius: 20px;
  font-size: 11px; font-weight: 700; white-space: nowrap;
}
.b-online   { background: #dcfce7; color: #166534; }
.b-offline  { background: #f1f5f9; color: #475569; }
.b-expired  { background: #fffbeb; color: #92400e; }
.b-admin    { background: #ede9fe; color: #5b21b6; }
.b-staff    { background: #e0f2fe; color: #0369a1; }
.b-resident { background: #dbeafe; color: #1e40af; }

/* ── AVATAR ──────────────────────────────────────────────────────── */
.avatar {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.av-admin    { background: linear-gradient(135deg,#7c3aed,#4f46e5); }
.av-staff    { background: linear-gradient(135deg,#0891b2,#0e7490); }
.av-resident { background: linear-gradient(135deg,#1a56db,#1f6bb8); }

/* ── CELLS ───────────────────────────────────────────────────────── */
.user-cell { display: flex; align-items: center; gap: 10px; }
.user-name { font-weight: 600; font-size: 13px; color: var(--dark); }
.user-full { font-size: 11px; color: var(--muted); }
.time-cell { display: flex; flex-direction: column; gap: 2px; }
.time-main { font-size: 13px; }
.time-sub  { font-size: 11px; color: var(--muted); }
.dev-cell  { display: flex; flex-direction: column; gap: 2px; }
.dev-main  { font-size: 13px; font-weight: 600; }
.dev-sub   { font-size: 11px; color: var(--muted); }
.loc-cell  { display: flex; flex-direction: column; gap: 2px; }
.loc-main  { font-size: 13px; font-weight: 600; }
.ip-tag    { font-family: 'Courier New', monospace; font-size: 11px; background: #f1f5f9; color: #334155; padding: 1px 6px; border-radius: 4px; display: inline-block; }

/* ── EMPTY STATE ─────────────────────────────────────────────────── */
.empty-state { text-align: center; padding: 56px 20px; color: var(--muted); }
.empty-state .ico { font-size: 40px; margin-bottom: 10px; opacity: .4; }
.empty-state p { font-size: 14px; }

/* ── PAGINATION ──────────────────────────────────────────────────── */
.pagination {
  padding: 14px 20px;
  display: flex; align-items: center; justify-content: space-between;
  border-top: 1px solid var(--border);
}
.page-info { font-size: 12px; color: var(--muted); }
.page-links { display: flex; gap: 4px; }
.page-links a,
.page-links span {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 6px;
  font-size: 13px; text-decoration: none; color: var(--text);
  border: 1px solid var(--border); background: #fff;
  transition: all .15s;
}
.page-links a:hover { background: var(--border); }
.page-links .cur { background: var(--primary); color: #fff; border-color: var(--primary); }
.page-links .dis { opacity: .4; pointer-events: none; }

/* ── REFRESH TIMER ───────────────────────────────────────────────── */
.refresh-badge {
  font-size: 11px; color: rgba(255,255,255,.45);
  font-variant-numeric: tabular-nums;
}

/* ── RESPONSIVE ──────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .tbl th:nth-child(7),
  .tbl td:nth-child(7),
  .tbl th:nth-child(9),
  .tbl td:nth-child(9) { display: none; }
}
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .main-content { margin-left: 0; max-width: 100%; }
}
@media (max-width: 640px) {
  .filter-bar { flex-direction: column; }
  .filter-group, .filter-group select, .filter-group input { width: 100%; }
}
</style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     SIDEBAR — identical structure to dashboard.php
     ══════════════════════════════════════════════════════════ -->
<?php $current_page = 'logs'; include 'sidebar.php'; ?>
<!-- ══════════════════════════════════════════════════════════
     MAIN CONTENT
     ══════════════════════════════════════════════════════════ -->
<div class="main-content">

  <!-- Topbar -->
  <div class="topbar">
    <div>
      <div class="topbar-title"><i class="fas fa-clipboard-list" style="margin-right:8px;opacity:.8"></i>Activity Logs</div>
      <div class="topbar-sub">Barangay Sto. Rosario — Admin Access Only</div>
    </div>
    <div class="spacer"></div>
    <div class="live-badge">
      <span class="live-dot"></span> Live monitoring
    </div>
    <span class="refresh-badge" id="refreshBadge"></span>
  </div>

  <div class="page">

    <!-- ── STAT CARDS ──────────────────────────────────────────── -->
    <div class="stats-grid">
      <div class="stat-card s-online">
        <div class="s-icon">🟢</div>
        <div class="s-val"><?= (int)$stats['online_now'] ?></div>
        <div class="s-lbl">Currently Online</div>
      </div>
      <div class="stat-card s-offline">
        <div class="s-icon">⚪</div>
        <div class="s-val"><?= (int)$stats['total_offline'] ?></div>
        <div class="s-lbl">Logged Out</div>
      </div>
      <div class="stat-card s-expired">
        <div class="s-icon">⏱️</div>
        <div class="s-val"><?= (int)$stats['total_expired'] ?></div>
        <div class="s-lbl">Session Expired</div>
      </div>
      <div class="stat-card s-admin">
        <div class="s-icon">🔐</div>
        <div class="s-val"><?= (int)$stats['admin_count'] ?></div>
        <div class="s-lbl">Admin Logins</div>
      </div>
      <div class="stat-card s-res">
        <div class="s-icon">👤</div>
        <div class="s-val"><?= (int)$stats['resident_count'] ?></div>
        <div class="s-lbl">Resident Logins</div>
      </div>
      <div class="stat-card s-mobile">
        <div class="s-icon">📱</div>
        <div class="s-val"><?= (int)$stats['mobile_count'] ?></div>
        <div class="s-lbl">Mobile Users</div>
      </div>
      <div class="stat-card">
        <div class="s-icon">🖥️</div>
        <div class="s-val"><?= (int)$stats['desktop_count'] ?></div>
        <div class="s-lbl">Desktop Users</div>
      </div>
      <div class="stat-card">
        <div class="s-icon">📊</div>
        <div class="s-val"><?= (int)$stats['total'] ?></div>
        <div class="s-lbl">Total Login Events</div>
      </div>
    </div>

    <!-- ── WHO'S ONLINE NOW ─────────────────────────────────────── -->
    <div class="online-panel">
      <div class="online-panel-hdr">
        <h2><span class="live-dot"></span> Who's Online Right Now</h2>
        <span class="cnt">
          <?= count($online_users) ?> active session<?= count($online_users)!==1?'s':'' ?>
        </span>
        <div style="margin-left:auto">
          <button class="btn btn-ghost" style="font-size:12px;padding:5px 10px"
                  onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Refresh
          </button>
        </div>
      </div>
      <?php if ($online_users): ?>
      <div class="online-tiles">
        <?php foreach ($online_users as $ou): ?>
        <div class="online-tile">
          <div class="avatar av-<?= htmlspecialchars($ou['user_type']) ?>">
            <?= strtoupper(substr($ou['username'], 0, 2)) ?>
          </div>
          <div class="info">
            <div class="name"><?= safe($ou['full_name'] ?: $ou['username']) ?></div>
            <div class="meta">
              <?= device_icon($ou['device_type']) ?> <?= safe($ou['device_type']) ?>
              · <?= safe($ou['browser']) ?>
            </div>
            <div class="meta">
              📍 <?= safe($ou['city']) ?><?= $ou['country'] ? ', '.safe($ou['country']) : '' ?>
            </div>
            <div class="meta">
              🕐 Since <?= date('g:i A', strtotime($ou['login_at'])) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="no-online">
        <i class="fas fa-user-slash" style="opacity:.3;font-size:24px;margin-bottom:6px;display:block"></i>
        No users currently online.
      </div>
      <?php endif; ?>
    </div>

    <!-- ── FILTER BAR ───────────────────────────────────────────── -->
    <form method="GET" class="filter-bar">
      <div class="filter-group">
        <label><i class="fas fa-circle"></i> Status</label>
        <select name="status">
          <option value="all"     <?= $filter_status==='all'     ?'selected':'' ?>>All Status</option>
          <option value="online"  <?= $filter_status==='online'  ?'selected':'' ?>>🟢 Online</option>
          <option value="offline" <?= $filter_status==='offline' ?'selected':'' ?>>⚪ Offline</option>
          <option value="expired" <?= $filter_status==='expired' ?'selected':'' ?>>⏱️ Expired</option>
        </select>
      </div>
      <div class="filter-group">
        <label><i class="fas fa-user-tag"></i> Role</label>
        <select name="role">
          <option value="all"      <?= $filter_role==='all'      ?'selected':'' ?>>All Roles</option>
          <option value="admin"    <?= $filter_role==='admin'    ?'selected':'' ?>>Admin</option>
          <option value="resident" <?= $filter_role==='resident' ?'selected':'' ?>>Resident</option>
          <option value="staff"    <?= $filter_role==='staff'    ?'selected':'' ?>>Staff</option>
        </select>
      </div>
      <div class="filter-group">
        <label><i class="fas fa-bolt"></i> Action</label>
        <select name="action">
          <option value="all"                    <?= $filter_action==='all'                    ?'selected':'' ?>>All Actions</option>
          <option value="login"                  <?= $filter_action==='login'                  ?'selected':'' ?>>🟢 Login</option>
          <option value="logout"                 <?= $filter_action==='logout'                 ?'selected':'' ?>>⚪ Logout</option>
          <option value="session_expired"        <?= $filter_action==='session_expired'        ?'selected':'' ?>>⏱️ Session Expired</option>
          <option value="approve_registration"   <?= $filter_action==='approve_registration'   ?'selected':'' ?>>✓ Approve Reg</option>
          <option value="reject_registration"    <?= $filter_action==='reject_registration'    ?'selected':'' ?>>✗ Reject Reg</option>
          <option value="undo_registration"      <?= $filter_action==='undo_registration'      ?'selected':'' ?>>↩ Undo Reg</option>
          <option value="approve_profile_update" <?= $filter_action==='approve_profile_update' ?'selected':'' ?>>✓ Profile Update</option>
          <option value="edit_resident"          <?= $filter_action==='edit_resident'          ?'selected':'' ?>>✎ Edit Resident</option>
          <option value="delete_resident"        <?= $filter_action==='delete_resident'        ?'selected':'' ?>>✗ Delete Resident</option>
          <option value="restore_resident"       <?= $filter_action==='restore_resident'       ?'selected':'' ?>>↩ Restore Resident</option>
        </select>
      </div>
      <div class="filter-group">
        <label><i class="fas fa-calendar-alt"></i> From</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($filter_date_from) ?>">
      </div>
      <div class="filter-group">
        <label><i class="fas fa-calendar-alt"></i> To</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($filter_date_to) ?>">
      </div>
      <div class="filter-group grow">
        <label><i class="fas fa-search"></i> Search</label>
        <input type="text" name="search" placeholder="Username, name, IP, city…"
               value="<?= htmlspecialchars($filter_search) ?>">
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i> Filter
      </button>
      <a href="admin_logs.php" class="btn btn-ghost">
        <i class="fas fa-times"></i> Clear
      </a>
    </form>

    <!-- ── HISTORY TABLE ────────────────────────────────────────── -->
    <div class="section-card">
      <div class="section-header">
        <h2>
          <i class="fas fa-history" style="color:var(--primary)"></i>
          Login History
        </h2>
        <div style="display:flex;align-items:center;gap:10px">
          <span class="count">
            <?= number_format($total_rows) ?> record<?= $total_rows!==1?'s':'' ?> found
          </span>
          <button class="btn-success-sm" onclick="exportCSV()">
            <i class="fas fa-file-csv"></i> Export CSV
          </button>
        </div>
      </div>

      <div style="overflow-x:auto">
        <table class="tbl" id="logsTable">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Role</th>
              <th>Action</th>
              <th>Details</th>
              <th>Status</th>
              <th>Login Time</th>
              <th>Logout / Duration</th>
              <th>Device</th>
              <th>Location</th>
              <th>IP Address</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="11">
                <div class="empty-state">
                  <div class="ico">📭</div>
                  <p>No activity logs found.</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $i => $row): ?>
            <tr class="<?= $row['status']==='online' ? 'row-online' : '' ?>">

              <!-- # -->
              <td style="color:var(--muted);font-size:12px">
                <?= $offset + $i + 1 ?>
              </td>

              <!-- User -->
              <td>
                <div class="user-cell">
                  <div class="avatar av-<?= htmlspecialchars($row['user_type']) ?>">
                    <?= strtoupper(substr($row['username'], 0, 2)) ?>
                  </div>
                  <div>
                    <div class="user-name"><?= safe($row['username']) ?></div>
                    <?php if ($row['full_name'] && $row['full_name'] !== $row['username']): ?>
                    <div class="user-full"><?= safe($row['full_name']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>

              <!-- Role -->
              <td><?= role_badge($row['user_type']) ?></td>

              <!-- Action -->
              <td><?= action_badge($row['action']) ?></td>

              <!-- Details -->
              <td style="font-size:.78rem;color:var(--muted);max-width:200px;white-space:normal">
                <?= safe($row['details'] ?? '', '—') ?>
              </td>

              <!-- Status -->
              <td><?= status_badge($row['status']) ?></td>

              <!-- Login time -->
              <td>
                <div class="time-cell">
                  <div class="time-main">
                    <?= date('M j, Y', strtotime($row['login_at'])) ?>
                  </div>
                  <div class="time-sub">
                    🕐 <?= date('g:i:s A', strtotime($row['login_at'])) ?>
                  </div>
                </div>
              </td>

              <!-- Logout / Duration -->
              <td>
                <div class="time-cell">
                  <?php if ($row['logout_at']): ?>
                    <div class="time-main">
                      <?= date('g:i:s A', strtotime($row['logout_at'])) ?>
                    </div>
                    <div class="time-sub">
                      ⏱ <?= fmt_duration($row['duration_sec']) ?>
                    </div>
                  <?php elseif ($row['status'] === 'online'): ?>
                    <div class="time-main" style="color:var(--online);font-weight:700">
                      Active now
                    </div>
                    <div class="time-sub">
                      ⏱ <?= fmt_duration(time() - strtotime($row['login_at'])) ?> elapsed
                    </div>
                  <?php else: ?>
                    <div class="time-main" style="color:var(--muted)">—</div>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Device -->
              <td>
                <div class="dev-cell">
                  <div class="dev-main">
                    <?= device_icon($row['device_type']) ?> <?= safe($row['device_type']) ?>
                  </div>
                  <div class="dev-sub"><?= safe($row['browser']) ?></div>
                  <div class="dev-sub"><?= safe($row['os']) ?></div>
                </div>
              </td>

              <!-- Location -->
              <td>
                <div class="loc-cell">
                  <div class="loc-main">
                    <?php
                    $parts = array_filter([$row['city'], $row['country']]);
                    echo $parts ? '📍 '.safe(implode(', ', $parts)) : '📍 —';
                    ?>
                  </div>
                  <?php if ($row['latitude'] && $row['longitude']): ?>
                  <div style="margin-top:2px">
                    <a href="https://maps.google.com/?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>"
                       target="_blank"
                       style="color:var(--primary);font-size:11px;text-decoration:none">
                      <i class="fas fa-map-marker-alt"></i> View on map
                    </a>
                  </div>
                  <?php endif; ?>
                </div>
              </td>

              <!-- IP -->
              <td><span class="ip-tag"><?= safe($row['ip_address']) ?></span></td>

            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <div class="page-info">
          Showing <?= $offset+1 ?>–<?= min($offset+$per_page, $total_rows) ?>
          of <?= number_format($total_rows) ?>
        </div>
        <div class="page-links">
          <a href="<?= qp(['page'=>1]) ?>"       class="<?= $page<=1?'dis':'' ?>">«</a>
          <a href="<?= qp(['page'=>$page-1]) ?>" class="<?= $page<=1?'dis':'' ?>">‹</a>
          <?php
          $start = max(1, $page-2);
          $end   = min($total_pages, $page+2);
          if ($start > 1) echo '<span>…</span>';
          for ($p=$start; $p<=$end; $p++) {
            $cls = ($p===$page) ? 'cur' : '';
            echo "<a href=\"".qp(['page'=>$p])."\" class=\"$cls\">$p</a>";
          }
          if ($end < $total_pages) echo '<span>…</span>';
          ?>
          <a href="<?= qp(['page'=>$page+1]) ?>" class="<?= $page>=$total_pages?'dis':'' ?>">›</a>
          <a href="<?= qp(['page'=>$total_pages]) ?>" class="<?= $page>=$total_pages?'dis':'' ?>">»</a>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /.section-card -->

  </div><!-- /.page -->
</div><!-- /.main-content -->

<script>
// ── Auto-refresh countdown (30s) ──────────────────────────────────
let secs = 30;
const badge = document.getElementById('refreshBadge');
setInterval(() => {
  secs--;
  badge.textContent = `(refresh in ${secs}s)`;
  if (secs <= 0) location.reload();
}, 1000);

// ── CSV Export ────────────────────────────────────────────────────
function exportCSV() {
  const tbl  = document.getElementById('logsTable');
  const rows = [...tbl.querySelectorAll('tr')];
  const csv  = rows.map(r =>
    [...r.querySelectorAll('th,td')]
      .map(c => '"' + c.innerText.replace(/"/g, '""').replace(/\n/g, ' ').trim() + '"')
      .join(',')
  ).join('\n');

  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(new Blob([csv], {type:'text/csv'}));
  a.download = 'activity_logs_' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
}
</script>
</body>
</html>