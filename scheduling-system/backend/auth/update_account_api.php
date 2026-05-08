<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized.']); exit; }

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$id       = (int)($input['id']        ?? 0);
$username = trim($input['username']   ?? '');
$fullName = trim($input['full_name']  ?? '');
$role     = trim($input['role']       ?? 'staff');
$email    = trim($input['email']      ?? '');
$mobile   = trim($input['mobile']     ?? '');

if ($id <= 0 || $username === '' || $fullName === '') {
  echo json_encode(['success' => false, 'message' => 'Missing required fields.']); exit;
}
if (!in_array($role, ['admin', 'staff'], true)) {
  echo json_encode(['success' => false, 'message' => 'Invalid role.']); exit;
}

try {
  // Check username uniqueness (excluding current user)
  $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
  $chk->execute([$username, $id]);
  if ($chk->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username already taken.']); exit;
  }

  $stmt = $pdo->prepare("
    UPDATE users SET username=?, full_name=?, role=?, email=?, mobile=?, updated_at=NOW()
    WHERE id=?
  ");
  $stmt->execute([$username, $fullName, $role, $email ?: null, $mobile ?: null, $id]);

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'update', 'users', ?, ?)")
      ->execute([(int)$_SESSION['user_id'], $id, "Updated account: $username"]);

  echo json_encode(['success' => true, 'message' => 'Account updated successfully.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}
