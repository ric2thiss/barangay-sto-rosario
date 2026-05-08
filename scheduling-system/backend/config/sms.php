<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
bpss_load_env(__DIR__ . '/../../.env');

define('FMCSMS_API_URL', (string)(getenv('FMCSMS_API_URL') ?: ($_ENV['FMCSMS_API_URL'] ?? 'https://fortmed.org/web/FMCSMS/api/messages.php')));
define('FMCSMS_API_KEY', (string)(getenv('FMCSMS_API_KEY') ?: ($_ENV['FMCSMS_API_KEY'] ?? '')));
define('FMCSMS_SENDER_NAME', (string)(getenv('FMCSMS_SENDER_NAME') ?: ($_ENV['FMCSMS_SENDER_NAME'] ?? (getenv('MOCEAN_SENDER_ID') ?: ($_ENV['MOCEAN_SENDER_ID'] ?? 'Your System')))));
define('FMCSMS_FROM_NUMBER', (string)(getenv('FMCSMS_FROM_NUMBER') ?: ($_ENV['FMCSMS_FROM_NUMBER'] ?? '')));

if (FMCSMS_API_KEY === '') {
  throw new RuntimeException('Missing FMCSMS_API_KEY. Add it to your .env file.');
}

/**
 * Send a single SMS via the FMCSMS API.
 * Returns ['success' => bool, 'status' => string|null, 'error' => string|null]
 */
function mocean_send_sms(string $to, string $text): array {
  // FMCSMS appears to preserve CRLF more reliably than bare LF in delivered SMS.
  $text = str_replace(["\r\n", "\r"], "\n", $text);
  $text = str_replace("\n", "\r\n", $text);

  $payload = [
    'SenderName'  => FMCSMS_SENDER_NAME,
    'ToNumber'    => $to,
    'MessageBody' => $text,
  ];

  if (FMCSMS_FROM_NUMBER !== '') {
    $payload['FromNumber'] = FMCSMS_FROM_NUMBER;
  }

  $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($json === false) {
    return ['success' => false, 'status' => null, 'error' => 'Failed to encode FMCSMS payload'];
  }

  $ch = curl_init(FMCSMS_API_URL);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $json,
    CURLOPT_HTTPHEADER     => [
      'Content-Type: application/json',
      'Accept: application/json',
      'X-API-Key: ' . FMCSMS_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 15,
  ]);

  $raw   = curl_exec($ch);
  $errno = curl_errno($ch);
  $error = curl_error($ch);
  $http  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($errno || $raw === false) {
    return ['success' => false, 'status' => null, 'error' => 'cURL error ' . $errno . ($error !== '' ? ': ' . $error : '')];
  }

  $data   = json_decode((string)$raw, true);
  $status = null;

  if (is_array($data)) {
    foreach (['status', 'message', 'result', 'code'] as $key) {
      if (isset($data[$key]) && is_scalar($data[$key])) {
        $status = (string)$data[$key];
        break;
      }
    }
  }

  $success = $http >= 200 && $http < 300;
  if (is_array($data)) {
    if (isset($data['success'])) {
      $success = filter_var($data['success'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool)$data['success'];
    } elseif (isset($data['status']) && is_string($data['status'])) {
      $success = in_array(strtolower($data['status']), ['ok', 'success', 'sent', 'queued'], true);
    }
  }

  if ($success) {
    return ['success' => true, 'status' => $status, 'error' => null];
  }

  return ['success' => false, 'status' => $status, 'error' => is_string($raw) ? $raw : 'FMCSMS request failed'];
}
