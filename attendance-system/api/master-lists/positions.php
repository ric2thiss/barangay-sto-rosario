<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';
requireAuth();

header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    "success" => false,
    "message" => "Positions are managed in profiling-system. This endpoint is disabled because attendance-system.position was removed."
]);
exit;

$method = $_SERVER['REQUEST_METHOD'];
$positionController = new PositionController();

switch ($method) {
    case 'GET':
        // Get all or single record
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $result = $positionController->getById($id);
            echo json_encode([
                "success" => $result !== null,
                "data" => $result
            ]);
        } else {
            $result = $positionController->getAll();
            echo json_encode([
                "success" => true,
                "data" => $result
            ]);
        }
        break;

    case 'POST':
        // Create new record
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        $result = $positionController->store($data);
        echo json_encode($result);
        break;

    case 'PUT':
    case 'PATCH':
        // Update record
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            parse_str(file_get_contents('php://input'), $data);
        }
        if (!isset($data['id'])) {
            echo json_encode([
                "success" => false,
                "message" => "ID is required."
            ]);
            break;
        }
        $id = intval($data['id']);
        unset($data['id']);
        $result = $positionController->update($id, $data);
        echo json_encode($result);
        break;

    case 'DELETE':
        // Delete record
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data && isset($_GET['id'])) {
            $id = intval($_GET['id']);
        } elseif ($data && isset($data['id'])) {
            $id = intval($data['id']);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "ID is required."
            ]);
            break;
        }
        $result = $positionController->delete($id);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Method not allowed."
        ]);
        break;
}
