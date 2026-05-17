<?php

class BiometricController 
{
    protected $fingerprintsRepository;

    public function __construct() {
        $db = (new Database())->connect();
        $this->fingerprintsRepository = new FingerprintsRepository($db);
    }

    public function store(array $data)
    {
        header('Content-Type: application/json');

        if(!is_array($data) || empty($data)){
            http_response_code(422);
            echo json_encode(["success" => false, "message" => "Missing parameter or wrong data type is given"]);
            return;
        }

        $required = ["employee_id", "status", "timestamp"];
        foreach ($required as $field) {
            if(!isset($data[$field])) {
                http_response_code(422);
                echo json_encode(["success" => false, "message" => "Missing required field: $field"]);
                return;
            }
        }

        $employee = $this->fingerprintsRepository->findBy('employee_id', $data["employee_id"]);

        if(!$employee) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Employee not found"
            ]);
            return;
        }

        echo json_encode([
            "success"  => true,
            "message"  => "Fingerprint verified successfully",
            "employee" => $employee,
        ]);
    }
}
