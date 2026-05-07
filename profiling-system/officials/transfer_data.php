<?php

// ============================================
// DATABASE CONNECTIONS
// ============================================

try {
    $sourceDB = new PDO(
        "mysql:host=localhost;dbname=sto_rosario;charset=utf8mb4",
        "root", "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to SOURCE: sto_rosario\n";
} catch (PDOException $e) {
    die("❌ SOURCE Failed: " . $e->getMessage() . "\n");
}

try {
    $residentDB = new PDO(
        "mysql:host=localhost;dbname=baranggay;charset=utf8mb4",
        "root", "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to TARGET residents DB: baranggay\n";
} catch (PDOException $e) {
    die("❌ TARGET (baranggay) Failed: " . $e->getMessage() . "\n");
}

try {
    $usersDB = new PDO(
        "mysql:host=localhost;dbname=baranggay;charset=utf8mb4",
        "root", "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to TARGET users DB: baranggay\n";
} catch (PDOException $e) {
    die("❌ TARGET (baranggay) Failed: " . $e->getMessage() . "\n");
}

echo "\n";

// ============================================
// ROLE MAPPING
// ============================================
$roleMap = [
    'admin'    => 1,
    'staff'    => 2,
    'resident' => 3,
];

// ============================================
// FETCH ALL FROM SOURCE
// ============================================
$fetchAll = $sourceDB->query("SELECT * FROM residents");
$residents = $fetchAll->fetchAll(PDO::FETCH_ASSOC);
echo "📦 Total records: " . count($residents) . "\n\n";

// ============================================
// PREPARE INSERTS
// ============================================
$insertResident = $residentDB->prepare("
    INSERT INTO residents (
        first_name, middle_name, last_name, suffix,
        birth_date, birth_place, sex, civil_status,
        relation_to_head, address, household_number,
        educational_attainment, grade_course, school,
        profession_occupation, employment_type,
        contact_number, phic_no, membership,
        family_planning_method, water_supply,
        sanitary_toilet, smoker, binge_drinker,
        hpn, dm, pwd, pwd_type,
        purok_id, residency_status, date_registered
    ) VALUES (
        :first_name, :middle_name, :last_name, :suffix,
        :birth_date, :birth_place, :sex, :civil_status,
        :relation_to_head, :address, :household_number,
        :educational_attainment, :grade_course, :school,
        :profession_occupation, :employment_type,
        :contact_number, :phic_no, :membership,
        :family_planning_method, :water_supply,
        :sanitary_toilet, :smoker, :binge_drinker,
        :hpn, :dm, :pwd, :pwd_type,
        :purok_id, :residency_status, :date_registered
    )
");

$insertUser = $usersDB->prepare("
    INSERT INTO users (
        name, username, email,
        password, role_id, status,
        created_at, updated_at
    ) VALUES (
        :name, :username, :email,
        :password, :role_id, :status,
        :created_at, :updated_at
    )
");

// ============================================
// COUNTERS
// ============================================
$successResident = 0;
$successUser     = 0;
$failedResident  = 0;
$failedUser      = 0;
$skippedResident = 0;
$skippedUser     = 0;

$residentDB->beginTransaction();
$usersDB->beginTransaction();

foreach ($residents as $row) {

    // FIXED: name = first_name middle_name surname (walang email)
    $fullName = trim(
        $row['first_name'] . ' ' .
        ($row['middle_name'] ? $row['middle_name'] . ' ' : '') .
        $row['surname']
    );

    $username = $row['username'] ?? '';

    echo "──────────────────────────────────\n";
    echo "Name    : {$fullName}\n";
    echo "Username: {$username}\n";

    // =============================================
    // RESIDENTS INSERT
    // =============================================

    // Duplicate check
    $checkRes = $residentDB->prepare("
        SELECT COUNT(*) FROM residents 
        WHERE first_name = ? AND last_name = ? AND birth_date = ?
    ");
    $checkRes->execute([
        $row['first_name'],
        $row['surname'],
        $row['birthdate']
    ]);

    $residentExists = $checkRes->fetchColumn() > 0;

    if ($residentExists) {
        echo "⚠️  Resident: SKIPPED (already exists)\n";
        $skippedResident++;
        // FIXED: hindi na nag-continue — tutuloy sa user insert
    } else {
        // PUROK — extract number from "Purok 7" → 7
        $purokRaw = $row['purok'] ?? '';
        if (!empty($purokRaw)) {
            preg_match('/\d+/', $purokRaw, $matches);
            $purokId = isset($matches[0]) ? (int)$matches[0] : null;
        } else {
            $purokId = null;
        }

        // COMBINED ADDRESS
        $addressParts = array_filter([
            $row['purok']        ?? null,
            $row['barangay']     ?? null,
            $row['municipality'] ?? null,
            $row['province']     ?? null,
        ]);
        $address = implode(', ', $addressParts);

        try {
            $insertResident->execute([
                ':first_name'             => $row['first_name'],
                ':middle_name'            => $row['middle_name'],
                ':last_name'              => $row['surname'],
                ':suffix'                 => null,
                ':birth_date'             => $row['birthdate'],
                ':birth_place'            => $row['birthplace'],
                ':sex'                    => $row['sex'],
                ':civil_status'           => $row['civil_status'],
                ':relation_to_head'       => $row['household_position'],
                ':address'                => $address,
                ':household_number'       => $row['household_no'],
                ':educational_attainment' => $row['educational_attainment'],
                ':grade_course'           => $row['grade_level'],
                ':school'                 => $row['school_name'],
                ':profession_occupation'  => $row['occupation'],
                ':employment_type'        => null,
                ':contact_number'         => $row['contact_no'],
                ':phic_no'                => $row['philhealth_no'],
                ':membership'             => $row['membership_type'],
                ':family_planning_method' => $row['family_planning'] === 'Yes' ? 'Yes' : null,
                ':water_supply'           => $row['water_source'],
                ':sanitary_toilet'        => ($row['toilet_type'] && $row['toilet_type'] !== 'None') ? 1 : 0,
                ':smoker'                 => $row['is_smoker'] === 'Yes' ? 1 : 0,
                ':binge_drinker'          => $row['is_binge_drinker'] === 'Yes' ? 1 : 0,
                ':hpn'                    => $row['has_hypertension'] === 'Yes' ? 1 : 0,
                ':dm'                     => $row['has_diabetes'] === 'Yes' ? 1 : 0,
                ':pwd'                    => $row['is_pwd'] === 'Yes' ? 1 : 0,
                ':pwd_type'               => $row['pwd_details'],
                ':purok_id'               => null,
                ':residency_status'       => 'Active',
                ':date_registered'        => date('Y-m-d'),
            ]);
            $successResident++;
            echo "✅ Resident: INSERTED\n";
        } catch (PDOException $e) {
            $failedResident++;
            echo "❌ Resident FAILED: " . $e->getMessage() . "\n";
        }
    }

    // =============================================
    // USERS INSERT — SEPARATE, hindi affected ng resident skip
    // =============================================

    if (empty($username)) {
        echo "➖ User    : SKIPPED (walang username)\n\n";
        $skippedUser++;
        continue;
    }

    // Duplicate check — users
    $checkUser = $usersDB->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $checkUser->execute([$username]);

    if ($checkUser->fetchColumn() > 0) {
        echo "⚠️  User   : SKIPPED (username already exists: {$username})\n\n";
        $skippedUser++;
        continue;
    }

    try {
        $userRole = $row['user_role'] ?? 'resident';
        $roleId   = $roleMap[$userRole] ?? 3;
        $status   = ucfirst($row['account_status'] ?? 'active');

        // FIXED: walang email sa residents mo — gumawa ng fallback
        $email = $username . '@sto-rosario.com';

        $insertUser->execute([
            ':name'       => $fullName,       // first_name middle_name surname
            ':username'   => $username,        // kian1, saga, Lian... (walang @gmail)
            ':email'      => $email,           // kian1@sto-rosario.com (fallback)
            ':password'   => $row['password'], // hashed — direct copy
            ':role_id'    => $roleId,
            ':status'     => $status,
            ':created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
            ':updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s'),
        ]);

        $successUser++;
        echo "✅ User    : INSERTED ({$username} → role: {$userRole}, id: {$roleId})\n\n";

    } catch (PDOException $e) {
        $failedUser++;
        echo "❌ User FAILED: " . $e->getMessage() . "\n\n";
    }
}

$residentDB->commit();
$usersDB->commit();

// ============================================
// SUMMARY
// ============================================
echo "\n==========================================\n";
echo "           MIGRATION SUMMARY\n";
echo "==========================================\n";
echo "RESIDENTS (baranggay)\n";
echo "  ✅ Inserted : $successResident\n";
echo "  ⚠️  Skipped  : $skippedResident\n";
echo "  ❌ Failed   : $failedResident\n";
echo "\nUSERS (baranggay)\n";
echo "  ✅ Inserted : $successUser\n";
echo "  ⚠️  Skipped  : $skippedUser\n";
echo "  ❌ Failed   : $failedUser\n";
echo "==========================================\n";
echo "✅ Migration Complete!\n";