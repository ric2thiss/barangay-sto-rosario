<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/helpers.php';
requireAuth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$serviceController = new NonResidentServiceController();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $result = $serviceController->getById($id);
            echo json_encode([
                "success" => $result !== null,
                "data" => $result
            ]);
        } else {
            $result = $serviceController->getAll();
            echo json_encode([
                "success" => true,
                "data" => $result
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        $result = $serviceController->store($data);
        echo json_encode($result);
        break;

    case 'PUT':
    case 'PATCH':
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
        $result = $serviceController->update($id, $data);
        echo json_encode($result);
        break;

    case 'DELETE':
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
        $result = $serviceController->delete($id);
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
