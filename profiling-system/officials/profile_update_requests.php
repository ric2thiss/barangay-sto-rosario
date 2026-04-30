<?php
// ── officials/profile_update_requests.php ────────────────────────────────
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: index.php'); exit();
}
$allowed_types = ['admin', 'staff', 'official'];
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    header('Location: ../resident/dashboard.php'); exit();
}

include('connection.php');
include('sidebar_counts.php');
// ── Helper: PSA-based SES classification ─────────────────────────────────
function classifySES(?float $monthly): ?string {
    if ($monthly === null || $monthly < 0) return null;
    if ($monthly < 10957)  return 'Poor';
    if ($monthly < 21914)  return 'Low Income';
    if ($monthly < 43828)  return 'Lower Middle Income';
    if ($monthly < 76669)  return 'Middle Income';
    if ($monthly < 131484) return 'Upper Middle Income';
    return 'High Income';
}

// ── Handle approve/reject POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $req_id = (int)($_POST['req_id'] ?? 0);

    if (!$req_id) { header('Location: profile_update_requests.php?error=invalid'); exit(); }

    // Load request
    $stmt = $conn->prepare("SELECT * FROM profile_update_requests WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $req_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) { header('Location: profile_update_requests.php?error=notfound'); exit(); }

    if ($action === 'approve') {

        // ── Re-derive financial fields server-side ────────────────────────
        $monthly_income = ($req['monthly_income'] !== null && $req['monthly_income'] !== '')
            ? (float)$req['monthly_income'] : null;
        $annual_income  = ($monthly_income !== null) ? round($monthly_income * 12, 2) : null;
        $ses            = classifySES($monthly_income);

        // ── Build dynamic UPDATE — only non-null submitted fields ─────────
        // Includes all updatable columns. Financial fields are always
        // derived together to keep annual_income and socioeconomic_status
        // consistent with monthly_income.
        $updatable = [
            // contact / occupation
            'contact_no'             => ['s', $req['contact_no']],
            'occupation_type'        => ['s', $req['occupation_type']],
            'occupation'             => ['s', $req['occupation']],
            // financial (always update as a trio when monthly is provided)
            'monthly_income'         => ['d', $monthly_income],
            'annual_income'          => ['d', $annual_income],
            'socioeconomic_status'   => ['s', $ses],
            // personal / household
            'civil_status'           => ['s', $req['civil_status']],
            'religion'               => ['s', $req['religion']],
            'purok'                  => ['s', $req['purok']],
            'barangay'               => ['s', $req['barangay']],
            'household_position'     => ['s', $req['household_position']],
            'educational_attainment' => ['s', $req['educational_attainment']],
            'suffix'                 => ['s', $req['suffix']],
            // photo
            'image_path'             => ['s', $req['new_image_path']],
        ];

        $set_parts = [];
        $params    = [];
        $types     = '';

        foreach ($updatable as $col => [$type, $val]) {
            // For financial fields: apply the trio together if monthly_income was submitted
            if (in_array($col, ['annual_income','socioeconomic_status'])) {
                if ($monthly_income === null) continue; // skip if no monthly submitted
            }
            if ($val !== null && $val !== '') {
                $set_parts[] = "`$col` = ?";
                $params[]    = $val;
                $types      .= $type;
            }
        }

        if (!empty($set_parts)) {
            $set_parts[] = '`updated_at` = NOW()';
            $types      .= 'i';
            $params[]    = $req['resident_id'];
            $sql = "UPDATE residents SET " . implode(', ', $set_parts) . " WHERE id = ?";
            $upd = $conn->prepare($sql);
            $upd->bind_param($types, ...$params);
            $upd->execute();
            $upd->close();
        }

        // Mark as approved
        $upd2 = $conn->prepare(
            "UPDATE profile_update_requests SET status='Approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?"
        );
        $upd2->bind_param('si', $_SESSION['username'], $req_id);
        $upd2->execute();
        $upd2->close();

        // Log the action
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, username, full_name, action, details, ip_address, login_at, status) VALUES (?, ?, ?, ?, 'approve_profile_update', ?, ?, NOW(), 'offline')");
        $log_user_type = $_SESSION['user_type'] ?? 'admin';
        $log_username = $_SESSION['username'] ?? 'Admin';
        $log_fullname = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
        $log_details = 'Approved profile update for ' . $req['first_name'] . ' ' . $req['surname'] . ' (Resident ID: ' . $req['resident_id'] . ')';
        $log_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $log_stmt->bind_param('isssss', $_SESSION['user_id'], $log_user_type, $log_username, $log_fullname, $log_details, $log_ip);
        $log_stmt->execute();
        $log_stmt->close();

        header('Location: profile_update_requests.php?approved=1'); exit();

    } elseif ($action === 'reject') {
        $reason = strip_tags(trim($_POST['rejection_reason'] ?? ''));
        if (empty($reason)) { header('Location: profile_update_requests.php?error=noreason'); exit(); }

        $upd = $conn->prepare(
            "UPDATE profile_update_requests SET status='Rejected', rejection_reason=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?"
        );
        $upd->bind_param('ssi', $reason, $_SESSION['username'], $req_id);
        $upd->execute();
        $upd->close();

        header('Location: profile_update_requests.php?rejected=1'); exit();
    }
}

