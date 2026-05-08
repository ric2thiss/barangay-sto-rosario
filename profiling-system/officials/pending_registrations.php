<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php"); exit();
}
$allowed_types = ['admin', 'staff', 'official'];
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    header("Location: ../resident/dashboard.php"); exit();
}
$is_superadmin = ($_SESSION['user_type'] === 'admin')
    || (!empty($_SESSION['is_superadmin']));

include("connection.php");
include('sidebar_counts.php');
// ── Counts ────────────────────────────────────────────────────────────────
$count_pending  = (int)$conn->query("SELECT COUNT(*) c FROM pending_registrations WHERE status='Pending'")->fetch_assoc()['c'];
$count_approved = (int)$conn->query("SELECT COUNT(*) c FROM pending_registrations WHERE status='Approved'")->fetch_assoc()['c'];
$count_rejected = (int)$conn->query("SELECT COUNT(*) c FROM pending_registrations WHERE status='Rejected'")->fetch_assoc()['c'];

// ── Filters ───────────────────────────────────────────────────────────────
$tab    = in_array($_GET['tab'] ?? '', ['pending','approved','rejected']) ? $_GET['tab'] : 'pending';
$search = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';
$page   = max(1, intval($_GET['page'] ?? 1));
$per    = 15;
$offset = ($page - 1) * $per;

$status_map = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'];
$status_val = $status_map[$tab];

$where = ["status='$status_val'"];
if ($search !== '') {
    $where[] = "(first_name LIKE '%$search%' OR surname LIKE '%$search%' OR middle_name LIKE '%$search%' OR username LIKE '%$search%' OR purok LIKE '%$search%')";
}
$where_sql = 'WHERE '.implode(' AND ', $where);

$total  = (int)$conn->query("SELECT COUNT(*) c FROM pending_registrations $where_sql")->fetch_assoc()['c'];
$pages  = max(1, ceil($total / $per));

