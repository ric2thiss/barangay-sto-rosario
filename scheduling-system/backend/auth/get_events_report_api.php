<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
  exit;
}

// Filters
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$type     = trim($_GET['type']      ?? '');
$status   = trim($_GET['status']    ?? '');

// Default: current month
if ($dateFrom === '') $dateFrom = date('Y-m-01');
if ($dateTo   === '') $dateTo   = date('Y-m-t');

try {
  // Build WHERE for events
  $where  = ['DATE(se.start_datetime) BETWEEN :df AND :dt'];
  $params = [':df' => $dateFrom, ':dt' => $dateTo];

  if ($type !== '') {
    $where[]        = 'se.schedule_type = :type';
    $params[':type'] = $type;
  }
  if ($status !== '') {
    $where[]          = 'se.status = :status';
    $params[':status'] = $status;
  }

  $whereStr = 'WHERE ' . implode(' AND ', $where);

  // Fetch events
  $evStmt = $pdo->prepare("
    SELECT
      se.id,
      se.title,
      se.schedule_type,
      se.description,
      se.location,
      se.start_datetime,
      se.end_datetime,
      se.status,
      u.full_name AS created_by,
      (
        SELECT COUNT(*) FROM sms_outbox so
        WHERE so.schedule_event_id = se.id AND so.status = 'sent'
      ) AS sms_sent,
      (
        SELECT COUNT(*) FROM sms_outbox so
        WHERE so.schedule_event_id = se.id AND so.status = 'failed'
      ) AS sms_failed,
      (
        SELECT md.status FROM minutes_documents md
        WHERE md.schedule_event_id = se.id
        ORDER BY md.created_at DESC LIMIT 1
      ) AS minutes_status
    FROM schedule_events se
    LEFT JOIN users u ON u.id = se.created_by
    $whereStr
    ORDER BY se.start_datetime ASC
  ");
  $evStmt->execute($params);
  $events = $evStmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($events)) {
    echo json_encode([
      'success' => true,
      'events'  => [],
      'summary' => ['total_events' => 0, 'total_agendas' => 0, 'done_agendas' => 0, 'total_sms_sent' => 0],
    ]);
    exit;
  }

  // Fetch all agendas for these events in one query
  $eventIds = array_column($events, 'id');
  $in       = implode(',', array_fill(0, count($eventIds), '?'));
  $agStmt   = $pdo->prepare("
    SELECT
      schedule_event_id,
      agenda_no,
      agenda_title,
      agenda_details,
      status
    FROM event_agendas
    WHERE schedule_event_id IN ($in)
    ORDER BY schedule_event_id, agenda_no ASC
  ");
  $agStmt->execute($eventIds);
  $allAgendas = $agStmt->fetchAll(PDO::FETCH_ASSOC);

  // Group agendas by event id
  $agendasByEvent = [];
  foreach ($allAgendas as $ag) {
    $agendasByEvent[(int)$ag['schedule_event_id']][] = $ag;
  }

  // Attach agendas to events
  foreach ($events as &$ev) {
    $ev['agendas'] = $agendasByEvent[(int)$ev['id']] ?? [];
  }
  unset($ev);

  // Summary
  $totalAgendas = count($allAgendas);
  $doneAgendas  = count(array_filter($allAgendas, fn($a) => $a['status'] === 'done'));
  $totalSmsSent = (int)array_sum(array_column($events, 'sms_sent'));

  echo json_encode([
    'success' => true,
    'events'  => $events,
    'summary' => [
      'total_events'  => count($events),
      'total_agendas' => $totalAgendas,
      'done_agendas'  => $doneAgendas,
      'total_sms_sent'=> $totalSmsSent,
    ],
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.', 'error' => 'Server error.']);
}
