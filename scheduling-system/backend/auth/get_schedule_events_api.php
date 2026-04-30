<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/conn.php';

// Must be logged in
if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode([]);
  exit;
}

// FullCalendar sends ?start=YYYY-MM-DD...&end=YYYY-MM-DD...
$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;

try {

  if ($start && $end) {
    // Overlapping range query
    $stmt = $pdo->prepare("
      SELECT id, title, start_datetime, end_datetime, location, status
      FROM schedule_events
      WHERE start_datetime < ? AND end_datetime > ?
      ORDER BY start_datetime ASC
    ");
    $stmt->execute([$end, $start]);
  } else {
    $stmt = $pdo->query("
      SELECT id, title, start_datetime, end_datetime, location, status
      FROM schedule_events
      ORDER BY start_datetime DESC
      LIMIT 200
    ");
  }

  $rows = $stmt->fetchAll();

  $events = array_map(function ($row) {
    return [
      'id'    => (string)$row['id'],
      'title' => $row['title'],
      'start' => date('c', strtotime($row['start_datetime'])),
      'end'   => date('c', strtotime($row['end_datetime'])),
      'extendedProps' => [
        'location' => $row['location'],
        'status'   => $row['status']
      ]
    ];
  }, $rows);

  echo json_encode($events);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([]);
}