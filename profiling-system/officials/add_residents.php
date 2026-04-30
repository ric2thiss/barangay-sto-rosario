<?php
/**
 * add_residents.php  (officials/ folder — admin side)
 * Inserts directly into `residents` table (admin-approved).
 */
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php");
    exit();
}

include("connection.php");
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'add_resident_errors.log');
// ── DUPLICATE PERSON CHECK (first_name + surname + birthdate) ────────────
// Uses separate queries per table to avoid collation mismatch on UNION.
$fn = trim($_POST['first_name'] ?? '');
$sn = trim($_POST['surname'] ?? '');
$mn = trim($_POST['middle_name'] ?? '');
$bd = trim($_POST['birthdate'] ?? '');

$dupFound = false;
$dupSource = '';
$dupUsername = '';

// Check residents table
$chkRes = $conn->prepare("SELECT id, username FROM residents WHERE first_name = ? AND surname = ? AND birthdate = ? LIMIT 1");
$chkRes->bind_param("sss", $fn, $sn, $bd);
$chkRes->execute();
$chkRes->store_result();
if ($chkRes->num_rows > 0) {
    $chkRes->bind_result($dupId, $dupUsername);
    $chkRes->fetch();
    $dupFound = true;
    $dupSource = 'Resident';
}
$chkRes->close();

// Check pending_registrations table
if (!$dupFound) {
    $chkPend = $conn->prepare("SELECT id, username FROM pending_registrations WHERE first_name = ? AND surname = ? AND birthdate = ? LIMIT 1");
    $chkPend->bind_param("sss", $fn, $sn, $bd);
    $chkPend->execute();
    $chkPend->store_result();
    if ($chkPend->num_rows > 0) {
        $chkPend->bind_result($dupId, $dupUsername);
        $chkPend->fetch();
        $dupFound = true;
        $dupSource = 'Pending Registration';
    }
    $chkPend->close();
}

// Check barangay_official table
if (!$dupFound) {
    $chkOff = $conn->prepare("SELECT id, username FROM barangay_official WHERE first_name = ? AND surname = ? AND birthdate = ? LIMIT 1");
    $chkOff->bind_param("sss", $fn, $sn, $bd);
    $chkOff->execute();
    $chkOff->store_result();
    if ($chkOff->num_rows > 0) {
        $chkOff->bind_result($dupId, $dupUsername);
        $chkOff->fetch();
        $dupFound = true;
        $dupSource = 'Barangay Official';
    }
    $chkOff->close();
}

