<?php

class NonResidentServiceController
{
    protected $serviceRepository;

    public function __construct() {
        $db = (new Database())->connect();
        $this->serviceRepository = new NonResidentServiceRepository($db);
    }

    public function getAll()
    {
        return $this->serviceRepository->findAll();
    }

    public function getById($id)
    {
        return $this->serviceRepository->findById($id);
    }

    public function store($data)
    {
        if (empty($data['service_name'])) {
            return ["success" => false, "message" => "Service name is required."];
        }
        if (empty($data['service_id'])) {
            return ["success" => false, "message" => "Service ID is required."];
        }

        $existing = $this->serviceRepository->findByServiceId($data['service_id']);
        if ($existing) {
            return ["success" => false, "message" => "Service ID already exists."];
        }

        $id = $this->serviceRepository->create([
            'service_id' => $data['service_id'],
            'service_name' => $data['service_name'],
            'is_allowed' => isset($data['is_allowed']) ? (int)$data['is_allowed'] : 1
        ]);

        if ($id) {
            return ["success" => true, "message" => "Service added successfully.", "id" => $id];
        }
        return ["success" => false, "message" => "Failed to add service."];
    }

    public function update($id, $data)
    {
        if (!$this->serviceRepository->exists($id)) {
            return ["success" => false, "message" => "Service not found."];
        }

        $updateData = [];
        if (isset($data['service_name'])) $updateData['service_name'] = $data['service_name'];
        if (isset($data['service_id'])) $updateData['service_id'] = $data['service_id'];
        if (isset($data['is_allowed'])) $updateData['is_allowed'] = (int)$data['is_allowed'];

        if (empty($updateData)) {
            return ["success" => false, "message" => "No data provided for update."];
        }

        $success = $this->serviceRepository->update($id, $updateData);
        if ($success) {
            return ["success" => true, "message" => "Service updated successfully."];
        }
        return ["success" => false, "message" => "Failed to update service."];
    }

    public function delete($id)
    {
        if (!$this->serviceRepository->exists($id)) {
            return ["success" => false, "message" => "Service not found."];
        }

        $success = $this->serviceRepository->delete($id);
        if ($success) {
            return ["success" => true, "message" => "Service deleted successfully."];
        }
        return ["success" => false, "message" => "Failed to delete service."];
    }
}
