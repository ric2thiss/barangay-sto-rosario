<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/conn.php';

function out(bool $success, string $message = ''): void {
  echo json_encode(['success' => $success, 'message' => $message]);
  exit;
}

// ✅ Require login
if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  out(false, 'Unauthorized.');
}

// ✅ POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  out(false, 'Method not allowed.');
}

// ✅ Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$eventId = (int)($data['event_id'] ?? 0);

if ($eventId <= 0) {
  http_response_code(400);
  out(false, 'Invalid event ID.');
}

try {
  $pdo->beginTransaction();

  // 1) Delete SMS records first
  $pdo->prepare("
      DELETE FROM sms_outbox
      WHERE schedule_event_id = ?
  ")->execute([$eventId]);

  // 2) Delete schedule event
  $stmt = $pdo->prepare("
      DELETE FROM schedule_events
      WHERE id = ?
      LIMIT 1
  ");
  $stmt->execute([$eventId]);

  if ($stmt->rowCount() === 0) {
      $pdo->rollBack();
      out(false, 'Schedule not found or already deleted.');
  }

  // 3) Optional: log activity
  $pdo->prepare("
      INSERT INTO activity_logs
        (user_id, action, entity_type, entity_id, description)
      VALUES (?, 'delete', 'schedule_events', ?, ?)
  ")->execute([
      (int)$_SESSION['user_id'],
      $eventId,
      "Deleted schedule event ID: {$eventId}",
  ]);

  $pdo->commit();

  out(true, 'Schedule deleted successfully.');

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  out(false, 'Server error.');
}