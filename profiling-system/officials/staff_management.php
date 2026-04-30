<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php"); exit();
}
$allowed_types = ['admin', 'official'];
$is_superadmin = ($_SESSION['user_type'] === 'admin') || (!empty($_SESSION['is_superadmin']));
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    header("Location: ../resident/dashboard.php"); exit();
}

include("connection.php");
include('sidebar_counts.php');

$records_per_page = 20;
$search_query = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

// Officials with a username set = staff accounts
$where = ["username IS NOT NULL AND username != ''"];
if (!empty($search_query))
    $where[] = "(first_name LIKE '%$search_query%' OR surname LIKE '%$search_query%' OR username LIKE '%$search_query%' OR position LIKE '%$search_query%')";
if ($status_filter !== 'all')
    $where[] = "status = '$status_filter'";

$where_clause = 'WHERE '.implode(' AND ', $where);

$result = $conn->query("
    SELECT * FROM barangay_official
    $where_clause
    ORDER BY position, surname
    LIMIT $records_per_page OFFSET $offset
");

$count_result = $conn->query("SELECT COUNT(*) as total FROM barangay_official $where_clause");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_records / $records_per_page));

$total_staff = (int)$conn->query("SELECT COUNT(*) c FROM barangay_official WHERE username IS NOT NULL AND username != ''")->fetch_assoc()['c'];
$active_staff = (int)$conn->query("SELECT COUNT(*) c FROM barangay_official WHERE username IS NOT NULL AND username != '' AND status='Active'")->fetch_assoc()['c'];

$rows = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $rows[] = $row;
}

