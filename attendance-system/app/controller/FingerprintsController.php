<?php

class FingerprintsController {
    protected $fingerprintsRepository;
    protected $employeeRepository;
    protected $residentFingerprintsRepository;

    public function __construct()
    {
        $db = (new Database())->connect();
        $this->fingerprintsRepository = new FingerprintsRepository($db);
        $this->employeeRepository = new EmployeeRepository($db);
        $this->residentFingerprintsRepository = new ResidentFingerprintsRepository($db);
    }

    public function index() 
    {
        return $this->fingerprintsRepository->getAllLimited();
    }
    
    public function enroll($data)
    {
        header('Content-Type: application/json'); 
        
        if (!isset($data["template"]) || empty($data["template"])) {
            http_response_code(422);
            echo json_encode([
                "success" => false,
                "error"   => "Template data is required"
            ]);
            return;
        }

        // If resident_id is provided (and employee_id is not), allow enrolling as resident.
        // If the resident is registered as an employee, we map resident_id -> employee_id and store into employee_fingerprints
        // so attendance identification continues to work.
        $hasEmployeeId = isset($data["employee_id"]) && !empty($data["employee_id"]);
        $hasResidentId = isset($data["resident_id"]) && !empty($data["resident_id"]);

        if (!$hasEmployeeId && $hasResidentId) {
            $residentId = (int) $data["resident_id"];
            $employee = $this->employeeRepository->findByResidentId($residentId);

            if ($employee) {
                // Resident is an employee; store as employee template (employee_fingerprints)
                $data["employee_id"] = is_object($employee) ? $employee->employee_id : ($employee['employee_id'] ?? null);
                $hasEmployeeId = isset($data["employee_id"]) && !empty($data["employee_id"]);
            } else {
                // Resident is NOT an employee; store in resident_fingerprints
                if ($this->residentFingerprintsRepository->existsByResidentId($residentId)) {
                    http_response_code(409);
                    echo json_encode(["message" => "Resident already enrolled"]);
                    return;
                }

                $this->residentFingerprintsRepository->create([
                    "resident_id" => $residentId,
                    "template" => $data["template"]
                ]);

                http_response_code(201);
                echo json_encode(["message" => "Resident fingerprint enrolled successfully"]);
                return;
            }
        }

        // Employee enrollment (default)
        if (!isset($data["employee_id"]) || empty($data["employee_id"])) {
            http_response_code(422);
            echo json_encode([
                "success" => false,
                "error"   => "Employee ID is required"
            ]);
            return;
        }

        if ($this->fingerprintsRepository->existsByEmployeeId($data["employee_id"])) {
            http_response_code(409);
            echo json_encode(["message"=>"Employee already enrolled"]);
            return;
        }

        $this->fingerprintsRepository->create([
            "employee_id" => $data["employee_id"],
            "template"    => $data["template"]
        ]);

        http_response_code(201);
        echo json_encode(["message"=>"Fingerprint enrolled successfully"]);
        return;
    }
}