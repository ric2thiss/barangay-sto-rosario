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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  out(false, 'Method not allowed.');
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);

$minutesId  = (int)($data['minutes_document_id'] ?? 0);
$minutesJson = $data['minutes_json'] ?? null;

if ($minutesId <= 0 || !is_array($minutesJson)) {
  http_response_code(400);
  out(false, 'Invalid payload.');
}

$jsonText = json_encode($minutesJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonText === false) {
  http_response_code(400);
  out(false, 'Failed to encode JSON.');
}

try {
  $pdo->beginTransaction();

  // ✅ get latest extraction row
  $stmt = $pdo->prepare("
    SELECT id, extraction_version
    FROM minutes_extractions
    WHERE minutes_document_id = ?
    ORDER BY extraction_version DESC, id DESC
    LIMIT 1
    FOR UPDATE
  ");
  $stmt->execute([$minutesId]);
  $latest = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$latest) {
    $pdo->rollBack();
    http_response_code(400);
    out(false, 'No extraction yet. Click Generate Minutes first.');
  }

  // ✅ overwrite latest JSON (NO NEW VERSION)
  $upd = $pdo->prepare("
    UPDATE minutes_extractions
    SET extracted_json = ?, model_name = 'manual-edit'
    WHERE id = ?
  ");
  $upd->execute([$jsonText, (int)$latest['id']]);

  // mark as final
  $pdo->prepare("UPDATE minutes_documents SET status='final', updated_at=NOW() WHERE id=?")
      ->execute([$minutesId]);

  $pdo->commit();

  $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'update', 'minutes_documents', ?, ?)")
      ->execute([(int)($_SESSION['user_id'] ?? 0), $minutesId, "Saved edits for minutes document ID: $minutesId"]);

  out(true, 'Saved.', [
    'updated_extraction_version' => (int)$latest['extraction_version']
  ]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  out(false, 'Server error.');
}