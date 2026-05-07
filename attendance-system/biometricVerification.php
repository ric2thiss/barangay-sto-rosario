<?php 

require_once "./bootstrap.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid request. JSON body is missing or malformed."
        ]);
        exit;
    }

    (new BiometricController())->store($data);
} else {
    header('Content-Type: application/json');
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
}
