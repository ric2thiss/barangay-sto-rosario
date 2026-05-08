<?php
/**
 * add_staff.php — POST handler for creating a new staff account
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: staff_management.php");
    exit();
}

// Parse & validate
$first_name = trim($_POST['first_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '') ?: null;
$surname = trim($_POST['surname'] ?? '');
$suffix = trim($_POST['suffix'] ?? '') ?: null;
$email = trim($_POST['email'] ?? '') ?: null;
$contact_no = trim($_POST['contact_no'] ?? '') ?: null;
$position = trim($_POST['position'] ?? '') ?: null;
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (empty($first_name) || empty($surname)) {
    $_SESSION['staff_error'] = 'First name and surname are required.';
    header("Location: staff_management.php");
    exit();
}
if (empty($username) || strlen($username) < 4 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $_SESSION['staff_error'] = 'Invalid username. Must be 4+ chars, letters/numbers/underscore only.';
    header("Location: staff_management.php");
    exit();
}
if (strlen($password) < 6) {
    $_SESSION['staff_error'] = 'Password must be at least 6 characters.';
    header("Location: staff_management.php");
    exit();
}
if ($password !== $confirm) {
    $_SESSION['staff_error'] = 'Passwords do not match.';
    header("Location: staff_management.php");
    exit();
}

// Check username uniqueness across staff, admin, residents, pending
$tables = ['staff', 'admin', 'residents', 'pending_registrations'];
foreach ($tables as $tbl) {
    $col = ($tbl === 'admin') ? 'username' : 'username';
    $chk = $conn->prepare("SELECT id FROM $tbl WHERE username = ? LIMIT 1");
    $chk->bind_param("s", $username);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        $_SESSION['staff_error'] = "Username '$username' is already taken.";
        header("Location: staff_management.php");
        exit();
    }
    $chk->close();
}

$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);


// Privileges
$can_view = isset($_POST['can_view_residents']) ? 1 : 0;
$can_add = isset($_POST['can_add_resident']) ? 1 : 0;
$can_edit = isset($_POST['can_edit_resident']) ? 1 : 0;
$can_appr = isset($_POST['can_approve']) ? 1 : 0;
$can_del = isset($_POST['can_delete']) ? 1 : 0;
$can_exp = isset($_POST['can_export']) ? 1 : 0;

// Photo upload
$image_path = 'default.jpg';
if (!empty($_FILES['image_path']['name'])) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($_FILES['image_path']['type'], $allowed) && $_FILES['image_path']['size'] <= 2 * 1024 * 1024) {
        $ext = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
        $image_path = 'staff_' . time() . '_' . uniqid() . '.' . $ext;
        $uploadDir = 'uploads/residents/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        move_uploaded_file($_FILES['image_path']['tmp_name'], $uploadDir . $image_path);
    }
}

$created_by = $_SESSION['username'] ?? 'Admin';

// Sanitize
$first_name = $conn->real_escape_string($first_name);
$middle_name = $middle_name ? $conn->real_escape_string($middle_name) : null;
$surname = $conn->real_escape_string($surname);
$suffix = $suffix ? $conn->real_escape_string($suffix) : null;
$email = $email ? $conn->real_escape_string($email) : null;
$contact_no = $contact_no ? $conn->real_escape_string($contact_no) : null;
$position = $position ? $conn->real_escape_string($position) : null;

// Type string: 9 strings + 6 ints + 2 strings = 17 params
$types = 'ssss'    // first_name, middle_name, surname, suffix
    . 'sssss'   // email, contact_no, username, password, position
    . 'iiiiii'  // 6 privilege flags
    . 'ss';     // image_path, created_by

$stmt = $conn->prepare("
    INSERT INTO staff (
        first_name, middle_name, surname, suffix,
        email, contact_no, username, password, position,
        can_view_residents, can_add_resident, can_edit_resident,
        can_approve, can_delete, can_export,
        image_path, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    $types,
    $first_name,
    $middle_name,
    $surname,
    $suffix,
    $email,
    $contact_no,
    $username,
    $hashed,
    $position,
    $can_view,
    $can_add,
    $can_edit,
    $can_appr,
    $can_del,
    $can_exp,
    $image_path,
    $created_by
);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;

    // Audit log
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $admin_user = $conn->real_escape_string($_SESSION['username'] ?? 'Admin');
    $log = $conn->prepare("INSERT INTO staff_audit_log (staff_id, staff_username, action, target_type, target_id, details, ip_address) VALUES (?, ?, 'create_staff', 'staff', ?, ?, ?)");
    $admin_id = (int) $_SESSION['user_id'];
    $detail = json_encode(['created_username' => $username, 'position' => $position]);
    $log->bind_param("isiss", $admin_id, $admin_user, $new_id, $detail, $ip);
    $log->execute();
    $log->close();

    $_SESSION['staff_success'] = "Staff account '$username' created successfully.";
} else {
    $_SESSION['staff_error'] = 'Failed to create staff account: ' . $stmt->error;
}

$stmt->close();
$conn->close();
header("Location: staff_management.php");
exit();
?>