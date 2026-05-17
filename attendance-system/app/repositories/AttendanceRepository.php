<?php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../utils/ProfilingPdo.php';

class AttendanceRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Attendance::class;
    }

    /**
     * Adds deleted_at filter only when the column exists (migration may not be applied yet).
     *
     * @param QueryBuilder $qb
     */
    private function applyAttendancesSoftDelete($qb, ?string $tableAlias = null): void
    {
        $this->applySoftDelete($qb, $tableAlias);
    }

    /**
     * Helper: merge barangay_official names from ProfilingPdo into attendance rows.
     * Uses employee_id to look up official names, then sets full_name on each row.
     *
     * @param array $attendances  Array of attendance objects/arrays (must have 'employee_id')
     * @return array              Array of merged objects
     */
    private function mergeOfficialNames(array $attendances): array
    {
        // Collect employee IDs (which are official IDs in profiling DB)
        $officialIds = [];
        foreach ($attendances as $att) {
            $eid = is_object($att) ? ($att->employee_id ?? null) : ($att['employee_id'] ?? null);
            if ($eid)
                $officialIds[] = (int) $eid;
        }
        $officialIds = array_unique($officialIds);

        // Batch fetch from profiling DB
        $officials = !empty($officialIds) ? ProfilingPdo::fetchOfficialsByIds($officialIds) : [];

        // Merge
        $result = [];
        foreach ($attendances as $att) {
            $a = is_object($att) ? json_decode(json_encode($att), true) : $att;
            $eid = $a['employee_id'] ?? null;
            $off = $officials[$eid] ?? null;
            $a['full_name'] = $off ? trim(($off['first_name'] ?? '') . ' ' . ($off['surname'] ?? '')) : null;
            $result[] = (object) $a;
        }
        return $result;
    }

    public function findAll(): array
    {
        $q = $this->modelClass::query();
        $this->applyAttendancesSoftDelete($q);

        return $q->get();
    }

    /**
     * Get last attendance record for today only
     * 
     * @return object|array|null
     */
    public function getLast()
    {
        $today = date("Y-m-d");
        $q = Attendance::query()
            ->table("attendances AS a")
            ->select("a.*, COALESCE(aw.label, a.window) AS window_label, act.name AS activity_name")
            ->leftJoin("attendance_windows AS aw", "LOWER(TRIM(a.window))", "=", "LOWER(TRIM(aw.label))")
            ->leftJoin("activities AS act", "a.activity_id", "=", "act.id");
        $this->applyAttendancesSoftDelete($q, 'a');
        return $q
            ->whereRaw("DATE(a.timestamp) = ? OR DATE(a.created_at) = ?", [$today, $today])
            ->orderBy('a.id', 'DESC')
            ->first();
    }

    /**
     * Get attendance count for today
     * 
     * @return int
     */
    public function getCountToday(): int
    {
        $q = Attendance::query()
            ->select("COUNT(DISTINCT employee_id) as Total");
        $this->applyAttendancesSoftDelete($q);
        $result = $q->whereRaw("DATE(created_at) = ?", [date("Y-m-d")])->first();

        return is_object($result) ? (int) ($result->Total ?? 0) : (int) ($result['Total'] ?? 0);
    }

    /**
     * Check if employee already logged today for a specific window
     * Uses case-insensitive comparison for window
     * 
     * @param string $employeeId
     * @param string $window
     * @return bool
     */
    public function existsTodayForWindow(string $employeeId, string $window): bool
    {
        // Normalize window to lowercase for case-insensitive comparison
        $normalizedWindow = strtolower(trim($window));

        $q = Attendance::query()
            ->where([
                "employee_id" => $employeeId,
            ]);
        $this->applyAttendancesSoftDelete($q);
        $existing = $q
            ->whereRaw("LOWER(TRIM(window)) = ?", [$normalizedWindow])
            ->whereRaw("DATE(created_at) = ?", [date("Y-m-d")])
            ->first();

        return $existing !== null;
    }

    /**
     * Whether a log already exists for this employee, window, and calendar day (by attendance timestamp).
     */
    public function existsForWindowOnDate(string $employeeId, string $window, string $dateYmd): bool
    {
        $normalizedWindow = strtolower(trim($window));
        $q = Attendance::query()
            ->where(["employee_id" => $employeeId]);
        $this->applyAttendancesSoftDelete($q);
        $existing = $q
            ->whereRaw("LOWER(TRIM(window)) = ?", [$normalizedWindow])
            ->whereRaw("DATE(COALESCE(`timestamp`, `created_at`)) = ?", [$dateYmd])
            ->first();
        return $existing !== null;
    }

    /**
     * All attendance rows in a date range (for analytics aggregation).
     *
     * @param int|string|null $activityFilter null / '' / 'all' = any activity; 0 = uncategorized (NULL); positive = that activity_id
     * @return array<int, array<string, mixed>>
     */
    public function getBetweenDatesByTimestamp(string $fromYmd, string $toYmd, $activityFilter = null): array
    {
        $q = Attendance::query()
            ->table("attendances AS a")
            ->select(
                "a.id",
                "a.employee_id",
                "a.timestamp",
                "a.created_at",
                "a.window",
                "a.activity_id",
                "COALESCE(aw.label, a.window) AS window_label"
            )
            ->leftJoin("attendance_windows AS aw", "LOWER(TRIM(a.window))", "=", "LOWER(TRIM(aw.label))");
        $this->applyAttendancesSoftDelete($q, 'a');
        $q->whereRaw("DATE(COALESCE(a.timestamp, a.created_at)) >= ?", [$fromYmd])
            ->whereRaw("DATE(COALESCE(a.timestamp, a.created_at)) <= ?", [$toYmd]);

        if ($activityFilter !== null && $activityFilter !== '' && strtolower((string) $activityFilter) !== 'all') {
            $aid = (int) $activityFilter;
            if ($aid === 0) {
                $q->whereNull("a.activity_id");
            } else {
                $q->where(["a.activity_id" => $aid]);
            }
        }

        $rows = $q->orderBy("a.timestamp", "ASC")->get();
        return is_array($rows) ? $rows : [];
    }

    /**
     * Get paginated attendances with search and date filtering
     * 
     * @param int $page
     * @param int $perPage
     * @param string $searchQuery
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array
     */
    public function getPaginated(int $page, int $perPage, string $searchQuery = '', ?string $fromDate = null, ?string $toDate = null, $activityFilter = null): array
    {
        $offset = ($page - 1) * $perPage;

        // If searching by name, find matching official IDs from profiling DB first
        $nameMatchIds = [];
        if (!empty($searchQuery)) {
            $nameMatchIds = ProfilingPdo::searchOfficials($searchQuery);
        }

        $baseQuery = Attendance::query()->table("attendances AS a")
            ->select("a.id AS attendance_id, a.employee_id, a.timestamp AS attendance_time, a.window, COALESCE(aw.label, a.window) AS window_label, a.activity_id, act.name AS activity_name")
            ->leftJoin("attendance_windows AS aw", "LOWER(TRIM(a.window))", "=", "LOWER(TRIM(aw.label))")
            ->leftJoin("activities AS act", "a.activity_id", "=", "act.id");
        $this->applyAttendancesSoftDelete($baseQuery, 'a');

        $countQuery = Attendance::query()->table("attendances AS a")
            ->select("COUNT(*) as total");
        $this->applyAttendancesSoftDelete($countQuery, 'a');

        if (!empty($searchQuery)) {
            if (!empty($nameMatchIds)) {
                $idPlaceholders = implode(',', array_fill(0, count($nameMatchIds), '?'));
                $searchCondition = "(a.employee_id LIKE ? OR a.employee_id IN ({$idPlaceholders}))";
                $searchParams = array_merge(["%{$searchQuery}%"], $nameMatchIds);
            } else {
                $searchCondition = "(a.employee_id LIKE ?)";
                $searchParams = ["%{$searchQuery}%"];
            }
            $baseQuery->whereRaw($searchCondition, $searchParams);
            $countQuery->whereRaw($searchCondition, $searchParams);
        }

        // Date filtering
        if (!empty($fromDate)) {
            $baseQuery->whereRaw("DATE(a.timestamp) >= ?", [$fromDate]);
            $countQuery->whereRaw("DATE(a.timestamp) >= ?", [$fromDate]);
        }
        if (!empty($toDate)) {
            $baseQuery->whereRaw("DATE(a.timestamp) <= ?", [$toDate]);
            $countQuery->whereRaw("DATE(a.timestamp) <= ?", [$toDate]);
        }

        // Activity filter: null = all; 0 = uncategorized only; positive = that activity
        if ($activityFilter !== null) {
            if ($activityFilter === 0) {
                $baseQuery->whereNull("a.activity_id");
                $countQuery->whereNull("a.activity_id");
            } elseif ((int) $activityFilter > 0) {
                $aid = (int) $activityFilter;
                $baseQuery->where(["a.activity_id" => $aid]);
                $countQuery->where(["a.activity_id" => $aid]);
            }
        }

        $totalCountQuery = $countQuery->first();
        $totalRecords = is_object($totalCountQuery) ? (int) $totalCountQuery->total : (int) ($totalCountQuery['total'] ?? 0);
        $totalPages = $totalRecords > 0 ? ceil($totalRecords / $perPage) : 1;

        $attendances = $baseQuery
            ->orderBy("a.timestamp", "DESC")
            ->limit($perPage)
            ->offset($offset)
            ->get();

        // Merge official names from profiling DB
        $merged = $this->mergeOfficialNames($attendances);

        return [
            "attendances" => $merged,
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
     * Paginated logs for attendance reports (sortable columns, same filters as getPaginated).
     *
     * @param 'timestamp'|'employee_id'|'full_name' $sortBy
     * @param 'asc'|'desc' $sortDir
     * @return array<string, mixed>
     */
    public function getPaginatedForReports(
        int $page,
        int $perPage,
        string $searchQuery = '',
        ?string $fromDate = null,
        ?string $toDate = null,
        $activityFilter = null,
        string $sortBy = 'timestamp',
        string $sortDir = 'desc'
    ): array {
        $offset = ($page - 1) * $perPage;

        // If searching by name, find matching official IDs from profiling DB first
        $nameMatchIds = [];
        if (!empty($searchQuery)) {
            $nameMatchIds = ProfilingPdo::searchOfficials($searchQuery);
        }

        $baseQuery = Attendance::query()->table("attendances AS a")
            ->select("a.id AS attendance_id, a.employee_id, a.timestamp AS attendance_time, a.window, COALESCE(aw.label, a.window) AS window_label, a.activity_id, act.name AS activity_name")
            ->leftJoin("attendance_windows AS aw", "LOWER(TRIM(a.window))", "=", "LOWER(TRIM(aw.label))")
            ->leftJoin("activities AS act", "a.activity_id", "=", "act.id");
        $this->applyAttendancesSoftDelete($baseQuery, 'a');

        $countQuery = Attendance::query()->table("attendances AS a")
            ->select("COUNT(*) as total");
        $this->applyAttendancesSoftDelete($countQuery, 'a');

        if (!empty($searchQuery)) {
            if (!empty($nameMatchIds)) {
                $idPlaceholders = implode(',', array_fill(0, count($nameMatchIds), '?'));
                $searchCondition = "(a.employee_id LIKE ? OR a.employee_id IN ({$idPlaceholders}))";
                $searchParams = array_merge(["%{$searchQuery}%"], $nameMatchIds);
            } else {
                $searchCondition = "(a.employee_id LIKE ?)";
                $searchParams = ["%{$searchQuery}%"];
            }
            $baseQuery->whereRaw($searchCondition, $searchParams);
            $countQuery->whereRaw($searchCondition, $searchParams);
        }

        if (!empty($fromDate)) {
            $baseQuery->whereRaw("DATE(a.timestamp) >= ?", [$fromDate]);
            $countQuery->whereRaw("DATE(a.timestamp) >= ?", [$fromDate]);
        }
        if (!empty($toDate)) {
            $baseQuery->whereRaw("DATE(a.timestamp) <= ?", [$toDate]);
            $countQuery->whereRaw("DATE(a.timestamp) <= ?", [$toDate]);
        }

        if ($activityFilter !== null) {
            if ($activityFilter === 0) {
                $baseQuery->whereNull("a.activity_id");
                $countQuery->whereNull("a.activity_id");
            } elseif ((int) $activityFilter > 0) {
                $aid = (int) $activityFilter;
                $baseQuery->where(["a.activity_id" => $aid]);
                $countQuery->where(["a.activity_id" => $aid]);
            }
        }

        $totalCountQuery = $countQuery->first();
        $totalRecords = is_object($totalCountQuery) ? (int) $totalCountQuery->total : (int) ($totalCountQuery['total'] ?? 0);
        $totalPages = $totalRecords > 0 ? ceil($totalRecords / $perPage) : 1;

        $dir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        if ($sortBy === 'employee_id') {
            $baseQuery->orderByRaw("a.employee_id {$dir}");
        } else {
            // For 'full_name' sort, we sort in PHP after merge; default to timestamp here
            $baseQuery->orderByRaw("a.timestamp {$dir}");
        }

        $attendances = $baseQuery
            ->limit($perPage)
            ->offset($offset)
            ->get();

        // Merge official names from profiling DB
        $merged = $this->mergeOfficialNames($attendances);

        // If sorting by full_name, sort in PHP
        if ($sortBy === 'full_name') {
            usort($merged, function ($a, $b) use ($dir) {
                $cmp = strcasecmp($a->full_name ?? '', $b->full_name ?? '');
                return $dir === 'ASC' ? $cmp : -$cmp;
            });
        }

        return [
            "attendances" => $merged,
            "pagination" => [
                "currentPage" => $page,
                "totalPages" => $totalPages,
                "totalRecords" => $totalRecords,
                "perPage" => $perPage,
                "startRecord" => $offset + 1,
                "endRecord" => min($offset + $perPage, $totalRecords),
            ],
            "searchQuery" => $searchQuery,
        ];
    }

    /**
     * Flat list for CSV/Word export (capped).
     *
     * @return array<int, object|array>
     */
    public function getReportLogsExport(
        string $searchQuery = '',
        ?string $fromDate = null,
        ?string $toDate = null,
        $activityFilter = null,
        string $sortBy = 'timestamp',
        string $sortDir = 'desc',
        int $maxRows = 5000
    ): array {
        // If searching by name, find matching official IDs from profiling DB first
        $nameMatchIds = [];
        if (!empty($searchQuery)) {
            $nameMatchIds = ProfilingPdo::searchOfficials($searchQuery);
        }

        $baseQuery = Attendance::query()->table("attendances AS a")
            ->select("a.id AS attendance_id, a.employee_id, a.timestamp AS attendance_time, a.window, COALESCE(aw.label, a.window) AS window_label, a.activity_id, act.name AS activity_name")
            ->leftJoin("attendance_windows AS aw", "LOWER(TRIM(a.window))", "=", "LOWER(TRIM(aw.label))")
            ->leftJoin("activities AS act", "a.activity_id", "=", "act.id");
        $this->applyAttendancesSoftDelete($baseQuery, 'a');

        if (!empty($searchQuery)) {
            if (!empty($nameMatchIds)) {
                $idPlaceholders = implode(',', array_fill(0, count($nameMatchIds), '?'));
                $searchCondition = "(a.employee_id LIKE ? OR a.employee_id IN ({$idPlaceholders}))";
                $searchParams = array_merge(["%{$searchQuery}%"], $nameMatchIds);
            } else {
                $searchCondition = "(a.employee_id LIKE ?)";
                $searchParams = ["%{$searchQuery}%"];
            }
            $baseQuery->whereRaw($searchCondition, $searchParams);
        }
        if (!empty($fromDate)) {
            $baseQuery->whereRaw("DATE(a.timestamp) >= ?", [$fromDate]);
        }
        if (!empty($toDate)) {
            $baseQuery->whereRaw("DATE(a.timestamp) <= ?", [$toDate]);
        }
        if ($activityFilter !== null) {
            if ($activityFilter === 0) {
                $baseQuery->whereNull("a.activity_id");
            } elseif ((int) $activityFilter > 0) {
                $baseQuery->where(["a.activity_id" => (int) $activityFilter]);
            }
        }

        $dir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        if ($sortBy === 'employee_id') {
            $baseQuery->orderByRaw("a.employee_id {$dir}");
        } else {
            $baseQuery->orderByRaw("a.timestamp {$dir}");
        }

        $attendances = $baseQuery->limit(max(1, min(20000, $maxRows)))->get();

        // Merge official names from profiling DB
        $merged = $this->mergeOfficialNames($attendances);

        if ($sortBy === 'full_name') {
            usort($merged, function ($a, $b) use ($dir) {
                $cmp = strcasecmp($a->full_name ?? '', $b->full_name ?? '');
                return $dir === 'ASC' ? $cmp : -$cmp;
            });
        }

        return $merged;
    }

    /**
     * Get attendance records between dates
     * 
     * @param string $from
     * @param string $to
     * @return array
     */
    public function getBetween(string $from, string $to): array
    {
        $q = Attendance::query();
        $this->applyAttendancesSoftDelete($q);

        return $q->whereRaw("DATE(created_at) BETWEEN ? AND ?", [$from, $to])->get();
    }

    /**
     * Get corresponding attendance (in/out pair) for the same employee on the same date
     * Uses case-insensitive comparison for window matching
     * 
     * @param string $employeeId
     * @param string $currentWindow The current window (e.g., "morning_out")
     * @param string $date The date to search (Y-m-d format)
     * @return object|array|null
     */
    public function getCorrespondingAttendance(string $employeeId, string $currentWindow, string $date)
    {
        // Normalize current window to lowercase for comparison
        $normalizedCurrentWindow = strtolower(trim($currentWindow));

        // Determine the corresponding window (case-insensitive)
        $correspondingWindow = null;

        if ($normalizedCurrentWindow === 'morning_out') {
            $correspondingWindow = 'morning_in';
        } elseif ($normalizedCurrentWindow === 'morning_in') {
            $correspondingWindow = 'morning_out';
        } elseif ($normalizedCurrentWindow === 'afternoon_out') {
            $correspondingWindow = 'afternoon_in';
        } elseif ($normalizedCurrentWindow === 'afternoon_in') {
            $correspondingWindow = 'afternoon_out';
        }

        if (!$correspondingWindow) {
            return null;
        }

        // Get the corresponding attendance using case-insensitive window comparison
        $q = Attendance::query()
            ->table("attendances AS a")
            ->select("a.*, COALESCE(aw.label, a.window) AS window_label")
            ->leftJoin("attendance_windows AS aw", "LOWER(TRIM(a.window))", "=", "LOWER(TRIM(aw.label))");
        $this->applyAttendancesSoftDelete($q, 'a');
        $result = $q
            ->where([
                "a.employee_id" => $employeeId,
            ])
            ->whereRaw("LOWER(TRIM(a.window)) = ?", [strtolower($correspondingWindow)])
            ->whereRaw("DATE(a.timestamp) = ? OR DATE(a.created_at) = ?", [$date, $date])
            ->orderBy('a.id', 'DESC')
            ->first();

        return $result;
    }
}
