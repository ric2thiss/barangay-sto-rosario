<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/conn.php';

function out(bool $success, string $message = '', array $extra = []): void {
  echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
  exit;
}


if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  out(false, 'Unauthorized.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  out(false, 'Method not allowed.');
}

$id = (int)($_POST['id'] ?? 0);

$contactId = (int)($_POST['contact_id'] ?? 0);
$contactIdDb = $contactId > 0 ? $contactId : null;

$fullName = trim((string)($_POST['full_name'] ?? ''));
$officialTitle = trim((string)($_POST['official_title'] ?? ''));
$committee = trim((string)($_POST['committee'] ?? ''));
$termStart = trim((string)($_POST['term_start'] ?? ''));
$termEnd   = trim((string)($_POST['term_end'] ?? ''));
$displayOrder = (int)($_POST['display_order'] ?? 1);
$isActive = (int)($_POST['is_active'] ?? 1);

$signatureName = trim((string)($_POST['signature_name'] ?? ''));

if ($fullName === '' || $officialTitle === '') {
  http_response_code(400);
  out(false, 'Full Name and Official Title are required.');
}

$termStartDb = $termStart !== '' ? $termStart : null;
$termEndDb   = $termEnd !== '' ? $termEnd : null;

$hasNewSignature = isset($_FILES['signature'])
  && is_array($_FILES['signature'])
  && ($_FILES['signature']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

$uploadDir = __DIR__ . '/../uploads/esignatures';
$publicBase = 'backend/uploads/esignatures';

function ensureDir(string $dir): void {
  if (!is_dir($dir)) mkdir($dir, 0775, true);
}

function isImageMime(string $mime): bool {
  return in_array($mime, ['image/png','image/jpeg','image/jpg','image/webp'], true);
}

function safeExtFromMime(string $mime): string {
  return match ($mime) {
    'image/png' => 'png',
    'image/jpeg', 'image/jpg' => 'jpg',
    'image/webp' => 'webp',
    default => 'bin'
  };
}

function deleteFileIfExists(?string $path): void {
  if (!$path) return;
  if (!str_starts_with($path, 'backend/uploads/esignatures/')) return;

  $abs = dirname(__DIR__) . '/../' . $path; // backend/auth -> backend
  $abs = realpath($abs) ?: $abs;
  if (is_file($abs)) @unlink($abs);
}

try {
  $pdo->beginTransaction();

  if ($id > 0) {
    $stmt = $pdo->prepare("
      UPDATE barangay_officials
      SET contact_id=?, full_name=?, official_title=?, committee=?, display_order=?, term_start=?, term_end=?, is_active=?
      WHERE id=?
    ");
    $stmt->execute([
      $contactIdDb,
      $fullName,
      $officialTitle,
      ($committee !== '' ? $committee : null),
      $displayOrder,
      $termStartDb,
      $termEndDb,
      $isActive,
      $id
    ]);
  } else {
    $stmt = $pdo->prepare("
      INSERT INTO barangay_officials (contact_id, full_name, official_title, committee, display_order, term_start, term_end, is_active, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
      $contactIdDb,
      $fullName,
      $officialTitle,
      ($committee !== '' ? $committee : null),
      $displayOrder,
      $termStartDb,
      $termEndDb,
      $isActive
    ]);
    $id = (int)$pdo->lastInsertId();
  }

  // existing signature
  $stmt = $pdo->prepare("
    SELECT bo.esignature_id, es.stored_path
    FROM barangay_officials bo
    LEFT JOIN esignatures es ON es.id = bo.esignature_id
    WHERE bo.id = ?
    LIMIT 1
  ");
  $stmt->execute([$id]);
  $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

  // Update signature_name only if no new file
  if (!$hasNewSignature && $existing && !empty($existing['esignature_id'])) {
    $pdo->prepare("UPDATE esignatures SET signature_name=? WHERE id=?")
        ->execute([($signatureName !== '' ? $signatureName : null), (int)$existing['esignature_id']]);
  }

  // Replace signature if new file uploaded
  if ($hasNewSignature) {
    $f = $_FILES['signature'];

    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      throw new RuntimeException('Signature upload failed.');
    }

    $tmp = (string)$f['tmp_name'];
    $size = (int)($f['size'] ?? 0);

    if ($size <= 0 || $size > 2_621_440) {
      throw new RuntimeException('Signature file is too large (max 2.5MB).');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = ($finfo ? (string)finfo_file($finfo, $tmp) : '') ?: '';
    if ($finfo) finfo_close($finfo);
    if (!isImageMime($mime)) {
      throw new RuntimeException('Signature must be an image (PNG/JPG/WebP).');
    }

    ensureDir($uploadDir);

    $ext = safeExtFromMime($mime);
    $storedName = 'sig_' . $id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $absPath = $uploadDir . '/' . $storedName;

    if (!move_uploaded_file($tmp, $absPath)) {
      throw new RuntimeException('Failed to save signature file.');
    }

    $storedPath = $publicBase . '/' . $storedName;

    // delete old signature (file + row)
    if ($existing && !empty($existing['esignature_id'])) {
      deleteFileIfExists($existing['stored_path'] ?? null);
      $pdo->prepare("DELETE FROM esignatures WHERE id=?")->execute([(int)$existing['esignature_id']]);
    }

    // insert new signature
    $originalName = (string)($f['name'] ?? 'signature');
    $stmt = $pdo->prepare("
      INSERT INTO esignatures (official_id, signature_name, original_name, stored_name, stored_path, mime_type, created_at)
      VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
      $id,
      ($signatureName !== '' ? $signatureName : null),
      $originalName,
      $storedName,
      $storedPath,
      $mime
    ]);

    $newSigId = (int)$pdo->lastInsertId();

    // store reference
    $pdo->prepare("UPDATE barangay_officials SET esignature_id=? WHERE id=?")
        ->execute([$newSigId, $id]);
  }

  $pdo->commit();

  $action = ($id > 0 && isset($_POST['id']) && (int)$_POST['id'] > 0) ? 'update' : 'create';
  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, ?, 'barangay_officials', ?, ?)")
      ->execute([(int)($_SESSION['user_id'] ?? 0), $action, $id, ($action === 'create' ? "Created" : "Updated") . " official: $fullName"]);

  out(true, 'Saved successfully.', ['id' => $id]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  out(false, 'Server error.');
}