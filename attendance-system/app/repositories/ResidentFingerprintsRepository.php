<?php

require_once __DIR__ . '/BaseRepository.php';

class ResidentFingerprintsRepository extends BaseRepository {
    protected function getModelClass(): string {
        return ResidentFingerprints::class;
    }

    public function existsByResidentId(int $residentId): bool {
        $fingerprint = $this->findBy('resident_id', $residentId);
        return $fingerprint !== null;
    }

    /**
     * Get all resident fingerprints with limited fields (for admin/debug use).
     *
     * @return array
     */
    public function getAllLimited(): array {
        return ResidentFingerprints::query()
            ->select("resident_id", "template")
            ->get();
    }
}

