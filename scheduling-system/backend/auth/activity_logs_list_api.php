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

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if ($currentUserId <= 0) {
  http_response_code(401);
  out(false, 'Unauthorized.');
}

$currentRole = $_SESSION['role'] ?? 'staff';

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 25);
if ($limit < 1) $limit = 25;
if ($limit > 100) $limit = 100;
$offset = ($page - 1) * $limit;

$q    = trim((string)($_GET['q'] ?? ''));
$date = trim((string)($_GET['date'] ?? ''));

try {
  $where = [];
  $params = [];

  if ($currentRole !== 'admin') {
    $where[] = 'l.user_id = ?';
    $params[] = $currentUserId;
  }

  if ($date !== '') {
    $where[] = 'l.created_at >= ? AND l.created_at < ?';
    $params[] = $date . ' 00:00:00';
    $params[] = $date . ' 23:59:59';
  }

  if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = "(
      l.action LIKE ?
      OR l.description LIKE ?
      OR l.entity_type LIKE ?
      OR CAST(l.entity_id AS CHAR) LIKE ?
      OR u.full_name LIKE ?
      OR u.username LIKE ?
    )";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
  }

  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  $sqlCount = "
    SELECT COUNT(*) AS cnt
    FROM activity_logs l
    LEFT JOIN users u ON u.id = l.user_id
    $whereSql
  ";
  $stmt = $pdo->prepare($sqlCount);
  foreach (array_values($params) as $index => $value) {
    $stmt->bindValue($index + 1, $value);
  }
  $stmt->execute();
  $total = (int)($stmt->fetchColumn() ?: 0);

  $sql = "
    SELECT
      l.id,
      l.user_id,
      l.action,
      l.entity_type,
      l.entity_id,
      l.description,
      l.created_at,
      u.full_name,
      u.username
    FROM activity_logs l
    LEFT JOIN users u ON u.id = l.user_id
    $whereSql
    ORDER BY l.created_at DESC, l.id DESC
    LIMIT ? OFFSET ?
  ";

  $stmt = $pdo->prepare($sql);
  $position = 1;
  foreach (array_values($params) as $value) {
    $stmt->bindValue($position++, $value);
  }
  $stmt->bindValue($position++, $limit, PDO::PARAM_INT);
  $stmt->bindValue($position++, $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

  foreach ($rows as &$r) {
    $uid = (int)($r['user_id'] ?? 0);

    if ($uid > 0 && $uid === $currentUserId) {
      $r['actor_label'] = 'You';
    } else if ($uid > 0) {
      $full = trim((string)($r['full_name'] ?? ''));
      $user = trim((string)($r['username'] ?? ''));
      $r['actor_label'] = $full !== '' ? $full : ($user !== '' ? $user : ('User #' . $uid));
    } else {
      $r['actor_label'] = 'System';
    }

    unset($r['full_name'], $r['username']);
  }
  unset($r);

  out(true, '', [
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
    'rows' => $rows,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  out(false, 'Failed to load logs.');
}
