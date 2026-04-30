<?php
/**
 * Reports API Endpoint
 * 
 * Returns report data based on type and date range
 * All calculations are based strictly on actual attendance records from the attendances table
 */

header("Content-Type: application/json");

require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

// Require authentication for reports
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$type = $_GET['type'] ?? 'attendance-department';
$fromDate = $_GET['from'] ?? date('Y-m-01');
$toDate = $_GET['to'] ?? date('Y-m-t');

/**
 * Calculate hours worked from attendance pairs
 * Pairs morning_in with morning_out and afternoon_in with afternoon_out
 * Returns total hours in decimal format
 * Only counts complete pairs (both in and out must exist)
 */
function calculateHoursFromAttendances($attendances) {
    $totalHours = 0.0;
    
    if (empty($attendances)) {
        return 0.0;
    }
    
    // Group by employee and date
    $grouped = [];
    foreach ($attendances as $att) {
        // Get timestamp - prefer timestamp field, fallback to created_at
        $timeValue = $att['timestamp'] ?? $att['created_at'] ?? null;
        if (!$timeValue) {
            continue; // Skip records without valid timestamp
        }
        
        $empId = $att['employee_id'] ?? null;
        if (!$empId) {
            continue; // Skip records without employee_id
        }
        
        // Parse date and timestamp
        $dateTime = strtotime($timeValue);
        if ($dateTime === false) {
            continue; // Skip invalid timestamps
        }
        
        $date = date('Y-m-d', $dateTime);
        $window = strtolower(trim($att['window'] ?? ''));
        if (empty($window)) {
            continue; // Skip records without window
        }
        
        if (!isset($grouped[$empId])) {
            $grouped[$empId] = [];
        }
        if (!isset($grouped[$empId][$date])) {
            $grouped[$empId][$date] = [];
        }
        
        // Store the earliest timestamp for each window (in case of duplicates)
        if (!isset($grouped[$empId][$date][$window]) || $dateTime < $grouped[$empId][$date][$window]) {
            $grouped[$empId][$date][$window] = $dateTime;
        }
    }
    
    // Calculate hours for each employee-date combination
    foreach ($grouped as $empId => $dates) {
        foreach ($dates as $date => $windows) {
            // Morning shift: morning_in to morning_out
            if (isset($windows['morning_in']) && isset($windows['morning_out'])) {
                $morningHours = ($windows['morning_out'] - $windows['morning_in']) / 3600; // Convert seconds to hours
                // Only count positive hours (out must be after in)
                if ($morningHours > 0 && $morningHours < 24) { // Sanity check: less than 24 hours
                    $totalHours += $morningHours;
                }
            }
            
            // Afternoon shift: afternoon_in to afternoon_out
            if (isset($windows['afternoon_in']) && isset($windows['afternoon_out'])) {
                $afternoonHours = ($windows['afternoon_out'] - $windows['afternoon_in']) / 3600; // Convert seconds to hours
                // Only count positive hours (out must be after in)
                if ($afternoonHours > 0 && $afternoonHours < 24) { // Sanity check: less than 24 hours
                    $totalHours += $afternoonHours;
                }
            }
        }
    }
    
    return round($totalHours, 2);
}

