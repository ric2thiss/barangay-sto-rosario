<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../utils/ProfilingPdo.php';

class VisitorLogRepository extends BaseRepository
{
    public function __construct(PDO $pdo = null)
    {
        // Use provided PDO or create new connection
        if ($pdo === null) {
            $pdo = (new Database())->connect();
        }
        parent::__construct($pdo);
    }

    protected function getModelClass(): string
    {
        return VisitorLog::class;
    }

    /**
     * @param QueryBuilder $query
     */
    private function applyVisitorSoftDelete($query): void
    {
        $this->applySoftDelete($query);
    }

    /**
     * Get visitor logs with optional filters
     * 
     * @param array $filters Optional filters: resident_id, is_resident, had_booking, date_from, date_to
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array
     */
    public function getLogs(array $filters = [], int $limit = null, int $offset = null): array
    {
        $query = VisitorLog::query();
        $this->applyVisitorSoftDelete($query);

        // Apply filters
        if (isset($filters['resident_id'])) {
            $query->where('resident_id', $filters['resident_id']);
        }

        if (isset($filters['is_resident'])) {
            $query->where('is_resident', $filters['is_resident'] ? 1 : 0);
        }

        if (isset($filters['had_booking'])) {
            $query->where('had_booking', $filters['had_booking'] ? 1 : 0);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
        } elseif (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        } elseif (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Order by most recent first
        $query->orderBy('created_at', 'DESC');

        // Apply limit and offset
        if ($limit !== null) {
            $query->limit($limit);
            if ($offset !== null) {
                $query->offset($offset);
            }
        }

        return $query->get();
    }

    /**
     * Get visitor logs count with filters
     * 
     * @param array $filters Optional filters
     * @return int
     */
    public function getCount(array $filters = []): int
    {
        $query = VisitorLog::query();
        $this->applyVisitorSoftDelete($query);

        // Apply same filters as getLogs
        if (isset($filters['resident_id'])) {
            $query->where('resident_id', $filters['resident_id']);
        }

        if (isset($filters['is_resident'])) {
            $query->where('is_resident', $filters['is_resident'] ? 1 : 0);
        }

        if (isset($filters['had_booking'])) {
            $query->where('had_booking', $filters['had_booking'] ? 1 : 0);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
        } elseif (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        } elseif (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->count();
    }

    /**
     * Find an existing visitor log by booking_id
     * Used to prevent duplicate logging for the same request/appointment.
     *
     * @param string $bookingId
     * @return array|null
     */
    public function findByBookingId(string $bookingId): ?array
    {
        $sql = "SELECT id FROM visitor_logs WHERE booking_id = :bid";
        if (SchemaColumnCache::visitorLogsHasDeletedAt()) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':bid' => $bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find whether a resident was already logged today for the same purpose.
     * Used to prevent duplicate walk-in logs (no booking_id).
     *
     * @param int    $residentId
     * @param string $purpose
     * @return array|null
     */
    public function findTodayLogByResident(int $residentId, string $purpose): ?array
    {
        $today = date('Y-m-d');
        $sql = "SELECT id FROM visitor_logs
                WHERE resident_id = :rid
                  AND purpose = :purpose
                  AND DATE(created_at) = :today";
        if (SchemaColumnCache::visitorLogsHasDeletedAt()) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':rid' => $residentId, ':purpose' => $purpose, ':today' => $today]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Create a visitor log entry
     * 
     * @param array $data Visitor log data
     * @return array|object|null
     */
    public function createLog(array $data)
    {
        return VisitorLog::create($data);
    }

    /**
     * Visitor Reports listing: queries local visitor_logs, then enriches with ProfilingPdo.
     *
     * @param array $filters date_from, date_to (datetime strings), optional search, purpose, gender, purok
     */
    public function getLogsForReports(array $filters, int $limit, int $offset, string $sortDir = 'DESC'): array
    {
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        [$where, $params] = $this->buildReportWhere($filters);

        $lim = max(1, min((int) $limit, 2000));
        $off = max(0, (int) $offset);

        $sql = "
            SELECT
                vl.id,
                vl.resident_id,
                vl.first_name,
                vl.middle_name,
                vl.last_name,
                vl.birthdate,
                vl.address,
                vl.purpose,
                vl.is_resident,
                vl.had_booking,
                vl.booking_id,
                vl.created_at,
                vl.updated_at
            FROM visitor_logs vl
            {$where}
            ORDER BY vl.created_at {$sortDir}
            LIMIT {$lim} OFFSET {$off}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Enrich with resident sex/purok from profiling DB
        return $this->enrichWithResidentData($rows);
    }

    public function getCountForReports(array $filters): int
    {
        [$where, $params] = $this->buildReportWhere($filters);
        $sql = "
            SELECT COUNT(*) AS c
            FROM visitor_logs vl
            {$where}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['c'] ?? 0);
    }

    public function getUniqueVisitorsCountForReports(array $filters): int
    {
        [$where, $params] = $this->buildReportWhere($filters);
        $keySql = "
            CASE
                WHEN vl.is_resident = 1 AND vl.resident_id IS NOT NULL THEN CONCAT('R:', vl.resident_id)
                ELSE CONCAT(
                    'N:',
                    LOWER(TRIM(COALESCE(vl.first_name, ''))),
                    '|',
                    LOWER(TRIM(COALESCE(vl.last_name, ''))),
                    '|',
                    COALESCE(CAST(vl.birthdate AS CHAR), '')
                )
            END
        ";
        $sql = "
            SELECT COUNT(DISTINCT ({$keySql})) AS c
            FROM visitor_logs vl
            {$where}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Distinct filter values for the given date range (no other filters).
     *
     * @return array{purposes: array, genders: array, puroks: array}
     */
    public function getReportFilterOptions(string $dateFrom, string $dateTo): array
    {
        $baseWhere = " FROM visitor_logs vl WHERE vl.created_at >= ? AND vl.created_at <= ?";
        $p = [$dateFrom, $dateTo];

        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT vl.purpose AS v {$baseWhere} AND TRIM(COALESCE(vl.purpose, '')) <> '' ORDER BY vl.purpose ASC"
        );
        $stmt->execute($p);
        $purposes = array_values(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));

        // Fetch resident IDs from the date range to look up sex/purok from profiling DB
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT vl.resident_id {$baseWhere} AND vl.is_resident = 1 AND vl.resident_id IS NOT NULL"
        );
        $stmt->execute($p);
        $residentIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $genders = ['Non-resident'];
        $puroks = ['Non-resident'];

        if (!empty($residentIds)) {
            $profilingPdo = ProfilingPdo::get();
            if ($profilingPdo) {
                $ph = implode(',', array_fill(0, count($residentIds), '?'));
                $s = $profilingPdo->prepare("SELECT DISTINCT sex FROM residents WHERE id IN ({$ph}) AND sex IS NOT NULL AND sex <> ''");
                $s->execute($residentIds);
                $genders = array_merge($s->fetchAll(PDO::FETCH_COLUMN) ?: [], $genders);

                $s = $profilingPdo->prepare("SELECT DISTINCT purok FROM residents WHERE id IN ({$ph}) AND purok IS NOT NULL AND TRIM(purok) <> ''");
                $s->execute($residentIds);
                $puroks = array_merge($s->fetchAll(PDO::FETCH_COLUMN) ?: [], $puroks);
            }
        }

        sort($genders);
        sort($puroks);

        return ['purposes' => $purposes, 'genders' => $genders, 'puroks' => $puroks];
    }

