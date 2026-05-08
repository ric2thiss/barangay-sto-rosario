<?php
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

/**
 * GET /api/dtr/employee-attendance.php?employee_id={id}&from={date}&to={date}
 * Get attendance records for a specific employee within a date range
 */
if ($method === "GET") {
    $employeeId = $_GET["employee_id"] ?? null;
    $fromDate = $_GET["from"] ?? null;
    $toDate = $_GET["to"] ?? null;

    if (empty($employeeId)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Employee ID is required"
        ]);
        exit;
    }

    try {
        $db = (new Database())->connect();
        $profilingDbName = defined("PROFILING_DB_NAME") ? PROFILING_DB_NAME : "profiling-system";
        $profDbQ = '`' . str_replace('`', '``', $profilingDbName) . '`';
        $delFilter = SchemaColumnCache::attendancesHasDeletedAt() ? 'a.deleted_at IS NULL AND ' : '';

        // Use the provided employee_id directly.
        // We intentionally do NOT require the employee to be enrolled in employee_fingerprints
        // so DTR can still be viewed for employees with attendance logs but no stored template.
        $employeeIdValue = (string) $employeeId;

        $employeeName = '';
        try {
            $stmt = $db->prepare("
                SELECT bo.first_name, bo.middle_name, bo.surname AS last_name
                FROM {$profDbQ}.`barangay_official` AS bo
                WHERE bo.id = ?
                LIMIT 1
            ");
            $stmt->execute([(string) $employeeIdValue]);
            $bo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($bo) {
                $firstName = $bo['first_name'] ?? '';
                $middleName = $bo['middle_name'] ?? '';
                $lastName = $bo['last_name'] ?? '';
                $employeeName = trim($firstName . ' ' . ($middleName ? ($middleName . ' ') : '') . $lastName);
            }
        } catch (Throwable $e) {
            // keep empty name on failure
        }

        // Build date filter
        $dateFilter = '';
        $params = [$employeeIdValue];
        
        if ($fromDate && $toDate) {
            $dateFilter = " AND DATE(a.timestamp) >= ? AND DATE(a.timestamp) <= ?";
            $params[] = $fromDate;
            $params[] = $toDate;
        } elseif ($fromDate) {
            $dateFilter = " AND DATE(a.timestamp) >= ?";
            $params[] = $fromDate;
        } elseif ($toDate) {
            $dateFilter = " AND DATE(a.timestamp) <= ?";
            $params[] = $toDate;
        }

        // Fetch attendance records
        $stmt = $db->prepare("
            SELECT 
                a.id,
                a.employee_id,
                a.timestamp,
                a.created_at,
                a.window,
                COALESCE(aw.label, a.window) AS window_label
            FROM attendances a
            LEFT JOIN attendance_windows aw ON LOWER(TRIM(a.window)) = LOWER(TRIM(aw.label))
            WHERE {$delFilter}a.employee_id = ?
            $dateFilter
            ORDER BY a.timestamp ASC
        ");
        
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group records by date and organize by attendance cycle
        $groupedByDate = [];
        $anomalies = [];

        $normalizeWindow = function ($value): string {
            $v = strtolower(trim((string) $value));
            // Make matching resilient to legacy values like "Morning In" / "morning in" / "morning-in"
            $v = str_replace([' ', '-'], '_', $v);
            // Collapse repeated underscores
            $v = preg_replace('/_+/', '_', $v);
            return $v ?: '';
        };

        foreach ($records as $record) {
            $date = date('Y-m-d', strtotime($record['timestamp']));
            $window = $normalizeWindow($record['window_label'] ?? ($record['window'] ?? ''));
            
            if (!isset($groupedByDate[$date])) {
                $groupedByDate[$date] = [
                    'date' => $date,
                    'morning_in' => null,
                    'morning_out' => null,
                    'afternoon_in' => null,
                    'afternoon_out' => null,
                ];
            }

            // Assign to appropriate window
            if ($window === 'morning_in') {
                $groupedByDate[$date]['morning_in'] = $record;
            } elseif ($window === 'morning_out') {
                $groupedByDate[$date]['morning_out'] = $record;
            } elseif ($window === 'afternoon_in') {
                $groupedByDate[$date]['afternoon_in'] = $record;
            } elseif ($window === 'afternoon_out') {
                $groupedByDate[$date]['afternoon_out'] = $record;
            }
        }

        // Calculate total hours and detect anomalies for each day
        foreach ($groupedByDate as $date => &$dayData) {
            $morningIn = $dayData['morning_in'];
            $morningOut = $dayData['morning_out'];
            $afternoonIn = $dayData['afternoon_in'];
            $afternoonOut = $dayData['afternoon_out'];

            // Calculate total hours
            $totalHours = 0;
            $totalMinutes = 0;

            if ($morningIn && $morningOut) {
                $morningInTime = strtotime($morningIn['timestamp']);
                $morningOutTime = strtotime($morningOut['timestamp']);
                $morningDiff = $morningOutTime - $morningInTime;
                $totalMinutes += round($morningDiff / 60);
            }

            if ($afternoonIn && $afternoonOut) {
                $afternoonInTime = strtotime($afternoonIn['timestamp']);
                $afternoonOutTime = strtotime($afternoonOut['timestamp']);
                $afternoonDiff = $afternoonOutTime - $afternoonInTime;
                $totalMinutes += round($afternoonDiff / 60);
            }

            $totalHours = floor($totalMinutes / 60);
            $remainingMinutes = $totalMinutes % 60;

            $dayData['total_hours'] = $totalHours;
            $dayData['total_minutes'] = $remainingMinutes;
            $dayData['total_hours_decimal'] = round($totalMinutes / 60, 2);

            // Detect anomalies
            $dayAnomalies = [];
            if (!$morningIn) {
                $dayAnomalies[] = 'Missing Morning In';
            }
            if (!$morningOut) {
                $dayAnomalies[] = 'Missing Morning Out';
            }
            if (!$afternoonIn) {
                $dayAnomalies[] = 'Missing Afternoon In';
            }
            if (!$afternoonOut) {
                $dayAnomalies[] = 'Missing Afternoon Out';
            }

            // Check for invalid cycles (e.g., morning_out without morning_in)
            if ($morningOut && !$morningIn) {
                $dayAnomalies[] = 'Morning Out without Morning In';
            }
            if ($afternoonIn && !$morningOut) {
                $dayAnomalies[] = 'Afternoon In without Morning Out';
            }
            if ($afternoonOut && !$afternoonIn) {
                $dayAnomalies[] = 'Afternoon Out without Afternoon In';
            }

            if (!empty($dayAnomalies)) {
                $anomalies[] = [
                    'date' => $date,
                    'anomalies' => $dayAnomalies
                ];
            }

            $dayData['anomalies'] = $dayAnomalies;
            $dayData['status'] = empty($dayAnomalies) ? 'Complete' : 'Incomplete';
        }

        // Convert to indexed array
        $attendanceData = array_values($groupedByDate);

        // Calculate statistics for charts
        $completeCount = 0;
        $incompleteCount = 0;
        $anomalousCount = 0;

        foreach ($attendanceData as $day) {
            if (empty($day['anomalies'])) {
                $completeCount++;
            } else {
                $incompleteCount++;
                $anomalousCount++;
            }
        }

        echo json_encode([
            "success" => true,
            "employee_id" => $employeeIdValue,
            "employee_name" => $employeeName,
            "attendance_data" => $attendanceData,
            "anomalies" => $anomalies,
            "statistics" => [
                "total_days" => count($attendanceData),
                "complete" => $completeCount,
                "incomplete" => $incompleteCount,
                "anomalous" => $anomalousCount
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    } catch (Throwable $e) {
        error_log('employee-attendance: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Server error",
            "message" => $e->getMessage(),
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
