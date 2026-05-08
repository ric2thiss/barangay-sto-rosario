<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: landingpage.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

require_once 'connection.php';
// Filtering and Pagination
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base conditions
$where_clauses = ["status = 'pending'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(idNo LIKE ? OR username LIKE ? OR firstName LIKE ? OR lastName LIKE ? OR emailAddress LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= "sssss";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Count total for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_pending = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_pending / $limit);

// Fetch results
$pending_query = "SELECT idNo, username, firstName, lastName, emailAddress FROM users $where_sql ORDER BY id DESC LIMIT ? OFFSET ?";
$pending_stmt = $conn->prepare($pending_query);
$pagination_params = array_merge($params, [$limit, $offset]);
$pagination_types = $types . "ii";
$pending_stmt->bind_param($pagination_types, ...$pagination_params);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();

// Count pending registrations (for badge)
$pending_reg_count = 0;
$reg_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE status = 'pending'");
$reg_stmt->execute();
$pending_reg_count = $reg_stmt->get_result()->fetch_assoc()['cnt'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
    <link rel="stylesheet" type="text/css" href="../css/dashboard.css">
    <title>PENDING ACCOUNTS - STEAM Vladimir Lahora</title>
    <style>
        .badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #cd5434;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            min-width: 20px;
            height: 20px;
            border-radius: 2px;
            padding: 0 6px;
            margin-left: 6px;
        }

        /* Modernized Modal Styles for Steam Theme */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: #171a21;
            border: 1px solid #3d4450;
            border-radius: 4px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            color: #c7d5e0;
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #3d4450;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1b2838;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 22px;
            color: #8f98a0;
            line-height: 1;
        }

        .modal-close:hover {
            color: #fff;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #3d4450;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            background: #1b2838;
        }

        .user-avatar {
            width: 64px;
            height: 64px;
            border-radius: 2px;
            background-color: #2a475e;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .btn-modal-primary {
            padding: 10px 20px;
            background: linear-gradient(to right, #47bfff 5%, #1a44c2 60%);
            color: #fff;
            border: none;
            border-radius: 2px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
        }

        .btn-modal-secondary {
            padding: 10px 20px;
            background-color: #3d4450;
            color: #c7d5e0;
            border: none;
            border-radius: 2px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
        }
    </style>
</head>

<body class="admin-body">
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i> Menu
    </button>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="steam-logo-container" style="margin-bottom: 20px; justify-content: center;">
                <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" style="height: 40px;">
            </div>
            <div style="color: white; font-weight: 800; font-size: 18px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 2px; text-align: center;">
                STEAM PORTAL
            </div>
            <div class="user-info-sidebar">
                <div style="color: #66c0f4; font-weight: 600;"><?php echo htmlspecialchars($username); ?></div>
                <span class="role-badge" style="background: #2a475e; color: #c7d5e0; font-size: 10px; padding: 2px 8px; border-radius: 2px;"><?php echo strtoupper(htmlspecialchars($role)); ?></span>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="user_management.php" class="nav-item"><i class="fas fa-users-cog"></i> User Management</a>
            <a href="pending_registrations.php" class="nav-item active">
                <i class="fas fa-user-clock"></i> Pending Accounts
                <?php if ($pending_reg_count > 0): ?>
                    <span class="badge-count" style="background:#cd5434; color:#fff; font-size:11px; font-weight:700; min-width:20px; height:20px; border-radius:2px; padding:0 6px; margin-left:6px; display:inline-flex; align-items:center; justify-content:center;"><?php echo $pending_reg_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="user_logs.php" class="nav-item"><i class="fas fa-history"></i> Logs</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
        </nav>
        <div class="logout-section">
            <form method="POST" action="logout.php">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-header" style="margin-bottom: 30px;">
            <div>
                <h1><i class="fas fa-user-clock" style="color:#66c0f4; margin-right:8px;"></i> Pending Accounts</h1>
                <p>Approve or reject newly registered system accounts.</p>
            </div>
        </div>

        <section class="users-section">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 4px;">
                <h2 class="section-title" style="margin: 0; color: #fff; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-user-clock" style="color:#66c0f4; margin-right: 10px;"></i> Account Requests
                </h2>

                <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <div style="position:relative;">
                        <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#8f98a0; font-size:14px;"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search pending..." style="padding:10px 12px 10px 35px; background: #23262e; border: 1px solid #000; border-radius: 2px; color: white; font-size:14px; width:220px; outline:none;">
                    </div>

                    <button type="submit" class="steam-btn" style="height: 38px; padding: 0 15px;">SEARCH</button>

                    <?php if (!empty($search)): ?>
                        <a href="pending_registrations.php" style="font-size:13px; color:#cd5434; text-decoration:none; font-weight:600; text-transform: uppercase;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="table-container">
                <table class="steam-table">
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_result && $pending_result->num_rows > 0): ?>
                            <?php while ($user = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['idNo']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></td>
                                    <td><?php echo htmlspecialchars($user['emailAddress']); ?></td>
                                    <td class="actions-cell" style="white-space: nowrap;">
                                        <button class="action-btn btn-view" onclick="viewUser('<?php echo $user['idNo']; ?>')"
                                            title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn btn-success"
                                            onclick="approveUser('<?php echo $user['idNo']; ?>')" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="action-btn btn-danger"
                                            onclick="rejectUser('<?php echo $user['idNo']; ?>')" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:40px; color:#6b7280;">
                                    No pending registrations found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination" style="margin-top:25px; display:flex; justify-content:flex-end; gap:8px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"
                            class="pagination-link">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                            class="pagination-link <?php echo $i === $page ? 'current' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"
                            class="pagination-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- ===================== VIEW USER MODAL ===================== -->
    <div class="modal-overlay" id="viewUserModal">
        <div class="modal-box" style="max-width:800px;">
            <div class="modal-header">
                <h3><i class="fas fa-user" style="color:#0ea5e9; margin-right:8px;"></i> Pending User Details</h3>
                <button class="modal-close" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="viewUserBody">
                <div style="text-align:center; padding:30px; color:#9ca3af;">
                    <i class="fas fa-spinner fa-spin" style="font-size:24px;"></i>
                    <p style="margin-top:10px;">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-modal-secondary" onclick="closeViewModal()">Close</button>
                <button class="btn-modal-primary" onclick="approveFromView()">
                    <i class="fas fa-check"></i> Approve Account
                </button>
            </div>
        </div>
    </div>


    <div id="toast" class="toast"></div>

    <script>
        const currentUserId = '<?php echo $user_id; ?>';
        const currentUserRole = '<?php echo $role; ?>';
        let _viewingUserId = null;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = `toast ${type} show`;
            t.style.borderLeft = type === 'error' ? '4px solid #ef4444' : '4px solid #10b981';
            setTimeout(() => t.classList.remove('show'), 3500);
        }

        /* ---- VIEW USER MODAL ---- */
        function viewUser(userId) {
            _viewingUserId = userId;
            document.getElementById('viewUserModal').classList.add('active');
            document.getElementById('viewUserBody').innerHTML =
                '<div style="text-align:center;padding:30px;color:#9ca3af;"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i><p style="margin-top:10px;">Loading...</p></div>';

            fetch('user_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=edit&user_id=${encodeURIComponent(userId)}`
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        renderViewModal(data.user);
                    } else {
                        document.getElementById('viewUserBody').innerHTML = `<p style="color:red;">${data.message}</p>`;
                    }
                })
                .catch(() => {
                    document.getElementById('viewUserBody').innerHTML = '<p style="color:red;">Failed to load user data.</p>';
                });
        }

        function renderViewModal(u) {
            const initials = ((u.firstName || '?').charAt(0) + (u.lastName || '?').charAt(0)).toUpperCase();
            const statusColor = '#f59e0b'; // Always pending here
            const roleBg = '#6b7280'; // Usually customer for pending

            const fmt = (v) => v ? v : '<span style="color:#9ca3af;">N/A</span>';
            const fmtDate = (v) => v ? new Date(v).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '<span style="color:#9ca3af;">N/A</span>';

            document.getElementById('viewUserBody').innerHTML = `
                <!-- Header -->
                <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid #f3f4f6;">
                    <div class="user-avatar" style="width:72px;height:72px;font-size:26px;">${initials}</div>
                    <div style="flex:1;">
                        <div style="font-size:20px; font-weight:700; color:#111827;">${u.firstName} ${u.lastName}</div>
                        <div style="font-size:13px; color:#6b7280; margin-bottom:8px;">@${u.username} &bull; ${u.emailAddress}</div>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <span style="background:${roleBg}; color:#fff; padding:3px 12px; border-radius:50px; font-size:11px; font-weight:700; text-transform:uppercase;">${u.role.replace('_', ' ')}</span>
                            <span style="background:${statusColor}; color:#fff; padding:3px 12px; border-radius:50px; font-size:11px; font-weight:700; text-transform:uppercase;">PENDING APPROVAL</span>
                        </div>
                    </div>
                </div>

                <!-- Personal Info -->
                <div style="margin-bottom:18px;">
                    <div style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:10px;"><i class="fas fa-id-card" style="margin-right:6px;color:#3b82f6;"></i>Personal Information</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">ID Number</div>
                            <div style="font-size:14px;font-weight:700;color:#111827;">${fmt(u.idNo)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">First Name</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.firstName)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Middle Name</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.middleName)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Last Name</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.lastName)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Extension</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.extension)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Sex</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.sex)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Birthday</div>
                            <div style="font-size:14px;color:#111827;">${fmtDate(u.birthday)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Age</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.age)}</div>
                        </div>
                    </div>
                </div>

                <!-- Address Info -->
                <div style="margin-bottom:18px;">
                    <div style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:10px;"><i class="fas fa-map-marker-alt" style="margin-right:6px;color:#ef4444;"></i>Address</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Purok/Street</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.purok)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Barangay</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.barangay)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Municipality</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.municipality)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Province</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.province)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">Country</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.country)}</div>
                        </div>
                        <div style="background:#f9fafb;padding:10px;border-radius:6px;">
                            <div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:3px;">ZIP Code</div>
                            <div style="font-size:14px;color:#111827;">${fmt(u.zipCode)}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function closeViewModal() {
            document.getElementById('viewUserModal').classList.remove('active');
        }

        function approveFromView() {
            if (_viewingUserId) {
                closeViewModal();
                approveUser(_viewingUserId);
            }
        }

        /* ---- ACTIONS ---- */
        function approveUser(userId) {
            if (!confirm('Approve this user account?')) return;
            fetch('user_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=approve&user_id=${encodeURIComponent(userId)}`
            }).then(r => r.json()).then(data => {
                if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
                else showToast(data.message, 'error');
            }).catch(() => showToast('Error approving user', 'error'));
        }

        function rejectUser(userId) {
            if (!confirm('Reject and delete this user account request?')) return;
            fetch('user_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=reject&user_id=${encodeURIComponent(userId)}`
            }).then(r => r.json()).then(data => {
                if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
                else showToast(data.message, 'error');
            }).catch(() => showToast('Error rejecting user', 'error'));
        }

        /* Close modals on backdrop click */
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function (e) {
                if (e.target === this) this.classList.remove('active');
            });
        });
    </script>

</body>

</html>