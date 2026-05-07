<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php"); exit();
}
$allowed_types = ['admin', 'official'];
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    header("Location: ../resident/barangay_officials.php"); exit();
}
if (empty($_SESSION['user_id'])) {
    session_destroy(); header("Location: index.php"); exit();
}
$is_superadmin = ($_SESSION['user_type'] === 'admin')
    || (!empty($_SESSION['is_superadmin']));

include("connection.php");
include('sidebar_counts.php');

$records_per_page = 20;
$search_query = isset($_GET['search_query'])
    ? $conn->real_escape_string(trim($_GET['search_query'])) : '';
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

if (!empty($search_query)) {
    $sql = "SELECT * FROM barangay_official
            WHERE first_name   LIKE '%$search_query%'
            OR    middle_name  LIKE '%$search_query%'
            OR    surname      LIKE '%$search_query%'
            OR    position     LIKE '%$search_query%'
            OR    chairmanship LIKE '%$search_query%'
            ORDER BY id DESC
            LIMIT $records_per_page OFFSET $offset";
    $count_sql = "SELECT COUNT(*) AS total FROM barangay_official
                  WHERE first_name   LIKE '%$search_query%'
                  OR    middle_name  LIKE '%$search_query%'
                  OR    surname      LIKE '%$search_query%'
                  OR    position     LIKE '%$search_query%'
                  OR    chairmanship LIKE '%$search_query%'";
} else {
    $sql       = "SELECT * FROM barangay_official ORDER BY id DESC
                  LIMIT $records_per_page OFFSET $offset";
    $count_sql = "SELECT COUNT(*) AS total FROM barangay_official";
}

$result        = $conn->query($sql);
$count_result  = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages   = ceil($total_records / $records_per_page);

