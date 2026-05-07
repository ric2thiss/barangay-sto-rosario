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
  $rows = $pdo->query("
    SELECT
      bo.id,
      bo.full_name,
      bo.official_title,
      es.signature_name,
      es.stored_path AS signature_url
    FROM barangay_officials bo
    LEFT JOIN esignatures es ON es.id = bo.esignature_id
    WHERE bo.is_active = 1
    ORDER BY bo.display_order ASC, bo.full_name ASC
  ")->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as &$r) {
    $r['signature_url'] = $r['signature_url'] ?: null;
  }

  out(true, '', ['rows' => $rows]);

} catch (Throwable $e) {
  http_response_code(500);
  out(false, 'Server error.');
}