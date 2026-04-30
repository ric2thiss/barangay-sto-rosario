<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';
require_once __DIR__ . '/../config/sms.php';

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
  exit;
}

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$eventId = (int)($input['event_id'] ?? 0);

if ($eventId <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid event ID.']);
  exit;
}

try {
  // Get the event's current SMS message
  $evStmt = $pdo->prepare("SELECT sms_message FROM schedule_events WHERE id = ? LIMIT 1");
  $evStmt->execute([$eventId]);
  $event = $evStmt->fetch(PDO::FETCH_ASSOC);

  if (!$event || empty($event['sms_message'])) {
    echo json_encode(['success' => false, 'message' => 'No SMS message found for this event.']);
    exit;
  }

  $message = (string)$event['sms_message'];

  // Get distinct mobiles from the outbox for this event
  $cStmt = $pdo->prepare("
    SELECT DISTINCT to_mobile
    FROM sms_outbox
    WHERE schedule_event_id = ?
      AND to_mobile IS NOT NULL
      AND to_mobile <> ''
  ");
  $cStmt->execute([$eventId]);
  $mobiles = $cStmt->fetchAll(PDO::FETCH_COLUMN);

  if (empty($mobiles)) {
    echo json_encode(['success' => false, 'message' => 'No recipients found for this event.']);
    exit;
  }

  // Insert new outbox rows for the resend with send_type='immediate'
  $insSms = $pdo->prepare("
    INSERT INTO sms_outbox
      (schedule_event_id, contact_id, to_mobile, message, send_at, send_type, status, queued_at)
    VALUES (?, NULL, ?, ?, NOW(), 'immediate', ?, NOW())
  ");

  $sentCount = 0;
  foreach ($mobiles as $to) {
    $result    = mocean_send_sms((string)$to, $message);
    $rowStatus = $result['success'] ? 'sent' : 'failed';
    if ($result['success']) $sentCount++;
    $insSms->execute([$eventId, $to, $message, $rowStatus]);
  }

  if ($sentCount === 0) {
    echo json_encode(['success' => false, 'message' => 'SMS failed to send to all recipients.']);
    exit;
  }

  echo json_encode([
    'success'  => true,
    'message'  => "SMS resent to {$sentCount} recipient(s).",
    'sms_sent' => $sentCount,
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.', 'error' => 'Server error.']);
}
