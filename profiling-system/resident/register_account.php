<?php
/**
 * register_account.php  (resident/ folder)
 * ─────────────────────────────────────────────────────────────────────────
 * PURE BACK-END PROCESSOR — no HTML output, no asset includes.
 * Inserts into pending_registrations (NOT residents).
 *
 * On any error  → stores message in $_SESSION['reg_error'] then redirects
 *                 back to register.php (the registration form).
 * On success    → sets $_SESSION['reg_success'] = true then redirects to
 *                 ../officials/login.php?registered=1
 *
 * WHY hybrid_assets.php IS NOT INCLUDED HERE:
 *   hybrid_assets.php outputs <script> tags starting at its very first line
 *   of real content (line 14 of that file). Any output sent before header()
 *   causes the "Cannot modify header information — headers already sent"
 *   warning and breaks all redirects. Asset/HTML includes belong only in
 *   pages that render a full HTML response, never in POST handlers.
 */

// ── MUST be the very first statement — nothing before this ────────────────
session_start();
include('../officials/connection.php');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/registration_errors.log');

// ── Only accept POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register_resident.php');
    exit();
}

/**
 * Store an error message in the session and redirect back to the form.
 * The registration form reads $_SESSION['reg_error'] to show a styled alert.
 * Using session flash instead of echo/alert keeps headers clean.
 */
function formError(string $msg): never
{
    $_SESSION['reg_error'] = $msg;
    header('Location: register_resident.php');
    exit();
}

// ── Helper: resolve "Other – specify" dropdowns ───────────────────────────
function resolveField(string $key, string $other_key, $conn): ?string
{
    $val = trim($_POST[$key] ?? '');
    if ($val === 'Other') {
        $specify = trim($_POST[$other_key] ?? '');
        $val = $specify !== '' ? $specify : 'Other';
    }
    return $val !== '' ? $conn->real_escape_string($val) : null;
}

// ── Helper: PSA FIES 2021 socioeconomic classification ────────────────────
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
// ── DUPLICATE PERSON CHECK (first_name + surname + birthdate) ────────────
// Uses separate queries per table to avoid collation mismatch on UNION.
$fn  = trim($_POST['first_name']  ?? '');
$sn  = trim($_POST['surname']     ?? '');
$mn  = trim($_POST['middle_name'] ?? '');
$bd  = trim($_POST['birthdate']   ?? '');

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
    formError("A person named \"$fn $sn\" with birthdate \"$bd\" already exists in the system ($dupSource record" . (!empty($dupUsername) ? ", Account: \"$dupUsername\"" : "") . "). Duplicate registration is not allowed.");
}
// ══════════════════════════════════════════════════════════════════════════
// 1. USERNAME & PASSWORD
// ══════════════════════════════════════════════════════════════════════════
$username = $conn->real_escape_string(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (strlen($username) < 4 || strlen($username) > 20) {
    formError('Username must be 4–20 characters.');
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    formError('Username may only contain letters, numbers, and underscores.');
}

$chk = $conn->prepare(
    'SELECT id FROM residents WHERE username = ?
     UNION
     SELECT id FROM pending_registrations WHERE username = ?'
);
$chk->bind_param('ss', $username, $username);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $chk->close();
    formError('Username is already taken. Please choose another.');
}
$chk->close();

if (strlen($password) < 6) {
    formError('Password must be at least 6 characters.');
}
if ($password !== $confirm) {
    formError('Passwords do not match.');
}
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ══════════════════════════════════════════════════════════════════════════
// 2. PERSONAL
// ══════════════════════════════════════════════════════════════════════════
$first_name = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
$middle_name = $conn->real_escape_string(trim($_POST['middle_name'] ?? ''));
$surname = $conn->real_escape_string(trim($_POST['surname'] ?? ''));
$suffix = !empty(trim($_POST['suffix'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['suffix'])) : null;

$birthdate = '';
if (!empty($_POST['birthdate'])) {
    $bd = trim($_POST['birthdate']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd)) {
        [$y, $m, $d] = explode('-', $bd);
        if (
            checkdate((int) $m, (int) $d, (int) $y)
            && (int) $y >= 1900
            && (int) $y <= (int) date('Y')
        ) {
            $birthdate = $bd;
        }
    }
}
if (empty($birthdate)) {
    formError('Invalid or missing birthdate.');
}

