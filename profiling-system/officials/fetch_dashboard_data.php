<?php
/**
 * fetch_dashboard_data.php — AJAX backend (admin only)
 *
 * FIXES & ENHANCEMENTS v3:
 *   1. Purok list now comes from BOTH residents AND barangay_official tables.
 *   2. PWD types expanded with Bisaya labels + Multiple/Chronic options.
 *   3. PWD type filter uses LIKE for partial/comma-separated matches.
 *   4. Collation normalised at connection level.
 *   5. $purok_and prevents double-WHERE SQL errors.
 *   6. Unified puroks array returned in JSON for dynamic dropdown population.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin','staff','official','resident'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
// Purok Presidents (residents with staff_position) are allowed
$is_purok_president = (($_SESSION['staff_position'] ?? '') === 'Purok President');
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
    session_destroy();
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit();
}
$_SESSION['login_time'] = time();

try {
    include("connection.php");

    $conn->set_charset('utf8mb4');
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->query("SET collation_connection = utf8mb4_general_ci");

    // ── Input validation ─────────────────────────────────────────────────
    $purok = trim($_GET['purok'] ?? 'all');
    // Purok President RBAC: force their purok
    if ($is_purok_president) {
        $purok = $_SESSION['purok'] ?? 'all';
    }
    if ($purok !== 'all') {
        $purok = preg_replace('/[^a-zA-Z0-9 \-_]/', '', $purok);
        if (strlen($purok) > 50) $purok = 'all';
    }

    $allowed_categories = [
        'all','pwd','deceased','newborns','seniors','voters',
        '4ps','solo_parent','hypertension','diabetes',
        'children_0_17','lgbtq','graduates',
        'residents','officials','smokers','nhts'
    ];
    $category = trim($_GET['category'] ?? 'all');
    if (!in_array($category, $allowed_categories, true)) $category = 'all';

    $allowed_ses = [
        'all','Poor','Low Income','Lower Middle Income',
        'Middle Income','Upper Middle Income','High Income'
    ];
    $ses_filter = trim($_GET['ses'] ?? 'all');
    if (!in_array($ses_filter, $allowed_ses, true)) $ses_filter = 'all';

    // Expanded PWD type whitelist — includes Bisaya-label options
    $allowed_pwd_types = [
        'all',
        'Physical Disability',
        'Visual Impairment',
        'Hearing Impairment',
        'Hearing Disability',
        'Speech Impairment',
        'Speech Disability',
        'Intellectual Disability',
        'Intellectual',
        'Psychosocial Disability',
        'Psychosocial',
        'Multiple Disabilities',
        'Chronic Illness',
        'Other',
    ];
    $pwd_type_filter = trim($_GET['pwd_type'] ?? 'all');
    if (!in_array($pwd_type_filter, $allowed_pwd_types, true)) $pwd_type_filter = 'all';

    $voter_status_filter = trim($_GET['voter_status'] ?? 'all');
    if (!in_array($voter_status_filter, ['all','Yes','No'], true)) $voter_status_filter = 'all';

    $household_no_filter = trim($_GET['household_no'] ?? 'all');
    if ($household_no_filter !== 'all') {
        $household_no_filter = preg_replace('/[^a-zA-Z0-9\-_]/', '', $household_no_filter);
        if (strlen($household_no_filter) > 30) $household_no_filter = 'all';
    }

    // Barangay filter
    $barangay_filter = trim($_GET['barangay'] ?? 'all');
    if ($barangay_filter !== 'all') {
        $barangay_filter = preg_replace('/[^a-zA-Z0-9 .\-_,()]/', '', $barangay_filter);
        if (strlen($barangay_filter) > 100) $barangay_filter = 'all';
    }

    $purok_safe    = ($purok !== 'all') ? $conn->real_escape_string($purok) : null;
    $ses_safe      = ($ses_filter !== 'all') ? $conn->real_escape_string($ses_filter) : null;
    $pwdtype_safe  = ($pwd_type_filter !== 'all') ? $conn->real_escape_string($pwd_type_filter) : null;
    $hhno_safe     = ($household_no_filter !== 'all') ? $conn->real_escape_string($household_no_filter) : null;
    $barangay_safe = ($barangay_filter !== 'all') ? $conn->real_escape_string($barangay_filter) : null;

    // Graduate sub-filters
    $grad_course_filter = trim($_GET['grad_course'] ?? 'all');
    $grad_year_filter   = trim($_GET['grad_year'] ?? 'all');
    $grad_course_safe   = ($grad_course_filter !== 'all' && $grad_course_filter !== '')
        ? $conn->real_escape_string($grad_course_filter) : null;
    $grad_year_safe     = ($grad_year_filter !== 'all' && $grad_year_filter !== '')
        ? intval($grad_year_filter) : null;

    // ── Helper ───────────────────────────────────────────────────────────
    function qry($conn, $sql) {
        $r = $conn->query($sql);
        if ($r === false) throw new Exception('Query failed: ' . $conn->error . ' | SQL: ' . substr($sql, 0, 400));
        $rows = [];
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    // ── FIX #1: Unified purok list from BOTH tables (filtered by barangay if set)
    $brgy_cond_res = ($barangay_safe ? " AND barangay = '$barangay_safe'" : '');
    $brgy_cond_off = ($barangay_safe ? " AND barangay = '$barangay_safe'" : '');
    $purok_rows = qry($conn, "
        SELECT DISTINCT purok FROM (
            SELECT purok FROM residents
                WHERE purok IS NOT NULL AND purok != '' AND is_deleted = 0 $brgy_cond_res
            UNION
            SELECT purok FROM barangay_official
                WHERE purok IS NOT NULL AND purok != '' AND status = 'Active' $brgy_cond_off
        ) AS all_puroks
        ORDER BY purok
    ");
    $all_puroks = array_column($purok_rows, 'purok');

    // ── UNION VIEW ───────────────────────────────────────────────────────
    $union_sql = "
        SELECT
            id, 'resident' AS source,
            username, first_name, middle_name, surname, suffix,
            birthdate, birthplace, age, sex, lgbtq_identity, lgbtq_other_text, civil_status, nationality,
            religion, ethnicity, blood_type, philhealth_no, length_of_residency,
            household_no, purok, barangay, municipality, province,
            voters_status, educational_attainment, total_household,
            grade_level, school_name,
            course, course_other, graduation_date, eligibility, eligibility_other,
            is_pwd, pwd_type, is_deceased, date_of_death, is_newborn,
            contact_no, occupation_type, occupation,
            monthly_income, annual_income, socioeconomic_status, household_position,
            house_ownership, house_material, toilet_type, water_source,
            is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker,
            has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health,
            membership_type, family_planning, image_path
        FROM residents
        WHERE is_deleted = 0

        UNION ALL

        SELECT
            id, 'official' AS source,
            username, first_name, middle_name, surname, suffix,
            birthdate, birthplace, age, sex, lgbtq_identity, lgbtq_other_text, civil_status, nationality,
            religion, ethnicity, blood_type, philhealth_no, length_of_residency,
            household_no, purok, barangay, municipality, province,
            voters_status, educational_attainment, total_household,
            grade_level, school_name,
            course, course_other, graduation_date, eligibility, eligibility_other,
            is_pwd, pwd_type, is_deceased, date_of_death, 'No' AS is_newborn,
            contact_no, occupation_type, occupation,
            monthly_income, annual_income, socioeconomic_status, household_position,
            house_ownership, house_material, toilet_type, water_source,
            is_4ps, is_nhts, is_solo_parent, is_smoker, is_binge_drinker,
            has_hypertension, has_diabetes, has_asthma, has_tb, has_cancer, has_mental_health,
            membership_type, family_planning, image_path
        FROM barangay_official
        WHERE status = 'Active'
    ";
    $base = "(SELECT * FROM ($union_sql) AS combined_data)";

    // ── Build WHERE conditions ───────────────────────────────────────────
    $conditions = [];

    if ($purok_safe)
        $conditions[] = "purok = '$purok_safe'";

    if ($ses_safe)
        $conditions[] = "socioeconomic_status = '$ses_safe'";

    // FIX #2: pwd_type uses LIKE for partial/comma-separated field values
    if ($pwdtype_safe)
        $conditions[] = "is_pwd = 'Yes' AND pwd_type LIKE '%$pwdtype_safe%'";

    if ($voter_status_filter !== 'all')
        $conditions[] = "voters_status = '" . ($voter_status_filter === 'Yes' ? 'Yes' : 'No') . "'";

    if ($hhno_safe)
        $conditions[] = "household_no = '$hhno_safe'";

    if ($barangay_safe)
        $conditions[] = "barangay = '$barangay_safe'";

    $category_label = null;
    switch ($category) {
        case 'pwd':
            if (!$pwdtype_safe) $conditions[] = "is_pwd = 'Yes'";
            $category_label = $pwdtype_safe ? "PWD – $pwd_type_filter" : 'PWD Only';
            break;
        case 'deceased':
            $conditions[]   = "is_deceased = 'Yes'";
            $category_label = 'Deceased Only'; break;
        case 'newborns':
            $conditions[]   = "age <= 1";
            $category_label = 'Newborns Only'; break;
        case 'seniors':
            $conditions[]   = "age >= 60";
            $category_label = 'Senior Citizens Only'; break;
        case 'voters':
            // Respect the voter_status dropdown: if user explicitly chose 'No', show unregistered
            if ($voter_status_filter === 'No') {
                // voter_status filter already added voters_status = 'No' above
                $category_label = 'Not Registered Voters';
            } else {
                // Default: show registered voters (only add if filter didn't already)
                if ($voter_status_filter === 'all') {
                    $conditions[] = "voters_status = 'Yes'";
                }
                $category_label = 'Registered Voters';
            }
            break;
        case '4ps':
            $conditions[]   = "is_4ps = 'Yes'";
            $category_label = '4Ps Beneficiaries'; break;
        case 'solo_parent':
            $conditions[]   = "is_solo_parent = 'Yes'";
            $category_label = 'Solo Parents'; break;
        case 'hypertension':
            $conditions[]   = "has_hypertension = 'Yes'";
            $category_label = 'With Hypertension'; break;
        case 'diabetes':
            $conditions[]   = "has_diabetes = 'Yes'";
            $category_label = 'With Diabetes'; break;
        case 'children_0_17':
            $conditions[]   = "age >= 0 AND age <= 17";
            $category_label = 'Children 0–17 Years Old'; break;
        case 'lgbtq':
            $conditions[]   = "sex = 'LGBTQ+'";
            $category_label = 'LGBTQ+'; break;
        case 'graduates':
            $conditions[]   = "educational_attainment IN ('College','Vocational','Post Graduate')";
            if ($grad_course_safe) {
                $conditions[] = "(course = '$grad_course_safe' OR course_other = '$grad_course_safe')";
            }
            if ($grad_year_safe) {
                $conditions[] = "YEAR(graduation_date) = $grad_year_safe";
            }
            $category_label = 'Graduates';
            if ($grad_course_safe) $category_label .= " — $grad_course_filter";
            if ($grad_year_safe)   $category_label .= " ($grad_year_safe)";
            break;
        case 'residents':
            $conditions[]   = "source = 'resident'";
            $category_label = 'Residents'; break;
        case 'officials':
            $conditions[]   = "source = 'official'";
            $category_label = 'Officials'; break;
        case 'smokers':
            $conditions[]   = "is_smoker = 'Yes'";
            $category_label = 'Smokers'; break;
        case 'nhts':
            $conditions[]   = "is_nhts = 'Yes'";
            $category_label = 'NHTS Households'; break;
    }

    $where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Chart-level WHERE: applies purok + barangay filters (not category/ses)
    $chart_conditions = [];
    if ($purok_safe)    $chart_conditions[] = "purok = '$purok_safe'";
    if ($barangay_safe) $chart_conditions[] = "barangay = '$barangay_safe'";
    $purok_where = $chart_conditions ? 'WHERE ' . implode(' AND ', $chart_conditions) : '';
    // Connector: AND if $purok_where already has WHERE, else WHERE
    $purok_and   = $purok_where ? 'AND' : 'WHERE';

    // ── 1. STATISTICS ────────────────────────────────────────────────────
    $stats_rows = qry($conn, "
        SELECT
            COUNT(*)                              AS total_population,
            SUM(is_deceased = 'Yes')              AS total_deceased,
            SUM(age <= 1)                         AS total_newborns,
            SUM(age >= 60)                        AS total_seniors,
            SUM(is_pwd = 'Yes')                   AS total_pwd,
            SUM(voters_status = 'Yes')            AS total_voters,
            SUM(voters_status = 'No')             AS total_non_voters,
            SUM(sex = 'Male')                     AS total_male,
            SUM(sex = 'Female')                   AS total_female,
            SUM(is_4ps = 'Yes')                   AS total_4ps,
            SUM(is_solo_parent = 'Yes')           AS total_solo_parent,
            SUM(has_hypertension = 'Yes')         AS total_hypertension,
            SUM(has_diabetes = 'Yes')             AS total_diabetes,
            SUM(is_smoker = 'Yes')                AS total_smokers,
            SUM(is_nhts = 'Yes')                  AS total_nhts,
            SUM(source = 'resident')              AS total_residents_only,
            SUM(source = 'official')              AS total_officials_only,
            SUM(age >= 0 AND age <= 17)           AS total_children_0_17,
            SUM(sex = 'LGBTQ+')                   AS total_lgbtq
        FROM $base AS s $where_clause
    ");
    $stats = array_map('intval', $stats_rows[0] ?? []);

    // ── 2. PEOPLE LIST ───────────────────────────────────────────────────
    $fields = "id, source, first_name, middle_name, surname, suffix, age, sex,
               lgbtq_identity, lgbtq_other_text,
               civil_status, purok, barangay, religion, ethnicity,
               is_pwd, pwd_type, is_deceased, voters_status, is_4ps,
               is_solo_parent, has_hypertension, has_diabetes, image_path,
               occupation, occupation_type, household_position, household_no,
               birthdate, birthplace, philhealth_no, educational_attainment,
               water_source, toilet_type, house_material, house_ownership,
               is_smoker, is_binge_drinker, has_asthma, has_tb,
               has_cancer, has_mental_health, is_nhts, family_planning,
               membership_type, monthly_income, annual_income, socioeconomic_status";

    $people = qry($conn, "
        SELECT $fields FROM $base AS u $where_clause
        ORDER BY purok, surname, first_name
    ");

    // ── 3. HOUSEHOLD NUMBERS (filtered by barangay if set) ────────────────
    $hh_brgy_cond = ($barangay_safe ? " AND barangay = '$barangay_safe'" : '');
    $hh_numbers = qry($conn, "
        SELECT DISTINCT household_no FROM $base AS h
        WHERE household_no IS NOT NULL AND household_no != '' $hh_brgy_cond
        ORDER BY household_no
    ");

    // ── 3b. DISTINCT BARANGAYS ───────────────────────────────────────────
    $barangay_rows = qry($conn, "
        SELECT DISTINCT barangay FROM (
            SELECT barangay FROM residents
                WHERE barangay IS NOT NULL AND barangay != '' AND is_deleted = 0
            UNION
            SELECT barangay FROM barangay_official
                WHERE barangay IS NOT NULL AND barangay != '' AND status = 'Active'
        ) AS all_barangays
        ORDER BY barangay
    ");
    $all_barangays = array_column($barangay_rows, 'barangay');

    // ── list_only optimisation — skip charts if the caller only needs people ──
    $list_only = isset($_GET['list_only']) && $_GET['list_only'] == '1';

    // ── 4. CHARTS ─────────────────────────────────────────────────────────
    $charts = [];
    $grad_courses = [];
    $grad_years = [];
  if (!$list_only) {

    // Population per purok
    foreach (qry($conn, "SELECT purok, COUNT(*) c FROM $base AS p $purok_where GROUP BY purok ORDER BY purok") as $r)
        $charts['population'][] = ['purok' => $r['purok'], 'count' => (int)$r['c']];

    // Residents vs Officials per purok
    foreach (qry($conn, "SELECT purok, SUM(source='resident') residents, SUM(source='official') officials FROM $base AS sp $purok_where GROUP BY purok ORDER BY purok") as $r)
        $charts['population_split'][] = ['purok' => $r['purok'], 'residents' => (int)$r['residents'], 'officials' => (int)$r['officials']];

    // Income
    foreach (qry($conn, "SELECT purok, COALESCE(SUM(monthly_income),0) t FROM $base AS i $purok_where GROUP BY purok ORDER BY purok") as $r)
        $charts['income'][] = ['purok' => $r['purok'], 'total' => (float)$r['t']];

    // SES
    foreach (qry($conn, "SELECT socioeconomic_status AS ses, COUNT(*) c FROM $base AS se $purok_where $purok_and socioeconomic_status IS NOT NULL AND socioeconomic_status != '' GROUP BY socioeconomic_status ORDER BY FIELD(socioeconomic_status,'Poor','Low Income','Lower Middle Income','Middle Income','Upper Middle Income','High Income')") as $r)
        $charts['ses'][] = ['label' => $r['ses'], 'count' => (int)$r['c']];

    // Sex
    foreach (qry($conn, "SELECT purok, SUM(sex='Male') m, SUM(sex='Female') f FROM $base AS sx $purok_where GROUP BY purok ORDER BY purok") as $r)
        $charts['sex'][] = ['purok' => $r['purok'], 'male' => (int)$r['m'], 'female' => (int)$r['f']];

    // Age groups
    foreach (qry($conn, "SELECT purok, SUM(age BETWEEN 0 AND 14) children, SUM(age BETWEEN 15 AND 24) youth, SUM(age BETWEEN 25 AND 59) adults, SUM(age >= 60) seniors FROM $base AS ag $purok_where GROUP BY purok ORDER BY purok") as $r)
        $charts['age_groups'][] = ['purok' => $r['purok'], 'children' => (int)$r['children'], 'youth' => (int)$r['youth'], 'adults' => (int)$r['adults'], 'seniors' => (int)$r['seniors']];

    // Occupation type
    foreach (qry($conn, "SELECT occupation_type, COUNT(*) c FROM $base AS oc $purok_where $purok_and occupation_type IS NOT NULL AND occupation_type != '' GROUP BY occupation_type ORDER BY c DESC") as $r)
        $charts['occupation_type'][] = ['label' => $r['occupation_type'], 'count' => (int)$r['c']];

    // PWD per purok
    foreach (qry($conn, "SELECT purok, COUNT(*) c FROM $base AS pp $purok_where $purok_and is_pwd = 'Yes' GROUP BY purok ORDER BY purok") as $r)
        $charts['pwd'][] = ['purok' => $r['purok'], 'count' => (int)$r['c']];

    // PWD by disability type (TRIM to normalise minor spacing variants)
    foreach (qry($conn, "SELECT TRIM(COALESCE(NULLIF(pwd_type,''),'Not Specified')) AS pwd_type, COUNT(*) c FROM $base AS pt $purok_where $purok_and is_pwd = 'Yes' GROUP BY TRIM(COALESCE(NULLIF(pwd_type,''),'Not Specified')) ORDER BY c DESC") as $r)
        $charts['pwd_types'][] = ['label' => $r['pwd_type'], 'count' => (int)$r['c']];

    // Voter status donut
    $voter_map = ['Yes' => 0, 'No' => 0];
    foreach (qry($conn, "SELECT voters_status, COUNT(*) c FROM $base AS vt $purok_where GROUP BY voters_status") as $r)
        $voter_map[$r['voters_status']] = (int)$r['c'];
    $charts['voter_status'] = [['label'=>'Registered','count'=>$voter_map['Yes']], ['label'=>'Not Registered','count'=>$voter_map['No']]];

    // Voters per purok
    foreach (qry($conn, "SELECT purok, COUNT(*) c FROM $base AS vp $purok_where $purok_and voters_status = 'Yes' GROUP BY purok ORDER BY purok") as $r)
        $charts['voters'][] = ['purok' => $r['purok'], 'count' => (int)$r['c']];

    // Seniors / Deceased / Newborns per purok
    foreach (['seniors' => "age >= 60", 'deceased' => "is_deceased = 'Yes'", 'newborns' => "age <= 1"] as $key => $cond)
        foreach (qry($conn, "SELECT purok, COUNT(*) c FROM $base AS sc $purok_where $purok_and $cond GROUP BY purok ORDER BY purok") as $r)
            $charts[$key][] = ['purok' => $r['purok'], 'count' => (int)$r['c']];

    // Education
    foreach (qry($conn, "SELECT purok, educational_attainment attainment, COUNT(*) c FROM $base AS ed $purok_where $purok_and educational_attainment IS NOT NULL AND educational_attainment != '' GROUP BY purok, educational_attainment ORDER BY purok, attainment") as $r)
        $charts['education'][] = ['purok' => $r['purok'], 'attainment' => $r['attainment'], 'count' => (int)$r['c']];

    // Simple label→count charts
    foreach ([
        'religion'        => ['religion',       "religion IS NOT NULL AND religion != ''"],
        'ethnicity'       => ['ethnicity',       "ethnicity IS NOT NULL AND ethnicity != ''"],
        'house_ownership' => ['house_ownership', "house_ownership IS NOT NULL AND house_ownership != ''"],
        'house_material'  => ['house_material',  "house_material IS NOT NULL AND house_material != ''"],
        'water_source'    => ['water_source',    "water_source IS NOT NULL AND water_source != ''"],
        'toilet_type'     => ['toilet_type',     "toilet_type IS NOT NULL AND toilet_type != ''"],
        'membership'      => ['membership_type', "membership_type IS NOT NULL AND membership_type != ''"],
    ] as $key => [$col, $cond])
        foreach (qry($conn, "SELECT $col lbl, COUNT(*) c FROM $base AS sm $purok_where $purok_and $cond GROUP BY $col ORDER BY c DESC") as $r)
            $charts[$key][] = ['label' => $r['lbl'], 'count' => (int)$r['c']];

    // Health per purok
    foreach (qry($conn, "SELECT purok, SUM(has_hypertension='Yes') hpn, SUM(has_diabetes='Yes') dm, SUM(has_asthma='Yes') asthma, SUM(has_tb='Yes') tb, SUM(has_cancer='Yes') cancer, SUM(has_mental_health='Yes') mental FROM $base AS hp $purok_where GROUP BY purok ORDER BY purok") as $r)
        $charts['health'][] = ['purok'=>$r['purok'],'hpn'=>(int)$r['hpn'],'dm'=>(int)$r['dm'],'asthma'=>(int)$r['asthma'],'tb'=>(int)$r['tb'],'cancer'=>(int)$r['cancer'],'mental'=>(int)$r['mental']];

    // Health summary
    $hs = qry($conn, "SELECT SUM(has_hypertension='Yes') hpn, SUM(has_diabetes='Yes') dm, SUM(has_asthma='Yes') asthma, SUM(has_tb='Yes') tb, SUM(has_cancer='Yes') cancer, SUM(has_mental_health='Yes') mental, SUM(is_smoker='Yes') smoker, SUM(is_binge_drinker='Yes') binge FROM $base AS hs $purok_where");
    if (!empty($hs)) {
        $h = $hs[0];
        $charts['health_summary'] = [
            ['label'=>'Hypertension','count'=>(int)$h['hpn']], ['label'=>'Diabetes','count'=>(int)$h['dm']],
            ['label'=>'Asthma','count'=>(int)$h['asthma']],   ['label'=>'Tuberculosis','count'=>(int)$h['tb']],
            ['label'=>'Cancer','count'=>(int)$h['cancer']],   ['label'=>'Mental Health','count'=>(int)$h['mental']],
            ['label'=>'Smokers','count'=>(int)$h['smoker']],  ['label'=>'Binge Drinkers','count'=>(int)$h['binge']],
        ];
    }

    // Social programs
    foreach (qry($conn, "SELECT purok, SUM(is_4ps='Yes') fourps, SUM(is_nhts='Yes') nhts, SUM(is_solo_parent='Yes') solo, SUM(family_planning='Yes') fp FROM $base AS sc2 $purok_where GROUP BY purok ORDER BY purok") as $r)
        $charts['social'][] = ['purok'=>$r['purok'],'fourps'=>(int)$r['fourps'],'nhts'=>(int)$r['nhts'],'solo'=>(int)$r['solo'],'fp'=>(int)$r['fp']];

    // ── Children 0-17 by age breakdown (for masterlist table) ────────────
    $children_by_age = [];
    $age_labels = ['0-11 MONTHS','1 YEAR OLD','2 YEARS OLD','3 YEARS OLD','4 YEARS OLD','5 YEARS OLD','6 YEARS OLD','7 YEARS OLD','8 YEARS OLD','9 YEARS OLD','10 YEARS OLD','11 YEARS OLD','12 YEARS OLD','13 YEARS OLD','14 YEARS OLD','15 YEARS OLD','16 YEARS OLD','17 YEARS OLD'];
    for ($a = 0; $a <= 17; $a++) {
        $age_cond = ($a === 0) ? "age = 0" : "age = $a";
        $row = qry($conn, "SELECT SUM(sex='Male') m, SUM(sex='Female') f, COUNT(*) t FROM $base AS ca $purok_where $purok_and $age_cond");
        $children_by_age[] = [
            'age_label' => $age_labels[$a],
            'age'       => $a,
            'male'      => (int)($row[0]['m'] ?? 0),
            'female'    => (int)($row[0]['f'] ?? 0),
            'total'     => (int)($row[0]['t'] ?? 0),
        ];
    }
    $charts['children_by_age'] = $children_by_age;

    // ── LGBTQ+ breakdown ────────────────────────────────────────────────
    foreach (qry($conn, "SELECT COALESCE(NULLIF(lgbtq_identity,''),'Not Specified') lbl, COUNT(*) c FROM $base AS lq $purok_where $purok_and sex = 'LGBTQ+' GROUP BY lgbtq_identity ORDER BY c DESC") as $r)
        $charts['lgbtq_breakdown'][] = ['label' => $r['lbl'], 'count' => (int)$r['c']];

    // Officials-specific charts
    $off_where = "WHERE status = 'Active'" . ($purok_safe ? " AND purok = '$purok_safe'" : '') . ($barangay_safe ? " AND barangay = '$barangay_safe'" : '');
    foreach ([
        'officials_position'     => "SELECT position lbl, COUNT(*) c FROM barangay_official $off_where GROUP BY position ORDER BY c DESC",
        'officials_purok'        => "SELECT COALESCE(purok,'Unassigned') lbl, COUNT(*) c FROM barangay_official $off_where GROUP BY purok ORDER BY purok",
        'officials_chairmanship' => "SELECT COALESCE(NULLIF(chairmanship,''),'None') lbl, COUNT(*) c FROM barangay_official $off_where GROUP BY chairmanship ORDER BY c DESC",
        'officials_sex'          => "SELECT sex lbl, COUNT(*) c FROM barangay_official $off_where GROUP BY sex",
    ] as $key => $sql)
        foreach (qry($conn, $sql) as $r)
            $charts[$key][] = ['label' => $r['lbl'], 'count' => (int)$r['c']];

    foreach (['population','population_split','income','ses','sex','age_groups','occupation_type','pwd','pwd_types','voter_status','voters','seniors','deceased','newborns','education','religion','ethnicity','house_ownership','house_material','water_source','toilet_type','health','health_summary','social','membership','officials_position','officials_purok','officials_chairmanship','officials_sex','children_by_age','lgbtq_breakdown'] as $k)
        if (!isset($charts[$k])) $charts[$k] = [];

    // ── Graduate dropdown data ──────────────────────────────────────────
    $grad_courses_rows = qry($conn, "
        SELECT DISTINCT COALESCE(NULLIF(course,''), course_other) AS c FROM $base AS gc
        WHERE (course IS NOT NULL AND course != '') OR (course_other IS NOT NULL AND course_other != '')
        ORDER BY c
    ");
    $grad_courses = array_filter(array_column($grad_courses_rows, 'c'));

    $grad_years_rows = qry($conn, "
        SELECT DISTINCT YEAR(graduation_date) AS y FROM $base AS gy
        WHERE graduation_date IS NOT NULL AND graduation_date > '1970-01-01'
        ORDER BY y DESC
    ");
    $grad_years = array_filter(array_column($grad_years_rows, 'y'));

  } // end if (!$list_only)

    $conn->close();

    echo json_encode([
        'success'        => true,
        'stats'          => $stats,
        'people'         => $people,
        'charts'         => $charts,
        'puroks'         => $all_puroks,
        'barangays'      => $all_barangays,
        'hh_numbers'     => array_column($hh_numbers, 'household_no'),
        'selected_purok' => $purok,
        'category_label' => $category_label,
        'grad_courses'   => array_values($grad_courses),
        'grad_years'     => array_values(array_map('intval', $grad_years)),
        'active_filters' => [
            'ses'          => $ses_filter,
            'pwd_type'     => $pwd_type_filter,
            'voter_status' => $voter_status_filter,
            'household_no' => $household_no_filter,
            'barangay'     => $barangay_filter,
        ],
    ]);

} catch (Exception $e) {
    error_log('[fetch_dashboard] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not load data. Check your database connection. (' . $e->getMessage() . ')']);
}