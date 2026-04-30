<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../auth/helpers.php";

header("Content-Type: application/json");

requireAuth();

$method = $_SERVER["REQUEST_METHOD"];

/**
 * POST /api/accounts/official-access.php?id=bo-{id}&allow={0|1}
 * Sets barangay_official.status to Active (allow) or Inactive (revoke).
 */
if ($method === "POST" || $method === "PUT") {
    $id = $_GET["id"] ?? $_POST["id"] ?? null;
    $allowRaw = $_GET["allow"] ?? $_POST["allow"] ?? null;

    if ($id === null || $id === "" || $allowRaw === null || $allowRaw === "") {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Account id and allow flag are required",
        ]);
        exit;
    }

    $allow = filter_var($allowRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($allow === null) {
        $allow = (int) $allowRaw === 1;
    }

    try {
        $accountController = new AccountController();
        $result = $accountController->toggleOfficialPortalAccess($id, $allow);

        $status = $result["status"] ?? 200;
        unset($result["status"]);
        http_response_code($status);
        echo json_encode($result);
        exit;
    } catch (Exception $err) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error" => "Something went wrong - " . $err->getMessage(),
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
