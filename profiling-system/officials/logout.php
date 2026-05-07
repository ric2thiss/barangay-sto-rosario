<?php
/**
 * logout.php — Ends session, writes logout to activity_logs, absolute redirect
 */
session_start();

include('connection.php');

// ── Absolute URL to login page ─────────────────────────────────────
$BASE_LOGIN = rtrim(
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . dirname($_SERVER['SCRIPT_NAME']),
    '/\\'
) . '/login.php';

// ── Mark logout in activity_logs ───────────────────────────────────
if (!empty($_SESSION['log_id'])) {
    $log_id = (int)$_SESSION['log_id'];
    $now    = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        UPDATE activity_logs
        SET logout_at    = ?,
            duration_sec = TIMESTAMPDIFF(SECOND, login_at, ?),
            status       = 'offline',
            action       = 'logout'
        WHERE id = ? AND status = 'online'
    ");
    if ($stmt) {
        $stmt->bind_param('ssi', $now, $now, $log_id);
        $stmt->execute();
        $stmt->close();
    }
}
$conn->close();

// ── Destroy session completely ─────────────────────────────────────
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// ── Absolute redirect — no relative paths ─────────────────────────
header("Location: $BASE_LOGIN");
exit();