<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/conn.php';

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
  exit;
}

$q      = trim((string)($_GET['q'] ?? ''));
$role   = trim((string)($_GET['role'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

try {
  $where = ['id != ?'];
  $params = [(int)$_SESSION['user_id']];

  if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(username LIKE ? OR full_name LIKE ? OR email LIKE ? OR mobile LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
  }

  if ($role !== '') {
    $where[] = 'role = ?';
    $params[] = $role;
  }

  if ($status !== '') {
    $where[] = 'is_active = ?';
    $params[] = ($status === 'active') ? 1 : 0;
  }

  $whereSql = implode(' AND ', $where);
  $stmt = $pdo->prepare("
    SELECT id, username, full_name, role, email, mobile, is_active, created_at
    FROM users
    WHERE $whereSql
    ORDER BY full_name ASC
  ");

  foreach (array_values($params) as $index => $value) {
    $stmt->bindValue($index + 1, $value);
  }
  $stmt->execute();

  echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}
