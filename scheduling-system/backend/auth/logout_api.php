<?php
declare(strict_types=1);

session_start();

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

// Clear remember-me cookie and token from DB
if (!empty($_COOKIE['remember_me'])) {
  $tokenHash = hash('sha256', $_COOKIE['remember_me']);
  require_once __DIR__ . '/../config/conn.php';
  $pdo->prepare("UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE remember_token = ?")
      ->execute([$tokenHash]);
  setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on']);
}

// Clear session data
$_SESSION = [];

// Remove session cookie
if (ini_get("session.use_cookies")) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $p['path'] ?? '/',
    $p['domain'] ?? '',
    $p['secure'] ?? false,
    $p['httponly'] ?? true
  );
}

session_destroy();

// If called via fetch/ajax, return JSON.
// If called via form POST (normal), redirect back to login.
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$isJson = stripos($accept, 'application/json') !== false;

if ($isJson) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['success' => true, 'message' => 'Logged out']);
  exit;
}

header("Location: ../../index.php?page=login");
exit;