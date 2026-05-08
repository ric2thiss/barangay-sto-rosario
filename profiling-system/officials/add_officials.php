<?php
/**
 * add_officials.php  — FIXED v4
 * Matches barangay_official column order exactly.
 * FIX: PWD type now reads pwd_type (radio) first, then pwd_type_resolved
 *      (hidden field for "Other" free-text), matching the modal's submit logic.
 */
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php"); exit();
}
$allowed_types = ['admin', 'official'];
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    header("Location: index.php"); exit();
}

include("connection.php");
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'add_official_errors.log');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit(); }

function resolveField(string $key, string $other_key, $conn): ?string {
    $val = trim($_POST[$key] ?? '');
    if ($val === 'Other') {
        $specify = trim($_POST[$other_key] ?? '');
        $val = $specify !== '' ? $specify : 'Other';
    }
    return $val !== '' ? $conn->real_escape_string($val) : null;
}

function classifySES(?float $monthly): ?string {
    if ($monthly === null || $monthly < 0) return null;
    if ($monthly < 10957)  return 'Poor';
    if ($monthly < 21914)  return 'Low Income';
    if ($monthly < 43828)  return 'Lower Middle Income';
    if ($monthly < 76669)  return 'Middle Income';
    if ($monthly < 131484) return 'Upper Middle Income';
    return 'High Income';
}

// ── 1. USERNAME & PASSWORD ───────────────────────────────────────────────
$username = $conn->real_escape_string(trim($_POST['username'] ?? ''));
$password = trim($_POST['password']         ?? '');
$confirm  = trim($_POST['confirm_password'] ?? '');

if (strlen($username) < 4 || strlen($username) > 20) {
    $_SESSION['error'] = "Username must be 4–20 characters.";
    header("Location: barangay_officials.php"); exit();
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $_SESSION['error'] = "Username: letters, numbers, underscore only.";
    header("Location: barangay_officials.php"); exit();
}
$chk = $conn->prepare("SELECT id FROM barangay_official WHERE username = ?");
$chk->bind_param("s", $username);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $_SESSION['error'] = "Username '$username' is already taken.";
    $chk->close(); header("Location: barangay_officials.php"); exit();
}
$chk->close();

if (strlen($password) < 6) {
    $_SESSION['error'] = "Password must be at least 6 characters.";
    header("Location: barangay_officials.php"); exit();
}
if ($password !== $confirm) {
    $_SESSION['error'] = "Passwords do not match!";
    header("Location: barangay_officials.php"); exit();
}
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ── 2. PERSONAL ──────────────────────────────────────────────────────────
$first_name  = $conn->real_escape_string(trim($_POST['first_name']  ?? ''));
$middle_name = $conn->real_escape_string(trim($_POST['middle_name'] ?? ''));
$surname     = $conn->real_escape_string(trim($_POST['surname']     ?? ''));
$suffix      = !empty(trim($_POST['suffix'] ?? ''))
               ? $conn->real_escape_string(trim($_POST['suffix'])) : null;

if (empty($first_name) || empty($surname)) {
    $_SESSION['error'] = "First name and surname are required.";
    header("Location: barangay_officials.php"); exit();
}

$birthdate = '';
if (!empty($_POST['birthdate'])) {
    $bd = trim($_POST['birthdate']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd)) {
        [$y, $m, $d] = explode('-', $bd);
        if (checkdate((int)$m, (int)$d, (int)$y) && (int)$y >= 1900 && (int)$y <= (int)date('Y'))
            $birthdate = $bd;
        else { $_SESSION['error'] = "Invalid birth year: $y."; header("Location: barangay_officials.php"); exit(); }
    } else { $_SESSION['error'] = "Invalid birthdate format (YYYY-MM-DD)."; header("Location: barangay_officials.php"); exit(); }
} else { $_SESSION['error'] = "Birthdate is required."; header("Location: barangay_officials.php"); exit(); }

// ── DUPLICATE PERSON CHECK (first_name + surname + birthdate) ────────────
// Uses separate queries per table to avoid collation mismatch on UNION.
$dupFound = false;
$dupSource = '';
$dupUsername = '';

