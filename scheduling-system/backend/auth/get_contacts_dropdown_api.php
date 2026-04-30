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

try {
  $stmt = $pdo->query("SELECT id, full_name, mobile FROM contacts ORDER BY full_name ASC");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  out(true, '', ['contacts' => $rows]);
} catch (Throwable $e) {
  http_response_code(500);
  out(false, 'Server error.');
}