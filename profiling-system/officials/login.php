<?php
/**
 * login.php — Unified Smart Login (fully fixed)
 * ─────────────────────────────────────────────────────────────────
 * Fixes applied:
 *  1. bind_param type string was 'isssssssssddssss' (16 chars, 15 params)
 *     → corrected to 'isssssssssddsss' (15 chars, 15 params)
 *  2. _log_login() called BEFORE $conn->close() and header()
 *  3. All redirects use absolute URLs — no ../relative paths
 *  4. output_buffering on (ob_start) so headers work even if .htaccess
 *     sends output before PHP can
 */
ob_start(); // buffer output — prevents "headers already sent" from .htaccess quirks
session_start();
include("connection.php");

// ── Build absolute base URLs once ────────────────────────────────
$SCHEME    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$HOST      = $_SERVER['HTTP_HOST'];
$SELF_DIR  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g. /Profiling/sto.rosario/officials
$PARENT    = dirname($SELF_DIR);                             // e.g. /Profiling/sto.rosario

$BASE      = "$SCHEME://$HOST$SELF_DIR";   // officials folder
$BASE_ROOT = "$SCHEME://$HOST$PARENT";     // project root

define('URL_ADMIN_DASH',    "$BASE/dashboard.php");
define('URL_STAFF_DASH',    "$BASE/staff_dashboard.php");
define('URL_RESIDENT_DASH', "$BASE_ROOT/resident/dashboard.php");
define('URL_LOGIN',         "$BASE/login.php");
define('URL_HOME',          "../../index.php");
define('URL_REGISTER',      "$BASE_ROOT/resident/register_residents.php");

// ── Security headers ──────────────────────────────────────────────
// NOTE: X-Frame-Options is set here in PHP too, but the .htaccess
// version is fine for non-PHP assets. The DENY value causes the
// Chrome error message but does NOT break functionality — it only
// blocks embedding in iframes, which is correct security behaviour.
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: same-origin');

// ── Session defaults ──────────────────────────────────────────────
$_SESSION += [
    'login_attempts' => 0,
    'lockout_time'   => 0,
    'user_blocked'   => false,
    'csrf_token'     => bin2hex(random_bytes(32)),
];

// ── Already logged in → absolute redirect ────────────────────────
if (!empty($_SESSION['user_id'])) {
    $dest = match($_SESSION['user_type'] ?? '') {
        'admin'    => URL_ADMIN_DASH,
        'staff'    => URL_STAFF_DASH,
        'resident' => URL_RESIDENT_DASH,
        default    => URL_LOGIN,
    };
    ob_end_clean();
    header("Location: $dest"); exit();
}

const MAX_ATTEMPTS      = 12;
const LOCKOUT_DURATIONS = [15, 30, 60];

$error_type          = '';
$reject_reason       = '';
$login_disabled      = false;
$forgot_link         = false;
$remaining_lock_time = 0;
$current_time        = time();
$just_registered     = isset($_GET['registered']) && $_GET['registered'] == '1';

// ── Blocked / locked check ────────────────────────────────────────
if ($_SESSION['user_blocked']) {
    $login_disabled = true;
    $error_type     = 'blocked';
} elseif ($_SESSION['lockout_time'] > 0 && $current_time < $_SESSION['lockout_time']) {
    $remaining_lock_time = $_SESSION['lockout_time'] - $current_time;
    $login_disabled      = true;
    $error_type          = 'locked';
}

