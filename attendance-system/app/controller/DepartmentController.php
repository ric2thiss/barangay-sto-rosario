<?php

class DepartmentController
{
    protected $departmentRepository;
    private $departmentsEnabled = true;

    public function __construct() {
        $db = (new Database())->connect();
        $this->departmentRepository = new DepartmentRepository($db);
    }

    /**
     * Detect missing table errors (e.g., user removed departments table).
     */
    private function isMissingDepartmentsTable(PDOException $e): bool
    {
        $msg = strtolower($e->getMessage());
        return ($e->getCode() === '42S02') || (str_contains($msg, 'departments') && str_contains($msg, "doesn't exist"));
    }

    private function disableDepartmentsFeature(): void
    {
        $this->departmentsEnabled = false;
    }

    /**
     * Get all departments
     * @return array
     */
    public function getAll()
    {
        if (!$this->departmentsEnabled) {
            return [];
        }

        try {
            return $this->departmentRepository->findAll();
        } catch (PDOException $e) {
            if ($this->isMissingDepartmentsTable($e)) {
                $this->disableDepartmentsFeature();
                return [];
            }
            throw $e;
        }
    }

    /**
     * Get department by ID
     * @param int $id
     * @return object|array|null
     */
    public function getById($id)
    {
        if (!$this->departmentsEnabled) {
            return null;
        }

        try {
            return $this->departmentRepository->findById($id);
        } catch (PDOException $e) {
            if ($this->isMissingDepartmentsTable($e)) {
                $this->disableDepartmentsFeature();
                return null;
            }
            throw $e;
        }
    }

    /**
     * Create a new department
     * @param array $data
     * @return array
     */
    public function store($data)
    {
        if (!$this->departmentsEnabled) {
            return [
                "success" => false,
                "message" => "Departments feature is disabled (departments table not present)."
            ];
        }

        // Validate required fields
        if (empty($data['department_name'])) {
            return [
                "success" => false,
                "message" => "Department name is required."
            ];
        }

        try {
            // Check for duplicate department_name
            $existing = $this->departmentRepository->findBy('department_name', trim($data['department_name']));
            if ($existing) {
                return [
                    "success" => false,
                    "message" => "Department already exists."
                ];
            }

            // Create the record
            $insertData = ['department_name' => trim($data['department_name'])];
            $id = $this->departmentRepository->create($insertData);

            if ($id) {
                return [
                    "success" => true,
                    "message" => "Department created successfully.",
                    "id" => $id
                ];
            }

            return [
                "success" => false,
                "message" => "Failed to create department."
            ];
        } catch (PDOException $e) {
            if ($this->isMissingDepartmentsTable($e)) {
                $this->disableDepartmentsFeature();
                return [
                    "success" => false,
                    "message" => "Departments feature is disabled (departments table not present)."
                ];
            }
            throw $e;
        }
    }

    /**
     * Update a department
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update($id, $data)
    {
        if (!$this->departmentsEnabled) {
            return [
                "success" => false,
                "message" => "Departments feature is disabled (departments table not present)."
            ];
        }

        // Validate required fields
        if (empty($data['department_name'])) {
            return [
                "success" => false,
                "message" => "Department name is required."
            ];
        }

        try {
            // Check if record exists
            if (!$this->departmentRepository->exists($id)) {
                return [
                    "success" => false,
                    "message" => "Department not found."
                ];
            }

            // Check for duplicate department_name (excluding current record)
            $existing = $this->departmentRepository->findBy('department_name', trim($data['department_name']));
            if ($existing && (is_object($existing) ? $existing->department_id : $existing['department_id']) != $id) {
                return [
                    "success" => false,
                    "message" => "Department with this name already exists."
                ];
            }

            // Update the record
            $updateData = ['department_name' => trim($data['department_name'])];
            $success = $this->departmentRepository->update($id, $updateData);

            if ($success) {
                return [
                    "success" => true,
                    "message" => "Department updated successfully."
                ];
            }

            return [
                "success" => false,
                "message" => "Failed to update department."
            ];
        } catch (PDOException $e) {
            if ($this->isMissingDepartmentsTable($e)) {
                $this->disableDepartmentsFeature();
                return [
                    "success" => false,
                    "message" => "Departments feature is disabled (departments table not present)."
                ];
            }
            throw $e;
        }
    }

    /**
     * Delete a department
     * @param int $id
     * @return array
     */
    public function delete($id)
    {
        if (!$this->departmentsEnabled) {
            return [
                "success" => false,
                "message" => "Departments feature is disabled (departments table not present)."
            ];
        }

        try {
            // Check if record exists
            if (!$this->departmentRepository->exists($id)) {
                return [
                    "success" => false,
                    "message" => "Department not found."
                ];
            }

            // Delete the record
            $success = $this->departmentRepository->delete($id);

            if ($success) {
                return [
                    "success" => true,
                    "message" => "Department deleted successfully."
                ];
            }

            return [
                "success" => false,
                "message" => "Failed to delete department."
            ];
        } catch (PDOException $e) {
            if ($this->isMissingDepartmentsTable($e)) {
                $this->disableDepartmentsFeature();
                return [
                    "success" => false,
                    "message" => "Departments feature is disabled (departments table not present)."
                ];
            }
            throw $e;
        }
    }

    /**
     * Legacy method for backward compatibility
     * @return array
     */
    public function getDepartmentLists()
    {
        return $this->getAll();
    }
}