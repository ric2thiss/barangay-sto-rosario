<?php
/**
 * deleted_residents.php — Archived (soft-deleted) residents module.
 * Functions identically to resident.php but queries is_deleted = 1.
 * Provides a "Restore Resident" button to bring them back to active status.
 */
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
$is_purok_president = (($_SESSION['staff_position'] ?? '') === 'Purok President');
if ($_SESSION['user_type'] === 'resident' && !$is_purok_president) {
    header("Location: ../resident/residents.php");
    exit();
}
$is_superadmin = ($_SESSION['user_type'] === 'admin') || (!empty($_SESSION['is_superadmin']));

include("connection.php");
include('sidebar_counts.php');

$records_per_page = 20;
$search_query = isset($_GET['search_query']) ? $conn->real_escape_string(trim($_GET['search_query'])) : '';
$purok_filter = isset($_GET['purok']) ? $conn->real_escape_string($_GET['purok']) : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Purok list from deleted residents
$purok_result = $conn->query("SELECT DISTINCT purok FROM residents WHERE is_deleted = 1 ORDER BY purok ASC");
$puroks = [];
if ($purok_result)
    while ($row = $purok_result->fetch_assoc())
        $puroks[] = $row['purok'];

// Purok President: force their purok filter
if ($is_purok_president) {
    $purok_filter = $_SESSION['purok'] ?? '';
}

// ── WHERE clause ──────────────────────────────────────────────────────────
$where = ['is_deleted = 1'];
if (!empty($search_query))
    $where[] = "(first_name LIKE '%$search_query%'
                 OR middle_name LIKE '%$search_query%'
                 OR surname LIKE '%$search_query%'
                 OR purok LIKE '%$search_query%')";
if ($purok_filter !== 'all')
    $where[] = "purok = '$purok_filter'";
// Purok President RBAC
if ($is_purok_president && $purok_filter === 'all') {
    $pp_purok = $conn->real_escape_string($_SESSION['purok'] ?? '');
    $where[] = "purok = '$pp_purok'";
}

$where_clause = 'WHERE ' . implode(' AND ', $where);

