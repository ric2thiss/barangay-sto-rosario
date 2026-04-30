<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conn.php';

function out(bool $success, string $message = ''): void {
  echo json_encode(['success'=>$success,'message'=>$message]);
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

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);
if ($id <= 0) out(false, 'Invalid ID.');

function rrmdir(string $dir): void {
  if (!is_dir($dir)) return;
  $items = scandir($dir);
  if (!$items) return;
  foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = $dir . DIRECTORY_SEPARATOR . $item;
    if (is_dir($path)) rrmdir($path);
    else @unlink($path);
  }
  @rmdir($dir);
}

try {
  $pdo->beginTransaction();

  $pdo->prepare("DELETE FROM minutes_extractions WHERE minutes_document_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM minutes_attachments WHERE minutes_document_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM minutes_documents WHERE id=?")->execute([$id]);

  $pdo->commit();

  // remove physical files folder
  rrmdir(__DIR__ . '/../../uploads/minutes/' . $id);

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'delete', 'minutes_documents', ?, ?)")
      ->execute([(int)($_SESSION['user_id'] ?? 0), $id, "Deleted minutes document ID: $id"]);

  out(true, 'Deleted.');
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  out(false, 'Delete failed.');
}