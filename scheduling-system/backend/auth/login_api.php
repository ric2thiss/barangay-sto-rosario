<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/conn.php'; // must provide $pdo (PDO)

function out(bool $success, string $message = ''): void {
  echo json_encode(['success' => $success, 'message' => $message]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  out(false, 'Method not allowed.');
}

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
  http_response_code(400);
  out(false, 'Please enter username and password.');
}

try {
  $stmt = $pdo->prepare("
    SELECT id, username, password_hash, full_name, role, is_active, profile_picture
    FROM users
    WHERE username = ?
    LIMIT 1
  ");
  $stmt->execute([$username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    http_response_code(401);
    out(false, 'Invalid username or password.');
  }

  if ((int)$user['is_active'] === 0) {
    http_response_code(403);
    out(false, 'Account is disabled.');
  }

  if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    out(false, 'Invalid username or password.');
  }

  // ✅ Set session
  $_SESSION['user_id']        = (int)$user['id'];
  $_SESSION['username']       = $user['username'];
  $_SESSION['full_name']      = $user['full_name'];
  $_SESSION['role']           = $user['role'];
  $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;

  // Remember me: generate a secure token, store hash in DB, set 30-day cookie
  if (!empty($_POST['remember_me'])) {
    $token      = bin2hex(random_bytes(32)); // 64-char hex token
    $tokenHash  = hash('sha256', $token);
    $expires    = date('Y-m-d H:i:s', strtotime('+30 days'));

    $pdo->prepare("UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?")
        ->execute([$tokenHash, $expires, (int)$user['id']]);

    setcookie('remember_me', $token, [
      'expires'  => time() + (30 * 24 * 60 * 60),
      'path'     => '/',
      'httponly' => true,
      'samesite' => 'Strict',
      'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    ]);
  }

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'login', 'users', ?, ?)")
      ->execute([(int)$user['id'], (int)$user['id'], "Logged in: {$user['username']}"]);

  out(true, 'OK');

} catch (Throwable $e) {
  http_response_code(500);
  out(false, 'Server error.');
}