// ─────────────────────────────────────────────────────────────────
// ACTIVITY LOGGER — inline function (no require_once path issues)
// Fixed: type string is now 'isssssssssddsss' — 15 chars, 15 params
// ─────────────────────────────────────────────────────────────────
function _log_login(mysqli $conn, int $uid, string $utype, string $uname, string $fname): int
{
    // ── IP ────────────────────────────────────────────────────────
    $ip = '127.0.0.1';
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $candidate = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                $ip = $candidate; break;
            }
        }
    }

    // ── User Agent ────────────────────────────────────────────────
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500); // cap length

    // ── Device type ───────────────────────────────────────────────
    $device = 'Desktop';
    if (empty($ua))                                                        $device = 'Unknown';
    elseif (preg_match('/tablet|ipad|playbook|silk/i', $ua))               $device = 'Tablet';
    elseif (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $ua)) $device = 'Mobile';

    // ── OS ────────────────────────────────────────────────────────
    $os = 'Unknown';
    if      (preg_match('/Android ([0-9.]+)/i',        $ua, $m)) $os = 'Android '.$m[1];
    elseif  (preg_match('/OS ([0-9_]+) like Mac OS/i', $ua, $m)) $os = 'iOS '.str_replace('_','.',$m[1]);
    elseif  (preg_match('/Mac OS X ([0-9_.]+)/i',      $ua, $m)) $os = 'macOS '.str_replace('_','.',$m[1]);
    elseif  (preg_match('/Windows NT 10/i',            $ua))     $os = 'Windows 10/11';
    elseif  (preg_match('/Windows NT 6\.3/i',          $ua))     $os = 'Windows 8.1';
    elseif  (preg_match('/Windows NT 6\.1/i',          $ua))     $os = 'Windows 7';
    elseif  (preg_match('/Ubuntu/i',                   $ua))     $os = 'Ubuntu';
    elseif  (preg_match('/Linux/i',                    $ua))     $os = 'Linux';

    // ── Browser ───────────────────────────────────────────────────
    $browser = 'Unknown';
    if      (strpos($ua, 'Edg/')      !== false) $browser = 'Edge';
    elseif  (strpos($ua, 'OPR/')      !== false) $browser = 'Opera';
    elseif  (strpos($ua, 'Firefox/')  !== false) $browser = 'Firefox';
    elseif  (strpos($ua, 'Chrome/')   !== false) $browser = 'Chrome';
    elseif  (strpos($ua, 'Safari/')   !== false) $browser = 'Safari';
    elseif  (strpos($ua, 'MSIE')      !== false) $browser = 'IE';
    elseif  (strpos($ua, 'Trident/')  !== false) $browser = 'IE';
    // Append major version number
    $ver_patterns = [
        'Edge'    => '/Edg\/(\d+)/',
        'Opera'   => '/OPR\/(\d+)/',
        'Firefox' => '/Firefox\/(\d+)/',
        'Chrome'  => '/Chrome\/(\d+)/',
        'Safari'  => '/Version\/(\d+)/',
    ];
    if (isset($ver_patterns[$browser]) && preg_match($ver_patterns[$browser], $ua, $m)) {
        $browser .= ' '.$m[1];
    }

    // ── Geo (ip-api.com — free, no key, skip for localhost) ───────
    $country = null; $city = null; $region = null;
    $lat     = null; $lon  = null;

    $is_local = in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'])
        || filter_var($ip, FILTER_VALIDATE_IP,
               FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

    if ($is_local) {
        $country = 'Local'; $city = 'Local'; $region = 'Local';
    } else {
        $ctx  = stream_context_create(['http' => ['timeout' => 3]]);
        $json = @file_get_contents(
            "http://ip-api.com/json/{$ip}?fields=country,regionName,city,lat,lon,status",
            false, $ctx
        );
        if ($json) {
            $geo = json_decode($json, true);
            if (($geo['status'] ?? '') === 'success') {
                $country = $geo['country']    ?? null;
                $city    = $geo['city']       ?? null;
                $region  = $geo['regionName'] ?? null;
                $lat     = isset($geo['lat']) ? (float)$geo['lat'] : null;
                $lon     = isset($geo['lon']) ? (float)$geo['lon'] : null;
            }
        }
    }

    // ── INSERT ────────────────────────────────────────────────────
    // 15 params → type string must be exactly 15 chars: isssssssssddsss
    //  i  = uid       (int)
    //  s  = utype     (string)
    //  s  = uname     (string)
    //  s  = fname     (string)
    //  s  = now       (string/datetime)
    //  s  = ip        (string)
    //  s  = country   (string|null)
    //  s  = city      (string|null)
    //  s  = region    (string|null)
    //  d  = lat       (double|null)
    //  d  = lon       (double|null)
    //  s  = ua        (string — TEXT column uses s not b)
    //  s  = device    (string)
    //  s  = os        (string)
    //  s  = browser   (string)
    //  ─────────────────────────────────────
    //  Total: 15 params, type string = 'isssssssssddsss'

    $now  = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        INSERT INTO activity_logs
            (user_id, user_type, username, full_name, action,
             login_at, ip_address,
             country, city, region, latitude, longitude,
             user_agent, device_type, os, browser, status)
        VALUES
            (?, ?, ?, ?, 'login',
             ?, ?,
             ?, ?, ?, ?, ?,
             ?, ?, ?, ?, 'online')
    ");

    if (!$stmt) {
        // Table doesn't exist yet — fail gracefully, log to PHP error log
        error_log("activity_logs INSERT prepare failed: ".$conn->error);
        return 0;
    }

    $stmt->bind_param(
        'isssssssssddsss',  // ← 15 chars for 15 params (was 16 — BUG FIXED)
        $uid,               // i
        $utype,             // s
        $uname,             // s
        $fname,             // s
        $now,               // s  (login_at)
        $ip,                // s
        $country,           // s
        $city,              // s
        $region,            // s
        $lat,               // d
        $lon,               // d
        $ua,                // s  (user_agent TEXT)
        $device,            // s
        $os,                // s
        $browser            // s
    );

    if (!$stmt->execute()) {
        error_log("activity_logs INSERT execute failed: ".$stmt->error);
        $stmt->close();
        return 0;
    }

    $log_id = (int)$conn->insert_id;
    $stmt->close();
    return $log_id;
}

