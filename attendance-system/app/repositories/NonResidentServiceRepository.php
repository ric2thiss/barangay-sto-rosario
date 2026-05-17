<?php

require_once __DIR__ . '/BaseRepository.php';

class NonResidentServiceRepository extends BaseRepository {
    protected function getModelClass(): string {
        return NonResidentService::class;
    }

    public function findAll(): array {
        return $this->modelClass::query()
            ->orderBy('service_name', 'ASC')
            ->get();
    }

    public function findByServiceId(string $serviceId) {
        return $this->modelClass::query()
            ->where('service_id', $serviceId)
            ->first();
    }
}
