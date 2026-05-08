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
$isActive = isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (int)$_GET['is_active'] : null;
$q = trim((string)($_GET['q'] ?? ''));

$where = [];
$params = [];

if ($isActive !== null) {
  $where[] = "bo.is_active = ?";
  $params[] = $isActive;
}

if ($q !== '') {
  $where[] = "(bo.full_name LIKE ? OR bo.official_title LIKE ? OR bo.committee LIKE ?)";
  $like = '%' . $q . '%';
  array_push($params, $like, $like, $like);
}

$sql = "
  SELECT
    bo.id,
    bo.contact_id,
    bo.full_name,
    bo.official_title,
    bo.committee,
    bo.display_order,
    bo.term_start,
    bo.term_end,
    bo.is_active,
    bo.esignature_id,
    c.mobile AS contact_mobile,
    es.signature_name,
    es.stored_path
  FROM barangay_officials bo
  LEFT JOIN contacts c ON c.id = bo.contact_id
  LEFT JOIN esignatures es ON es.id = bo.esignature_id
";

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY bo.display_order ASC, bo.full_name ASC";

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as &$r) {
    $r['signature_url'] = ($r['stored_path'] ?? null) ? $r['stored_path'] : null;
    unset($r['stored_path']);
  }

  out(true, '', ['officials' => $rows]);
} catch (Throwable $e) {
  http_response_code(500);
  out(false, 'Server error.');
}