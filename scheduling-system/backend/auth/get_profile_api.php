<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
  exit;
}

require_once __DIR__ . '/../config/conn.php';

try {
  $stmt = $pdo->prepare("
    SELECT id, username, full_name, email, mobile, role, profile_picture, created_at
    FROM users WHERE id = ? LIMIT 1
  ");
  $stmt->execute([(int)$_SESSION['user_id']]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
  }

  echo json_encode(['success' => true, 'data' => $user]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}
