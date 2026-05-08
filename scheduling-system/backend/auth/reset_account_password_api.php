<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized.']); exit; }

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$id      = (int)($input['id']               ?? 0);
$newPass = $input['password']               ?? '';
$confirm = $input['confirm_password']       ?? '';

if ($id <= 0 || $newPass === '') {
  echo json_encode(['success' => false, 'message' => 'Missing required fields.']); exit;
}
if (strlen($newPass) < 6) {
  echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']); exit;
}
if ($newPass !== $confirm) {
  echo json_encode(['success' => false, 'message' => 'Passwords do not match.']); exit;
}

try {
  $hash = password_hash($newPass, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?");
  $stmt->execute([$hash, $id]);

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'update', 'users', ?, ?)")
      ->execute([(int)$_SESSION['user_id'], $id, "Reset password for account ID: $id"]);

  echo json_encode(['success' => true, 'message' => 'Password reset successfully.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}
