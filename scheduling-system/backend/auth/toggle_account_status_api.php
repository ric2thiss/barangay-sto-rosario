<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized.']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id    = (int)($input['id'] ?? 0);

if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }

// Prevent disabling own account
if ($id === (int)$_SESSION['user_id']) {
  echo json_encode(['success' => false, 'message' => 'You cannot disable your own account.']); exit;
}

try {
  $cur = $pdo->prepare("SELECT is_active FROM users WHERE id = ? LIMIT 1");
  $cur->execute([$id]);
  $row = $cur->fetch(PDO::FETCH_ASSOC);
  if (!$row) { echo json_encode(['success'=>false,'message'=>'User not found.']); exit; }

  $newStatus = $row['is_active'] ? 0 : 1;
  $pdo->prepare("UPDATE users SET is_active=?, updated_at=NOW() WHERE id=?")->execute([$newStatus, $id]);

  $label = $newStatus ? 'enabled' : 'disabled';

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'update', 'users', ?, ?)")
      ->execute([(int)$_SESSION['user_id'], $id, "Account $label (ID: $id)"]);

  echo json_encode(['success' => true, 'message' => "Account $label.", 'is_active' => $newStatus]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}
