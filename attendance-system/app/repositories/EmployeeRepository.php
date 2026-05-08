<?php

require_once __DIR__ . '/BaseRepository.php';

class EmployeeRepository extends BaseRepository {
    protected function getModelClass(): string {
        return Employee::class;
    }

    private function isMissingEmployeesTable(PDOException $e): bool
    {
        $msg = strtolower($e->getMessage());
        return ($e->getCode() === '42S02') || (str_contains($msg, 'employees') && str_contains($msg, "doesn't exist"));
    }

    /**
     * Get all employees with related data
     * 
     * @return array
     */
    public function getAllWithRelations(): array {
        try {
            return Employee::query()
                ->select(
                    "residents.id as resident_id",
                    "residents.first_name",
                    "residents.middle_name",
                    "residents.surname as last_name",
                    "NULL as suffix",
                    "residents.sex as gender",
                    "employees.employee_id",
                    "position.position_name",
                    "NULL as department_name",
                    "activity_types.activity_name"
                )
                ->join("`" . PROFILING_DB_NAME . "`.`residents` as residents", "employees.resident_id", "=", "residents.id")
                ->leftJoin("employee_activity", "employees.employee_id", "=", "employee_activity.employee_id")
                ->leftJoin("activity_types", "employee_activity.activity_types_id", "=", "activity_types.activity_types_id")
                ->leftJoin("position", "employees.position_id", "=", "position.position_id")
                ->get();
        } catch (PDOException $e) {
            if ($this->isMissingEmployeesTable($e)) {
                return [];
            }
            throw $e;
        }
    }

    /**
     * Get paginated employees with search and filters
     * 
     * @param int $page
     * @param int $perPage
     * @param string $searchQuery
     * @param array $filters Optional filters: department_id, position_id
     * @return array
     */
    public function getPaginated(int $page, int $perPage, string $searchQuery = '', array $filters = []): array {
        $offset = ($page - 1) * $perPage;

        try {
            $baseQuery = Employee::query()
                ->select(
                    "residents.id as resident_id",
                    "residents.first_name",
                    "residents.middle_name",
                    "residents.surname as last_name",
                    "NULL as suffix",
                    "residents.sex as gender",
                    "employees.employee_id",
                    "position.position_name",
                    "NULL as department_name",
                    "activity_types.activity_name"
                )
                ->join("`" . PROFILING_DB_NAME . "`.`residents` as residents", "employees.resident_id", "=", "residents.id")
                ->leftJoin("employee_activity", "employees.employee_id", "=", "employee_activity.employee_id")
                ->leftJoin("activity_types", "employee_activity.activity_types_id", "=", "activity_types.activity_types_id")
                ->leftJoin("position", "employees.position_id", "=", "position.position_id");

            $countQuery = Employee::query()
                ->select("COUNT(DISTINCT employees.employee_id) as total")
                ->join("`" . PROFILING_DB_NAME . "`.`residents` as residents", "employees.resident_id", "=", "residents.id")
                ->leftJoin("employee_activity", "employees.employee_id", "=", "employee_activity.employee_id")
                ->leftJoin("activity_types", "employee_activity.activity_types_id", "=", "activity_types.activity_types_id")
                ->leftJoin("position", "employees.position_id", "=", "position.position_id");
        } catch (PDOException $e) {
            if ($this->isMissingEmployeesTable($e)) {
                return [
                    "employees" => [],
                    "pagination" => [
                        "currentPage" => $page,
                        "totalPages" => 1,
                        "totalRecords" => 0,
                        "perPage" => $perPage,
                        "startRecord" => 0,
                        "endRecord" => 0,
                    ],
                    "searchQuery" => $searchQuery
                ];
            }
            throw $e;
        }

        if (!empty($searchQuery)) {
            $searchCondition = "(CONCAT(residents.first_name, ' ', residents.surname) LIKE ? OR employees.employee_id LIKE ? OR position.position_name LIKE ?)";
            $searchParams = ["%{$searchQuery}%", "%{$searchQuery}%", "%{$searchQuery}%"];
            
            $baseQuery->whereRaw($searchCondition, $searchParams);
            $countQuery->whereRaw($searchCondition, $searchParams);
        }

        // Apply filters
        if (!empty($filters['department_id'])) {
            $baseQuery->where('employees.department_id', $filters['department_id']);
            $countQuery->where('employees.department_id', $filters['department_id']);
        }

        if (!empty($filters['position_id'])) {
            $baseQuery->where('employees.position_id', $filters['position_id']);
            $countQuery->where('employees.position_id', $filters['position_id']);
        }

        try {
            $totalCountQuery = $countQuery->first();
            $totalRecords = is_object($totalCountQuery) ? (int) $totalCountQuery->total : (int) ($totalCountQuery['total'] ?? 0);
            $totalPages = $totalRecords > 0 ? ceil($totalRecords / $perPage) : 1;

            $employees = $baseQuery
                ->limit($perPage)
                ->offset($offset)
                ->get();
        } catch (PDOException $e) {
            if ($this->isMissingEmployeesTable($e)) {
                return [
                    "employees" => [],
                    "pagination" => [
                        "currentPage" => $page,
                        "totalPages" => 1,
                        "totalRecords" => 0,
                        "perPage" => $perPage,
                        "startRecord" => 0,
                        "endRecord" => 0,
                    ],
                    "searchQuery" => $searchQuery
                ];
            }
            throw $e;
        }

        return [
            "employees" => $employees,
            "pagination" => [
                "currentPage" => $page,
                "totalPages" => $totalPages,
                "totalRecords" => $totalRecords,
                "perPage" => $perPage,
                "startRecord" => $offset + 1,
                "endRecord" => min($offset + $perPage, $totalRecords),
            ],
            "searchQuery" => $searchQuery
        ];
    }

