<?php
/**
 * Seed attendances for attendance-system using the first N barangay officials
 * from profiling-system (same IDs as attendances.employee_id).
 *
 * Usage (from project root):
 *   php database/seed_attendance_barangay_officials.php
 *   php database/seed_attendance_barangay_officials.php --people=10 --days=5
 *
 * Inserts, per person per weekday: morning_in, morning_out, afternoon_in, afternoon_out
 * (4 rows per day). Re-running inserts additional rows (no idempotency).
 */

declare(strict_types=1);

$base = dirname(__DIR__);
require_once $base . '/bootstrap.php';

$people = 10;
$days = 5;
foreach ($argv as $arg) {
    if (preg_match('/^--people=(\d+)$/', $arg, $m)) {
        $people = max(1, (int) $m[1]);
    }
    if (preg_match('/^--days=(\d+)$/', $arg, $m)) {
        $days = max(1, (int) $m[1]);
    }
}

$pdo = (new Database())->connect();
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$prof = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';

$stmt = $pdo->prepare(
    "SELECT id FROM `" . str_replace('`', '``', $prof) . "`.`barangay_official` ORDER BY id ASC LIMIT " . (int) $people
);
$stmt->execute();
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (!is_array($ids) || count($ids) === 0) {
    fwrite(STDERR, "No rows in `{$prof}`.`barangay_official`. Nothing to seed.\n");
    exit(1);
}

if (count($ids) < $people) {
    fwrite(STDERR, "Warning: only " . count($ids) . " official(s) found; seeding those.\n");
}

$tz = new DateTimeZone('Asia/Manila');
$today = new DateTime('today', $tz);
$candidate = clone $today;
$oldest = (clone $today)->modify('-200 days');
$weekdayDates = [];
while (count($weekdayDates) < $days) {
    $dow = (int) $candidate->format('N'); // 1 = Mon .. 7 = Sun
    if ($dow >= 1 && $dow <= 5) {
        $weekdayDates[] = $candidate->format('Y-m-d');
    }
    $candidate->modify('-1 day');
    if ($candidate < $oldest) {
        fwrite(STDERR, "Could not collect {$days} weekdays in range.\n");
        exit(1);
    }
}

$windows = [
    ['morning_in', '08:05:00'],
    ['morning_out', '12:00:00'],
    ['afternoon_in', '13:05:00'],
    ['afternoon_out', '17:00:00'],
];

$hasDeletedAt = false;
try {
    $chk = $pdo->query("SHOW COLUMNS FROM `attendances` LIKE 'deleted_at'");
    $hasDeletedAt = $chk && $chk->rowCount() > 0;
} catch (Throwable $e) {
    // ignore
}

if ($hasDeletedAt) {
    $sql = 'INSERT INTO `attendances` (`employee_id`, `timestamp`, `created_at`, `updated_at`, `window`, `activity_id`, `deleted_at`) VALUES (?,?,?,?,?,?,NULL)';
} else {
    $sql = 'INSERT INTO `attendances` (`employee_id`, `timestamp`, `created_at`, `updated_at`, `window`, `activity_id`) VALUES (?,?,?,?,?,NULL)';
}

$insert = $pdo->prepare($sql);
$count = 0;

foreach ($ids as $eid) {
    $eidStr = (string) $eid;
    foreach ($weekdayDates as $date) {
        foreach ($windows as [$win, $hm]) {
            $ts = $date . ' ' . $hm;
            $insert->execute([$eidStr, $ts, $ts, $ts, $win]);
            $count++;
        }
    }
}

echo "Seeded {$count} attendance row(s) for " . count($ids) . " official(s), {$days} weekday(s) each, " . count($windows) . " window(s) per day.\n";
echo "Profiling DB: {$prof}, dates: " . implode(', ', $weekdayDates) . "\n";
