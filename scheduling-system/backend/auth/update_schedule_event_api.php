<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';
require_once __DIR__ . '/../config/sms.php';

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
  exit;
}

$userId = (int)$_SESSION['user_id'];

$eventId     = $_POST['event_id'] ?? '';
$title       = trim($_POST['title'] ?? '');
$agendasJson = trim($_POST['agendas_json'] ?? '[]');
$agendas     = json_decode($agendasJson, true);
if (!is_array($agendas)) $agendas = [];
$start   = $_POST['start_datetime'] ?? '';
$end     = $_POST['end_datetime'] ?? '';
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');

// UI
$notifySms = isset($_POST['notify_sms']) && (string)$_POST['notify_sms'] === '1';
$sendSmsNow = isset($_POST['send_sms_now']) && (string)$_POST['send_sms_now'] === '1';

// IMPORTANT: textarea name="sms_message"
$smsMessage = trim($_POST['sms_message'] ?? '');

// notify options
$allowAutoNotify = isset($_POST['allow_auto_notify']) ? (int)$_POST['allow_auto_notify'] : 0;
$offsetMinutes   = isset($_POST['notify_offset_minutes']) ? (int)$_POST['notify_offset_minutes'] : 60;

// Recipients
$notifyGroups = $_POST['notify_groups'] ?? [];       // group_name strings
$notifyContacts = $_POST['notify_contacts'] ?? [];   // contact IDs
$excludedConflictContacts = $_POST['excluded_conflict_contacts'] ?? [];
if (!is_array($excludedConflictContacts)) $excludedConflictContacts = [];

