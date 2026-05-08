<?php
/**
 * export_csv.php
 * Handles CSV export for each demographic chart on the dashboard.
 * Usage: export_csv.php?report=<report_name>&purok=<purok|all>
 *
 * NEW in this version:
 *  - 'population' report now includes full resident list (name, HH No., age, sex, etc.)
 *  - HH No. included in all resident-level exports
 *  - New demographic reports: religion, ethnicity, house_ownership, house_material,
 *    water_source, toilet_type, health_summary, health_per_purok, social_programs,
 *    membership, residents_list (complete resident export)
 */

session_start();

// ── Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

include("connection.php");

// ── Allowed reports ─────────────────────────────────────────────────────────
$allowed_reports = [
    // Population / demographics
    'population',
    'residents_list',
    'income',
    'sex',
    'seniors',
    'deceased',
    'newborns',
    'age_groups',
    'pwd',
    'voters',
    'education',
    // NEW demographic reports
    'religion',
    'ethnicity',
    'house_ownership',
    'house_material',
    'water_source',
    'toilet_type',
    'health_summary',
    'health_per_purok',
    'social_programs',
    'membership',
    // Officials
    'officials_position',
    'officials_purok',
    'officials_chairmanship',
    'officials_sex',
    // Export all
    'all',
];

$report = isset($_GET['report']) ? trim($_GET['report']) : '';
$purok  = isset($_GET['purok'])  ? trim($_GET['purok'])  : 'all';

if (!in_array($report, $allowed_reports, true)) {
    http_response_code(400);
    exit('Invalid report parameter.');
}

if ($purok !== 'all' && !preg_match('/^[\w\s\-]+$/', $purok)) {
    http_response_code(400);
    exit('Invalid purok parameter.');
}

// ── Helpers ─────────────────────────────────────────────────────────────────
function send_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");   // UTF-8 BOM for Excel
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
}

// Returns [whereClause, params] — the WHERE clause already includes 'WHERE'
// Always excludes soft-deleted residents
function purok_where(string $purok, string $alias = ''): array
{
    $col = $alias ? "$alias.purok" : 'purok';
    if ($purok === 'all') return [' WHERE is_deleted = 0 ', []];
    return [" WHERE is_deleted = 0 AND $col = ? ", [$purok]];
}

// Same but as extra AND clause (for queries that already have a WHERE)
// Always excludes soft-deleted residents
function purok_and(string $purok, string $alias = ''): array
{
    $col = $alias ? "$alias.purok" : 'purok';
    if ($purok === 'all') return [' AND is_deleted = 0 ', []];
    return [" AND is_deleted = 0 AND $col = ? ", [$purok]];
}

function run_query(mysqli $conn, string $sql, array $params = []): array
{
    if (empty($params)) {
        $r = $conn->query($sql);
        $rows = [];
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        return $rows;
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) { http_response_code(500); exit('Prepare failed: ' . $conn->error); }
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $r = $stmt->get_result();
    $rows = [];
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    $stmt->close();
    return $rows;
}

$date_suffix = date('Y-m-d');

