<?php

class ResidentController
{
    protected $residentRepository;

    public function __construct() {
        $db = (new Database())->connect();
        $this->residentRepository = new ResidentRepository($db);
    }

    public function getAllResidentNotEmployee()
    {
        return $this->residentRepository->getAllNotEmployee();
    }

    public function getAllResidents($id = null)
    {
        return $this->residentRepository->getAllWithRelations($id);
    }

    /**
     * Get paginated residents with search and filters
     *
     * @param int $page Current page number
     * @param int $perPage Records per page
     * @param string $searchQuery Search term (optional)
     * @param array $filters Optional filters: status_type, is_active
     * @return array
     */
    public function getPaginatedResidents($page = 1, $perPage = 10, $searchQuery = '', $filters = [])
    {
        return $this->residentRepository->getPaginated($page, $perPage, $searchQuery, $filters);
    }

    /**
     * Store a new resident with address, status, and biometrics
     *
     * @param array $data Resident data
     * @param array $addressData Address data (optional)
     * @param array $statusData Status data (optional)
     * @param array $biometricData Biometric data (optional)
     * @return array
     */
    public function store($data, $addressData = [], $statusData = [], $biometricData = null)
    {
        return [
            "success" => false,
            "message" => "Residents are managed by profiling-system. Create residents there."
        ];
    }

    /**
     * Update an existing resident with address, status, and biometrics
     *
     * @param string $residentId Resident ID
     * @param array $data Resident data
     * @param array $addressData Address data (optional)
     * @param array $statusData Status data (optional)
     * @param array $biometricData Biometric data (optional)
     * @return array
     */
    public function update($residentId, $data, $addressData = [], $statusData = [], $biometricData = null)
    {
        return [
            "success" => false,
            "message" => "Residents are managed by profiling-system. Update residents there."
        ];
    }

    public function delete($residentId)
    {
        return [
            "success" => false,
            "message" => "Residents are managed by profiling-system. Delete residents there."
        ];
    }
}