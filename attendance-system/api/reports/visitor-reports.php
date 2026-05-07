<?php
/**
 * Visitor Reports API Endpoint
 * 
 * Returns visitor report data based on type and date range
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

$type = $_GET['type'] ?? 'total-visitors';
$fromDate = $_GET['from'] ?? date('Y-m-01');
$toDate = $_GET['to'] ?? date('Y-m-t');

try {
    $db = (new Database())->connect();
    
    switch ($type) {
        case 'total-visitors':
            // Total visitors by month/day
            $query = "
                SELECT 
                    DATE(created_at) as visit_date,
                    COUNT(*) as visitor_count
                FROM visitor_logs
                WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY visit_date ASC
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = array_map(function($row) {
                return [
                    'date' => $row['visit_date'],
                    'count' => (int)$row['visitor_count']
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
            
        case 'services-availed':
            // Services availed by visitors (purpose field)
            $query = "
                SELECT 
                    purpose as service,
                    COUNT(*) as count
                FROM visitor_logs
                WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY purpose
                ORDER BY count DESC
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = array_map(function($row) {
                return [
                    'service' => $row['service'] ?: 'Not Specified',
                    'count' => (int)$row['count']
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
            
        case 'visitor-types':
            // Types of visitors: Residents vs Non-Residents
            $query = "
                SELECT 
                    CASE 
                        WHEN is_resident = 1 THEN 'Residents'
                        ELSE 'Non-Residents'
                    END as visitor_type,
                    COUNT(*) as count
                FROM visitor_logs
                WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY is_resident
                ORDER BY is_resident DESC
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = array_map(function($row) {
                return [
                    'type' => $row['visitor_type'],
                    'count' => (int)$row['count']
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
            
        case 'appointment-types':
            // Appointment types: Online vs Walk-in
            $query = "
                SELECT 
                    CASE 
                        WHEN had_booking = 1 THEN 'Online Appointment'
                        ELSE 'Walk-in'
                    END as appointment_type,
                    COUNT(*) as count
                FROM visitor_logs
                WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY had_booking
                ORDER BY had_booking DESC
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = array_map(function($row) {
                return [
                    'type' => $row['appointment_type'],
                    'count' => (int)$row['count']
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
            
        case 'gender-distribution':
            // Gender distribution (join with profiling-system residents for resident visitors)
            $query = "
                SELECT 
                    COALESCE(r.sex, 'Unknown') as gender,
                    COUNT(*) as count
                FROM visitor_logs vl
                LEFT JOIN `" . PROFILING_DB_NAME . "`.`residents` r ON vl.resident_id = r.id AND vl.is_resident = 1
                WHERE vl.deleted_at IS NULL AND DATE(vl.created_at) BETWEEN ? AND ?
                GROUP BY COALESCE(r.sex, 'Unknown')
                ORDER BY count DESC
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = array_map(function($row) {
                return [
                    'gender' => $row['gender'],
                    'count' => (int)$row['count']
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
            
        case 'age-services':
            // Age groups and their services availed
            $query = "
                SELECT 
                    CASE 
                        WHEN vl.birthdate IS NOT NULL THEN
                            CASE 
                                WHEN TIMESTAMPDIFF(YEAR, vl.birthdate, CURDATE()) < 18 THEN 'Under 18'
                                WHEN TIMESTAMPDIFF(YEAR, vl.birthdate, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                                WHEN TIMESTAMPDIFF(YEAR, vl.birthdate, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                                WHEN TIMESTAMPDIFF(YEAR, vl.birthdate, CURDATE()) BETWEEN 36 AND 50 THEN '36-50'
                                WHEN TIMESTAMPDIFF(YEAR, vl.birthdate, CURDATE()) BETWEEN 51 AND 65 THEN '51-65'
                                ELSE '65+'
                            END
                        ELSE 'Unknown'
                    END as age_group,
                    COALESCE(vl.purpose, 'Not Specified') as service,
                    COUNT(*) as count
                FROM visitor_logs vl
                WHERE vl.deleted_at IS NULL AND DATE(vl.created_at) BETWEEN ? AND ?
                GROUP BY age_group, service
                ORDER BY age_group, count DESC
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$fromDate, $toDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group by age group
            $grouped = [];
            foreach ($results as $row) {
                $ageGroup = $row['age_group'];
                if (!isset($grouped[$ageGroup])) {
                    $grouped[$ageGroup] = [];
                }
                $grouped[$ageGroup][] = [
                    'service' => $row['service'],
                    'count' => (int)$row['count']
                ];
            }
            
            // Convert to array format
            $data = [];
            foreach ($grouped as $ageGroup => $services) {
                $data[] = [
                    'age_group' => $ageGroup,
                    'services' => $services
                ];
            }
            
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
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Server error",
        "message" => $e->getMessage()
    ]);
}
