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

function out(bool $success, array $data = [], string $message = ''): void {
  echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message]);
  exit;
}

try {
  $groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
  $q = trim($_GET['q'] ?? '');

  if ($groupId > 0) {
    $sql = "
      SELECT c.id, c.full_name, c.mobile,
             GROUP_CONCAT(DISTINCT g.group_name ORDER BY g.group_name SEPARATOR ', ') AS groups,
             GROUP_CONCAT(DISTINCT m.group_id ORDER BY m.group_id SEPARATOR ',') AS group_ids
      FROM contacts c
      JOIN contact_group_members mf
        ON mf.contact_id = c.id AND mf.group_id = :gid
      LEFT JOIN contact_group_members m ON m.contact_id = c.id
      LEFT JOIN contact_groups g ON g.id = m.group_id
      WHERE c.is_active = 1
    ";
    if ($q !== '') $sql .= " AND (c.full_name LIKE :q OR c.mobile LIKE :q OR g.group_name LIKE :q)";
    $sql .= " GROUP BY c.id ORDER BY c.full_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':gid', $groupId, PDO::PARAM_INT);
    if ($q !== '') $stmt->bindValue(':q', "%$q%");
    $stmt->execute();
    out(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  $sql = "
    SELECT c.id, c.full_name, c.mobile,
           GROUP_CONCAT(DISTINCT g.group_name ORDER BY g.group_name SEPARATOR ', ') AS groups,
           GROUP_CONCAT(DISTINCT m.group_id ORDER BY m.group_id SEPARATOR ',') AS group_ids
    FROM contacts c
    LEFT JOIN contact_group_members m ON m.contact_id = c.id
    LEFT JOIN contact_groups g ON g.id = m.group_id
    WHERE c.is_active = 1
  ";
  if ($q !== '') $sql .= " AND (c.full_name LIKE :q OR c.mobile LIKE :q OR g.group_name LIKE :q)";
  $sql .= " GROUP BY c.id ORDER BY c.full_name ASC";

  $stmt = $pdo->prepare($sql);
  if ($q !== '') $stmt->bindValue(':q', "%$q%");
  $stmt->execute();
  out(true, $stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Throwable $e) {
  out(false, [], 'Server error.');
}
