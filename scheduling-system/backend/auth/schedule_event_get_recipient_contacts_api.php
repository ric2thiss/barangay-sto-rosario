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
  $q = trim($_GET['q'] ?? '');

  // ✅ we accept group names from JS: groups[]
  $groups = $_GET['groups'] ?? [];
  if (!is_array($groups)) $groups = [];

  $groupNames = [];
  foreach ($groups as $g) {
    $g = trim((string)$g);
    if ($g !== '') $groupNames[] = $g;
  }
  $groupNames = array_values(array_unique($groupNames));

  $params = [];
  $sql = "
    SELECT DISTINCT c.id, c.full_name, c.mobile
    FROM contacts c
  ";

  // Filter by group names if provided
  if (!empty($groupNames)) {
    $in = implode(',', array_fill(0, count($groupNames), '?'));
    $sql .= "
      JOIN contact_group_members m ON m.contact_id = c.id
      JOIN contact_groups g ON g.id = m.group_id AND g.group_name IN ($in)
    ";
    $params = array_merge($params, $groupNames);
  }

  $sql .= " WHERE c.is_active = 1 ";

  if ($q !== '') {
    $sql .= " AND (c.full_name LIKE ? OR c.mobile LIKE ?) ";
    $params[] = "%$q%";
    $params[] = "%$q%";
  }

  $sql .= " ORDER BY c.full_name ASC LIMIT 500";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  out(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
  out(false, [], 'Server error.');
}