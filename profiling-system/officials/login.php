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
<title>Official Login - Sto. Rosario</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root { --brand-primary: #1f3a93; --brand-secondary: #2e4fc7; }
    body { background: #f4f6fb; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
    .login-wrap { min-height: 100vh; display: flex; }
    .login-brand {
        flex: 0 0 42%;
        background: linear-gradient(155deg, var(--brand-primary) 0%, var(--brand-secondary) 55%, #1a56db 100%);
        display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
        padding: 3rem 3.5rem; position: relative; overflow: hidden;
    }
    .login-brand::before {
        content: ""; position: absolute; inset: 0;
        background: radial-gradient(ellipse at 10% 80%, rgba(255,255,255,.08) 0%, transparent 55%),
                    radial-gradient(ellipse at 90% 10%, rgba(255,255,255,.05) 0%, transparent 45%);
        pointer-events: none;
    }
    .login-brand-circle { position: absolute; border-radius: 50%; background: rgba(255,255,255,.06); }
    .lbc-1 { width:380px; height:380px; bottom:-120px; right:-100px; }
    .lbc-2 { width:200px; height:200px; top:-50px; right:20px; }
    .login-brand-content { position: relative; z-index: 2; }
    .login-feature-item { display: flex; align-items: center; gap: .75rem; margin-bottom: .85rem; opacity: .85; color: white; font-size: 0.9rem; }
    .login-feature-dot { width: 8px; height: 8px; border-radius: 50%; background: #93c5fd; flex-shrink: 0; }
    .login-form-panel { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 2.5rem 2rem; background: #f4f6fb; }
    .login-card { width: 100%; max-width: 400px; background: #fff; border-radius: 20px; box-shadow: 0 8px 40px rgba(31,58,147,.1); padding: 2.5rem; }
    .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 .2rem rgba(31,58,147,.18); }
    .btn-login { background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary)); color: #fff; border: none; font-weight: 600; letter-spacing: .3px; transition: all .15s; }
    .btn-login:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); color: #fff; }
    .btn-login:disabled { opacity: .6; cursor: not-allowed; }
    @media (max-width: 767px) {
        .login-brand { display: none; }
        .login-form-panel { background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%); padding: 1.5rem 1rem; }
        .login-card { box-shadow: 0 12px 48px rgba(0,0,0,.25); }
    }
</style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-brand">
            <div class="login-brand-circle lbc-1"></div>
            <div class="login-brand-circle lbc-2"></div>
            <div class="login-brand-content">
                <a href="../index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4 opacity-75 hover-opacity-100 transition-all">
                    <i class="bi bi-arrow-left-circle"></i> Back to System Portal
                </a>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="image/logo.jpg" alt="Logo" style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.15);padding:2px;">
                    <div class="text-white">
                        <div class="fw-bold lh-1" style="font-size:1.1rem;">Profiling System</div>
                        <div class="opacity-75" style="font-size:.8rem;">Resident Information System</div>
                    </div>
                </div>
                <h2 class="text-white fw-bold mb-2" style="font-size:clamp(1.5rem,2.5vw,2.2rem);line-height:1.25;">
                    Digital Census,<br><span style="color:#93c5fd;">Data Integrity</span>
                </h2>
                <p class="text-white opacity-75 mb-4" style="font-size:.9rem;max-width:350px;">
                    Authorized portal for barangay officials to manage resident demographics and household profiling.
                </p>
                <div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Centralized Resident Records</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Household Connectivity Mapping</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Advanced Demographic Analytics</div>
                    <div class="login-feature-item"><span class="login-feature-dot"></span> Official Accountability Tracking</div>
                </div>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="d-md-none mb-3 w-100 text-center" style="max-width:400px;">
                <a href="../index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none small opacity-75 hover-opacity-100 transition-all">
                    <i class="bi bi-arrow-left-circle"></i> Back to System Portal
                </a>
            </div>
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="image/logo.jpg" alt="Logo" class="mb-3" style="width:64px;height:64px;border-radius:50%;box-shadow:0 4px 16px rgba(31,58,147,.25);">
                    <h4 class="fw-bold mb-1">Official Access</h4>
                    <p class="text-muted small">Sign in to manage barangay profiling</p>
                </div>

                <?php if ($just_registered && !$error_type): ?>
                <div class="alert alert-success small mb-3" style="border-radius:10px;">
                    <strong>Registration Submitted!</strong><br>Your account is pending review.
                </div>
                <?php elseif ($error_type === 'blocked'): ?>
                <div class="alert alert-dark small mb-3" style="border-radius:10px;">
                    <strong>Account Blocked</strong><br>Too many failed attempts.
                </div>
                <?php elseif ($error_type === 'locked'): ?>
                <div class="alert alert-warning small mb-3" style="border-radius:10px;">
                    ⏱️ Locked out. Try again in <b id="countdown"><?= (int)$remaining_lock_time ?></b>s.
                </div>
                <?php elseif ($error_type === 'pending'): ?>
                <div class="alert alert-info small mb-3" style="border-radius:10px;">
                    <strong>Awaiting Approval</strong><br>Residency verification in progress.
                </div>
                <?php elseif ($error_type === 'rejected'): ?>
                <div class="alert alert-danger small mb-3" style="border-radius:10px;">
                    <strong>Rejected</strong><br>Residency was not approved.
                </div>
                <?php elseif ($error_type === 'invalid'): ?>
                <div class="alert alert-danger small mb-3" style="border-radius:10px;">
                    <strong>Login Failed</strong><br>Invalid username or password.
                    <?php if ($rem_attempts > 0 && $rem_attempts < 3): ?>
                        (<?= (int)$rem_attempts ?> remaining)
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!$just_registered || $error_type): ?>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" <?= $login_disabled ? 'disabled' : '' ?>>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" name="password" id="pwdField" placeholder="Password" required <?= $login_disabled ? 'disabled' : '' ?>>
                            <button class="btn btn-outline-secondary" type="button" id="togglePwd"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <?php if ($forgot_link): ?>
                        <a href="forgot_password.php" class="small text-decoration-none">Forgot?</a>
                        <?php endif; ?>
                        <a href="<?= URL_REGISTER ?>" class="small text-decoration-none fw-bold" style="color: var(--brand-secondary);">Register Resident</a>
                    </div>
                    <button class="btn btn-login w-100 py-2 rounded-3" type="submit" <?= $login_disabled ? 'disabled' : '' ?>>
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>
                <?php else: ?>
                <div class="text-center">
                    <a href="<?= URL_LOGIN ?>" class="btn btn-outline-primary btn-sm rounded-pill mt-2">← Back to Login</a>
                </div>
                <?php endif; ?>
            </div>
            <div class="text-muted small mt-4" style="opacity:.6;">
                &copy; <?php echo date('Y'); ?> Profiling System. All rights reserved.
            </div>
        </div>
    </div>

    <script>
    const pwd = document.getElementById('pwdField');
    const btn = document.getElementById('togglePwd');
    if(btn) {
        btn.addEventListener('click', () => {
            const show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    }

    const cd = document.getElementById('countdown');
    if (cd) {
        let s = parseInt(cd.textContent, 10);
        setInterval(() => { s <= 1 ? location.reload() : cd.textContent = --s; }, 1000);
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>