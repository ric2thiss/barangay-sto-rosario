<?php
/**
 * API Endpoint: Pending certificate / blotter requests for visitor check-in
 * GET /api/visitors/check-booking.php?resident_id={id}
 *
 * Reads barangay_services2.certificate_requests and blotter_records (replaces online_appointment).
 * Returns up to 2 most recent Pending requests combined.
 */
require_once __DIR__ . "/../../bootstrap.php";

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$residentId = $_GET['resident_id'] ?? null;

if (empty($residentId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'resident_id parameter is required'
    ]);
    exit;
}

$rid = (int) $residentId;
$dbName = defined('BARANGAY_SERVICES2_DB_NAME') ? BARANGAY_SERVICES2_DB_NAME : 'barangay_services2';

/**
 * @param array $req
 * @return array<string,mixed>
 */
function format_booking_from_request(array $req): array
{
    $created = $req['created_at'] ?? null;
    $ts = $created ? strtotime((string) $created) : false;
    return [
        'booking_id' => ($req['type'] === 'certificate' ? 'cert:' : 'blotter:') . $req['id'],
        'request_type' => $req['type'],
        'request_id' => $req['id'],
        'service_name' => $req['service_name'] ?? 'Service',
        'purpose' => $req['purpose'] ?? '',
        'appointment_date' => $ts ? date('Y-m-d', $ts) : null,
        'appointment_time' => $ts ? date('H:i:s', $ts) : null,
        'status' => 'Pending',
        'notes' => ''
    ];
}

try {
    $db = (new Database())->connect();
    $q = '`' . str_replace('`', '', $dbName) . '`';

    // Pending certificate requests (join type name)
    $sqlCert = "
        SELECT
            cr.request_id AS id,
            cr.resident_id,
            cr.purpose,
            cr.status,
            cr.created_at,
            ct.certificate_name
        FROM {$q}.`certificate_requests` cr
        LEFT JOIN {$q}.`certificate_types` ct ON cr.certificate_type_id = ct.certificate_type_id
        WHERE cr.resident_id = :rid
          AND LOWER(TRIM(cr.status)) = 'pending'
        ORDER BY cr.created_at DESC, cr.request_id DESC
    ";
    $stmt = $db->prepare($sqlCert);
    $stmt->execute([':rid' => $rid]);
    $certRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending blotter where resident is complainant or respondent
    $sqlBlot = "
        SELECT
            br.blotter_id AS id,
            br.complainant_id,
            br.respondent_id,
            br.purpose,
            br.incident_type,
            br.status,
            br.created_at
        FROM {$q}.`blotter_records` br
        WHERE LOWER(TRIM(br.status)) = 'pending'
          AND (br.complainant_id = :rid OR br.respondent_id = :rid)
        ORDER BY br.created_at DESC, br.blotter_id DESC
    ";
    $stmtB = $db->prepare($sqlBlot);
    $stmtB->execute([':rid' => $rid]);
    $blotRows = $stmtB->fetchAll(PDO::FETCH_ASSOC);

    $unified = [];

    foreach ($certRows as $row) {
        $name = !empty($row['certificate_name']) ? $row['certificate_name'] : ($row['purpose'] ?: 'Certificate');
        $unified[] = [
            'type' => 'certificate',
            'id' => (int) $row['id'],
            'service_name' => $name,
            'purpose' => $row['purpose'] ?? '',
            'status' => $row['status'] ?? 'Pending',
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    foreach ($blotRows as $row) {
        $label = 'Blotter';
        if (!empty($row['incident_type'])) {
            $label .= ': ' . $row['incident_type'];
        }
        $unified[] = [
            'type' => 'blotter',
            'id' => (int) $row['id'],
            'service_name' => $label,
            'purpose' => $row['purpose'] ?? ($row['incident_type'] ?? 'Blotter'),
            'status' => $row['status'] ?? 'Pending',
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    usort($unified, static function ($a, $b) {
        $ta = isset($a['created_at']) ? strtotime((string) $a['created_at']) : 0;
        $tb = isset($b['created_at']) ? strtotime((string) $b['created_at']) : 0;
        return $tb <=> $ta;
    });

    $pendingRequests = array_slice($unified, 0, 2);

    // History: any non-pending certificate for this resident
    $sqlHistCert = "
        SELECT COUNT(*) FROM {$q}.`certificate_requests`
        WHERE resident_id = :rid AND LOWER(TRIM(status)) <> 'pending'
    ";
    $h1 = $db->prepare($sqlHistCert);
    $h1->execute([':rid' => $rid]);
    $histCert = (int) $h1->fetchColumn();

    // History: any blotter row for this resident with status other than pending
    $sqlHistBlot = "
        SELECT COUNT(*) FROM {$q}.`blotter_records`
        WHERE (complainant_id = :rid OR respondent_id = :rid)
          AND LOWER(TRIM(status)) <> 'pending'
    ";
    $h2 = $db->prepare($sqlHistBlot);
    $h2->execute([':rid' => $rid]);
    $histBlot = (int) $h2->fetchColumn();

    // Ever interacted (any row), including pending — used to distinguish walk-in vs completed-only
    $sqlAnyCert = "SELECT COUNT(*) FROM {$q}.`certificate_requests` WHERE resident_id = :rid";
    $ac = $db->prepare($sqlAnyCert);
    $ac->execute([':rid' => $rid]);
    $anyCert = (int) $ac->fetchColumn();

    $sqlAnyBlot = "
        SELECT COUNT(*) FROM {$q}.`blotter_records`
        WHERE complainant_id = :rid OR respondent_id = :rid
    ";
    $ab = $db->prepare($sqlAnyBlot);
    $ab->execute([':rid' => $rid]);
    $anyBlot = (int) $ab->fetchColumn();

    $hasPending = count($pendingRequests) > 0;
    $anyHistory = ($anyCert + $anyBlot) > 0;
    $completedOnly = !$hasPending && $anyHistory && ($histCert + $histBlot) > 0;
    $walkIn = !$hasPending && !$anyHistory;

    $firstBooking = null;
    if ($hasPending) {
        $firstBooking = format_booking_from_request($pendingRequests[0]);
    }

    echo json_encode([
        'success' => true,
        'has_pending' => $hasPending,
        'pending_count' => count($pendingRequests),
        'pending_requests' => $pendingRequests,
        // Backward compatibility for older clients
        'has_booking' => $hasPending,
        'booking' => $firstBooking,
        'completed_only' => $completedOnly,
        'walk_in' => $walkIn,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to check pending requests',
        'message' => $e->getMessage()
    ]);
}
