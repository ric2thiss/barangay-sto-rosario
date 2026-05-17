<?php
// ── resident/submit_update_request.php ───────────────────────────────────
session_start();
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'resident') {
    header('Location: login_resident.php'); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php'); exit();
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    header('Location: dashboard.php?error=csrf'); exit();
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include('../officials/connection.php');

$resident_id = (int)$_SESSION['user_id'];

// Block if pending request already exists
$chk = $conn->prepare("SELECT id FROM profile_update_requests WHERE resident_id = ? AND status = 'Pending' LIMIT 1");
$chk->bind_param('i', $resident_id);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    $chk->close(); $conn->close();
    header('Location: dashboard.php?exists=1'); exit();
}
$chk->close();

// Whitelist validation
$allowed_civil  = ['Single','Married','Widowed','Divorced'];
$allowed_purok  = ['Purok 1','Purok 2','Purok 3','Purok 4','Purok 5','Purok 6','Purok 7','Purok 8','Purok 9','Purok 10'];
$allowed_hh     = ['Head','Spouse','Son','Daughter','Mother','Father','Grandparent','Other'];
$allowed_educ   = ['None','Elementary','High School','Senior High','College','Vocational','Post Graduate'];

$contact_no             = preg_replace('/[^0-9+\-() ]/', '', substr($_POST['contact_no']   ?? '', 0, 20));
$occupation             = strip_tags(substr(trim($_POST['occupation']             ?? ''), 0, 100));
$religion               = strip_tags(substr(trim($_POST['religion']               ?? ''), 0, 100));
$resident_note          = strip_tags(substr(trim($_POST['resident_note']          ?? ''), 0, 500));
$monthly_income         = is_numeric($_POST['monthly_income'] ?? '') ? round((float)$_POST['monthly_income'], 2) : null;
$civil_status           = in_array($_POST['civil_status']           ?? '', $allowed_civil, true) ? $_POST['civil_status']           : null;
$purok                  = in_array($_POST['purok']                  ?? '', $allowed_purok, true) ? $_POST['purok']                  : null;
$household_position     = in_array($_POST['household_position']     ?? '', $allowed_hh,   true) ? $_POST['household_position']     : null;
$educational_attainment = in_array($_POST['educational_attainment'] ?? '', $allowed_educ, true) ? $_POST['educational_attainment'] : null;

// Photo upload (optional)
$new_image_path = null;
if (!empty($_FILES['new_photo']['tmp_name']) && $_FILES['new_photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['new_photo'];
    if ($file['size'] > 3 * 1024 * 1024) {
        $conn->close(); header('Location: dashboard.php?error=file_too_large'); exit();
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $mime_map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!array_key_exists($mime, $mime_map)) {
        $conn->close(); header('Location: dashboard.php?error=invalid_file'); exit();
    }
    $filename = time() . '_' . $resident_id . '_req.' . $mime_map[$mime];
    if (!move_uploaded_file($file['tmp_name'], '../officials/uploads/residents/' . $filename)) {
        $conn->close(); header('Location: dashboard.php?error=upload_failed'); exit();
    }
    $new_image_path = $filename;
}

// Insert — single clean bind_param with correct types
// i=resident_id, s=contact_no, s=occupation, d=monthly_income, s=civil_status,
// s=religion, s=purok, s=household_position, s=educational_attainment,
// s=new_image_path, s=resident_note
$stmt = $conn->prepare("
    INSERT INTO profile_update_requests
        (resident_id, contact_no, occupation, monthly_income, civil_status,
         religion, purok, household_position, educational_attainment,
         new_image_path, resident_note)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('issdsssssss',
    $resident_id,
    $contact_no,
    $occupation,
    $monthly_income,
    $civil_status,
    $religion,
    $purok,
    $household_position,
    $educational_attainment,
    $new_image_path,
    $resident_note
);
$stmt->execute();
$stmt->close();
$conn->close();

header('Location: dashboard.php?sent=1');
exit();