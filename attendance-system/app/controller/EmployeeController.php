<?php

class EmployeeController {
    protected $employeeRepository;
    protected $residentRepository;

    private function isMissingEmployeesTable(PDOException $e): bool
    {
        $msg = strtolower($e->getMessage());
        return ($e->getCode() === '42S02') || (str_contains($msg, 'employees') && str_contains($msg, "doesn't exist"));
    }

    public function __construct() {
        $db = (new Database())->connect();
        $this->employeeRepository = new EmployeeRepository($db);
        $this->residentRepository = new ResidentRepository($db);
    }

    public function store($data)
    {
        // Validate input
        if (!$data) {
            return [
                "success" => false,
                "status"  => 400,
                "error"   => "Invalid or missing data"
            ];
        }

        if (
            empty($data["employee_id"]) ||
            empty($data["resident_id"]) ||
            empty($data["position_id"]) ||
            empty($data["hired_date"])
        ) {
            // department_id is optional
            return [
                "success" => false,
                "status"  => 400,
                "error"   => "Incomplete input data"
            ];
        }

        try {
            $residentId = (int) $data["resident_id"];
            if (!$this->residentRepository->existsById($residentId)) {
                return [
                    "success" => false,
                    "status"  => 404,
                    "error"   => "Resident not found in profiling system."
                ];
            }

            $createdEmployee = $this->employeeRepository->create($data);

            if ($createdEmployee) {
                return [
                    "success" => true,
                    "status"  => 201,
                    "message" => "Employee successfully created.",
                ];
            }

            return [
                "success" => false,
                "status"  => 500,
                "error"   => "Failed to create employee."
            ];

        } catch (PDOException $err) {
            if ($this->isMissingEmployeesTable($err)) {
                return [
                    "success" => false,
                    "status"  => 501,
                    "error"   => "Local employees table is not available. Employees are managed by profiling-system."
                ];
            }
            return [
                "success" => false,
                "status"  => 400,
                "error"   => $this->getUserFriendlyErrorMessage($err)
            ];
        } catch (Exception $err) {
            return [
                "success" => false,
                "status"  => 500,
                "error"   => "An unexpected error occurred. Please try again or contact support if the problem persists."
            ];
        }
    }

    public function getAllEmployees()
    {
        $employees = $this->employeeRepository->getAllWithRelations();
        $employeeCounts = $this->employeeRepository->getEmployeeCount();

        return ["employees"=>$employees, "employeeCounts" => [["count" => $employeeCounts]]];
    }

    /**
     * Get paginated employees with search and filters
     *
     * @param int $page Current page number
     * @param int $perPage Records per page
     * @param string $searchQuery Search term (optional)
     * @param array $filters Optional filters: department_id, position_id
     * @return array
     */
    public function getPaginatedEmployees($page = 1, $perPage = 10, $searchQuery = '', $filters = [])
    {
        return $this->employeeRepository->getPaginated($page, $perPage, $searchQuery, $filters);
    }

    /**
     * Get the last created employee ID
     * 
     * @return string|null
     */
    public function getLastEmployeeId()
    {
        return $this->employeeRepository->getLastEmployeeId();
    }

    /**
     * Get employee by ID
     * 
     * @param string $employeeId
     * @return array|null
     */
    public function getEmployeeById(string $employeeId): ?array
    {
        return $this->employeeRepository->getEmployeeById($employeeId);
    }

    /**
     * Update employee
     * 
     * @param string $employeeId
     * @param array $data
     * @return array
     */
    public function update(string $employeeId, array $data)
    {
        // Validate input
        if (empty($data["position_id"]) || empty($data["hired_date"])) {
            return [
                "success" => false,
                "status"  => 400,
                "error"   => "Position and hired date are required"
            ];
        }

        try {
            // Get existing employee
            $existingEmployee = $this->employeeRepository->getEmployeeById($employeeId);
            if (!$existingEmployee) {
                return [
                    "success" => false,
                    "status"  => 404,
                    "error"   => "Employee not found"
                ];
            }

            // Update employee data
            $updateData = [
                "position_id" => intval($data["position_id"]),
                "hired_date" => trim($data["hired_date"])
            ];

            if (!empty($data["department_id"])) {
                $updateData["department_id"] = intval($data["department_id"]);
            } else {
                $updateData["department_id"] = null;
            }

            $updated = Employee::query()
                ->where('employee_id', $employeeId)
                ->update($updateData);

            if ($updated) {
                return [
                    "success" => true,
                    "status"  => 200,
                    "message" => "Employee successfully updated."
                ];
            }

            return [
                "success" => false,
                "status"  => 500,
                "error"   => "Failed to update employee."
            ];

        } catch (PDOException $err) {
            if ($this->isMissingEmployeesTable($err)) {
                return [
                    "success" => false,
                    "status"  => 501,
                    "error"   => "Local employees table is not available. Employees are managed by profiling-system."
                ];
            }
            return [
                "success" => false,
                "status"  => 400,
                "error"   => $this->getUserFriendlyErrorMessage($err)
            ];
        } catch (Exception $err) {
            return [
                "success" => false,
                "status"  => 500,
                "error"   => "An unexpected error occurred. Please try again or contact support if the problem persists."
            ];
        }
    }