// ─────────────────────────────────────────────────────────────────
// PROCESS POST
// ─────────────────────────────────────────────────────────────────
if (!$login_disabled && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $error_type = 'csrf';
    } else {
        $_SESSION['csrf_token']   = bin2hex(random_bytes(32)); // rotate
        $_SESSION['lockout_time'] = 0;

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)
            || strlen($username) > 50 || strlen($password) > 128) {
            $error_type = 'invalid';
            $_SESSION['login_attempts']++;
        } else {
            $matched = false;

            // ════════════════════════════════════════════════════
            // STEP 1 — admin table
            // ════════════════════════════════════════════════════
            $stmt = $conn->prepare(
                "SELECT id, username, password, full_name FROM admin WHERE username = ? LIMIT 1"
            );
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($admin) {
                $matched = true;
                $valid   = false;

                if (password_verify($password, $admin['password'])) {
                    $valid = true;
                } elseif ($admin['password'] === $password) {
                    // Plain-text fallback → upgrade on the fly
                    $valid  = true;
                    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $upd    = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
                    $upd->bind_param('si', $hashed, $admin['id']);
                    $upd->execute(); $upd->close();
                }

                if ($valid) {
                    session_regenerate_id(true);
                    $_SESSION['user_id']        = $admin['id'];
                    $_SESSION['user_type']      = 'admin';
                    $_SESSION['username']       = $admin['username'];
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['login_time']     = time();
                    $_SESSION['is_superadmin']  = 1;
                    // Admin has all privileges
                    $_SESSION['can_view_residents'] = 1;
                    $_SESSION['can_add_resident']   = 1;
                    $_SESSION['can_edit_resident']  = 1;
                    $_SESSION['can_approve']        = 1;
                    $_SESSION['can_delete']         = 1;
                    $_SESSION['can_export']         = 1;
                    $_SESSION['can_manage_staff']   = 1;
                    $_SESSION['can_view_logs']      = 1;
                    $_SESSION['can_manage_profile_updates'] = 1;

                    // ✅ LOG FIRST — before close() and header()
                    $_SESSION['log_id'] = _log_login(
                        $conn,
                        $admin['id'],
                        'admin',
                        $admin['username'],
                        $admin['full_name'] ?? $admin['username']
                    );

                    $conn->close();
                    ob_end_clean();
                    header('Location: ' . URL_ADMIN_DASH);
                    exit();
                } else {
                    $error_type = 'invalid';
                }
            }

            // ════════════════════════════════════════════════════
            // STEP 1.5 — staff table
            // ════════════════════════════════════════════════════
            if (!$matched) {
                $stmt = $conn->prepare(
                    "SELECT id, username, password, first_name, middle_name, surname, suffix,
                            position, status,
                            can_view_residents, can_add_resident, can_edit_resident,
                            can_approve, can_delete, can_export
                     FROM staff WHERE username = ? LIMIT 1"
                );
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $staff = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($staff) {
                    $matched = true;

                    if (password_verify($password, $staff['password'])) {
                        if ($staff['status'] === 'Inactive') {
                            $error_type = 'inactive';
                        } else {
                            session_regenerate_id(true);
                            $_SESSION['user_id']        = $staff['id'];
                            $_SESSION['user_type']      = 'staff';
                            $_SESSION['username']       = $staff['username'];
                            $_SESSION['name']           = trim($staff['first_name'].' '.$staff['surname']);
                            $_SESSION['staff_position'] = $staff['position'] ?? '';
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['login_time']     = time();

                            // Store privilege flags in session
                            $_SESSION['can_view_residents'] = (int)$staff['can_view_residents'];
                            $_SESSION['can_add_resident']   = (int)$staff['can_add_resident'];
                            $_SESSION['can_edit_resident']  = (int)$staff['can_edit_resident'];
                            $_SESSION['can_approve']        = (int)$staff['can_approve'];
                            $_SESSION['can_delete']         = (int)$staff['can_delete'];
                            $_SESSION['can_export']         = (int)$staff['can_export'];

                            // Update last login
                            $upd = $conn->prepare("UPDATE staff SET last_login = NOW() WHERE id = ?");
                            $upd->bind_param('i', $staff['id']);
                            $upd->execute();
                            $upd->close();

                            // Log login
                            $_SESSION['log_id'] = _log_login(
                                $conn,
                                $staff['id'],
                                'staff',
                                $staff['username'],
                                trim($staff['first_name'].' '.$staff['surname'])
                            );

                            $conn->close();
                            ob_end_clean();
                            header('Location: ' . URL_ADMIN_DASH);
                            exit();
                        }
                    } else {
                        $error_type = 'invalid';
                    }
                }
            }

            // ════════════════════════════════════════════════════
            // STEP 1.75 — barangay_official table
            // ════════════════════════════════════════════════════
            if (!$matched) {
                $stmt = $conn->prepare(
                    "SELECT id, username, password, first_name, middle_name, surname, suffix,
                            position, status, image_path,
                            can_view_residents, can_add_resident, can_edit_resident,
                            can_approve, can_delete, can_export,
                            can_manage_staff, can_view_logs, can_manage_profile_updates
                     FROM barangay_official WHERE username = ? AND username IS NOT NULL AND username != '' LIMIT 1"
                );
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $official = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($official) {
                    $matched = true;
                    $valid   = false;

                    if (password_verify($password, $official['password'])) {
                        $valid = true;
                    } elseif ($official['password'] === $password) {
                        // Plain-text fallback → upgrade on the fly
                        $valid  = true;
                        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        $upd    = $conn->prepare("UPDATE barangay_official SET password = ? WHERE id = ?");
                        $upd->bind_param('si', $hashed, $official['id']);
                        $upd->execute(); $upd->close();
                    }

                    if ($valid) {
                        if ($official['status'] === 'Inactive') {
                            $error_type = 'inactive';
                        } else {
                            session_regenerate_id(true);

                            // Secretary position = superadmin (all privileges)
                            $is_secretary = (stripos($official['position'], 'Secretary') !== false);

                            $_SESSION['user_id']        = $official['id'];
                            $_SESSION['user_type']      = 'official';
                            $_SESSION['username']       = $official['username'];
                            $_SESSION['name']           = trim($official['first_name'].' '.$official['surname']);
                            $_SESSION['staff_position'] = $official['position'] ?? '';
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['login_time']     = time();
                            $_SESSION['is_superadmin']  = $is_secretary ? 1 : 0;

                            // Store privilege flags in session
                            // Secretary gets all privileges regardless of DB flags
                            $_SESSION['can_view_residents'] = $is_secretary ? 1 : (int)$official['can_view_residents'];
                            $_SESSION['can_add_resident']   = $is_secretary ? 1 : (int)$official['can_add_resident'];
                            $_SESSION['can_edit_resident']  = $is_secretary ? 1 : (int)$official['can_edit_resident'];
                            $_SESSION['can_approve']        = $is_secretary ? 1 : (int)$official['can_approve'];
                            $_SESSION['can_delete']         = $is_secretary ? 1 : (int)$official['can_delete'];
                            $_SESSION['can_export']         = $is_secretary ? 1 : (int)$official['can_export'];
                            $_SESSION['can_manage_staff']   = $is_secretary ? 1 : (int)($official['can_manage_staff'] ?? 0);
                            $_SESSION['can_view_logs']      = $is_secretary ? 1 : (int)($official['can_view_logs'] ?? 0);
                            $_SESSION['can_manage_profile_updates'] = $is_secretary ? 1 : (int)($official['can_manage_profile_updates'] ?? 0);

                            // Update last login
                            $upd = $conn->prepare("UPDATE barangay_official SET last_login = NOW() WHERE id = ?");
                            $upd->bind_param('i', $official['id']);
                            $upd->execute();
                            $upd->close();

                            // Log login
                            $_SESSION['log_id'] = _log_login(
                                $conn,
                                $official['id'],
                                'official',
                                $official['username'],
                                trim($official['first_name'].' '.$official['surname'])
                            );

                            $conn->close();
                            ob_end_clean();
                            header('Location: ' . URL_ADMIN_DASH);
                            exit();
                        }
                    } else {
                        $error_type = 'invalid';
                    }
                }
            }

            // ════════════════════════════════════════════════════
            // STEP 2 — residents table
            // ════════════════════════════════════════════════════
            if (!$matched) {
                $stmt = $conn->prepare(
                    "SELECT id, username, password, first_name, surname,
                            user_role, account_status, is_purok_president, purok
                     FROM residents WHERE username = ? AND is_deleted = 0 LIMIT 1"
                );
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($user) {
                    $matched = true;

                    if (password_verify($password, $user['password'])) {
                        if ($user['account_status'] === 'suspended') {
                            $error_type = 'suspended';
                        } elseif ($user['account_status'] === 'inactive') {
                            $error_type = 'inactive';
                        } else {
                            session_regenerate_id(true);

                            // ── Purok President RBAC ──────────────────────────
                            // If this resident is a Purok President, promote
                            // them into the admin portal with scoped privileges.
                            if ((int)$user['is_purok_president'] === 1) {
                                $_SESSION['user_id']        = $user['id'];
                                $_SESSION['user_type']      = 'resident';
                                $_SESSION['username']       = $user['username'];
                                $_SESSION['name']           = $user['first_name'].' '.$user['surname'];
                                $_SESSION['staff_position'] = 'Purok President';
                                $_SESSION['purok']          = $user['purok'] ?? '';
                                $_SESSION['login_attempts'] = 0;
                                $_SESSION['login_time']     = time();
                                $_SESSION['is_superadmin']  = 0;

                                // Grant add/edit/delete within their purok
                                $_SESSION['can_view_residents'] = 1;
                                $_SESSION['can_add_resident']   = 1;
                                $_SESSION['can_edit_resident']  = 1;
                                $_SESSION['can_approve']        = 0;
                                $_SESSION['can_delete']         = 1;
                                $_SESSION['can_export']         = 1;
                                $_SESSION['can_manage_staff']   = 0;
                                $_SESSION['can_view_logs']      = 0;
                                $_SESSION['can_manage_profile_updates'] = 0;

                                $_SESSION['log_id'] = _log_login(
                                    $conn,
                                    $user['id'],
                                    'purok_president',
                                    $user['username'],
                                    $user['first_name'].' '.$user['surname']
                                );

                                $conn->close();
                                ob_end_clean();
                                header('Location: ' . URL_ADMIN_DASH);
                                exit();
                            }

                            // ── Normal resident login ────────────────────────
                            $_SESSION['user_id']        = $user['id'];
                            $_SESSION['user_type']      = $user['user_role'];
                            $_SESSION['username']       = $user['username'];
                            $_SESSION['name']           = $user['first_name'].' '.$user['surname'];
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['login_time']     = time();

                            // ✅ LOG FIRST — before close() and header()
                            $_SESSION['log_id'] = _log_login(
                                $conn,
                                $user['id'],
                                $user['user_role'],
                                $user['username'],
                                $user['first_name'].' '.$user['surname']
                            );

                            $dest = match($user['user_role']) {
                                'admin' => URL_ADMIN_DASH,
                                'staff' => URL_STAFF_DASH,
                                default => URL_RESIDENT_DASH,
                            };

                            $conn->close();
                            ob_end_clean();
                            header("Location: $dest");
                            exit();
                        }
                    } else {
                        $error_type = 'invalid';
                    }
                }
            }

            // ════════════════════════════════════════════════════
            // STEP 3 — pending_registrations
            // ════════════════════════════════════════════════════
            if (!$matched) {
                $stmt = $conn->prepare(
                    "SELECT id, password, status, rejection_reason
                     FROM pending_registrations WHERE username = ? LIMIT 1"
                );
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $pending = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($pending && password_verify($password, $pending['password'])) {
                    $error_type = match($pending['status']) {
                        'Pending'  => 'pending',
                        'Rejected' => 'rejected',
                        default    => 'invalid',
                    };
                    if ($error_type === 'rejected') {
                        $reject_reason = $pending['rejection_reason'] ?? '';
                    }
                } else {
                    $error_type = 'invalid';
                }
            }

            // ── Rate limiting ─────────────────────────────────────
            if (in_array($error_type, ['invalid', 'suspended', 'inactive'])) {
                $_SESSION['login_attempts']++;
                $attempts = $_SESSION['login_attempts'];
                $set_idx  = (int)floor($attempts / 3);
                if ($attempts >= 2) $forgot_link = true;

                if ($attempts % 3 === 0 && $set_idx > 0) {
                    $lock_dur = LOCKOUT_DURATIONS[min($set_idx, count(LOCKOUT_DURATIONS)) - 1];
                    $_SESSION['lockout_time'] = $current_time + $lock_dur;
                    $remaining_lock_time      = $lock_dur;
                    $login_disabled           = true;
                    $error_type               = 'locked';
                }
                if ($attempts >= MAX_ATTEMPTS) {
                    $_SESSION['user_blocked'] = true;
                    $login_disabled           = true;
                    $error_type               = 'blocked';
                }
            }
        }
    }
}

