<?php
/**
 * edit_staff.php — POST handler for updating staff details & privileges
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php"); exit();
}
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: staff_management.php"); exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['staff_error'] = 'Invalid staff ID.';
    header("Location: staff_management.php"); exit();
}

$first_name  = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
$middle_name = trim($_POST['middle_name'] ?? '') ?: null;
if ($middle_name) $middle_name = $conn->real_escape_string($middle_name);
$surname     = $conn->real_escape_string(trim($_POST['surname'] ?? ''));
$suffix      = trim($_POST['suffix'] ?? '') ?: null;
if ($suffix) $suffix = $conn->real_escape_string($suffix);
$email       = trim($_POST['email'] ?? '') ?: null;
if ($email) $email = $conn->real_escape_string($email);
$contact_no  = trim($_POST['contact_no'] ?? '') ?: null;
if ($contact_no) $contact_no = $conn->real_escape_string($contact_no);
$position    = trim($_POST['position'] ?? '') ?: null;
if ($position) $position = $conn->real_escape_string($position);
$status      = in_array($_POST['status'] ?? '', ['Active','Inactive']) ? $_POST['status'] : 'Active';

if (empty($first_name) || empty($surname)) {
    $_SESSION['staff_error'] = 'First name and surname are required.';
    header("Location: staff_management.php"); exit();
}

// Privileges
$can_view = isset($_POST['can_view_residents']) ? 1 : 0;
$can_add  = isset($_POST['can_add_resident']) ? 1 : 0;
$can_edit = isset($_POST['can_edit_resident']) ? 1 : 0;
$can_appr = isset($_POST['can_approve']) ? 1 : 0;
$can_del  = isset($_POST['can_delete']) ? 1 : 0;
$can_exp  = isset($_POST['can_export']) ? 1 : 0;

// Photo upload (optional)
$image_sql = '';
$image_path = null;
if (!empty($_FILES['image_path']['name']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg','image/png','image/gif'];
    if (in_array($_FILES['image_path']['type'], $allowed) && $_FILES['image_path']['size'] <= 2*1024*1024) {
        $ext = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
        $image_path = 'staff_'.time().'_'.uniqid().'.'.$ext;
        $uploadDir = 'uploads/residents/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        move_uploaded_file($_FILES['image_path']['tmp_name'], $uploadDir.$image_path);
        $image_sql = ", image_path = ?";
    }
}

$sql = "UPDATE staff SET
    first_name = ?, middle_name = ?, surname = ?, suffix = ?,
    email = ?, contact_no = ?, position = ?, status = ?,
    can_view_residents = ?, can_add_resident = ?, can_edit_resident = ?,
    can_approve = ?, can_delete = ?, can_export = ?
    $image_sql
    WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($image_path) {
    $types = 'ssssssss' . 'iiiiii' . 'si';  // 8s + 6i + s(image) + i(id) = 16
    $stmt->bind_param($types,
        $first_name, $middle_name, $surname, $suffix,
        $email, $contact_no, $position, $status,
        $can_view, $can_add, $can_edit,
        $can_appr, $can_del, $can_exp,
        $image_path, $id
    );
} else {
    $types = 'ssssssss' . 'iiiiii' . 'i';  // 8s + 6i + i(id) = 15
    $stmt->bind_param($types,
        $first_name, $middle_name, $surname, $suffix,
        $email, $contact_no, $position, $status,
        $can_view, $can_add, $can_edit,
        $can_appr, $can_del, $can_exp,
        $id
    );
}

if ($stmt->execute()) {
    // Audit log
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $admin_user = $conn->real_escape_string($_SESSION['username'] ?? 'Admin');
    $admin_id = (int)$_SESSION['user_id'];
    $detail = json_encode(['edited_staff_id' => $id, 'new_status' => $status]);
    $log = $conn->prepare("INSERT INTO staff_audit_log (staff_id, staff_username, action, target_type, target_id, details, ip_address) VALUES (?, ?, 'edit_staff', 'staff', ?, ?, ?)");
    $log->bind_param("isiss", $admin_id, $admin_user, $id, $detail, $ip);
    $log->execute();
    $log->close();
    
    $_SESSION['staff_success'] = 'Staff account updated successfully.';
} else {
    $_SESSION['staff_error'] = 'Failed to update staff: '.$stmt->error;
}

$stmt->close();
$conn->close();
header("Location: staff_management.php");
exit();
?>
