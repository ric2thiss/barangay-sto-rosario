<?php
// ── resident/dashboard.php ────────────────────────────────────────────────
session_start();

// ── Auth guard ────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'resident') {
    header('Location: ../officials/login.php'); exit();
}

// ── Session timeout (2 hours idle) ────────────────────────────────────────
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
    session_destroy();
    header('Location: ../officials/login.php?timeout=1'); exit();
}
$_SESSION['login_time'] = time();

// ── Security headers ──────────────────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: same-origin');

// ── CSRF token ────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include('../officials/connection.php');

$resident_id = (int)$_SESSION['user_id'];

// ── Load resident ─────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM residents WHERE id = ? AND account_status = 'active' LIMIT 1");
$stmt->bind_param('i', $resident_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$me) {
    session_destroy();
    header('Location: ../officials/login.php?error=suspended'); exit();
}

// ── Last profile update request ───────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT * FROM profile_update_requests WHERE resident_id = ? ORDER BY created_at DESC LIMIT 1"
);
$stmt->bind_param('i', $resident_id);
$stmt->execute();
$last_request = $stmt->get_result()->fetch_assoc();
$stmt->close();
$has_pending = $last_request && $last_request['status'] === 'Pending';

// ── Residents list (privacy: name + purok only) ────────────────────────────
$r_list = $conn->query(
    "SELECT first_name, middle_name, surname, purok
     FROM residents
     WHERE is_deceased = 'No' OR is_deceased IS NULL
     ORDER BY purok, surname, first_name"
);
$residents_list = [];
while ($row = $r_list->fetch_assoc()) $residents_list[] = $row;

// ── Officials list (public info) ───────────────────────────────────────────
$o_list = $conn->query(
    "SELECT first_name, middle_name, surname, purok, position, image_path
     FROM barangay_official
     WHERE status = 'Active'
     ORDER BY CASE position
         WHEN 'Barangay Captain'   THEN 1
         WHEN 'Barangay Kagawad'   THEN 2
         WHEN 'Sangguniang Barangay (SB) Member' THEN 3
         WHEN 'SK Chairperson'     THEN 4
         WHEN 'Barangay Secretary' THEN 5
         WHEN 'Barangay Treasurer' THEN 6
         ELSE 7
     END, first_name"
);
$officials_list = [];
while ($row = $o_list->fetch_assoc()) $officials_list[] = $row;

$conn->close();

// ── Helpers ───────────────────────────────────────────────────────────────

/**
 * PSA FIES 2021 SES classification from monthly income.
 */
function classifySES(?float $monthly): ?string {
    if ($monthly === null || $monthly < 0) return null;
    if ($monthly < 10957)  return 'Poor';
    if ($monthly < 21914)  return 'Low Income';
    if ($monthly < 43828)  return 'Lower Middle Income';
    if ($monthly < 76669)  return 'Middle Income';
    if ($monthly < 131484) return 'Upper Middle Income';
    return 'High Income';
}

function pesoFormat(?float $v): string {
    if ($v === null) return '—';
    return '₱' . number_format($v, 2);
}

// Derived values for profile display
$monthly = ($me['monthly_income'] !== null && $me['monthly_income'] !== '')
    ? (float)$me['monthly_income'] : null;
$annual  = ($monthly !== null) ? round($monthly * 12, 2) : null;
$ses     = !empty($me['socioeconomic_status'])
    ? $me['socioeconomic_status']
    : classifySES($monthly);

// ── Flash message ─────────────────────────────────────────────────────────
$flash = '';
if (isset($_GET['sent']))    $flash = 'success:Your update request has been submitted and is pending admin approval.';
if (isset($_GET['exists']))  $flash = 'warning:You already have a pending update request. Please wait for admin review.';
if (isset($_GET['timeout'])) $flash = 'warning:Your session expired. Please log in again.';