    /**
     * Enrich visitor log rows with resident sex/purok from profiling DB.
     */
    private function enrichWithResidentData(array $rows): array
    {
        $residentIds = [];
        foreach ($rows as $row) {
            if (($row->is_resident ?? 0) && ($row->resident_id ?? null)) {
                $residentIds[] = (int) $row->resident_id;
            }
        }
        $residentIds = array_unique($residentIds);

        $residentMap = [];
        if (!empty($residentIds)) {
            $profilingPdo = ProfilingPdo::get();
            if ($profilingPdo) {
                $ph = implode(',', array_fill(0, count($residentIds), '?'));
                $stmt = $profilingPdo->prepare("SELECT id, sex, purok FROM residents WHERE id IN ({$ph})");
                $stmt->execute(array_values($residentIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $residentMap[$r['id']] = $r;
                }
            }
        }

        foreach ($rows as $row) {
            $rid = $row->resident_id ?? null;
            $res = $residentMap[$rid] ?? null;
            $row->resident_sex = $res['sex'] ?? null;
            $row->resident_purok = $res['purok'] ?? null;
        }
        return $rows;
    }

    /**
     * @return array{0: string, 1: array} WHERE clause (starts with WHERE) and bound parameters
     */
    private function buildReportWhere(array $filters): array
    {
        if (empty($filters['date_from']) || empty($filters['date_to'])) {
            throw new InvalidArgumentException('date_from and date_to are required for visitor reports');
        }

        if (SchemaColumnCache::visitorLogsHasDeletedAt()) {
            $where = ' WHERE vl.deleted_at IS NULL AND vl.created_at >= ? AND vl.created_at <= ?';
        } else {
            $where = ' WHERE vl.created_at >= ? AND vl.created_at <= ?';
        }
        $params = [$filters['date_from'], $filters['date_to']];

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $where .= " AND (
                vl.first_name LIKE ? OR vl.middle_name LIKE ? OR vl.last_name LIKE ?
                OR CONCAT(COALESCE(vl.first_name, ''), ' ', COALESCE(vl.middle_name, ''), ' ', COALESCE(vl.last_name, '')) LIKE ?
                OR vl.purpose LIKE ?
                OR vl.address LIKE ?
            )";
            array_push($params, $term, $term, $term, $term, $term, $term);
        }

