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
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) out(false, 'Invalid ID.');

try {
  $stmt = $pdo->prepare("SELECT * FROM minutes_documents WHERE id = ?");
  $stmt->execute([$id]);
  $doc = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$doc) out(false, 'Minutes not found.');

  $filesStmt = $pdo->prepare("
    SELECT id, original_name, stored_path, mime_type, file_size, created_at
    FROM minutes_attachments
    WHERE minutes_document_id = ?
    ORDER BY id DESC
  ");
  $filesStmt->execute([$id]);
  $files = $filesStmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($files as &$f) {
    // assumes /uploads is web-accessible
    $f['public_url'] = $f['stored_path'];
  }

  $exStmt = $pdo->prepare("
    SELECT extracted_json
    FROM minutes_extractions
    WHERE minutes_document_id = ?
    ORDER BY id DESC
    LIMIT 1
  ");
  $exStmt->execute([$id]);
  $latest = $exStmt->fetchColumn();

  out(true, '', [
    'doc' => $doc,
    'files' => $files,
    'latest_extraction_json' => $latest ?: null,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  out(false, 'Server error.');
}