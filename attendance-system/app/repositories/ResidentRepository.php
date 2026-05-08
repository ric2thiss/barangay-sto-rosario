<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../query/QueryBuilder.php';

class ResidentRepository extends BaseRepository {
    protected function getModelClass(): string {
        return Resident::class;
    }

    private function profilingResidentsTable(string $alias = 'residents'): string {
        $table = "`" . PROFILING_DB_NAME . "`.`residents`";
        return $alias ? "{$table} AS {$alias}" : $table;
    }

    private function mapResidentRow($row): array {
        if (is_object($row)) {
            $row = json_decode(json_encode($row), true);
        }

        $residentId = $row['resident_id'] ?? $row['id'] ?? null;

        return [
            'resident_id' => $residentId,
            'first_name' => $row['first_name'] ?? null,
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'] ?? ($row['surname'] ?? null),
            'suffix' => null,
            'gender' => $row['gender'] ?? ($row['sex'] ?? null),
            'birthdate' => $row['birthdate'] ?? null,
            'place_of_birth_city' => $row['birthplace'] ?? null,
            'place_of_birth_province' => null,
            'age' => $row['age'] ?? null,
            'civil_status' => $row['civil_status'] ?? null,
            'nationality' => $row['nationality'] ?? null,
            'is_pwd' => $row['is_pwd'] ?? null,
            'is_deceased' => $row['is_deceased'] ?? null,
            'contact_no' => $row['contact_no'] ?? null,
            'purok' => $row['purok'] ?? null,
            'barangay' => $row['barangay'] ?? null,
            'municipality_city' => $row['municipality'] ?? null,
            'province' => $row['province'] ?? null,
            'household_no' => $row['household_no'] ?? null,
            'total_household' => $row['total_household'] ?? null,
            'voters_status' => $row['voters_status'] ?? null,
            'educational_attainment' => $row['educational_attainment'] ?? null,
            'occupation' => $row['occupation'] ?? null,
            'monthly_income' => $row['monthly_income'] ?? null,
            'annual_income' => $row['annual_income'] ?? null,
            'photo_path' => $row['photo_path'] ?? ($row['image_path'] ?? null),
            'image_path' => $row['image_path'] ?? null,
            'phil_sys_number' => null,
            'address_type' => null,
            'house_number' => null,
            'building_name' => null,
            'street_name' => null,
            'subdivision_village' => null,
            'sitio' => null,
            'district' => null,
            'region' => null,
            'postal_code' => null,
            'status_type' => null,
            'is_active' => null,
            'status_name' => null,
        ];
    }

    /**
     * Get all residents not assigned as employees
     */
    public function getAllNotEmployee(): array {
        // attendance-system no longer owns employees; cannot exclude "employees" here.
        $query = new QueryBuilder($this->pdo);
        $rows = $query->table($this->profilingResidentsTable())
            ->select(
                "residents.id as resident_id",
                "residents.first_name",
                "residents.middle_name",
                "residents.surname as last_name",
                "residents.sex as gender",
                "residents.birthdate",
                "residents.age",
                "residents.purok",
                "residents.barangay",
                "residents.municipality",
                "residents.province",
                "residents.image_path"
            )
            ->get();

        return array_map([$this, 'mapResidentRow'], $rows);
    }

    /**
     * Get residents from profiling-system (read-only)
     *
     * @param string|null $id
     * @return array|object|null
     */
    public function getAllWithRelations(?string $id = null) {
        $query = new QueryBuilder($this->pdo);
        $query->table($this->profilingResidentsTable())
            ->select(
                "residents.id as resident_id",
                "residents.first_name",
                "residents.middle_name",
                "residents.surname as last_name",
                "residents.sex as gender",
                "residents.birthdate",
                "residents.birthplace",
                "residents.age",
                "residents.civil_status",
                "residents.nationality",
                "residents.is_pwd",
                "residents.is_deceased",
                "residents.contact_no",
                "residents.purok",
                "residents.barangay",
                "residents.municipality",
                "residents.province",
                "residents.total_household",
                "residents.voters_status",
                "residents.educational_attainment",
                "residents.occupation",
                "residents.monthly_income",
                "residents.annual_income",
                "residents.image_path",
                "residents.created_at",
                "residents.updated_at"
            );

        if ($id) {
            $query->where("residents.id", $id);
        }

        $rows = $query->get();

        if (empty($rows)) {
            return $id ? null : [];
        }

        $mapped = array_map([$this, 'mapResidentRow'], $rows);
        return $id ? ($mapped[0] ?? null) : $mapped;
    }

