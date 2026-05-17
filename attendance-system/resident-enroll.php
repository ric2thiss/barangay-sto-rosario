<?php

require_once "./bootstrap.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    $controller = new FingerprintsController();
    echo $controller->enroll($data);
    exit;
} else {
    http_response_code(400);
    echo json_encode(["message" => "Bad request. Only POST method is allowed"]);
    exit;
}
