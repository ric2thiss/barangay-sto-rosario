<?php
session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'customer';
$session_user_id = $_SESSION['user_id'];

require_once 'connection.php';

// ---- Fetch full user profile ----
$user_query = "SELECT id, idNo, firstName, middleName, lastName, extension, birthday, age, sex, emailAddress,
               purok, barangay, municipality, province, country, zipCode, role, status, profile_picture
               FROM users WHERE username = ? LIMIT 1";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

$db_user_id = $user_data['id'] ?? 0;
if (!$user_data || $db_user_id == 0) {
    header('Location: login.php');
    exit();
}
$profile = $user_data;

// ---- Sidebar badge counts (admin/super_admin only) ----
$pending_reg_count = 0;
if (in_array($role, ['admin', 'super_admin'])) {
    $r = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE status = 'pending'");
    $r->execute();
    $pending_reg_count = $r->get_result()->fetch_assoc()['cnt'];
}

$errors = [];
$success_message = '';

// ============================================================
// ----- Handle full profile update -----
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {

    // Collect and sanitize
    $f = function ($k) {
        return trim($_POST[$k] ?? ''); };

    $firstName = $f('firstName');
    $middleName = $f('middleName');
    $lastName = $f('lastName');
    $extension = $f('extension');
    $birthday = $f('birthday');
    $sex = $f('sex');
    $emailAddress = $f('emailAddress');
    $purok = $f('purok');
    $barangay = $f('barangay');
    $municipality = $f('municipality');
    $province = $f('province');
    $country = $f('country');
    $zipCode = $f('zipCode');

    // ---- Required validations ----
    if (empty($firstName))
        $errors[] = 'First name is required.';
    if (empty($lastName))
        $errors[] = 'Last name is required.';
    if (empty($sex))
        $errors[] = 'Sex is required.';
    if (empty($birthday))
        $errors[] = 'Birthday is required.';
    if (empty($emailAddress))
        $errors[] = 'Email address is required.';
    elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Invalid email format.';

    // ---- Email uniqueness ----
    if (empty($errors) || (count($errors) === 0)) {
        // Check email used by another user
    }
    if (!empty($emailAddress) && filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $chk = $conn->prepare("SELECT id FROM users WHERE emailAddress = ? AND id != ?");
        $chk->bind_param("si", $emailAddress, $db_user_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'This email is already used by another account.';
        }
    }

    // ---- Auto-calculate age from birthday ----
    $age = 0;
    if (!empty($birthday)) {
        $dob = new DateTime($birthday);
        $now = new DateTime();
        $age = $dob->diff($now)->y;
    }

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE users SET firstName=?, middleName=?, lastName=?, extension=?,
            birthday=?, age=?, sex=?, emailAddress=?, purok=?, barangay=?, municipality=?,
            province=?, country=?, zipCode=? WHERE id=?");
        $upd->bind_param(
            "ssssssssssssssi",
            $firstName,
            $middleName,
            $lastName,
            $extension,
            $birthday,
            $age,
            $sex,
            $emailAddress,
            $purok,
            $barangay,
            $municipality,
            $province,
            $country,
            $zipCode,
            $db_user_id
        );

        if ($upd->execute()) {
            $success_message = 'Profile updated successfully!';
            // Refresh local $profile array
            $profile['firstName'] = $firstName;
            $profile['middleName'] = $middleName;
            $profile['lastName'] = $lastName;
            $profile['extension'] = $extension;
            $profile['birthday'] = $birthday;
            $profile['age'] = $age;
            $profile['sex'] = $sex;
            $profile['emailAddress'] = $emailAddress;
            $profile['purok'] = $purok;
            $profile['barangay'] = $barangay;
            $profile['municipality'] = $municipality;
            $profile['province'] = $province;
            $profile['country'] = $country;
            $profile['zipCode'] = $zipCode;
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

// ---- Handle profile picture upload ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_picture') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            $errors[] = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File size must be less than 5MB.';
        } else {
            $upload_dir = '../uploads/profile_pictures/';
            if (!file_exists($upload_dir))
                mkdir($upload_dir, 0755, true);
            $old = $profile['profile_picture'] ?? null;
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $db_user_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                $upd = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $upd->bind_param("si", $filename, $db_user_id);
                if ($upd->execute()) {
                    $success_message = 'Profile picture updated!';
                    if ($old && file_exists($upload_dir . $old))
                        unlink($upload_dir . $old);
                    $profile['profile_picture'] = $filename;
                } else {
                    $errors[] = 'Failed to save profile picture.';
                    @unlink($upload_dir . $filename);
                }
            } else {
                $errors[] = 'Failed to upload file.';
            }
        }
    } else {
        $errors[] = 'Please select a valid image file.';
    }
}

