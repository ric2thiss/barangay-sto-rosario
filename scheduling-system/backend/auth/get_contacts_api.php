<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

$q = trim($_GET['q'] ?? '');
$limit = 200;

try {
  if ($q !== '') {
    $stmt = $pdo->prepare("
      SELECT c.id, c.full_name, c.mobile, cg.group_name
      FROM contacts c
      LEFT JOIN contact_groups cg ON cg.id = c.group_id
      WHERE c.is_active=1 AND c.full_name LIKE ?
      ORDER BY c.full_name ASC
      LIMIT $limit
    ");
    $stmt->execute(['%'.$q.'%']);
  } else {
    $stmt = $pdo->query("
      SELECT c.id, c.full_name, c.mobile, cg.group_name
      FROM contacts c
      LEFT JOIN contact_groups cg ON cg.id = c.group_id
      WHERE c.is_active=1
      ORDER BY c.full_name ASC
      LIMIT $limit
    ");
  }

  $rows = $stmt->fetchAll();
  echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}