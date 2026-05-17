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

try {
  // Fetch all queued outbox rows whose send_at has passed
  $stmt = $pdo->prepare("
    SELECT id, to_mobile, message
    FROM sms_outbox
    WHERE status = 'queued'
      AND send_at <= NOW()
    ORDER BY send_at ASC
    LIMIT 50
  ");
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($rows)) {
    echo json_encode(['success' => true, 'processed' => 0]);
    exit;
  }

  $stmtUpdate = $pdo->prepare("
    UPDATE sms_outbox
    SET status = ?, sent_at = NOW()
    WHERE id = ?
  ");

  $processed = 0;
  foreach ($rows as $row) {
    $result = mocean_send_sms((string)$row['to_mobile'], (string)$row['message']);
    $newStatus = $result['success'] ? 'sent' : 'failed';
    $stmtUpdate->execute([$newStatus, (int)$row['id']]);
    $processed++;
  }

  echo json_encode(['success' => true, 'processed' => $processed]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.', 'error' => 'Server error.']);
}
