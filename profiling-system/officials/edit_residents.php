<?php
/**
 * edit_residents.php
 * Handles POST from edit_resident_modal.php — updates residents table.
 *
 * SYNC with register_account.php:
 *   + suffix
 *   + occupation_type  (with Other support)
 *   + pwd_type         (replaces pwd_details)
 *   + annual_income    (auto-computed as monthly × 12)
 *   + socioeconomic_status  (auto-computed server-side)
 */
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php"); exit();
}

include("connection.php");

// ── RBAC: Block users without edit privilege ──────────────────────────
$is_superadmin = ($_SESSION['user_type'] === 'admin') || (!empty($_SESSION['is_superadmin']));
if (!$is_superadmin && empty($_SESSION['can_edit_resident'])) {
    $_SESSION['error'] = 'You do not have permission to edit residents.';
    header("Location: resident.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: resident.php"); exit();
}

// ── Helpers ───────────────────────────────────────────────────────────────
if (!function_exists('resolveFieldEdit')) {
    function resolveFieldEdit(string $key, string $other_key, $conn): ?string {
        $val = trim($_POST[$key] ?? '');
        if ($val === 'Other') {
            $specify = trim($_POST[$other_key] ?? '');
            $val     = $specify !== '' ? $specify : 'Other';
        }
        return $val !== '' ? $conn->real_escape_string($val) : null;
    }
}
if (!function_exists('esc')) {
    function esc($conn, string $key, string $default = ''): string {
        return $conn->real_escape_string(trim($_POST[$key] ?? $default));
    }
}
if (!function_exists('yesno')) {
    function yesno(string $key): string {
        return (isset($_POST[$key]) && $_POST[$key] === 'Yes') ? 'Yes' : 'No';
    }
}
if (!function_exists('optFloat')) {
    function optFloat(string $key): ?float {
        return (isset($_POST[$key]) && $_POST[$key] !== '') ? floatval($_POST[$key]) : null;
    }
}
if (!function_exists('optInt')) {
    function optInt(string $key): ?int {
        return (isset($_POST[$key]) && $_POST[$key] !== '') ? intval($_POST[$key]) : null;
    }
}
if (!function_exists('sqlVal')) {
    function sqlVal(?string $v): string {
        return $v !== null ? "'" . $v . "'" : 'NULL';
    }
}
if (!function_exists('sqlNum')) {
    function sqlNum($v): string {
        return $v !== null ? (string)(float)$v : 'NULL';
    }
}
if (!function_exists('sqlInt')) {
    function sqlInt($v): string {
        return $v !== null ? (string)(int)$v : 'NULL';
    }
}

// ── PSA SES classifier (mirrors register_account.php) ────────────────────
if (!function_exists('classifySESEdit')) {
    function classifySESEdit(?float $m): ?string {
        if ($m === null || $m < 0) return null;
        if ($m < 10957)   return 'Poor';
        if ($m < 21914)   return 'Low Income';
        if ($m < 43828)   return 'Lower Middle Income';
        if ($m < 76669)   return 'Middle Income';
        if ($m < 131484)  return 'Upper Middle Income';
        return 'High Income';
    }
}

// ── 1. Validate ID ────────────────────────────────────────────────────────
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid resident ID.";
    header("Location: resident.php"); exit();
}

// ── 2. Personal ───────────────────────────────────────────────────────────
$first_name   = esc($conn, 'first_name');
$middle_name  = esc($conn, 'middle_name');
$surname      = esc($conn, 'surname');
$suffix       = !empty(trim($_POST['suffix'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['suffix'])) : null;
$birthplace   = esc($conn, 'birthplace');
$age          = intval($_POST['age'] ?? 0);
$sex          = esc($conn, 'sex');
$civil_status = esc($conn, 'civil_status');
$nationality  = esc($conn, 'nationality', 'Filipino');
$contact_no   = esc($conn, 'contact_no');
$email        = !empty(trim($_POST['email'] ?? '')) ? $conn->real_escape_string(trim($_POST['email'])) : null;

if (empty($first_name) || empty($surname)) {
    $_SESSION['error'] = "First name and surname are required.";
    header("Location: resident.php"); exit();
}

$birthdate = '';
if (!empty($_POST['birthdate'])) {
    $bd = trim($_POST['birthdate']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd)) {
        [$y, $m, $d] = explode('-', $bd);
        if (checkdate((int)$m, (int)$d, (int)$y) && (int)$y >= 1900 && (int)$y <= date('Y'))
            $birthdate = $bd;
    }
}
if (empty($birthdate)) {
    $_SESSION['error'] = "Invalid birthdate format (must be YYYY-MM-DD).";
    header("Location: resident.php"); exit();
}