function detectFileType(string $mime, string $ext): string {
  if (str_starts_with($mime, 'image/')) return 'image';
  if ($mime === 'application/pdf') return 'pdf';
  if (in_array($ext, ['doc','docx']) || str_contains($mime, 'word')) return 'doc';
  if (in_array($ext, ['xls','xlsx']) || str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel')) return 'xls';
  return 'other';
}

function resolveFinalContactIds(PDO $pdo, array $notifyContacts, array $notifyGroups): array {
  $finalIds = [];

  foreach ((array)$notifyContacts as $cid) {
    if (ctype_digit((string)$cid)) $finalIds[(int)$cid] = true;
  }

  $groupNames = array_values(array_unique(array_filter(array_map(fn($x) => trim((string)$x), (array)$notifyGroups))));

  if (!empty($groupNames)) {
    $in = implode(',', array_fill(0, count($groupNames), '?'));
    $q = $pdo->prepare("
      SELECT DISTINCT c.id
      FROM contacts c
      JOIN contact_group_members m ON m.contact_id = c.id
      JOIN contact_groups g ON g.id = m.group_id
      WHERE g.group_name IN ($in)
        AND c.is_active = 1
    ");
    $q->execute($groupNames);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $finalIds[(int)$r['id']] = true;
    }
  }

  return array_keys($finalIds);
}

function excludeContactIds(array $contactIds, array $excludedIds): array {
  if (empty($excludedIds)) return $contactIds;
  $excluded = array_fill_keys(array_map('intval', $excludedIds), true);
  return array_values(array_filter($contactIds, static fn ($id) => !isset($excluded[(int)$id])));
}

function findScheduleConflicts(PDO $pdo, array $contactIds, string $start, string $end, ?int $excludeEventId = null): array {
  if (empty($contactIds)) return [];

  $placeholders = implode(',', array_fill(0, count($contactIds), '?'));
  $excludeSql = $excludeEventId ? " AND se.id <> ?" : "";

  $sql = "
    SELECT
      c.id AS contact_id,
      c.full_name,
      se.id AS event_id,
      se.title,
      se.start_datetime,
      se.end_datetime
    FROM schedule_events se
    JOIN (
      SELECT schedule_event_id, contact_id
      FROM schedule_participants
      UNION
      SELECT schedule_event_id, contact_id
      FROM sms_outbox
      WHERE contact_id IS NOT NULL
    ) links ON links.schedule_event_id = se.id
    JOIN contacts c ON c.id = links.contact_id
    WHERE links.contact_id IN ($placeholders)
      AND se.status <> 'cancelled'
      AND se.start_datetime < ?
      AND se.end_datetime > ?
      $excludeSql
    ORDER BY c.full_name ASC, se.start_datetime ASC
  ";

  $params = array_map('intval', $contactIds);
  $params[] = $end;
  $params[] = $start;
  if ($excludeEventId) $params[] = $excludeEventId;

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $seen = [];
  $conflicts = [];

  foreach ($rows as $row) {
    $key = (int)$row['contact_id'] . ':' . (int)$row['event_id'];
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $conflicts[] = [
      'contact_id' => (int)$row['contact_id'],
      'contact_name' => (string)$row['full_name'],
      'event_id' => (int)$row['event_id'],
      'event_title' => (string)$row['title'],
      'start_datetime' => (string)$row['start_datetime'],
      'end_datetime' => (string)$row['end_datetime'],
    ];
  }

  return $conflicts;
}

function syncScheduleParticipants(PDO $pdo, int $eventId, array $contactIds): void {
  $pdo->prepare("DELETE FROM schedule_participants WHERE schedule_event_id = ?")->execute([$eventId]);
  if (empty($contactIds)) return;

  $stmt = $pdo->prepare("
    INSERT INTO schedule_participants (schedule_event_id, contact_id, role_in_event, attendance)
    VALUES (?, ?, NULL, 'pending')
  ");

  foreach ($contactIds as $contactId) {
    $stmt->execute([$eventId, (int)$contactId]);
  }
}

function buildConflictMessage(array $conflicts): string {
  if (empty($conflicts)) return 'Selected contacts have conflicting schedules.';

  $lines = ["Cannot proceed because these contacts already have overlapping schedules:"];
  foreach ($conflicts as $conflict) {
    $start = date('M d, Y g:i A', strtotime((string)$conflict['start_datetime']));
    $end = date('M d, Y g:i A', strtotime((string)$conflict['end_datetime']));
    $lines[] = sprintf(
      '%s already has "%s" from %s to %s.',
      $conflict['contact_name'],
      $conflict['event_title'],
      $start,
      $end
    );
  }

  return implode("\n", $lines);
}

function fail(string $msg, int $code = 200, array $extra = []): void {
  if ($code !== 200) http_response_code($code);
  echo json_encode(array_merge(['success' => false, 'message' => $msg], $extra));
  exit;
}

if ($eventId === '' || !ctype_digit((string)$eventId)) fail('Invalid event ID.');
if ($title === '') fail('Subject is required.');
if ($start === '' || $end === '') fail('Start and End date/time are required.');

try {
  $startDt = new DateTime($start);
  $endDt   = new DateTime($end);
  if ($endDt <= $startDt) fail('End date/time must be after start.');
} catch (Throwable $e) {
  fail('Invalid date/time format.');
}

$finalContactIds = resolveFinalContactIds($pdo, $notifyContacts, $notifyGroups);
$finalContactIds = excludeContactIds($finalContactIds, $excludedConflictContacts);
$conflicts = findScheduleConflicts(
  $pdo,
  $finalContactIds,
  $startDt->format('Y-m-d H:i:s'),
  $endDt->format('Y-m-d H:i:s'),
  (int)$eventId
);
if (!empty($conflicts)) {
  fail(buildConflictMessage($conflicts), 409, ['conflicts' => $conflicts]);
}

try {
  $pdo->beginTransaction();

  // 1) Update schedule_events
  $stmt = $pdo->prepare("
    UPDATE schedule_events
    SET title = :title,
        description = :description,
        location = :location,
        start_datetime = :start_datetime,
        end_datetime = :end_datetime,
        sms_message = :sms_message,
        send_sms_now = :send_sms_now,
        allow_auto_notify = :allow_auto_notify,
        notify_offset_minutes = :notify_offset_minutes,
        updated_at = NOW()
    WHERE id = :id
    LIMIT 1
  ");

  $stmt->execute([
    ':title' => $title,
    ':description' => ($description !== '' ? $description : null),
    ':location' => ($location !== '' ? $location : null),
    ':start_datetime' => $startDt->format('Y-m-d H:i:s'),
    ':end_datetime' => $endDt->format('Y-m-d H:i:s'),
    ':sms_message' => ($smsMessage !== '' ? $smsMessage : null),
    ':send_sms_now' => $sendSmsNow ? 1 : 0,
    ':allow_auto_notify' => $allowAutoNotify ? 1 : 0,
    ':notify_offset_minutes' => max(1, $offsetMinutes),
    ':id' => (int)$eventId
  ]);

  // 2) Sync agendas: delete old, re-insert from submitted JSON
  $pdo->prepare("DELETE FROM event_agendas WHERE schedule_event_id = ?")->execute([(int)$eventId]);

  if (!empty($agendas)) {
    $stmtAg = $pdo->prepare("
      INSERT INTO event_agendas (schedule_event_id, agenda_no, agenda_title, agenda_details, status)
      VALUES (?, ?, ?, ?, ?)
    ");
    $agNo = 1;
    foreach ($agendas as $a) {
      $agTopic   = trim((string)($a['topic']   ?? ''));
      $agStatus  = in_array($a['status'] ?? '', ['pending','done','deferred','cancelled'])
                    ? $a['status'] : 'pending';
      $agRemarks = trim((string)($a['remarks'] ?? ''));
      if ($agTopic === '') continue;
      $stmtAg->execute([
        (int)$eventId, $agNo, $agTopic,
        $agRemarks !== '' ? $agRemarks : null,
        $agStatus
      ]);
      $agNo++;
    }
  }

  // 2b) Handle NEW file uploads (adds to existing attachments)
  $uploadDir = __DIR__ . '/../uploads/event_attachments/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

  if (!empty($_FILES['event_files']['name'][0])) {
    $stmtFile = $pdo->prepare("
      INSERT INTO event_attachments
        (schedule_event_id, uploaded_by, file_type, original_name, stored_name, stored_path, mime_type, file_size)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $files = $_FILES['event_files'];
    foreach ($files['name'] as $i => $origName) {
      if ((int)$files['error'][$i] !== UPLOAD_ERR_OK) continue;
      $origName = basename((string)$origName);
      $mime     = (string)($files['type'][$i] ?? '');
      $size     = (int)($files['size'][$i] ?? 0);
      $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
      $stored   = $eventId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      if (!move_uploaded_file($files['tmp_name'][$i], $uploadDir . $stored)) continue;
      $stmtFile->execute([
        (int)$eventId, $userId,
        detectFileType($mime, $ext),
        $origName, $stored,
        'backend/uploads/event_attachments/' . $stored,
        $mime, $size
      ]);
    }
  }

  // 3) Refresh participant links used by conflict detection and attendance.
  syncScheduleParticipants($pdo, (int)$eventId, $finalContactIds);

  // 4) Refresh sms_outbox rows for this event — clear queued/cancelled, keep sent/failed history
  $pdo->prepare("
    DELETE FROM sms_outbox
    WHERE schedule_event_id = ?
      AND status IN ('queued','cancelled')
  ")->execute([(int)$eventId]);

  // Insert new outbox rows + send immediately via Mocean if notify_sms=1
  $sentCount = 0;

  if (!empty($finalContactIds) && $smsMessage !== '') {
    $nowStr     = (new DateTime())->format('Y-m-d H:i:s');
    $autoSendAt = ($allowAutoNotify === 1)
      ? (new DateTime($startDt->format('Y-m-d H:i:s')))->modify("-{$offsetMinutes} minutes")->format('Y-m-d H:i:s')
      : $nowStr;

    $in2 = implode(',', array_fill(0, count($finalContactIds), '?'));
    $q2  = $pdo->prepare("
      SELECT id, mobile
      FROM contacts
      WHERE id IN ($in2)
        AND is_active = 1
        AND mobile IS NOT NULL
        AND mobile <> ''
    ");
    $q2->execute($finalContactIds);

    $insSms = $pdo->prepare("
      INSERT INTO sms_outbox
        (schedule_event_id, contact_id, to_mobile, message, send_at, send_type, status, queued_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    // Deduplicate by normalized mobile number; collect unique rows for dual-insert
    $uniqueRows  = [];
    $seenMobiles = [];

    foreach ($q2->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $to   = trim((string)($r['mobile'] ?? ''));
      if ($to === '') continue;
      $norm = preg_replace('/[\s\-]/', '', $to);
      if (isset($seenMobiles[$norm])) continue;
      $seenMobiles[$norm] = true;
      $uniqueRows[] = ['id' => (int)$r['id'], 'mobile' => $to];
    }

    foreach ($uniqueRows as $r) {
      if ($notifySms && $sendSmsNow) {
        // Send immediately via Mocean API
        $result    = mocean_send_sms($r['mobile'], $smsMessage);
        $rowStatus = $result['success'] ? 'sent' : 'failed';
        if ($result['success']) $sentCount++;
      } else {
        $rowStatus = 'cancelled';
      }

      // Insert immediate/history row
      $insSms->execute([(int)$eventId, $r['id'], $r['mobile'], $smsMessage, $nowStr, 'immediate', $rowStatus]);

      // Insert auto-notify queued row if enabled and send_at is in the future
      if ($allowAutoNotify === 1 && $autoSendAt > $nowStr) {
        $insSms->execute([(int)$eventId, $r['id'], $r['mobile'], $smsMessage, $autoSendAt, 'auto', 'queued']);
      }
    }
  }

  // 5) Activity log
  $log = $pdo->prepare("
    INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, created_at)
    VALUES (:uid, 'update', 'schedule_events', :eid, :desc, NOW())
  ");
  $log->execute([
    ':uid'  => $userId,
    ':eid'  => (int)$eventId,
    ':desc' => 'Updated schedule event',
  ]);

  $pdo->commit();

  echo json_encode([
    'success'  => true,
    'message'  => 'Event updated successfully.',
    'sms_sent' => $sentCount
  ]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Failed to update event.',
      ]);
}
