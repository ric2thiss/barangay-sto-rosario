<?php

require_once __DIR__ . '/BaseRepository.php';

class VisitorLogRepository extends BaseRepository {
    public function __construct(PDO $pdo = null) {
        // Use provided PDO or create new connection
        if ($pdo === null) {
            $pdo = (new Database())->connect();
        }
        parent::__construct($pdo);
    }

    protected function getModelClass(): string {
        return VisitorLog::class;
    }

    /**
     * @param QueryBuilder $query
     */
    private function applyVisitorSoftDelete($query): void
    {
        if (SchemaColumnCache::visitorLogsHasDeletedAt()) {
            $query->whereRaw('(deleted_at IS NULL)');
        }
    }

    /**
     * Get visitor logs with optional filters
     * 
     * @param array $filters Optional filters: resident_id, is_resident, had_booking, date_from, date_to
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array
     */
    public function getLogs(array $filters = [], int $limit = null, int $offset = null): array {
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
    public function getCount(array $filters = []): int {
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
     * Create a visitor log entry
     * 
     * @param array $data Visitor log data
     * @return array|object|null
     */
    public function createLog(array $data) {
        return VisitorLog::create($data);
    }

    /**
     * Visitor Reports listing: READ-ONLY SELECT with optional join to profiling residents.
     *
     * @param array $filters date_from, date_to (datetime strings), optional search, purpose, gender, purok
     */
    public function getLogsForReports(array $filters, int $limit, int $offset, string $sortDir = 'DESC'): array {
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        [$where, $params] = $this->buildReportWhere($filters);
        $profilingDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
        $residents = '`' . $profilingDb . '`.`residents`';

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
                vl.updated_at,
                r.sex AS resident_sex,
                r.purok AS resident_purok
            FROM visitor_logs vl
            LEFT JOIN {$residents} r ON vl.resident_id = r.id AND vl.is_resident = 1
            {$where}
            ORDER BY vl.created_at {$sortDir}
            LIMIT {$lim} OFFSET {$off}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getCountForReports(array $filters): int {
        [$where, $params] = $this->buildReportWhere($filters);
        $profilingDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
        $residents = '`' . $profilingDb . '`.`residents`';
        $sql = "
            SELECT COUNT(*) AS c
            FROM visitor_logs vl
            LEFT JOIN {$residents} r ON vl.resident_id = r.id AND vl.is_resident = 1
            {$where}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['c'] ?? 0);
    }

    public function getUniqueVisitorsCountForReports(array $filters): int {
        [$where, $params] = $this->buildReportWhere($filters);
        $profilingDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
        $residents = '`' . $profilingDb . '`.`residents`';
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
            LEFT JOIN {$residents} r ON vl.resident_id = r.id AND vl.is_resident = 1
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
    public function getReportFilterOptions(string $dateFrom, string $dateTo): array {
        $profilingDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
        $residents = '`' . $profilingDb . '`.`residents`';
        $baseJoin = "
            FROM visitor_logs vl
            LEFT JOIN {$residents} r ON vl.resident_id = r.id AND vl.is_resident = 1
            WHERE vl.created_at >= ? AND vl.created_at <= ?
        ";
        $p = [$dateFrom, $dateTo];

        $purposes = [];
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT vl.purpose AS v {$baseJoin} AND TRIM(COALESCE(vl.purpose, '')) <> '' ORDER BY vl.purpose ASC"
        );
        $stmt->execute($p);
        $purposes = array_values(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));

        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT COALESCE(r.sex, IF(vl.is_resident = 1, 'Unknown', 'Non-resident')) AS v {$baseJoin} ORDER BY v ASC"
        );
        $stmt->execute($p);
        $genders = array_values(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [], static function ($g) {
            return $g !== null && $g !== '';
        }));

        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT COALESCE(NULLIF(TRIM(r.purok), ''), IF(vl.is_resident = 1, 'Unknown', 'Non-resident')) AS v {$baseJoin} ORDER BY v ASC"
        );
        $stmt->execute($p);
        $puroks = array_values(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));

        return ['purposes' => $purposes, 'genders' => $genders, 'puroks' => $puroks];
    }

    /**
     * @return array{0: string, 1: array} WHERE clause (starts with WHERE) and bound parameters
     */
    private function buildReportWhere(array $filters): array {
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

        if (!empty($filters['gender'])) {
            $where .= ' AND COALESCE(r.sex, IF(vl.is_resident = 1, \'Unknown\', \'Non-resident\')) = ?';
            $params[] = $filters['gender'];
        }

        if (!empty($filters['purok'])) {
            $where .= ' AND COALESCE(NULLIF(TRIM(r.purok), \'\'), IF(vl.is_resident = 1, \'Unknown\', \'Non-resident\')) = ?';
            $params[] = $filters['purok'];
        }

        return [$where, $params];
    }
}
