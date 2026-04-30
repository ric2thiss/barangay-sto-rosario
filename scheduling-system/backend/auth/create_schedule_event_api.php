<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';
require_once __DIR__ . '/../config/sms.php';

function out(bool $success, string $message = '', array $extra = []) {
  echo json_encode(array_merge(['success'=>$success,'message'=>$message], $extra));
  exit;
}

function fail(string $message, int $code = 400, array $extra = []): void {
  http_response_code($code);
  out(false, $message, $extra);
}

function resolveFinalContactIds(PDO $pdo, array $contactIds, array $groupNames): array {
  $finalIds = [];
  foreach ($contactIds as $cid) $finalIds[(int)$cid] = true;

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  fail('Method not allowed.', 405);
}

if (empty($_SESSION['user_id'])) {
  fail('Unauthorized.', 401);
}

$userId = (int)$_SESSION['user_id'];

$title = trim($_POST['title'] ?? '');
$start = trim($_POST['start_datetime'] ?? '');
$end   = trim($_POST['end_datetime'] ?? '');

if ($title === '' || $start === '' || $end === '') {
  fail('Subject, From and To are required.');
}

$description = trim($_POST['description'] ?? '');
$location    = trim($_POST['location'] ?? '');

// IMPORTANT: your textarea is name="sms_message"
$smsMessage  = trim($_POST['sms_message'] ?? '');

$notifySms   = (int)($_POST['notify_sms'] ?? 0);
$sendSmsNow  = (int)($_POST['send_sms_now'] ?? 1);
$allowAuto   = (int)($_POST['allow_auto_notify'] ?? 0);
$offsetMin   = (int)($_POST['notify_offset_minutes'] ?? 60);

$agendasJson = trim($_POST['agendas_json'] ?? '[]');
$agendas = json_decode($agendasJson, true);
if (!is_array($agendas)) $agendas = [];

// recipients
$notifyContacts = $_POST['notify_contacts'] ?? [];
if (!is_array($notifyContacts)) $notifyContacts = [];
$contactIds = array_values(array_unique(array_filter(array_map('intval', $notifyContacts))));

$notifyGroups = $_POST['notify_groups'] ?? [];
if (!is_array($notifyGroups)) $notifyGroups = [];
$groupNames = array_values(array_unique(array_filter(array_map(fn($x)=>trim((string)$x), $notifyGroups))));
$excludedConflictContacts = $_POST['excluded_conflict_contacts'] ?? [];
if (!is_array($excludedConflictContacts)) $excludedConflictContacts = [];

try {
  $startDt = new DateTime($start);
  $endDt = new DateTime($end);
  if ($endDt <= $startDt) fail('End date/time must be after start.');
  $start = $startDt->format('Y-m-d H:i:s');
  $end = $endDt->format('Y-m-d H:i:s');
} catch (Throwable $e) {
  fail('Invalid date/time format.');
}

$finalContactIds = resolveFinalContactIds($pdo, $contactIds, $groupNames);
$finalContactIds = excludeContactIds($finalContactIds, $excludedConflictContacts);
$conflicts = findScheduleConflicts($pdo, $finalContactIds, $start, $end);
if (!empty($conflicts)) {
  fail(buildConflictMessage($conflicts), 409, ['conflicts' => $conflicts]);
}

/**
 * Fallback build message if sms_message is empty
 */
if ($smsMessage === '') {
  $dateLine = '';
  $timeLine = '';
  if ($startDt && $endDt) {
    $sameDate = $startDt->format('Y-m-d') === $endDt->format('Y-m-d');
    $dateLine = $sameDate
      ? $startDt->format('M d, Y')
      : $startDt->format('M d, Y') . ' - ' . $endDt->format('M d, Y');
    $timeLine = $sameDate
      ? $startDt->format('g:i A') . ' - ' . $endDt->format('g:i A')
      : $startDt->format('M d, Y g:i A') . ' - ' . $endDt->format('M d, Y g:i A');
  } elseif ($startDt) {
    $dateLine = $startDt->format('M d, Y');
    $timeLine = $startDt->format('g:i A');
  } elseif ($endDt) {
    $dateLine = $endDt->format('M d, Y');
    $timeLine = $endDt->format('g:i A');
  }

  $lines = [];
  $lines[] = "Subject: {$title}";
  if ($dateLine !== '')    $lines[] = "Date: {$dateLine}";
  if ($timeLine !== '')    $lines[] = "Time: {$timeLine}";
  if ($location !== '')    $lines[] = "Location: {$location}";
  if ($description !== '') $lines[] = "Description: {$description}";

  if (!empty($agendas)) {
    $lines[] = "";
    $lines[] = "Agenda:";
    $no = 1;
    foreach ($agendas as $a) {
      $topic   = trim((string)($a['topic']   ?? $a['title'] ?? ''));
      $status  = trim((string)($a['status']  ?? 'pending'));
      $remarks = trim((string)($a['remarks'] ?? ''));
      if ($topic === '') continue;

      $extra = [];
      if ($status !== '' && $status !== 'pending') $extra[] = $status;
      if ($remarks !== '') $extra[] = $remarks;
      $suffix = $extra ? " (" . implode(" - ", $extra) . ")" : "";

      $lines[] = "{$no}. {$topic}{$suffix}";
      $no++;
    }
  }

  $smsMessage = trim(implode("\n", $lines));
}