$result = $conn->query("
    SELECT id, first_name, middle_name, surname, suffix,
           age, sex, purok, barangay, occupation_type,
           monthly_income, socioeconomic_status, image_path,
           deleted_at, is_purok_president
    FROM residents
    $where_clause
    ORDER BY deleted_at DESC
    LIMIT $records_per_page OFFSET $offset
");

$count_result = $conn->query("SELECT COUNT(*) as total FROM residents $where_clause");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

function build_url_del($page, $search, $purok) {
    $p = "page=$page";
    if (!empty($search)) $p .= "&search_query=" . urlencode($search);
    if ($purok !== 'all')  $p .= "&purok=" . urlencode($purok);
    return $p;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted Residents — Barangay Sto. Rosario</title>
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

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--body-bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        .sidebar { width: var(--sidebar-w); min-height: 100vh; background: var(--sidebar-bg); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; z-index: 100; overflow-y: auto; }
        .sidebar-brand { padding: 28px 20px 20px; border-bottom: 1px solid rgba(255,255,255,.08); display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .sidebar-brand img { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,.15); }
        .sidebar-brand h2 { color: #fff; font-size: .95rem; font-weight: 700; text-align: center; }
        .sidebar nav { padding: 16px 12px; flex: 1; }
        .sidebar nav ul { list-style: none; display: flex; flex-direction: column; gap: 4px; }
        .sidebar nav a { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; color: rgba(255,255,255,.65); text-decoration: none; font-size: .875rem; font-weight: 500; transition: background .15s, color .15s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar nav a.active { background: var(--primary); }
        .sidebar nav a i { width: 18px; text-align: center; font-size: .9rem; }
        .nav-badge { margin-left: auto; background: #e02424; color: #fff; font-size: .68rem; font-weight: 800; padding: 1px 7px; border-radius: 20px; }

        .main-content { margin-left: var(--sidebar-w); flex: 1; padding: 28px 28px 48px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 1.5rem; font-weight: 800; color: #111827; }
        .page-header p { font-size: .85rem; color: var(--muted); margin-top: 2px; }

        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); }

        .filter-bar { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        .filter-bar label { font-size: .75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .4px; display: block; margin-bottom: 5px; }
        .filter-bar select, .filter-bar input[type=text] { border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: .875rem; color: var(--text); background: #fff; outline: none; transition: border-color .2s; font-family: inherit; }
        .filter-bar select:focus, .filter-bar input[type=text]:focus { border-color: var(--primary); }

        .btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: 8px; font-size: .875rem; font-weight: 600; border: none; cursor: pointer; transition: opacity .15s, transform .1s; text-decoration: none; white-space: nowrap; }
        .btn:hover { opacity: .88; transform: translateY(-1px); }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-secondary { background: #f1f5f9; color: var(--muted); border: 1px solid var(--border); }
        .btn-secondary:hover { color: var(--text); }
        .btn-info { background: var(--info); color: #fff; }
        .btn-warning { background: var(--warning); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 5px 12px; font-size: .8rem; }

        .alert { padding: 12px 16px; border-radius: 8px; font-size: .875rem; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        thead th { background: #f8fafc; color: var(--muted); font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }
        tbody td { padding: 10px 14px; vertical-align: middle; }
        .resident-img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border); }
        .pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; white-space: nowrap; }
        .pill-blue { background: #eff6ff; color: var(--primary); }
        .pill-red { background: #fef2f2; color: var(--danger); }
        .pill-dark { background: #f3f4f6; color: #374151; }
        .pill-gray { background: #f8fafc; color: var(--muted); }
        .pill-amber { background: #fffbeb; color: #92400e; }

        .pagination { display: flex; gap: 4px; justify-content: center; align-items: center; padding: 16px; }
        .page-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 8px; border-radius: 8px; border: 1px solid var(--border); background: #fff; color: var(--text); font-size: .85rem; font-weight: 600; text-decoration: none; transition: all .15s; }
        .page-btn:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
        .page-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; }
        .page-btn.disabled { opacity: .4; pointer-events: none; }

        .no-data { text-align: center; padding: 40px; color: var(--muted); }
        .no-data i { font-size: 2rem; opacity: .3; display: block; margin-bottom: 10px; }
        .action-group { display: flex; gap: 5px; flex-wrap: wrap; }

        .archive-banner { background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: var(--radius); padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .archive-banner i { font-size: 1.3rem; color: #b45309; }
        .archive-banner p { font-size: .85rem; color: #78350f; margin: 0; }
        .archive-banner strong { color: #92400e; }
    </style>
</head>

<body>

    <?php $current_page = 'deleted_residents';
    include 'sidebar.php'; ?>

    <main class="main-content">

        <div class="page-header">
            <h1><i class="fas fa-user-slash" style="color:var(--danger)"></i> Deleted Residents</h1>
            <p>Archived residents that can be restored to the active list</p>
        </div>

        <div class="archive-banner">
            <i class="fas fa-archive"></i>
            <p><strong><?= $total_records ?></strong> resident<?= $total_records != 1 ? 's' : '' ?> in archive. These records are preserved and can be restored at any time.</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">

            <!-- Filter bar -->
            <form method="GET" action="">
                <div class="filter-bar">
                    <div>
                        <label><i class="fas fa-map-marker-alt"></i> Purok</label>
                        <select name="purok" <?= $is_purok_president ? 'disabled' : '' ?>>
                            <option value="all" <?= $purok_filter === 'all' ? 'selected' : '' ?>>All Puroks</option>
                            <?php foreach ($puroks as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $purok_filter === $p ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:1">
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search_query" placeholder="Name, purok…"
                            value="<?= htmlspecialchars($search_query) ?>" style="width:100%">
                    </div>
                    <div style="display:flex;gap:8px;align-items:flex-end">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
                        <a href="deleted_residents.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </div>
            </form>

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
                            <th>Age</th>
                            <th>Sex</th>
                            <th>Deleted On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0):
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="pill pill-blue"><?= htmlspecialchars($row['purok']) ?></span></td>
                                    <td>
                                        <img src="uploads/residents/<?= htmlspecialchars($row['image_path'] ?? 'default.jpg') ?>"
                                            class="resident-img" alt="" onerror="this.onerror=null; this.src='uploads/residents/default_photo_male.jpg'">
                                    </td>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['middle_name'] ?: '—') ?></td>
                                    <td style="font-weight:600"><?= htmlspecialchars($row['surname']) ?></td>
                                    <td><?= (int)$row['age'] ?></td>
                                    <td><?= htmlspecialchars($row['sex']) ?></td>
                                    <td>
                                        <span class="pill pill-amber">
                                            <?= $row['deleted_at'] ? date('M d, Y g:ia', strtotime($row['deleted_at'])) : '—' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <form method="POST" action="restore_resident.php" style="display:inline">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Restore this resident to the active list?')"
                                                    title="Restore Resident">
                                                    <i class="fas fa-undo"></i> Restore
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="9" class="no-data">
                                    <i class="fas fa-inbox"></i>
                                    No archived residents<?= (!empty($search_query) || $purok_filter !== 'all') ? ' matching your filters' : '' ?>.
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
                    <a href="?<?= build_url_del($page - 1, $search_query, $purok_filter) ?>"
                        class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?<?= build_url_del($i, $search_query, $purok_filter) ?>"
                            class="page-btn <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?<?= build_url_del($page + 1, $search_query, $purok_filter) ?>"
                        class="page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <div style="text-align:center;padding:0 16px 16px;font-size:.8rem;color:var(--muted)">
                    Showing <?= $offset + 1 ?>–<?= min($offset + $records_per_page, $total_records) ?> of
                    <?= $total_records ?> archived residents
                </div>
            <?php endif; ?>

        </div><!-- /.card -->

    </main>

    <script>
        // Auto-dismiss flash alerts
        setTimeout(function () {
            document.querySelectorAll('.alert-success,.alert-danger').forEach(function (el) {
                try { bootstrap.Alert.getOrCreateInstance(el).close(); }
                catch (err) { el.style.display = 'none'; }
            });
        }, 5000);
    </script>
</body>
</html>
<?php $conn->close(); ?>
