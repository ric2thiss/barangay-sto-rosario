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

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$outboxId = (int)($input['outbox_id'] ?? 0);

if ($outboxId <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid outbox ID.']);
  exit;
}

try {
  // Fetch the original outbox row
  $stmt = $pdo->prepare("
    SELECT id, schedule_event_id, contact_id, to_mobile, message
    FROM sms_outbox
    WHERE id = ?
    LIMIT 1
  ");
  $stmt->execute([$outboxId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(['success' => false, 'message' => 'SMS record not found.']);
    exit;
  }

  // Send via Mocean
  $result    = mocean_send_sms((string)$row['to_mobile'], (string)$row['message']);
  $rowStatus = $result['success'] ? 'sent' : 'failed';

  // Log a new resend row in sms_outbox
  $ins = $pdo->prepare("
    INSERT INTO sms_outbox
      (schedule_event_id, contact_id, to_mobile, message, send_at, send_type, status, queued_at, sent_at)
    VALUES (?, ?, ?, ?, NOW(), 'immediate', ?, NOW(), ?)
  ");
  $ins->execute([
    $row['schedule_event_id'],
    $row['contact_id'],
    $row['to_mobile'],
    $row['message'],
    $rowStatus,
    $result['success'] ? date('Y-m-d H:i:s') : null,
  ]);

  if (!$result['success']) {
    echo json_encode(['success' => false, 'message' => 'SMS failed to send.', 'error' => $result['error'] ?? '']);
    exit;
  }

  echo json_encode(['success' => true, 'message' => 'SMS resent successfully.']);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.', 'error' => 'Server error.']);
}
