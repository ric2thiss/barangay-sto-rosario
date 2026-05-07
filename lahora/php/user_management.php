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
require_once 'user_logger.php';

// Count pending registrations (for badge)
$pending_reg_count = 0;
$reg_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE status = 'pending'");
$reg_stmt->execute();
$pending_reg_count = $reg_stmt->get_result()->fetch_assoc()['cnt'];

// Filtering and Pagination
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role_filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Base conditions
$where_clauses = ["status != 'pending'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(username LIKE ? OR firstName LIKE ? OR lastName LIKE ? OR emailAddress LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($role_filter)) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Count total for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Fetch results
$users_query = "SELECT idNo, username, firstName, lastName, emailAddress, role, status FROM users $where_sql ORDER BY role, lastName, firstName LIMIT ? OFFSET ?";
$users_stmt = $conn->prepare($users_query);
$pagination_params = array_merge($params, [$limit, $offset]);
$pagination_types = $types . "ii";
$users_stmt->bind_param($pagination_types, ...$pagination_params);
$users_stmt->execute();
$users_result = $users_stmt->get_result();

// Handle add user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $newUsername = trim($_POST['username'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $extension = trim($_POST['extension'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirmPassword'] ?? '';
    $userRole = $_POST['role'] ?? '';
    $idNo = trim($_POST['idNo'] ?? '');
    $sex = $_POST['sex'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $purok = trim($_POST['purok'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $municipality = trim($_POST['municipality'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $zipCode = trim($_POST['zipCode'] ?? '');



    $errors = [];

    // Required validations
    if (empty($newUsername))
        $errors[] = 'Username is required';
    if (empty($firstName))
        $errors[] = 'First name is required';
    if (empty($lastName))
        $errors[] = 'Last name is required';
    if (empty($email))
        $errors[] = 'Email is required';
    if (empty($password))
        $errors[] = 'Password is required';
    if ($password !== $confirmPass)
        $errors[] = 'Passwords do not match';
    if (strlen($password) < 8)
        $errors[] = 'Password must be at least 8 characters';
    if (empty($userRole))
        $errors[] = 'Role is required';
    if (empty($idNo))
        $errors[] = 'ID Number is required';
    if (empty($sex))
        $errors[] = 'Sex is required';
    if (empty($birthday))
        $errors[] = 'Birthday is required';
    if (empty($purok))
        $errors[] = 'Purok/Street is required';
    if (empty($barangay))
        $errors[] = 'Barangay is required';
    if (empty($municipality))
        $errors[] = 'Municipality is required';
    if (empty($province))
        $errors[] = 'Province is required';
    if (empty($country))
        $errors[] = 'Country is required';
    if (empty($zipCode))
        $errors[] = 'ZIP Code is required';


    if (!in_array($userRole, ['admin', 'super_admin', 'customer'])) {
        $errors[] = 'Invalid role';
    } elseif ($role === 'admin' && $userRole === 'super_admin') {
        $errors[] = 'Admins cannot create Super Admin accounts';
    }

    if (!empty($newUsername)) {
        $c = $conn->prepare("SELECT idNo FROM users WHERE username = ?");
        $c->bind_param("s", $newUsername);
        $c->execute();
        if ($c->get_result()->num_rows > 0)
            $errors[] = 'Username already exists';
    }
    if (!empty($email)) {
        $c = $conn->prepare("SELECT idNo FROM users WHERE emailAddress = ?");
        $c->bind_param("s", $email);
        $c->execute();
        if ($c->get_result()->num_rows > 0)
            $errors[] = 'Email already exists';
    }
    if (!empty($idNo)) {
        $c = $conn->prepare("SELECT idNo FROM users WHERE idNo = ?");
        $c->bind_param("s", $idNo);
        $c->execute();
        if ($c->get_result()->num_rows > 0)
            $errors[] = 'ID Number already exists';
    }

    // Calculate age
    $age = 0;
    if (!empty($birthday)) {
        $dob = new DateTime($birthday);
        $now = new DateTime();
        $age = $dob->diff($now)->y;
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Set status to 'incomplete' so they are redirected to complete_profile.php upon login
        // or the admin can be redirected now if they need to fill more info.
        // However, since we removed security questions, they MUST be filled.

        $ins = $conn->prepare("INSERT INTO users (idNo, username, firstName, middleName, lastName, extension, sex, birthday, age, emailAddress, password, role, status, purok, barangay, municipality, province, country, zipCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'incomplete', ?, ?, ?, ?, ?, ?)");
        
        $ins->bind_param(
            "ssssssssisssssssss",
            $idNo,
            $newUsername,
            $firstName,
            $middleName,
            $lastName,
            $extension,
            $sex,
            $birthday,
            $age,
            $email,
            $hashed,
            $userRole,
            $purok,
            $barangay,
            $municipality,
            $province,
            $country,
            $zipCode
        );
        if ($ins->execute()) {
            logAction('CREATE_USER', "User {$username} created new user {$newUsername}");
            header("Location: user_management.php?success=1");
            exit();
        } else {
            $errors[] = "Failed to add user. Please try again.";
        }
    }
}

$last_id_result = $conn->query("SELECT idNo FROM users ORDER BY id DESC LIMIT 1");
$last_id = ($last_id_result && $last_id_result->num_rows > 0) ? $last_id_result->fetch_assoc()['idNo'] : '';

// No need to re-fetch here as the variables are handled above
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
    <title>USER MANAGEMENT - STEAM Vladimir Lahora</title>
    <style>
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
            background: #fff;
            border-radius: 8px;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            color: #1f2937;
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #111827;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            color: #9ca3af;
            line-height: 1;
        }

        .modal-close:hover {
            color: #111827;
        }

        .modal-body {
            padding: 25px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-field {
            margin-bottom: 15px;
        }

        .form-field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-field input,
        .form-field select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #111827;
            background: #fff;
            outline: none;
            transition: all 0.2s;
        }

        .form-field input:focus,
        .form-field select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #f9fafb;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .btn-modal-primary {
            padding: 10px 20px;
            background: #111827;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-modal-primary:hover {
            background: #374151;
        }

        .btn-modal-secondary {
            padding: 10px 20px;
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-modal-secondary:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        .action-btn {
            background: #3d4450;
            color: #c7d5e0;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #66c0f4;
            color: white;
        }

        .btn-confirm {
            background: #66c0f4;
            color: white;
            width: auto;
            padding: 0 20px;
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
            <a href="user_management.php" class="nav-item active"><i class="fas fa-users-cog"></i> User Management</a>
            <a href="pending_registrations.php" class="nav-item">
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
        <div class="content-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom: 30px;">
            <div>
                <h1><i class="fas fa-users-cog" style="color:#66c0f4; margin-right:8px;"></i> User Management</h1>
                <p>Manage system accounts, administrators, and client permissions.</p>
            </div>
            <button class="steam-btn" onclick="openAddUserModal()" title="Add New User">
                <i class="fas fa-user-plus"></i> ADD USER
            </button>
        </div>

        <?php if (isset($success_message)): ?>
            <div
                style="padding:15px; background-color:#dcfce7; color:#166534; border-radius:8px; margin-bottom:20px; border:1px solid #bdf0cc;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <section class="users-section">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 4px;">
                <h2 class="section-title" style="margin: 0; color: #fff; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-users" style="color:#66c0f4; margin-right: 10px;"></i> System Accounts
                </h2>

                <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <div style="position:relative;">
                        <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#8f98a0; font-size:14px;"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search users..." style="padding:10px 12px 10px 35px; background: #23262e; border: 1px solid #000; border-radius: 2px; color: white; font-size:14px; width:220px; outline:none;">
                    </div>

                    <button type="submit" class="steam-btn" style="height: 38px; padding: 0 15px;">SEARCH</button>

                    <select name="role_filter" onchange="this.form.submit()" style="padding:10px 12px; background: #23262e; border: 1px solid #000; border-radius: 2px; color: white; font-size:14px; outline:none; cursor:pointer;">
                        <option value="">All Roles</option>
                        <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="super_admin" <?php echo $role_filter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                    </select>

                    <select name="status_filter" onchange="this.form.submit()" style="padding:10px 12px; background: #23262e; border: 1px solid #000; border-radius: 2px; color: white; font-size:14px; outline:none; cursor:pointer;">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        <option value="incomplete" <?php echo $status_filter === 'incomplete' ? 'selected' : ''; ?>>Incomplete</option>
                    </select>

                    <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                        <a href="user_management.php" style="font-size:13px; color:#cd5434; text-decoration:none; font-weight:600; text-transform: uppercase;">Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="table-container">
                <table class="steam-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result && $users_result->num_rows > 0): ?>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['idNo']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></td>
                                    <td><?php echo htmlspecialchars($user['emailAddress']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge status-<?php echo htmlspecialchars($user['status'] ?? 'active'); ?>">
                                            <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell" style="white-space: nowrap;">
                                        <?php
                                        $is_own_account = ($user['idNo'] === $user_id);
                                        $target_is_superadmin = ($user['role'] === 'super_admin');
                                        $show_edit = false;
                                        $show_block = false;
                                        $show_delete = false;

                                        if ($role === 'super_admin') {
                                            $show_edit = true;
                                            if (!$is_own_account) {
                                                $show_block = true;
                                                $show_delete = true;
                                            }
                                        } elseif ($role === 'admin') {
                                            if ($is_own_account) {
                                                $show_edit = true;
                                            } elseif (!$target_is_superadmin) {
                                                $show_edit = true;
                                                $show_block = true;
                                            }
                                        }
                                        ?>

                                        <button class="action-btn btn-view" onclick="viewUser('<?php echo $user['idNo']; ?>')"
                                            title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if ($show_edit): ?>
                                            <button class="action-btn btn-edit" onclick="editUser('<?php echo $user['idNo']; ?>')"
                                                title="Edit">
                                                <i class="fas fa-pencil-alt"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (($user['status'] ?? 'active') === 'active' && $show_block): ?>
                                            <button class="action-btn btn-warn" onclick="blockUser('<?php echo $user['idNo']; ?>')"
                                                title="Block">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php elseif (($user['status'] ?? '') === 'blocked' && $show_block): ?>
                                            <button class="action-btn btn-success"
                                                onclick="unblockUser('<?php echo $user['idNo']; ?>')" title="Unblock">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($show_delete): ?>
                                            <button class="action-btn btn-danger"
                                                onclick="deleteUser('<?php echo $user['idNo']; ?>', '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>')"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px; color:#6b7280;">
                                    No users found in the system.
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo urlencode($role_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>"
                            class="pagination-link">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo urlencode($role_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>"
                            class="pagination-link <?php echo $i === $page ? 'current' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role_filter=<?php echo urlencode($role_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>"
                            class="pagination-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- ===================== ADD USER MODAL ===================== -->
    <!-- ===================== ADD USER MODAL ===================== -->
    <div class="modal-overlay" id="addUserModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus" style="color:#6366f1; margin-right:8px;"></i> Add New User</h3>
                <button class="modal-close" onclick="closeAddUserModal()">&times;</button>
            </div>

            <?php if (!empty($errors)): ?>
                <div
                    style="margin:15px 25px 0; padding:12px; background-color:#fee2e2; color:#991b1b; border-radius:6px; border:1px solid #fecaca; font-size:14px;">
                    <?php foreach ($errors as $e): ?>
                        <p style="margin:0;"><?php echo htmlspecialchars($e); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="modal-body" id="addUserForm">
                <input type="hidden" name="action" value="add_user">

                <!-- Account & Role -->
                <h4
                    style="margin-bottom: 15px; color: #111827; font-size: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
                    Account Details</h4>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>ID Number <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="idNo" id="addIdNo" placeholder="e.g. 2026-0001" maxlength="9">
                        <small style="color:#9ca3af; font-size:12px;">Last:
                            <strong><?php echo htmlspecialchars($last_id ?: 'None'); ?></strong></small>
                        <div class="field-error" id="addIdNo-error"></div>
                    </div>
                    <div class="form-field">
                        <label>Username <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="username" id="addUsername">
                        <div class="field-error" id="addUsername-error"></div>
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Email <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="email" id="addEmail">
                        <div class="field-error" id="addEmail-error"></div>
                    </div>
                    <div class="form-field">
                        <label>Role <span style="color:#ef4444;">*</span></label>
                        <select name="role" id="addRole">
                            <option value="">Select Role</option>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                            <?php if ($role === 'super_admin'): ?>
                                <option value="super_admin">Super Admin</option>
                            <?php endif; ?>
                        </select>
                        <div class="field-error" id="addRole-error"></div>
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Password <span style="color:#ef4444;">*</span></label>
                        <input type="password" name="password" id="addPassword">
                        <div class="field-error" id="addPassword-error"></div>
                        <div class="password-strength-bar">
                            <div class="bar-fill" id="addPasswordStrengthBar"></div>
                        </div>
                        <div class="field-success" id="addPassword-strength"></div>
                    </div>
                    <div class="form-field">
                        <label>Confirm Password <span style="color:#ef4444;">*</span></label>
                        <input type="password" name="confirmPassword" id="addConfirmPassword">
                        <div class="field-error" id="addConfirmPassword-error"></div>
                        <div class="field-success" id="addConfirmPassword-success"></div>
                    </div>
                </div>

                <!-- Personal Information -->
                <h4
                    style="margin-bottom: 15px; margin-top: 25px; color: #111827; font-size: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
                    Personal Information</h4>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>First Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="firstName" id="addFirstName">
                        <div class="field-error" id="addFirstName-error"></div>
                    </div>
                    <div class="form-field">
                        <label>Middle Name (Optional)</label>
                        <input type="text" name="middleName" id="addMiddleName" placeholder="Full Middle Name">
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Last Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lastName" id="addLastName">
                        <div class="field-error" id="addLastName-error"></div>
                    </div>
                    <div class="form-field">
                        <label>Extension (Optional)</label>
                        <input type="text" name="extension" id="addExtension" placeholder="e.g. Jr, Sr">
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Sex <span style="color:#ef4444;">*</span></label>
                        <select name="sex" id="addSex">
                            <option value="" disabled selected hidden>Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        <div class="field-error" id="addSex-error"></div>
                    </div>
                    <div class="form-field">
                        <label>Birthday <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="birthday" id="addBirthday" max="<?php echo date('Y-m-d'); ?>">
                        <div class="field-error" id="addBirthday-error"></div>
                    </div>
                </div>

                <!-- Permanent Address -->
                <h4
                    style="margin-bottom: 15px; margin-top: 25px; color: #111827; font-size: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
                    Permanent Address</h4>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Purok / Street <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="purok" id="addPurok">
                    </div>
                    <div class="form-field">
                        <label>Barangay <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="barangay" id="addBarangay">
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Municipality <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="municipality" id="addMunicipality">
                    </div>
                    <div class="form-field">
                        <label>Province <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="province" id="addProvince">
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Country <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="country" id="addCountry">
                    </div>
                    <div class="form-field">
                        <label>ZIP Code <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="zipCode" id="addZipCode">
                    </div>
                </div>

                <!-- Security questions removed to revert to old design -->



                <div class="modal-footer"
                    style="padding: 15px 0 0; margin-top: 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-modal-secondary" onclick="closeAddUserModal()">Cancel</button>
                    <button type="submit" class="btn-modal-primary" id="addUserSubmitBtn"><i class="fas fa-save"></i>
                        Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== VIEW USER MODAL ===================== -->
    <div class="modal-overlay" id="viewUserModal">
        <div class="modal-box" style="max-width:800px;">
            <div class="modal-header">
                <h3><i class="fas fa-user" style="color:#0ea5e9; margin-right:8px;"></i> User Details</h3>
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
                <button class="btn-modal-primary" id="viewEditBtn" style="display:none;"
                    onclick="triggerEditFromView()">
                    <i class="fas fa-pencil-alt"></i> <span id="viewEditBtnLabel">Edit User</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ===================== EDIT USER MODAL ===================== -->
    <div class="modal-overlay" id="editUserModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="editModalTitle"><i class="fas fa-pencil-alt" style="color:#6366f1; margin-right:8px;"></i> Edit
                    User</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editUserForm" class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="editUserId">

                <!-- Account & Role -->
                <h4
                    style="margin-bottom: 15px; color: #111827; font-size: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
                    Account Details</h4>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>ID Number</label>
                        <input type="text" id="editIdNoDisplay" disabled
                            style="background-color: #f3f4f6; cursor: not-allowed;">
                    </div>
                    <div class="form-field">
                        <label>Username <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="username" id="editUsername" required>
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Email <span style="color:#ef4444;">*</span></label>
                        <input type="email" name="email" id="editEmail" required>
                    </div>
                    <div class="form-field">
                        <label>Role <span style="color:#ef4444;">*</span></label>
                        <select name="role" id="editRole" required>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                            <?php if ($role === 'super_admin'): ?>
                                <option value="super_admin">Super Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- Personal Information -->
                <h4
                    style="margin-bottom: 15px; margin-top: 25px; color: #111827; font-size: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
                    Personal Information</h4>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>First Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="firstName" id="editFirstName" required>
                    </div>
                    <div class="form-field">
                        <label>Middle Name (Optional)</label>
                        <input type="text" name="middleName" id="editMiddleName" placeholder="Full Middle Name">
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Last Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="lastName" id="editLastName" required>
                    </div>
                    <div class="form-field">
                        <label>Extension (Optional)</label>
                        <input type="text" name="extension" id="editExtension" placeholder="e.g. Jr, Sr">
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Sex <span style="color:#ef4444;">*</span></label>
                        <select name="sex" id="editSex" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Birthday <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="birthday" id="editBirthday" required
                            max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <!-- Permanent Address -->
                <h4
                    style="margin-bottom: 15px; margin-top: 25px; color: #111827; font-size: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
                    Permanent Address</h4>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Purok / Street <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="purok" id="editPurok" required>
                    </div>
                    <div class="form-field">
                        <label>Barangay <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="barangay" id="editBarangay" required>
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Municipality <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="municipality" id="editMunicipality" required>
                    </div>
                    <div class="form-field">
                        <label>Province <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="province" id="editProvince" required>
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:18px;">
                    <div class="form-field">
                        <label>Country <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="country" id="editCountry" required>
                    </div>
                    <div class="form-field">
                        <label>ZIP Code <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="zipCode" id="editZipCode" required>
                    </div>
                </div>

                <?php if ($role === 'super_admin'): ?>
                    <!-- Password Update Section — Super Admin only -->
                    <div id="editPasswordSection"
                        style="margin-bottom:18px; padding:16px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;">
                        <div
                            style="font-size:13px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:12px;">
                            <i class="fas fa-lock" style="color:#f59e0b; margin-right:6px;"></i> Update Password <span
                                style="font-weight:400; color:#9ca3af; font-size:11px;">(Super Admin Only — leave blank to
                                keep current)</span>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-field">
                                <label>New Password</label>
                                <input type="password" name="new_password" id="editNewPassword"
                                    placeholder="Leave blank to keep current" autocomplete="new-password">
                                <small style="color:#9ca3af; font-size:11px;">Min 8 chars with uppercase, number & special
                                    char</small>
                            </div>
                            <div class="form-field">
                                <label>Confirm New Password</label>
                                <input type="password" id="editConfirmPassword" placeholder="Confirm new password">
                                <div id="editPasswordMatchMsg" style="font-size:12px; margin-top:4px;"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="modal-footer"
                    style="padding: 15px 0 0; margin-top: 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-modal-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-modal-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== DELETE REASON MODAL ===================== -->
    <div class="modal-overlay" id="deleteReasonModal">
        <div class="modal-box narrow">
            <div class="modal-header">
                <h3><i class="fas fa-trash" style="color:#ef4444; margin-right:8px;"></i> Confirm Deletion</h3>
                <button class="modal-close" onclick="closeDeleteReasonModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px; color:#374151; margin-bottom:12px;">
                    You are requesting to delete user <strong id="deleteTargetName"></strong>.
                    This requires approval from another active super admin.
                </p>
                <div class="delete-reason-area">
                    <label
                        style="font-size:13px; font-weight:600; color:#4b5563; display:block; margin-bottom:6px;">Reason
                        (optional)</label>
                    <textarea id="deleteReason" placeholder="Provide a reason for this deletion request..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-modal-secondary" onclick="closeDeleteReasonModal()">Cancel</button>
                <button class="btn-modal-primary" style="background-color:#ef4444;" onclick="confirmDeleteRequest()">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const currentUserRole = "<?php echo htmlspecialchars($role); ?>";
        const currentUserId = "<?php echo htmlspecialchars($user_id); ?>";
        let _viewingUserId = null;
        let _deleteTargetId = null;

        /* ---- Toast ---- */
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = `toast ${type} show`;
            t.style.borderLeft = type === 'error' ? '4px solid #ef4444' : '4px solid #10b981';
            setTimeout(() => t.classList.remove('show'), 3500);
        }

        /* ---- Sidebar ---- */
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        /* ================= ADD USER VALIDATION ================= */
        // Re-use validation logic from register.js
        const isEmpty = (value, label) => !value.trim() ? `${label} is required.` : null;
        const hasWhiteSpaces = (value) => /\s/.test(value) ? "Cannot contain white spaces." : null;
        const hasDoubleSpaces = (value) => /\s{2,}/.test(value) ? "Cannot contain double spaces." : null;
        const hasTripleConsecutiveLetters = (value) => { const n = value.toLowerCase(); return /(.)\1\1/.test(n) ? "Cannot have 3 identical letters." : null; };
        const hasContainsNumber = (input, label) => /\d/.test(input) ? `${label} must not contain number.` : null;
        const hasCheckMinMaxLength = (value, label, min, max) => {
            const len = value.trim().length;
            if (len < min) return `${label} must be at least ${min} characters.`;
            if (len > max) return `${label} must be ${max} characters or fewer.`;
            return null;
        };
        const hasRejectAllCapsv2 = (value) => { if (!/[A-Za-z]/.test(value)) return null; return value === value.toUpperCase() ? "Do not use all capital letters." : null; };
        const hasRejectAllCaps = (value) => { if (!/[A-Za-z]/.test(value)) return null; return /[A-Z]{2,}/.test(value) ? "Do not use all capital letters." : null; };

        const validateAddIDNumber = (value) => {
            const trimmed = value.trim();
            const emptyErr = isEmpty(trimmed, "ID Number");
            if (emptyErr) return emptyErr;
            if (!/^\d{4}-\d{4}$/.test(trimmed)) return "ID Number must be in the format XXXX-XXXX.";
            return null;
        };

        const findFirstErrorPosition = (value, label) => {
            const trimmed = value.trim();
            const emptyError = isEmpty(trimmed, label);
            if (emptyError) return emptyError;
            if (hasDoubleSpaces(value)) return hasDoubleSpaces(value);

            const errors = [];
            for (let i = 0; i < trimmed.length; i++) {
                const char = trimmed[i];
                if (/\d/.test(char)) errors.push({ position: i, error: `${label} must not contain number.` });
                if (/[^a-zA-Z0-9 ñÑ]/.test(char)) errors.push({ position: i, error: "Cannot contain special character." });
                if (i >= 2) {
                    const threeChars = trimmed.substring(i - 2, i + 1).toLowerCase();
                    if (/(.)\1\1/.test(threeChars)) errors.push({ position: i - 2, error: "Cannot have 3 identical letters." });
                }
            }
            const words = trimmed.split(/\s+/);
            for (let word of words) {
                if (word.length > 0 && word[0] !== word[0].toUpperCase()) {
                    errors.push({ position: trimmed.indexOf(word), error: "Each word must start with a capital letter." });
                }
            }
            if (/[A-Za-z]/.test(trimmed) && /[A-Z]{2,}/.test(trimmed)) {
                for (let i = 0; i < trimmed.length - 1; i++) {
                    if (/[A-Z]/.test(trimmed[i]) && /[A-Z]/.test(trimmed[i + 1])) {
                        errors.push({ position: i, error: "Do not use all capital letters." });
                        break;
                    }
                }
            }
            if (errors.length > 0) {
                return errors.reduce((earliest, current) => current.position < earliest.position ? current : earliest).error;
            }
            const lengthError = hasCheckMinMaxLength(trimmed, label, 2, 20);
            if (lengthError) return lengthError;
            return null;
        };

        const validateAddUsername = (value) => {
            const trimmed = value.trim();
            const emptyErr = isEmpty(trimmed, "Username");
            if (emptyErr) return emptyErr;
            if (hasDoubleSpaces(trimmed)) return "Username must not contain double spaces.";
            if (hasWhiteSpaces(trimmed)) return "Username must not contain white spaces.";
            if (hasTripleConsecutiveLetters(trimmed)) return "Username must not contain 3 consecutive letters.";
            if (hasRejectAllCapsv2(trimmed)) return "Username must not be all caps.";
            const limitError = hasCheckMinMaxLength(trimmed, "Username", 5, 20);
            if (limitError) return limitError;
            if (/^\d/.test(trimmed)) return "Username must not start with a number.";
            if (!/^[a-zA-Z][a-zA-Z0-9._]*$/.test(trimmed)) return "Username contains invalid characters.";
            return null;
        };

        const validateAddEmail = (value) => {
            const trimmed = value.trim();
            const emptyErr = isEmpty(trimmed, "Email");
            if (emptyErr) return emptyErr;
            if (hasDoubleSpaces(trimmed)) return "Email must not contain double spaces.";
            if (hasWhiteSpaces(trimmed)) return "Email must not contain white spaces.";
            if (hasTripleConsecutiveLetters(trimmed)) return "Email must not contain 3 consecutive letters.";
            const limitError = hasCheckMinMaxLength(trimmed, "Email", 8, 50);
            if (limitError) return limitError;
            const emailRegex = /^[a-z0-9._-]+@[^\s@]+\.[^\s@]{2,}$/;
            if (!emailRegex.test(trimmed)) return "Invalid email format.";
            const localPart = trimmed.split('@')[0];
            if (localPart.length === 1) return "Local part (before @) must be at least 2 characters.";
            return null;
        };

        const validateAddPassword = (value) => {
            const trimmed = value.trim();
            const emptyErr = isEmpty(trimmed, "Password");
            if (emptyErr) return emptyErr;
            const limitError = hasCheckMinMaxLength(trimmed, "Password", 8, 20);
            if (limitError) return limitError;
            if (!/[A-Z]/.test(trimmed)) return "Must contain at least one uppercase letter.";
            if (!/[0-9]/.test(trimmed)) return "Must contain at least one number.";
            if (!/[!@#$%^&*()_+]/.test(trimmed)) return "Must contain at least one special character.";
            return null;
        };

        const getPasswordStrength = (password) => {
            let score = 0;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[!@#$%^&*()_+]/.test(password)) score++;
            if (/.{8,}/.test(password)) score++;
            if (score <= 2) return { level: "Weak", color: "#ef4444", pct: 33 };
            if (score === 3) return { level: "Medium", color: "#f59e0b", pct: 66 };
            return { level: "Strong", color: "#10b981", pct: 100 };
        };

        const checkExists = async (field, value) => {
            try {
                const response = await fetch(`check_exists.php?field=${field}&value=${encodeURIComponent(value)}`);
                const result = await response.json();
                return result.exists;
            } catch (err) { return false; }
        };

        // Validation state for add user form
        const addValidation = { idNo: false, username: false, firstName: false, lastName: false, email: false, sex: false, password: false, confirmPassword: false, role: false };

        function showAddFieldError(fieldId, error) {
            const el = document.getElementById(fieldId + '-error');
            const input = document.getElementById(fieldId);
            if (!el || !input) return;
            if (error) {
                el.textContent = error;
                el.style.color = '#ef4444';
                input.classList.remove('input-valid');
                input.classList.add('input-invalid');
            } else {
                el.textContent = '';
                input.classList.remove('input-invalid');
                input.classList.add('input-valid');
            }
        }

        function clearAddFieldState(fieldId) {
            const el = document.getElementById(fieldId + '-error');
            const input = document.getElementById(fieldId);
            if (el) el.textContent = '';
            if (input) { input.classList.remove('input-valid', 'input-invalid'); }
        }

        async function validateAddField(fieldId) {
            const input = document.getElementById(fieldId);
            if (!input) return;
            const value = input.value;
            let error = null;

            switch (fieldId) {
                case 'addIdNo':
                    error = validateAddIDNumber(value);
                    if (!error) {
                        const exists = await checkExists('idNo', value.trim());
                        if (exists) error = "ID number already exists.";
                    }
                    addValidation.idNo = !error;
                    break;
                case 'addUsername':
                    error = validateAddUsername(value);
                    if (!error) {
                        const exists = await checkExists('username', value.trim());
                        if (exists) error = "Username already exists.";
                    }
                    addValidation.username = !error;
                    break;
                case 'addFirstName':
                    error = findFirstErrorPosition(value, "First Name");
                    addValidation.firstName = !error;
                    break;
                case 'addLastName':
                    error = findFirstErrorPosition(value, "Last Name");
                    addValidation.lastName = !error;
                    break;
                case 'addEmail':
                    error = validateAddEmail(value);
                    if (!error) {
                        const exists = await checkExists('emailAddress', value.trim());
                        if (exists) error = "Email already exists.";
                    }
                    addValidation.email = !error;
                    break;
                case 'addSex':
                    if (!value) { error = "Sex is required."; }
                    addValidation.sex = !error;
                    break;
                case 'addPassword':
                    error = validateAddPassword(value);
                    addValidation.password = !error;
                    // Update strength bar
                    const strengthBar = document.getElementById('addPasswordStrengthBar');
                    const strengthLabel = document.getElementById('addPassword-strength');
                    if (value.trim().length >= 4) {
                        const { level, color, pct } = getPasswordStrength(value);
                        strengthBar.style.width = pct + '%';
                        strengthBar.style.backgroundColor = color;
                        strengthLabel.textContent = level + ' password';
                        strengthLabel.style.color = color;
                    } else {
                        strengthBar.style.width = '0%';
                        strengthLabel.textContent = '';
                    }
                    break;
                case 'addConfirmPassword':
                    const passwordVal = document.getElementById('addPassword').value;
                    if (!value) {
                        error = "Please confirm your password.";
                    } else if (value !== passwordVal) {
                        error = "Passwords do not match.";
                    }
                    addValidation.confirmPassword = !error;

                    const successEl = document.getElementById('addConfirmPassword-success');
                    if (!error && value) {
                        successEl.textContent = "✓ Passwords match";
                        successEl.style.color = "#10b981";
                        successEl.style.fontSize = "12px";
                        successEl.style.marginTop = "4px";
                    } else {
                        successEl.textContent = "";
                    }
                    break;
                case 'addRole':
                    if (!value) { error = "Role is required."; }
                    addValidation.role = !error;
                    break;
            }

            showAddFieldError(fieldId, error);
        }

        // Debounce helper 
        function debounce(func, wait) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Attach real-time validation to add user form fields
        document.addEventListener('DOMContentLoaded', () => {
            const fields = ['addIdNo', 'addUsername', 'addFirstName', 'addLastName', 'addEmail', 'addPassword', 'addConfirmPassword'];
            fields.forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (input) {
                    input.addEventListener('input', debounce(() => validateAddField(fieldId), 400));
                    input.addEventListener('blur', () => validateAddField(fieldId));
                }
            });

            // Select fields - immediate validation
            ['addSex', 'addRole'].forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (input) {
                    input.addEventListener('change', () => validateAddField(fieldId));
                }
            });

            // ID Number - only numbers and dash
            const addIdNoInput = document.getElementById('addIdNo');
            if (addIdNoInput) {
                addIdNoInput.addEventListener('keydown', (e) => {
                    const allowed = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '-', 'Backspace', 'ArrowLeft', 'ArrowRight', 'Tab', 'Delete'];
                    if (!allowed.includes(e.key)) e.preventDefault();
                });
            }
        });

        // Intercept add user form submission for client-side validation
        document.getElementById('addUserForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const allFields = ['addIdNo', 'addUsername', 'addFirstName', 'addLastName', 'addEmail', 'addSex', 'addPassword', 'addConfirmPassword', 'addRole'];
            let hasErrors = false;
            for (const fieldId of allFields) {
                await validateAddField(fieldId);
            }
            // Check all validation states
            for (const key of Object.keys(addValidation)) {
                if (!addValidation[key]) { hasErrors = true; break; }
            }
            if (hasErrors) {
                showToast('Please fix all validation errors before submitting.', 'error');
                return;
            }
            // All valid — submit the form
            this.submit();
        });

        /* ---- ADD USER MODAL ---- */
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('active');
        }
        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.remove('active');
        }

        /* ---- VIEW USER MODAL ---- */
        function viewUser(userId) {
            _viewingUserId = userId;
            document.getElementById('viewUserModal').classList.add('active');
            document.getElementById('viewUserBody').innerHTML =
                '<div style="text-align:center;padding:30px;color:#9ca3af;"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i><p style="margin-top:10px;">Loading...</p></div>';
            document.getElementById('viewEditBtn').style.display = 'none';

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
            const statusColor = u.status === 'active' ? '#10b981' : (u.status === 'blocked' ? '#ef4444' : '#f59e0b');
            const roleBg = u.role === 'super_admin' ? '#3b82f6' : (u.role === 'admin' ? '#6366f1' : '#6b7280');
            const isOwnAccount = (u.idNo === currentUserId);

            let privileges = '';
            if (u.role === 'customer' || u.role === 'user') {
                privileges = '<li>Can view services & book appointments</li>';
            } else if (u.role === 'admin') {
                privileges = '<li>Can view, create, edit users (non-super admin)</li><li>Can block/unblock users</li><li>Can manage services</li>';
            } else if (u.role === 'super_admin') {
                privileges = '<li>Can view all accounts</li><li>Can delete users</li><li>Can Edit Admin and User Accounts</li><li>Can view all logs</li>';
            }

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
                            <span style="background:${statusColor}; color:#fff; padding:3px 12px; border-radius:50px; font-size:11px; font-weight:700; text-transform:uppercase;">${u.status}</span>
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

                <!-- Privileges -->
                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:15px;">
                    <div style="font-size:12px; font-weight:700; color:#374151; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.05em;"><i class="fas fa-shield-alt" style="margin-right:6px;color:#6366f1;"></i>Privileges</div>
                    <ul class="privilege-list">${privileges}</ul>
                </div>
            `;

            // Show edit button logic
            let canEdit = false;
            if (currentUserRole === 'super_admin') {
                canEdit = true;
            } else if (currentUserRole === 'admin') {
                canEdit = (isOwnAccount || u.role !== 'super_admin');
            }
            const editBtn = document.getElementById('viewEditBtn');
            const editBtnLabel = document.getElementById('viewEditBtnLabel');
            editBtn.style.display = canEdit ? 'inline-block' : 'none';
            editBtnLabel.textContent = isOwnAccount ? 'Edit Your Account' : 'Edit User';
        }

        function closeViewModal() {
            document.getElementById('viewUserModal').classList.remove('active');
        }

        function triggerEditFromView() {
            closeViewModal();
            editUser(_viewingUserId);
        }

        /* ---- EDIT USER MODAL ---- */
        function editUser(userId) {
            document.getElementById('editUserModal').classList.add('active');
            // Clear password fields on open
            const npf = document.getElementById('editNewPassword');
            const cpf = document.getElementById('editConfirmPassword');
            const msg = document.getElementById('editPasswordMatchMsg');
            if (npf) npf.value = '';
            if (cpf) cpf.value = '';
            if (msg) msg.textContent = '';

            fetch('user_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=edit&user_id=${encodeURIComponent(userId)}`
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const u = data.user;
                        document.getElementById('editUserId').value = u.idNo;
                        document.getElementById('editIdNoDisplay').value = u.idNo;
                        document.getElementById('editUsername').value = u.username;
                        document.getElementById('editEmail').value = u.emailAddress;
                        document.getElementById('editFirstName').value = u.firstName;
                        document.getElementById('editMiddleName').value = u.middleName || '';
                        document.getElementById('editLastName').value = u.lastName;
                        document.getElementById('editExtension').value = u.extension || '';
                        document.getElementById('editSex').value = u.sex || 'Male';
                        document.getElementById('editBirthday').value = u.birthday || '';
                        document.getElementById('editPurok').value = u.purok || '';
                        document.getElementById('editBarangay').value = u.barangay || '';
                        document.getElementById('editMunicipality').value = u.municipality || '';
                        document.getElementById('editProvince').value = u.province || '';
                        document.getElementById('editCountry').value = u.country || '';
                        document.getElementById('editZipCode').value = u.zipCode || '';

                        const roleSelect = document.getElementById('editRole');
                        for (let opt of roleSelect.options) {
                            opt.selected = opt.value === u.role;
                        }
                        // Update modal title
                        const isOwn = (u.idNo === currentUserId);
                        document.getElementById('editModalTitle').innerHTML = `<i class="fas fa-pencil-alt" style="color:#6366f1; margin-right:8px;"></i> ${isOwn ? 'Edit Your Account' : 'Edit User'}`;
                    } else {
                        closeEditModal();
                        showToast(data.message || 'Error loading user', 'error');
                    }
                })
                .catch(() => {
                    closeEditModal();
                    showToast('Failed to load user data', 'error');
                });
        }

        function closeEditModal() {
            document.getElementById('editUserModal').classList.remove('active');
        }

        // Password confirm live check in edit modal
        const editConfirmPasswordEl = document.getElementById('editConfirmPassword');
        if (editConfirmPasswordEl) {
            editConfirmPasswordEl.addEventListener('input', () => {
                const pw = document.getElementById('editNewPassword').value;
                const cpw = editConfirmPasswordEl.value;
                const msg = document.getElementById('editPasswordMatchMsg');
                if (!cpw) { msg.textContent = ''; return; }
                if (pw === cpw) {
                    msg.textContent = '✓ Passwords match';
                    msg.style.color = '#10b981';
                } else {
                    msg.textContent = '✗ Passwords do not match';
                    msg.style.color = '#ef4444';
                }
            });
        }

        document.getElementById('editUserForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate password fields if filled
            const newPw = document.getElementById('editNewPassword');
            const confPw = document.getElementById('editConfirmPassword');
            if (newPw && newPw.value.trim()) {
                if (!confPw || newPw.value !== confPw.value) {
                    showToast('Passwords do not match. Please confirm the new password.', 'error');
                    return;
                }
                if (newPw.value.trim().length < 8) {
                    showToast('New password must be at least 8 characters.', 'error');
                    return;
                }
            }

            const formData = new FormData(this);

            fetch('user_actions.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'User updated successfully', 'success');
                        closeEditModal();
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        showToast(data.message || 'Error updating user', 'error');
                    }
                })
                .catch(() => showToast('Error updating user', 'error'));
        });

        /* ---- BLOCK / UNBLOCK ---- */
        function blockUser(userId) {
            if (!confirm('Block this user? They will lose access to the system.')) return;
            fetch('user_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=block&user_id=${encodeURIComponent(userId)}`
            }).then(r => r.json()).then(data => {
                if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
                else showToast(data.message, 'error');
            }).catch(() => showToast('Error blocking user', 'error'));
        }

        function unblockUser(userId) {
            if (!confirm('Unblock this user?')) return;
            fetch('user_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=unblock&user_id=${encodeURIComponent(userId)}`
            }).then(r => r.json()).then(data => {
                if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
                else showToast(data.message, 'error');
            }).catch(() => showToast('Error unblocking user', 'error'));
        }

        /* ---- DELETE ---- */
        function deleteUser(userId, username) {
            if (!confirm(`Are you sure you want to permanently delete the user account for "${username}"? This action cannot be undone.`)) return;

            fetch('user_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&user_id=${encodeURIComponent(userId)}`
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message, 'error');
                }
            }).catch(() => {
                showToast('Error processing deletion', 'error');
            });
        }



        /* ---- APPROVE / REJECT ---- */
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
            if (!confirm('Reject and remove this user account?')) return;
            fetch('user_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=reject&user_id=${encodeURIComponent(userId)}`
            }).then(r => r.json()).then(data => {
                if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
                else showToast(data.message, 'error');
            }).catch(() => showToast('Error rejecting user', 'error'));
        }

        /* ---- Close modals on backdrop click ---- */
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function (e) {
                if (e.target === this) this.classList.remove('active');
            });
        });

        /* ---- Auto open add modal if there are errors ---- */
        <?php if (!empty($errors)): ?>
            openAddUserModal();
        <?php endif; ?>

        /* ---- Success Message handling ---- */
        window.onload = function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1') {
                showToast('New user account created successfully!', 'success');
                // Clean up URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        };
    </script>
</body>

</html>