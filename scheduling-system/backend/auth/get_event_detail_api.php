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

$eventId = (int)($_GET['event_id'] ?? 0);
if ($eventId <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid event ID.']);
  exit;
}

try {
  // Fetch agendas ordered by agenda_no
  $stmtAg = $pdo->prepare("
    SELECT id, agenda_no, agenda_title, agenda_details, status
    FROM event_agendas
    WHERE schedule_event_id = ?
    ORDER BY agenda_no ASC
  ");
  $stmtAg->execute([$eventId]);
  $agendas = $stmtAg->fetchAll(PDO::FETCH_ASSOC);

  // Fetch attachments
  $stmtAt = $pdo->prepare("
    SELECT id, original_name, stored_name, stored_path, mime_type, file_size, file_type, created_at
    FROM event_attachments
    WHERE schedule_event_id = ?
    ORDER BY created_at ASC
  ");
  $stmtAt->execute([$eventId]);
  $attachments = $stmtAt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success'     => true,
    'agendas'     => $agendas,
    'attachments' => $attachments,
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.', 'error' => 'Server error.']);
}
