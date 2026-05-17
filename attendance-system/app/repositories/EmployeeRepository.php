<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../utils/ProfilingPdo.php';

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
     * Helper: merge resident data from ProfilingPdo into employee rows.
     * Fetches resident info in a single batch query, then maps it onto each row.
     *
     * @param array $employees  Array of employee objects/arrays (must have 'resident_id')
     * @return array            Array of merged objects
     */
    private function mergeResidentData(array $employees): array {
        // Collect resident IDs
        $residentIds = [];
        foreach ($employees as $emp) {
            $rid = is_object($emp) ? ($emp->resident_id ?? null) : ($emp['resident_id'] ?? null);
            if ($rid) $residentIds[] = (int) $rid;
        }
        $residentIds = array_unique($residentIds);

        // Batch fetch from profiling DB
        $residents = !empty($residentIds) ? ProfilingPdo::fetchResidentsByIds($residentIds) : [];

        // Merge
        $result = [];
        foreach ($employees as $emp) {
            $e = is_object($emp) ? json_decode(json_encode($emp), true) : $emp;
            $rid = $e['resident_id'] ?? null;
            $res = $residents[$rid] ?? [];

            $result[] = (object) [
                'resident_id'     => $rid,
                'first_name'      => $res['first_name'] ?? null,
                'middle_name'     => $res['middle_name'] ?? null,
                'last_name'       => $res['last_name'] ?? null,
                'suffix'          => null,
                'gender'          => $res['gender'] ?? null,
                'employee_id'     => $e['employee_id'] ?? null,
                'position_name'   => $e['position_name'] ?? null,
                'department_name' => null,
                'activity_name'   => $e['activity_name'] ?? null,
            ];
        }
        return $result;
    }

    /**
     * Get all employees with related data
     * 
     * @return array
     */
    public function getAllWithRelations(): array {
        try {
            $employees = Employee::query()
                ->select(
                    "employees.employee_id",
                    "employees.resident_id",
                    "position.position_name",
                    "activity_types.activity_name"
                )
                ->leftJoin("employee_activity", "employees.employee_id", "=", "employee_activity.employee_id")
                ->leftJoin("activity_types", "employee_activity.activity_types_id", "=", "activity_types.activity_types_id")
                ->leftJoin("position", "employees.position_id", "=", "position.position_id")
                ->get();

            return $this->mergeResidentData($employees);
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
        $emptyResult = [
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

        try {
            // If searching by name, find matching resident IDs from profiling DB first
            $nameMatchIds = [];
            if (!empty($searchQuery)) {
                $nameMatchIds = ProfilingPdo::searchResidents($searchQuery);
            }

            $baseQuery = Employee::query()
                ->select(
                    "employees.employee_id",
                    "employees.resident_id",
                    "position.position_name",
                    "activity_types.activity_name"
                )
                ->leftJoin("employee_activity", "employees.employee_id", "=", "employee_activity.employee_id")
                ->leftJoin("activity_types", "employee_activity.activity_types_id", "=", "activity_types.activity_types_id")
                ->leftJoin("position", "employees.position_id", "=", "position.position_id");

            $countQuery = Employee::query()
                ->select("COUNT(DISTINCT employees.employee_id) as total")
                ->leftJoin("employee_activity", "employees.employee_id", "=", "employee_activity.employee_id")
                ->leftJoin("activity_types", "employee_activity.activity_types_id", "=", "activity_types.activity_types_id")
                ->leftJoin("position", "employees.position_id", "=", "position.position_id");

            if (!empty($searchQuery)) {
                if (!empty($nameMatchIds)) {
                    $idPlaceholders = implode(',', array_fill(0, count($nameMatchIds), '?'));
                    $searchCondition = "(employees.employee_id LIKE ? OR position.position_name LIKE ? OR employees.resident_id IN ({$idPlaceholders}))";
                    $searchParams = array_merge(["%{$searchQuery}%", "%{$searchQuery}%"], $nameMatchIds);
                } else {
                    $searchCondition = "(employees.employee_id LIKE ? OR position.position_name LIKE ?)";
                    $searchParams = ["%{$searchQuery}%", "%{$searchQuery}%"];
                }
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

            $totalCountQuery = $countQuery->first();
            $totalRecords = is_object($totalCountQuery) ? (int) $totalCountQuery->total : (int) ($totalCountQuery['total'] ?? 0);
            $totalPages = $totalRecords > 0 ? ceil($totalRecords / $perPage) : 1;

            $employees = $baseQuery
                ->limit($perPage)
                ->offset($offset)
                ->get();

            // Merge resident data from profiling DB
            $merged = $this->mergeResidentData($employees);

            return [
                "employees" => $merged,
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
        } catch (PDOException $e) {
            if ($this->isMissingEmployeesTable($e)) {
                return $emptyResult;
            }
            throw $e;
        }
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

        if (!$employee) {
            return null;
        }

        $e = is_object($employee) ? json_decode(json_encode($employee), true) : $employee;

        // Fetch resident data from profiling DB
        $rid = $e['resident_id'] ?? null;
        if ($rid) {
            $residents = ProfilingPdo::fetchResidentsByIds([(int) $rid]);
            $res = $residents[$rid] ?? [];
            $e['first_name'] = $res['first_name'] ?? null;
            $e['middle_name'] = $res['middle_name'] ?? null;
            $e['last_name'] = $res['last_name'] ?? null;
            $e['suffix'] = null;
            $e['department_name'] = null;
        }

        return $e;
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
