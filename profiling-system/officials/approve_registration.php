<?php
/**
 * approve_registration.php  (officials/ folder)
 * ─────────────────────────────────────────────────────────────────────────
 * Handles approve / reject / undo POST actions.
 *
 * APPROVE:  Copies the full row from pending_registrations → residents,
 *           then marks pending record as 'Approved'. Resident can now login.
 *
 * REJECT:   Updates pending record status to 'Rejected' + stores reason.
 *           Record stays in pending_registrations for audit trail.
 *
 * UNDO:     Reverts status back to 'Pending' (clears reason & review info).
 *           If it was Approved, also deletes the copied row from residents.
 *
 * FIELDS SYNCED (matches register_account.php / pending_registrations table):
 *   username, password,
 *   first_name, middle_name, surname, suffix,
 *   birthdate, birthplace, age, sex, civil_status, nationality,
 *   religion, ethnicity, blood_type, philhealth_no, length_of_residency,
 *   household_no, purok, barangay, municipality, province,
 *   voters_status, educational_attainment, total_household, grade_level, school_name,
 *   course, course_other, graduation_date, eligibility, eligibility_other,
 *   is_pwd, pwd_type, is_deceased, date_of_death, is_newborn,
 *   contact_no, occupation_type, occupation,
 *   monthly_income, annual_income, socioeconomic_status, household_position,
 *   house_ownership, house_material, toilet_type, water_source,
 *   is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker,
 *   has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health,
 *   membership_type, family_planning,
 *   image_path, created_at
 */
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php"); exit();
}
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'staff' && $_SESSION['user_type'] !== 'official') {
    header("Location: ../resident/dashboard.php"); exit();
}

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pending_registrations.php"); exit();
}

$action = trim($_POST['action'] ?? '');
$id     = intval($_POST['id']   ?? 0);
$tab    = in_array($_POST['tab'] ?? '', ['pending','approved','rejected']) ? $_POST['tab'] : 'pending';
$q      = urlencode($_POST['q'] ?? '');

$reviewer = $conn->real_escape_string(
    $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin'
);

if (!in_array($action, ['approve','reject','undo']) || $id <= 0) {
    header("Location: pending_registrations.php?tab=$tab&q=$q&error=invalid"); exit();
}

// ── Fetch the pending registration ───────────────────────────────────────
$fetch = $conn->prepare("SELECT * FROM pending_registrations WHERE id = ? LIMIT 1");
$fetch->bind_param("i", $id);
$fetch->execute();
$pr = $fetch->get_result()->fetch_assoc();
$fetch->close();

if (!$pr) {
    header("Location: pending_registrations.php?tab=$tab&q=$q&error=notfound"); exit();
}

// ── Helper: auto-classify SES from monthly income (PSA FIES 2021) ────────
function classifySES(?float $monthly): ?string {
    if ($monthly === null || $monthly < 0) return null;
    if ($monthly < 10957)  return 'Poor';
    if ($monthly < 21914)  return 'Low Income';
    if ($monthly < 43828)  return 'Lower Middle Income';
    if ($monthly < 76669)  return 'Middle Income';
    if ($monthly < 131484) return 'Upper Middle Income';
    return 'High Income';
}

// ── Ensure annual_income and socioeconomic_status are always consistent ───
// Even if the pending record was saved before this logic existed, we
// re-derive both values server-side on approval so residents.* is always clean.
$monthly_income = ($pr['monthly_income'] !== null && $pr['monthly_income'] !== '')
    ? (float)$pr['monthly_income'] : null;
$annual_income  = ($monthly_income !== null) ? round($monthly_income * 12, 2) : null;
$ses            = (!empty($pr['socioeconomic_status']))
    ? $pr['socioeconomic_status']
    : classifySES($monthly_income);

$success_key = '';

