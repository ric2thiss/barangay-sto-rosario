<?php
/**
 * save_official_account.php — POST handler for setting/updating
 * an official's login credentials and privilege flags.
 */
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php"); exit();
}
$allowed = ['admin', 'official'];
$is_superadmin = ($_SESSION['user_type'] === 'admin') || (!empty($_SESSION['is_superadmin']));
if (!in_array($_SESSION['user_type'], $allowed) || !$is_superadmin) {
    $_SESSION['error'] = 'Unauthorized: Only superadmin can manage official accounts.';
    header("Location: barangay_officials.php"); exit();
}

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: barangay_officials.php"); exit();
}

$official_id = intval($_POST['official_id'] ?? 0);
if ($official_id <= 0) {
    $_SESSION['error'] = 'Invalid official ID.';
    header("Location: barangay_officials.php"); exit();
}

// Fetch existing official
$stmt = $conn->prepare("SELECT id, username, password, position FROM barangay_official WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $official_id);
$stmt->execute();
$off = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$off) {
    $_SESSION['error'] = 'Official not found.';
    header("Location: barangay_officials.php"); exit();
}

$new_username = trim($_POST['username'] ?? '');
$new_password = trim($_POST['password'] ?? '');

// Validate username
if (!empty($new_username)) {
    if (strlen($new_username) < 4 || !preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        $_SESSION['error'] = 'Invalid username. Must be 4+ chars, letters/numbers/underscore only.';
        header("Location: barangay_officials.php"); exit();
    }

    // Check uniqueness (skip same official)
    if ($new_username !== $off['username']) {
        $check_tables = [
            "SELECT id FROM barangay_official WHERE username = ? AND id != $official_id LIMIT 1",
            "SELECT id FROM admin WHERE username = ? LIMIT 1",
            "SELECT id FROM residents WHERE username = ? LIMIT 1",
            "SELECT id FROM staff WHERE username = ? LIMIT 1",
            "SELECT id FROM pending_registrations WHERE username = ? LIMIT 1",
        ];
        foreach ($check_tables as $sql) {
            $chk = $conn->prepare($sql);
            $chk->bind_param('s', $new_username);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $chk->close();
                $_SESSION['error'] = "Username '$new_username' is already taken.";
                header("Location: barangay_officials.php"); exit();
            }
            $chk->close();
        }
    }
}

// Privilege flags
$can_view = isset($_POST['can_view_residents']) ? 1 : 0;
$can_add  = isset($_POST['can_add_resident'])   ? 1 : 0;
$can_edit = isset($_POST['can_edit_resident'])   ? 1 : 0;
$can_appr = isset($_POST['can_approve'])         ? 1 : 0;
$can_del  = isset($_POST['can_delete'])          ? 1 : 0;
$can_exp  = isset($_POST['can_export'])          ? 1 : 0;
$can_staff = isset($_POST['can_manage_staff'])   ? 1 : 0;
$can_logs  = isset($_POST['can_view_logs'])      ? 1 : 0;

// Build update
if (!empty($new_password)) {
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters.';
        header("Location: barangay_officials.php"); exit();
    }
    $hashed = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $conn->prepare("
        UPDATE barangay_official SET
            username = ?, password = ?,
            can_view_residents = ?, can_add_resident = ?, can_edit_resident = ?,
            can_approve = ?, can_delete = ?, can_export = ?,
            can_manage_staff = ?, can_view_logs = ?
        WHERE id = ?
    ");
    $stmt->bind_param('ssiiiiiiiii',
        $new_username, $hashed,
        $can_view, $can_add, $can_edit,
        $can_appr, $can_del, $can_exp,
        $can_staff, $can_logs,
        $official_id
    );
} else {
    $stmt = $conn->prepare("
        UPDATE barangay_official SET
            username = ?,
            can_view_residents = ?, can_add_resident = ?, can_edit_resident = ?,
            can_approve = ?, can_delete = ?, can_export = ?,
            can_manage_staff = ?, can_view_logs = ?
        WHERE id = ?
    ");
    $stmt->bind_param('siiiiiiiii',
        $new_username,
        $can_view, $can_add, $can_edit,
        $can_appr, $can_del, $can_exp,
        $can_staff, $can_logs,
        $official_id
    );
}

if ($stmt->execute()) {
    $name = htmlspecialchars($off['position'] . ' — ' . ($new_username ?: 'no username'));
    $_SESSION['success'] = "Account settings updated for $name.";
} else {
    $_SESSION['error'] = "Failed to update: " . $stmt->error;
}

$stmt->close();
$conn->close();
header("Location: barangay_officials.php");
exit();
