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
function cleanIds($arr): array {
  if (!is_array($arr)) return [];
  $ids = [];
  foreach ($arr as $v) {
    $n = (int)$v;
    if ($n > 0) $ids[] = $n;
  }
  return array_values(array_unique($ids));
}

$action = $_GET['action'] ?? '';

try {
  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) out(false, 'Invalid id.');

    $stmt = $pdo->prepare("SELECT id, full_name, mobile, address FROM contacts WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$contact) out(false, 'Not found.');

    $stmt2 = $pdo->prepare("SELECT group_id FROM contact_group_members WHERE contact_id=?");
    $stmt2->execute([$id]);
    $groupIds = array_map('intval', $stmt2->fetchAll(PDO::FETCH_COLUMN));

    out(true, '', ['contact'=>$contact, 'group_ids'=>$groupIds]);
  }

  if ($action === 'create') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Method not allowed.');

    $full = trim($_POST['full_name'] ?? '');
    $mob  = trim($_POST['mobile'] ?? '');
    $addr = trim($_POST['address'] ?? '');
    $groupIds = cleanIds($_POST['group_ids'] ?? []);

    if ($full === '') out(false, 'Full name is required.');
    if (!$groupIds) out(false, 'Select at least 1 group.');

    $pdo->beginTransaction();

    $ins = $pdo->prepare("INSERT INTO contacts (full_name, mobile, address, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
    $ins->execute([$full, ($mob===''?null:$mob), ($addr===''?null:$addr)]);
    $contactId = (int)$pdo->lastInsertId();

    $insM = $pdo->prepare("INSERT INTO contact_group_members (contact_id, group_id, created_at) VALUES (?, ?, NOW())");
    foreach ($groupIds as $gid) $insM->execute([$contactId, $gid]);

    $pdo->commit();

    $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'create', 'contacts', ?, ?)")
        ->execute([(int)($_SESSION['user_id'] ?? 0), $contactId, "Created contact: $full"]);

    out(true, 'Created.');
  }

  if ($action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Method not allowed.');

    $id   = (int)($_POST['id'] ?? 0);
    $full = trim($_POST['full_name'] ?? '');
    $mob  = trim($_POST['mobile'] ?? '');
    $addr = trim($_POST['address'] ?? '');
    $groupIds = cleanIds($_POST['group_ids'] ?? []);

    if ($id <= 0) out(false, 'Invalid id.');
    if ($full === '') out(false, 'Full name is required.');
    if (!$groupIds) out(false, 'Select at least 1 group.');

    $pdo->beginTransaction();

    $upd = $pdo->prepare("UPDATE contacts SET full_name=?, mobile=?, address=?, updated_at=NOW() WHERE id=? LIMIT 1");
    $upd->execute([$full, ($mob===''?null:$mob), ($addr===''?null:$addr), $id]);

    $pdo->prepare("DELETE FROM contact_group_members WHERE contact_id=?")->execute([$id]);

    $insM = $pdo->prepare("INSERT INTO contact_group_members (contact_id, group_id, created_at) VALUES (?, ?, NOW())");
    foreach ($groupIds as $gid) $insM->execute([$id, $gid]);

    $pdo->commit();

    $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES (?, 'update', 'contacts', ?, ?)")
        ->execute([(int)($_SESSION['user_id'] ?? 0), $id, "Updated contact: $full"]);

    out(true, 'Updated.');
  }

  out(false, 'Invalid action.');
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  out(false, 'Server error.');
}