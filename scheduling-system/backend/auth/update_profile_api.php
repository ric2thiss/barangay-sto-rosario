<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
  exit;
}

require_once __DIR__ . '/../config/conn.php';

function out(bool $ok, string $msg, array $extra = []): void {
  echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
  exit;
}

$userId   = (int)$_SESSION['user_id'];
$action   = trim($_POST['action'] ?? 'info');

try {
  // ── Update info (full_name, email, mobile) ─────────────────────────────
  if ($action === 'info') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email']     ?? '');
    $mobile   = trim($_POST['mobile']    ?? '');

    if ($fullName === '') out(false, 'Full name is required.');

    // Check email uniqueness (if provided)
    if ($email !== '') {
      $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
      $chk->execute([$email, $userId]);
      if ($chk->fetch()) out(false, 'Email is already used by another account.');
    }

    // Check mobile uniqueness (if provided)
    if ($mobile !== '') {
      $chk = $pdo->prepare("SELECT id FROM users WHERE mobile = ? AND id != ? LIMIT 1");
      $chk->execute([$mobile, $userId]);
      if ($chk->fetch()) out(false, 'Mobile is already used by another account.');
    }

    $pdo->prepare("UPDATE users SET full_name = ?, email = ?, mobile = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$fullName, $email ?: null, $mobile ?: null, $userId]);

    // Refresh session
    $_SESSION['full_name'] = $fullName;

    $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'update', 'users', ?, ?)")
        ->execute([$userId, $userId, "Updated profile info"]);

    out(true, 'Profile updated successfully.');
  }

  // ── Change password ─────────────────────────────────────────────────────
  if ($action === 'password') {
    $current = (string)($_POST['current_password'] ?? '');
    $new     = (string)($_POST['new_password']     ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($current === '' || $new === '' || $confirm === '') out(false, 'All password fields are required.');
    if (strlen($new) < 6) out(false, 'New password must be at least 6 characters.');
    if ($new !== $confirm) out(false, 'New passwords do not match.');

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current, $user['password_hash'])) {
      out(false, 'Current password is incorrect.');
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$hash, $userId]);

    $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'update', 'users', ?, ?)")
        ->execute([$userId, $userId, "Changed password"]);

    out(true, 'Password changed successfully.');
  }

  // ── Upload profile picture ───────────────────────────────────────────────
  if ($action === 'picture') {
    if (empty($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
      out(false, 'No image uploaded or upload error.');
    }

    $file = $_FILES['profile_picture'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = ($finfo ? (string)finfo_file($finfo, $file['tmp_name']) : '') ?: '';
    if ($finfo) finfo_close($finfo);
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowed, true)) out(false, 'Only JPG, PNG, WEBP, or GIF images are allowed.');
    if ($file['size'] > 3 * 1024 * 1024) out(false, 'Image must be under 3 MB.');

    $ext     = match($mime) {
      'image/png'  => 'png',
      'image/webp' => 'webp',
      'image/gif'  => 'gif',
      default      => 'jpg',
    };
    $dir     = __DIR__ . '/../../uploads/avatars/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    // Delete old picture if exists
    $old = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ? LIMIT 1");
    $old->execute([$userId]);
    $oldPic = $old->fetchColumn();
    if ($oldPic && file_exists(__DIR__ . '/../../' . $oldPic)) {
      @unlink(__DIR__ . '/../../' . $oldPic);
    }

    $stored  = 'avatar_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest    = $dir . $stored;
    if (!move_uploaded_file($file['tmp_name'], $dest)) out(false, 'Failed to save image.');

    $path = 'uploads/avatars/' . $stored;
    $pdo->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$path, $userId]);

    $_SESSION['profile_picture'] = $path;

    $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'update', 'users', ?, ?)")
        ->execute([$userId, $userId, "Updated profile picture"]);

    out(true, 'Profile picture updated.', ['path' => $path]);
  }

  // ── Remove profile picture ───────────────────────────────────────────────
  if ($action === 'remove_picture') {
    $old = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ? LIMIT 1");
    $old->execute([$userId]);
    $oldPic = $old->fetchColumn();
    if ($oldPic && file_exists(__DIR__ . '/../../' . $oldPic)) {
      @unlink(__DIR__ . '/../../' . $oldPic);
    }
    $pdo->prepare("UPDATE users SET profile_picture = NULL, updated_at = NOW() WHERE id = ?")
        ->execute([$userId]);
    $_SESSION['profile_picture'] = null;
    out(true, 'Profile picture removed.');
  }

  out(false, 'Unknown action.');

} catch (Throwable $e) {
  http_response_code(500);
  out(false, 'Server error.');
}