// ── 3. Demographic ────────────────────────────────────────────────────────
$religion            = resolveFieldEdit('religion',   'religion_other',   $conn);
$ethnicity           = resolveFieldEdit('ethnicity',  'ethnicity_other',  $conn);
$blood_type          = resolveFieldEdit('blood_type', 'blood_type_other', $conn);
$height              = optFloat('height');
$weight              = optFloat('weight');
$philhealth_no       = !empty(trim($_POST['philhealth_no'] ?? '')) ? esc($conn, 'philhealth_no') : null;
$length_of_residency = optInt('length_of_residency');
$membership_type     = !empty(trim($_POST['membership_type'] ?? '')) ? esc($conn, 'membership_type') : null;
$is_nhts             = yesno('is_nhts');
$is_4ps              = yesno('is_4ps');
$is_solo_parent      = yesno('is_solo_parent');
$family_planning     = yesno('family_planning');

// ── 4. Address ────────────────────────────────────────────────────────────
$household_no = !empty(trim($_POST['household_no'] ?? '')) ? esc($conn, 'household_no') : null;
$purok        = esc($conn, 'purok');
$barangay     = esc($conn, 'barangay');
$municipality = esc($conn, 'municipality');
$province     = esc($conn, 'province');

if (empty($purok) || empty($barangay)) {
    $_SESSION['error'] = "Purok and Barangay are required.";
    header("Location: resident.php"); exit();
}

// Purok President RBAC: lock to their own purok and barangay
if (($_SESSION['staff_position'] ?? '') === 'Purok President') {
    $pp_purok = $conn->real_escape_string($_SESSION['purok'] ?? '');
    $pp_barangay = $conn->real_escape_string($_SESSION['barangay'] ?? '');
    // Verify the resident being edited belongs to their purok and barangay
    $chk = $conn->prepare("SELECT purok, barangay FROM residents WHERE id = ? LIMIT 1");
    $chk->bind_param('i', $id);
    $chk->execute();
    $orig = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($orig && ($orig['purok'] !== $pp_purok || $orig['barangay'] !== $pp_barangay)) {
        $_SESSION['error'] = "You can only edit residents within your barangay ($pp_barangay) and purok ($pp_purok).";
        header("Location: resident.php"); exit();
    }
    // Force purok and barangay to their own
    $purok = $pp_purok;
    $barangay = $pp_barangay;
}

// ── 5. Housing ────────────────────────────────────────────────────────────
$house_ownership = resolveFieldEdit('house_ownership', 'house_ownership_other', $conn);
$house_material  = resolveFieldEdit('house_material',  'house_material_other',  $conn);
$toilet_type     = resolveFieldEdit('toilet_type',     'toilet_type_other',     $conn);
$water_source    = resolveFieldEdit('water_source',    'water_source_other',    $conn);

// ── 6. Occupation Type, Occupation & Income ───────────────────────────────
// occupation_type — required, with Other support
$occupation_type = resolveFieldEdit('occupation_type', 'occupation_type_other', $conn);
if (empty($occupation_type)) {
    $_SESSION['error'] = "Please select an Occupation Type.";
    header("Location: resident.php"); exit();
}

$occupation        = !empty(trim($_POST['occupation'] ?? '')) ? esc($conn, 'occupation') : null;
$father_name       = esc($conn, 'father_name');
$father_occupation = esc($conn, 'father_occupation');
$mother_name       = esc($conn, 'mother_name');
$mother_occupation = esc($conn, 'mother_occupation');

// Annual income computed server-side; SES auto-classified
$monthly_income       = optFloat('monthly_income');
$annual_income        = ($monthly_income !== null) ? round($monthly_income * 12, 2) : null;
$socioeconomic_status = ($monthly_income !== null) ? classifySESEdit($monthly_income) : null;

// ── 7. Voter & Education ──────────────────────────────────────────────────
$voters_status          = esc($conn, 'voters_status', 'No');
$educational_attainment = esc($conn, 'educational_attainment');
$grade_level            = !empty(trim($_POST['grade_level'] ?? '')) ? esc($conn, 'grade_level') : null;
$school_name            = !empty(trim($_POST['school_name'] ?? '')) ? esc($conn, 'school_name') : null;

// Graduate-level education fields
$course = resolveFieldEdit('course', 'course_other', $conn);
$course_other = !empty(trim($_POST['course_other'] ?? '')) ? esc($conn, 'course_other') : null;
$graduation_date = null;
if (!empty(trim($_POST['graduation_date'] ?? ''))) {
    $gd = trim($_POST['graduation_date']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $gd)) $graduation_date = $gd;
}
$eligibility = resolveFieldEdit('eligibility', 'eligibility_other', $conn);
$eligibility_other = !empty(trim($_POST['eligibility_other'] ?? '')) ? esc($conn, 'eligibility_other') : null;