function staffUrl($page, $q, $status) {
    $u = "?page=$page";
    if ($q !== '') $u .= "&q=".urlencode($q);
    if ($status !== 'all') $u .= "&status=".urlencode($status);
    return $u;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management — Barangay Sto. Rosario</title>
    <?php include 'hybrid_assets.php'; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        :root{--primary:#1a56db;--primary-light:#e8f0fe;--success:#0e9f6e;--danger:#e02424;--warning:#ff8a00;--info:#0891b2;--sidebar-bg:#0f172a;--sidebar-w:250px;--body-bg:#f1f5f9;--card-bg:#fff;--border:#e2e8f0;--text:#1e293b;--muted:#64748b;--radius:12px;--shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--body-bg);color:var(--text);display:flex;min-height:100vh;}
        .sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--sidebar-bg);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:100;overflow-y:auto;}
        .sidebar-brand{padding:28px 20px 20px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;flex-direction:column;align-items:center;gap:10px;}
        .sidebar-brand img{width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.15);}
        .sidebar-brand h2{color:#fff;font-size:.95rem;font-weight:700;text-align:center;}
        .sidebar nav{padding:16px 12px;flex:1;}.sidebar nav ul{list-style:none;display:flex;flex-direction:column;gap:4px;}
        .sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;color:rgba(255,255,255,.65);text-decoration:none;font-size:.875rem;font-weight:500;transition:background .15s,color .15s;}
        .sidebar nav a:hover,.sidebar nav a.active{background:rgba(255,255,255,.1);color:#fff;}
        .sidebar nav a.active{background:var(--primary);}
        .sidebar nav a i{width:18px;text-align:center;font-size:.9rem;}
        .nav-badge{margin-left:auto;background:#e02424;color:#fff;font-size:.68rem;font-weight:800;padding:1px 7px;border-radius:20px;}
        .main-content{margin-left:var(--sidebar-w);flex:1;padding:28px 28px 48px;}
        .page-header{margin-bottom:24px;}.page-header h1{font-size:1.5rem;font-weight:800;color:#111827;}.page-header p{font-size:.85rem;color:var(--muted);margin-top:2px;}
        .card{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);}
        .filter-bar{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;}
        .filter-bar label{font-size:.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:5px;}
        .filter-bar select,.filter-bar input[type=text]{border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:.875rem;color:var(--text);background:#fff;outline:none;font-family:inherit;}
        .btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;font-size:.875rem;font-weight:600;border:none;cursor:pointer;transition:opacity .15s;text-decoration:none;white-space:nowrap;}
        .btn:hover{opacity:.88;}.btn-primary{background:var(--primary);color:#fff;}.btn-success{background:var(--success);color:#fff;}
        .btn-secondary{background:#f1f5f9;color:var(--muted);border:1px solid var(--border);}.btn-info{background:var(--info);color:#fff;}
        .btn-warning{background:var(--warning);color:#fff;}.btn-danger{background:var(--danger);color:#fff;}.btn-sm{padding:5px 12px;font-size:.8rem;}
        .alert{padding:12px 16px;border-radius:8px;font-size:.875rem;margin-bottom:16px;display:flex;align-items:center;gap:10px;}
        .alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;}
        .alert-danger{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;}
        .table-wrap{overflow-x:auto;}table{width:100%;border-collapse:collapse;font-size:.875rem;}
        thead th{background:#f8fafc;color:var(--muted);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:11px 14px;border-bottom:1px solid var(--border);white-space:nowrap;}
        tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s;}tbody tr:hover{background:#f8fafc;}tbody td{padding:10px 14px;vertical-align:middle;}
        .staff-img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
        .pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap;}
        .pill-green{background:#ecfdf5;color:var(--success);}.pill-red{background:#fef2f2;color:var(--danger);}.pill-blue{background:#eff6ff;color:var(--primary);}
        .priv-badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:.68rem;font-weight:600;margin:1px 2px;background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;}
        .priv-badge.off{background:#f8fafc;color:#cbd5e1;border-color:#e2e8f0;text-decoration:line-through;}
        .no-data{text-align:center;padding:40px;color:var(--muted);}.no-data i{font-size:2rem;opacity:.3;display:block;margin-bottom:10px;}
        .action-group{display:flex;gap:5px;flex-wrap:wrap;}
        .stat-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
        .stat-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow);}
        .stat-card .icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;}
        .stat-card .info h3{font-size:1.4rem;font-weight:800;line-height:1;}.stat-card .info p{font-size:.78rem;color:var(--muted);margin-top:2px;}
        .pagination{display:flex;gap:4px;justify-content:center;align-items:center;padding:16px;}
        .page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:8px;border:1px solid var(--border);background:#fff;color:var(--text);font-size:.85rem;font-weight:600;text-decoration:none;transition:all .15s;}
        .page-btn:hover{background:var(--primary-light);border-color:var(--primary);color:var(--primary);}
        .page-btn.active{background:var(--primary);border-color:var(--primary);color:#fff;}.page-btn.disabled{opacity:.4;pointer-events:none;}
    </style>
</head>
<body>

<?php $current_page = 'staff'; include 'sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-user-shield" style="color:var(--primary)"></i> Staff Management</h1>
        <p>Officials with login credentials and system privileges. To add staff, set account credentials on the <a href="barangay_officials.php" style="color:var(--primary);font-weight:600">Barangay Officials</a> page.</p>
    </div>

    <?php if (isset($_SESSION['staff_success'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['staff_success']); unset($_SESSION['staff_success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['staff_error'])): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['staff_error']); unset($_SESSION['staff_error']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="stat-cards">
        <div class="stat-card">
            <div class="icon" style="background:#eff6ff;color:var(--primary)"><i class="fas fa-users"></i></div>
            <div class="info"><h3><?= $total_staff ?></h3><p>Officials with Accounts</p></div>
        </div>
        <div class="stat-card">
            <div class="icon" style="background:#ecfdf5;color:var(--success)"><i class="fas fa-user-check"></i></div>
            <div class="info"><h3><?= $active_staff ?></h3><p>Active</p></div>
        </div>
        <div class="stat-card">
            <div class="icon" style="background:#fef2f2;color:var(--danger)"><i class="fas fa-user-slash"></i></div>
            <div class="info"><h3><?= $total_staff - $active_staff ?></h3><p>Inactive</p></div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="">
            <div class="filter-bar">
                <a href="barangay_officials.php" class="btn btn-success"><i class="fas fa-user-shield"></i> Manage Accounts in Officials</a>
                <div>
                    <label><i class="fas fa-filter"></i> Status</label>
                    <select name="status">
                        <option value="all" <?= $status_filter==='all'?'selected':'' ?>>All Status</option>
                        <option value="Active" <?= $status_filter==='Active'?'selected':'' ?>>Active</option>
                        <option value="Inactive" <?= $status_filter==='Inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </div>
                <div style="flex:1">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="q" placeholder="Name, username, position…" value="<?= htmlspecialchars($search_query) ?>" style="width:100%">
                </div>
                <div style="display:flex;gap:8px;align-items:flex-end">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
                    <a href="staff_management.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>Photo</th><th>Name</th><th>Username</th><th>Position</th>
                    <th>Status</th><th>Privileges</th><th>Last Login</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if (count($rows) > 0): foreach ($rows as $s):
                    $is_sec = (stripos($s['position'], 'Secretary') !== false);
                ?>
                <tr>
                    <td><img src="uploads/officials/<?= htmlspecialchars($s['image_path'] ?? 'default.jpg') ?>" class="staff-img" alt="" onerror="this.onerror=null; this.src='uploads/officials/default_photo_male.jpg'"></td>
                    <td style="font-weight:600">
                        <?= htmlspecialchars($s['first_name'].' '.($s['middle_name']?$s['middle_name'][0].'. ':'').$s['surname'].($s['suffix']?' '.$s['suffix']:'')) ?>
                        <?php if ($is_sec): ?><br><span class="pill pill-blue" style="font-size:.62rem"><i class="fas fa-crown"></i> Superadmin</span><?php endif; ?>
                    </td>
                    <td><code style="font-size:.82rem"><?= htmlspecialchars($s['username']) ?></code></td>
                    <td><span class="pill pill-blue"><?= htmlspecialchars($s['position']) ?></span></td>
                    <td><span class="pill <?= $s['status']==='Active'?'pill-green':'pill-red' ?>"><?= $s['status'] ?></span></td>
                    <td>
                        <?php
                        $privs = ['can_view_residents'=>'View','can_add_resident'=>'Add','can_edit_resident'=>'Edit','can_approve'=>'Approve','can_delete'=>'Delete','can_export'=>'Export','can_manage_staff'=>'Staff','can_view_logs'=>'Logs','can_manage_profile_updates'=>'Profile'];
                        if ($is_sec) {
                            echo '<span class="priv-badge" style="background:#ecfdf5;color:#065f46;border-color:#a7f3d0">All (Superadmin)</span>';
                        } else {
                            foreach ($privs as $col => $label) {
                                $on = (int)$s[$col];
                                echo '<span class="priv-badge '.($on?'':'off').'">'.$label.'</span>';
                            }
                        }
                        ?>
                    </td>
                    <td style="font-size:.78rem;color:var(--muted)"><?= !empty($s['last_login']) ? date('M d, Y g:i A', strtotime($s['last_login'])) : '—' ?></td>
                    <td>
                        <div class="action-group">
                            <a href="barangay_officials.php" class="btn btn-sm btn-info" title="Manage on Officials page"><i class="fas fa-external-link-alt"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="8" class="no-data">
                    <i class="fas fa-user-shield"></i>
                    No staff accounts found<?= (!empty($search_query)||$status_filter!=='all') ? ' matching your filters' : '. Set accounts on the <a href="barangay_officials.php">Officials page</a>.' ?>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1):
            $sp = max(1, $page - 4); $ep = min($total_pages, $page + 5);
        ?>
        <div class="pagination">
            <a href="?<?= staffUrl($page-1,$search_query,$status_filter) ?>" class="page-btn <?= $page<=1?'disabled':'' ?>"><i class="fas fa-chevron-left"></i></a>
            <?php for ($i=$sp; $i<=$ep; $i++): ?>
                <a href="?<?= staffUrl($i,$search_query,$status_filter) ?>" class="page-btn <?= $page==$i?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?<?= staffUrl($page+1,$search_query,$status_filter) ?>" class="page-btn <?= $page>=$total_pages?'disabled':'' ?>"><i class="fas fa-chevron-right"></i></a>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
setTimeout(function(){
    document.querySelectorAll('.alert-success,.alert-danger').forEach(function(el){
        try{bootstrap.Alert.getOrCreateInstance(el).close();}catch(e){el.style.display='none';}
    });
},5000);
</script>
</body>
</html>
<?php $conn->close(); ?>
