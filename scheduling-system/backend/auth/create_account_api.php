<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized.']); exit; }

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($input['username']  ?? '');
$fullName = trim($input['full_name'] ?? '');
$role     = trim($input['role']      ?? 'staff');
$email    = trim($input['email']     ?? '');
$mobile   = trim($input['mobile']    ?? '');
$password = $input['password']          ?? '';
$confirm  = $input['confirm_password']  ?? '';

if ($username === '' || $fullName === '' || $password === '') {
  echo json_encode(['success' => false, 'message' => 'Username, full name, and password are required.']); exit;
}
if (strlen($password) < 6) {
  echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']); exit;
}
if ($password !== $confirm) {
  echo json_encode(['success' => false, 'message' => 'Passwords do not match.']); exit;
}
if (!in_array($role, ['admin', 'staff'], true)) {
  echo json_encode(['success' => false, 'message' => 'Invalid role.']); exit;
}

try {
  $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
  $chk->execute([$username]);
  if ($chk->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username already exists.']); exit;
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("
    INSERT INTO users (username, password_hash, full_name, role, email, mobile, is_active, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
  ");
  $stmt->execute([$username, $hash, $fullName, $role, $email ?: null, $mobile ?: null]);
  $newId = (int)$pdo->lastInsertId();

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'create', 'users', ?, ?)")
      ->execute([(int)$_SESSION['user_id'], $newId, "Created account: $username"]);

  echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}
