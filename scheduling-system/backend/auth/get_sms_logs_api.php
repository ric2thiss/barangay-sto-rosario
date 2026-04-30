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

$role   = $_SESSION['role'] ?? 'staff';
$selfId = (int)($_SESSION['user_id'] ?? 0);

$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo   = trim((string)($_GET['date_to'] ?? ''));
$status   = trim((string)($_GET['status'] ?? ''));
$q        = trim((string)($_GET['q'] ?? ''));
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 20;
$offset   = ($page - 1) * $limit;

if ($dateFrom === '' && $dateTo === '') {
  $dateFrom = date('Y-m-d');
  $dateTo   = date('Y-m-d');
}

try {
  $where = [];
  $params = [];

  if ($dateFrom !== '') {
    $where[] = 'DATE(o.queued_at) >= ?';
    $params[] = $dateFrom;
  }

  if ($dateTo !== '') {
    $where[] = 'DATE(o.queued_at) <= ?';
    $params[] = $dateTo;
  }

  if ($role === 'staff') {
    $where[] = 'se.created_by = ?';
    $params[] = $selfId;
  }

  if ($status !== '') {
    $where[] = 'o.status = ?';
    $params[] = $status;
  }

  if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(o.to_mobile LIKE ? OR o.message LIKE ? OR se.title LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
  }

  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  $countSql = "
    SELECT COUNT(*)
    FROM sms_outbox o
    LEFT JOIN schedule_events se ON se.id = o.schedule_event_id
    $whereSql
  ";
  $countStmt = $pdo->prepare($countSql);
  foreach (array_values($params) as $index => $value) {
    $countStmt->bindValue($index + 1, $value);
  }
  $countStmt->execute();
  $total = (int)$countStmt->fetchColumn();

  $sql = "
    SELECT
      o.id,
      o.to_mobile,
      o.message,
      o.send_type,
      o.status,
      o.queued_at,
      o.sent_at,
      o.send_at,
      o.error_message,
      se.id         AS event_id,
      se.title      AS event_title,
      se.created_by AS sent_by_id,
      u.full_name   AS sent_by_name
    FROM sms_outbox o
    LEFT JOIN schedule_events se ON se.id = o.schedule_event_id
    LEFT JOIN users u ON u.id = se.created_by
    $whereSql
    ORDER BY o.queued_at DESC
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
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success' => true,
    'data'    => $rows,
    'total'   => $total,
    'page'    => $page,
    'pages'   => (int)ceil($total / $limit),
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.', 'error' => 'Server error.']);
}