if ($dupFound) {
    $_SESSION['error'] = "A person named \"$fn $sn\" with birthdate \"$bd\" already exists in the system ($dupSource record" . (!empty($dupUsername) ? ", Account: \"$dupUsername\"" : "") . "). Duplicate registration is not allowed.";
    header("Location: resident.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit();
}

// ── Helper: resolve "Other – specify" dropdowns ──────────────────────────
function resolveField(string $key, string $other_key, $conn): ?string
{
    $val = trim($_POST[$key] ?? '');
    if ($val === 'Other') {
        $specify = trim($_POST[$other_key] ?? '');
        $val = $specify !== '' ? $specify : 'Other';
    }
    return $val !== '' ? $conn->real_escape_string($val) : null;
}

// ── Helper: PSA-based socioeconomic classification ──────────────────────
function classifySES(?float $monthly_income): ?string
{
    if ($monthly_income === null || $monthly_income < 0)
        return null;
    if ($monthly_income < 10957)
        return 'Poor';
    if ($monthly_income < 21914)
        return 'Low Income';
    if ($monthly_income < 43828)
        return 'Lower Middle Income';
    if ($monthly_income < 76669)
        return 'Middle Income';
    if ($monthly_income < 131484)
        return 'Upper Middle Income';
    return 'High Income';
}

// ── 1. USERNAME & PASSWORD ───────────────────────────────────────────────
$username = $conn->real_escape_string(trim($_POST['username'] ?? ''));
$password = trim($_POST['password'] ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');

if (strlen($username) < 4 || strlen($username) > 20) {
    $_SESSION['error'] = "Username must be 4–20 characters.";
    header("Location: resident.php");
    exit();
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $_SESSION['error'] = "Username: letters, numbers, underscore only.";
    header("Location: resident.php");
    exit();
}

$chk = $conn->prepare("SELECT id FROM residents WHERE username = ? UNION SELECT id FROM pending_registrations WHERE username = ?");
$chk->bind_param("ss", $username, $username);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $_SESSION['error'] = "Username '$username' is already taken.";
    $chk->close();
    header("Location: resident.php");
    exit();
}
$chk->close();

if (strlen($password) < 6) {
    $_SESSION['error'] = "Password must be at least 6 characters.";
    header("Location: resident.php");
    exit();
}
if ($password !== $confirm) {
    $_SESSION['error'] = "Passwords do not match!";
    header("Location: resident.php");
    exit();
}
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ── 2. PERSONAL ──────────────────────────────────────────────────────────
$first_name = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
$middle_name = $conn->real_escape_string(trim($_POST['middle_name'] ?? ''));
$surname = $conn->real_escape_string(trim($_POST['surname'] ?? ''));
$suffix = !empty(trim($_POST['suffix'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['suffix'])) : null;

if (empty($first_name) || empty($surname)) {
    $_SESSION['error'] = "First name and surname are required.";
    header("Location: resident.php");
    exit();
}

$birthdate = '';
if (!empty($_POST['birthdate'])) {
    $bd = trim($_POST['birthdate']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd)) {
        [$y, $m, $d] = explode('-', $bd);
        if (checkdate((int) $m, (int) $d, (int) $y) && (int) $y >= 1900 && (int) $y <= date('Y'))
            $birthdate = $bd;
        else {
            $_SESSION['error'] = "Invalid birth year: $y.";
            header("Location: resident.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid birthdate format (must be YYYY-MM-DD).";
        header("Location: resident.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Birthdate is required.";
    header("Location: resident.php");
    exit();
}

$birthplace = $conn->real_escape_string(trim($_POST['birthplace'] ?? ''));
$age = intval($_POST['age'] ?? 0);
$sex = $conn->real_escape_string($_POST['sex'] ?? '');
$civil_status = $conn->real_escape_string($_POST['civil_status'] ?? '');
$nationality = $conn->real_escape_string(trim($_POST['nationality'] ?? 'Filipino'));

// ── LGBTQ+ identity ─────────────────────────────────────────────────
$lgbtq_identity = null;
$lgbtq_other_text = null;
if ($sex === 'LGBTQ+') {
    $li = trim($_POST['lgbtq_identity'] ?? '');
    if (!empty($li))
        $lgbtq_identity = $conn->real_escape_string($li);
    if ($li === 'Other') {
        $lot = trim($_POST['lgbtq_other_text'] ?? '');
        if (!empty($lot))
            $lgbtq_other_text = $conn->real_escape_string($lot);
    }
}

// ── 3. DEMOGRAPHIC ───────────────────────────────────────────────────────
$religion = resolveField('religion', 'religion_other', $conn);
$ethnicity = resolveField('ethnicity', 'ethnicity_other', $conn);
$blood_type = resolveField('blood_type', 'blood_type_other', $conn);
$philhealth_no = !empty(trim($_POST['philhealth_no'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['philhealth_no'])) : null;
$length_of_residency = (isset($_POST['length_of_residency']) && $_POST['length_of_residency'] !== '')
    ? intval($_POST['length_of_residency']) : null;

// ── 4. ADDRESS ───────────────────────────────────────────────────────────
$household_no = !empty(trim($_POST['household_no'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['household_no'])) : null;
$purok = $conn->real_escape_string(trim($_POST['purok'] ?? ''));
$barangay = $conn->real_escape_string(trim($_POST['barangay'] ?? ''));
$municipality = $conn->real_escape_string(trim($_POST['municipality'] ?? ''));
$province = $conn->real_escape_string(trim($_POST['province'] ?? ''));

if (empty($purok) || empty($barangay)) {
    $_SESSION['error'] = "Purok and Barangay are required.";
    header("Location: resident.php");
    exit();
}

// Purok President RBAC: lock to their own purok
if (($_SESSION['staff_position'] ?? '') === 'Purok President') {
    $purok = $conn->real_escape_string($_SESSION['purok'] ?? '');
    if (empty($purok)) {
        $_SESSION['error'] = "Your Purok assignment is missing. Contact an administrator.";
        header("Location: resident.php");
        exit();
    }
}

// ── 5. VOTER / EDUCATION ─────────────────────────────────────────────────
$voters_status = $conn->real_escape_string($_POST['voters_status'] ?? 'No');
$educational_attainment = $conn->real_escape_string($_POST['educational_attainment'] ?? '');
$total_household = intval($_POST['total_household'] ?? 1);
$grade_level = !empty(trim($_POST['grade_level'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['grade_level'])) : null;
$school_name = !empty(trim($_POST['school_name'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['school_name'])) : null;

// ── 5b. GRADUATE EDUCATION FIELDS ────────────────────────────────────────
$course = null;
$course_other = null;
$graduation_date = null;
$eligibility = null;
$eligibility_other = null;

$rawCourse = trim($_POST['course'] ?? '');
if ($rawCourse !== '') {
    if ($rawCourse === 'Others') {
        $co = trim($_POST['course_other'] ?? '');
        $course = 'Others';
        $course_other = $co !== '' ? $conn->real_escape_string($co) : null;
    } else {
        $course = $conn->real_escape_string($rawCourse);
    }
}

$rawGradDate = trim($_POST['graduation_date'] ?? '');
if ($rawGradDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawGradDate)) {
    $graduation_date = $rawGradDate;
}

$rawElig = trim($_POST['eligibility'] ?? '');
if ($rawElig !== '') {
    if ($rawElig === 'Others') {
        $eo = trim($_POST['eligibility_other'] ?? '');
        $eligibility = 'Others';
        $eligibility_other = $eo !== '' ? $conn->real_escape_string($eo) : null;
    } else {
        $eligibility = $conn->real_escape_string($rawElig);
    }
}

// ── 6. SPECIAL STATUS ────────────────────────────────────────────────────
$is_pwd = ($_POST['is_pwd'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$pwd_type = null;

if ($is_pwd === 'Yes') {
    // FIX: Check pwd_type first (radio), then pwd_type_resolved (hidden for "Other" free-text)
    $pt = trim($_POST['pwd_type'] ?? '');

    if ($pt === 'Other' || empty($pt)) {
        // Use the hidden resolved field which contains the free-text value
        $resolved = trim($_POST['pwd_type_resolved'] ?? '');
        if (!empty($resolved)) {
            $pt = $resolved;
        }
    }

    if (empty($pt)) {
        $_SESSION['error'] = "Please specify the disability type for PWD.";
        header("Location: resident.php");
        exit();
    }
    $pwd_type = $conn->real_escape_string($pt);
}

$is_deceased = ($_POST['is_deceased'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$date_of_death = null;
if ($is_deceased === 'Yes' && !empty($_POST['date_of_death'])) {
    $dd = trim($_POST['date_of_death']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dd)) {
        [$dy, $dm, $dday] = explode('-', $dd);
        if (checkdate((int) $dm, (int) $dday, (int) $dy))
            $date_of_death = $dd;
    }
}
$is_newborn = ($_POST['is_newborn'] ?? 'No') === 'Yes' ? 'Yes' : 'No';

// ── 7. CONTACT, OCCUPATION TYPE & OCCUPATION ────────────────────────────
$contact_no = $conn->real_escape_string(trim($_POST['contact_no'] ?? ''));
$email = !empty(trim($_POST['email'] ?? '')) ? $conn->real_escape_string(trim($_POST['email'])) : null;

$occupation_type = resolveField('occupation_type', 'occupation_type_other', $conn);
if (empty($occupation_type)) {
    $_SESSION['error'] = "Please select an Occupation Type.";
    header("Location: resident.php");
    exit();
}

$occupation = null;
if (!empty(trim($_POST['occupation'] ?? ''))) {
    $occ = trim($_POST['occupation']);
    if (strtolower($occ) !== 'n/a' && $occ !== '0')
        $occupation = $conn->real_escape_string($occ);
}

$monthly_income = (isset($_POST['monthly_income']) && $_POST['monthly_income'] !== '')
    ? floatval($_POST['monthly_income']) : null;
$annual_income = ($monthly_income !== null) ? round($monthly_income * 12, 2) : null;
$socioeconomic_status = ($monthly_income !== null) ? classifySES($monthly_income) : null;

$hp = trim($_POST['household_position'] ?? '');
if (empty($hp)) {
    $_SESSION['error'] = "Household position is required.";
    header("Location: resident.php");
    exit();
}
$household_position = $conn->real_escape_string($hp);

// ── 8. HOUSING ───────────────────────────────────────────────────────────
$house_ownership = resolveField('house_ownership', 'house_ownership_other', $conn);
$house_material = resolveField('house_material', 'house_material_other', $conn);
$toilet_type = resolveField('toilet_type', 'toilet_type_other', $conn);
$water_source = resolveField('water_source', 'water_source_other', $conn);

// ── 9. SOCIAL FLAGS ──────────────────────────────────────────────────────
$is_4ps = ($_POST['is_4ps'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$is_nhts = ($_POST['is_nhts'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$is_solo_parent = ($_POST['is_solo_parent'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$is_smoker = ($_POST['is_smoker'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$is_binge_drinker = ($_POST['is_binge_drinker'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$family_planning = ($_POST['family_planning'] ?? 'No') === 'Yes' ? 'Yes' : 'No';

// ── 10. HEALTH CONDITIONS ────────────────────────────────────────────────
$has_hypertension = ($_POST['has_hypertension'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$has_diabetes = ($_POST['has_diabetes'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$has_asthma = ($_POST['has_asthma'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$has_tb = ($_POST['has_tb'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$has_cancer = ($_POST['has_cancer'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$has_mental_health = ($_POST['has_mental_health'] ?? 'No') === 'Yes' ? 'Yes' : 'No';

// ── 11. PHILHEALTH MEMBERSHIP ─────────────────────────────────────────────
$membership_type = !empty(trim($_POST['membership_type'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['membership_type'])) : null;

// ── 12. IMAGE — camera first, then file upload ───────────────────────────
$uploadDir = "uploads/residents/";
if (!file_exists($uploadDir))
    mkdir($uploadDir, 0777, true);

// Gender-based default photo
if ($sex === 'LGBTQ+') {
    $image_path = 'default_photo_lgbtq.jpg';
} elseif ($sex === 'Female') {
    $image_path = 'default_photo_female.jpg';
} else {
    $image_path = 'default_photo_male.jpg';
}

if (!empty($_POST['camera_image'])) {
    $camData = $_POST['camera_image'];
    if (preg_match('/^data:image\/(\w+);base64,/', $camData, $matches)) {
        $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $imgBinary = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $camData));
        if ($imgBinary === false || strlen($imgBinary) > 2 * 1024 * 1024) {
            $_SESSION['error'] = "Camera image is invalid or too large (max 2MB).";
            header("Location: resident.php");
            exit();
        }
        $image_path = time() . '_' . uniqid() . '_cam.' . $ext;
        if (file_put_contents($uploadDir . $image_path, $imgBinary) === false) {
            $_SESSION['error'] = "Failed to save camera photo.";
            header("Location: resident.php");
            exit();
        }
    }
} elseif (!empty($_FILES['Image']['name'])) {
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['Image']['type'], $allowed)) {
        $_SESSION['error'] = "Invalid image type. Only JPG, PNG, GIF allowed.";
        header("Location: resident.php");
        exit();
    }
    if ($_FILES['Image']['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = "Image must be under 2MB.";
        header("Location: resident.php");
        exit();
    }
    $ext = pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION);
    $image_path = time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES['Image']['tmp_name'], $uploadDir . $image_path)) {
        $_SESSION['error'] = "Failed to upload image.";
        header("Location: resident.php");
        exit();
    }
}
// If no photo uploaded, the gender-based default is used

// ── 13. INSERT ───────────────────────────────────────────────────────────
$sql = "
    INSERT INTO residents (
        username, password,
        first_name, middle_name, surname, suffix,
        birthdate, birthplace, age, sex, lgbtq_identity, lgbtq_other_text, civil_status, nationality,
        religion, ethnicity, blood_type, philhealth_no, length_of_residency,
        household_no, purok, barangay, municipality, province,
        voters_status, educational_attainment, total_household,
        grade_level, school_name,
        course, course_other, graduation_date, eligibility, eligibility_other,
        is_pwd, pwd_type, is_deceased, date_of_death, is_newborn,
        contact_no, email, occupation_type, occupation,
        monthly_income, annual_income, socioeconomic_status, household_position,
        house_ownership, house_material, toilet_type, water_source,
        is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker,
        has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health,
        membership_type, family_planning,
        image_path, created_at
    ) VALUES (
        ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?,
        ?, NOW()
    )";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $_SESSION['error'] = "DB prepare error: " . $conn->error;
    error_log("PREPARE ERROR: " . $conn->error);
    header("Location: resident.php");
    exit();
}

$types =
    'ss' .  // username, password
    'ssss' .  // first_name, middle_name, surname, suffix
    'ssisss' .  // birthdate, birthplace, age(i), sex, lgbtq_identity, lgbtq_other_text
    'ss' .  // civil_status, nationality
    'ssssi' .  // religion, ethnicity, blood_type, philhealth_no, length_of_residency(i)
    'sssss' .  // household_no, purok, barangay, municipality, province
    'ssiss' .  // voters_status, educational_attainment, total_household(i), grade_level, school_name
    'sssss' .  // course, course_other, graduation_date, eligibility, eligibility_other
    'sssss' .  // is_pwd, pwd_type, is_deceased, date_of_death, is_newborn
    'ssss' .  // contact_no, email, occupation_type, occupation
    'ddss' .  // monthly_income(d), annual_income(d), socioeconomic_status, household_position
    'ssss' .  // house_ownership, house_material, toilet_type, water_source
    'sssss' .  // is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker
    'ssssss' .  // has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health
    'ss' .  // membership_type, family_planning
    's';        // image_path
// Total: 2+4+6+2+5+5+5+5+5+3+4+4+5+6+2+1 = 64

$stmt->bind_param(
    $types,
    $username,
    $hashed_password,
    $first_name,
    $middle_name,
    $surname,
    $suffix,
    $birthdate,
    $birthplace,
    $age,
    $sex,
    $lgbtq_identity,
    $lgbtq_other_text,
    $civil_status,
    $nationality,
    $religion,
    $ethnicity,
    $blood_type,
    $philhealth_no,
    $length_of_residency,
    $household_no,
    $purok,
    $barangay,
    $municipality,
    $province,
    $voters_status,
    $educational_attainment,
    $total_household,
    $grade_level,
    $school_name,
    $course,
    $course_other,
    $graduation_date,
    $eligibility,
    $eligibility_other,
    $is_pwd,
    $pwd_type,
    $is_deceased,
    $date_of_death,
    $is_newborn,
    $contact_no,
    $email,
    $occupation_type,
    $occupation,
    $monthly_income,
    $annual_income,
    $socioeconomic_status,
    $household_position,
    $house_ownership,
    $house_material,
    $toilet_type,
    $water_source,
    $is_4ps,
    $is_nhts,
    $is_solo_parent,
    $is_smoker,
    $is_binge_drinker,
    $has_hypertension,
    $has_diabetes,
    $has_asthma,
    $has_tb,
    $has_cancer,
    $has_mental_health,
    $membership_type,
    $family_planning,
    $image_path
);

if ($stmt->execute()) {
    $new_id = $stmt->insert_id;
    $_SESSION['success'] = "Resident added successfully! (ID: $new_id, Username: $username)";
} else {
    $err = $stmt->error;
    error_log("EXECUTE ERROR: $err");
    $_SESSION['error'] = "Insert failed: $err";
}

$stmt->close();
$conn->close();
header("Location: resident.php");
exit();
?>