// ── Master report definitions ─────────────────────────────────────────────
function get_report_data(string $report, mysqli $conn, string $purok): array
{
    [$wh, $wp] = purok_where($purok);

    switch ($report) {

        // ── Population summary per Purok ─────────────────────────────────
        case 'population':
            $sql = "SELECT
                        purok                                             AS 'Purok',
                        COUNT(*)                                          AS 'Total',
                        SUM(sex='Male')                                   AS 'Male',
                        SUM(sex='Female')                                 AS 'Female',
                        SUM(age>=60)                                      AS 'Seniors',
                        SUM(age<=1)                                       AS 'Newborns',
                        SUM(is_pwd='Yes')                                 AS 'PWD',
                        SUM(voters_status='Yes')                          AS 'Registered Voters',
                        SUM(is_deceased='Yes')                            AS 'Deceased',
                        SUM(is_4ps='Yes')                                 AS '4Ps',
                        SUM(is_solo_parent='Yes')                         AS 'Solo Parent',
                        SUM(is_nhts='Yes')                                AS 'NHTS'
                    FROM residents $wh GROUP BY purok ORDER BY purok";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Purok','Total','Male','Female','Seniors (60+)','Newborns','PWD',
                              'Registered Voters','Deceased','4Ps','Solo Parent','NHTS'],
                'rows'    => array_map(fn($r) => [
                    $r['Purok'],$r['Total'],$r['Male'],$r['Female'],$r['Seniors'],
                    $r['Newborns'],$r['PWD'],$r['Registered Voters'],$r['Deceased'],
                    $r['4Ps'],$r['Solo Parent'],$r['NHTS'],
                ], $rows),
                'label'   => 'Population Summary per Purok',
            ];

        // ── Full resident list (with HH No., names, demographics) ────────
        case 'residents_list':
            $sql = "SELECT
                        household_no                                      AS 'HH No.',
                        purok                                             AS 'Purok',
                        CONCAT(surname, ', ', first_name,
                               IF(middle_name IS NOT NULL AND middle_name != '',
                                  CONCAT(' ', middle_name), ''))          AS 'Full Name',
                        surname                                           AS 'Surname',
                        first_name                                        AS 'First Name',
                        middle_name                                       AS 'Middle Name',
                        sex                                               AS 'Sex',
                        birthdate                                         AS 'Birthdate',
                        age                                               AS 'Age',
                        birthplace                                        AS 'Birthplace',
                        civil_status                                      AS 'Civil Status',
                        nationality                                       AS 'Nationality',
                        contact_no                                        AS 'Contact No.',
                        household_position                                AS 'Relation to Head',
                        total_household                                   AS 'Total HH Members',
                        voters_status                                     AS 'Voter Status',
                        educational_attainment                            AS 'Educ. Attainment',
                        grade_level                                       AS 'Grade/Year Level',
                        school_name                                       AS 'School',
                        occupation                                        AS 'Occupation',
                        monthly_income                                    AS 'Monthly Income',
                        annual_income                                     AS 'Annual Income',
                        religion                                          AS 'Religion',
                        ethnicity                                         AS 'Ethnicity',
                        blood_type                                        AS 'Blood Type',
                        philhealth_no                                     AS 'PHIC No.',
                        membership_type                                   AS 'PHIC Membership',
                        house_ownership                                   AS 'House Ownership',
                        house_material                                    AS 'House Material',
                        toilet_type                                       AS 'Toilet Type',
                        water_source                                      AS 'Water Source',
                        length_of_residency                               AS 'Residency (yrs)',
                        is_4ps                                            AS '4Ps',
                        is_nhts                                           AS 'NHTS',
                        is_solo_parent                                    AS 'Solo Parent',
                        family_planning                                   AS 'Family Planning',
                        is_pwd                                            AS 'PWD',
                        pwd_details                                       AS 'PWD Details',
                        is_smoker                                         AS 'Smoker',
                        is_binge_drinker                                  AS 'Binge Drinker',
                        has_hypertension                                  AS 'Hypertension (HPN)',
                        has_diabetes                                      AS 'Diabetes (DM)',
                        has_asthma                                        AS 'Asthma',
                        has_tb                                            AS 'Tuberculosis (TB)',
                        has_cancer                                        AS 'Cancer',
                        has_mental_health                                 AS 'Mental Health',
                        is_newborn                                        AS 'Newborn',
                        is_deceased                                       AS 'Deceased',
                        date_of_death                                     AS 'Date of Death',
                        barangay                                          AS 'Barangay',
                        municipality                                      AS 'Municipality',
                        province                                          AS 'Province',
                        created_at                                        AS 'Date Registered'
                    FROM residents $wh ORDER BY purok, household_no, surname, first_name";
            $rows = run_query($conn, $sql, $wp);
            $headers = array_keys($rows[0] ?? ['No data' => '']);
            return [
                'headers' => $headers,
                'rows'    => array_map(fn($r) => array_values($r), $rows),
                'label'   => 'Complete Resident List',
            ];

        // ── Income per Purok ─────────────────────────────────────────────
        case 'income':
            $sql = "SELECT purok AS 'Purok',
                           COALESCE(SUM(monthly_income),0)   AS 'Total Monthly Income',
                           ROUND(AVG(monthly_income),2)      AS 'Avg Monthly Income',
                           COALESCE(SUM(annual_income),0)    AS 'Total Annual Income'
                    FROM residents $wh GROUP BY purok ORDER BY purok";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Purok','Total Monthly Income','Avg Monthly Income','Total Annual Income'],
                'rows'    => array_map(fn($r) => [
                    $r['Purok'],$r['Total Monthly Income'],
                    $r['Avg Monthly Income'],$r['Total Annual Income'],
                ], $rows),
                'label'   => 'Income per Purok',
            ];

        // ── Sex Distribution ─────────────────────────────────────────────
        case 'sex':
            $sql = "SELECT purok AS 'Purok',
                           SUM(sex='Male') AS 'Male', SUM(sex='Female') AS 'Female',
                           COUNT(*) AS 'Total'
                    FROM residents $wh GROUP BY purok ORDER BY purok";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Purok','Male','Female','Total'],
                'rows'    => array_map(fn($r) => [$r['Purok'],$r['Male'],$r['Female'],$r['Total']], $rows),
                'label'   => 'Sex Distribution per Purok',
            ];

        // ── Senior Citizens ──────────────────────────────────────────────
        case 'seniors': {
            [$and, $ap] = purok_and($purok);
            $sql  = "SELECT household_no AS 'HH No.', purok AS 'Purok',
                            CONCAT(surname,', ',first_name) AS 'Name',
                            age AS 'Age', sex AS 'Sex', contact_no AS 'Contact'
                     FROM residents WHERE age >= 60 $and ORDER BY purok, surname";
            $rows = run_query($conn, $sql, $ap);
            return [
                'headers' => ['HH No.','Purok','Name','Age','Sex','Contact'],
                'rows'    => array_map(fn($r) => array_values($r), $rows),
                'label'   => 'Senior Citizens List',
            ];
        }

        // ── Deceased ─────────────────────────────────────────────────────
        case 'deceased': {
            [$and, $ap] = purok_and($purok);
            $sql  = "SELECT household_no AS 'HH No.', purok AS 'Purok',
                            CONCAT(surname,', ',first_name) AS 'Name',
                            age AS 'Age', sex AS 'Sex', date_of_death AS 'Date of Death'
                     FROM residents WHERE is_deceased = 'Yes' $and ORDER BY purok, surname";
            $rows = run_query($conn, $sql, $ap);
            return [
                'headers' => ['HH No.','Purok','Name','Age','Sex','Date of Death'],
                'rows'    => array_map(fn($r) => array_values($r), $rows),
                'label'   => 'Deceased Residents List',
            ];
        }

        // ── Newborns ─────────────────────────────────────────────────────
        case 'newborns': {
            [$and, $ap] = purok_and($purok);
            $sql  = "SELECT household_no AS 'HH No.', purok AS 'Purok',
                            CONCAT(surname,', ',first_name) AS 'Name',
                            birthdate AS 'Birthdate', age AS 'Age (months/yrs)', sex AS 'Sex'
                     FROM residents WHERE age <= 1 $and ORDER BY purok, birthdate DESC";
            $rows = run_query($conn, $sql, $ap);
            return [
                'headers' => ['HH No.','Purok','Name','Birthdate','Age','Sex'],
                'rows'    => array_map(fn($r) => array_values($r), $rows),
                'label'   => 'Newborns List',
            ];
        }

        // ── Age Groups ───────────────────────────────────────────────────
        case 'age_groups':
            $sql = "SELECT purok AS 'Purok',
                           SUM(age BETWEEN 0  AND 14) AS 'Children (0–14)',
                           SUM(age BETWEEN 15 AND 24) AS 'Youth (15–24)',
                           SUM(age BETWEEN 25 AND 59) AS 'Adults (25–59)',
                           SUM(age >= 60)             AS 'Seniors (60+)',
                           COUNT(*)                   AS 'Total'
                    FROM residents $wh GROUP BY purok ORDER BY purok";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Purok','Children (0–14)','Youth (15–24)','Adults (25–59)','Seniors (60+)','Total'],
                'rows'    => array_map(fn($r) => [
                    $r['Purok'],$r['Children (0–14)'],$r['Youth (15–24)'],
                    $r['Adults (25–59)'],$r['Seniors (60+)'],$r['Total'],
                ], $rows),
                'label'   => 'Age Groups per Purok',
            ];

        // ── PWD ──────────────────────────────────────────────────────────
        case 'pwd': {
            [$and, $ap] = purok_and($purok);
            $sql  = "SELECT household_no AS 'HH No.', purok AS 'Purok',
                            CONCAT(surname,', ',first_name) AS 'Name',
                            age AS 'Age', sex AS 'Sex', pwd_details AS 'Type of Disability'
                     FROM residents WHERE is_pwd = 'Yes' $and ORDER BY purok, surname";
            $rows = run_query($conn, $sql, $ap);
            return [
                'headers' => ['HH No.','Purok','Name','Age','Sex','Type of Disability'],
                'rows'    => array_map(fn($r) => array_values($r), $rows),
                'label'   => 'PWD Residents List',
            ];
        }

        // ── Voters ───────────────────────────────────────────────────────
        case 'voters': {
            [$and, $ap] = purok_and($purok);
            $sql  = "SELECT household_no AS 'HH No.', purok AS 'Purok',
                            CONCAT(surname,', ',first_name) AS 'Name',
                            age AS 'Age', sex AS 'Sex'
                     FROM residents WHERE voters_status = 'Yes' $and ORDER BY purok, surname";
            $rows = run_query($conn, $sql, $ap);
            return [
                'headers' => ['HH No.','Purok','Name','Age','Sex'],
                'rows'    => array_map(fn($r) => array_values($r), $rows),
                'label'   => 'Registered Voters List',
            ];
        }

        // ── Educational Attainment ───────────────────────────────────────
        case 'education':
            $sql = "SELECT purok AS 'Purok',
                           educational_attainment AS 'Attainment',
                           COUNT(*) AS 'Count'
                    FROM residents
                    WHERE educational_attainment IS NOT NULL AND educational_attainment != ''
                    " . ($purok !== 'all' ? "AND purok = ?" : "") . "
                    GROUP BY purok, educational_attainment
                    ORDER BY purok, educational_attainment";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Purok','Educational Attainment','Count'],
                'rows'    => array_map(fn($r) => [$r['Purok'],$r['Attainment'],$r['Count']], $rows),
                'label'   => 'Educational Attainment per Purok',
            ];

        // ════════ NEW DEMOGRAPHIC REPORTS ════════════════════════════════

        // ── Religion Distribution ────────────────────────────────────────
        case 'religion': {
            // Get total count first (separate query, no subquery conflict)
            $total_sql  = "SELECT COUNT(*) AS total FROM residents $wh";
            $total_rows = run_query($conn, $total_sql, $wp);
            $total      = (int)($total_rows[0]['total'] ?? 1);
            if ($total === 0) $total = 1;

            $sql  = "SELECT religion AS 'Religion', COUNT(*) AS 'Count'
                     FROM residents
                     WHERE religion IS NOT NULL AND religion != ''
                     " . ($purok !== 'all' ? "AND purok = ?" : "") . "
                     GROUP BY religion ORDER BY Count DESC";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Religion', 'Count', 'Percentage (%)'],
                'rows'    => array_map(fn($r) => [
                    $r['Religion'],
                    $r['Count'],
                    round((int)$r['Count'] * 100.0 / $total, 1),
                ], $rows),
                'label'   => 'Religion Distribution',
            ];
        }

        // ── Ethnicity Distribution ───────────────────────────────────────
        case 'ethnicity': {
            $total_sql  = "SELECT COUNT(*) AS total FROM residents $wh";
            $total_rows = run_query($conn, $total_sql, $wp);
            $total      = (int)($total_rows[0]['total'] ?? 1);
            if ($total === 0) $total = 1;

            $sql  = "SELECT ethnicity AS 'Ethnicity/IP Group', COUNT(*) AS 'Count'
                     FROM residents
                     WHERE ethnicity IS NOT NULL AND ethnicity != ''
                     " . ($purok !== 'all' ? "AND purok = ?" : "") . "
                     GROUP BY ethnicity ORDER BY Count DESC";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Ethnicity/IP Group', 'Count', 'Percentage (%)'],
                'rows'    => array_map(fn($r) => [
                    $r['Ethnicity/IP Group'],
                    $r['Count'],
                    round((int)$r['Count'] * 100.0 / $total, 1),
                ], $rows),
                'label'   => 'Ethnicity/IP Group Distribution',
            ];
        }

        // ── House Ownership ──────────────────────────────────────────────
        case 'house_ownership':
            $sql = "SELECT house_ownership AS 'House Ownership', COUNT(*) AS 'Count'
                    FROM residents
                    WHERE house_ownership IS NOT NULL
                    " . ($purok !== 'all' ? "AND purok = ?" : "") . "
                    GROUP BY house_ownership ORDER BY Count DESC";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['House Ownership','Count'],
                'rows'    => array_map(fn($r) => [$r['House Ownership'],$r['Count']], $rows),
                'label'   => 'House Ownership Distribution',
            ];

        // ── House Material ───────────────────────────────────────────────
        case 'house_material':
            $sql = "SELECT house_material AS 'House Material', COUNT(*) AS 'Count'
                    FROM residents
                    WHERE house_material IS NOT NULL
                    " . ($purok !== 'all' ? "AND purok = ?" : "") . "
                    GROUP BY house_material ORDER BY Count DESC";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['House Material','Count'],
                'rows'    => array_map(fn($r) => [$r['House Material'],$r['Count']], $rows),
                'label'   => 'House Material Distribution',
            ];

        // ── Water Source ─────────────────────────────────────────────────
        case 'water_source':
            $sql = "SELECT water_source AS 'Water Source', COUNT(*) AS 'Count'
                    FROM residents
                    WHERE water_source IS NOT NULL
                    " . ($purok !== 'all' ? "AND purok = ?" : "") . "
                    GROUP BY water_source ORDER BY Count DESC";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Water Source','Count'],
                'rows'    => array_map(fn($r) => [$r['Water Source'],$r['Count']], $rows),
                'label'   => 'Water Source Distribution',
            ];

        // ── Toilet Type ──────────────────────────────────────────────────
        case 'toilet_type':
            $sql = "SELECT toilet_type AS 'Toilet Type', COUNT(*) AS 'Count'
                    FROM residents
                    WHERE toilet_type IS NOT NULL
                    " . ($purok !== 'all' ? "AND purok = ?" : "") . "
                    GROUP BY toilet_type ORDER BY Count DESC";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Toilet Type','Count'],
                'rows'    => array_map(fn($r) => [$r['Toilet Type'],$r['Count']], $rows),
                'label'   => 'Toilet Type Distribution',
            ];

        // ── Health Conditions Summary ────────────────────────────────────
        case 'health_summary':
            $sql = "SELECT
                        SUM(has_hypertension='Yes')  AS 'Hypertension (HPN)',
                        SUM(has_diabetes='Yes')      AS 'Diabetes (DM)',
                        SUM(has_asthma='Yes')        AS 'Asthma',
                        SUM(has_tb='Yes')            AS 'Tuberculosis (TB)',
                        SUM(has_cancer='Yes')        AS 'Cancer',
                        SUM(has_mental_health='Yes') AS 'Mental Health',
                        SUM(is_smoker='Yes')         AS 'Smokers',
                        SUM(is_binge_drinker='Yes')  AS 'Binge Drinkers',
                        COUNT(*)                     AS 'Total Residents'
                    FROM residents $wh";
            $rows = run_query($conn, $sql, $wp);
            // Pivot: condition name → count
            $pivot = [];
            if (!empty($rows)) {
                foreach ($rows[0] as $condition => $count) {
                    $pivot[] = [$condition, (int)$count];
                }
            }
            return [
                'headers' => ['Health Condition / Lifestyle','Count'],
                'rows'    => $pivot,
                'label'   => 'Health Conditions Summary',
            ];

        // ── Health Conditions per Purok ──────────────────────────────────
        case 'health_per_purok':
            $sql = "SELECT purok AS 'Purok',
                           SUM(has_hypertension='Yes')  AS 'HPN',
                           SUM(has_diabetes='Yes')      AS 'DM',
                           SUM(has_asthma='Yes')        AS 'Asthma',
                           SUM(has_tb='Yes')            AS 'TB',
                           SUM(has_cancer='Yes')        AS 'Cancer',
                           SUM(has_mental_health='Yes') AS 'Mental Health',
                           SUM(is_smoker='Yes')         AS 'Smokers',
                           SUM(is_binge_drinker='Yes')  AS 'Binge Drinkers'
                    FROM residents $wh GROUP BY purok ORDER BY purok";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Purok','HPN','DM','Asthma','TB','Cancer','Mental Health','Smokers','Binge Drinkers'],
                'rows'    => array_map(fn($r) => array_values($r), $rows),
                'label'   => 'Health Conditions per Purok',
            ];

        // ── Social Programs per Purok ────────────────────────────────────
        case 'social_programs':
            $sql = "SELECT purok AS 'Purok',
                           SUM(is_4ps='Yes')          AS '4Ps',
                           SUM(is_nhts='Yes')         AS 'NHTS',
                           SUM(is_solo_parent='Yes')  AS 'Solo Parent',
                           SUM(family_planning='Yes') AS 'Family Planning',
                           COUNT(*)                   AS 'Total Residents'
                    FROM residents $wh GROUP BY purok ORDER BY purok";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['Purok','4Ps','NHTS','Solo Parent','Family Planning','Total Residents'],
                'rows'    => array_map(fn($r) => array_values($r), $rows),
                'label'   => 'Social Programs per Purok',
            ];

        // ── PhilHealth Membership ────────────────────────────────────────
        case 'membership':
            $sql = "SELECT COALESCE(NULLIF(membership_type,''), 'Not a Member') AS 'Membership Type',
                           COUNT(*) AS 'Count'
                    FROM residents
                    " . ($purok !== 'all' ? "WHERE purok = ?" : "") . "
                    GROUP BY membership_type ORDER BY Count DESC";
            $rows = run_query($conn, $sql, $wp);
            return [
                'headers' => ['PhilHealth Membership Type','Count'],
                'rows'    => array_map(fn($r) => [$r['Membership Type'],$r['Count']], $rows),
                'label'   => 'PhilHealth Membership Distribution',
            ];

        // ────────── Officials Reports ──────────────────────────────────────

        // ── Officials: by Position ───────────────────────────────────────
        case 'officials_position':
            $sql = "SELECT position AS 'Position', COUNT(*) AS 'Count'
                    FROM barangay_official WHERE status='Active'
                    GROUP BY position
                    ORDER BY FIELD(position,'Barangay Captain','Barangay Kagawad',
                        'Sangguniang Barangay (SB) Member','SK Chairperson','Barangay Secretary','Barangay Treasurer')";
            $rows = run_query($conn, $sql);
            return [
                'headers' => ['Position','Count'],
                'rows'    => array_map(fn($r) => [$r['Position'],$r['Count']], $rows),
                'label'   => 'Officials by Position',
            ];

        // ── Officials: by Purok ──────────────────────────────────────────
        case 'officials_purok':
            $sql = "SELECT COALESCE(purok,'Not Assigned') AS 'Purok', COUNT(*) AS 'Count'
                    FROM barangay_official WHERE status='Active'
                    GROUP BY purok ORDER BY purok";
            $rows = run_query($conn, $sql);
            return [
                'headers' => ['Purok','Officials Count'],
                'rows'    => array_map(fn($r) => [$r['Purok'],$r['Count']], $rows),
                'label'   => 'Officials by Purok',
            ];

        // ── Officials: by Chairmanship ───────────────────────────────────
        case 'officials_chairmanship':
            $sql = "SELECT COALESCE(NULLIF(chairmanship,''),'None') AS 'Chairmanship',
                           COUNT(*) AS 'Count'
                    FROM barangay_official WHERE status='Active'
                    GROUP BY chairmanship ORDER BY chairmanship";
            $rows = run_query($conn, $sql);
            return [
                'headers' => ['Chairmanship','Count'],
                'rows'    => array_map(fn($r) => [$r['Chairmanship'],$r['Count']], $rows),
                'label'   => 'Officials by Chairmanship',
            ];

        // ── Officials: by Sex ────────────────────────────────────────────
        case 'officials_sex':
            $sql = "SELECT sex AS 'Sex', COUNT(*) AS 'Count'
                    FROM barangay_official WHERE status='Active' GROUP BY sex";
            $rows = run_query($conn, $sql);
            return [
                'headers' => ['Sex','Count'],
                'rows'    => array_map(fn($r) => [$r['Sex'],$r['Count']], $rows),
                'label'   => 'Officials by Sex',
            ];

        default:
            http_response_code(400); exit('Unknown report.');
    }
}