        if (!empty($filters['purpose'])) {
            $where .= ' AND vl.purpose = ?';
            $params[] = $filters['purpose'];
        }

        // Gender/purok filters: pre-fetch matching resident IDs from profiling DB
        if (!empty($filters['gender'])) {
            $matchIds = $this->getResidentIdsByGender($filters['gender']);
            if ($filters['gender'] === 'Non-resident') {
                $where .= ' AND vl.is_resident = 0';
            } elseif (!empty($matchIds)) {
                $ph = implode(',', array_fill(0, count($matchIds), '?'));
                $where .= " AND vl.resident_id IN ({$ph})";
                $params = array_merge($params, $matchIds);
            } else {
                $where .= ' AND 1 = 0'; // no matches
            }
        }

        if (!empty($filters['purok'])) {
            $matchIds = $this->getResidentIdsByPurok($filters['purok']);
            if ($filters['purok'] === 'Non-resident') {
                $where .= ' AND vl.is_resident = 0';
            } elseif (!empty($matchIds)) {
                $ph = implode(',', array_fill(0, count($matchIds), '?'));
                $where .= " AND vl.resident_id IN ({$ph})";
                $params = array_merge($params, $matchIds);
            } else {
                $where .= ' AND 1 = 0';
            }
        }

        return [$where, $params];
    }

    private function getResidentIdsByGender(string $gender): array
    {
        $pdo = ProfilingPdo::get();
        if (!$pdo)
            return [];
        $stmt = $pdo->prepare("SELECT id FROM residents WHERE sex = ?");
        $stmt->execute([$gender]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function getResidentIdsByPurok(string $purok): array
    {
        $pdo = ProfilingPdo::get();
        if (!$pdo)
            return [];
        $stmt = $pdo->prepare("SELECT id FROM residents WHERE TRIM(purok) = ?");
        $stmt->execute([$purok]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}
