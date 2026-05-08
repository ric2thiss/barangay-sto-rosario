<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/conn.php';

function out(bool $success, string $message = ''): void {
  echo json_encode(['success'=>$success,'message'=>$message]);
  exit;
}


if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  out(false, 'Unauthorized.');
}
try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Method not allowed.');
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) out(false, 'Invalid id.');

  $stmt = $pdo->prepare("UPDATE contacts SET is_active=0, updated_at=NOW() WHERE id=? LIMIT 1");
  $stmt->execute([$id]);

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'delete', 'contacts', ?, ?)")
      ->execute([(int)($_SESSION['user_id'] ?? 0), $id, "Deleted contact ID: $id"]);

  out(true, 'Deleted.');
} catch (Throwable $e) {
  out(false, 'Server error.');
}