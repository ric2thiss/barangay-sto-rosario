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
$title = trim((string)($data['title'] ?? ''));

if ($id <= 0) out(false, 'Invalid ID.');
if ($title === '') out(false, 'Title is required.');

try {
  $stmt = $pdo->prepare("UPDATE minutes_documents SET title=?, updated_at=NOW() WHERE id=?");
  $stmt->execute([$title, $id]);
  out(true, 'Updated.');
} catch (Throwable $e) {
  http_response_code(500);
  out(false, 'Update failed.');
}