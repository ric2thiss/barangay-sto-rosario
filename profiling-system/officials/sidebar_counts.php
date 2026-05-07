<?php
// sidebar_counts.php
// ── Shared sidebar notification counts ───────────────────────────────────
// Include this BEFORE rendering the sidebar on any admin page.
// Requires $conn (mysqli) to already be open.

$pending_reg = 0;
$pending_profile_updates = 0;

if (isset($conn) && $conn) {
    $r1 = $conn->query("SELECT COUNT(*) c FROM pending_registrations WHERE status='Pending'");
    if ($r1) $pending_reg = (int)$r1->fetch_assoc()['c'];

    $r2 = $conn->query("SELECT COUNT(*) c FROM profile_update_requests WHERE status='Pending'");
    if ($r2) $pending_profile_updates = (int)$r2->fetch_assoc()['c'];
}