// Buffer per-row modals (same pattern as resident.php)
$modal_html = '';
if ($result && $result->num_rows > 0) {
    ob_start();
    while ($row = $result->fetch_assoc()) {
        include 'modals/officials/view_official_modal.php';
        include 'modals/officials/edit_official_modal.php';
        // Account / privilege modal
        ?>
        <div class="modal fade" id="accountModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="save_official_account.php">
                        <input type="hidden" name="official_id" value="<?= $row['id'] ?>">
                        <div class="modal-header" style="background:linear-gradient(135deg,#1e3a5f,#1a56db);color:#fff">
                            <h5 class="modal-title"><i class="fas fa-user-shield"></i> Account & Privileges — <?= htmlspecialchars($row['first_name'].' '.$row['surname']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div style="margin-bottom:14px;padding:10px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;font-size:.82rem;color:#0c4a6e">
                                <i class="fas fa-info-circle"></i>
                                <strong>Position:</strong> <?= htmlspecialchars($row['position']) ?>
                                <?php if (stripos($row['position'], 'Secretary') !== false): ?>
                                    <br><span style="color:#065f46;font-weight:700"><i class="fas fa-crown"></i> Superadmin — all privileges granted automatically</span>
                                <?php endif; ?>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                                <div>
                                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($row['username'] ?? '') ?>" placeholder="e.g. juan_cruz" minlength="4" pattern="[a-zA-Z0-9_]+">
                                </div>
                                <div>
                                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;display:block;margin-bottom:4px">Password <small style="text-transform:none;font-weight:400">(leave blank to keep)</small></label>
                                    <input type="password" name="password" class="form-control" placeholder="New password" minlength="6">
                                </div>
                            </div>
                            <h6 style="font-size:.82rem;font-weight:700;color:#1e293b;margin-bottom:10px;border-bottom:1px solid #e2e8f0;padding-bottom:6px"><i class="fas fa-key"></i> System Privileges</h6>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                                <?php
                                $privs = [
                                    'can_view_residents' => ['View Residents', 'fa-eye'],
                                    'can_add_resident'   => ['Add Resident', 'fa-plus'],
                                    'can_edit_resident'  => ['Edit Resident', 'fa-edit'],
                                    'can_approve'        => ['Approve Registrations', 'fa-check-circle'],
                                    'can_delete'         => ['Delete Records', 'fa-trash'],
                                    'can_export'         => ['Export Data', 'fa-file-export'],
                                    'can_manage_staff'   => ['Staff Management', 'fa-user-shield'],
                                    'can_view_logs'      => ['Activity Logs', 'fa-clipboard-list'],
                                    'can_manage_profile_updates' => ['Profile Updates', 'fa-user-edit'],
                                ];
                                foreach ($privs as $key => $info):
                                    $checked = !empty($row[$key]) ? 'checked' : '';
                                ?>
                                <label style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:.82rem;transition:background .15s">
                                    <input type="checkbox" name="<?= $key ?>" <?= $checked ?> style="width:16px;height:16px;accent-color:#1a56db">
                                    <i class="fas <?= $info[1] ?>" style="color:#64748b;width:16px;text-align:center"></i>
                                    <?= $info[0] ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
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
    <title>Barangay Officials — Sto. Rosario</title>
    <?php include 'hybrid_assets.php'; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        :root {
            --primary:#1a56db; --primary-light:#e8f0fe;
            --success:#0e9f6e; --danger:#e02424; --warning:#ff8a00; --info:#0891b2;
            --sidebar-bg:#0f172a; --sidebar-w:250px;
            --body-bg:#f1f5f9; --card-bg:#fff;
            --border:#e2e8f0; --text:#1e293b; --muted:#64748b;
            --radius:12px; --shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--body-bg);color:var(--text);display:flex;min-height:100vh;}

        /* Sidebar */
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

        /* Main */
        .main-content{margin-left:var(--sidebar-w);flex:1;padding:28px 28px 48px;}
        .page-header{margin-bottom:24px;}
        .page-header h1{font-size:1.5rem;font-weight:800;color:#111827;}
        .page-header p{font-size:.85rem;color:var(--muted);margin-top:2px;}

        /* Card */
        .card{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);}

        /* Filter bar */
        .filter-bar{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;}
        .filter-bar label{font-size:.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:5px;}
        .filter-bar select,.filter-bar input[type=text]{border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:.875rem;color:var(--text);background:#fff;outline:none;transition:border-color .2s;font-family:inherit;}
        .filter-bar select:focus,.filter-bar input[type=text]:focus{border-color:var(--primary);}

        /* Buttons */
        .btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;font-size:.875rem;font-weight:600;border:none;cursor:pointer;transition:opacity .15s,transform .1s;text-decoration:none;white-space:nowrap;}
        .btn:hover{opacity:.88;transform:translateY(-1px);}
        .btn-primary  {background:var(--primary);color:#fff;}
        .btn-success  {background:var(--success);color:#fff;}
        .btn-secondary{background:#f1f5f9;color:var(--muted);border:1px solid var(--border);}
        .btn-secondary:hover{color:var(--text);}
        .btn-info     {background:var(--info);color:#fff;}
        .btn-warning  {background:var(--warning);color:#fff;}
        .btn-danger   {background:var(--danger);color:#fff;}
        .btn-sm{padding:5px 12px;font-size:.8rem;}

        /* Alert */
        .alert{padding:12px 16px;border-radius:8px;font-size:.875rem;margin-bottom:16px;display:flex;align-items:center;gap:10px;}
        .alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;}
        .alert-danger {background:#fef2f2;color:#991b1b;border:1px solid #fecaca;}

        /* Table */
        .table-wrap{overflow-x:auto;}
        table{width:100%;border-collapse:collapse;font-size:.875rem;}
        thead th{background:#f8fafc;color:var(--muted);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:11px 14px;border-bottom:1px solid var(--border);white-space:nowrap;}
        tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s;}
        tbody tr:last-child{border-bottom:none;}
        tbody tr:hover{background:#f8fafc;}
        tbody td{padding:10px 14px;vertical-align:middle;}
        .official-img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}

        /* Pills */
        .pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap;}
        .pill-blue  {background:#eff6ff;color:var(--primary);}
        .pill-green {background:#ecfdf5;color:var(--success);}
        .pill-red   {background:#fef2f2;color:var(--danger);}

        /* Pagination */
        .pagination{display:flex;gap:4px;justify-content:center;align-items:center;padding:16px;}
        .page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:8px;border:1px solid var(--border);background:#fff;color:var(--text);font-size:.85rem;font-weight:600;text-decoration:none;transition:all .15s;}
        .page-btn:hover{background:var(--primary-light);border-color:var(--primary);color:var(--primary);}
        .page-btn.active{background:var(--primary);border-color:var(--primary);color:#fff;}
        .page-btn.disabled{opacity:.4;pointer-events:none;}

        .no-data{text-align:center;padding:40px;color:var(--muted);}
        .no-data i{font-size:2rem;opacity:.3;display:block;margin-bottom:10px;}
        .action-group{display:flex;gap:5px;flex-wrap:wrap;}
    </style>
</head>
<body>

<?php $current_page = 'officials'; include 'sidebar.php'; ?>

<main class="main-content">

    <div class="page-header">
        <h1><i class="fas fa-user-tie" style="color:var(--primary)"></i> Barangay Officials</h1>
        <p>Manage all active and inactive barangay officials</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card">

        <!-- Filter bar -->
        <form method="GET" action="">
            <div class="filter-bar">
                <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addOfficialModal">
                    <i class="fas fa-plus"></i> Add Official
                </button>

                <!-- Search -->
                <div style="flex:1">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search_query"
                           placeholder="Name, position, chairmanship…"
                           value="<?= htmlspecialchars($search_query) ?>" style="width:100%">
                </div>

                <div style="display:flex;gap:8px;align-items:flex-end">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Search</button>
                    <a href="barangay_officials.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </div>
        </form>

        <?php if (!empty($search_query)): ?>
        <div style="padding:10px 20px;border-bottom:1px solid var(--border);display:flex;gap:8px;align-items:center;background:#f8fafc;">
            <span style="font-size:.78rem;color:var(--muted)"><strong>Active filters:</strong></span>
            <span class="pill pill-blue">Search: <?= htmlspecialchars($search_query) ?></span>
            <span class="pill" style="background:#f8fafc;color:var(--muted)"><?= $total_records ?> result<?= $total_records!=1?'s':'' ?></span>
        </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Surname</th>
                        <th>Position</th>
                        <th>Chairmanship</th>
                        <th>Term</th>
                        <th>Status</th>
                        <th>Account</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $term = date('M Y', strtotime($row['term_start']))
                              . ' – '
                              . date('M Y', strtotime($row['term_end']));
                ?>
                <tr>
                    <td>
                        <img src="uploads/officials/<?= htmlspecialchars($row['image_path']) ?>"
                             class="official-img" alt=""
                             onerror="this.onerror=null; this.src='uploads/officials/default_photo_male.jpg'">
                    </td>
                    <td style="font-weight:600"><?= htmlspecialchars($row['first_name']) ?></td>
                    <td><?= htmlspecialchars($row['middle_name'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($row['surname']) ?></td>
                    <td><span class="pill pill-blue"><?= htmlspecialchars($row['position']) ?></span></td>
                    <td><?= htmlspecialchars($row['chairmanship'] ?: '—') ?></td>
                    <td style="font-size:.8rem;color:var(--muted)"><?= $term ?></td>
                    <td>
                        <span class="pill <?= $row['status'] === 'Active' ? 'pill-green' : 'pill-red' ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($row['username'])): ?>
                            <span class="pill pill-green"><i class="fas fa-check"></i> <?= htmlspecialchars($row['username']) ?></span>
                        <?php else: ?>
                            <span class="pill pill-red"><i class="fas fa-times"></i> None</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-group">
                            <button class="btn btn-sm btn-info"
                                    data-bs-toggle="modal"
                                    data-bs-target="#viewOfficialModal<?= $row['id'] ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editOfficialModal<?= $row['id'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteModal"
                                    data-id="<?= $row['id'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php if ($is_superadmin): ?>
                            <button class="btn btn-sm" style="background:#1e3a5f;color:#fff"
                                    data-bs-toggle="modal"
                                    data-bs-target="#accountModal<?= $row['id'] ?>">
                                <i class="fas fa-user-shield"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="10" class="no-data">
                        <i class="fas fa-search"></i>
                        No officials found<?= !empty($search_query) ? ' matching your filters' : '' ?>.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1):
            $start_page = max(1, $page - 4);
            $end_page   = min($total_pages, $page + 5);
        ?>
        <div class="pagination">
            <a href="?page=<?= $page-1 ?><?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>"
               class="page-btn <?= $page<=1?'disabled':'' ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php if ($start_page > 1): ?>
                <a href="?page=1<?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>" class="page-btn">1</a>
                <?php if ($start_page > 2): ?><span style="color:var(--muted);padding:0 4px">…</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($i=$start_page; $i<=$end_page; $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>"
                   class="page-btn <?= $page==$i?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages-1): ?><span style="color:var(--muted);padding:0 4px">…</span><?php endif; ?>
                <a href="?page=<?= $total_pages ?><?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>" class="page-btn"><?= $total_pages ?></a>
            <?php endif; ?>
            <a href="?page=<?= $page+1 ?><?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>"
               class="page-btn <?= $page>=$total_pages?'disabled':'' ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <div style="text-align:center;padding:0 16px 16px;font-size:.8rem;color:var(--muted)">
            Showing <?= min(($page - 1) * $records_per_page + 1, $total_records) ?>–<?= min($page * $records_per_page, $total_records) ?> of <?= $total_records ?> officials
        </div>
        <?php endif; ?>

    </div><!-- /.card -->

</main>

<!-- ALL MODALS — rendered OUTSIDE the table and main -->

<?php include 'modals/officials/add_official_modal.php'; ?>

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
            <div class="modal-body">Are you sure you want to delete this official? This action cannot be undone.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="delete_officials.php">
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