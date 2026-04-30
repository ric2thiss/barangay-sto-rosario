<?php
/**
 * download.php — Serves a file from exports/ with proper headers
 * Usage: download.php?file=filename.xlsx
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$file = isset($_GET['file']) ? basename($_GET['file']) : '';
$path = __DIR__ . '/exports/' . $file;

if (empty($file) || !file_exists($path) || pathinfo($file, PATHINFO_EXTENSION) !== 'xlsx') {
    http_response_code(404);
    echo 'File not found.';
    exit();
}

// Clean all output buffers
while (ob_get_level()) ob_end_clean();

// Force download with correct headers
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($path));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($path);
exit();