$birthplace = $conn->real_escape_string(trim($_POST['birthplace'] ?? ''));
$age = intval($_POST['age'] ?? 0);
$sex = $conn->real_escape_string($_POST['sex'] ?? '');
$civil_status = $conn->real_escape_string($_POST['civil_status'] ?? '');
$nationality = $conn->real_escape_string(trim($_POST['nationality'] ?? 'Filipino'));

// LGBTQ+ identity
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

// ══════════════════════════════════════════════════════════════════════════
// 3. DEMOGRAPHIC
// ══════════════════════════════════════════════════════════════════════════
$religion = resolveField('religion', 'religion_other', $conn);
$ethnicity = resolveField('ethnicity', 'ethnicity_other', $conn);
$blood_type = resolveField('blood_type', 'blood_type_other', $conn);
$philhealth_no = !empty(trim($_POST['philhealth_no'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['philhealth_no'])) : null;
$length_of_residency = (isset($_POST['length_of_residency']) && $_POST['length_of_residency'] !== '')
    ? intval($_POST['length_of_residency']) : null;

// ══════════════════════════════════════════════════════════════════════════
// 4. ADDRESS
// ══════════════════════════════════════════════════════════════════════════
$household_no = !empty(trim($_POST['household_no'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['household_no'])) : null;
$purok = $conn->real_escape_string(trim($_POST['purok'] ?? ''));
$barangay = $conn->real_escape_string(trim($_POST['barangay'] ?? ''));
$municipality = $conn->real_escape_string(trim($_POST['municipality'] ?? ''));
$province = $conn->real_escape_string(trim($_POST['province'] ?? ''));

$allowed_barangays = [
    'Buhang',
    'Caloc-an',
    'Guiasan',
    'Marcos',
    'Poblacion',
    'Santo Niño',
    'Santo Rosario',
    'Taod-oy',
];
if (!in_array($barangay, $allowed_barangays)) {
    formError('Please select a valid Barangay.');
}

// ══════════════════════════════════════════════════════════════════════════
// 5. VOTER / EDUCATION / GRADE / SCHOOL
// ══════════════════════════════════════════════════════════════════════════
$voters_status = $conn->real_escape_string($_POST['voters_status'] ?? 'No');
$educational_attainment = $conn->real_escape_string($_POST['educational_attainment'] ?? '');
$total_household = intval($_POST['total_household'] ?? 1);
$grade_level = !empty(trim($_POST['grade_level'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['grade_level'])) : null;
$school_name = !empty(trim($_POST['school_name'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['school_name'])) : null;

// ── Graduate-level fields: course, graduation date, eligibility ──────────
$graduate_levels = [
    'Elementary Graduate',
    'High School Graduate',
    'Senior High School Graduate',
    'College Graduate',
    'Vocational Graduate',
    'Post Graduate'
];
$course_levels = ['College Graduate', 'Vocational Graduate', 'Post Graduate'];

$course = null;
$course_other = null;
if (in_array($educational_attainment, $course_levels)) {
    $raw_course = trim($_POST['course'] ?? '');
    if ($raw_course === 'Others') {
        $course = 'Others';
        $co = trim($_POST['course_other'] ?? '');
        if (empty($co))
            formError('Please specify your course.');
        if (strlen($co) > 150)
            formError('Course name must not exceed 150 characters.');
        if (!preg_match('/^[a-zA-Z0-9\s\-\.\,\/\(\)]+$/', $co))
            formError('Course name contains invalid characters.');
        $course_other = $conn->real_escape_string($co);
    } elseif (!empty($raw_course)) {
        $course = $conn->real_escape_string($raw_course);
    }
}

$graduation_date = null;
if (in_array($educational_attainment, $graduate_levels) && !empty(trim($_POST['graduation_date'] ?? ''))) {
    $gd = trim($_POST['graduation_date']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $gd)) {
        [$gy, $gm, $gday] = explode('-', $gd);
        if (checkdate((int) $gm, (int) $gday, (int) $gy)) {
            if (strtotime($gd) > time())
                formError('Graduation date cannot be in the future.');
            $graduation_date = $gd;
        }
    }
}

$eligibility = null;
$eligibility_other = null;
if (in_array($educational_attainment, $graduate_levels)) {
    $raw_elig = trim($_POST['eligibility'] ?? '');
    if ($raw_elig === 'Others') {
        $eligibility = 'Others';
        $eo = trim($_POST['eligibility_other'] ?? '');
        if (empty($eo))
            formError('Please specify your eligibility.');
        if (strlen($eo) > 150)
            formError('Eligibility must not exceed 150 characters.');
        $eligibility_other = $conn->real_escape_string($eo);
    } elseif (!empty($raw_elig)) {
        $eligibility = $conn->real_escape_string($raw_elig);
    }
}

// ══════════════════════════════════════════════════════════════════════════
// 6. STATUS FLAGS
// ══════════════════════════════════════════════════════════════════════════
$is_pwd = (isset($_POST['is_pwd']) && $_POST['is_pwd'] === 'Yes') ? 'Yes' : 'No';
$pwd_type = null;
if ($is_pwd === 'Yes') {
    $pt = trim($_POST['pwd_type'] ?? '');
    if (empty($pt)) {
        formError('Please select a disability type for the PWD field.');
    }
    $pwd_type = $conn->real_escape_string($pt);
}

$is_deceased = (isset($_POST['is_deceased']) && $_POST['is_deceased'] === 'Yes') ? 'Yes' : 'No';
$date_of_death = null;
if ($is_deceased === 'Yes' && !empty($_POST['date_of_death'])) {
    $dd = trim($_POST['date_of_death']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dd)) {
        [$dy, $dm, $dday] = explode('-', $dd);
        if (checkdate((int) $dm, (int) $dday, (int) $dy)) {
            $date_of_death = $dd;
        }
    }
}

$is_newborn = (isset($_POST['is_newborn']) && $_POST['is_newborn'] === 'Yes') ? 'Yes' : 'No';

// ══════════════════════════════════════════════════════════════════════════
// 7. CONTACT, OCCUPATION TYPE, OCCUPATION & FINANCIAL
// ══════════════════════════════════════════════════════════════════════════
$contact_no = $conn->real_escape_string(trim($_POST['contact_no'] ?? ''));
$email = !empty(trim($_POST['email'] ?? '')) ? $conn->real_escape_string(trim($_POST['email'])) : null;

$occupation_type = resolveField('occupation_type', 'occupation_type_other', $conn);
if (empty($occupation_type)) {
    formError('Please select an Occupation Type.');
}

$occupation = null;
if (!empty(trim($_POST['occupation'] ?? ''))) {
    $occ = trim($_POST['occupation']);
    if (strtolower($occ) !== 'n/a' && $occ !== '0') {
        $occupation = $conn->real_escape_string($occ);
    }
}

// Annual income and SES always computed server-side — never trust client value
$monthly_income = (isset($_POST['monthly_income']) && $_POST['monthly_income'] !== '')
    ? floatval($_POST['monthly_income']) : null;
$annual_income = ($monthly_income !== null) ? round($monthly_income * 12, 2) : null;
$socioeconomic_status = ($monthly_income !== null) ? classifySES($monthly_income) : null;

$household_position = null;
if (!empty(trim($_POST['household_position'] ?? ''))) {
    $household_position = $conn->real_escape_string(trim($_POST['household_position']));
}

// ══════════════════════════════════════════════════════════════════════════
// 8. HOUSING
// ══════════════════════════════════════════════════════════════════════════
$house_ownership = resolveField('house_ownership', 'house_ownership_other', $conn);
$house_material = resolveField('house_material', 'house_material_other', $conn);
$toilet_type = resolveField('toilet_type', 'toilet_type_other', $conn);
$water_source = resolveField('water_source', 'water_source_other', $conn);

// ══════════════════════════════════════════════════════════════════════════
// 9. SOCIAL FLAGS
// ══════════════════════════════════════════════════════════════════════════
$is_4ps = (isset($_POST['is_4ps']) && $_POST['is_4ps'] === 'Yes') ? 'Yes' : 'No';
$is_nhts = (isset($_POST['is_nhts']) && $_POST['is_nhts'] === 'Yes') ? 'Yes' : 'No';
$is_solo_parent = (isset($_POST['is_solo_parent']) && $_POST['is_solo_parent'] === 'Yes') ? 'Yes' : 'No';
$is_smoker = (isset($_POST['is_smoker']) && $_POST['is_smoker'] === 'Yes') ? 'Yes' : 'No';
$is_binge_drinker = (isset($_POST['is_binge_drinker']) && $_POST['is_binge_drinker'] === 'Yes') ? 'Yes' : 'No';

// ══════════════════════════════════════════════════════════════════════════
// 10. HEALTH CONDITIONS
// ══════════════════════════════════════════════════════════════════════════
$has_hypertension = (isset($_POST['has_hypertension']) && $_POST['has_hypertension'] === 'Yes') ? 'Yes' : 'No';
$has_diabetes = (isset($_POST['has_diabetes']) && $_POST['has_diabetes'] === 'Yes') ? 'Yes' : 'No';
$has_asthma = (isset($_POST['has_asthma']) && $_POST['has_asthma'] === 'Yes') ? 'Yes' : 'No';
$has_tb = (isset($_POST['has_tb']) && $_POST['has_tb'] === 'Yes') ? 'Yes' : 'No';
$has_cancer = (isset($_POST['has_cancer']) && $_POST['has_cancer'] === 'Yes') ? 'Yes' : 'No';
$has_mental_health = (isset($_POST['has_mental_health']) && $_POST['has_mental_health'] === 'Yes') ? 'Yes' : 'No';

// ══════════════════════════════════════════════════════════════════════════
// 11. PHILHEALTH MEMBERSHIP
// ══════════════════════════════════════════════════════════════════════════
$membership_type = !empty(trim($_POST['membership_type'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['membership_type'])) : null;
$family_planning = (isset($_POST['family_planning']) && $_POST['family_planning'] === 'Yes') ? 'Yes' : 'No';

// ══════════════════════════════════════════════════════════════════════════
// 12. IMAGE UPLOAD
// ══════════════════════════════════════════════════════════════════════════
$uploadDir = '../officials/uploads/residents/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
// Gender-based default photo
if ($sex === 'LGBTQ+') {
    $image_path = 'default_photo_lgbtq.jpg';
} elseif ($sex === 'Female') {
    $image_path = 'default_photo_female.jpg';
} else {
    $image_path = 'default_photo_male.jpg';
}
$cameraImage = trim($_POST['camera_image'] ?? '');

if (!empty($cameraImage)) {
    // Camera capture (base64 data URI)
    if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,(.+)$/i', $cameraImage, $matches)) {
        formError('Invalid camera image format.');
    }
    $imgExt = strtolower($matches[1]) === 'jpg' ? 'jpeg' : strtolower($matches[1]);
    $imgData = base64_decode($matches[2]);
    if ($imgData === false) {
        formError('Invalid camera image data.');
    }
    if (strlen($imgData) > 2 * 1024 * 1024) {
        formError('Camera photo must be under 2 MB.');
    }
    $image_path = time() . '_' . uniqid() . '.' . $imgExt;
    if (!file_put_contents($uploadDir . $image_path, $imgData)) {
        formError('Failed to save camera photo. Check folder permissions.');
    }

} elseif (!empty($_FILES['image_path']['name'])) {
    // Standard file upload
    $allowed_mime = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($_FILES['image_path']['type'], $allowed_mime)) {
        formError('Invalid image type. Please upload JPEG, PNG, GIF, or WebP.');
    }
    if ($_FILES['image_path']['size'] > 2 * 1024 * 1024) {
        formError('Image must be under 2 MB.');
    }
    $ext = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
    $image_path = time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES['image_path']['tmp_name'], $uploadDir . $image_path)) {
        formError('Failed to upload image. Check folder permissions.');
    }

} else {
    formError('Please upload or capture a profile photo.');
}

