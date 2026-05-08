<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

function out(bool $success, array $data = [], string $message = ''): void {
  echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message]);
  exit;
}

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  out(false, [], 'Unauthorized.');
}

try {
  $stmt = $pdo->query("SELECT id, group_name FROM contact_groups ORDER BY group_name ASC");
  out(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
  out(false, [], 'Server error.');
}