// ── Load requests ─────────────────────────────────────────────────────────
$tab    = in_array($_GET['tab'] ?? '', ['pending','approved','rejected']) ? $_GET['tab'] : 'pending';
$status = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'][$tab];

$stmt = $conn->prepare("
    SELECT
        pur.*,
        r.first_name, r.middle_name, r.surname,
        r.image_path           AS current_photo,
        r.contact_no           AS cur_contact,
        r.occupation_type      AS cur_occupation_type,
        r.occupation           AS cur_occupation,
        r.monthly_income       AS cur_monthly_income,
        r.socioeconomic_status AS cur_ses,
        r.civil_status         AS cur_civil,
        r.religion             AS cur_religion,
        r.purok                AS cur_purok,
        r.barangay             AS cur_barangay,
        r.suffix               AS cur_suffix,
        r.household_position   AS cur_household_position,
        r.educational_attainment AS cur_educational_attainment
    FROM profile_update_requests pur
    JOIN residents r ON r.id = pur.resident_id
    WHERE pur.status = ?
    ORDER BY pur.created_at DESC
");
$stmt->bind_param('s', $status);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$counts = [];
foreach (['Pending','Approved','Rejected'] as $s) {
    $c = $conn->query("SELECT COUNT(*) c FROM profile_update_requests WHERE status='$s'");
    $counts[strtolower($s)] = (int)$c->fetch_assoc()['c'];
}

$pending_reg = (int)$conn->query(
    "SELECT COUNT(*) c FROM pending_registrations WHERE status='Pending'"
)->fetch_assoc()['c'];



// ── PSA SES label for display ─────────────────────────────────────────────
function sesLabel(?float $monthly): string {
    if ($monthly === null) return '—';
    return classifySES($monthly) ?? '—';
}

$SES_COLORS = [
    'Poor'               => 'background:#fee2e2;color:#991b1b',
    'Low Income'         => 'background:#fff7ed;color:#c2410c',
    'Lower Middle Income'=> 'background:#fefce8;color:#854d0e',
    'Middle Income'      => 'background:#f0fdf4;color:#166534',
    'Upper Middle Income'=> 'background:#eff6ff;color:#1d4ed8',
    'High Income'        => 'background:#f5f3ff;color:#5b21b6',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Update Requests — Admin</title>
    <?php include 'hybrid_assets.php'; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        :root{--primary:#1a56db;--primary-light:#e8f0fe;--success:#0e9f6e;--danger:#e02424;--warning:#d97706;--sidebar-bg:#0f172a;--sidebar-w:250px;--body-bg:#f1f5f9;--card-bg:#fff;--border:#e2e8f0;--text:#1e293b;--muted:#64748b;--radius:12px;--shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--body-bg);color:var(--text);display:flex;min-height:100vh;}
        .sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--sidebar-bg);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100;overflow-y:auto;}
        .sidebar-brand{padding:28px 20px 20px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;align-items:center;gap:10px;}
        .sidebar-brand img{width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.15);}
        .sidebar-brand h2{color:#fff;font-size:.95rem;font-weight:700;text-align:center;}
        .sidebar nav{padding:16px 12px;flex:1;}
        .sidebar nav ul{list-style:none;display:flex;flex-direction:column;gap:4px;}
        .sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;color:rgba(255,255,255,.65);text-decoration:none;font-size:.875rem;font-weight:500;transition:background .15s,color .15s;}
        .sidebar nav a:hover,.sidebar nav a.active{background:rgba(255,255,255,.1);color:#fff;}
        .sidebar nav a.active{background:var(--primary);}
        .sidebar nav a i{width:18px;text-align:center;}
        .nav-badge{margin-left:auto;background:#e02424;color:#fff;font-size:.68rem;font-weight:800;padding:1px 7px;border-radius:20px;}
        .main{margin-left:var(--sidebar-w);flex:1;padding:28px 28px 48px;max-width:calc(100% - var(--sidebar-w));}
        .page-header{margin-bottom:22px;}
        .page-header h1{font-size:1.4rem;font-weight:800;color:#111827;}
        .page-header p{font-size:.82rem;color:var(--muted);margin-top:3px;}
        .flash{border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:.875rem;font-weight:600;}
        .flash-success{background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46;}
        .flash-error  {background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;}
        .stat-row{display:flex;gap:14px;margin-bottom:22px;flex-wrap:wrap;}
        .stat-chip{display:flex;align-items:center;gap:12px;background:var(--card-bg);border:1px solid var(--border);border-radius:10px;padding:14px 18px;box-shadow:var(--shadow);flex:1;min-width:130px;}
        .stat-chip .ico{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0;}
        .stat-chip .val{font-size:1.5rem;font-weight:800;line-height:1;color:#111827;}
        .stat-chip .lbl{font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;}
        .chip-pending .ico{background:#fffbeb;color:var(--warning);}
        .chip-approved .ico{background:#ecfdf5;color:var(--success);}
        .chip-rejected .ico{background:#fef2f2;color:var(--danger);}
        .tab-bar{display:flex;gap:4px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:6px;margin-bottom:16px;box-shadow:var(--shadow);}
        .tab-btn{flex:1;text-align:center;padding:9px;border-radius:8px;border:none;background:transparent;font-size:.875rem;font-weight:600;color:var(--muted);cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .15s;}
        .tab-btn:hover{background:#f8fafc;}
        .tab-pending{background:var(--warning);color:#fff;}
        .tab-approved{background:var(--success);color:#fff;}
        .tab-rejected{background:var(--danger);color:#fff;}
        .card{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;}
        .table-wrap{overflow-x:auto;}
        table{width:100%;border-collapse:collapse;font-size:.875rem;}
        thead th{background:#f8fafc;color:var(--muted);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:11px 14px;border-bottom:1px solid var(--border);white-space:nowrap;}
        tbody tr{border-bottom:1px solid #f1f5f9;}
        tbody tr:last-child{border-bottom:none;}
        tbody tr:hover{background:#f8fafc;}
        tbody td{padding:10px 14px;vertical-align:middle;}
        .avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
        .pill{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.7rem;font-weight:700;}
        .pill-pending{background:#fffbeb;color:#92400e;}
        .pill-approved{background:#ecfdf5;color:#065f46;}
        .pill-rejected{background:#fef2f2;color:#991b1b;}
        .act{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:7px;border:none;font-size:.75rem;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;transition:opacity .15s;}
        .act:hover{opacity:.85;}
        .act-approve{background:#ecfdf5;color:#065f46;border:1px solid #6ee7b7;}
        .act-reject {background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;}
        .act-view   {background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;}
        .diff-item{display:flex;gap:8px;align-items:center;font-size:.8rem;margin-bottom:6px;flex-wrap:wrap;}
        .diff-label{color:var(--muted);font-size:.72rem;min-width:72px;flex-shrink:0;}
        .diff-old{color:var(--danger);text-decoration:line-through;}
        .diff-new{color:var(--success);font-weight:700;}
        /* Modal */
        .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:20px;}
        .overlay.open{display:flex;}
        .mbox{background:#fff;border-radius:16px;width:100%;max-width:640px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:mIn .18s ease;}
        @keyframes mIn{from{transform:scale(.96);opacity:0}to{transform:scale(1);opacity:1}}
        .mhead{padding:18px 22px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;position:sticky;top:0;background:#fff;z-index:2;}
        .mhead h5{font-size:1rem;font-weight:700;margin:0;flex:1;}
        .mclose{background:none;border:none;font-size:1.1rem;color:var(--muted);cursor:pointer;padding:4px;}
        .mclose:hover{color:var(--danger);}
        .mbody{padding:18px 22px;}
        .mfoot{padding:14px 22px 18px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;position:sticky;bottom:0;background:#fff;z-index:2;}
        .mbtn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:none;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s;}
        .mbtn:hover{opacity:.88;}
        .mbtn-approve{background:var(--success);color:#fff;}
        .mbtn-reject {background:var(--danger);color:#fff;}
        .mbtn-cancel {background:#f1f5f9;color:var(--text);border:1px solid var(--border);}
        textarea.mta{width:100%;border:1px solid var(--border);border-radius:8px;padding:9px 12px;font-size:.875rem;font-family:inherit;resize:vertical;min-height:80px;outline:none;transition:border-color .2s;}
        textarea.mta:focus{border-color:var(--danger);}
        .empty{padding:44px;text-align:center;color:var(--muted);}
        .empty i{font-size:2rem;opacity:.2;display:block;margin-bottom:12px;}
        .msec{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin:16px 0 8px;padding-bottom:5px;border-bottom:1px solid var(--border);}
        .msec:first-of-type{margin-top:0;}
        @media(max-width:768px){.sidebar{transform:translateX(-100%)}.main{margin-left:0;max-width:100%;padding:16px;}}
    </style>
</head>
<body>

<?php $current_page = 'profile_updates'; include 'sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <h1><i class="fas fa-user-edit" style="color:var(--primary)"></i> Resident Profile Update Requests</h1>
        <p>Review and approve or reject profile changes submitted by residents</p>
    </div>

    <?php
    $messages = [
        'approved' => ['success', 'Profile update approved and applied.'],
        'rejected' => ['success', 'Profile update rejected.'],
        'noreason' => ['error',   'Rejection reason is required.'],
        'notfound' => ['error',   'Request not found.'],
        'invalid'  => ['error',   'Invalid action.'],
    ];
    if (isset($_GET['approved']))     [$ft,$fm] = $messages['approved'];
    elseif (isset($_GET['rejected'])) [$ft,$fm] = $messages['rejected'];
    elseif (isset($_GET['error']) && isset($messages[$_GET['error']])) [$ft,$fm] = $messages[$_GET['error']];
    if (isset($ft)):
    ?>
    <div class="flash flash-<?= $ft ?>">
        <i class="fas fa-<?= $ft === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($fm) ?>
    </div>
    <?php endif; ?>

    <div class="stat-row">
        <div class="stat-chip chip-pending"><div class="ico"><i class="fas fa-hourglass-half"></i></div><div><div class="val"><?= $counts['pending'] ?></div><div class="lbl">Pending</div></div></div>
        <div class="stat-chip chip-approved"><div class="ico"><i class="fas fa-check-circle"></i></div><div><div class="val"><?= $counts['approved'] ?></div><div class="lbl">Approved</div></div></div>
        <div class="stat-chip chip-rejected"><div class="ico"><i class="fas fa-times-circle"></i></div><div><div class="val"><?= $counts['rejected'] ?></div><div class="lbl">Rejected</div></div></div>
    </div>

    <div class="tab-bar">
        <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $t => $l):
            $icons  = ['pending'=>'hourglass-half','approved'=>'check-circle','rejected'=>'times-circle'];
            $active = $tab === $t ? "tab-$t" : '';
        ?>
        <a href="?tab=<?= $t ?>" class="tab-btn <?= $active ?>">
            <i class="fas fa-<?= $icons[$t] ?>"></i> <?= $l ?>
            <span style="background:rgba(0,0,0,.15);padding:1px 7px;border-radius:12px;font-size:.7rem"><?= $counts[$t] ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>Resident</th>
                    <th>Requested Changes</th>
                    <th>SES Impact</th>
                    <th>Note</th>
                    <th>Submitted</th>
                    <?php if ($tab !== 'pending'): ?><th>Reviewed</th><?php endif; ?>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="7">
                    <div class="empty"><i class="fas fa-inbox"></i><p>No <?= $status ?> update requests.</p></div>
                </td></tr>
                <?php else: foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <img src="uploads/residents/<?= htmlspecialchars($row['current_photo'] ?? 'default_photo_male.jpg') ?>"
                                 class="avatar" onerror="this.onerror=null; this.src='uploads/residents/default_photo_male.jpg'" alt="">
                            <div>
                                <div style="font-weight:700;font-size:.875rem">
                                    <?= htmlspecialchars($row['surname'] . ', ' . $row['first_name']) ?>
                                    <?php if (!empty($row['suffix'])): ?>
                                        <span style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($row['suffix']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($row['cur_purok'] ?? '—') ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php
                        $changes = [
                            'Contact'       => [$row['cur_contact'],              $row['contact_no']],
                            'Occ. Type'     => [$row['cur_occupation_type'],      $row['occupation_type']],
                            'Occupation'    => [$row['cur_occupation'],           $row['occupation']],
                            'Monthly Inc.'  => [$row['cur_monthly_income'],       $row['monthly_income']],
                            'Civil Status'  => [$row['cur_civil'],                $row['civil_status']],
                            'Religion'      => [$row['cur_religion'],             $row['religion']],
                            'Purok'         => [$row['cur_purok'],                $row['purok']],
                            'Barangay'      => [$row['cur_barangay'],             $row['barangay']],
                            'HH Position'   => [$row['cur_household_position'],   $row['household_position']],
                            'Education'     => [$row['cur_educational_attainment'], $row['educational_attainment']],
                            'Suffix'        => [$row['cur_suffix'],               $row['suffix']],
                        ];
                        $shown = 0;
                        foreach ($changes as $lbl => [$old, $new]):
                            if ($new !== null && $new !== '' && (string)$new !== (string)$old):
                                $shown++;
                        ?>
                        <div class="diff-item">
                            <span class="diff-label"><?= htmlspecialchars($lbl) ?></span>
                            <span class="diff-old"><?= htmlspecialchars($old ?? '—') ?></span>
                            <i class="fas fa-arrow-right" style="color:var(--muted);font-size:.65rem;flex-shrink:0"></i>
                            <span class="diff-new">
                                <?php if ($lbl === 'Monthly Inc.'): ?>
                                    ₱<?= number_format((float)$new, 2) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($new) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; endforeach;
                        if ($row['new_image_path']): $shown++; ?>
                        <div class="diff-item">
                            <span class="diff-label">Photo</span>
                            <span class="diff-new">New photo uploaded</span>
                        </div>
                        <?php endif;
                        if (!$shown): ?>
                        <span style="color:var(--muted);font-size:.8rem">No visible changes</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        // Show SES impact if monthly_income changed
                        if ($row['monthly_income'] !== null && $row['monthly_income'] !== '') {
                            $new_ses = classifySES((float)$row['monthly_income']);
                            $old_ses = $row['cur_ses'] ?? classifySES((float)($row['cur_monthly_income'] ?? 0));
                            $sc = $SES_COLORS[$new_ses] ?? 'background:#f1f5f9;color:#64748b';
                            if ($new_ses !== $old_ses) {
                                echo '<div style="font-size:.72rem;color:var(--muted);margin-bottom:3px">';
                                echo '<span style="text-decoration:line-through;color:var(--danger)">' . htmlspecialchars($old_ses ?? '—') . '</span>';
                                echo ' → </div>';
                            }
                            echo '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:.68rem;font-weight:700;' . $sc . '">';
                            echo htmlspecialchars($new_ses ?? '—') . '</span>';
                        } else {
                            echo '<span style="color:var(--muted);font-size:.8rem">—</span>';
                        }
                        ?>
                    </td>
                    <td style="font-size:.8rem;color:var(--muted);max-width:160px">
                        <?= htmlspecialchars(mb_substr($row['resident_note'] ?? '—', 0, 80)) ?>
                    </td>
                    <td style="font-size:.78rem;color:var(--muted)">
                        <?= date('M d, Y', strtotime($row['created_at'])) ?>
                    </td>
                    <?php if ($tab !== 'pending'): ?>
                    <td style="font-size:.78rem;color:var(--muted)">
                        <?= $row['reviewed_at'] ? date('M d, Y', strtotime($row['reviewed_at'])) : '—' ?>
                        <?= $row['reviewed_by'] ? '<br><span style="font-size:.7rem">by ' . htmlspecialchars($row['reviewed_by']) . '</span>' : '' ?>
                        <?php if ($tab === 'rejected' && $row['rejection_reason']): ?>
                        <div style="color:var(--danger);font-size:.72rem;margin-top:3px">
                            <?= htmlspecialchars(mb_substr($row['rejection_reason'], 0, 60)) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                            <button class="act act-view"
                                    onclick='openView(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)'>
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php if ($tab === 'pending'): ?>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Approve this profile update?')">
                                <input type="hidden" name="action"  value="approve">
                                <input type="hidden" name="req_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="act act-approve">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <button class="act act-reject" onclick="openReject(<?= $row['id'] ?>)">
                                <i class="fas fa-times"></i> Reject
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ── View Modal ─────────────────────────────────────────────────────── -->
<div class="overlay" id="viewModal">
    <div class="mbox">
        <div class="mhead">
            <i class="fas fa-user-edit" style="color:var(--primary)"></i>
            <h5>Update Request Details</h5>
            <button class="mclose" onclick="closeModal('viewModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="mbody" id="viewBody"></div>
        <div class="mfoot">
            <button class="mbtn mbtn-cancel" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- ── Reject Modal ───────────────────────────────────────────────────── -->
<div class="overlay" id="rejectModal">
    <div class="mbox" style="max-width:460px">
        <div class="mhead">
            <i class="fas fa-times-circle" style="color:var(--danger)"></i>
            <h5>Reject Update Request</h5>
            <button class="mclose" onclick="closeModal('rejectModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" id="rejectForm">
            <div class="mbody">
                <input type="hidden" name="action"  value="reject">
                <input type="hidden" name="req_id"  id="rejectReqId">
                <p style="font-size:.875rem;color:var(--muted);margin-bottom:12px">
                    Provide a reason. The resident will see this when they log in.
                </p>
                <label style="font-size:.8rem;font-weight:700;color:var(--muted);display:block;margin-bottom:6px">
                    Reason <span style="color:var(--danger)">*</span>
                </label>
                <textarea class="mta" name="rejection_reason"
                    placeholder="e.g., Information provided does not match our records…" required></textarea>
            </div>
            <div class="mfoot">
                <button type="button" class="mbtn mbtn-cancel" onclick="closeModal('rejectModal')">Cancel</button>
                <button type="submit" class="mbtn mbtn-reject">
                    <i class="fas fa-times-circle"></i> Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── SES colour map (mirrors PHP) ──────────────────────────────────────────
const SES_COLORS = {
    'Poor':               'background:#fee2e2;color:#991b1b',
    'Low Income':         'background:#fff7ed;color:#c2410c',
    'Lower Middle Income':'background:#fefce8;color:#854d0e',
    'Middle Income':      'background:#f0fdf4;color:#166534',
    'Upper Middle Income':'background:#eff6ff;color:#1d4ed8',
    'High Income':        'background:#f5f3ff;color:#5b21b6',
};

function sesBadge(v) {
    if (!v) return '—';
    const s = SES_COLORS[v] || 'background:#f1f5f9;color:#64748b';
    return `<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700;${s}">${esc(v)}</span>`;
}

function classifySES(monthly) {
    if (monthly === null || monthly < 0) return null;
    if (monthly < 10957)  return 'Poor';
    if (monthly < 21914)  return 'Low Income';
    if (monthly < 43828)  return 'Lower Middle Income';
    if (monthly < 76669)  return 'Middle Income';
    if (monthly < 131484) return 'Upper Middle Income';
    return 'High Income';
}

function peso(v) {
    if (v === null || v === '' || v === undefined) return '—';
    return '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function esc(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function openView(r) {
    // All fields that can be requested for change
    const fields = [
        { label: 'Contact No.',           newVal: r.contact_no,             oldVal: r.cur_contact },
        { label: 'Occupation Type',        newVal: r.occupation_type,        oldVal: r.cur_occupation_type },
        { label: 'Occupation',             newVal: r.occupation,             oldVal: r.cur_occupation },
        { label: 'Monthly Income',         newVal: r.monthly_income,         oldVal: r.cur_monthly_income, isCurrency: true },
        { label: 'Civil Status',           newVal: r.civil_status,           oldVal: r.cur_civil },
        { label: 'Religion',               newVal: r.religion,               oldVal: r.cur_religion },
        { label: 'Purok',                  newVal: r.purok,                  oldVal: r.cur_purok },
        { label: 'Barangay',               newVal: r.barangay,               oldVal: r.cur_barangay },
        { label: 'Household Position',     newVal: r.household_position,     oldVal: r.cur_household_position },
        { label: 'Educational Attainment', newVal: r.educational_attainment, oldVal: r.cur_educational_attainment },
        { label: 'Suffix',                 newVal: r.suffix,                 oldVal: r.cur_suffix },
    ];

    let html = `<div style="font-size:.9rem;font-weight:700;margin-bottom:4px">${esc(r.surname)}, ${esc(r.first_name)}${r.suffix ? ' ' + esc(r.suffix) : ''}</div>`;
    html += `<div style="font-size:.78rem;color:var(--muted);margin-bottom:16px">${esc(r.cur_purok || '—')}</div>`;

    // ── Changes section ───────────────────────────────────────────────────
    html += '<div class="msec"><i class="fas fa-exchange-alt"></i> Requested Field Changes</div>';
    html += '<div style="display:grid;gap:8px">';

    let changeCount = 0;
    fields.forEach(({ label, newVal, oldVal, isCurrency }) => {
        if (!newVal && newVal !== 0) return;
        if (String(newVal) === String(oldVal)) return;
        changeCount++;
        const fmtNew = isCurrency ? peso(newVal) : esc(newVal);
        const fmtOld = isCurrency ? peso(oldVal) : esc(oldVal);
        html += `
            <div class="diff-item">
                <span class="diff-label" style="min-width:140px">${esc(label)}</span>
                ${oldVal ? `<span class="diff-old">${fmtOld}</span>
                <i class="fas fa-arrow-right" style="color:var(--muted);font-size:.65rem;flex-shrink:0"></i>` : ''}
                <span class="diff-new">${fmtNew}</span>
            </div>`;
    });

    if (r.new_image_path) {
        changeCount++;
        html += `
            <div class="diff-item">
                <span class="diff-label" style="min-width:140px">Profile Photo</span>
                <div style="display:flex;gap:8px;align-items:center">
                    <img src="uploads/residents/${esc(r.current_photo)}"
                         style="width:44px;height:44px;border-radius:6px;object-fit:cover;border:2px solid var(--border);opacity:.5" 
                         onerror="this.onerror=null; this.src='uploads/residents/default_photo_male.jpg'" alt="Current">
                    <i class="fas fa-arrow-right" style="color:var(--muted);font-size:.65rem"></i>
                    <img src="uploads/residents/${esc(r.new_image_path)}"
                         style="width:44px;height:44px;border-radius:6px;object-fit:cover;border:2px solid var(--success)" 
                         onerror="this.onerror=null; this.src='uploads/residents/default_photo_male.jpg'" alt="New">
                </div>
            </div>`;
    }

    if (!changeCount) {
        html += `<p style="color:var(--muted);font-size:.85rem">No visible field changes detected.</p>`;
    }
    html += '</div>';

    // ── SES impact ────────────────────────────────────────────────────────
    if (r.monthly_income !== null && r.monthly_income !== '') {
        const newSES = classifySES(parseFloat(r.monthly_income));
        const annualIncome = parseFloat(r.monthly_income) * 12;
        html += '<div class="msec" style="margin-top:16px"><i class="fas fa-chart-line"></i> Financial Impact (Auto-computed)</div>';
        html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;background:#f8fafc;border-radius:8px;padding:12px;border:1px solid var(--border)">';
        html += `<div><div style="font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">Annual Income</div>
                     <div style="font-size:.9rem;font-weight:700;color:var(--success)">${peso(annualIncome)}</div></div>`;
        html += `<div><div style="font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">SES Classification</div>
                     <div>${sesBadge(newSES)}</div></div>`;
        html += '</div>';
    }

    // ── Resident note ─────────────────────────────────────────────────────
    if (r.resident_note) {
        html += `
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:10px 13px;font-size:.82rem;color:var(--muted);margin-top:14px">
            <strong style="color:var(--text)">Resident's Note:</strong><br>${esc(r.resident_note)}
        </div>`;
    }

    // ── Rejection reason (if rejected) ────────────────────────────────────
    if (r.rejection_reason) {
        html += `
        <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 13px;font-size:.82rem;color:#991b1b;margin-top:14px">
            <strong>Rejection Reason:</strong><br>${esc(r.rejection_reason)}
        </div>`;
    }

    document.getElementById('viewBody').innerHTML = html;
    document.getElementById('viewModal').classList.add('open');
}

function openReject(id) {
    document.getElementById('rejectReqId').value = id;
    document.getElementById('rejectModal').classList.add('open');
}
</script>
</body>
</html>