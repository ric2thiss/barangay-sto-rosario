<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/conn.php';
require_once __DIR__ . '/../config/removebg.php';

function out(bool $success, string $message = '', array $extra = []): void {
  echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
  exit;
}

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  out(false, 'Unauthorized.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  out(false, 'Method not allowed.');
}

if (!isset($_FILES['image'])) {
  http_response_code(400);
  out(false, 'No image uploaded. Use form-data field "image".');
}

$f = $_FILES['image'];
if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
  http_response_code(400);
  out(false, 'Upload failed.');
}

$tmp  = (string)($f['tmp_name'] ?? '');
$size = (int)($f['size'] ?? 0);

if ($tmp === '' || !is_uploaded_file($tmp)) {
  http_response_code(400);
  out(false, 'Invalid upload payload.');
}

if ($size <= 0 || $size > 5_000_000) { // keep it small for signatures
  http_response_code(400);
  out(false, 'Image too large (max 5MB).');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = ($finfo ? (string)finfo_file($finfo, $tmp) : '') ?: '';
if ($finfo) finfo_close($finfo);
$allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
  http_response_code(400);
  out(false, 'Unsupported file type. Use PNG/JPG/WebP.');
}

try {
  $cfile = new CURLFile($tmp, $mime, (string)($f['name'] ?? 'image'));

  $post = [
    'image_file' => $cfile,
    'size'       => 'auto',
    'format'     => 'png',   // png supports transparency
    // 'crop'    => 'true',  // optional
  ];

  $ch = curl_init('https://api.remove.bg/v1.0/removebg');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'X-Api-Key: ' . REMOVEBG_API_KEY,
    ],
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_TIMEOUT => 120,
  ]);

  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($resp === false) {
    throw new RuntimeException('Curl error: ' . $err);
  }

  // remove.bg returns image bytes on success, JSON error on failure
  if ($code >= 200 && $code < 300) {
    out(true, 'OK', [
      'mime_type' => 'image/png',
      'filename'  => 'signature_nobg.png',
      'data_base64' => base64_encode($resp),
    ]);
  }

  // try to parse remove.bg error response
  $maybe = json_decode($resp, true);
  $msg = 'Background removal failed.';
  if (is_array($maybe)) {
    $msg = (string)($maybe['errors'][0]['title'] ?? $maybe['errors'][0]['code'] ?? $msg);
  }

  http_response_code(500);
  out(false, $msg);

} catch (Throwable $e) {
  http_response_code(500);
  error_log((string)$e);
  out(false, 'Server error.');
}