try {
    $db = (new Database())->connect();
    $delFilter = SchemaColumnCache::attendancesHasDeletedAt() ? 'a.deleted_at IS NULL AND ' : '';
    $profDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
    $profDbQ = '`' . str_replace('`', '``', $profDb) . '`';

    switch ($type) {
        case 'attendance-position':
            // Attendance - Total Hours by Position
            // Query from both databases: profiling DB for officials and residents, attendance-system for attendance
            // Note: employee_id in attendance-system corresponds to id in profiling-system tables
            $query = "
                SELECT 
                    a.id,
                    a.employee_id,
                    a.timestamp,
                    a.created_at,
                    a.window,
                    COALESCE(ps_off.position, ps_res.occupation, 'N/A') AS position
                FROM attendances a
                LEFT JOIN {$profDbQ}.`barangay_official` ps_off ON a.employee_id = ps_off.id
                LEFT JOIN {$profDbQ}.`residents` ps_res ON a.employee_id = ps_res.id
                WHERE {$delFilter}DATE(COALESCE(a.timestamp, a.created_at)) BETWEEN ? AND ?
                ORDER BY a.employee_id, DATE(COALESCE(a.timestamp, a.created_at)), a.window
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $allAttendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by position and calculate metrics
            $positionData = [];
            
            foreach ($allAttendances as $att) {
                $position = $att['position'] ?? 'N/A';
                
                if (!isset($positionData[$position])) {
                    $positionData[$position] = [
                        'position' => $position,
                        'employees' => [],
                        'attendance_records' => [],
                        'total_attendance' => 0
                    ];
                }
                
                $empId = $att['employee_id'];
                if (!in_array($empId, $positionData[$position]['employees'])) {
                    $positionData[$position]['employees'][] = $empId;
                }
                
                $positionData[$position]['attendance_records'][] = $att;
                $positionData[$position]['total_attendance']++;
            }
            
            // Calculate hours per employee, then aggregate by position
            $results = [];
            foreach ($positionData as $position => $posInfo) {
                $employeeAttendances = [];
                
                foreach ($posInfo['attendance_records'] as $att) {
                    $empId = $att['employee_id'];
                    if (!isset($employeeAttendances[$empId])) {
                        $employeeAttendances[$empId] = [];
                    }
                    $employeeAttendances[$empId][] = $att;
                }
                
                $totalHours = 0.0;
                foreach ($employeeAttendances as $empId => $empAtts) {
                    $empHours = calculateHoursFromAttendances($empAtts);
                    $totalHours += $empHours;
                }
                
                $totalEmployees = count($posInfo['employees']);
                $avgHoursPerEmployee = $totalEmployees > 0 
                    ? round($totalHours / $totalEmployees, 2)
                    : 0.0;
                
                $results[] = [
                    'position' => $position,
                    'total_employees' => $totalEmployees,
                    'total_attendance' => $posInfo['total_attendance'],
                    'total_hours' => round($totalHours, 2),
                    'avg_hours_per_employee' => $avgHoursPerEmployee
                ];
            }
            
            usort($results, function($a, $b) {
                return $b['total_hours'] - $a['total_hours'];
            });
            
            $data = array_map(function($row) {
                return [
                    'position' => $row['position'],
                    'total_employees' => (int)$row['total_employees'],
                    'total_attendance' => (int)$row['total_attendance'],
                    'total_hours' => (float)$row['total_hours'],
                    'avg_hours_per_employee' => (float)$row['avg_hours_per_employee']
                ];
            }, $results);
            
            echo json_encode([
                'success' => true,
                'type' => $type,
                'from' => $fromDate,
                'to' => $toDate,
                'data' => $data
            ]);
            break;
            
        case 'attendance-chairmanship':
            // Attendance - Total Hours by Chairmanship (only barangay officials have chairmanship)
            $query = "
                SELECT 
                    a.id,
                    a.employee_id,
                    a.timestamp,
                    a.created_at,
                    a.window,
                    COALESCE(ps_off.chairmanship, 'N/A') AS chairmanship
                FROM attendances a
                LEFT JOIN {$profDbQ}.`barangay_official` ps_off ON a.employee_id = ps_off.id
                WHERE {$delFilter}DATE(COALESCE(a.timestamp, a.created_at)) BETWEEN ? AND ?
                ORDER BY a.employee_id, DATE(COALESCE(a.timestamp, a.created_at)), a.window
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $allAttendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by chairmanship and calculate metrics
            $chairmanshipData = [];
            
            foreach ($allAttendances as $att) {
                $chairmanship = trim($att['chairmanship'] ?? '');
                if (empty($chairmanship)) {
                    $chairmanship = 'N/A';
                }
                
                if (!isset($chairmanshipData[$chairmanship])) {
                    $chairmanshipData[$chairmanship] = [
                        'chairmanship' => $chairmanship,
                        'employees' => [],
                        'attendance_records' => [],
                        'total_attendance' => 0
                    ];
                }
                
                $empId = $att['employee_id'];
                if (!in_array($empId, $chairmanshipData[$chairmanship]['employees'])) {
                    $chairmanshipData[$chairmanship]['employees'][] = $empId;
                }
                
                $chairmanshipData[$chairmanship]['attendance_records'][] = $att;
                $chairmanshipData[$chairmanship]['total_attendance']++;
            }
            
            // Calculate hours per employee, then aggregate by chairmanship
            $results = [];
            foreach ($chairmanshipData as $chairmanship => $chairInfo) {
                $employeeAttendances = [];
                
                foreach ($chairInfo['attendance_records'] as $att) {
                    $empId = $att['employee_id'];
                    if (!isset($employeeAttendances[$empId])) {
                        $employeeAttendances[$empId] = [];
                    }
                    $employeeAttendances[$empId][] = $att;
                }
                
                $totalHours = 0.0;
                foreach ($employeeAttendances as $empId => $empAtts) {
                    $empHours = calculateHoursFromAttendances($empAtts);
                    $totalHours += $empHours;
                }
                
                $totalEmployees = count($chairInfo['employees']);
                $avgHoursPerEmployee = $totalEmployees > 0 
                    ? round($totalHours / $totalEmployees, 2)
                    : 0.0;
                
                $results[] = [
                    'chairmanship' => $chairmanship,
                    'total_employees' => $totalEmployees,
                    'total_attendance' => $chairInfo['total_attendance'],
                    'total_hours' => round($totalHours, 2),
                    'avg_hours_per_employee' => $avgHoursPerEmployee
                ];
            }
            
            usort($results, function($a, $b) {
                return $b['total_hours'] - $a['total_hours'];
            });
            
            $data = array_map(function($row) {
                return [
                    'chairmanship' => $row['chairmanship'],
                    'total_employees' => (int)$row['total_employees'],
                    'total_attendance' => (int)$row['total_attendance'],
                    'total_hours' => (float)$row['total_hours'],
                    'avg_hours_per_employee' => (float)$row['avg_hours_per_employee']
                ];
            }, $results);
            
            echo json_encode([
                'success' => true,
                'type' => $type,
                'from' => $fromDate,
                'to' => $toDate,
                'data' => $data
            ]);
            break;
            
        case 'attendance-employee':
            // Attendance - Total Hours by Employee (detailed per-employee report)
            $query = "
                SELECT 
                    a.id,
                    a.employee_id,
                    a.timestamp,
                    a.created_at,
                    a.window,
                    COALESCE(
                        CONCAT(ps_off.first_name, ' ', COALESCE(ps_off.middle_name, ''), ' ', ps_off.surname),
                        CONCAT(ps_res.first_name, ' ', COALESCE(ps_res.middle_name, ''), ' ', ps_res.surname),
                        a.employee_id
                    ) AS employee_name,
                    COALESCE(ps_off.position, ps_res.occupation, 'N/A') AS position,
                    COALESCE(ps_off.chairmanship, 'N/A') AS chairmanship
                FROM attendances a
                LEFT JOIN {$profDbQ}.`barangay_official` ps_off ON a.employee_id = ps_off.id
                LEFT JOIN {$profDbQ}.`residents` ps_res ON a.employee_id = ps_res.id
                WHERE {$delFilter}DATE(COALESCE(a.timestamp, a.created_at)) BETWEEN ? AND ?
                ORDER BY a.employee_id, DATE(COALESCE(a.timestamp, a.created_at)), a.window
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $allAttendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by employee
            $employeeData = [];
            
            foreach ($allAttendances as $att) {
                $empId = $att['employee_id'];
                
                if (!isset($employeeData[$empId])) {
                    $employeeData[$empId] = [
                        'employee_id' => $empId,
                        'employee_name' => $att['employee_name'],
                        'position' => $att['position'],
                        'chairmanship' => $att['chairmanship'],
                        'attendance_records' => [],
                        'total_attendance' => 0
                    ];
                }
                
                $employeeData[$empId]['attendance_records'][] = $att;
                $employeeData[$empId]['total_attendance']++;
            }
            
            // Calculate hours for each employee
            $results = [];
            foreach ($employeeData as $empId => $empInfo) {
                $totalHours = calculateHoursFromAttendances($empInfo['attendance_records']);
                
                $results[] = [
                    'employee_id' => $empId,
                    'employee_name' => $empInfo['employee_name'],
                    'position' => $empInfo['position'],
                    'chairmanship' => $empInfo['chairmanship'],
                    'total_attendance' => $empInfo['total_attendance'],
                    'total_hours' => round($totalHours, 2)
                ];
            }
            
            usort($results, function($a, $b) {
                return $b['total_hours'] - $a['total_hours'];
            });
            
            $data = array_map(function($row) {
                return [
                    'employee_id' => $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'position' => $row['position'],
                    'chairmanship' => $row['chairmanship'],
                    'total_attendance' => (int)$row['total_attendance'],
                    'total_hours' => (float)$row['total_hours']
                ];
            }, $results);
            
            echo json_encode([
                'success' => true,
                'type' => $type,
                'from' => $fromDate,
                'to' => $toDate,
                'data' => $data
            ]);
            break;
            
        case 'attendance-daily':
            // Attendance - Daily Attendance Summary
            $query = "
                SELECT 
                    a.id,
                    a.employee_id,
                    a.timestamp,
                    a.created_at,
                    a.window,
                    DATE(COALESCE(a.timestamp, a.created_at)) AS attendance_date,
                    COALESCE(
                        CONCAT(ps_off.first_name, ' ', COALESCE(ps_off.middle_name, ''), ' ', ps_off.surname),
                        CONCAT(ps_res.first_name, ' ', COALESCE(ps_res.middle_name, ''), ' ', ps_res.surname),
                        a.employee_id
                    ) AS employee_name,
                    COALESCE(ps_off.position, ps_res.occupation, 'N/A') AS position,
                    COALESCE(ps_off.chairmanship, 'N/A') AS chairmanship
                FROM attendances a
                LEFT JOIN {$profDbQ}.`barangay_official` ps_off ON a.employee_id = ps_off.id
                LEFT JOIN {$profDbQ}.`residents` ps_res ON a.employee_id = ps_res.id
                WHERE {$delFilter}DATE(COALESCE(a.timestamp, a.created_at)) BETWEEN ? AND ?
                ORDER BY attendance_date DESC, a.employee_id, a.window
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $allAttendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by date and employee for detailed daily view
            $dailyData = [];
            
            foreach ($allAttendances as $att) {
                $date = $att['attendance_date'];
                $empId = $att['employee_id'];
                $key = $date . '_' . $empId;
                
                if (!isset($dailyData[$key])) {
                    $dailyData[$key] = [
                        'date' => $date,
                        'employee_id' => $empId,
                        'employee_name' => $att['employee_name'],
                        'position' => $att['position'],
                        'chairmanship' => $att['chairmanship'],
                        'attendance_records' => [],
                        'time_in' => null,
                        'time_out' => null
                    ];
                }
                
                $dailyData[$key]['attendance_records'][] = $att;
                
                // Track first time in and last time out
                $timeValue = $att['timestamp'] ?? $att['created_at'] ?? null;
                if ($timeValue) {
                    $window = strtolower(trim($att['window'] ?? ''));
                    if (strpos($window, '_in') !== false) {
                        if (!$dailyData[$key]['time_in'] || $timeValue < $dailyData[$key]['time_in']) {
                            $dailyData[$key]['time_in'] = $timeValue;
                        }
                    }
                    if (strpos($window, '_out') !== false) {
                        if (!$dailyData[$key]['time_out'] || $timeValue > $dailyData[$key]['time_out']) {
                            $dailyData[$key]['time_out'] = $timeValue;
                        }
                    }
                }
            }
            
            // Calculate hours for each day-employee combination
            $results = [];
            foreach ($dailyData as $key => $dayInfo) {
                $totalHours = calculateHoursFromAttendances($dayInfo['attendance_records']);
                
                $results[] = [
                    'date' => $dayInfo['date'],
                    'employee_name' => $dayInfo['employee_name'],
                    'position' => $dayInfo['position'],
                    'chairmanship' => $dayInfo['chairmanship'],
                    'time_in' => $dayInfo['time_in'] ? date('g:i A', strtotime($dayInfo['time_in'])) : 'N/A',
                    'time_out' => $dayInfo['time_out'] ? date('g:i A', strtotime($dayInfo['time_out'])) : 'N/A',
                    'total_hours' => round($totalHours, 2)
                ];
            }
            
            // Already sorted by date DESC in query
            
            $data = array_map(function($row) {
                return [
                    'date' => $row['date'],
                    'employee_name' => $row['employee_name'],
                    'position' => $row['position'],
                    'chairmanship' => $row['chairmanship'],
                    'time_in' => $row['time_in'],
                    'time_out' => $row['time_out'],
                    'total_hours' => (float)$row['total_hours']
                ];
            }, $results);
            
            echo json_encode([
                'success' => true,
                'type' => $type,
                'from' => $fromDate,
                'to' => $toDate,
                'data' => $data
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid report type"]);
            break;
    }
    
} catch (Throwable $e) {
    error_log('reports/index.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Server error",
        "message" => $e->getMessage(),
    ]);
}
