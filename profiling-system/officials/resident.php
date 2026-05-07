<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php");
    exit();
}
$allowed_types = ['admin', 'staff', 'official', 'resident'];
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    header("Location: ../resident/residents.php");
    exit();
}
// Purok Presidents are residents with staff_position = 'Purok President'
// They must only see their own purok
$is_purok_president = (($_SESSION['staff_position'] ?? '') === 'Purok President');
if ($_SESSION['user_type'] === 'resident' && !$is_purok_president) {
    header("Location: ../resident/residents.php");
    exit();
}
if (empty($_SESSION['user_id'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
$is_superadmin = ($_SESSION['user_type'] === 'admin')
    || (!empty($_SESSION['is_superadmin']));

include("connection.php");
include('sidebar_counts.php');
$records_per_page = 20;
$search_query = isset($_GET['search_query']) ? $conn->real_escape_string(trim($_GET['search_query'])) : '';
$purok_filter = isset($_GET['purok']) ? $conn->real_escape_string($_GET['purok']) : 'all';
$ses_filter = isset($_GET['ses']) ? $conn->real_escape_string($_GET['ses']) : 'all';
$edu_filter = isset($_GET['edu']) ? $conn->real_escape_string($_GET['edu']) : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Purok list for filter dropdown
$purok_result = $conn->query("SELECT DISTINCT purok FROM residents WHERE is_deleted = 0 ORDER BY purok ASC");
$puroks = [];
if ($purok_result)
    while ($row = $purok_result->fetch_assoc())
        $puroks[] = $row['purok'];

// Purok President: force their purok filter
if ($is_purok_president) {
    $purok_filter = $_SESSION['purok'] ?? '';
}

// SES options (matching register_account.php classifySES thresholds)
$ses_options = ['Poor', 'Low Income', 'Lower Middle Income', 'Middle Income', 'Upper Middle Income', 'High Income'];

// Educational attainment options
$edu_options = [
    'No Formal Education',
    'Elementary Level',
    'Elementary Graduate',
    'High School Level',
    'High School Graduate',
    'Senior High School Level',
    'Senior High School Graduate',
    'College Level',
    'College Graduate',
    'Vocational Level',
    'Vocational Graduate',
    'Post Graduate',
    'Others'
];

// ── WHERE clause ──────────────────────────────────────────────────────────
$where = ['is_deleted = 0'];
if (!empty($search_query))
    $where[] = "(first_name LIKE '%$search_query%'
                 OR middle_name LIKE '%$search_query%'
                 OR surname LIKE '%$search_query%'
                 OR purok LIKE '%$search_query%'
                 OR barangay LIKE '%$search_query%'
                 OR age LIKE '%$search_query%'
                 OR occupation LIKE '%$search_query%'
                 OR occupation_type LIKE '%$search_query%')";
if ($purok_filter !== 'all')
    $where[] = "purok = '$purok_filter'";
if ($ses_filter !== 'all')
    $where[] = "socioeconomic_status = '$ses_filter'";
if ($edu_filter !== 'all')
    $where[] = "educational_attainment = '$edu_filter'";
// Purok President RBAC: restrict to their own purok
if ($is_purok_president && $purok_filter === 'all') {
    $pp_purok = $conn->real_escape_string($_SESSION['purok'] ?? '');
    $where[] = "purok = '$pp_purok'";
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── SELECT all fields used by modals and table ────────────────────────────
$result = $conn->query("
    SELECT
        id, username,
        first_name, middle_name, surname, suffix,
        birthdate, birthplace, age, sex, civil_status, nationality,
        religion, ethnicity, blood_type, philhealth_no, length_of_residency,
        household_no, purok, barangay, municipality, province,
        voters_status, educational_attainment, total_household,
        grade_level, school_name,
        course, course_other, graduation_date, eligibility, eligibility_other,
        is_pwd, pwd_type, is_deceased, date_of_death, is_newborn,
        contact_no, occupation_type, occupation,
        monthly_income, annual_income, socioeconomic_status, household_position,
        house_ownership, house_material, toilet_type, water_source,
        is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker,
        has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health,
        membership_type, family_planning,
        image_path, is_purok_president,
        created_at, updated_at
    FROM residents
    $where_clause
    ORDER BY id DESC
    LIMIT $records_per_page OFFSET $offset
");

$count_result = $conn->query("SELECT COUNT(*) as total FROM residents $where_clause");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

function build_url($page, $search, $purok, $ses = 'all', $edu = 'all')
{
    $p = "page=$page";
    if (!empty($search))
        $p .= "&search_query=" . urlencode($search);
    if ($purok !== 'all')
        $p .= "&purok=" . urlencode($purok);
    if ($ses !== 'all')
        $p .= "&ses=" . urlencode($ses);
    if ($edu !== 'all')
        $p .= "&edu=" . urlencode($edu);
    return $p;
}

// ── Buffer per-row modals ─────────────────────────────────────────────────
$modal_html = '';
if ($result && $result->num_rows > 0) {
    ob_start();
    while ($row = $result->fetch_assoc()) {
        include 'modals/view_resident_modal.php';
        include 'modals/edit_resident_modal.php';
    }
    $modal_html = ob_get_clean();
    $result->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents — Barangay Sto. Rosario</title>
    <?php include 'hybrid_assets.php'; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --primary: #1a56db;
            --primary-light: #e8f0fe;
            --success: #0e9f6e;
            --danger: #e02424;
            --warning: #ff8a00;
            --info: #0891b2;
            --sidebar-bg: #0f172a;
            --sidebar-w: 250px;
            --body-bg: #f1f5f9;
            --card-bg: #fff;
            --border: #e2e8f0;
            --text: #1e293b;
            --muted: #64748b;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0, 0, 0, .08), 0 4px 16px rgba(0, 0, 0, .06);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--body-bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 28px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, .15);
        }

        .sidebar-brand h2 {
            color: #fff;
            font-size: .95rem;
            font-weight: 700;
            text-align: center;
        }

        .sidebar nav {
            padding: 16px 12px;
            flex: 1;
        }

        .sidebar nav ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            color: rgba(255, 255, 255, .65);
            text-decoration: none;
            font-size: .875rem;
            font-weight: 500;
            transition: background .15s, color .15s;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: rgba(255, 255, 255, .1);
            color: #fff;
        }

        .sidebar nav a.active {
            background: var(--primary);
        }

        .sidebar nav a i {
            width: 18px;
            text-align: center;
            font-size: .9rem;
        }

        .nav-badge {
            margin-left: auto;
            background: #e02424;
            color: #fff;
            font-size: .68rem;
            font-weight: 800;
            padding: 1px 7px;
            border-radius: 20px;
        }

        /* Main */
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 28px 28px 48px;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #111827;
        }

        .page-header p {
            font-size: .85rem;
            color: var(--muted);
            margin-top: 2px;
        }

        /* Card */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        /* Filter bar */
        .filter-bar {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }

        .filter-bar label {
            font-size: .75rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .4px;
            display: block;
            margin-bottom: 5px;
        }

        .filter-bar select,
        .filter-bar input[type=text] {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: .875rem;
            color: var(--text);
            background: #fff;
            outline: none;
            transition: border-color .2s;
            font-family: inherit;
        }

        .filter-bar select:focus,
        .filter-bar input[type=text]:focus {
            border-color: var(--primary);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: opacity .15s, transform .1s;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn:hover {
            opacity: .88;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--muted);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            color: var(--text);
        }

        .btn-info {
            background: var(--info);
            color: #fff;
        }

        .btn-warning {
            background: var(--warning);
            color: #fff;
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: .8rem;
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: .875rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Active filters strip */
        .active-filters {
            padding: 10px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            background: #f8fafc;
        }

        .active-filters span {
            font-size: .78rem;
            color: var(--muted);
        }

        /* Table */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .875rem;
        }

        thead th {
            background: #f8fafc;
            color: var(--muted);
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 11px 14px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background .1s;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody td {
            padding: 10px 14px;
            vertical-align: middle;
        }

        .resident-img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
        }

        /* Pills */
        .pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .pill-blue {
            background: #eff6ff;
            color: var(--primary);
        }

        .pill-red {
            background: #fef2f2;
            color: var(--danger);
        }

        .pill-dark {
            background: #f3f4f6;
            color: #374151;
        }

        .pill-cyan {
            background: #ecfeff;
            color: var(--info);
        }

        .pill-orange {
            background: #fff7ed;
            color: var(--warning);
        }

        .pill-gray {
            background: #f8fafc;
            color: var(--muted);
        }

        .pill-green {
            background: #ecfdf5;
            color: #0e9f6e;
        }

        .pill-purple {
            background: #f5f3ff;
            color: #7c3aed;
        }

        /* SES badge */
        .ses-badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 10px;
            font-size: .68rem;
            font-weight: 700;
            white-space: nowrap;
        }

        /* Pagination */
        .pagination {
            display: flex;
            gap: 4px;
            justify-content: center;
            align-items: center;
            padding: 16px;
        }

        .page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 8px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .15s;
        }

        .page-btn:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }

        .page-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .page-btn.disabled {
            opacity: .4;
            pointer-events: none;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }

        .no-data i {
            font-size: 2rem;
            opacity: .3;
            display: block;
            margin-bottom: 10px;
        }

        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
    </style>