// ---- Handle profile picture delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_picture') {
    if (!empty($profile['profile_picture'])) {
        $upload_dir = '../uploads/profile_pictures/';
        if (file_exists($upload_dir . $profile['profile_picture'])) {
            unlink($upload_dir . $profile['profile_picture']);
        }
        $upd = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $upd->bind_param("i", $db_user_id);
        if ($upd->execute()) {
            $success_message = 'Profile picture removed!';
            $profile['profile_picture'] = null;
        } else {
            $errors[] = 'Failed to remove profile picture.';
        }
    }
}

$initials = strtoupper(
    substr($profile['firstName'] ?? 'U', 0, 1) .
    substr($profile['lastName'] ?? 'N', 0, 1)
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROFILE - STEAM Vladimir Lahora</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/steam_theme.css">
    <?php if (in_array($role, ['admin', 'super_admin'])): ?>
        <link rel="stylesheet" type="text/css" href="../css/dashboard.css">
    <?php else: ?>
        <link rel="stylesheet" type="text/css" href="../css/customer_dashboard.css">
    <?php endif; ?>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #1b2838;
            background-image: radial-gradient(circle at top right, #1b2838 0%, #171a21 100%);
            color: #c7d5e0;
        }

        .page-wrapper {
            max-width: 1000px;
            margin: 40px auto;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 4px;
            padding: 0;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .profile-header {
            background: linear-gradient(to right, #2a475e 0%, #1b2838 100%);
            padding: 40px;
            display: flex;
            gap: 30px;
            border-bottom: 2px solid rgba(0,0,0,0.3);
        }

        .avatar-img, .avatar-initials {
            width: 164px;
            height: 164px;
            border-radius: 0;
            border: 2px solid #66c0f4;
            box-shadow: 0 0 10px rgba(102, 192, 244, 0.3);
        }

        .header-info h1 {
            font-size: 34px;
            font-weight: 400;
            letter-spacing: 1px;
            text-transform: none;
        }

        .section-card {
            background: rgba(0, 0, 0, 0.2);
            border: none;
            border-radius: 0;
            margin-bottom: 2px;
        }

        .section-title {
            background: rgba(0, 0, 0, 0.4);
            color: #66c0f4;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .form-field label {
            color: #8f98a0;
        }

        .form-field input, .form-field select {
            background: #23262e;
            border: 1px solid #000;
            color: white;
            border-radius: 2px;
        }

        .form-field input:focus {
            border-color: #66c0f4;
        }

        .btn-primary {
            background: linear-gradient(to right, #47bfff 5%, #1a44c2 60%);
        }

        /* Sidebar modification for Admin */
        .sidebar {
            background: #171a21;
        }
        .nav-item {
            color: #8f98a0;
        }
        .nav-item.active {
            background: #2a475e;
            color: white;
        }
    </style>
</head>

<body>

    <?php if (in_array($role, ['admin', 'super_admin'])): ?>
        <!-- ===== ADMIN LAYOUT (sidebar) ===== -->
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
                <a href="pending_registrations.php" class="nav-item">
                    <i class="fas fa-user-clock"></i> Pending Accounts
                    <?php if ($pending_reg_count > 0): ?>
                        <span style="background:#cd5434;color:#fff;font-size:11px;font-weight:700;min-width:20px;height:20px;border-radius:2px;padding:0 6px;margin-left:6px;display:inline-flex;align-items:center;justify-content:center;"><?php echo $pending_reg_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="user_logs.php" class="nav-item"><i class="fas fa-history"></i> Logs</a>
                <a href="profile.php" class="nav-item active"><i class="fas fa-user-circle"></i> Profile</a>
            </nav>
            <div class="logout-section">
                <form method="POST" action="logout.php">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </aside>
        <div class="admin-main">
            <div class="content-header">
                <h1><i class="fas fa-user-circle" style="color:#6366f1;margin-right:8px;"></i>My Profile</h1>
                <p>View and edit your personal account details.</p>
            </div>

        <?php else: ?>
            <!-- ===== CUSTOMER LAYOUT (top nav & sidebar) ===== -->
            <header class="steam-header">
                <div class="steam-logo-container">
                    <img src="../IMAGE/Steam_icon_logo.svg.png" alt="Steam Logo" class="steam-logo-img">
                    <span class="steam-brand-text">STEAM</span>
                </div>
                <nav class="steam-nav">
                    <a href="landingpage.php">HOME</a>
                </nav>
            </header>
            
            <div class="library-container">
                <!-- Sidebar -->
                <aside class="library-sidebar">
                    <div class="sidebar-search">
                        <input type="text" placeholder="Search by name">
                    </div>
                    <div class="sidebar-list">
                        <div class="sidebar-group">
                            <span class="group-title">NAVIGATION</span>
                            <a href="landingpage.php" class="sidebar-item">
                                <i class="fas fa-home"></i>
                                <span>Home</span>
                            </a>
                            <a href="customer_dashboard.php" class="sidebar-item">
                                <i class="fas fa-th-large"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="profile.php" class="sidebar-item active">
                                <i class="fas fa-user-circle"></i>
                                <span>Profile</span>
                            </a>
                            <a href="logout.php" class="sidebar-item" onclick="return confirm('Are you sure you want to logout?')">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </aside>
                
                <!-- Main Content wrapper for sidebar layout -->
                <main class="library-main" style="padding: 20px; overflow-y: auto;">
                    <div class="page-wrapper" style="margin: 0 auto; max-width: 1000px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05);">
        <?php endif; ?>

            <!-- ---- Toasts ---- -->
            <?php if ($success_message): ?>
                <div class="alert alert-success"><i
                        class="fas fa-check-circle"></i><span><?php echo htmlspecialchars($success_message); ?></span></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>
                    <div><?php foreach ($errors as $e)
                        echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div>
                </div>
            <?php endif; ?>

            <!-- ======================== PROFILE HEADER ======================== -->
            <div class="profile-header">
                <div class="avatar-wrap">
                    <?php if (!empty($profile['profile_picture']) && file_exists('../uploads/profile_pictures/' . $profile['profile_picture'])): ?>
                        <img class="avatar-img"
                            src="../uploads/profile_pictures/<?php echo htmlspecialchars($profile['profile_picture']); ?>"
                            alt="Profile Picture">
                    <?php else: ?>
                        <div class="avatar-initials"><?php echo $initials; ?></div>
                    <?php endif; ?>
                </div>
                <div class="header-info">
                    <h1><?php echo htmlspecialchars(trim(($profile['firstName'] ?? '') . ' ' . ($profile['lastName'] ?? ''))); ?>
                    </h1>
                    <div class="subtext">@<?php echo htmlspecialchars($username); ?> &bull;
                        <?php echo htmlspecialchars($profile['emailAddress'] ?? ''); ?></div>
                    <div class="header-badges">
                        <span
                            class="badge badge-role-<?php echo htmlspecialchars($role); ?>"><?php echo str_replace('_', ' ', $role); ?></span>
                        <span
                            class="badge badge-status-<?php echo htmlspecialchars($profile['status'] ?? 'active'); ?>"><?php echo ucfirst($profile['status'] ?? 'active'); ?></span>
                    </div>
                </div>
            </div>

            <!-- ======================== PROFILE PICTURE ======================== -->
            <div class="section-card">
                <div class="section-title"><i class="fas fa-camera" style="color:#6366f1;"></i> Profile Picture</div>
                <div class="section-body">
                    <div class="picture-wrap">
                        <div>
                            <?php if (!empty($profile['profile_picture']) && file_exists('../uploads/profile_pictures/' . $profile['profile_picture'])): ?>
                                <img class="picture-thumb"
                                    src="../uploads/profile_pictures/<?php echo htmlspecialchars($profile['profile_picture']); ?>"
                                    alt="Photo">
                            <?php else: ?>
                                <div class="picture-thumb-empty"><?php echo $initials; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="picture-actions">
                            <form method="POST" enctype="multipart/form-data" id="picForm">
                                <input type="hidden" name="action" value="upload_picture">
                                <input type="file" name="profile_picture" id="profilePicInput" accept="image/*"
                                    onchange="document.getElementById('picForm').submit()">
                                <label for="profilePicInput" class="file-label">
                                    <i class="fas fa-upload"></i> Upload New Photo
                                </label>
                            </form>
                            <?php if (!empty($profile['profile_picture'])): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="delete_picture">
                                    <button type="submit" class="btn btn-danger" style="font-size:12px;padding:7px 14px;"
                                        onclick="return confirm('Remove your profile picture?')">
                                        <i class="fas fa-trash"></i> Remove Photo
                                    </button>
                                </form>
                            <?php endif; ?>
                            <span class="pic-hint">JPG, PNG, GIF or WEBP &mdash; Max 5MB</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================== FULL PROFILE EDIT FORM ======================== -->
            <form method="POST" id="profileForm" novalidate>
                <input type="hidden" name="action" value="update_profile">

                <!-- ---- Personal Information ---- -->
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-id-card" style="color:#3b82f6;"></i> Personal
                        Information</div>
                    <div class="section-body">

                        <!-- Row 1: ID (read-only), First, Middle, Last -->
                        <div class="form-grid-3" style="margin-bottom:14px;">
                            <div class="form-field">
                                <label>ID Number</label>
                                <input type="text" value="<?php echo htmlspecialchars($profile['idNo'] ?? ''); ?>"
                                    disabled style="background:#f8fafc;color:#9ca3af;cursor:not-allowed;">
                                <span class="field-hint">Cannot be changed</span>
                            </div>
                            <div class="form-field">
                                <label>First Name <span class="req">*</span></label>
                                <input type="text" name="firstName" id="fFirstName"
                                    value="<?php echo htmlspecialchars($profile['firstName'] ?? ''); ?>"
                                    placeholder="e.g. Juan" maxlength="50" required>
                                <span class="field-err" id="err-firstName"></span>
                            </div>
                            <div class="form-field">
                                <label>Middle Name</label>
                                <input type="text" name="middleName" id="fMiddleName"
                                    value="<?php echo htmlspecialchars($profile['middleName'] ?? ''); ?>"
                                    placeholder="Optional" maxlength="50">
                            </div>
                        </div>

                        <!-- Row 2: Last Name, Extension, Sex -->
                        <div class="form-grid-3" style="margin-bottom:14px;">
                            <div class="form-field">
                                <label>Last Name <span class="req">*</span></label>
                                <input type="text" name="lastName" id="fLastName"
                                    value="<?php echo htmlspecialchars($profile['lastName'] ?? ''); ?>"
                                    placeholder="e.g. Dela Cruz" maxlength="50" required>
                                <span class="field-err" id="err-lastName"></span>
                            </div>
                            <div class="form-field">
                                <label>Extension (Suffix)</label>
                                <select name="extension" id="fExtension">
                                    <?php $ext = $profile['extension'] ?? ''; ?>
                                    <option value="">None</option>
                                    <?php foreach (['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php if ($ext === $s)
                                               echo 'selected'; ?>>
                                            <?php echo $s; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Sex <span class="req">*</span></label>
                                <select name="sex" id="fSex" required>
                                    <option value="">Select Sex</option>
                                    <option value="Male" <?php if (($profile['sex'] ?? '') === 'Male')
                                        echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if (($profile['sex'] ?? '') === 'Female')
                                        echo 'selected'; ?>>Female</option>
                                </select>
                                <span class="field-err" id="err-sex"></span>
                            </div>
                        </div>

                        <!-- Row 3: Birthday (auto-calculates age) -->
                        <div class="form-grid-3">
                            <div class="form-field">
                                <label>Birthday <span class="req">*</span></label>
                                <input type="date" name="birthday" id="fBirthday"
                                    value="<?php echo htmlspecialchars($profile['birthday'] ?? ''); ?>"
                                    max="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" required>
                                <span class="field-err" id="err-birthday"></span>
                            </div>
                            <div class="form-field">
                                <label>Age</label>
                                <input type="number" id="fAge" name="age_display" readonly
                                    value="<?php echo htmlspecialchars($profile['age'] ?? ''); ?>"
                                    style="background:#f8fafc;color:#6b7280;cursor:not-allowed;"
                                    placeholder="Auto-calculated">
                                <span class="field-hint">Auto-calculated from birthday</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---- Contact Information ---- -->
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-envelope" style="color:#10b981;"></i> Contact
                        Information</div>
                    <div class="section-body">
                        <div class="form-grid-2">
                            <div class="form-field" style="grid-column:1/-1;">
                                <label>Email Address <span class="req">*</span></label>
                                <input type="email" name="emailAddress" id="fEmail"
                                    value="<?php echo htmlspecialchars($profile['emailAddress'] ?? ''); ?>"
                                    placeholder="you@example.com" required>
                                <span class="field-err" id="err-email"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---- Permanent Address ---- -->
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-map-marker-alt" style="color:#ef4444;"></i> Permanent
                        Address</div>
                    <div class="section-body">
                        <div class="form-grid-3" style="margin-bottom:14px;">
                            <div class="form-field">
                                <label>Purok / Street</label>
                                <input type="text" name="purok" id="fPurok"
                                    value="<?php echo htmlspecialchars($profile['purok'] ?? ''); ?>"
                                    placeholder="e.g. Purok 4" maxlength="100">
                            </div>
                            <div class="form-field">
                                <label>Barangay</label>
                                <input type="text" name="barangay" id="fBarangay"
                                    value="<?php echo htmlspecialchars($profile['barangay'] ?? ''); ?>"
                                    placeholder="e.g. Poblacion" maxlength="100">
                            </div>
                            <div class="form-field">
                                <label>Municipality / City</label>
                                <input type="text" name="municipality" id="fMunicipality"
                                    value="<?php echo htmlspecialchars($profile['municipality'] ?? ''); ?>"
                                    placeholder="e.g. Davao City" maxlength="100">
                            </div>
                        </div>
                        <div class="form-grid-3">
                            <div class="form-field">
                                <label>Province</label>
                                <input type="text" name="province" id="fProvince"
                                    value="<?php echo htmlspecialchars($profile['province'] ?? ''); ?>"
                                    placeholder="e.g. Davao del Sur" maxlength="100">
                            </div>
                            <div class="form-field">
                                <label>Country</label>
                                <input type="text" name="country" id="fCountry"
                                    value="<?php echo htmlspecialchars($profile['country'] ?? ''); ?>"
                                    placeholder="e.g. Philippines" maxlength="100">
                            </div>
                            <div class="form-field">
                                <label>ZIP Code</label>
                                <input type="text" name="zipCode" id="fZipCode"
                                    value="<?php echo htmlspecialchars($profile['zipCode'] ?? ''); ?>"
                                    placeholder="e.g. 8000" maxlength="10">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ---- Save Button ---- -->
                <div class="save-row">
                    <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                    <span id="saveMsg" style="font-size:13px;color:#6b7280;"></span>
                </div>
            </form>
        </div><!-- end page-wrapper -->

            <?php if (in_array($role, ['admin', 'super_admin'])): ?>
            </div><!-- end admin-main -->
        <?php else: ?>
                </div><!-- end page-wrapper -->
            </main><!-- end library-main -->
        </div><!-- end library-container -->
        <footer class="steam-footer" style="padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.05);">
            <div class="footer-content" style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 20px; padding: 0 20px;">
                <img src="../IMAGE/footerLogo_valve_new.png" alt="Valve Logo" style="height: 20px; opacity: 0.5;">
                <p style="font-size: 11px; color: #8f98a0;">&copy; <?php echo date("Y"); ?> Vladimir Lahora. All rights reserved.</p>
            </div>
        </footer>
    <?php endif; ?>

    <script>
        /* ---- Sidebar toggle (admin) ---- */
        function toggleSidebar() {
            document.getElementById('sidebar') && document.getElementById('sidebar').classList.toggle('active');
        }

        /* ---- Auto-calculate age from birthday ---- */
        const birthdayInput = document.getElementById('fBirthday');
        const ageInput = document.getElementById('fAge');
        function calcAge() {
            const val = birthdayInput.value;
            if (!val) { ageInput.value = ''; return; }
            const dob = new Date(val);
            const now = new Date();
            let age = now.getFullYear() - dob.getFullYear();
            const m = now.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) age--;
            ageInput.value = age >= 0 ? age : '';
        }
        birthdayInput.addEventListener('change', calcAge);
        calcAge(); // run on load

        /* ---- Real-time field validation ---- */
        function showErr(id, msg) {
            const el = document.getElementById('err-' + id);
            const inp = document.getElementById('f' + id.charAt(0).toUpperCase() + id.slice(1)) ||
                document.getElementById('fEmail');
            if (el) { el.textContent = msg; el.style.display = msg ? 'block' : 'none'; }
            if (inp) { inp.classList.toggle('is-invalid', !!msg); inp.classList.toggle('is-valid', !msg && inp.value.trim() !== ''); }
        }

        function validateName(val, label) {
            if (!val.trim()) return label + ' is required.';
            if (val.trim().length < 2) return label + ' must be at least 2 characters.';
            if (/\d/.test(val)) return label + ' must not contain numbers.';
            if (/[^a-zA-ZñÑ\s\-.]/.test(val)) return label + ' contains invalid characters.';
            return '';
        }

        const firstName = document.getElementById('fFirstName');
        const lastName = document.getElementById('fLastName');
        const sex = document.getElementById('fSex');
        const birthday = document.getElementById('fBirthday');
        const email = document.getElementById('fEmail');

        firstName.addEventListener('input', () => showErr('firstName', validateName(firstName.value, 'First name')));
        lastName.addEventListener('input', () => showErr('lastName', validateName(lastName.value, 'Last name')));

        sex.addEventListener('change', () => {
            showErr('sex', sex.value ? '' : 'Sex is required.');
        });

        birthday.addEventListener('change', () => {
            if (!birthday.value) { showErr('birthday', 'Birthday is required.'); return; }
            const dob = new Date(birthday.value);
            const now = new Date();
            if (dob >= now) { showErr('birthday', 'Birthday must be in the past.'); return; }
            showErr('birthday', '');
        });

        email.addEventListener('input', () => {
            const v = email.value.trim();
            if (!v) { showErr('email', 'Email is required.'); return; }
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
            showErr('email', re.test(v) ? '' : 'Invalid email format.');
        });

        /* ---- Form submit — block if invalid ---- */
        document.getElementById('profileForm').addEventListener('submit', function (e) {
            let valid = true;

            const fnErr = validateName(firstName.value, 'First name');
            const lnErr = validateName(lastName.value, 'Last name');
            showErr('firstName', fnErr);
            showErr('lastName', lnErr);
            if (fnErr || lnErr) valid = false;

            if (!sex.value) { showErr('sex', 'Sex is required.'); valid = false; }
            if (!birthday.value) { showErr('birthday', 'Birthday is required.'); valid = false; }

            const emailVal = email.value.trim();
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
            if (!emailVal) { showErr('email', 'Email is required.'); valid = false; }
            else if (!re.test(emailVal)) { showErr('email', 'Invalid email format.'); valid = false; }

            if (!valid) {
                e.preventDefault();
                document.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                const btn = document.getElementById('saveProfileBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
        });
    </script>
</body>

</html>