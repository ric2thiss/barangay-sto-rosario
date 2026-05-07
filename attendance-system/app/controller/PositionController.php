<?php

class PositionController
{
    protected $positionRepository;

    public function __construct() {
        $db = (new Database())->connect();
        $this->positionRepository = new PositionRepository($db);
    }

    /**
     * Get all positions
     * @return array
     */
    public function getAll()
    {
        return $this->positionRepository->findAll();
    }

    /**
     * Get position by ID
     * @param int $id
     * @return object|array|null
     */
    public function getById($id)
    {
        return $this->positionRepository->findById($id);
    }

    /**
     * Create a new position
     * @param array $data
     * @return array
     */
    public function store($data)
    {
        // Validate required fields
        if (empty($data['position_name'])) {
            return [
                "success" => false,
                "message" => "Position name is required."
            ];
        }

        // Check for duplicate position_name
        $existing = $this->positionRepository->findBy('position_name', trim($data['position_name']));
        if ($existing) {
            return [
                "success" => false,
                "message" => "Position already exists."
            ];
        }

        // Create the record
        $insertData = ['position_name' => trim($data['position_name'])];
        $id = $this->positionRepository->create($insertData);

        if ($id) {
            return [
                "success" => true,
                "message" => "Position created successfully.",
                "id" => $id
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to create position."
        ];
    }

    /**
     * Update a position
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update($id, $data)
    {
        // Validate required fields
        if (empty($data['position_name'])) {
            return [
                "success" => false,
                "message" => "Position name is required."
            ];
        }

        // Check if record exists
        if (!$this->positionRepository->exists($id)) {
            return [
                "success" => false,
                "message" => "Position not found."
            ];
        }

        // Check for duplicate position_name (excluding current record)
        $existing = $this->positionRepository->findBy('position_name', trim($data['position_name']));
        if ($existing && (is_object($existing) ? $existing->position_id : $existing['position_id']) != $id) {
            return [
                "success" => false,
                "message" => "Position with this name already exists."
            ];
        }

        // Update the record
        $updateData = ['position_name' => trim($data['position_name'])];
        $success = $this->positionRepository->update($id, $updateData);

        if ($success) {
            return [
                "success" => true,
                "message" => "Position updated successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to update position."
        ];
    }

    /**
     * Delete a position
     * @param int $id
     * @return array
     */
    public function delete($id)
    {
        // Check if record exists
        if (!$this->positionRepository->exists($id)) {
            return [
                "success" => false,
                "message" => "Position not found."
            ];
        }

        // Delete the record
        $success = $this->positionRepository->delete($id);

        if ($success) {
            return [
                "success" => true,
                "message" => "Position deleted successfully."
            ];
        }

        return [
            "success" => false,
            "message" => "Failed to delete position."
        ];
    }

    /**
     * Legacy method for backward compatibility
     * @return array
     */
    public function getAllPosition()
    {
        return $this->getAll();
    }
}