// ── SELECT all fields that exist in register_account.php ─────────────────
$result = $conn->query("
    SELECT
        id, username,
        first_name, middle_name, surname, suffix,
        birthdate, birthplace, age, sex, civil_status, nationality,
        religion, ethnicity, blood_type, height, weight, philhealth_no, length_of_residency,
        household_no, purok, barangay, municipality, province,
        voters_status, educational_attainment, total_household,
        grade_level, school_name,
        course, course_other, graduation_date, eligibility, eligibility_other,
        is_pwd, pwd_type, is_deceased, date_of_death, is_newborn,
        contact_no, email, occupation_type, occupation,
        father_name, father_occupation, mother_name, mother_occupation,
        monthly_income, annual_income, socioeconomic_status, household_position,
        house_ownership, house_material, toilet_type, water_source,
        is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker,
        has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health,
        membership_type, family_planning,
        image_path,
        status, rejection_reason, reviewed_at, reviewed_by, created_at
    FROM pending_registrations
    $where_sql
    ORDER BY created_at DESC
    LIMIT $per OFFSET $offset
");
$rows = [];
if ($result) while ($r = $result->fetch_assoc()) $rows[] = $r;



// ── URL builder ───────────────────────────────────────────────────────────
function purl($tab, $q, $page) {
    $u = "?tab=$tab";
    if ($q !== '') $u .= "&q=".urlencode($q);
    if ($page > 1) $u .= "&page=$page";
    return $u;
}

// ── PSA-based SES label (mirrors register_account.php) ───────────────────
function sesLabel(?float $monthly): string {
    if ($monthly === null || $monthly < 0) return '—';
    if ($monthly < 10957)  return 'Poor';
    if ($monthly < 21914)  return 'Low Income';
    if ($monthly < 43828)  return 'Lower Middle Income';
    if ($monthly < 76669)  return 'Middle Income';
    if ($monthly < 131484) return 'Upper Middle Income';
    return 'High Income';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Registrations — Barangay Sto. Rosario</title>
    <?php include 'hybrid_assets.php'; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        :root {
            --primary:#1a56db; --primary-light:#e8f0fe;
            --success:#0e9f6e; --danger:#e02424; --warning:#ff8a00; --info:#0891b2;
            --pending-c:#d97706;
            --sidebar-bg:#0f172a; --sidebar-w:250px;
            --body-bg:#f1f5f9; --card-bg:#fff;
            --border:#e2e8f0; --text:#1e293b; --muted:#64748b;
            --radius:12px; --shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--body-bg);color:var(--text);display:flex;min-height:100vh;}

        /* ── Sidebar ─────────────────────────────────────────────────── */
        .sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--sidebar-bg);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100;overflow-y:auto;}
        .sidebar-brand{padding:28px 20px 20px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;align-items:center;gap:10px;}
        .sidebar-brand img{width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.15);}
        .sidebar-brand h2{color:#fff;font-size:.95rem;font-weight:700;text-align:center;}
        .sidebar nav{padding:16px 12px;flex:1;}
        .sidebar nav ul{list-style:none;display:flex;flex-direction:column;gap:4px;}
        .sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;color:rgba(255,255,255,.65);text-decoration:none;font-size:.875rem;font-weight:500;transition:background .15s,color .15s;}
        .sidebar nav a:hover,.sidebar nav a.active{background:rgba(255,255,255,.1);color:#fff;}
        .sidebar nav a.active{background:var(--primary);}
        .sidebar nav a i{width:18px;text-align:center;font-size:.9rem;}
        .nav-badge{margin-left:auto;background:#e02424;color:#fff;font-size:.68rem;font-weight:800;padding:1px 7px;border-radius:20px;}

        /* ── Main ────────────────────────────────────────────────────── */
        .main-content{margin-left:var(--sidebar-w);flex:1;padding:28px 28px 48px;max-width:calc(100% - var(--sidebar-w));}
        .page-header{margin-bottom:24px;}
        .page-header h1{font-size:1.5rem;font-weight:800;color:#111827;}
        .page-header p{font-size:.85rem;color:var(--muted);margin-top:3px;}

        /* Flash */
        .flash{border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:.875rem;font-weight:600;}
        .flash-success{background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46;}
        .flash-error  {background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;}

        /* Stat chips */
        .stat-row{display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap;}
        .stat-chip{display:flex;align-items:center;gap:12px;background:var(--card-bg);border:1px solid var(--border);border-radius:10px;padding:14px 18px;box-shadow:var(--shadow);flex:1;min-width:130px;}
        .stat-chip .ico{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0;}
        .stat-chip .val{font-size:1.5rem;font-weight:800;line-height:1;color:#111827;}
        .stat-chip .lbl{font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;}
        .chip-pending  .ico{background:#fffbeb;color:#d97706;}
        .chip-approved .ico{background:#ecfdf5;color:var(--success);}
        .chip-rejected .ico{background:#fef2f2;color:var(--danger);}

        /* Tab bar */
        .tab-bar{display:flex;gap:4px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:6px;margin-bottom:18px;box-shadow:var(--shadow);}
        .tab-btn{flex:1;text-align:center;padding:9px 12px;border-radius:8px;border:none;background:transparent;font-size:.875rem;font-weight:600;color:var(--muted);cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:7px;transition:all .15s;}
        .tab-btn:hover{background:#f8fafc;color:var(--text);}
        .tab-btn.active-pending {background:#d97706;color:#fff;}
        .tab-btn.active-approved{background:var(--success);color:#fff;}
        .tab-btn.active-rejected{background:var(--danger);color:#fff;}
        .tab-cnt{padding:1px 7px;border-radius:12px;font-size:.7rem;font-weight:800;}
        .tab-btn.active-pending .tab-cnt,.tab-btn.active-approved .tab-cnt,.tab-btn.active-rejected .tab-cnt{background:rgba(255,255,255,.25);}
        .tab-btn:not([class*=active]) .tab-cnt{background:#f1f5f9;color:var(--muted);}

        /* Toolbar */
        .toolbar{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;box-shadow:var(--shadow);}
        .search-box{flex:1;min-width:200px;position:relative;}
        .search-box input{width:100%;border:1px solid var(--border);border-radius:8px;padding:8px 12px 8px 36px;font-size:.875rem;color:var(--text);font-family:inherit;outline:none;transition:border-color .2s;}
        .search-box input:focus{border-color:var(--primary);}
        .search-box i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.85rem;}
        .btn-sm{padding:8px 16px;border-radius:8px;border:none;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:opacity .15s;}
        .btn-sm:hover{opacity:.88;}
        .btn-primary-sm{background:var(--primary);color:#fff;}
        .btn-muted-sm{background:#f1f5f9;color:var(--muted);border:1px solid var(--border);}

        /* Card / Table */
        .card{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;}
        .table-wrap{overflow-x:auto;}
        table{width:100%;border-collapse:collapse;font-size:.875rem;}
        thead th{background:#f8fafc;color:var(--muted);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:11px 14px;border-bottom:1px solid var(--border);white-space:nowrap;}
        tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s;}
        tbody tr:last-child{border-bottom:none;}
        tbody tr:hover{background:#f8fafc;}
        tbody td{padding:10px 14px;vertical-align:middle;}
        .avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}

        /* Pills */
        .pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap;}
        .pill-pending {background:#fffbeb;color:#92400e;border:1px solid #fde68a;}
        .pill-approved{background:#ecfdf5;color:#065f46;}
        .pill-rejected{background:#fef2f2;color:#991b1b;}
        .pill-blue    {background:#eff6ff;color:var(--primary);}
        .pill-orange  {background:#fff7ed;color:#c2410c;}
        .pill-gray    {background:#f1f5f9;color:var(--muted);}

        /* Action buttons */
        .act{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:7px;border:none;font-size:.75rem;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;transition:opacity .15s;}
        .act:hover{opacity:.85;}
        .act-view    {background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;}
        .act-approve {background:#ecfdf5;color:#065f46;border:1px solid #6ee7b7;}
        .act-reject  {background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;}
        .act-undo    {background:#f8fafc;color:var(--muted);border:1px solid var(--border);}

        /* Empty state */
        .empty{padding:52px 24px;text-align:center;color:var(--muted);}
        .empty i{font-size:2.5rem;opacity:.2;display:block;margin-bottom:14px;}

        /* Pagination */
        .pager{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px;}
        .pager-info{font-size:.82rem;color:var(--muted);}
        .pager-btns{display:flex;gap:4px;}
        .pg{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid var(--border);background:var(--card-bg);color:var(--text);font-size:.82rem;font-weight:600;text-decoration:none;transition:background .15s;}
        .pg:hover{background:#f1f5f9;}
        .pg.current{background:var(--primary);color:#fff;border-color:var(--primary);}
        .pg.disabled{opacity:.35;pointer-events:none;}

        /* ── View Modal ──────────────────────────────────────────────── */
        .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:20px;}
        .overlay.open{display:flex;}
        .mbox{background:#fff;border-radius:16px;width:100%;max-width:780px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:mIn .18s ease;}
        @keyframes mIn{from{transform:scale(.96);opacity:0}to{transform:scale(1);opacity:1}}
        .mhead{padding:20px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;position:sticky;top:0;background:#fff;z-index:2;}
        .mhead h5{font-size:1.05rem;font-weight:700;margin:0;flex:1;}
        .mclose{background:none;border:none;font-size:1.15rem;color:var(--muted);cursor:pointer;line-height:1;padding:4px;}
        .mclose:hover{color:var(--danger);}
        .mbody{padding:20px 24px;}
        .mfoot{padding:14px 24px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;position:sticky;bottom:0;background:#fff;z-index:2;}

        /* Profile row */
        .p-row{display:flex;align-items:center;gap:16px;padding-bottom:18px;margin-bottom:18px;border-bottom:1px solid var(--border);}
        .p-row img{width:80px;height:80px;border-radius:12px;object-fit:cover;border:2px solid var(--border);}
        .p-row .pname{font-size:1.1rem;font-weight:800;}
        .p-row .psub{font-size:.8rem;color:var(--muted);margin-top:3px;}

        /* Section headers inside modal */
        .msec{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin:18px 0 10px;padding-bottom:5px;border-bottom:1px solid var(--border);}
        .msec:first-of-type{margin-top:0;}

        /* Info grid */
        .ig{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
        .ig2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
        .ig-item label{display:block;font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;}
        .ig-item span{font-size:.875rem;color:var(--text);font-weight:500;}
        .ig-full{grid-column:1/-1;}

        /* Flag badges row */
        .flag-row{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px;}

        /* Yes/No badges */
        .yn-yes{display:inline-block;padding:2px 9px;border-radius:12px;font-size:.7rem;font-weight:700;background:#ecfdf5;color:#065f46;}
        .yn-no {display:inline-block;padding:2px 9px;border-radius:12px;font-size:.7rem;font-weight:700;background:#f1f5f9;color:var(--muted);}

        /* Rejection box */
        .rej-box{background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px;margin-top:14px;}
        .rej-box label{display:block;font-size:.82rem;font-weight:700;color:#991b1b;margin-bottom:7px;}
        textarea.mta{width:100%;border:1px solid var(--border);border-radius:8px;padding:10px 12px;font-size:.875rem;font-family:inherit;resize:vertical;min-height:90px;outline:none;transition:border-color .2s;}
        textarea.mta:focus{border-color:var(--danger);}
        textarea.mta.err{border-color:var(--danger);}

        /* Modal action buttons */
        .mbtn{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:9px;border:none;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s;}
        .mbtn:hover{opacity:.88;}
        .mbtn-approve{background:var(--success);color:#fff;}
        .mbtn-reject {background:var(--danger);color:#fff;}
        .mbtn-cancel {background:#f1f5f9;color:var(--text);border:1px solid var(--border);}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%);}
            .main-content{margin-left:0;max-width:100%;padding:16px;}
            .ig{grid-template-columns:1fr 1fr;}
            .ig2{grid-template-columns:1fr;}
        }
        @media(max-width:480px){
            .ig{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>

<!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
<?php $current_page = 'pending'; include 'sidebar.php'; ?>

<!-- ── Main ───────────────────────────────────────────────────────────── -->
<main class="main-content">

    <div class="page-header">
        <h1><i class="fas fa-user-clock" style="color:var(--pending-c)"></i> Resident Registration Approvals</h1>
        <p>Review sign-ups and approve or reject based on Sto. Rosario residency verification</p>
    </div>

    <!-- Flash -->
    <?php
    $flash_messages = [
        'approved'  => ['success', 'Registration approved. Resident can now log in.'],
        'rejected'  => ['success', 'Registration rejected. Reason has been recorded.'],
        'undone'    => ['success', 'Decision reversed. Registration is now Pending again.'],
        'already'   => ['success', 'This registration was already approved.'],
        'notfound'  => ['error',   'Registration record not found.'],
        'duplicate' => ['error',   'Username already exists in the residents table. Cannot approve.'],
        'noreason'  => ['error',   'Rejection reason is required.'],
        'invalid'   => ['error',   'Invalid action.'],
    ];
    $flash_key = $_GET['success'] ?? $_GET['error'] ?? '';
    if ($flash_key && isset($flash_messages[$flash_key])):
        [$ftype, $fmsg] = $flash_messages[$flash_key];
    ?>
    <div class="flash flash-<?= $ftype ?>">
        <i class="fas fa-<?= $ftype==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($fmsg) ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stat-row">
        <div class="stat-chip chip-pending">
            <div class="ico"><i class="fas fa-hourglass-half"></i></div>
            <div><div class="val"><?= $count_pending ?></div><div class="lbl">Pending</div></div>
        </div>
        <div class="stat-chip chip-approved">
            <div class="ico"><i class="fas fa-check-circle"></i></div>
            <div><div class="val"><?= $count_approved ?></div><div class="lbl">Approved</div></div>
        </div>
        <div class="stat-chip chip-rejected">
            <div class="ico"><i class="fas fa-times-circle"></i></div>
            <div><div class="val"><?= $count_rejected ?></div><div class="lbl">Rejected</div></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
        <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $t=>$l):
            $cnt   = ${'count_'.$t};
            $icons = ['pending'=>'hourglass-half','approved'=>'check-circle','rejected'=>'times-circle'];
            $act   = $tab===$t ? "active-$t" : '';
        ?>
        <a href="<?= purl($t,$search,1) ?>" class="tab-btn <?= $act ?>">
            <i class="fas fa-<?= $icons[$t] ?>"></i> <?= $l ?>
            <span class="tab-cnt"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Toolbar -->
    <form method="GET" action="" class="toolbar">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search name, username, purok…" autocomplete="off">
        </div>
        <button type="submit" class="btn-sm btn-primary-sm"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?>
            <a href="<?= purl($tab,'',1) ?>" class="btn-sm btn-muted-sm"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Purok / Barangay</th>
                        <th>Age / Sex</th>
                        <th>Occupation Type</th>
                        <th>SES</th>
                        <th>Submitted</th>
                        <?php if ($tab==='rejected'): ?><th>Reason</th><?php endif; ?>
                        <?php if ($tab!=='pending'):  ?><th>Reviewed</th><?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="11">
                        <div class="empty">
                            <i class="fas fa-<?= $tab==='pending'?'hourglass-half':($tab==='approved'?'check-circle':'times-circle') ?>"></i>
                            <p>No <?= $status_val ?> registrations<?= $search?' matching your search':'' ?>.</p>
                        </div>
                    </td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <img src="uploads/residents/<?= htmlspecialchars($r['image_path']??'default_photo_male.jpg') ?>"
                                 class="avatar" alt=""
                                 onerror="this.onerror=null; this.src='uploads/residents/default_photo_male.jpg'">
                        </td>
                        <td style="font-weight:700">
                            <?= htmlspecialchars(
                                $r['surname'].', '.
                                $r['first_name'].
                                ($r['middle_name'] ? ' '.$r['middle_name'] : '').
                                ($r['suffix']      ? ' '.$r['suffix']      : '')
                            ) ?>
                        </td>
                        <td style="color:var(--muted);font-size:.82rem">@<?= htmlspecialchars($r['username']) ?></td>
                        <td>
                            <span class="pill pill-blue"><?= htmlspecialchars($r['purok']) ?></span>
                            <br><span style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($r['barangay']) ?></span>
                        </td>
                        <td><?= (int)$r['age'] ?> / <?= htmlspecialchars($r['sex']) ?></td>
                        <td style="font-size:.82rem"><?= htmlspecialchars($r['occupation_type'] ?? '—') ?></td>
                        <td>
                            <?php
                            $ses = $r['socioeconomic_status'] ?? null;
                            if (!$ses && $r['monthly_income'] !== null)
                                $ses = sesLabel((float)$r['monthly_income']);
                            $sesColors = [
                                'Poor'               => '#fee2e2;color:#991b1b',
                                'Low Income'         => '#fff7ed;color:#c2410c',
                                'Lower Middle Income'=> '#fefce8;color:#854d0e',
                                'Middle Income'      => '#f0fdf4;color:#166534',
                                'Upper Middle Income'=> '#eff6ff;color:#1d4ed8',
                                'High Income'        => '#f5f3ff;color:#5b21b6',
                            ];
                            $sc = isset($sesColors[$ses]) ? $sesColors[$ses] : '#f1f5f9;color:#64748b';
                            echo $ses
                                ? '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:.68rem;font-weight:700;background:'.$sc.'">'.htmlspecialchars($ses).'</span>'
                                : '<span style="color:var(--muted);font-size:.8rem">—</span>';
                            ?>
                        </td>
                        <td style="font-size:.8rem;color:var(--muted)">
                            <?= date('M d, Y', strtotime($r['created_at'])) ?><br>
                            <span style="font-size:.72rem"><?= date('h:i A', strtotime($r['created_at'])) ?></span>
                        </td>
                        <?php if ($tab==='rejected'): ?>
                        <td style="font-size:.78rem;color:#991b1b;max-width:180px">
                            <?= $r['rejection_reason']
                                ? htmlspecialchars(mb_substr($r['rejection_reason'],0,80)).(mb_strlen($r['rejection_reason'])>80?'…':'')
                                : '—' ?>
                        </td>
                        <?php endif; ?>
                        <?php if ($tab!=='pending'): ?>
                        <td style="font-size:.78rem;color:var(--muted)">
                            <?= $r['reviewed_at'] ? date('M d, Y', strtotime($r['reviewed_at'])) : '—' ?>
                            <?= $r['reviewed_by'] ? '<br><span style="font-size:.7rem">by '.htmlspecialchars($r['reviewed_by']).'</span>' : '' ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <div style="display:flex;gap:5px;flex-wrap:wrap">
                                <button class="act act-view" onclick='openView(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)'>
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <?php if ($tab==='pending'): ?>
                                    <button class="act act-approve" onclick="doApprove(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['first_name'].' '.$r['surname'])) ?>')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="act act-reject" onclick="openReject(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['first_name'].' '.$r['surname'])) ?>')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php elseif ($tab==='approved'): ?>
                                    <button class="act act-undo" onclick="doUndo(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['first_name'].' '.$r['surname'])) ?>', 'approved')">
                                        <i class="fas fa-undo"></i> Revert
                                    </button>
                                <?php elseif ($tab==='rejected'): ?>
                                    <button class="act act-approve" onclick="doApprove(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['first_name'].' '.$r['surname'])) ?>')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="act act-undo" onclick="doUndo(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['first_name'].' '.$r['surname'])) ?>', 'rejected')">
                                        <i class="fas fa-undo"></i> Set Pending
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="pager">
            <span class="pager-info">Showing <?= min(($page-1)*$per+1,$total) ?>–<?= min($page*$per,$total) ?> of <?= $total ?></span>
            <div class="pager-btns">
                <a href="<?= purl($tab,$search,$page-1) ?>" class="pg <?= $page<=1?'disabled':'' ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?>
                <a href="<?= purl($tab,$search,$i) ?>" class="pg <?= $i===$page?'current':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="<?= purl($tab,$search,$page+1) ?>" class="pg <?= $page>=$pages?'disabled':'' ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
        <?php endif; ?>
    </div>

</main>

<!-- ══════════════════════════════════════════════════
     VIEW MODAL — full field set matching register_account.php
     ══════════════════════════════════════════════════ -->
<div class="overlay" id="viewOverlay">
    <div class="mbox">
        <div class="mhead">
            <i class="fas fa-id-card" style="color:var(--primary)"></i>
            <h5>Resident Details</h5>
            <button class="mclose" onclick="closeModal('viewOverlay')"><i class="fas fa-times"></i></button>
        </div>
        <div class="mbody" id="viewBody"></div>
        <div class="mfoot" id="viewFoot">
            <button class="mbtn mbtn-cancel" onclick="closeModal('viewOverlay')">Close</button>
        </div>
    </div>
</div>

<!-- REJECT MODAL -->
<div class="overlay" id="rejectOverlay">
    <div class="mbox" style="max-width:480px">
        <div class="mhead">
            <i class="fas fa-times-circle" style="color:var(--danger)"></i>
            <h5>Reject Registration</h5>
            <button class="mclose" onclick="closeModal('rejectOverlay')"><i class="fas fa-times"></i></button>
        </div>
        <div class="mbody">
            <p style="font-size:.875rem;color:var(--muted);margin-bottom:4px">
                Rejecting registration of <strong id="rejectName"></strong>.
            </p>
            <p style="font-size:.82rem;color:var(--muted);margin-bottom:14px">
                The record will be kept for audit purposes. The resident will not be able to log in.
            </p>
            <div class="rej-box">
                <label><i class="fas fa-comment-alt"></i> Reason for Rejection <span style="color:var(--danger)">*</span></label>
                <textarea class="mta" id="rejectReason"
                    placeholder="e.g., Cannot verify residency in Sto. Rosario, Incomplete information, Duplicate registration…"></textarea>
                <small style="color:#991b1b;font-size:.75rem;display:block;margin-top:6px">
                    Required. This will be shown to the resident if they attempt to log in again.
                </small>
            </div>
        </div>
        <div class="mfoot">
            <button class="mbtn mbtn-cancel" onclick="closeModal('rejectOverlay')">Cancel</button>
            <button class="mbtn mbtn-reject" onclick="submitReject()">
                <i class="fas fa-times-circle"></i> Confirm Rejection
            </button>
        </div>
    </div>
</div>

<!-- Hidden POST forms -->
<form id="fApprove" method="POST" action="approve_registration.php" style="display:none">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="id"  id="fApproveId">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
    <input type="hidden" name="q"   value="<?= htmlspecialchars($search) ?>">
</form>
<form id="fReject" method="POST" action="approve_registration.php" style="display:none">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="id"               id="fRejectId">
    <input type="hidden" name="rejection_reason" id="fRejectReason">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
    <input type="hidden" name="q"   value="<?= htmlspecialchars($search) ?>">
</form>
<form id="fUndo" method="POST" action="approve_registration.php" style="display:none">
    <input type="hidden" name="action" value="undo">
    <input type="hidden" name="id"  id="fUndoId">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
    <input type="hidden" name="q"   value="<?= htmlspecialchars($search) ?>">
</form>

<script>
// ── Modal helpers ─────────────────────────────────────────────────────────
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.overlay').forEach(o=>{
    o.addEventListener('click', e=>{ if(e.target===o) o.classList.remove('open'); });
});

// ── XSS-safe escaper ──────────────────────────────────────────────────────
function esc(s){
    if(s==null||s===''||s==='0') return '—';
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function raw(s){ return s==null?'':String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ── Yes/No badge ──────────────────────────────────────────────────────────
function yn(v, label){
    const yes = v==='Yes'||v===1||v==='1';
    return `<span class="${yes?'yn-yes':'yn-no'}">${yes?(label||'Yes'):'No'}</span>`;
}

// ── Currency formatter ────────────────────────────────────────────────────
function peso(v){
    if(v===null||v===''||v==='0'||v===undefined) return '—';
    return '₱' + Number(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
}

// ── SES colour map ────────────────────────────────────────────────────────
const SES_COLORS = {
    'Poor':               'background:#fee2e2;color:#991b1b',
    'Low Income':         'background:#fff7ed;color:#c2410c',
    'Lower Middle Income':'background:#fefce8;color:#854d0e',
    'Middle Income':      'background:#f0fdf4;color:#166534',
    'Upper Middle Income':'background:#eff6ff;color:#1d4ed8',
    'High Income':        'background:#f5f3ff;color:#5b21b6',
};
function sesBadge(v){
    if(!v) return '—';
    const s = SES_COLORS[v]||'background:#f1f5f9;color:#64748b';
    return `<span style="display:inline-block;padding:3px 10px;border-radius:10px;font-size:.72rem;font-weight:700;${s}">${raw(v)}</span>`;
}

// ── View resident ─────────────────────────────────────────────────────────
function openView(r) {
    const pillClass = r.status==='Approved'?'pill-approved':r.status==='Rejected'?'pill-rejected':'pill-pending';
    const pillIcon  = r.status==='Approved'?'✅':r.status==='Rejected'?'❌':'⏳';

    // Full name with suffix
    const fullName = [r.first_name, r.middle_name, r.surname].filter(Boolean).join(' ')
                   + (r.suffix ? ', ' + r.suffix : '');

    // Annual income: use stored value, or compute from monthly × 12
    const annual = r.annual_income
        ? r.annual_income
        : (r.monthly_income ? (parseFloat(r.monthly_income) * 12).toFixed(2) : null);

    // SES: use stored, or derive label for display only
    const sesLabels = [
        [10957,'Poor'],[21914,'Low Income'],[43828,'Lower Middle Income'],
        [76669,'Middle Income'],[131484,'Upper Middle Income']
    ];
    let ses = r.socioeconomic_status;
    if(!ses && r.monthly_income){
        const m = parseFloat(r.monthly_income);
        ses = 'High Income';
        for(const [thresh, lbl] of sesLabels){ if(m < thresh){ ses=lbl; break; } }
    }

    document.getElementById('viewBody').innerHTML = `
        <!-- Profile header -->
        <div class="p-row">
            <img src="uploads/residents/${raw(r.image_path||'default_photo_male.jpg')}"
                 onerror="this.onerror=null; this.src='uploads/residents/default_photo_male.jpg'" alt="">
            <div>
                <div class="pname">${raw(fullName)}</div>
                <div class="psub">@${raw(r.username)} &nbsp;·&nbsp; <span class="pill ${pillClass}">${pillIcon} ${raw(r.status)}</span></div>
                <div class="psub" style="margin-top:5px">
                    <i class="fas fa-calendar-alt"></i> Submitted ${raw(r.created_at ? r.created_at.substring(0,10) : '—')}
                </div>
            </div>
        </div>

        <!-- PERSONAL -->
        <div class="msec"><i class="fas fa-user"></i> Personal Information</div>
        <div class="ig">
            <div class="ig-item"><label>First Name</label><span>${esc(r.first_name)}</span></div>
            <div class="ig-item"><label>Middle Name</label><span>${esc(r.middle_name)}</span></div>
            <div class="ig-item"><label>Surname</label><span>${esc(r.surname)}</span></div>
            <div class="ig-item"><label>Suffix</label><span>${esc(r.suffix)}</span></div>
            <div class="ig-item"><label>Birthdate</label><span>${esc(r.birthdate)}</span></div>
            <div class="ig-item"><label>Birthplace</label><span>${esc(r.birthplace)}</span></div>
            <div class="ig-item"><label>Age</label><span>${esc(r.age)}</span></div>
            <div class="ig-item"><label>Sex</label><span>${esc(r.sex)}</span></div>
            <div class="ig-item"><label>Civil Status</label><span>${esc(r.civil_status)}</span></div>
            <div class="ig-item"><label>Nationality</label><span>${esc(r.nationality)}</span></div>
            <div class="ig-item"><label>Religion</label><span>${esc(r.religion)}</span></div>
            <div class="ig-item"><label>Ethnicity</label><span>${esc(r.ethnicity)}</span></div>
            <div class="ig-item"><label>Blood Type</label><span>${esc(r.blood_type)}</span></div>
            <div class="ig-item"><label>PhilHealth No.</label><span>${esc(r.philhealth_no)}</span></div>
            <div class="ig-item"><label>Years of Residency</label><span>${r.length_of_residency!=null?esc(r.length_of_residency)+' yr(s)':'—'}</span></div>
        </div>

        <!-- ADDRESS -->
        <div class="msec"><i class="fas fa-map-marker-alt"></i> Address</div>
        <div class="ig">
            <div class="ig-item"><label>Household No.</label><span>${esc(r.household_no)}</span></div>
            <div class="ig-item"><label>Purok</label><span>${esc(r.purok)}</span></div>
            <div class="ig-item"><label>Barangay</label><span>${esc(r.barangay)}</span></div>
            <div class="ig-item"><label>Municipality</label><span>${esc(r.municipality)}</span></div>
            <div class="ig-item"><label>Province</label><span>${esc(r.province)}</span></div>
            <div class="ig-item"><label>Total Household Members</label><span>${esc(r.total_household)}</span></div>
        </div>

        <!-- VOTER / EDUCATION -->
        <div class="msec"><i class="fas fa-graduation-cap"></i> Education &amp; Voter Info</div>
        <div class="ig">
            <div class="ig-item"><label>Voter Status</label><span>${yn(r.voters_status,'Registered Voter')}</span></div>
            <div class="ig-item"><label>Educational Attainment</label><span>${esc(r.educational_attainment)}</span></div>
            <div class="ig-item"><label>Grade / Year Level</label><span>${esc(r.grade_level)}</span></div>
            <div class="ig-full ig-item"><label>School Name</label><span>${esc(r.school_name)}</span></div>
            ${r.course ? '<div class="ig-full ig-item"><label>Course</label><span>' + esc(r.course === 'Others' && r.course_other ? r.course_other : r.course) + '</span></div>' : ''}
            ${r.graduation_date ? '<div class="ig-item"><label>Date of Graduation</label><span>' + esc(r.graduation_date) + '</span></div>' : ''}
            ${r.eligibility ? '<div class="ig-item"><label>Eligibility</label><span>' + esc(r.eligibility === 'Others' && r.eligibility_other ? r.eligibility_other : r.eligibility) + '</span></div>' : ''}
        </div>

        <!-- OCCUPATION & FINANCE -->
        <div class="msec"><i class="fas fa-briefcase"></i> Occupation &amp; Financial</div>
        <div class="ig">
            <div class="ig-item"><label>Occupation Type</label><span>${esc(r.occupation_type)}</span></div>
            <div class="ig-item"><label>Occupation / Job Title</label><span>${esc(r.occupation)}</span></div>
            <div class="ig-item"><label>Household Position</label><span>${esc(r.household_position)}</span></div>
            <div class="ig-item"><label>Monthly Income</label><span>${peso(r.monthly_income)}</span></div>
            <div class="ig-item"><label>Annual Income</label><span>${peso(annual)}</span></div>
            <div class="ig-item"><label>Socioeconomic Status</label><span>${sesBadge(ses)}</span></div>
        </div>

        <!-- HOUSING -->
        <div class="msec"><i class="fas fa-home"></i> Housing</div>
        <div class="ig2">
            <div class="ig-item"><label>House Ownership</label><span>${esc(r.house_ownership)}</span></div>
            <div class="ig-item"><label>House Material</label><span>${esc(r.house_material)}</span></div>
            <div class="ig-item"><label>Toilet Type</label><span>${esc(r.toilet_type)}</span></div>
            <div class="ig-item"><label>Water Source</label><span>${esc(r.water_source)}</span></div>
        </div>

        <!-- STATUS FLAGS -->
        <div class="msec"><i class="fas fa-tags"></i> Status &amp; Classification</div>
        <div class="ig2">
            <div class="ig-item">
                <label>PWD</label>
                <span>${yn(r.is_pwd)}${r.is_pwd==='Yes'&&r.pwd_type?' &nbsp;<span class="pill pill-orange">'+raw(r.pwd_type)+'</span>':''}</span>
            </div>
            <div class="ig-item"><label>Deceased</label><span>${yn(r.is_deceased)}${r.is_deceased==='Yes'&&r.date_of_death?' ('+raw(r.date_of_death)+')':''}</span></div>
            <div class="ig-item"><label>Newborn</label><span>${yn(r.is_newborn)}</span></div>
            <div class="ig-item"><label>Contact No.</label><span>${esc(r.contact_no)}</span></div>
        </div>

        <!-- SOCIAL PROGRAMS -->
        <div class="msec"><i class="fas fa-hands-helping"></i> Social Programs &amp; Lifestyle</div>
        <div class="flag-row">
            ${yn(r.is_4ps,'4Ps Beneficiary')}
            ${yn(r.is_nhts,'NHTS Member')}
            ${yn(r.is_solo_parent,'Solo Parent')}
            ${yn(r.is_smoker,'Smoker')}
            ${yn(r.is_binge_drinker,'Binge Drinker')}
        </div>

        <!-- HEALTH -->
        <div class="msec" style="margin-top:16px"><i class="fas fa-heartbeat"></i> Health Conditions</div>
        <div class="flag-row">
            ${yn(r.has_hypertension,'Hypertension')}
            ${yn(r.has_diabetes,'Diabetes')}
            ${yn(r.has_asthma,'Asthma')}
            ${yn(r.has_tb,'Tuberculosis')}
            ${yn(r.has_cancer,'Cancer')}
            ${yn(r.has_mental_health,'Mental Health')}
        </div>

        <!-- PHILHEALTH -->
        <div class="msec" style="margin-top:16px"><i class="fas fa-shield-alt"></i> PhilHealth &amp; Family Planning</div>
        <div class="ig2">
            <div class="ig-item"><label>Membership Type</label><span>${esc(r.membership_type)}</span></div>
            <div class="ig-item"><label>Family Planning</label><span>${yn(r.family_planning,'Practicing')}</span></div>
        </div>

        ${r.status==='Rejected'&&r.rejection_reason ? `
        <div style="margin-top:18px;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px;">
            <div style="font-size:.75rem;font-weight:700;color:#991b1b;margin-bottom:6px;">
                <i class="fas fa-times-circle"></i> Rejection Reason
            </div>
            <div style="font-size:.875rem;color:#991b1b;">${raw(r.rejection_reason)}</div>
        </div>` : ''}
    `;

    // Footer action buttons
    let btns = `<button class="mbtn mbtn-cancel" onclick="closeModal('viewOverlay')">Close</button>`;
    if (r.status === 'Pending') {
        btns += `
        <button class="mbtn mbtn-approve" onclick="closeModal('viewOverlay');doApprove(${r.id},'${raw(r.first_name+' '+r.surname)}')">
            <i class="fas fa-check"></i> Approve
        </button>
        <button class="mbtn mbtn-reject" onclick="closeModal('viewOverlay');openReject(${r.id},'${raw(r.first_name+' '+r.surname)}')">
            <i class="fas fa-times"></i> Reject
        </button>`;
    } else if (r.status === 'Rejected') {
        btns += `
        <button class="mbtn mbtn-approve" onclick="closeModal('viewOverlay');doApprove(${r.id},'${raw(r.first_name+' '+r.surname)}')">
            <i class="fas fa-check"></i> Approve Anyway
        </button>`;
    }
    document.getElementById('viewFoot').innerHTML = btns;
    document.getElementById('viewOverlay').classList.add('open');
}

// ── Approve ───────────────────────────────────────────────────────────────
function doApprove(id, name) {
    if (!confirm(`Approve the registration of "${name}"?\n\nThis will allow them to log in to the resident portal.`)) return;
    document.getElementById('fApproveId').value = id;
    document.getElementById('fApprove').submit();
}

// ── Reject ────────────────────────────────────────────────────────────────
let _rid = null;
function openReject(id, name) {
    _rid = id;
    document.getElementById('rejectName').textContent = name;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectReason').classList.remove('err');
    document.getElementById('rejectOverlay').classList.add('open');
}
function submitReject() {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        document.getElementById('rejectReason').classList.add('err');
        document.getElementById('rejectReason').focus();
        return;
    }
    document.getElementById('fRejectId').value     = _rid;
    document.getElementById('fRejectReason').value = reason;
    document.getElementById('fReject').submit();
}

// ── Undo ──────────────────────────────────────────────────────────────────
function doUndo(id, name, from) {
    const msg = from === 'approved'
        ? `Revert "${name}" back to Pending?\n\nThey will lose login access until re-approved.`
        : `Set "${name}" back to Pending review?`;
    if (!confirm(msg)) return;
    document.getElementById('fUndoId').value = id;
    document.getElementById('fUndo').submit();
}
</script>
</body>
</html>