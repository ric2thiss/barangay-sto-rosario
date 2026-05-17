<?php

require_once __DIR__ . '/BaseRepository.php';

class FingerprintsRepository extends BaseRepository {
    protected function getModelClass(): string {
        return Fingerprints::class;
    }

    /**
     * Get all fingerprints with limited fields
     * 
     * @return array
     */
    public function getAllLimited(): array {
        return Fingerprints::query()
            ->select("employee_id", "template")
            ->get();
    }

    /**
     * Check if employee fingerprint exists
     * 
     * @param string $employeeId
     * @return bool
     */
    public function existsByEmployeeId(string $employeeId): bool {
        $fingerprint = $this->findBy('employee_id', $employeeId);
        return $fingerprint !== null;
    }

    /**
     * Find fingerprint by employee identifier with normalization.
     *
     * Some clients may send numeric IDs with leading zeros ("001") while the DB stores "1".
     * Since employee_id is stored as varchar, we support a numeric-compare fallback for digit-only IDs.
     *
     * @param string $employeeId
     * @return object|array|null
     */
    public function findByEmployeeIdentifier(string $employeeId) {
        $employeeId = trim($employeeId);
        if ($employeeId === '') {
            return null;
        }

        $fingerprint = $this->findBy('employee_id', $employeeId);
        if ($fingerprint) {
            return $fingerprint;
        }

        if (ctype_digit($employeeId)) {
            return $this->modelClass::query()
                ->whereRaw("CAST(employee_id AS UNSIGNED) = ?", [(int) $employeeId])
                ->first();
        }

        return null;
    }

    /**
     * Backward-compatible existence check that tolerates numeric formatting differences.
     */
    public function existsByEmployeeIdentifier(string $employeeId): bool {
        return $this->findByEmployeeIdentifier($employeeId) !== null;
    }
}