$total_household        = intval($_POST['total_household'] ?? 1);
$household_position     = esc($conn, 'household_position');

if (empty($household_position)) {
    $_SESSION['error'] = "Household position is required.";
    header("Location: resident.php"); exit();
}

// ── 8. Special Status ─────────────────────────────────────────────────────
$is_pwd   = yesno('is_pwd');
$pwd_type = null;
if ($is_pwd === 'Yes') {
    // Accept pwd_type (structured) or pwd_details (legacy fallback)
    $pt = trim($_POST['pwd_type'] ?? '');
    if (empty($pt)) $pt = trim($_POST['pwd_details'] ?? '');
    if (empty($pt)) {
        $_SESSION['error'] = "Please specify the disability type for PWD.";
        header("Location: resident.php"); exit();
    }
    $pwd_type = $conn->real_escape_string($pt);
}

$is_newborn  = yesno('is_newborn');
$is_deceased = yesno('is_deceased');
$date_of_death = null;
if ($is_deceased === 'Yes' && !empty($_POST['date_of_death'])) {
    $dd = trim($_POST['date_of_death']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dd)) {
        [$dy, $dm, $dday] = explode('-', $dd);
        if (checkdate((int)$dm, (int)$dday, (int)$dy))
            $date_of_death = $dd;
    }
}

// ── 9. Health Conditions ──────────────────────────────────────────────────
$is_smoker         = yesno('is_smoker');
$is_binge_drinker  = yesno('is_binge_drinker');
$has_hypertension  = yesno('has_hypertension');
$has_diabetes      = yesno('has_diabetes');
$has_asthma        = yesno('has_asthma');
$has_tb            = yesno('has_tb');
$has_cancer        = yesno('has_cancer');
$has_mental_health = yesno('has_mental_health');

// ── 10. Image — camera takes priority over file upload ────────────────────
$uploadDir = "uploads/residents/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
$image_sql = ''; // empty = keep current image

if (!empty($_POST['camera_image'])) {
    $camData = $_POST['camera_image'];
    if (preg_match('/^data:image\/(\w+);base64,/', $camData, $matches)) {
        $ext       = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $imgBinary = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $camData));
        if ($imgBinary === false || strlen($imgBinary) > 2 * 1024 * 1024) {
            $_SESSION['error'] = "Camera image is invalid or too large (max 2MB).";
            header("Location: resident.php"); exit();
        }
        $oldRow = $conn->query("SELECT image_path FROM residents WHERE id=$id")->fetch_assoc();
        if ($oldRow && $oldRow['image_path'] !== 'default.jpg') {
            $oldFile = $uploadDir . $oldRow['image_path'];
            if (file_exists($oldFile)) unlink($oldFile);
        }
        $newName = time() . '_' . uniqid() . '_cam.' . $ext;
        if (file_put_contents($uploadDir . $newName, $imgBinary) === false) {
            $_SESSION['error'] = "Failed to save camera photo.";
            header("Location: resident.php"); exit();
        }
        $image_sql = ", image_path = '" . $conn->real_escape_string($newName) . "'";
    }

} elseif (!empty($_FILES['image_path']['name'])) {
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['image_path']['type'], $allowed)) {
        $_SESSION['error'] = "Invalid image type. Only JPG, PNG, GIF allowed.";
        header("Location: resident.php"); exit();
    }
    if ($_FILES['image_path']['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = "Image must be under 2MB.";
        header("Location: resident.php"); exit();
    }
    $oldRow = $conn->query("SELECT image_path FROM residents WHERE id=$id")->fetch_assoc();
    if ($oldRow && $oldRow['image_path'] !== 'default.jpg') {
        $oldFile = $uploadDir . $oldRow['image_path'];
        if (file_exists($oldFile)) unlink($oldFile);
    }
    $ext     = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
    $newName = time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES['image_path']['tmp_name'], $uploadDir . $newName)) {
        $_SESSION['error'] = "Failed to upload image.";
        header("Location: resident.php"); exit();
    }
    $image_sql = ", image_path = '" . $conn->real_escape_string($newName) . "'";
}