    /**
     * Get paginated residents with search
     */
    public function getPaginated(int $page, int $perPage, string $searchQuery = '', array $filters = []): array {
        $offset = ($page - 1) * $perPage;

        $countQuery = new QueryBuilder($this->pdo);
        $countQuery->table($this->profilingResidentsTable())
            ->select("COUNT(*) as total");

        if (!empty($searchQuery)) {
            $countQuery->whereRaw(
                "(CONCAT(residents.first_name, ' ', residents.surname) LIKE ? OR residents.id LIKE ?)",
                ["%{$searchQuery}%", "%{$searchQuery}%"]
            );
        }

        // Filters from profiling-system.residents (enum Yes/No)
        if (!empty($filters['is_pwd'])) {
            $countQuery->whereRaw("residents.is_pwd = ?", [$filters['is_pwd']]);
        }
        if (!empty($filters['is_deceased'])) {
            $countQuery->whereRaw("residents.is_deceased = ?", [$filters['is_deceased']]);
        }

        $totalCountQuery = $countQuery->first();
        $totalRecords = is_object($totalCountQuery) ? (int) $totalCountQuery->total : (int) ($totalCountQuery['total'] ?? 0);
        $totalPages = $totalRecords > 0 ? ceil($totalRecords / $perPage) : 1;

        $baseQuery = new QueryBuilder($this->pdo);
        $baseQuery->table($this->profilingResidentsTable())
            ->select(
                "residents.id as resident_id",
                "residents.first_name",
                "residents.middle_name",
                "residents.surname as last_name",
                "residents.sex as gender",
                "residents.birthdate",
                "residents.birthplace",
                "residents.age",
                "residents.civil_status",
                "residents.nationality",
                "residents.is_pwd",
                "residents.is_deceased",
                "residents.contact_no",
                "residents.purok",
                "residents.barangay",
                "residents.municipality",
                "residents.province",
                "residents.image_path"
            );

        if (!empty($searchQuery)) {
            $baseQuery->whereRaw(
                "(CONCAT(residents.first_name, ' ', residents.surname) LIKE ? OR residents.id LIKE ?)",
                ["%{$searchQuery}%", "%{$searchQuery}%"]
            );
        }

        if (!empty($filters['is_pwd'])) {
            $baseQuery->whereRaw("residents.is_pwd = ?", [$filters['is_pwd']]);
        }
        if (!empty($filters['is_deceased'])) {
            $baseQuery->whereRaw("residents.is_deceased = ?", [$filters['is_deceased']]);
        }

        $residents = $baseQuery
            ->limit($perPage)
            ->offset($offset)
            ->get();

        $processedResidents = array_map([$this, 'mapResidentRow'], $residents);

        return [
            "residents" => $processedResidents,
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
     * Residents are managed by profiling-system.
     */
    public function existsByPhilSysNumber(string $philSysNumber, ?string $excludeId = null): bool {
        return false;
    }

    public function existsById(int $residentId): bool {
        // Residents live in profiling-system; attendance-system no longer owns a local `residents` table.
        // Query the fully-qualified profiling-system.residents table directly.
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM `" . PROFILING_DB_NAME . "`.`residents` WHERE id = ? LIMIT 1");
            $stmt->execute([$residentId]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            // If profiling DB is unreachable or table missing, treat as non-existent.
            return false;
        }
    }

    public function isEmployee(string $residentId): bool {
        // attendance-system no longer owns employee records; employees live in profiling-system (barangay_official).
        return false;
    }

    public function create(array $data) {
        throw new RuntimeException("Residents are read-only in attendance-system.");
    }

    public function update($id, array $data): bool {
        throw new RuntimeException("Residents are read-only in attendance-system.");
    }

    public function delete($id): bool {
        throw new RuntimeException("Residents are read-only in attendance-system.");
    }

    public function createWithRelations(array $data, array $addressData = [], array $statusData = [], ?array $biometricData = null): array {
        return [
            "success" => false,
            "message" => "Residents are managed by profiling-system. Create the resident there."
        ];
    }

    public function updateWithRelations(string $residentId, array $data, array $addressData = [], array $statusData = [], ?array $biometricData = null): array {
        return [
            "success" => false,
            "message" => "Residents are managed by profiling-system. Update the resident there."
        ];
    }

    public function deleteWithRelations(string $residentId): array {
        return [
            "success" => false,
            "message" => "Residents are managed by profiling-system. Delete the resident there."
        ];
    }
}