switch ($action) {

    // ── APPROVE ───────────────────────────────────────────────────────────
    case 'approve':

        if ($pr['status'] === 'Approved') {
            header("Location: pending_registrations.php?tab=approved&q=$q&success=already"); exit();
        }

        // Check username not already taken in residents (race condition guard)
        $chk = $conn->prepare("SELECT id FROM residents WHERE username = ? LIMIT 1");
        $chk->bind_param("s", $pr['username']);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            header("Location: pending_registrations.php?tab=pending&q=$q&error=duplicate"); exit();
        }
        $chk->close();

        // ── Copy full row into residents table ────────────────────────────
        // Column order matches the INSERT exactly — update this list if you
        // ever add columns to residents in the future.
        $ins = $conn->prepare("
            INSERT INTO residents (
                username, password,
                first_name, middle_name, surname, suffix,
                birthdate, birthplace, age, sex, civil_status, nationality,
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
                image_path, created_at
            ) VALUES (
                ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
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
                ?, ?
            )
        ");

        // TYPE STRING — 63 parameters
        $types =
            'ss'        //  2 : username, password
          . 'ssss'      //  4 : first_name, middle_name, surname, suffix
          . 'ssisss'    //  6 : birthdate, birthplace, age(i), sex, civil_status, nationality
          . 'ssssi'     //  5 : religion, ethnicity, blood_type, philhealth_no, length_of_residency(i)
          . 'sssss'     //  5 : household_no, purok, barangay, municipality, province
          . 'ssiss'     //  5 : voters_status, educational_attainment, total_household(i), grade_level, school_name
          . 'sssss'     //  5 : course, course_other, graduation_date, eligibility, eligibility_other
          . 'sssss'     //  5 : is_pwd, pwd_type, is_deceased, date_of_death, is_newborn
          . 'ssss'       //  4 : contact_no, email, occupation_type, occupation
          . 'ddss'      //  4 : monthly_income(d), annual_income(d), socioeconomic_status, household_position
          . 'ssss'      //  4 : house_ownership, house_material, toilet_type, water_source
          . 'sssss'     //  5 : is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker
          . 'ssssss'    //  6 : health flags
          . 'ss'        //  2 : membership_type, family_planning
          . 'ss'        //  2 : image_path, created_at
        ;
        // Total: 2+4+6+5+5+5+5+5+3+4+4+5+6+2+2 = 63

        $ins->bind_param(
            $types,
            // 1-2: credentials
            $pr['username'],    $pr['password'],
            // 3-6: name
            $pr['first_name'],  $pr['middle_name'], $pr['surname'],   $pr['suffix'],
            // 7-12: personal
            $pr['birthdate'],   $pr['birthplace'],  $pr['age'],
            $pr['sex'],         $pr['civil_status'], $pr['nationality'],
            // 13-17: demographic
            $pr['religion'],    $pr['ethnicity'],   $pr['blood_type'],
            $pr['philhealth_no'], $pr['length_of_residency'],
            // 18-22: address
            $pr['household_no'], $pr['purok'],      $pr['barangay'],
            $pr['municipality'], $pr['province'],
            // 23-27: voter / education
            $pr['voters_status'], $pr['educational_attainment'], $pr['total_household'],
            $pr['grade_level'],   $pr['school_name'],
            // 28-32: graduate education
            $pr['course'],      $pr['course_other'], $pr['graduation_date'],
            $pr['eligibility'], $pr['eligibility_other'],
            // 33-37: status flags
            $pr['is_pwd'],      $pr['pwd_type'],    $pr['is_deceased'],
            $pr['date_of_death'], $pr['is_newborn'],
            // 38-40: contact / occupation
            $pr['contact_no'],  $pr['email'], $pr['occupation_type'], $pr['occupation'],
            // 41-44: financial (re-derived server-side)
            $monthly_income,    $annual_income,     $ses,
            $pr['household_position'],
            // 45-48: housing
            $pr['house_ownership'], $pr['house_material'],
            $pr['toilet_type'],     $pr['water_source'],
            // 49-53: social flags
            $pr['is_4ps'],      $pr['is_nhts'],     $pr['is_solo_parent'],
            $pr['is_smoker'],   $pr['is_binge_drinker'],
            // 54-59: health
            $pr['has_hypertension'], $pr['has_diabetes'], $pr['has_asthma'],
            $pr['has_tb'],           $pr['has_cancer'],   $pr['has_mental_health'],
            // 60-61: philhealth / family
            $pr['membership_type'], $pr['family_planning'],
            // 62-63: photo + audit
            $pr['image_path'],  $pr['created_at']
        );

        if (!$ins->execute()) {
            $err = $ins->error;
            $ins->close();
            header("Location: pending_registrations.php?tab=pending&q=$q&error=".urlencode($err)); exit();
        }
        $ins->close();

        // Mark pending record as Approved (keep for audit)
        $upd = $conn->prepare("
            UPDATE pending_registrations
            SET status='Approved', rejection_reason=NULL,
                reviewed_by=?, reviewed_at=NOW()
            WHERE id=?
        ");
        $upd->bind_param("si", $reviewer, $id);
        $upd->execute();
        $upd->close();

        // Log the action
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, username, full_name, action, details, ip_address, login_at, status) VALUES (?, ?, ?, ?, 'approve_registration', ?, ?, NOW(), 'offline')");
        $log_utype = $_SESSION['user_type'] ?? 'admin';
        $log_uname = $_SESSION['username'] ?? 'Admin';
        $log_fname = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
        $log_details = 'Approved registration for ' . $pr['first_name'] . ' ' . $pr['surname'] . ' (username: ' . $pr['username'] . ')';
        $log_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $log_stmt->bind_param('isssss', $_SESSION['user_id'], $log_utype, $log_uname, $log_fname, $log_details, $log_ip);
        $log_stmt->execute();
        $log_stmt->close();

        $success_key = 'approved';
        $tab = 'approved';
        break;

    // ── REJECT ────────────────────────────────────────────────────────────
    case 'reject':
        $reason = trim($_POST['rejection_reason'] ?? '');
        if (empty($reason)) {
            header("Location: pending_registrations.php?tab=pending&q=$q&error=noreason"); exit();
        }

        $upd = $conn->prepare("
            UPDATE pending_registrations
            SET status='Rejected', rejection_reason=?,
                reviewed_by=?, reviewed_at=NOW()
            WHERE id=?
        ");
        $upd->bind_param("ssi", $reason, $reviewer, $id);
        $upd->execute();
        $upd->close();

        // Log the action
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, username, full_name, action, details, ip_address, login_at, status) VALUES (?, ?, ?, ?, 'reject_registration', ?, ?, NOW(), 'offline')");
        $log_utype = $_SESSION['user_type'] ?? 'admin';
        $log_uname = $_SESSION['username'] ?? 'Admin';
        $log_fname = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
        $log_details = 'Rejected registration for ' . $pr['first_name'] . ' ' . $pr['surname'] . ' (username: ' . $pr['username'] . '). Reason: ' . $reason;
        $log_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $log_stmt->bind_param('isssss', $_SESSION['user_id'], $log_utype, $log_uname, $log_fname, $log_details, $log_ip);
        $log_stmt->execute();
        $log_stmt->close();

        $success_key = 'rejected';
        $tab = 'rejected';
        break;

    // ── UNDO ──────────────────────────────────────────────────────────────
    case 'undo':
        // If it was Approved: also remove the copied row from residents
        if ($pr['status'] === 'Approved') {
            $del = $conn->prepare("DELETE FROM residents WHERE username = ? LIMIT 1");
            $del->bind_param("s", $pr['username']);
            $del->execute();
            $del->close();
        }

        $upd = $conn->prepare("
            UPDATE pending_registrations
            SET status='Pending', rejection_reason=NULL,
                reviewed_by=NULL, reviewed_at=NULL
            WHERE id=?
        ");
        $upd->bind_param("i", $id);
        $upd->execute();
        $upd->close();

        // Log the action
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, username, full_name, action, details, ip_address, login_at, status) VALUES (?, ?, ?, ?, 'undo_registration', ?, ?, NOW(), 'offline')");
        $log_utype = $_SESSION['user_type'] ?? 'admin';
        $log_uname = $_SESSION['username'] ?? 'Admin';
        $log_fname = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
        $log_details = 'Undid registration action for ' . $pr['first_name'] . ' ' . $pr['surname'] . ' (username: ' . $pr['username'] . ')';
        $log_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $log_stmt->bind_param('isssss', $_SESSION['user_id'], $log_utype, $log_uname, $log_fname, $log_details, $log_ip);
        $log_stmt->execute();
        $log_stmt->close();

        $success_key = 'undone';
        $tab = 'pending';
        break;
}

$conn->close();
header("Location: pending_registrations.php?tab=$tab&q=$q&success=$success_key");
exit();
?>