<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conn.php';

function out(bool $success, string $message = '', array $extra = []): void {
  echo json_encode(array_merge(['success'=>$success,'message'=>$message], $extra));
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

$createdBy = (int)$_SESSION['user_id'];

$title = trim((string)($_POST['title'] ?? ''));
if ($title === '') $title = 'Minutes of Meeting';

if (!isset($_FILES['files'])) {
  http_response_code(400);
  out(false, 'No files uploaded.');
}

$files = $_FILES['files'];
if (!is_array($files['name'])) {
  http_response_code(400);
  out(false, 'Invalid upload payload.');
}

try {
  $pdo->beginTransaction();

  // create minutes document
  $stmt = $pdo->prepare("INSERT INTO minutes_documents (created_by, title, status) VALUES (?, ?, 'draft')");
  $stmt->execute([$createdBy, $title]);
  $docId = (int)$pdo->lastInsertId();

  // folder: uploads/minutes/{docId}
  $baseDir = __DIR__ . '/../../uploads/minutes/' . $docId;
  if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true)) {
    throw new RuntimeException('Failed to create upload folder.');
  }

  $allowed = [
    'image/jpeg' => 'image',
    'image/png'  => 'image',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
  ];

  $ins = $pdo->prepare("
    INSERT INTO minutes_attachments
      (minutes_document_id, uploaded_by, file_type, original_name, stored_name, stored_path, mime_type, file_size)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $saved = 0;
  $count = count($files['name']);

  for ($i = 0; $i < $count; $i++) {
    $orig = (string)$files['name'][$i];
    $tmp  = (string)$files['tmp_name'][$i];
    $err  = (int)$files['error'][$i];
    $size = (int)$files['size'][$i];

    if ($err !== UPLOAD_ERR_OK) continue;
    if (!is_uploaded_file($tmp)) continue;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = ($finfo ? (string)finfo_file($finfo, $tmp) : '') ?: 'application/octet-stream';
    if ($finfo) finfo_close($finfo);
    if (!isset($allowed[$mime])) continue;

    $fileType = $allowed[$mime];
    $ext = pathinfo($orig, PATHINFO_EXTENSION);
    $stored = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
    $dest = $baseDir . '/' . $stored;

    if (!move_uploaded_file($tmp, $dest)) continue;

    $storedPath = 'uploads/minutes/' . $docId . '/' . $stored;

    $ins->execute([$docId, $createdBy, $fileType, $orig, $stored, $storedPath, $mime, $size]);
    $saved++;
  }

  if ($saved <= 0) {
    throw new RuntimeException('No valid files saved. Upload JPG/PNG/PDF/DOC/DOCX only.');
  }

  $pdo->commit();
  out(true, 'Uploaded.', ['minutes_document_id' => $docId, 'saved_files' => $saved]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  out(false, 'Upload failed.');
}