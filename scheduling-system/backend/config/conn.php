<?php

require_once __DIR__ . '/env.php';
bpss_load_env(__DIR__ . '/../../.env');

date_default_timezone_set('Asia/Manila');

$DB_HOST = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$DB_NAME = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'pss_db');
$DB_USER = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$DB_PASS = (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? ''));

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    http_response_code(500);
    // Return JSON if the caller already set JSON content-type, otherwise plain text
    $isApi = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    if ($isApi || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '_api.php') !== false)) {
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['success' => false, 'message' => 'Service temporarily unavailable.']));
    }
    exit("Service temporarily unavailable.");
}
