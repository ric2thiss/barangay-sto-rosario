<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'data' => [], 'message' => 'Unauthorized.']);
  exit;
}

require_once __DIR__ . '/../config/conn.php';

function out(bool $success, array $data = [], string $message = '', array $extra = []): void {
  echo json_encode(array_merge(['success'=>$success,'data'=>$data,'message'=>$message], $extra));
  exit;
}

$action = $_GET['action'] ?? 'list';

try {
  if ($action === 'list') {
    $stmt = $pdo->query("SELECT id, group_name FROM contact_groups ORDER BY group_name ASC");
    out(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  if ($action === 'create') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, [], 'Method not allowed.');
    $name = trim($_POST['group_name'] ?? '');
    if ($name === '') out(false, [], 'Group name is required.');

    $chk = $pdo->prepare("SELECT id FROM contact_groups WHERE group_name=? LIMIT 1");
    $chk->execute([$name]);
    if ($chk->fetchColumn()) out(false, [], 'Group name already exists.');

    $ins = $pdo->prepare("INSERT INTO contact_groups (group_name, created_at) VALUES (?, NOW())");
    $ins->execute([$name]);

    out(true, [], 'Created.', ['id' => (int)$pdo->lastInsertId()]);
  }

  out(false, [], 'Invalid action.');
} catch (Throwable $e) {
  error_log((string)$e);
  out(false, [], 'Server error.');
}