<?php

require_once __DIR__ . '/BaseRepository.php';

class ActivityRepository extends BaseRepository {
    protected function getModelClass(): string {
        return Activity::class;
    }

    public function existsById(int $id): bool {
        return Activity::query()->where('id', $id)->first() !== null;
    }

    /**
     * LGUMS row already imported (same external_id, source LGUMS).
     */
    public function findImportedLgums(string $externalId): ?object {
        $row = Activity::query()
            ->where(['source' => 'LGUMS', 'external_id' => $externalId])
            ->first();
        return $row ?: null;
    }

    /**
     * Duplicate local activity: same trimmed name and activity_date.
     */
    public function findLocalDuplicate(string $name, string $dateYmd): ?object {
        $trim = trim($name);
        $rows = Activity::query()
            ->where(['source' => 'LOCAL', 'activity_date' => $dateYmd])
            ->get();
        foreach ($rows as $row) {
            $r = is_object($row) ? (array) json_decode(json_encode($row), true) : $row;
            if (isset($r['name']) && strcasecmp(trim((string) $r['name']), $trim) === 0) {
                return is_object($row) ? $row : (object) $r;
            }
        }
        return null;
    }

    /**
     * Import LGUMS schedule rows into activities (skip if external_id already linked).
     *
     * @param array<int, array{id:mixed, event_name:string, event_date:string}> $lgumsRows
     */
    public function importLgumsRows(array $lgumsRows, string $fallbackDateYmd): void {
        $now = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');

        foreach ($lgumsRows as $row) {
            $ext = isset($row['id']) ? (string) $row['id'] : '';
            if ($ext === '') {
                continue;
            }
            if ($this->findImportedLgums($ext)) {
                continue;
            }
            $name = trim((string) ($row['event_name'] ?? ''));
            if ($name === '') {
                $name = 'Event ' . $ext;
            }
            $d = !empty($row['event_date']) ? (string) $row['event_date'] : $fallbackDateYmd;

            Activity::create([
                'name' => $name,
                'description' => null,
                'source' => 'LGUMS',
                'external_id' => $ext,
                'activity_date' => substr($d, 0, 10),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Activities for a given calendar day (dropdown / tagging).
     *
     * @return array<int, object|array>
     */
    public function listForDate(string $dateYmd): array {
        return Activity::query()
            ->where(['activity_date' => $dateYmd])
            ->orderByRaw("FIELD(source, 'LGUMS', 'LOCAL') ASC, name ASC")
            ->get();
    }

    /**
     * Paginated list for admin (optional date range and search on name).
     *
     * @return array{activities: array, pagination: array}
     */
    public function getPaginated(
        int $page,
        int $perPage,
        string $search = '',
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $offset = ($page - 1) * $perPage;

        $base = Activity::query()->table('activities AS a')->select('a.*');
        $countQ = Activity::query()->table('activities AS a')->select('COUNT(*) AS total');

        if ($search !== '') {
            $base->whereRaw('a.name LIKE ?', ['%' . $search . '%']);
            $countQ->whereRaw('a.name LIKE ?', ['%' . $search . '%']);
        }
        if (!empty($fromDate)) {
            $base->whereRaw('a.activity_date >= ?', [$fromDate]);
            $countQ->whereRaw('a.activity_date >= ?', [$fromDate]);
        }
        if (!empty($toDate)) {
            $base->whereRaw('a.activity_date <= ?', [$toDate]);
            $countQ->whereRaw('a.activity_date <= ?', [$toDate]);
        }

        $totalRow = $countQ->first();
        $totalRecords = is_object($totalRow) ? (int) ($totalRow->total ?? 0) : (int) ($totalRow['total'] ?? 0);
        $totalPages = $totalRecords > 0 ? (int) ceil($totalRecords / $perPage) : 1;

        $activities = $base
            ->orderByRaw('a.activity_date DESC, a.id DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();

        return [
            'activities' => $activities,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords,
                'perPage' => $perPage,
                'startRecord' => $offset + 1,
                'endRecord' => min($offset + $perPage, $totalRecords),
            ],
        ];
    }

    public function countAttendancesForActivity(int $activityId): int {
        $q = Attendance::query()
            ->select('COUNT(*) AS c')
            ->where(['activity_id' => $activityId]);
        if (SchemaColumnCache::attendancesHasDeletedAt()) {
            $q->whereRaw('(deleted_at IS NULL)');
        }
        $row = $q->first();
        if (!$row) {
            return 0;
        }
        return is_object($row) ? (int) ($row->c ?? 0) : (int) ($row['c'] ?? 0);
    }
}
