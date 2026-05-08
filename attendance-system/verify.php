<?php

require_once "./bootstrap.php";

$controller = new VerificationLogController();

// 🔹 Handle POST request (from C# app)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (!$data || !isset($data['employee_id'], $data['status'], $data['token'])) {
        http_response_code(400);
        echo "Invalid request";
        exit;
    }

    // Validate secret key
    if ($data['token'] !== "MY_SECRET_KEY") {
        http_response_code(403);
        echo "Unauthorized";
        exit;
    }

    try {
        // Store verification log and generate token
        $confirmToken = $controller->store($data);

        // Return token to C# app
        echo $confirmToken;
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error: " . $e->getMessage();
    }

    exit;
}

// 🔹 Browser GET confirmation
if (isset($_GET['confirm'])) {
    $token = $_GET['confirm'];
    echo $controller->confirm($token);
    exit;
}

// Invalid request method
http_response_code(405);
echo "Invalid request method.";