</head>

<body>

    <?php $current_page = 'residents';
    include 'sidebar.php'; ?>

    <main class="main-content">

        <div class="page-header">
            <h1><i class="fas fa-users" style="color:var(--primary)"></i> Residents</h1>
            <p>Manage all registered residents of Barangay Sto. Rosario</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">

            <!-- Filter bar -->
            <form method="GET" action="">
                <div class="filter-bar">
                    <button class="btn btn-success" type="button" data-bs-toggle="modal"
                        data-bs-target="#addResidentModal">
                        <i class="fas fa-plus"></i> Add Resident
                    </button>

                    <!-- Purok filter -->
                    <div>
                        <label><i class="fas fa-map-marker-alt"></i> Purok</label>
                        <select name="purok">
                            <option value="all" <?= $purok_filter === 'all' ? 'selected' : '' ?>>All Puroks</option>
                            <?php foreach ($puroks as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $purok_filter === $p ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- SES filter — mirrors register_account.php classifySES() buckets -->
                    <div>
                        <label><i class="fas fa-chart-bar"></i> Socioeconomic Status</label>
                        <select name="ses">
                            <option value="all" <?= $ses_filter === 'all' ? 'selected' : '' ?>>All SES</option>
                            <?php foreach ($ses_options as $s): ?>
                                <option value="<?= htmlspecialchars($s) ?>" <?= $ses_filter === $s ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Educational Attainment filter -->
                    <div>
                        <label><i class="fas fa-graduation-cap"></i> Education</label>
                        <select name="edu">
                            <option value="all" <?= $edu_filter === 'all' ? 'selected' : '' ?>>All Education</option>
                            <?php foreach ($edu_options as $e): ?>
                                <option value="<?= htmlspecialchars($e) ?>" <?= $edu_filter === $e ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Search -->
                    <div style="flex:1">
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search_query" placeholder="Name, barangay, occupation, course…"
                            value="<?= htmlspecialchars($search_query) ?>" style="width:100%">
                    </div>

                    <div style="display:flex;gap:8px;align-items:flex-end">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
                        <a href="resident.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </div>
            </form>

            <!-- Active filters strip -->
            <?php if ($purok_filter !== 'all' || $ses_filter !== 'all' || $edu_filter !== 'all' || !empty($search_query)): ?>
                <div class="active-filters">
                    <span><strong>Active filters:</strong></span>
                    <?php if ($purok_filter !== 'all'): ?>
                        <span class="pill pill-blue">Purok: <?= htmlspecialchars($purok_filter) ?></span>
                    <?php endif; ?>
                    <?php if ($ses_filter !== 'all'): ?>
                        <span class="pill pill-purple">SES: <?= htmlspecialchars($ses_filter) ?></span>
                    <?php endif; ?>
                    <?php if ($edu_filter !== 'all'): ?>
                        <span class="pill pill-green">Education: <?= htmlspecialchars($edu_filter) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($search_query)): ?>
                        <span class="pill pill-cyan">Search: <?= htmlspecialchars($search_query) ?></span>
                    <?php endif; ?>
                    <span class="pill pill-gray"><?= $total_records ?> result<?= $total_records != 1 ? 's' : '' ?></span>
                </div>
            <?php endif; ?>

            <!-- Table -->
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Purok</th>
                            <th>Photo</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Surname</th>
                            <th>Suffix</th>
                            <th>Age</th>
                            <th>Sex</th>
                            <th>Occupation Type</th>
                            <th>Monthly Income</th>
                            <th>SES</th>
                            <th>Status Flags</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ── SES badge helper (PHP) ─────────────────────────────────
                        function sesBadgePhp(?string $ses): string
                        {
                            $map = [
                                'Poor' => 'background:#fee2e2;color:#991b1b',
                                'Low Income' => 'background:#fff7ed;color:#c2410c',
                                'Lower Middle Income' => 'background:#fefce8;color:#854d0e',
                                'Middle Income' => 'background:#f0fdf4;color:#166534',
                                'Upper Middle Income' => 'background:#eff6ff;color:#1d4ed8',
                                'High Income' => 'background:#f5f3ff;color:#5b21b6',
                            ];
                            if (!$ses)
                                return '<span style="color:var(--muted)">—</span>';
                            $style = $map[$ses] ?? 'background:#f1f5f9;color:#64748b';
                            return '<span class="ses-badge" style="' . $style . '">' . htmlspecialchars($ses) . '</span>';
                        }

                        // ── SES auto-classify from monthly income (mirrors register_account.php) ──
                        function classifySES(?float $m): ?string
                        {
                            if ($m === null || $m < 0)
                                return null;
                            if ($m < 10957)
                                return 'Poor';
                            if ($m < 21914)
                                return 'Low Income';
                            if ($m < 43828)
                                return 'Lower Middle Income';
                            if ($m < 76669)
                                return 'Middle Income';
                            if ($m < 131484)
                                return 'Upper Middle Income';
                            return 'High Income';
                        }

                        if ($result && $result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                // ── Compute SES if not stored ──────────────────────
                                $ses = $row['socioeconomic_status'] ?? null;
                                if (!$ses && $row['monthly_income'] !== null)
                                    $ses = classifySES((float) $row['monthly_income']);

                                // ── Status flag badges ─────────────────────────────
                                $badges = [];
                                if ($row['is_pwd'] === 'Yes')
                                    $badges[] = '<span class="pill pill-red">PWD</span>';
                                if ($row['is_deceased'] === 'Yes')
                                    $badges[] = '<span class="pill pill-dark">Deceased</span>';
                                if ((int) $row['age'] <= 1)
                                    $badges[] = '<span class="pill pill-cyan">Newborn</span>';
                                if ((int) $row['age'] >= 60)
                                    $badges[] = '<span class="pill pill-orange">Senior</span>';
                                if ($row['voters_status'] === 'Yes')
                                    $badges[] = '<span class="pill pill-gray">Voter</span>';
                                if ($row['is_4ps'] === 'Yes')
                                    $badges[] = '<span class="pill pill-green">4Ps</span>';
                                if ($row['is_solo_parent'] === 'Yes')
                                    $badges[] = '<span class="pill pill-purple">Solo Parent</span>';
                                if ($row['is_nhts'] === 'Yes')
                                    $badges[] = '<span class="pill pill-blue">NHTS</span>';
                                ?>
                                <tr>
                                    <td><span class="pill pill-blue"><?= htmlspecialchars($row['purok']) ?></span></td>
                                    <td>
                                        <img src="uploads/residents/<?= htmlspecialchars($row['image_path'] ?? 'default.jpg') ?>"
                                            class="resident-img" alt="" onerror="this.onerror=null; this.src='uploads/residents/default_photo_male.jpg'">
                                    </td>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['middle_name'] ?: '—') ?></td>
                                    <td style="font-weight:600"><?= htmlspecialchars($row['surname']) ?></td>
                                    <td style="font-size:.82rem;color:var(--muted)">
                                        <?= htmlspecialchars($row['suffix'] ?: '—') ?>
                                    </td>
                                    <td><?= (int) $row['age'] ?></td>
                                    <td><?= htmlspecialchars($row['sex']) ?></td>
                                    <td style="font-size:.82rem"><?= htmlspecialchars($row['occupation_type'] ?: '—') ?></td>
                                    <td style="font-size:.82rem">
                                        <?= $row['monthly_income'] !== null
                                            ? '₱' . number_format((float) $row['monthly_income'], 0)
                                            : '<span style="color:var(--muted)">—</span>' ?>
                                    </td>
                                    <td><?= sesBadgePhp($ses) ?></td>
                                    <td>
                                        <?= $badges ? implode(' ', $badges) : '<span style="color:var(--muted)">—</span>' ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                                data-bs-target="#viewResidentModal<?= $row['id'] ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#editModal<?= $row['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteModal" data-id="<?= $row['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php if ($is_superadmin): ?>
                                            <?php if ((int)$row['is_purok_president'] === 1): ?>
                                            <form method="POST" action="promote_president.php" style="display:inline">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="demote">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Demote from Purok President"
                                                    onclick="return confirm('Demote this resident from Purok President?')">
                                                    <i class="fas fa-crown" style="color:#c2410c"></i>
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <form method="POST" action="promote_president.php" style="display:inline">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="promote">
                                                <button type="submit" class="btn btn-sm btn-success" title="Promote to Purok President"
                                                    onclick="return confirm('Promote this resident to Purok President?')">
                                                    <i class="fas fa-crown"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="13" class="no-data">
                                    <i class="fas fa-search"></i>
                                    No residents
                                    found<?= (!empty($search_query) || $purok_filter !== 'all' || $ses_filter !== 'all') ? ' matching your filters' : '' ?>.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1):
                $start_page = max(1, $page - 4);
                $end_page = min($total_pages, $page + 5);
                ?>
                <div class="pagination">
                    <a href="?<?= build_url($page - 1, $search_query, $purok_filter, $ses_filter, $edu_filter) ?>"
                        class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php if ($start_page > 1): ?>
                        <a href="?<?= build_url(1, $search_query, $purok_filter, $ses_filter, $edu_filter) ?>"
                            class="page-btn">1</a>
                        <?php if ($start_page > 2): ?><span style="color:var(--muted);padding:0 4px">…</span><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?<?= build_url($i, $search_query, $purok_filter, $ses_filter, $edu_filter) ?>"
                            class="page-btn <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?><span
                                style="color:var(--muted);padding:0 4px">…</span><?php endif; ?>
                        <a href="?<?= build_url($total_pages, $search_query, $purok_filter, $ses_filter, $edu_filter) ?>"
                            class="page-btn"><?= $total_pages ?></a>
                    <?php endif; ?>
                    <a href="?<?= build_url($page + 1, $search_query, $purok_filter, $ses_filter, $edu_filter) ?>"
                        class="page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <div style="text-align:center;padding:0 16px 16px;font-size:.8rem;color:var(--muted)">
                    Showing <?= $offset + 1 ?>–<?= min($offset + $records_per_page, $total_records) ?> of
                    <?= $total_records ?>
                    residents
                </div>
            <?php endif; ?>

        </div><!-- /.card -->

    </main>

    <!-- ══════════════════════════════════════════════════════════════
     ALL MODALS — rendered OUTSIDE the table and main
     ══════════════════════════════════════════════════════════════ -->

    <?php include 'modals/add_resident_modal.php'; ?>

    <!-- Per-row view & edit modals -->
    <?= $modal_html ?>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">Are you sure you want to archive this resident? They will be moved to the Deleted Residents list and can be restored later.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="delete_residents.php">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('deleteModal').addEventListener('show.bs.modal', function (e) {
            document.getElementById('deleteId').value = e.relatedTarget.getAttribute('data-id');
        });

        // Auto-dismiss flash alerts
        setTimeout(function () {
            document.querySelectorAll('.alert-success,.alert-danger,.alert-info').forEach(function (el) {
                try { bootstrap.Alert.getOrCreateInstance(el).close(); }
                catch (err) { el.style.display = 'none'; }
            });
        }, 5000);
    </script>
</body>

</html>
<?php $conn->close(); ?>