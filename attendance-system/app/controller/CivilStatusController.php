<?php

class CivilStatusController
{
    protected $civilStatusRepository;

    public function __construct() {
        $db = (new Database())->connect();
        $this->civilStatusRepository = new CivilStatusRepository($db);
    }

    /**
     * Get all civil statuses
     * @return array
     */
    public function getAll()
    {
        return $this->civilStatusRepository->findAll();
    }

    /**
     * Get civil status by ID
     * @param int $id
     * @return object|array|null
     */
    public function getById($id)
    {
        return $this->civilStatusRepository->findById($id);
    }

    /**
     * Create a new civil status
     * @param array $data
     * @return array
     */
    public function store($data)
    {
        // Validate required fields
        if (empty($data['status_name'])) {
            return [
                "success" => false,
                "message" => "Status name is required."
            ];
        }

        // Check for duplicate status_name
        $existing = $this->civilStatusRepository->findBy('status_name', trim($data['status_name']));
        if ($existing) {
            return [
                "success" => false,
                "message" => "Civil status already exists."
            ];
        }

        // Create the record
        $insertData = ['status_name' => trim($data['status_name'])];
        $id = $this->civilStatusRepository->create($insertData);

        if ($id) {
            return [
                "success" => true,
                "message" => "Civil status created successfully.",
                "id" => $id
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to create civil status."
        ];
    }

    /**
     * Update a civil status
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update($id, $data)
    {
        // Validate required fields
        if (empty($data['status_name'])) {
            return [
                "success" => false,
                "message" => "Status name is required."
            ];
        }

        // Check if record exists
        if (!$this->civilStatusRepository->exists($id)) {
            return [
                "success" => false,
                "message" => "Civil status not found."
            ];
        }

        // Check for duplicate status_name (excluding current record)
        $existing = $this->civilStatusRepository->findBy('status_name', trim($data['status_name']));
        if ($existing && (is_object($existing) ? $existing->civil_status_id : $existing['civil_status_id']) != $id) {
            return [
                "success" => false,
                "message" => "Civil status with this name already exists."
            ];
        }

        // Update the record
        $updateData = ['status_name' => trim($data['status_name'])];
        $success = $this->civilStatusRepository->update($id, $updateData);

        if ($success) {
            return [
                "success" => true,
                "message" => "Civil status updated successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to update civil status."
        ];
    }

    /**
     * Delete a civil status
     * @param int $id
     * @return array
     */
    public function delete($id)
    {
        // Check if record exists
        if (!$this->civilStatusRepository->exists($id)) {
            return [
                "success" => false,
                "message" => "Civil status not found."
            ];
        }

        // Delete the record
        $success = $this->civilStatusRepository->delete($id);

        if ($success) {
            return [
                "success" => true,
                "message" => "Civil status deleted successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to delete civil status."
        ];
    }
}
