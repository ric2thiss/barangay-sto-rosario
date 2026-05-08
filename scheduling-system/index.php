<?php
session_start([
  'cookie_httponly' => true,
  'cookie_samesite' => 'Strict',
]);

// Remember-me auto-login: restore session from cookie if not already logged in
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
  $cookieToken = $_COOKIE['remember_me'];
  $tokenHash   = hash('sha256', $cookieToken);

  require_once __DIR__ . '/backend/config/conn.php';
  $stmt = $pdo->prepare("
    SELECT id, username, full_name, role, is_active, remember_token_expires, profile_picture
    FROM users
    WHERE remember_token = ? AND is_active = 1
    LIMIT 1
  ");
  $stmt->execute([$tokenHash]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && strtotime($user['remember_token_expires']) > time()) {
    $_SESSION['user_id']         = (int)$user['id'];
    $_SESSION['username']        = $user['username'];
    $_SESSION['full_name']       = $user['full_name'];
    $_SESSION['role']            = $user['role'];
    $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
  } else {
    // Token expired or invalid — clear the cookie
    setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on']);
    if ($user) {
      $pdo->prepare("UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE id = ?")
          ->execute([(int)$user['id']]);
    }
  }
}

$routes = require __DIR__ . '/frontend/config/routes.php';
$page = $_GET['page'] ?? null;

$publicPages  = $routes['public'];
$privatePages = $routes['private'];

$page = $page ?: (!empty($_SESSION['user_id'])
  ? ($routes['default_private'] ?? 'dashboard')
  : ($routes['default_public'] ?? 'login')
);

$isPublic  = array_key_exists($page, $publicPages);
$isPrivate = array_key_exists($page, $privatePages);

// Unknown page -> safe redirect
if (!$isPublic && !$isPrivate) {
  header("Location: index.php?page=" . (!empty($_SESSION['user_id']) ? 'dashboard' : 'login'));
  exit;
}

// Not logged in -> block private pages
if ($isPrivate && empty($_SESSION['user_id'])) {
  header("Location: index.php?page=login");
  exit;
}

// Admin-only pages -> redirect staff to dashboard
$adminOnlyPages = ['accounts'];
if ($isPrivate && in_array($page, $adminOnlyPages, true) && ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: index.php?page=dashboard");
  exit;
}

// Already logged in -> block login
if ($page === 'login' && !empty($_SESSION['user_id'])) {
  header("Location: index.php?page=dashboard");
  exit;
}

// Always load header
require __DIR__ . '/frontend/includes/header.php';

// Load sidebar ONLY for logged in users
if (!empty($_SESSION['user_id'])) {
  require __DIR__ . '/frontend/includes/sidebar.php';
}

// Load page
require $isPublic ? $publicPages[$page] : $privatePages[$page];

// Load modal only if logged in and on that page
if (!empty($_SESSION['user_id']) && $page === 'create_event') {
  require __DIR__ . '/frontend/includes/modals/create_event_modal.php';
}
if (!empty($_SESSION['user_id']) && $page === 'accounts') {
  require __DIR__ . '/frontend/includes/modals/account_modal.php';
}

// ✅ Load footer ONLY when logged in
if (!empty($_SESSION['user_id'])) {
  require __DIR__ . '/frontend/includes/footer.php';
}