// ── 11. UPDATE ────────────────────────────────────────────────────────────
$sql = "UPDATE residents SET
    first_name            = '$first_name',
    middle_name           = '$middle_name',
    surname               = '$surname',
    suffix                = " . sqlVal($suffix)               . ",
    birthdate             = '$birthdate',
    birthplace            = '$birthplace',
    age                   = $age,
    sex                   = '$sex',
    civil_status          = '$civil_status',
    nationality           = '$nationality',
    contact_no            = '$contact_no',
    email                 = " . sqlVal($email)                . ",
    religion              = " . sqlVal($religion)             . ",
    ethnicity             = " . sqlVal($ethnicity)            . ",
    blood_type            = " . sqlVal($blood_type)           . ",
    height                = " . sqlNum($height)               . ",
    weight                = " . sqlNum($weight)               . ",
    philhealth_no         = " . sqlVal($philhealth_no)        . ",
    length_of_residency   = " . sqlInt($length_of_residency)  . ",
    membership_type       = " . sqlVal($membership_type)      . ",
    is_nhts               = '$is_nhts',
    is_4ps                = '$is_4ps',
    is_solo_parent        = '$is_solo_parent',
    family_planning       = '$family_planning',
    household_no          = " . sqlVal($household_no)         . ",
    purok                 = '$purok',
    barangay              = '$barangay',
    municipality          = '$municipality',
    province              = '$province',
    house_ownership       = " . sqlVal($house_ownership)      . ",
    house_material        = " . sqlVal($house_material)       . ",
    toilet_type           = " . sqlVal($toilet_type)          . ",
    water_source          = " . sqlVal($water_source)         . ",
    occupation_type       = " . sqlVal($occupation_type)      . ",
    occupation            = " . sqlVal($occupation)           . ",
    father_name           = " . sqlVal($father_name)          . ",
    father_occupation     = " . sqlVal($father_occupation)    . ",
    mother_name           = " . sqlVal($mother_name)          . ",
    mother_occupation     = " . sqlVal($mother_occupation)    . ",
    monthly_income        = " . sqlNum($monthly_income)       . ",
    annual_income         = " . sqlNum($annual_income)        . ",
    socioeconomic_status  = " . sqlVal($socioeconomic_status) . ",
    voters_status         = '$voters_status',
    educational_attainment= '$educational_attainment',
    grade_level           = " . sqlVal($grade_level)          . ",
    school_name           = " . sqlVal($school_name)          . ",
    course                = " . sqlVal($course)               . ",
    course_other          = " . sqlVal($course_other)         . ",
    graduation_date       = " . sqlVal($graduation_date)      . ",
    eligibility           = " . sqlVal($eligibility)          . ",
    eligibility_other     = " . sqlVal($eligibility_other)    . ",
    total_household       = $total_household,
    household_position    = '$household_position',
    is_pwd                = '$is_pwd',
    pwd_type              = " . sqlVal($pwd_type)             . ",
    is_newborn            = '$is_newborn',
    is_deceased           = '$is_deceased',
    date_of_death         = " . sqlVal($date_of_death)        . ",
    is_smoker             = '$is_smoker',
    is_binge_drinker      = '$is_binge_drinker',
    has_hypertension      = '$has_hypertension',
    has_diabetes          = '$has_diabetes',
    has_asthma            = '$has_asthma',
    has_tb                = '$has_tb',
    has_cancer            = '$has_cancer',
    has_mental_health     = '$has_mental_health',
    updated_at            = NOW()
    $image_sql
WHERE id = $id
LIMIT 1";

if ($conn->query($sql) === true) {
    $_SESSION['success'] = "Resident (ID: $id) updated successfully!";

    // Log the edit action
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, username, full_name, action, details, ip_address, login_at, status) VALUES (?, ?, ?, ?, 'edit_resident', ?, ?, NOW(), 'offline')");
    $log_utype = $_SESSION['user_type'] ?? 'admin';
    $log_uname = $_SESSION['username'] ?? 'Admin';
    $log_fname = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
    $res_name = ($first_name ?? '') . ' ' . ($surname ?? '');
    $log_details = "Edited resident: $res_name (ID: $id)";
    if ((($_SESSION['user_type'] ?? '') === 'resident' || ($_SESSION['user_type'] ?? '') === 'staff') && ($_SESSION['staff_position'] ?? '') === 'Purok President') {
        $log_details .= " [Scope: Barangay " . ($_SESSION['barangay'] ?? 'N/A') . ", Purok " . ($_SESSION['purok'] ?? 'N/A') . "]";
    }
    $log_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $log_stmt->bind_param('isssss', $_SESSION['user_id'], $log_utype, $log_uname, $log_fname, $log_details, $log_ip);
    $log_stmt->execute();
    $log_stmt->close();
} else {
    error_log("EDIT UPDATE ERROR id=$id: " . $conn->error);
    $_SESSION['error'] = "Update failed: " . $conn->error;
}

$conn->close();
header("Location: resident.php");
exit();
?>