// Check barangay_official table
$chkOff = $conn->prepare("SELECT id, username FROM barangay_official WHERE first_name = ? AND surname = ? AND birthdate = ? LIMIT 1");
$chkOff->bind_param("sss", $first_name, $surname, $birthdate);
$chkOff->execute();
$chkOff->store_result();
if ($chkOff->num_rows > 0) {
    $chkOff->bind_result($dupId, $dupUsername);
    $chkOff->fetch();
    $dupFound = true;
    $dupSource = 'Barangay Official';
}
$chkOff->close();

// Check residents table
if (!$dupFound) {
    $chkRes = $conn->prepare("SELECT id, username FROM residents WHERE first_name = ? AND surname = ? AND birthdate = ? LIMIT 1");
    $chkRes->bind_param("sss", $first_name, $surname, $birthdate);
    $chkRes->execute();
    $chkRes->store_result();
    if ($chkRes->num_rows > 0) {
        $chkRes->bind_result($dupId, $dupUsername);
        $chkRes->fetch();
        $dupFound = true;
        $dupSource = 'Resident';
    }
    $chkRes->close();
}

// Check pending_registrations table
if (!$dupFound) {
    $chkPend = $conn->prepare("SELECT id, username FROM pending_registrations WHERE first_name = ? AND surname = ? AND birthdate = ? LIMIT 1");
    $chkPend->bind_param("sss", $first_name, $surname, $birthdate);
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

if ($dupFound) {
    $_SESSION['error'] = "A person named \"$first_name $surname\" with birthdate \"$birthdate\" already exists in the system ($dupSource record" . (!empty($dupUsername) ? ", Account: \"$dupUsername\"" : "") . "). Duplicate entry is not allowed.";
    header("Location: barangay_officials.php");
    exit();
}

$birthplace   = $conn->real_escape_string(trim($_POST['birthplace']  ?? ''));
$age          = intval($_POST['age'] ?? 0);
$sex          = $conn->real_escape_string($_POST['sex']          ?? '');
$civil_status = $conn->real_escape_string($_POST['civil_status'] ?? '');
$nationality  = $conn->real_escape_string(trim($_POST['nationality'] ?? 'Filipino'));
$contact_no   = $conn->real_escape_string(trim($_POST['contact_no']  ?? ''));
$email        = !empty(trim($_POST['email'] ?? '')) ? $conn->real_escape_string(trim($_POST['email'])) : null;

// ── 3. POSITION & TERM ───────────────────────────────────────────────────
$position      = $conn->real_escape_string(trim($_POST['position']      ?? ''));
$chairmanship  = $conn->real_escape_string(trim($_POST['chairmanship']  ?? ''));
$status_val    = $conn->real_escape_string(trim($_POST['status']        ?? 'Active'));
$voters_status = $conn->real_escape_string($_POST['voters_status']      ?? 'Yes');

if (empty($position)) {
    $_SESSION['error'] = "Position is required.";
    header("Location: barangay_officials.php"); exit();
}

$term_start = '';
if (!empty($_POST['term_start'])) {
    $ts = trim($_POST['term_start']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ts)) {
        [$ty,$tm,$td] = explode('-', $ts);
        if (checkdate((int)$tm,(int)$td,(int)$ty)) $term_start = $ts;
    }
}
if (empty($term_start)) { $_SESSION['error'] = "Term start date is required."; header("Location: barangay_officials.php"); exit(); }

$term_end = '';
if (!empty($_POST['term_end'])) {
    $te = trim($_POST['term_end']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $te)) {
        [$ty,$tm,$td] = explode('-', $te);
        if (checkdate((int)$tm,(int)$td,(int)$ty)) $term_end = $te;
    }
}
if (empty($term_end)) { $_SESSION['error'] = "Term end date is required."; header("Location: barangay_officials.php"); exit(); }
if ($term_end <= $term_start) { $_SESSION['error'] = "Term end must be after term start."; header("Location: barangay_officials.php"); exit(); }

$years_in_service = (isset($_POST['years_in_service']) && $_POST['years_in_service'] !== '')
    ? intval($_POST['years_in_service']) : null;

