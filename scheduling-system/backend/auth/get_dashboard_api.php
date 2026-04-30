<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/conn.php';

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
  exit;
}

try {
  $today   = date('Y-m-d');
  $weekEnd = date('Y-m-d', strtotime('+6 days'));

  // ── Stats ────────────────────────────────────────────────────────────────

  // Events today
  $s1 = $pdo->prepare("SELECT COUNT(*) FROM schedule_events WHERE DATE(start_datetime) = ? AND status != 'cancelled'");
  $s1->execute([$today]);
  $eventsToday = (int)$s1->fetchColumn();

  // Events this week (next 7 days, not counting today)
  $s2 = $pdo->prepare("SELECT COUNT(*) FROM schedule_events
    WHERE DATE(start_datetime) BETWEEN ? AND ? AND status != 'cancelled'");
  $s2->execute([$today, $weekEnd]);
  $eventsWeek = (int)$s2->fetchColumn();

  // Total active contacts
  $s3 = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_active = 1");
  $totalContacts = (int)$s3->fetchColumn();

  // SMS sent today
  $s4 = $pdo->prepare("SELECT COUNT(*) FROM sms_outbox WHERE DATE(queued_at) = ? AND status = 'sent'");
  $s4->execute([$today]);
  $smsSentToday = (int)$s4->fetchColumn();

  // ── Today's events with agendas ──────────────────────────────────────────

  $todayEvStmt = $pdo->prepare("
    SELECT
      se.id,
      se.title,
      se.schedule_type,
      se.location,
      se.start_datetime,
      se.end_datetime,
      se.status,
      se.description
    FROM schedule_events se
    WHERE DATE(se.start_datetime) = ?
      AND se.status != 'cancelled'
    ORDER BY se.start_datetime ASC
  ");
  $todayEvStmt->execute([$today]);
  $todayEvents = $todayEvStmt->fetchAll(PDO::FETCH_ASSOC);

  // Attach agendas to today's events
  if (!empty($todayEvents)) {
    $ids   = array_column($todayEvents, 'id');
    $in    = implode(',', array_fill(0, count($ids), '?'));
    $agSt  = $pdo->prepare("
      SELECT schedule_event_id, agenda_no, agenda_title, agenda_details, status
      FROM event_agendas
      WHERE schedule_event_id IN ($in)
      ORDER BY schedule_event_id, agenda_no
    ");
    $agSt->execute($ids);
    $agMap = [];
    foreach ($agSt->fetchAll(PDO::FETCH_ASSOC) as $ag) {
      $agMap[(int)$ag['schedule_event_id']][] = $ag;
    }
    foreach ($todayEvents as &$ev) {
      $ev['agendas'] = $agMap[(int)$ev['id']] ?? [];
    }
    unset($ev);
  }

  // ── Upcoming events (next 7 days, excluding today) ───────────────────────

  $upcomingStmt = $pdo->prepare("
    SELECT
      se.id,
      se.title,
      se.schedule_type,
      se.location,
      se.start_datetime,
      se.status
    FROM schedule_events se
    WHERE DATE(se.start_datetime) BETWEEN ? AND ?
      AND se.status != 'cancelled'
    ORDER BY se.start_datetime ASC
    LIMIT 10
  ");
  $upcomingStmt->execute([$today, $weekEnd]);
  $upcoming = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);

  // ── Recent SMS (last 5 sent) ─────────────────────────────────────────────

  $recentSmsStmt = $pdo->prepare("
    SELECT
      o.to_mobile,
      o.status,
      o.queued_at,
      se.title AS event_title
    FROM sms_outbox o
    LEFT JOIN schedule_events se ON se.id = o.schedule_event_id
    WHERE o.status IN ('sent', 'failed')
    ORDER BY o.queued_at DESC
    LIMIT 5
  ");
  $recentSmsStmt->execute();
  $recentSms = $recentSmsStmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success' => true,
    'stats' => [
      'events_today'   => $eventsToday,
      'events_week'    => $eventsWeek,
      'total_contacts' => $totalContacts,
      'sms_sent_today' => $smsSentToday,
    ],
    'today_events' => $todayEvents,
    'upcoming'     => $upcoming,
    'recent_sms'   => $recentSms,
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.', 'error' => 'Server error.']);
}
