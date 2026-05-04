<?php

require_once __DIR__ . "/../bootstrap.php";

$host = defined('DB_HOST') ? DB_HOST : "localhost";
$dbname = defined('DB_NAME') ? DB_NAME : "attendance-system";
$user = defined('DB_USER') ? DB_USER : "root";
$pass = defined('DB_PASS') ? DB_PASS : "";
$charset = defined('DB_CHARSET') ? DB_CHARSET : "utf8mb4";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["id"], $data["name"])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO logbook (client_id, name) VALUES (?, ?)");
    $stmt->execute([
        $data["id"],
        $data["name"]
    ]);

    echo json_encode(["status" => "success", "message" => "Attendance logged"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