function detectFileType(string $mime, string $ext): string {
  if (str_starts_with($mime, 'image/')) return 'image';
  if ($mime === 'application/pdf') return 'pdf';
  if (in_array($ext, ['doc','docx']) || str_contains($mime, 'word')) return 'doc';
  if (in_array($ext, ['xls','xlsx']) || str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel')) return 'xls';
  return 'other';
}

try {
  $pdo->beginTransaction();

  // 1) Insert schedule event
  $stmt = $pdo->prepare("
    INSERT INTO schedule_events
      (created_by, title, description, location,
       start_datetime, end_datetime,
       sms_message, send_sms_now,
       allow_auto_notify, notify_offset_minutes, status)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
  ");

  $stmt->execute([
    $userId,
    $title,
    $description !== '' ? $description : null,
    $location !== '' ? $location : null,
    $start,
    $end,
    $smsMessage !== '' ? $smsMessage : null,
    $sendSmsNow,
    $allowAuto,
    $offsetMin
  ]);

  $eventId = (int)$pdo->lastInsertId();

  // 2) Save agendas into event_agendas
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
        $eventId, $agNo, $agTopic,
        $agRemarks !== '' ? $agRemarks : null,
        $agStatus
      ]);
      $agNo++;
    }
  }

  // 3) Handle file uploads into event_attachments
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
        $eventId, $userId,
        detectFileType($mime, $ext),
        $origName, $stored,
        'backend/uploads/event_attachments/' . $stored,
        $mime, $size
      ]);
    }
  }

  // 4) Save selected recipients for conflict tracking / attendance.
  syncScheduleParticipants($pdo, $eventId, $finalContactIds);

  // 5) Save recipients into sms_outbox + send via Mocean if notify_sms=1
  $sentCount = 0;

  if (!empty($finalContactIds) && $smsMessage !== '') {
    $nowStr    = (new DateTime())->format('Y-m-d H:i:s');
    $autoSendAt = ($allowAuto === 1)
      ? (new DateTime($start))->modify("-{$offsetMin} minutes")->format('Y-m-d H:i:s')
      : $nowStr;

    $in2 = implode(',', array_fill(0, count($finalContactIds), '?'));
    $stmtMob = $pdo->prepare("
      SELECT id, mobile
      FROM contacts
      WHERE id IN ($in2)
        AND is_active = 1
        AND mobile IS NOT NULL
        AND mobile <> ''
    ");
    $stmtMob->execute($finalContactIds);

    $stmtSms = $pdo->prepare("
      INSERT INTO sms_outbox
        (schedule_event_id, contact_id, to_mobile, message, send_at, send_type, status, queued_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    // Deduplicate by normalized mobile number; collect unique rows for dual-insert
    $uniqueRows  = [];
    $seenMobiles = [];

    foreach ($stmtMob->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $to   = trim((string)($row['mobile'] ?? ''));
      if ($to === '') continue;
      $norm = preg_replace('/[\s\-]/', '', $to);
      if (isset($seenMobiles[$norm])) continue;
      $seenMobiles[$norm] = true;
      $uniqueRows[] = ['id' => (int)$row['id'], 'mobile' => $to];
    }

    foreach ($uniqueRows as $r) {
      if ($notifySms === 1) {
        // Row 1: immediate send — call Mocean API now
        $result    = mocean_send_sms($r['mobile'], $smsMessage);
        $rowStatus = $result['success'] ? 'sent' : 'failed';
        if ($result['success']) $sentCount++;
      } else {
        $rowStatus = 'cancelled';
      }

      // Insert immediate/history row
      $stmtSms->execute([$eventId, $r['id'], $r['mobile'], $smsMessage, $nowStr, 'immediate', $rowStatus]);

      // Row 2: auto-notify row (future queued) — only if auto-notify enabled and send_at is in future
      if ($allowAuto === 1 && $autoSendAt > $nowStr) {
        $stmtSms->execute([$eventId, $r['id'], $r['mobile'], $smsMessage, $autoSendAt, 'auto', 'queued']);
      }
    }
  }

  // 6) activity log
  $stmtLog = $pdo->prepare("
    INSERT INTO activity_logs
      (user_id, action, entity_type, entity_id, description)
    VALUES (?, 'create', 'schedule_events', ?, ?)
  ");
  $stmtLog->execute([
    $userId,
    $eventId,
    "Created event: $title",
  ]);

  $pdo->commit();
  out(true, 'Event saved successfully.', ['event_id' => $eventId, 'sms_sent' => $sentCount]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  out(false, 'Server error.');
}