// ── Static option lists (shared between PHP form and JS) ──────────────────
$OCCUPATION_TYPES = [
    'Employed','Self-Employed','Unemployed','Student','Retired',
    'Farmer','Fisherman','OFW','PWD / Cannot Work','Other'
];
$BARANGAYS = [
    'Buhang','Caloc-an','Guiasan','Marcos','Poblacion',
    'Santo Niño','Santo Rosario','Taod-oy'
];
$PWD_TYPES = [
    // English label => Bisaya translation shown in parentheses
    'Visual Impairment'             => 'Visual Impairment (Problema sa Paninaway)',
    'Hearing Impairment'            => 'Hearing Impairment (Problema sa Pandungog)',
    'Physical / Mobility Impairment'=> 'Physical / Mobility Impairment (Problema sa Paglihok)',
    'Intellectual Disability'       => 'Intellectual Disability (Pagkakuli sa Pag-isip)',
    'Learning Disability'           => 'Learning Disability (Pagkakuli sa Pagkat-on)',
    'Mental / Psychosocial Disability'=> 'Mental / Psychosocial Disability (Sakit sa Hunahuna)',
    'Speech / Language Impairment'  => 'Speech / Language Impairment (Problema sa Pagsulti)',
    'Chronic Illness'               => 'Chronic Illness (Dugay nga Sakit)',
    'Multiple Disability'           => 'Multiple Disability (Daghang Kapansanan)',
    'Other'                         => 'Other (Uban pa)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — Barangay Sto. Rosario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        :root {
            --primary:#1a56db; --primary-light:#e8f0fe;
            --success:#0e9f6e; --danger:#e02424; --warning:#d97706;
            --dark:#111827; --sidebar-w:240px; --sidebar-bg:#0f172a;
            --body-bg:#f1f5f9; --card-bg:#ffffff; --border:#e2e8f0;
            --text:#1e293b; --muted:#64748b; --radius:12px;
            --shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--body-bg);color:var(--text);display:flex;min-height:100vh;}

        /* ── Sidebar ─────────────────────────────────────────── */
        .sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--sidebar-bg);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100;overflow-y:auto;}
        .sidebar-brand{padding:24px 18px 18px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;align-items:center;gap:10px;}
        .sidebar-brand img{width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.15);}
        .sidebar-brand .sb-name{color:#fff;font-size:.85rem;font-weight:700;text-align:center;line-height:1.3;}
        .sidebar-brand .sb-role{font-size:.7rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.5px;}
        .sidebar nav{padding:14px 10px;flex:1;}
        .sidebar nav ul{list-style:none;display:flex;flex-direction:column;gap:3px;}
        .sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 13px;border-radius:8px;color:rgba(255,255,255,.65);text-decoration:none;font-size:.85rem;font-weight:500;transition:background .15s,color .15s;}
        .sidebar nav a:hover,.sidebar nav a.active{background:rgba(255,255,255,.1);color:#fff;}
        .sidebar nav a.active{background:var(--primary);}
        .sidebar nav a i{width:18px;text-align:center;}

        /* ── Main ─────────────────────────────────────────────── */
        .main{margin-left:var(--sidebar-w);flex:1;padding:24px 24px 48px;max-width:calc(100% - var(--sidebar-w));}
        .page-title{font-size:1.4rem;font-weight:800;color:var(--dark);margin-bottom:4px;}
        .page-sub{font-size:.82rem;color:var(--muted);margin-bottom:22px;}

        /* ── Flash ────────────────────────────────────────────── */
        .flash{border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:.875rem;font-weight:600;}
        .flash-success{background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46;}
        .flash-warning{background:#fffbeb;border:1px solid #fde68a;color:#92400e;}
        .flash-error  {background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;}

        /* ── Card ─────────────────────────────────────────────── */
        .card{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:22px 24px;margin-bottom:22px;box-shadow:var(--shadow);}
        .card-header{display:flex;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);}
        .card-header h5{font-size:1rem;font-weight:700;color:var(--dark);margin:0;flex:1;}
        .card-header i{color:var(--primary);}

        /* ── Profile ──────────────────────────────────────────── */
        .profile-wrap{display:flex;gap:22px;align-items:flex-start;flex-wrap:wrap;}
        .profile-photo img{width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--border);flex-shrink:0;}
        .profile-info{flex:1;min-width:220px;}
        .profile-name{font-size:1.25rem;font-weight:800;color:var(--dark);}
        .profile-meta{font-size:.82rem;color:var(--muted);margin-top:3px;}
        .profile-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-top:16px;}
        .pf-item label{display:block;font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;}
        .pf-item span{font-size:.875rem;font-weight:500;color:var(--text);}

        /* SES badge */
        .ses-badge{display:inline-block;padding:2px 10px;border-radius:10px;font-size:.72rem;font-weight:700;}

        /* ── Pending notice ───────────────────────────────────── */
        .pending-notice{background:#fffbeb;border:1px solid #fde68a;border-left:4px solid var(--warning);border-radius:10px;padding:13px 16px;margin-bottom:18px;display:flex;gap:12px;align-items:flex-start;}
        .pending-notice i{color:var(--warning);font-size:1.1rem;margin-top:1px;}
        .pending-notice .pn-text strong{display:block;font-size:.875rem;font-weight:700;color:#92400e;}
        .pending-notice .pn-text span{font-size:.8rem;color:#78350f;}

        /* ── Form ─────────────────────────────────────────────── */
        .form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;}
        .form-group{display:flex;flex-direction:column;gap:6px;}
        .form-group label{font-size:.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;}
        .form-group input,
        .form-group select,
        .form-group textarea{border:1px solid var(--border);border-radius:8px;padding:9px 12px;font-size:.875rem;color:var(--text);font-family:inherit;outline:none;transition:border-color .2s,box-shadow .2s;background:#fff;}
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(26,86,219,.1);}
        .form-group input[readonly]{background:#f8fafc;color:var(--muted);cursor:not-allowed;}
        .form-group textarea{resize:vertical;min-height:80px;}
        .form-full{grid-column:1/-1;}
        .form-note{font-size:.75rem;color:var(--muted);margin-top:14px;background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:10px 13px;}
        .form-note i{color:var(--warning);}
        .btn-submit{background:var(--primary);color:#fff;border:none;border-radius:8px;padding:10px 22px;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:8px;transition:opacity .15s;margin-top:16px;}
        .btn-submit:hover{opacity:.88;}
        .btn-submit:disabled{opacity:.5;cursor:not-allowed;}

        /* PWD type section */
        .pwd-section{margin-top:6px;padding:12px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;display:none;}
        .pwd-section.visible{display:block;}
        .pwd-section label{font-size:.75rem;font-weight:700;color:#c2410c;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;display:block;}

        /* Auto-computed field highlight */
        .computed-group input{background:#f0f9ff;border-color:#bae6fd;color:#0c4a6e;font-weight:700;}

        /* ── Table ────────────────────────────────────────────── */
        .table-wrap{overflow-x:auto;border-radius:8px;border:1px solid var(--border);}
        table{width:100%;border-collapse:collapse;font-size:.875rem;}
        thead th{background:#f8fafc;color:var(--muted);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:10px 13px;border-bottom:1px solid var(--border);white-space:nowrap;}
        tbody tr{border-bottom:1px solid #f1f5f9;}
        tbody tr:last-child{border-bottom:none;}
        tbody tr:hover{background:#f8fafc;}
        tbody td{padding:9px 13px;vertical-align:middle;}
        .pill{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.7rem;font-weight:700;}
        .pill-blue{background:#eff6ff;color:var(--primary);}

        /* ── Search ───────────────────────────────────────────── */
        .search-bar{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
        .search-bar input{border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:.875rem;font-family:inherit;outline:none;flex:1;transition:border-color .2s;}
        .search-bar input:focus{border-color:var(--primary);}

        /* ── Privacy notice ───────────────────────────────────── */
        .privacy-note{background:#f0f9ff;border:1px solid #bae6fd;border-left:4px solid #0891b2;border-radius:10px;padding:11px 14px;margin-bottom:16px;font-size:.8rem;color:#0c4a6e;display:flex;gap:10px;align-items:flex-start;}
        .privacy-note i{color:#0891b2;flex-shrink:0;margin-top:1px;}

        .off-img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%);}
            .main{margin-left:0;max-width:100%;padding:16px;}
        }
    </style>
</head>
<body>

<!-- ── Sidebar ─────────────────────────────────────────────────────── -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="../officials/uploads/residents/<?= htmlspecialchars($me['image_path']) ?>"
             onerror="this.onerror=null; this.src='../officials/uploads/residents/default_photo_male.jpg'" alt="Photo">
        <div class="sb-name"><?= htmlspecialchars($me['first_name'] . ' ' . $me['surname']) ?></div>
        <div class="sb-role">Resident Portal</div>
    </div>
    <nav>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> My Dashboard</a></li>
            <li><a href="dashboard.php#profile"><i class="fas fa-user-circle"></i> My Profile</a></li>
            <li><a href="dashboard.php#edit-request"><i class="fas fa-edit"></i> Request Update</a></li>
            <li><a href="dashboard.php#residents"><i class="fas fa-users"></i> Residents List</a></li>
            <li><a href="dashboard.php#officials"><i class="fas fa-user-tie"></i> Barangay Officials</a></li>
            <li><a href="../officials/logout.php" onclick="return confirm('Log out?')">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a></li>
        </ul>
    </nav>
</aside>

<!-- ── Main ────────────────────────────────────────────────────────── -->
<main class="main">
    <div class="page-title">Welcome, <?= htmlspecialchars($me['first_name']) ?>! 👋</div>
    <div class="page-sub">Barangay Sto. Rosario — Resident Portal</div>

    <?php if ($flash): [$ftype, $fmsg] = explode(':', $flash, 2); ?>
    <div class="flash flash-<?= htmlspecialchars($ftype) ?>">
        <i class="fas fa-<?= $ftype==='success'?'check-circle':($ftype==='warning'?'exclamation-triangle':'times-circle') ?>"></i>
        <?= htmlspecialchars($fmsg) ?>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         MY PROFILE
         ═══════════════════════════════════════════════════════════ -->
    <div class="card" id="profile">
        <div class="card-header">
            <i class="fas fa-user-circle"></i>
            <h5>My Profile</h5>
            <?php if ($has_pending): ?>
            <span style="background:#fffbeb;color:#92400e;font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:12px;border:1px solid #fde68a;">
                <i class="fas fa-clock"></i> Update Pending
            </span>
            <?php endif; ?>
        </div>

        <div class="profile-wrap">
            <div class="profile-photo">
                <img src="../officials/uploads/residents/<?= htmlspecialchars($me['image_path']) ?>"
                     onerror="this.onerror=null; this.src='../officials/uploads/residents/default_photo_male.jpg'" alt="Profile Photo">
            </div>
            <div class="profile-info">
                <div class="profile-name">
                    <?= htmlspecialchars(
                        $me['surname'] . ', ' . $me['first_name'] .
                        ($me['middle_name'] ? ' ' . $me['middle_name'] : '') .
                        ($me['suffix']      ? ', ' . $me['suffix']     : '')
                    ) ?>
                </div>
                <div class="profile-meta">
                    @<?= htmlspecialchars($me['username']) ?> &nbsp;·&nbsp;
                    <?= htmlspecialchars($me['purok'] ?? '—') ?>, <?= htmlspecialchars($me['barangay'] ?? 'Barangay Sto. Rosario') ?>
                </div>

                <div class="profile-grid">
                    <?php
                    // SES badge styling
                    $SES_COLORS = [
                        'Poor'               => 'background:#fee2e2;color:#991b1b',
                        'Low Income'         => 'background:#fff7ed;color:#c2410c',
                        'Lower Middle Income'=> 'background:#fefce8;color:#854d0e',
                        'Middle Income'      => 'background:#f0fdf4;color:#166534',
                        'Upper Middle Income'=> 'background:#eff6ff;color:#1d4ed8',
                        'High Income'        => 'background:#f5f3ff;color:#5b21b6',
                    ];

                    $fields_display = [
                        'Age'                    => $me['age'] ?? '—',
                        'Sex'                    => $me['sex'] ?? '—',
                        'Civil Status'           => $me['civil_status'] ?? '—',
                        'Birthdate'              => $me['birthdate'] ?? '—',
                        'Religion'               => $me['religion'] ?? '—',
                        'Contact No.'            => $me['contact_no'] ?? '—',
                        'Occupation Type'        => $me['occupation_type'] ?? '—',
                        'Occupation'             => $me['occupation'] ?? '—',
                        'Household Position'     => $me['household_position'] ?? '—',
                        'Educational Attainment' => $me['educational_attainment'] ?? '—',
                        'Monthly Income'         => $monthly !== null ? pesoFormat($monthly) : '—',
                        'Annual Income'          => $annual  !== null ? pesoFormat($annual)  : '—',
                        'Purok'                  => $me['purok'] ?? '—',
                        'Barangay'               => $me['barangay'] ?? '—',
                        'PWD'                    => $me['is_pwd'] ?? 'No',
                        'PWD Type'               => ($me['is_pwd'] === 'Yes' && !empty($me['pwd_type']))
                                                       ? $me['pwd_type'] : null,
                    ];

                    foreach ($fields_display as $label => $value):
                        if ($value === null) continue; // skip null (e.g. PWD Type when not PWD)
                    ?>
                    <div class="pf-item">
                        <label><?= $label ?></label>
                        <?php if ($label === 'Socioeconomic Status'): ?>
                            <?php if ($ses && isset($SES_COLORS[$ses])): ?>
                                <span><span class="ses-badge" style="<?= $SES_COLORS[$ses] ?>"><?= htmlspecialchars($ses) ?></span></span>
                            <?php else: ?>
                                <span>—</span>
                            <?php endif; ?>
                        <?php elseif ($label === 'Annual Income'): ?>
                            <span style="font-weight:700;color:var(--success)"><?= htmlspecialchars((string)$value) ?></span>
                        <?php else: ?>
                            <span><?= htmlspecialchars((string)$value) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <!-- Socioeconomic Status — always shown as its own row -->
                    <div class="pf-item" style="grid-column:1/-1">
                        <label>Socioeconomic Status</label>
                        <?php if ($ses && isset($SES_COLORS[$ses])): ?>
                            <span><span class="ses-badge" style="<?= $SES_COLORS[$ses] ?>"><?= htmlspecialchars($ses) ?></span>
                            <small style="font-size:.72rem;color:var(--muted);margin-left:6px">(based on monthly income per PSA FIES 2021)</small></span>
                        <?php else: ?>
                            <span>—</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         REQUEST PROFILE UPDATE
         ═══════════════════════════════════════════════════════════ -->
    <div class="card" id="edit-request">
        <div class="card-header">
            <i class="fas fa-edit"></i>
            <h5>Request Profile Update</h5>
        </div>

        <?php if ($has_pending): ?>
        <div class="pending-notice">
            <i class="fas fa-hourglass-half"></i>
            <div class="pn-text">
                <strong>You have a pending update request</strong>
                <span>Submitted <?= date('M d, Y', strtotime($last_request['created_at'])) ?>. Please wait for admin approval before submitting a new one.</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($last_request && $last_request['status'] === 'Rejected'): ?>
        <div class="flash flash-error" style="margin-bottom:16px">
            <i class="fas fa-times-circle"></i>
            Your last update request was rejected.
            <?php if ($last_request['rejection_reason']): ?>
            <br><strong>Reason:</strong> <?= htmlspecialchars($last_request['rejection_reason']) ?>
            <?php endif; ?>
        </div>
        <?php elseif ($last_request && $last_request['status'] === 'Approved'): ?>
        <div class="flash flash-success" style="margin-bottom:16px">
            <i class="fas fa-check-circle"></i>
            Your last update request was approved on <?= date('M d, Y', strtotime($last_request['reviewed_at'])) ?>.
        </div>
        <?php endif; ?>

        <form method="POST" action="submit_update_request.php" enctype="multipart/form-data"
              <?= $has_pending ? 'style="opacity:.55;pointer-events:none"' : '' ?>>

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="form-grid">

                <!-- ── Contact ──────────────────────────────────── -->
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_no" maxlength="20"
                           value="<?= htmlspecialchars($me['contact_no'] ?? '') ?>"
                           placeholder="e.g. 09XXXXXXXXX">
                </div>

                <!-- ── Civil Status ─────────────────────────────── -->
                <div class="form-group">
                    <label>Civil Status</label>
                    <select name="civil_status">
                        <?php foreach (['Single','Married','Widowed','Divorced','Separated','Annulled'] as $cs): ?>
                        <option value="<?= $cs ?>" <?= ($me['civil_status'] === $cs) ? 'selected' : '' ?>>
                            <?= $cs ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ── Religion ─────────────────────────────────── -->
                <div class="form-group">
                    <label>Religion</label>
                    <input type="text" name="religion" maxlength="100"
                           value="<?= htmlspecialchars($me['religion'] ?? '') ?>">
                </div>

                <!-- ── Suffix ────────────────────────────────────── -->
                <div class="form-group">
                    <label>Suffix <small style="font-weight:400;text-transform:none">(Jr., Sr., III…)</small></label>
                    <input type="text" name="suffix" maxlength="20"
                           value="<?= htmlspecialchars($me['suffix'] ?? '') ?>"
                           placeholder="e.g. Jr., Sr., III">
                </div>

                <!-- ── Purok ─────────────────────────────────────── -->
                <div class="form-group">
                    <label>Purok</label>
                    <select name="purok">
                        <?php foreach (['Purok 1','Purok 2','Purok 3','Purok 4','Purok 5',
                                        'Purok 6','Purok 7','Purok 8','Purok 9','Purok 10'] as $pk): ?>
                        <option value="<?= $pk ?>" <?= ($me['purok'] === $pk) ? 'selected' : '' ?>>
                            <?= $pk ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ── Barangay ──────────────────────────────────── -->
                <div class="form-group">
                    <label>Barangay</label>
                    <select name="barangay">
                        <?php foreach ($BARANGAYS as $bg): ?>
                        <option value="<?= $bg ?>" <?= ($me['barangay'] === $bg) ? 'selected' : '' ?>>
                            <?= $bg ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ── Household Position ────────────────────────── -->
                <div class="form-group">
                    <label>Household Position</label>
                    <select name="household_position">
                        <?php foreach (['Head','Spouse','Son','Daughter','Mother','Father','Grandparent','Other'] as $hp): ?>
                        <option value="<?= $hp ?>" <?= ($me['household_position'] === $hp) ? 'selected' : '' ?>>
                            <?= $hp ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ── Educational Attainment ────────────────────── -->
                <div class="form-group">
                    <label>Educational Attainment</label>
                    <select name="educational_attainment">
                        <?php foreach (['None','Elementary','High School','Senior High','College',
                                        'Vocational','Post Graduate'] as $ea): ?>
                        <option value="<?= $ea ?>" <?= ($me['educational_attainment'] === $ea) ? 'selected' : '' ?>>
                            <?= $ea ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ── Occupation Type ───────────────────────────── -->
                <div class="form-group">
                    <label>Occupation Type</label>
                    <select name="occupation_type" id="occupationTypeSelect"
                            onchange="handleOccupationTypeOther(this)">
                        <?php foreach ($OCCUPATION_TYPES as $ot): ?>
                        <option value="<?= $ot ?>"
                            <?= ($me['occupation_type'] === $ot) ? 'selected' : '' ?>>
                            <?= $ot ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Other specify for occupation type -->
                <div class="form-group" id="occTypeOtherGroup"
                     style="<?= ($me['occupation_type'] === 'Other') ? '' : 'display:none' ?>">
                    <label>Specify Occupation Type</label>
                    <input type="text" name="occupation_type_other" maxlength="100"
                           value="<?= ($me['occupation_type'] === 'Other') ? '' : '' ?>"
                           placeholder="Please specify…">
                </div>

                <!-- ── Occupation / Job Title ────────────────────── -->
                <div class="form-group">
                    <label>Occupation / Job Title</label>
                    <input type="text" name="occupation" maxlength="100"
                           value="<?= htmlspecialchars($me['occupation'] ?? '') ?>"
                           placeholder="e.g. Teacher, Farmer, Student">
                </div>

                <!-- ── Monthly Income ────────────────────────────── -->
                <div class="form-group">
                    <label>Monthly Income (₱)</label>
                    <input type="number" name="monthly_income" id="monthlyIncomeInput"
                           min="0" max="9999999" step="0.01"
                           value="<?= htmlspecialchars($me['monthly_income'] ?? '') ?>"
                           placeholder="0.00"
                           oninput="computeFinancials()">
                </div>

                <!-- ── Annual Income (read-only, auto-computed) ──── -->
                <div class="form-group computed-group">
                    <label>
                        Annual Income (₱)
                        <span style="font-size:.65rem;font-weight:400;color:#0891b2;text-transform:none;margin-left:4px">
                            <i class="fas fa-calculator"></i> Auto-computed
                        </span>
                    </label>
                    <input type="text" id="annualIncomeDisplay"
                           value="<?= $annual !== null ? number_format($annual, 2) : '' ?>"
                           readonly placeholder="Computed from monthly income">
                    <!-- Hidden field carries the actual value for reference only.
                         The server always recalculates from monthly_income. -->
                    <input type="hidden" name="annual_income" id="annualIncomeHidden"
                           value="<?= htmlspecialchars((string)($annual ?? '')) ?>">
                </div>

                <!-- ── SES (read-only, auto-computed) ────────────── -->
                <div class="form-group computed-group">
                    <label>
                        Socioeconomic Status
                        <span style="font-size:.65rem;font-weight:400;color:#0891b2;text-transform:none;margin-left:4px">
                            <i class="fas fa-calculator"></i> PSA FIES 2021
                        </span>
                    </label>
                    <input type="text" id="sesDisplay"
                           value="<?= htmlspecialchars($ses ?? '') ?>"
                           readonly placeholder="Based on monthly income">
                    <!-- Server always re-classifies; this is display-only -->
                </div>

                <!-- ── PWD ───────────────────────────────────────── -->
                <div class="form-group">
                    <label>PWD (Person with Disability)</label>
                    <select name="is_pwd" id="isPwdSelect" onchange="togglePwdType(this)">
                        <option value="No"  <?= ($me['is_pwd'] !== 'Yes') ? 'selected' : '' ?>>No</option>
                        <option value="Yes" <?= ($me['is_pwd'] === 'Yes') ? 'selected' : '' ?>>Yes</option>
                    </select>
                </div>

                <!-- ── New Profile Photo ─────────────────────────── -->
                <div class="form-group">
                    <label>New Profile Photo <small style="font-weight:400;text-transform:none">(optional, max 2MB)</small></label>
                    <input type="file" name="new_photo" accept="image/jpeg,image/png,image/webp">
                </div>

            </div><!-- /form-grid -->

            <!-- ── PWD Type (shown only if PWD = Yes) ─────────────────── -->
            <div class="pwd-section <?= ($me['is_pwd'] === 'Yes') ? 'visible' : '' ?>" id="pwdTypeSection">
                <label><i class="fas fa-wheelchair"></i> Disability Type (Matang sa Kapansanan) *</label>
                <select name="pwd_type" id="pwdTypeSelect">
                    <option value="">— Select Disability Type —</option>
                    <?php foreach ($PWD_TYPES as $val => $label): ?>
                    <option value="<?= htmlspecialchars($val) ?>"
                        <?= ($me['pwd_type'] === $val) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <!-- Other specify -->
                <div id="pwdOtherGroup" style="margin-top:8px;<?= ($me['pwd_type'] === 'Other') ? '' : 'display:none' ?>">
                    <label style="font-size:.75rem;font-weight:600;color:#c2410c;margin-bottom:4px;display:block">
                        Specify Disability <span style="color:var(--danger)">*</span>
                    </label>
                    <input type="text" name="pwd_type_other" maxlength="150"
                           placeholder="Please describe the disability…"
                           style="width:100%;border:1px solid #fed7aa;border-radius:8px;padding:8px 12px;font-size:.875rem;font-family:inherit;outline:none;">
                </div>
            </div>

            <!-- ── Resident Note ────────────────────────────────────── -->
            <div class="form-grid" style="margin-top:16px">
                <div class="form-group form-full">
                    <label>Note to Admin <small style="font-weight:400;text-transform:none">(optional)</small></label>
                    <textarea name="resident_note" maxlength="500"
                              placeholder="Explain why you are requesting this change…"></textarea>
                </div>
            </div>

            <div class="form-note">
                <i class="fas fa-info-circle"></i>
                <strong>Important:</strong> Changes take effect only after admin approval.
                Sensitive information (name, birthdate, sex) can only be changed by the admin.
                Annual income and socioeconomic status are computed automatically from your monthly income.
            </div>

            <button type="submit" class="btn-submit" <?= $has_pending ? 'disabled' : '' ?>>
                <i class="fas fa-paper-plane"></i>
                <?= $has_pending ? 'Request Pending…' : 'Submit Update Request' ?>
            </button>
        </form>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         RESIDENTS LIST
         ═══════════════════════════════════════════════════════════ -->
    <div class="card" id="residents">
        <div class="card-header">
            <i class="fas fa-users"></i>
            <h5>Residents of Barangay Sto. Rosario</h5>
            <span style="background:var(--primary-light);color:var(--primary);font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:12px;">
                <?= count($residents_list) ?> residents
            </span>
        </div>

        <div class="privacy-note">
            <i class="fas fa-shield-alt"></i>
            <span>Only names and purok are shown to protect resident privacy (R.A. 10173 — Data Privacy Act of 2012).</span>
        </div>

        <div class="search-bar">
            <input type="text" id="residentSearch" placeholder="Search by name or purok…"
                   oninput="filterResidents()">
        </div>

        <div class="table-wrap">
            <table id="residentsTable">
                <thead>
                    <tr><th>#</th><th>Full Name</th><th>Purok</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($residents_list as $i => $r): ?>
                    <tr>
                        <td style="color:var(--muted);font-size:.8rem"><?= $i + 1 ?></td>
                        <td style="font-weight:600">
                            <?= htmlspecialchars(
                                $r['surname'] . ', ' . $r['first_name'] .
                                ($r['middle_name'] ? ' ' . substr($r['middle_name'], 0, 1) . '.' : '')
                            ) ?>
                        </td>
                        <td><span class="pill pill-blue"><?= htmlspecialchars($r['purok'] ?? '—') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         BARANGAY OFFICIALS
         ═══════════════════════════════════════════════════════════ -->
    <div class="card" id="officials">
        <div class="card-header">
            <i class="fas fa-user-tie"></i>
            <h5>Barangay Officials</h5>
            <span style="background:var(--primary-light);color:var(--primary);font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:12px;">
                <?= count($officials_list) ?> active
            </span>
        </div>

        <div class="privacy-note">
            <i class="fas fa-shield-alt"></i>
            <span>Officials' names, purok, and positions are public information as they serve the community.</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Photo</th><th>Full Name</th><th>Position</th><th>Purok</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($officials_list as $o): ?>
                    <tr>
                        <td>
                            <img src="../officials/uploads/officials/<?= htmlspecialchars($o['image_path'] ?? 'default_photo_male.jpg') ?>"
                                 class="off-img" alt=""
                                 onerror="this.onerror=null; this.src='../officials/uploads/officials/default_photo_male.jpg'">
                        </td>
                        <td style="font-weight:600">
                            <?= htmlspecialchars(
                                $o['surname'] . ', ' . $o['first_name'] .
                                ($o['middle_name'] ? ' ' . $o['middle_name'] : '')
                            ) ?>
                        </td>
                        <td><span class="pill pill-blue"><?= htmlspecialchars($o['position']) ?></span></td>
                        <td><?= htmlspecialchars($o['purok']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
// ── PSA FIES 2021 thresholds (mirrors PHP) ───────────────────────────────
const SES_THRESHOLDS = [
    [10957,  'Poor'],
    [21914,  'Low Income'],
    [43828,  'Lower Middle Income'],
    [76669,  'Middle Income'],
    [131484, 'Upper Middle Income'],
];
const SES_COLORS = {
    'Poor':               'background:#fee2e2;color:#991b1b',
    'Low Income':         'background:#fff7ed;color:#c2410c',
    'Lower Middle Income':'background:#fefce8;color:#854d0e',
    'Middle Income':      'background:#f0fdf4;color:#166534',
    'Upper Middle Income':'background:#eff6ff;color:#1d4ed8',
    'High Income':        'background:#f5f3ff;color:#5b21b6',
};

function classifySES(monthly) {
    if (monthly === null || isNaN(monthly) || monthly < 0) return null;
    for (const [thresh, label] of SES_THRESHOLDS) {
        if (monthly < thresh) return label;
    }
    return 'High Income';
}

/**
 * Recomputes annual income and SES whenever the monthly income field changes.
 * The server always re-derives these — this is purely for live UX feedback.
 */
function computeFinancials() {
    const rawVal  = document.getElementById('monthlyIncomeInput').value.trim();
    const monthly = rawVal !== '' ? parseFloat(rawVal) : null;

    const annualDisplay = document.getElementById('annualIncomeDisplay');
    const annualHidden  = document.getElementById('annualIncomeHidden');
    const sesDisplay    = document.getElementById('sesDisplay');

    if (monthly !== null && !isNaN(monthly) && monthly >= 0) {
        const annual = monthly * 12;
        const ses    = classifySES(monthly);

        annualDisplay.value = annual.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        annualHidden.value  = annual.toFixed(2);
        sesDisplay.value    = ses ?? '';

        // Colour-code the SES display field for visual feedback
        const sc = ses ? SES_COLORS[ses] : '';
        // Apply inline style to the wrapping group's input
        if (sc) {
            sesDisplay.style.cssText = sc.replace(/;/g, ';') + ';font-weight:700;border-radius:8px;';
        }
    } else {
        annualDisplay.value = '';
        annualHidden.value  = '';
        sesDisplay.value    = '';
        sesDisplay.style.cssText = '';
    }
}

// ── PWD Type toggle ───────────────────────────────────────────────────────
function togglePwdType(select) {
    const section = document.getElementById('pwdTypeSection');
    if (select.value === 'Yes') {
        section.classList.add('visible');
    } else {
        section.classList.remove('visible');
        document.getElementById('pwdTypeSelect').value = '';
        const otherGroup = document.getElementById('pwdOtherGroup');
        if (otherGroup) otherGroup.style.display = 'none';
    }
}

// Show/hide "Other specify" for PWD type
const pwdTypeSelect = document.getElementById('pwdTypeSelect');
if (pwdTypeSelect) {
    pwdTypeSelect.addEventListener('change', function () {
        const otherGroup = document.getElementById('pwdOtherGroup');
        otherGroup.style.display = this.value === 'Other' ? 'block' : 'none';
    });
}

// ── Occupation Type "Other" ───────────────────────────────────────────────
function handleOccupationTypeOther(select) {
    const otherGroup = document.getElementById('occTypeOtherGroup');
    otherGroup.style.display = select.value === 'Other' ? '' : 'none';
}

// ── Residents search ──────────────────────────────────────────────────────
function filterResidents() {
    const q = document.getElementById('residentSearch').value.toLowerCase().replace(/[<>]/g, '');
    document.querySelectorAll('#residentsTable tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// Sanitize search input (XSS prevention)
document.getElementById('residentSearch').addEventListener('input', function () {
    this.value = this.value.replace(/[<>]/g, '');
});

// ── Init: compute financials on page load to populate display fields ───────
computeFinancials();
</script>
</body>
</html>