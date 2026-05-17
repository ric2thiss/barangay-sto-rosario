<?php
/**
 * update_official.php  (officials/ folder — admin side)
 * Updates an existing Barangay Official record.
 *
 * SYNC with add_residents.php (v2):
 *   + suffix
 *   + occupation_type  (with Other support) — required
 *   + pwd_type         (structured card-picker, replaces free-text pwd_details)
 *   + socioeconomic_status (auto-computed PSA brackets)
 *   + annual_income    (auto-computed as monthly × 12)
 *   + barangay validated against allowed values
 *   Username & password NOT updated here (managed separately)
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

// ── RBAC: Block users without edit privilege ──────────────────────────
$is_superadmin = ($_SESSION['user_type'] === 'admin') || (!empty($_SESSION['is_superadmin']));
if (!$is_superadmin && empty($_SESSION['can_edit_resident'])) {
    $_SESSION['error'] = 'You do not have permission to edit records.';
    header("Location: barangay_officials.php"); exit();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'update_official_errors.log');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit(); }

// ── Helper: resolve "Other – specify" dropdowns ───────────────────────────
function offResolveField(string $key, string $other_key, $conn): ?string {
    $val = trim($_POST[$key] ?? '');
    if ($val === 'Other') {
        $specify = trim($_POST[$other_key] ?? '');
        $val = $specify !== '' ? $specify : 'Other';
    }
    return $val !== '' ? $conn->real_escape_string($val) : null;
}

// ── Helper: PSA socioeconomic classification ──────────────────────────────
function classifySES(?float $monthly): ?string {
    if ($monthly === null || $monthly < 0) return null;
    if ($monthly < 10957)   return 'Poor';
    if ($monthly < 21914)   return 'Low Income';
    if ($monthly < 43828)   return 'Lower Middle Income';
    if ($monthly < 76669)   return 'Middle Income';
    if ($monthly < 131484)  return 'Upper Middle Income';
    return 'High Income';
}

// ── 1. ID ─────────────────────────────────────────────────────────────────
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid official ID.";
    header("Location: barangay_officials.php"); exit();
}

$chk = $conn->prepare("SELECT id FROM barangay_official WHERE id = ?");
$chk->bind_param("i", $id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows === 0) {
    $_SESSION['error'] = "Official record not found.";
    $chk->close(); header("Location: barangay_officials.php"); exit();
}
$chk->close();

// ── 2. PERSONAL ───────────────────────────────────────────────────────────
$first_name  = $conn->real_escape_string(trim($_POST['first_name']  ?? ''));
$middle_name = $conn->real_escape_string(trim($_POST['middle_name'] ?? ''));
$surname     = $conn->real_escape_string(trim($_POST['surname']     ?? ''));
$suffix      = !empty(trim($_POST['suffix'] ?? ''))
               ? $conn->real_escape_string(trim($_POST['suffix'])) : null;

if (empty($first_name) || empty($surname)) {
    $_SESSION['error'] = "First name and surname are required.";
    header("Location: barangay_officials.php"); exit();
}

// Birthdate
$birthdate = '';
if (!empty($_POST['birthdate'])) {
    $bd = trim($_POST['birthdate']);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd)) {
        [$y, $m, $d] = explode('-', $bd);
        if (checkdate((int)$m, (int)$d, (int)$y) && (int)$y >= 1900 && (int)$y <= (int)date('Y'))
            $birthdate = $bd;
        else { $_SESSION['error'] = "Invalid birth year: $y."; header("Location: barangay_officials.php"); exit(); }
    } else { $_SESSION['error'] = "Birthdate must be YYYY-MM-DD."; header("Location: barangay_officials.php"); exit(); }
} else { $_SESSION['error'] = "Birthdate is required."; header("Location: barangay_officials.php"); exit(); }

$birthplace   = $conn->real_escape_string(trim($_POST['birthplace']  ?? ''));
$age          = intval($_POST['age'] ?? 0);
$sex          = $conn->real_escape_string($_POST['sex']          ?? '');
$civil_status = $conn->real_escape_string($_POST['civil_status'] ?? '');
$nationality  = $conn->real_escape_string(trim($_POST['nationality'] ?? 'Filipino'));
$contact_no   = $conn->real_escape_string(trim($_POST['contact_no']  ?? ''));
$email        = !empty(trim($_POST['email'] ?? '')) ? $conn->real_escape_string(trim($_POST['email'])) : null;

// ── 3. POSITION & TERM ────────────────────────────────────────────────────
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

// ── 4. ADDRESS ────────────────────────────────────────────────────────────
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

// ── 5. OCCUPATION TYPE & OCCUPATION ──────────────────────────────────────
$occupation_type = offResolveField('occupation_type', 'occupation_type_other', $conn);
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

// ── 6. HOUSEHOLD & EDUCATION ──────────────────────────────────────────────
$hp = trim($_POST['household_position'] ?? '');
if (empty($hp)) { $_SESSION['error'] = "Household position is required."; header("Location: barangay_officials.php"); exit(); }
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

// ── 7. DEMOGRAPHIC ────────────────────────────────────────────────────────
$blood_type          = offResolveField('blood_type', 'blood_type_other', $conn);
$religion            = offResolveField('religion',   'religion_other',   $conn);
$ethnicity           = offResolveField('ethnicity',  'ethnicity_other',  $conn);
$philhealth_no       = !empty(trim($_POST['philhealth_no'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['philhealth_no'])) : null;
$membership_type     = !empty(trim($_POST['membership_type'] ?? ''))
    ? $conn->real_escape_string(trim($_POST['membership_type'])) : null;
$height = (isset($_POST['height']) && $_POST['height'] !== '') ? floatval($_POST['height']) : null;
$weight = (isset($_POST['weight']) && $_POST['weight'] !== '') ? floatval($_POST['weight']) : null;
$length_of_residency = (isset($_POST['length_of_residency']) && $_POST['length_of_residency'] !== '')
    ? intval($_POST['length_of_residency']) : null;

// ── 8. HOUSING ────────────────────────────────────────────────────────────
$house_ownership = offResolveField('house_ownership', 'house_ownership_other', $conn);
$house_material  = offResolveField('house_material',  'house_material_other',  $conn);
$toilet_type     = offResolveField('toilet_type',     'toilet_type_other',     $conn);
$water_source    = offResolveField('water_source',    'water_source_other',    $conn);

// ── 9. SOCIAL FLAGS ───────────────────────────────────────────────────────
$is_4ps          = (($_POST['is_4ps']          ?? 'No') === 'Yes') ? 'Yes' : 'No';
$is_nhts         = (($_POST['is_nhts']         ?? 'No') === 'Yes') ? 'Yes' : 'No';
$is_solo_parent  = (($_POST['is_solo_parent']  ?? 'No') === 'Yes') ? 'Yes' : 'No';
$family_planning = (($_POST['family_planning'] ?? 'No') === 'Yes') ? 'Yes' : 'No';

// ── 10. SPECIAL STATUS ────────────────────────────────────────────────────
$is_pwd   = (($_POST['is_pwd'] ?? 'No') === 'Yes') ? 'Yes' : 'No';
$pwd_type = null;
if ($is_pwd === 'Yes') {
    $pt = trim($_POST['pwd_type'] ?? '');
    if (empty($pt)) $pt = trim($_POST['pwd_details'] ?? '');
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

// ── 11. HEALTH CONDITIONS ─────────────────────────────────────────────────
$is_smoker         = (($_POST['is_smoker']         ?? 'No') === 'Yes') ? 'Yes' : 'No';
$is_binge_drinker  = (($_POST['is_binge_drinker']  ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_hypertension  = (($_POST['has_hypertension']  ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_diabetes      = (($_POST['has_diabetes']      ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_asthma        = (($_POST['has_asthma']        ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_tb            = (($_POST['has_tb']            ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_cancer        = (($_POST['has_cancer']        ?? 'No') === 'Yes') ? 'Yes' : 'No';
$has_mental_health = (($_POST['has_mental_health'] ?? 'No') === 'Yes') ? 'Yes' : 'No';

// ── 12. IMAGE UPDATE ──────────────────────────────────────────────────────
$uploadDir = "uploads/officials/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

$imgRes  = $conn->query("SELECT image_path FROM barangay_official WHERE id = " . intval($id));
$oldImg  = $imgRes ? ($imgRes->fetch_assoc()['image_path'] ?? 'default.jpg') : 'default.jpg';
$newImg  = null;

if (!empty($_POST['camera_image'])) {
    $camData = $_POST['camera_image'];
    if (preg_match('/^data:image\/(\w+);base64,/', $camData, $m)) {
        $ext    = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        $binary = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $camData));
        if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
            $_SESSION['error'] = "Camera image invalid or exceeds 2MB.";
            header("Location: barangay_officials.php"); exit();
        }
        $newImg = time() . '_' . uniqid() . '_cam.' . $ext;
        if (file_put_contents($uploadDir . $newImg, $binary) === false) {
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
    $ext    = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $newImg = time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newImg)) {
        $_SESSION['error'] = "Failed to upload image.";
        header("Location: barangay_officials.php"); exit();
    }
}

if ($newImg !== null && $oldImg !== 'default.jpg' && file_exists($uploadDir . $oldImg)) {
    @unlink($uploadDir . $oldImg);
}

// ── 13. PREPARED UPDATE ───────────────────────────────────────────────────
$imgSql  = $newImg !== null ? ', image_path = ?' : '';
$imgType = $newImg !== null ? 's' : '';

$sql = "
    UPDATE barangay_official SET
        first_name            = ?,
        middle_name           = ?,
        surname               = ?,
        suffix                = ?,
        birthdate             = ?,
        birthplace            = ?,
        age                   = ?,
        sex                   = ?,
        civil_status          = ?,
        nationality           = ?,
        contact_no            = ?,
        email                 = ?,
        blood_type            = ?,
        religion              = ?,
        ethnicity             = ?,
        philhealth_no         = ?,
        membership_type       = ?,
        height                = ?,
        weight                = ?,
        length_of_residency   = ?,
        position              = ?,
        chairmanship          = ?,
        status                = ?,
        voters_status         = ?,
        term_start            = ?,
        term_end              = ?,
        years_in_service      = ?,
        household_no          = ?,
        purok                 = ?,
        barangay              = ?,
        municipality          = ?,
        province              = ?,
        occupation_type       = ?,
        occupation            = ?,
        monthly_income        = ?,
        annual_income         = ?,
        socioeconomic_status  = ?,
        household_position    = ?,
        total_household       = ?,
        father_name           = ?,
        father_occupation     = ?,
        mother_name           = ?,
        mother_occupation     = ?,
        educational_attainment= ?,
        grade_level           = ?,
        school_name           = ?,
        house_ownership       = ?,
        house_material        = ?,
        toilet_type           = ?,
        water_source          = ?,
        is_4ps                = ?,
        is_nhts               = ?,
        is_solo_parent        = ?,
        family_planning       = ?,
        is_pwd                = ?,
        pwd_type              = ?,
        is_deceased           = ?,
        date_of_death         = ?,
        is_smoker             = ?,
        is_binge_drinker      = ?,
        has_hypertension      = ?,
        has_diabetes          = ?,
        has_asthma            = ?,
        has_tb                = ?,
        has_cancer            = ?,
        has_mental_health     = ?
        $imgSql
    WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $_SESSION['error'] = "DB prepare error: " . $conn->error;
    error_log("UPDATE OFFICIAL PREPARE ERROR: " . $conn->error);
    header("Location: barangay_officials.php"); exit();
}

// TYPE STRING (59 fixed params + optional image + id)
// ssss = first_name, middle_name, surname, suffix
// ssiss s = birthdate, birthplace, age(i), sex, civil_status, nationality (6)
// sssss = contact_no, email, blood_type, religion, ethnicity (5)
// ssidd = philhealth_no, membership_type, length_of_residency(i), height(d), weight(d) (5)
// ssss = position, chairmanship, status, voters_status (4)
// ssi = term_start, term_end, years_in_service(i) (3)
// sssss = household_no, purok, barangay, municipality, province (5)
// ss = occupation_type, occupation (2)
// dds = monthly_income(d), annual_income(d), socioeconomic_status (3)
// sissss = household_position, total_household(i), father_name, father_occupation, mother_name, mother_occupation (6)
// sss = educational_attainment, grade_level, school_name (3)
// ssss = house_ownership, house_material, toilet_type, water_source (4)
// ssss = is_4ps, is_nhts, is_solo_parent, family_planning (4)
// ssss = is_pwd, pwd_type, is_deceased, date_of_death (4)
// ss = is_smoker, is_binge_drinker (2)
// ssssss = has_hypertension..has_mental_health (6)
// [s] optional image
// i = id
// TOTAL fixed = 4+6+5+3+4+3+5+2+3+2+3+4+4+4+2+6 = 60

$types =
    'ssss'   .  // first_name, middle_name, surname, suffix
    'ssiss'  .  // birthdate, birthplace, age(i), sex, civil_status
    's'      .  // nationality
    'sssss'  .  // contact_no, email, blood_type, religion, ethnicity
    'ssddi'  .  // philhealth_no, membership_type, height(d), weight(d), length_of_residency(i)
    'ssss'   .  // position, chairmanship, status, voters_status
    'ssi'    .  // term_start, term_end, years_in_service(i)
    'sssss'  .  // household_no, purok, barangay, municipality, province
    'ss'     .  // occupation_type, occupation
    'dds'    .  // monthly_income(d), annual_income(d), socioeconomic_status
    'sissss' .  // household_position, total_household(i), father_name, father_occupation, mother_name, mother_occupation
    'sss'    .  // educational_attainment, grade_level, school_name
    'ssss'   .  // house_ownership, house_material, toilet_type, water_source
    'ssss'   .  // is_4ps, is_nhts, is_solo_parent, family_planning
    'ssss'   .  // is_pwd, pwd_type, is_deceased, date_of_death
    'ss'     .  // is_smoker, is_binge_drinker
    'ssssss' .  // has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health
    $imgType .  // optional image
    'i';        // id

$values = [
    $first_name, $middle_name, $surname, $suffix,
    $birthdate, $birthplace, $age, $sex, $civil_status, $nationality,
    $contact_no, $email, $blood_type, $religion, $ethnicity,
    $philhealth_no, $membership_type, $height, $weight, $length_of_residency,
    $position, $chairmanship, $status_val, $voters_status,
    $term_start, $term_end, $years_in_service,
    $household_no, $purok, $barangay, $municipality, $province,
    $occupation_type, $occupation,
    $monthly_income, $annual_income, $socioeconomic_status,
    $household_position, $total_household, $father_name, $father_occupation, $mother_name, $mother_occupation,
    $educational_attainment, $grade_level, $school_name,
    $house_ownership, $house_material, $toilet_type, $water_source,
    $is_4ps, $is_nhts, $is_solo_parent, $family_planning,
    $is_pwd, $pwd_type, $is_deceased, $date_of_death,
    $is_smoker, $is_binge_drinker,
    $has_hypertension, $has_diabetes, $has_asthma, $has_tb, $has_cancer, $has_mental_health,
];
if ($newImg !== null) { $values[] = $newImg; }
$values[] = $id;

$bindArr = array_merge([$types], $values);
$refs    = [];
foreach ($bindArr as $k => $v) { $refs[$k] = &$bindArr[$k]; }
call_user_func_array([$stmt, 'bind_param'], $refs);

try {
    if ($stmt->execute()) {
        $_SESSION['success'] = "Official updated successfully!";
    } else {
        $err = $stmt->error;
        error_log("UPDATE OFFICIAL EXECUTE ERROR: $err");
        $_SESSION['error'] = "Update failed: $err";
    }
} catch (mysqli_sql_exception $ex) {
    $msg = $ex->getMessage();
    error_log("UPDATE OFFICIAL EXCEPTION: $msg");
    if (strpos($msg, 'Duplicate entry') !== false && strpos($msg, 'uq_official_identity') !== false) {
        $_SESSION['error'] = "Update failed: A different official with the same name and birthdate already exists.";
    } else {
        $_SESSION['error'] = "Update failed: " . $msg;
    }
}

$stmt->close();
$conn->close();
header("Location: barangay_officials.php");
exit();