    /**
     * Delete employee (with archive)
     * 
     * Archives the employee to archive_employees table before deletion.
     * Also deletes related employee_activity records due to foreign key constraints.
     * 
     * @param string $employeeId
     * @return array
     */
    public function delete(string $employeeId)
    {
        // Employees are managed in profiling-system (barangay_official). attendance-system is read-only.
        return [
            "success" => false,
            "status"  => 410,
            "error"   => "Employee delete is disabled. Manage employees in the profiling-system database."
        ];
    }

    /**
     * Convert database errors to user-friendly messages for DELETE operations
     * 
     * @param PDOException $exception
     * @return string
     */
    private function getDeleteErrorMessage(PDOException $exception): string
    {
        $errorCode = $exception->getCode();
        $errorMessage = $exception->getMessage();
        
        // Handle foreign key constraint violations (1451) - Cannot delete parent row
        if (strpos($errorMessage, '1451') !== false || strpos($errorMessage, 'Cannot delete or update a parent row') !== false) {
            return "Cannot delete employee because they have related records in the system. The employee may have activity records, attendance records, or other related data. Please contact the administrator to remove related records first.";
        }
        
        // Handle foreign key constraint violations (1452) - Cannot add child row
        if (strpos($errorMessage, '1452') !== false) {
            return "Cannot delete employee due to a data integrity constraint. Please contact the administrator.";
        }
        
        // Handle other constraint violations (23000)
        if ($errorCode == 23000) {
            return "Cannot delete employee due to a database constraint. Please contact the administrator.";
        }
        
        // Generic database error
        return "Failed to delete employee. Error: " . $errorMessage;
    }

    /**
     * Convert database errors to user-friendly messages for INSERT/UPDATE operations
     * 
     * @param PDOException $exception
     * @return string
     */
    private function getUserFriendlyErrorMessage(PDOException $exception): string
    {
        $errorCode = $exception->getCode();
        $errorMessage = $exception->getMessage();
        
        // Handle duplicate entry errors (1062)
        if ($errorCode == 23000 || strpos($errorMessage, '1062') !== false) {
            // Extract the duplicate value if possible
            if (preg_match("/Duplicate entry '([^']+)' for key/", $errorMessage, $matches)) {
                $duplicateValue = $matches[1];
                
                // Check if it's an employee_id duplicate
                if (preg_match("/for key 'PRIMARY'/", $errorMessage) || 
                    preg_match("/for key 'employee_id'/", $errorMessage)) {
                    return "Employee ID '{$duplicateValue}' is already in use. Please use a different Employee ID.";
                }
                
                // Generic duplicate entry message
                return "The information you entered already exists in the system. Please check your input and try again.";
            }
            
            return "This record already exists. Please check if the employee has already been registered.";
        }
        
        // Handle foreign key constraint violations (1452)
        if (strpos($errorMessage, '1452') !== false) {
            if (strpos($errorMessage, 'resident_id') !== false) {
                return "The selected resident is invalid or does not exist. Please select a valid resident.";
            }
            if (strpos($errorMessage, 'position_id') !== false) {
                return "The selected position is invalid. Please select a valid position.";
            }
            if (strpos($errorMessage, 'department_id') !== false) {
                return "The selected department is invalid. Please select a valid department.";
            }
            return "The information you entered references data that does not exist. Please check your selections and try again.";
        }
        
        // Handle other constraint violations
        if ($errorCode == 23000) {
            return "The information you entered violates a data constraint. Please check your input and try again.";
        }
        
        // Generic database error
        return "Unable to save the employee information. Please check all fields and try again. If the problem persists, contact support.";
    }
}