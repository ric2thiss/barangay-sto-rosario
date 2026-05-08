<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized.']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id    = (int)($input['id'] ?? 0);

if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }

if ($id === (int)$_SESSION['user_id']) {
  echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']); exit;
}

try {
  $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
  $stmt->execute([$id]);

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'delete', 'users', ?, ?)")
      ->execute([(int)$_SESSION['user_id'], $id, "Deleted account ID: $id"]);

  echo json_encode(['success' => true, 'message' => 'Account deleted.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}