// ── 4. ADDRESS ───────────────────────────────────────────────────────────
$household_no = !empty(trim($_POST['household_no'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['household_no'])) : null;
$purok = $conn->real_escape_string(trim($_POST['purok'] ?? ''));

$allowed_barangays = ['Buhang','Caloc-an','Guiasan','Marcos','Poblacion','Santo Niño','Santo Rosario','Taod-oy'];
$barangay_raw = trim($_POST['barangay'] ?? '');
if (!in_array($barangay_raw, $allowed_barangays)) {
    $_SESSION['error'] = "Invalid barangay selection.";
    header("Location: barangay_officials.php"); exit();
}
$barangay     = $conn->real_escape_string($barangay_raw);
$municipality = $conn->real_escape_string(trim($_POST['municipality'] ?? ''));
$province     = $conn->real_escape_string(trim($_POST['province']     ?? ''));

if (empty($purok) || empty($barangay)) {
    $_SESSION['error'] = "Purok and Barangay are required.";
    header("Location: barangay_officials.php"); exit();
}

// ── 5. OCCUPATION & INCOME ───────────────────────────────────────────────
$occupation_type = resolveField('occupation_type', 'occupation_type_other', $conn);
if (empty($occupation_type)) {
    $_SESSION['error'] = "Please select an Occupation Type.";
    header("Location: barangay_officials.php"); exit();
}

$occupation = null;
if (!empty(trim($_POST['occupation'] ?? ''))) {
    $occ = trim($_POST['occupation']);
    if (strtolower($occ) !== 'n/a' && $occ !== '0')
        $occupation = $conn->real_escape_string($occ);
}

$monthly_income = (isset($_POST['monthly_income']) && $_POST['monthly_income'] !== '')
    ? floatval($_POST['monthly_income']) : null;
$annual_income  = ($monthly_income !== null) ? round($monthly_income * 12, 2) : null;
$socioeconomic_status = ($monthly_income !== null) ? classifySES($monthly_income) : null;

// ── 6. HOUSEHOLD & EDUCATION ─────────────────────────────────────────────
$hp = trim($_POST['household_position'] ?? '');
if ($hp === '' || $hp === '0') {
    $_SESSION['error'] = "Household position is required.";
    header("Location: barangay_officials.php"); exit();
}
$household_position = $conn->real_escape_string($hp);

$total_household        = intval($_POST['total_household'] ?? 1);

$father_name = !empty(trim($_POST['father_name'] ?? '')) ? $conn->real_escape_string(trim($_POST['father_name'])) : null;
$father_occupation = !empty(trim($_POST['father_occupation'] ?? '')) ? $conn->real_escape_string(trim($_POST['father_occupation'])) : null;
$mother_name = !empty(trim($_POST['mother_name'] ?? '')) ? $conn->real_escape_string(trim($_POST['mother_name'])) : null;
$mother_occupation = !empty(trim($_POST['mother_occupation'] ?? '')) ? $conn->real_escape_string(trim($_POST['mother_occupation'])) : null;

$educational_attainment = $conn->real_escape_string($_POST['educational_attainment'] ?? '');
$grade_level = !empty(trim($_POST['grade_level'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['grade_level'])) : null;
$school_name = !empty(trim($_POST['school_name'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['school_name'])) : null;

// ── 6b. GRADUATE EDUCATION FIELDS ────────────────────────────────────────
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

// ── 7. DEMOGRAPHIC ───────────────────────────────────────────────────────
$blood_type      = resolveField('blood_type',  'blood_type_other',  $conn);
$religion        = resolveField('religion',    'religion_other',    $conn);
$ethnicity       = resolveField('ethnicity',   'ethnicity_other',   $conn);
$philhealth_no   = !empty(trim($_POST['philhealth_no'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['philhealth_no'])) : null;
$membership_type = !empty(trim($_POST['membership_type'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['membership_type'])) : null;
$height = (isset($_POST['height']) && $_POST['height'] !== '') ? floatval($_POST['height']) : null;
$weight = (isset($_POST['weight']) && $_POST['weight'] !== '') ? floatval($_POST['weight']) : null;
$length_of_residency = (isset($_POST['length_of_residency']) && $_POST['length_of_residency'] !== '')
    ? intval($_POST['length_of_residency']) : null;

// ── 8. HOUSING ───────────────────────────────────────────────────────────
$house_ownership = resolveField('house_ownership', 'house_ownership_other', $conn);
$house_material  = resolveField('house_material',  'house_material_other',  $conn);
$toilet_type     = resolveField('toilet_type',     'toilet_type_other',     $conn);
$water_source    = resolveField('water_source',    'water_source_other',    $conn);

// ── 9. SOCIAL FLAGS ──────────────────────────────────────────────────────
$is_4ps          = (($_POST['is_4ps']          ?? 'No') === 'Yes') ? 'Yes' : 'No';
$is_nhts         = (($_POST['is_nhts']         ?? 'No') === 'Yes') ? 'Yes' : 'No';
$is_solo_parent  = (($_POST['is_solo_parent']  ?? 'No') === 'Yes') ? 'Yes' : 'No';
$family_planning = (($_POST['family_planning'] ?? 'No') === 'Yes') ? 'Yes' : 'No';

// ── 10. SPECIAL STATUS ───────────────────────────────────────────────────
$is_pwd   = (($_POST['is_pwd'] ?? 'No') === 'Yes') ? 'Yes' : 'No';
$pwd_type = null;

if ($is_pwd === 'Yes') {
    // FIX: Read radio value first; if "Other" or empty, fall back to the hidden resolved field
    $pt = trim($_POST['pwd_type'] ?? '');
    
    if ($pt === 'Other' || empty($pt)) {
        $resolved = trim($_POST['pwd_type_resolved'] ?? '');
        if (!empty($resolved)) {
            $pt = $resolved;
        }
    }
    
    // Legacy fallback: pwd_details field (older forms)
    if (empty($pt)) {
        $pt = trim($_POST['pwd_details'] ?? '');
    }
    
    if (empty($pt)) {
        $_SESSION['error'] = "Please specify the disability type for PWD.";
        header("Location: barangay_officials.php"); exit();
    }
    $pwd_type = $conn->real_escape_string($pt);
}

$is_deceased   = (($_POST['is_deceased'] ?? 'No') === 'Yes') ? 'Yes' : 'No';
$date_of_death = null;
if ($is_deceased === 'Yes' && !empty($_POST['date_of_death'])) {
    $dd = trim($_POST['date_of_death']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dd)) {
        [$dy,$dm,$dday] = explode('-', $dd);
        if (checkdate((int)$dm,(int)$dday,(int)$dy)) $date_of_death = $dd;
    }
}

// ── 11. HEALTH CONDITIONS ────────────────────────────────────────────────
$is_smoker         = (($_POST['is_smoker']         ?? 'No') === 'Yes') ? 'Yes' : 'No';
$is_binge_drinker  = (($_POST['is_binge_drinker']  ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_hypertension  = (($_POST['has_hypertension']  ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_diabetes      = (($_POST['has_diabetes']      ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_asthma        = (($_POST['has_asthma']        ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_tb            = (($_POST['has_tb']            ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_cancer        = (($_POST['has_cancer']        ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_mental_health = (($_POST['has_mental_health'] ?? 'No') === 'Yes') ? 'Yes' : 'No';

// ── 12. IMAGE ────────────────────────────────────────────────────────────
$uploadDir  = "uploads/officials/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

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
        $ext       = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $imgBinary = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $camData));
        if ($imgBinary === false || strlen($imgBinary) > 2 * 1024 * 1024) {
            $_SESSION['error'] = "Camera image is invalid or too large (max 2MB).";
            header("Location: barangay_officials.php"); exit();
        }
        $image_path = time() . '_' . uniqid() . '_cam.' . $ext;
        if (file_put_contents($uploadDir . $image_path, $imgBinary) === false) {
            $_SESSION['error'] = "Failed to save camera photo.";
            header("Location: barangay_officials.php"); exit();
        }
    }
} elseif (!empty($_FILES['image']['name'])) {
    $allowed = ['image/jpeg','image/jpg','image/png','image/gif'];
    if (!in_array($_FILES['image']['type'], $allowed)) {
        $_SESSION['error'] = "Invalid image type. Only JPG, PNG, GIF allowed.";
        header("Location: barangay_officials.php"); exit();
    }
    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = "Image must be under 2MB.";
        header("Location: barangay_officials.php"); exit();
    }
    $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_path = time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image_path)) {
        $_SESSION['error'] = "Failed to upload image.";
        header("Location: barangay_officials.php"); exit();
    }
}
// If no photo uploaded, the gender-based default is used

// ── 13. INSERT ───────────────────────────────────────────────────────────
$sql = "
    INSERT INTO barangay_official (
        username, password,
        first_name, middle_name, surname, suffix,
        birthdate, birthplace, age, sex, civil_status, nationality,
        contact_no, email, purok, barangay, municipality, province,
        position, chairmanship, occupation,
        monthly_income, annual_income,
        household_no, household_position, total_household, voters_status,
        educational_attainment, term_start, term_end, status,
        image_path,
        occupation_type, socioeconomic_status,
        pwd_type, is_pwd,
        blood_type, religion, ethnicity, philhealth_no, membership_type, height, weight,
        father_name, father_occupation, mother_name, mother_occupation,
        length_of_residency, years_in_service,
        grade_level, school_name,
        course, course_other, graduation_date, eligibility, eligibility_other,
        house_ownership, house_material, toilet_type, water_source,
        is_4ps, is_nhts, is_solo_parent, family_planning,
        is_deceased, date_of_death,
        is_smoker, is_binge_drinker,
        has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health,
        created_at
    ) VALUES (
        ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?,
        ?, ?,
        ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?,
        ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?,
        ?, ?,
        ?, ?, ?, ?, ?, ?,
        NOW()
    )";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $_SESSION['error'] = "Prepare failed: " . $conn->error;
    error_log("ADD OFFICIAL PREPARE ERROR: " . $conn->error);
    header("Location: barangay_officials.php"); exit();
}

$types =
    'ss'     .  // username, password
    'ssss'   .  // first_name, middle_name, surname, suffix
    'ssisss' .  // birthdate, birthplace, age(i), sex, civil_status, nationality
    'ssssss' .  // contact_no, email, purok, barangay, municipality, province
    'sss'    .  // position, chairmanship, occupation
    'dd'     .  // monthly_income, annual_income
    'ssis'   .  // household_no, household_position, total_household(i), voters_status
    'sss'    .  // educational_attainment, term_start, term_end
    's'      .  // status
    's'      .  // image_path
    'ss'     .  // occupation_type, socioeconomic_status
    'ss'     .  // pwd_type, is_pwd
    'sssssdd'.  // blood_type, religion, ethnicity, philhealth_no, membership_type, height(d), weight(d)
    'ssss'   .  // father_name, father_occupation, mother_name, mother_occupation
    'ii'     .  // length_of_residency, years_in_service
    'ss'     .  // grade_level, school_name
    'sssss'  .  // course, course_other, graduation_date, eligibility, eligibility_other
    'ssss'   .  // house_ownership, house_material, toilet_type, water_source
    'ssss'   .  // is_4ps, is_nhts, is_solo_parent, family_planning
    'ss'     .  // is_deceased, date_of_death
    'ss'     .  // is_smoker, is_binge_drinker
    'ssssss' ;  // has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health

$stmt->bind_param($types,
    $username, $hashed_password,
    $first_name, $middle_name, $surname, $suffix,
    $birthdate, $birthplace, $age, $sex, $civil_status, $nationality,
    $contact_no, $email, $purok, $barangay, $municipality, $province,
    $position, $chairmanship, $occupation,
    $monthly_income, $annual_income,
    $household_no, $household_position, $total_household, $voters_status,
    $educational_attainment, $term_start, $term_end, $status_val,
    $image_path,
    $occupation_type, $socioeconomic_status,
    $pwd_type, $is_pwd,
    $blood_type, $religion, $ethnicity, $philhealth_no, $membership_type, $height, $weight,
    $father_name, $father_occupation, $mother_name, $mother_occupation,
    $length_of_residency, $years_in_service,
    $grade_level, $school_name,
    $course, $course_other, $graduation_date, $eligibility, $eligibility_other,
    $house_ownership, $house_material, $toilet_type, $water_source,
    $is_4ps, $is_nhts, $is_solo_parent, $family_planning,
    $is_deceased, $date_of_death,
    $is_smoker, $is_binge_drinker,
    $has_hypertension, $has_diabetes, $has_asthma, $has_tb, $has_cancer, $has_mental_health
);

if ($stmt->execute()) {
    $new_id = $stmt->insert_id;
    $_SESSION['success'] = "Barangay Official added successfully! (ID: $new_id)";
} else {
    $_SESSION['error'] = "Insert failed: " . $stmt->error;
    error_log("ADD OFFICIAL EXECUTE ERROR: " . $stmt->error);
}

$stmt->close();
$conn->close();
header("Location: barangay_officials.php");
exit();
?>