// ══════════════════════════════════════════════════════════════════════════
// 13. INSERT INTO pending_registrations
// ══════════════════════════════════════════════════════════════════════════
$sql = "
    INSERT INTO pending_registrations (
        username, password,
        first_name, middle_name, surname, suffix,
        birthdate, birthplace, age, sex, lgbtq_identity, lgbtq_other_text, civil_status, nationality,
        religion, ethnicity, blood_type, philhealth_no, length_of_residency,
        household_no, purok, barangay, municipality, province,
        voters_status, educational_attainment, total_household, grade_level, school_name,
        course, course_other, graduation_date, eligibility, eligibility_other,
        is_pwd, pwd_type, is_deceased, date_of_death, is_newborn,
        contact_no, email, occupation_type, occupation,
        monthly_income, annual_income, socioeconomic_status, household_position,
        house_ownership, house_material, toilet_type, water_source,
        is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker,
        has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health,
        membership_type, family_planning,
        image_path, status, created_at
    ) VALUES (
        ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?,
        ?, 'Pending', NOW()
    )";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('PREPARE ERROR: ' . $conn->error);
    formError('Database error. Please contact the administrator.');
}

// ── TYPE STRING — exactly 64 characters ──────────────────────────────────────
$types = 'ss'        //  2: username, password
    . 'ssss'      //  4: first_name, middle_name, surname, suffix
    . 'ssisss'    //  6: birthdate, birthplace, age(i), sex, lgbtq_identity, lgbtq_other_text
    . 'ss'        //  2: civil_status, nationality
    . 'ssssi'     //  5: religion, ethnicity, blood_type, philhealth_no, length_of_residency(i)
    . 'sssss'     //  5: household_no, purok, barangay, municipality, province
    . 'ssiss'     //  5: voters_status, educational_attainment, total_household(i), grade_level, school_name
    . 'sssss'     //  5: course, course_other, graduation_date, eligibility, eligibility_other
    . 'sssss'     //  5: is_pwd, pwd_type, is_deceased, date_of_death, is_newborn
    . 'ssss'       //  4: contact_no, email, occupation_type, occupation
    . 'ddss'      //  4: monthly_income(d), annual_income(d), socioeconomic_status, household_position
    . 'ssss'      //  4: house_ownership, house_material, toilet_type, water_source
    . 'sssss'     //  5: is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker
    . 'ssssss'    //  6: health flags
    . 'ss'        //  2: membership_type, family_planning
    . 's';        //  1: image_path