// ── Handle 'all' — ZIP (with CSV fallback if ZipArchive unavailable) ─────────
if ($report === 'all') {

    $all_reports = [
        'residents_list',
        'population',
        'income',
        'sex',
        'seniors',
        'deceased',
        'newborns',
        'age_groups',
        'pwd',
        'voters',
        'education',
        'religion',
        'ethnicity',
        'house_ownership',
        'house_material',
        'water_source',
        'toilet_type',
        'health_summary',
        'health_per_purok',
        'social_programs',
        'membership',
        'officials_position',
        'officials_purok',
        'officials_chairmanship',
        'officials_sex',
    ];

    // ── Try ZIP first ─────────────────────────────────────────────────────
    $zip_succeeded = false;

    if (class_exists('ZipArchive')) {
        $zip_path = tempnam(sys_get_temp_dir(), 'brgy_') . '.zip';
        $zip      = new ZipArchive();

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {

            foreach ($all_reports as $rpt) {
                try { $data = get_report_data($rpt, $conn, $purok); }
                catch (Exception $e) { continue; }
                if (empty($data['rows'])) continue;

                $tmp = fopen('php://temp', 'r+');
                fputs($tmp, "\xEF\xBB\xBF");
                fputcsv($tmp, $data['headers']);
                foreach ($data['rows'] as $row) { fputcsv($tmp, $row); }
                rewind($tmp);
                $csv_content = stream_get_contents($tmp);
                fclose($tmp);

                $zip->addFromString('demographic_' . $rpt . '_' . $date_suffix . '.csv', $csv_content);
            }

            $zip->close();

            if (file_exists($zip_path) && filesize($zip_path) > 0) {
                $conn->close();
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="barangay_demographics_' . $date_suffix . '.zip"');
                header('Content-Length: ' . filesize($zip_path));
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                ob_clean(); flush();
                readfile($zip_path);
                unlink($zip_path);
                exit;
            }
        }

        // Clean up temp file if ZIP failed
        if (file_exists($zip_path)) unlink($zip_path);
    }

    // ── Fallback: single merged CSV (always works, no ZipArchive needed) ──
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="barangay_demographics_ALL_' . $date_suffix . '.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");

    foreach ($all_reports as $rpt) {
        try { $data = get_report_data($rpt, $conn, $purok); }
        catch (Exception $e) { continue; }
        if (empty($data['rows'])) continue;

        fputcsv($out, ['']);
        fputcsv($out, ['=== ' . strtoupper($data['label']) . ' ===']);
        fputcsv($out, $data['headers']);
        foreach ($data['rows'] as $row) { fputcsv($out, $row); }
    }

    fclose($out);
    $conn->close();
    exit;
}

// ── Handle single report ─────────────────────────────────────────────────────
$data     = get_report_data($report, $conn, $purok);
$filename = 'demographic_' . $report . '_' . $date_suffix . '.csv';
$conn->close();
send_csv($filename, $data['headers'], $data['rows']);
exit;