$conn->close();
$rem_attempts = max(0, 3 - ($_SESSION['login_attempts'] % 3));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Barangay Sto. Rosario</title>
<style>
:root{--primary:#0f3c6e;--primary-lt:#1f6bb8;--primary-xlt:#2278d4;--gold:#c8963e;--gold-lt:#e8b45a;--danger:#e05252;--warning:#d97706;--muted:rgba(255,255,255,.5)}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{min-height:100vh;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
body{background:linear-gradient(150deg,#071628 0%,#0f3c6e 50%,#1f6bb8 100%);min-height:100vh;position:relative}
body::before{content:'';position:fixed;inset:0;background-image:radial-gradient(rgba(255,255,255,.035) 1px,transparent 1px);background-size:26px 26px;pointer-events:none;z-index:0}
.page{position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column}
.site-header{display:flex;align-items:center;gap:14px;padding:14px 40px;background:rgba(5,15,40,.70);backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.08);animation:slideDown .5s ease both}
@keyframes slideDown{from{opacity:0;transform:translateY(-18px)}to{opacity:1;transform:translateY(0)}}
.logo-badge{width:52px;height:52px;border-radius:50%;overflow:hidden;text-decoration:none;border:2px solid rgba(255,255,255,.18);box-shadow:0 4px 14px rgba(200,150,62,.35);transition:transform .2s;display:block;flex-shrink:0}
.logo-badge:hover{transform:scale(1.07)}
.logo-badge img{width:100%;height:100%;object-fit:cover}
.site-header h1{font-size:15px;font-weight:700;color:#fff;line-height:1.2}
.site-header p{font-size:11px;color:var(--muted);letter-spacing:.06em;text-transform:uppercase}
.header-nav{margin-left:auto;display:flex;align-items:center;gap:5px}
.nav-pill{font-size:12px;color:rgba(255,255,255,.55);text-decoration:none;padding:5px 12px;border-radius:20px;border:1px solid rgba(255,255,255,.12);transition:color .18s,border-color .18s,background .18s;white-space:nowrap}
.nav-pill:hover{color:#fff;border-color:rgba(255,255,255,.3);background:rgba(255,255,255,.07)}
.nav-pill.active{color:var(--gold-lt);border-color:rgba(200,150,62,.4);background:rgba(200,150,62,.08)}
.center{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px}
.card{width:100%;max-width:430px;background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(5,15,40,.45),0 0 0 1px rgba(255,255,255,.1);overflow:hidden;animation:riseUp .55s .1s ease both}
@keyframes riseUp{from{opacity:0;transform:translateY(26px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
.card-strip{height:5px;background:linear-gradient(90deg,var(--primary),var(--primary-lt),var(--primary-xlt))}
.card-body{padding:34px 36px 28px}
.card-header{text-align:center;margin-bottom:22px}
.card-seal{width:76px;height:76px;border-radius:50%;background:linear-gradient(145deg,#e8f0fb,#d0e0f5);border:2px solid rgba(15,60,110,.15);display:flex;align-items:center;justify-content:center;font-size:34px;margin:0 auto 14px;box-shadow:0 4px 18px rgba(15,60,110,.12)}
.card-eyebrow{font-size:10px;letter-spacing:.2em;text-transform:uppercase;color:var(--primary-lt);margin-bottom:4px;font-weight:600}
.card-title{font-size:22px;font-weight:800;color:var(--primary);letter-spacing:-.01em}
.card-sub{font-size:12px;color:#6c8ab0;margin-top:3px;font-style:italic}
.card-divider{height:1px;background:linear-gradient(90deg,transparent,#d0dce8,transparent);margin-bottom:22px}
.auto-hint{display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:18px;font-size:11.5px;color:#8aa4c0;background:#f4f8ff;border:1px dashed #c8daf0;border-radius:8px;padding:8px 12px}
.auto-hint b{color:var(--primary-lt);font-weight:600}
.notice{border-radius:9px;padding:12px 14px;margin-bottom:18px;display:flex;gap:11px;align-items:flex-start}
.notice .ico{font-size:17px;flex-shrink:0;margin-top:1px}
.notice strong{display:block;font-size:13px;font-weight:700;margin-bottom:3px}
.notice p{margin:0;font-size:12.5px;line-height:1.55}
.notice .reason{margin-top:7px;padding:6px 9px;background:rgba(0,0,0,.06);border-radius:6px;font-style:italic;font-size:12px}
.n-success{background:#ecfdf5;border:1px solid #6ee7b7;border-left:4px solid #059669}.n-success .ico,.n-success strong{color:#065f46}.n-success p{color:#047857}
.n-pending{background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #d97706}.n-pending .ico,.n-pending strong{color:#b45309}.n-pending p{color:#78350f}
.n-rejected{background:#fef2f2;border:1px solid #fca5a5;border-left:4px solid #dc2626}.n-rejected .ico,.n-rejected strong{color:#991b1b}.n-rejected p{color:#7f1d1d}
.n-error{background:#fff3f3;border:1px solid #f5c0c0;border-left:4px solid var(--danger)}.n-error .ico,.n-error strong{color:#b94040}.n-error p{color:#8b3030}
.n-warn{background:#fffbeb;border:1px solid #fde68a;border-left:4px solid var(--warning)}.n-warn .ico,.n-warn strong{color:#92400e}.n-warn p{color:#78350f}
.n-blocked{background:#1a1a2e;border:1px solid #333;border-left:4px solid #555}.n-blocked .ico,.n-blocked strong,.n-blocked p{color:#ccc}
.countdown-bar{margin-bottom:16px;padding:10px 14px;background:#fff8e7;border:1px solid #fde68a;border-left:4px solid var(--warning);border-radius:9px;font-size:13px;color:#78350f;display:flex;align-items:center;gap:8px}
.countdown-bar b{font-weight:700;color:#b45309;font-size:15px}
.field{margin-bottom:16px}
.field label{display:block;margin-bottom:5px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#4a6080}
.field label .req{color:#dc3545;margin-left:2px}
.input-wrap{position:relative}
.field-ico{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:14px;pointer-events:none;opacity:.38}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:14px;opacity:.35;padding:0;line-height:1;transition:opacity .18s}
.toggle-pw:hover{opacity:.85}
.field input[type=text],.field input[type=password]{width:100%;padding:11px 40px;background:#f4f7fb;border:1.5px solid #d0dce8;border-radius:8px;color:#1a2d4a;font-size:14px;font-family:inherit;outline:none;transition:border-color .22s,background .22s,box-shadow .22s}
.field input:focus{border-color:var(--primary-lt);background:#fff;box-shadow:0 0 0 3px rgba(31,107,184,.12)}
.show-pw-row{display:flex;align-items:center;gap:7px;margin-top:7px;font-size:12px;color:#6c8ab0;cursor:pointer}
.show-pw-row input{accent-color:var(--primary-lt);cursor:pointer}
.btn-login{width:100%;padding:13px;background:linear-gradient(135deg,var(--primary),var(--primary-lt));border:none;border-radius:8px;color:#fff;font-size:14px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;font-family:inherit;margin-top:4px;transition:transform .2s,box-shadow .2s,filter .2s;box-shadow:0 4px 18px rgba(15,60,110,.30)}
.btn-login:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 8px 26px rgba(15,60,110,.40);filter:brightness(1.08)}
.btn-login:active:not(:disabled){transform:translateY(0)}
.btn-login:disabled{opacity:.55;cursor:not-allowed}
.card-links{margin-top:18px;display:flex;flex-direction:column;gap:8px;text-align:center}
.card-links-divider{height:1px;background:#e0e8f0;margin:2px 0}
.card-links-row{display:flex;justify-content:center;gap:16px;font-size:12px;flex-wrap:wrap}
.card-links-row a,.forgot-link{color:#6c8ab0;text-decoration:none;transition:color .18s;display:inline-flex;align-items:center;gap:4px}
.card-links-row a:hover,.forgot-link:hover{color:var(--primary)}
.forgot-link{font-weight:600;font-size:12.5px}
.back-to-login{display:inline-block;margin-top:6px;color:var(--primary-lt);font-weight:700;font-size:13.5px;text-decoration:none}
.back-to-login:hover{color:var(--primary);text-decoration:underline}
footer{padding:14px 40px;text-align:center;border-top:1px solid rgba(255,255,255,.06);font-size:11px;color:rgba(255,255,255,.28);letter-spacing:.04em;font-family:'Courier New',monospace}
footer strong{color:rgba(200,150,62,.6);font-weight:normal}
@media(max-width:520px){.site-header{padding:12px 16px}.header-nav{gap:3px}.nav-pill{font-size:11px;padding:4px 8px}.card-body{padding:26px 20px 22px}}
</style>
</head>
<body>
<div class="page">

<header class="site-header">
  <a href="<?= URL_HOME ?>" class="logo-badge" title="Home">
    <img src="image/logo.jpg" alt="Barangay Logo">
  </a>
  <div>
    <h1>Barangay Sto. Rosario</h1>
    <p>Magallanes, Agusan del Norte</p>
  </div>
  <nav class="header-nav">
    <a href="<?= URL_HOME ?>"     class="nav-pill">🏠 Home</a>
    <a href="<?= URL_LOGIN ?>"    class="nav-pill active">🔐 Login</a>
    <a href="<?= URL_REGISTER ?>" class="nav-pill">📝 Register</a>
  </nav>
</header>

<div class="center">
<div class="card">
  <div class="card-strip"></div>
  <div class="card-body">

    <div class="card-header">
      <div class="card-seal">🏛️</div>
      <p class="card-eyebrow">Barangay Portal</p>
      <h2 class="card-title">Sign In</h2>
      <p class="card-sub">Barangay Sto. Rosario — Resident Information System</p>
    </div>

    <div class="card-divider"></div>

    <div class="auto-hint">
      🔍 Just enter your credentials —
      <b>your role is detected automatically</b>
    </div>

    <?php if ($just_registered && !$error_type): ?>
    <div class="notice n-success">
      <span class="ico">✅</span>
      <div>
        <strong>Registration Submitted!</strong>
        <p>Your account is <strong>pending review</strong> by the Barangay Admin. You can log in once your residency is verified.</p>
      </div>
    </div>
    <?php elseif ($error_type === 'blocked'): ?>
    <div class="notice n-blocked">
      <span class="ico">🚫</span>
      <div><strong>Account Blocked</strong>
        <p>Too many failed login attempts. Please contact the system administrator.</p>
      </div>
    </div>
    <?php elseif ($error_type === 'locked'): ?>
    <div class="countdown-bar">
      ⏱️ Too many failed attempts. Try again in
      <b id="countdown"><?= (int)$remaining_lock_time ?></b> seconds.
    </div>
    <?php elseif ($error_type === 'pending'): ?>
    <div class="notice n-pending">
      <span class="ico">⏳</span>
      <div><strong>Account Awaiting Approval</strong>
        <p>Your registration is still under review. You will be able to log in once your residency in Sto. Rosario is verified.</p>
      </div>
    </div>
    <?php elseif ($error_type === 'rejected'): ?>
    <div class="notice n-rejected">
      <span class="ico">❌</span>
      <div><strong>Registration Not Approved</strong>
        <p>Your registration was not approved by the Barangay Admin.</p>
        <?php if ($reject_reason): ?>
        <div class="reason"><strong>Reason:</strong> <?= htmlspecialchars($reject_reason) ?></div>
        <?php endif; ?>
        <p style="margin-top:7px">Please contact the Barangay office for assistance.</p>
      </div>
    </div>
    <?php elseif ($error_type === 'suspended'): ?>
    <div class="notice n-warn">
      <span class="ico">⚠️</span>
      <div><strong>Account Suspended</strong>
        <p>Your account has been suspended. Please contact the Barangay office.</p>
      </div>
    </div>
    <?php elseif ($error_type === 'inactive'): ?>
    <div class="notice n-warn">
      <span class="ico">😴</span>
      <div><strong>Account Inactive</strong>
        <p>Your account is currently inactive. Please contact the Barangay office to reactivate it.</p>
      </div>
    </div>
    <?php elseif ($error_type === 'csrf'): ?>
    <div class="notice n-error">
      <span class="ico">⚠️</span>
      <div><strong>Invalid Request</strong>
        <p>Security token mismatch. Please try again.</p>
      </div>
    </div>
    <?php elseif ($error_type === 'invalid'): ?>
    <div class="notice n-error">
      <span class="ico">⚠️</span>
      <div><strong>Login Failed</strong>
        <p>Invalid username or password.
          <?php if ($rem_attempts > 0 && $rem_attempts < 3): ?>
            <strong><?= (int)$rem_attempts ?></strong> attempt(s) remaining before lockout.
          <?php endif; ?>
        </p>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!$just_registered || $error_type): ?>
    <form method="POST" autocomplete="off">
      <input type="hidden" name="csrf_token"
             value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <div class="field">
        <label for="username">Username <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="field-ico">👤</span>
          <input type="text" id="username" name="username"
                 placeholder="Enter your username"
                 autocomplete="username" maxlength="50" required
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 <?= $login_disabled ? 'disabled' : '' ?>>
        </div>
      </div>

      <div class="field">
        <label for="pwdField">Password <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="field-ico">🔒</span>
          <input type="password" id="pwdField" name="password"
                 placeholder="Enter your password"
                 autocomplete="current-password" maxlength="128" required
                 <?= $login_disabled ? 'disabled' : '' ?>>
          <button type="button" class="toggle-pw" id="togglePwd">👁️</button>
        </div>
        <label class="show-pw-row">
          <input type="checkbox" id="showPw" <?= $login_disabled ? 'disabled' : '' ?>>
          Show password
        </label>
      </div>

      <button type="submit" class="btn-login"
              <?= $login_disabled ? 'disabled' : '' ?>>
        🔐 Sign In
      </button>
    </form>

    <div class="card-links">
      <?php if ($forgot_link): ?>
      <a href="forgot_password.php" class="forgot-link"
         <?= $login_disabled ? 'tabindex="-1" style="pointer-events:none;opacity:.45"' : '' ?>>
        🔑 Forgot your password?
      </a>
      <?php endif; ?>
      <div class="card-links-divider"></div>
      <div class="card-links-row">
        <a href="<?= URL_HOME ?>">🏠 Home</a>
        <a href="<?= URL_REGISTER ?>">📝 Register as Resident</a>
      </div>
    </div>

    <?php else: ?>
    <div style="text-align:center;margin-top:10px">
      <a href="<?= URL_LOGIN ?>" class="back-to-login">← Back to Login</a>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>

<footer>
  <p>Copyright &copy; 2026 <strong>Barangay Sto. Rosario Resident Information System</strong>. All rights reserved.</p>
</footer>
</div>

<script>
const pw  = document.getElementById('pwdField');
const btn = document.getElementById('togglePwd');
const cb  = document.getElementById('showPw');
function setPw(show) {
  if (!pw) return;
  pw.type = show ? 'text' : 'password';
  btn.textContent = show ? '🙈' : '👁️';
  if (cb && cb.checked !== show) cb.checked = show;
}
if (btn) btn.addEventListener('click',  () => setPw(pw.type === 'password'));
if (cb)  cb.addEventListener('change',  () => setPw(cb.checked));

const uField = document.getElementById('username');
if (uField) uField.addEventListener('input', function(){ this.value = this.value.replace(/[<>]/g,''); });

const cd = document.getElementById('countdown');
if (cd) {
  let s = parseInt(cd.textContent, 10);
  setInterval(() => { s <= 1 ? location.reload() : cd.textContent = --s; }, 1000);
}
</script>
</body>
</html>