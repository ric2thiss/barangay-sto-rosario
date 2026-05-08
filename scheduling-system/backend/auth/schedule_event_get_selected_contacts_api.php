<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

function out(bool $success, array $data = [], string $message = ''): void {
  echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message]);
  exit;
}

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  out(false, [], 'Unauthorized.');
}

$eventId = (int)($_GET['event_id'] ?? 0);
if ($eventId <= 0) {
  out(false, [], 'Invalid event_id.');
}

try {
  $stmt = $pdo->prepare("
    SELECT DISTINCT contact_id
    FROM (
      SELECT contact_id
      FROM schedule_participants
      WHERE schedule_event_id = ?

      UNION

      SELECT contact_id
      FROM sms_outbox
      WHERE schedule_event_id = ?
        AND contact_id IS NOT NULL
    ) picked
    WHERE contact_id IS NOT NULL
  ");
  $stmt->execute([$eventId, $eventId]);

  $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
  out(true, $ids);
} catch (Throwable $e) {
  out(false, [], 'Server error.');
}
