<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/conn.php';

function out(bool $success, string $message = ''): void {
  echo json_encode(['success' => $success, 'message' => $message]);
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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  out(false, 'Invalid ID.');
}

function deleteFileIfExists(?string $path): void {
  if (!$path) return;
  if (!str_starts_with($path, 'backend/uploads/esignatures/')) return;

  $abs = dirname(__DIR__) . '/../' . $path;
  $abs = realpath($abs) ?: $abs;
  if (is_file($abs)) @unlink($abs);
}

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("
    SELECT bo.esignature_id, es.stored_path
    FROM barangay_officials bo
    LEFT JOIN esignatures es ON es.id = bo.esignature_id
    WHERE bo.id = ?
    LIMIT 1
  ");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    $pdo->rollBack();
    http_response_code(404);
    out(false, 'Official not found.');
  }

  if (!empty($row['esignature_id'])) {
    deleteFileIfExists($row['stored_path'] ?? null);
    $pdo->prepare("DELETE FROM esignatures WHERE id=?")->execute([(int)$row['esignature_id']]);
  }

  $pdo->prepare("DELETE FROM barangay_officials WHERE id=?")->execute([$id]);

  $pdo->commit();

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'delete', 'barangay_officials', ?, ?)")
      ->execute([(int)($_SESSION['user_id'] ?? 0), $id, "Deleted official ID: $id"]);

  out(true, 'Deleted successfully.');
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  out(false, 'Server error.');
}