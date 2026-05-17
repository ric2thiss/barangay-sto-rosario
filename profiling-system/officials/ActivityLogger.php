<?php
/**
 * ActivityLogger.php
 * ─────────────────────────────────────────────────────────────────
 * Include this in login.php, logout.php, and session-check files.
 * Usage:
 *   require_once 'ActivityLogger.php';
 *   $log_id = ActivityLogger::logLogin($conn, $user_id, $user_type, $username, $full_name);
 *   $_SESSION['log_id'] = $log_id;   // store so logout can update the same row
 *
 *   ActivityLogger::logLogout($conn, $_SESSION['log_id']);  // on logout
 *   ActivityLogger::logLogout($conn, $_SESSION['log_id'], 'expired'); // on session timeout
 */
class ActivityLogger
{
    // ── Parse device info from User-Agent ────────────────────────
    public static function parseDevice(string $ua): array
    {
        $device  = 'Unknown';
        $os      = 'Unknown';
        $browser = 'Unknown';

        // Device type
        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            $device = 'Tablet';
        } elseif (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $ua)) {
            $device = 'Mobile';
        } elseif (!empty($ua)) {
            $device = 'Desktop';
        }

        // OS
        $os_map = [
            'Windows NT 10'  => 'Windows 10/11',
            'Windows NT 6.3' => 'Windows 8.1',
            'Windows NT 6.1' => 'Windows 7',
            'Mac OS X'       => 'macOS',
            'Android'        => 'Android',
            'iPhone'         => 'iOS',
            'iPad'           => 'iPadOS',
            'Linux'          => 'Linux',
            'Ubuntu'         => 'Ubuntu',
        ];
        foreach ($os_map as $needle => $label) {
            if (stripos($ua, $needle) !== false) { $os = $label; break; }
        }
        // Android version
        if (preg_match('/Android ([0-9.]+)/i', $ua, $m)) $os = 'Android '.$m[1];
        // iOS version
        if (preg_match('/OS ([0-9_]+) like Mac OS/i', $ua, $m))
            $os = 'iOS '.str_replace('_','.',$m[1]);
        // macOS version
        if (preg_match('/Mac OS X ([0-9_]+)/i', $ua, $m))
            $os = 'macOS '.str_replace('_','.',$m[1]);

        // Browser (order matters — Edge before Chrome, Chrome before Safari)
        $browser_map = [
            'Edg/'     => 'Microsoft Edge',
            'OPR/'     => 'Opera',
            'Opera'    => 'Opera',
            'YaBrowser'=> 'Yandex Browser',
            'Firefox/' => 'Firefox',
            'Chrome/'  => 'Chrome',
            'Safari/'  => 'Safari',
            'MSIE'     => 'Internet Explorer',
            'Trident/' => 'Internet Explorer',
        ];
        foreach ($browser_map as $needle => $label) {
            if (strpos($ua, $needle) !== false) { $browser = $label; break; }
        }

        // Browser version
        $ver_patterns = [
            'Microsoft Edge' => '/Edg\/([0-9.]+)/',
            'Firefox'        => '/Firefox\/([0-9.]+)/',
            'Chrome'         => '/Chrome\/([0-9.]+)/',
            'Safari'         => '/Version\/([0-9.]+)/',
            'Opera'          => '/OPR\/([0-9.]+)/',
        ];
        if (isset($ver_patterns[$browser]) && preg_match($ver_patterns[$browser], $ua, $m)) {
            $browser .= ' '.explode('.', $m[1])[0]; // major version only
        }

        return compact('device', 'os', 'browser');
    }

    // ── Get real IP (respects proxies) ───────────────────────────
    public static function getIP(): string
    {
        foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                // X_FORWARDED_FOR can be comma-separated list
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }

    // ── Geo-lookup via ip-api.com (free, no key needed) ──────────
    public static function getGeo(string $ip): array
    {
        $defaults = ['country'=>null,'city'=>null,'region'=>null,'lat'=>null,'lon'=>null];

        // Skip private/local IPs
        if (in_array($ip, ['127.0.0.1','::1','0.0.0.0'])
            || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $defaults + ['country'=>'Local','city'=>'Local','region'=>'Local'];
        }

        $url  = "http://ip-api.com/json/{$ip}?fields=country,regionName,city,lat,lon,status";
        $ctx  = stream_context_create(['http'=>['timeout'=>3]]);
        $json = @file_get_contents($url, false, $ctx);
        if (!$json) return $defaults;

        $data = json_decode($json, true);
        if (!$data || ($data['status'] ?? '') !== 'success') return $defaults;

        return [
            'country' => $data['country']    ?? null,
            'city'    => $data['city']       ?? null,
            'region'  => $data['regionName'] ?? null,
            'lat'     => $data['lat']        ?? null,
            'lon'     => $data['lon']        ?? null,
        ];
    }

    // ── Log a login event → returns inserted log_id ───────────────
    public static function logLogin(
        mysqli $conn,
        int    $user_id,
        string $user_type,
        string $username,
        string $full_name = ''
    ): int {
        $ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip      = self::getIP();
        $device  = self::parseDevice($ua);
        $geo     = self::getGeo($ip);
        $now     = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("
            INSERT INTO activity_logs
                (user_id, user_type, username, full_name, action,
                 login_at, ip_address,
                 country, city, region, latitude, longitude,
                 user_agent, device_type, os, browser, status)
            VALUES (?,?,?,?,'login',?,?,?,?,?,?,?,?,?,?,?,'online')
        ");
        $stmt->bind_param(
            'isssssssssddssss',
            $user_id, $user_type, $username, $full_name,
            $now, $ip,
            $geo['country'], $geo['city'], $geo['region'],
            $geo['lat'], $geo['lon'],
            $ua, $device['device'], $device['os'], $device['browser']
        );
        $stmt->execute();
        $log_id = (int)$conn->insert_id;
        $stmt->close();

        return $log_id;
    }

    // ── Update row on logout / session expiry ─────────────────────
    public static function logLogout(mysqli $conn, int $log_id, string $status = 'offline'): void
    {
        if ($log_id <= 0) return;
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("
            UPDATE activity_logs
            SET logout_at    = ?,
                duration_sec = TIMESTAMPDIFF(SECOND, login_at, ?),
                status       = ?,
                action       = IF(? = 'expired', 'session_expired', 'logout')
            WHERE id = ? AND status = 'online'
        ");
        $stmt->bind_param('ssssi', $now, $now, $status, $status, $log_id);
        $stmt->execute();
        $stmt->close();
    }

    // ── Mark stale sessions (>8h) as expired — call from cron or dashboard ──
    public static function markExpired(mysqli $conn, int $max_minutes = 480): void
    {
        $conn->query("
            UPDATE activity_logs
            SET status       = 'expired',
                action       = 'session_expired',
                logout_at    = NOW(),
                duration_sec = TIMESTAMPDIFF(SECOND, login_at, NOW())
            WHERE status = 'online'
              AND login_at < DATE_SUB(NOW(), INTERVAL {$max_minutes} MINUTE)
        ");
    }
}