    /**
     * Get employee with position
     * 
     * @param string $employeeId
     * @return object|array|null
     */
    public function getWithPosition(string $employeeId) {
        try {
            return Employee::query()
                ->select(
                    "employees.employee_id",
                    "employees.resident_id",
                    "employees.position_id",
                    "employees.hired_date",
                    "position.position_name"
                )
                ->leftJoin("position", "employees.position_id", "=", "position.position_id")
                ->where('employees.employee_id', $employeeId)
                ->first();
        } catch (PDOException $e) {
            if ($this->isMissingEmployeesTable($e)) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Get last employee ID
     * 
     * @return string|null
     */
    public function getLastEmployeeId(): ?string {
        try {
            $lastEmployee = Employee::query()
                ->select("employee_id")
                ->orderBy("created_at", "DESC")
                ->first();

            return is_object($lastEmployee) 
                ? ($lastEmployee->employee_id ?? null)
                : ($lastEmployee['employee_id'] ?? null);
        } catch (PDOException $e) {
            if ($this->isMissingEmployeesTable($e)) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Get employee count
     * 
     * @return int
     */
    public function getEmployeeCount(): int {
        try {
            $result = Employee::query()
                ->select("COUNT(*) as count")
                ->first();
        
            return is_object($result) ? (int) ($result->count ?? 0) : (int) ($result['count'] ?? 0);
        } catch (PDOException $e) {
            if ($this->isMissingEmployeesTable($e)) {
                return 0;
            }
            throw $e;
        }
    }

    /**
     * Get employee by ID with resident information
     * 
     * @param string $employeeId
     * @return array|null
     */
    public function getEmployeeById(string $employeeId): ?array {
        try {
            $employee = Employee::query()
                ->select(
                    "employees.employee_id",
                    "employees.resident_id",
                    "employees.position_id",
                    "employees.department_id",
                    "employees.hired_date",
                    "residents.first_name",
                    "residents.middle_name",
                    "residents.surname as last_name",
                    "NULL as suffix",
                    "position.position_name",
                    "NULL as department_name"
                )
                ->join("`" . PROFILING_DB_NAME . "`.`residents` as residents", "employees.resident_id", "=", "residents.id")
                ->leftJoin("position", "employees.position_id", "=", "position.position_id")
                ->where('employees.employee_id', $employeeId)
                ->first();
        } catch (PDOException $e) {
            if ($this->isMissingEmployeesTable($e)) {
                return null;
            }
            throw $e;
        }

        if (!$employee) {
            return null;
        }

        // Convert to array if object
        if (is_object($employee)) {
            return json_decode(json_encode($employee), true);
        }

        return $employee;
    }

    /**
     * Find employee by resident ID
     *
     * @param int $residentId
     * @return object|array|null
     */
    public function findByResidentId(int $residentId) {
        try {
            return Employee::query()
                ->where('resident_id', $residentId)
                ->first();
        } catch (PDOException $e) {
            if ($this->isMissingEmployeesTable($e)) {
                return null;
            }
            throw $e;
        }
    }
}