// Total: 2+4+6+2+5+5+5+5+5+3+4+4+5+6+2+1 = 64

$stmt->bind_param(
    $types,
    // 1-2 : credentials
    $username,
    $hashed_password,
    // 3-6 : name
    $first_name,
    $middle_name,
    $surname,
    $suffix,
    // 7-14 : personal + LGBTQ+
    $birthdate,
    $birthplace,
    $age,
    $sex,
    $lgbtq_identity,
    $lgbtq_other_text,
    $civil_status,
    $nationality,
    // 15-19 : demographic
    $religion,
    $ethnicity,
    $blood_type,
    $philhealth_no,
    $length_of_residency,
    // 20-24 : address
    $household_no,
    $purok,
    $barangay,
    $municipality,
    $province,
    // 25-29 : voter / education
    $voters_status,
    $educational_attainment,
    $total_household,
    $grade_level,
    $school_name,
    // 30-34 : graduate education fields
    $course,
    $course_other,
    $graduation_date,
    $eligibility,
    $eligibility_other,
    // 35-39 : status flags
    $is_pwd,
    $pwd_type,
    $is_deceased,
    $date_of_death,
    $is_newborn,
    // 40-42 : contact / occupation
    $contact_no,
    $email,
    $occupation_type,
    $occupation,
    // 43-46 : financial
    $monthly_income,
    $annual_income,
    $socioeconomic_status,
    $household_position,
    // 47-50 : housing
    $house_ownership,
    $house_material,
    $toilet_type,
    $water_source,
    // 51-55 : social flags
    $is_4ps,
    $is_nhts,
    $is_solo_parent,
    $is_smoker,
    $is_binge_drinker,
    // 56-61 : health
    $has_hypertension,
    $has_diabetes,
    $has_asthma,
    $has_tb,
    $has_cancer,
    $has_mental_health,
    // 62-63 : philhealth / family
    $membership_type,
    $family_planning,
    // 64 : photo
    $image_path
);

// ══════════════════════════════════════════════════════════════════════════
// 14. EXECUTE & REDIRECT
// ══════════════════════════════════════════════════════════════════════════
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    // Signal login.php to show the "Registration submitted" success banner.
    // login.php should check: if (isset($_GET['registered'])) show success alert.
    $_SESSION['reg_success'] = true;

    header('Location: ../officials/login.php?registered=1');
    exit();

} else {
    $err = $stmt->error;
    error_log('EXECUTE ERROR: ' . $err);
    $stmt->close();
    $conn->close();
    formError('Registration failed. Please try again or contact the administrator.');
}