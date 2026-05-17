<?php

require_once __DIR__ . '/BaseRepository.php';

class ActivityRepository extends BaseRepository {
    protected function getModelClass(): string {
        return Activity::class;
    }

    public function existsById(int $id): bool {
        $q = Activity::query()->where('id', $id);
        $this->applySoftDelete($q);
        return $q->first() !== null;
    }

    /**
     * PSS/LGUMS row already imported (same external_id).
     */
    public function findImportedLgums(string $externalId): ?object {
        $q = Activity::query()
            ->whereRaw("source IN ('PSS', 'LGUMS')")
            ->where(['external_id' => $externalId]);
        $this->applySoftDelete($q);
        $row = $q->first();
        return $row ?: null;
    }

    /**
     * Duplicate local activity: same trimmed name and activity_date.
     */
    public function findLocalDuplicate(string $name, string $dateYmd): ?object {
        $trim = trim($name);
        $q = Activity::query()
            ->where(['source' => 'LOCAL', 'activity_date' => $dateYmd]);
        $this->applySoftDelete($q);
        $rows = $q->get();
        foreach ($rows as $row) {
            $r = is_object($row) ? (array) json_decode(json_encode($row), true) : $row;
            if (isset($r['name']) && strcasecmp(trim((string) $r['name']), $trim) === 0) {
                return is_object($row) ? $row : (object) $r;
            }
        }
        return null;
    }

    /**
     * Import PSS schedule rows into activities (skip if external_id already linked or duplicate name).
     *
     * @param array<int, array{id:mixed, event_name:string, event_date:string}> $pssRows
     */
    public function importLgumsRows(array $pssRows, string $fallbackDateYmd): void {
        $now = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');

        // Pre-fetch existing activities for the date to quickly check duplicates
        $existingForDate = [];

        foreach ($pssRows as $row) {
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
            $d = !empty($row['event_date']) ? substr((string) $row['event_date'], 0, 10) : $fallbackDateYmd;

            // Load existing activities for this date if not already loaded
            if (!isset($existingForDate[$d])) {
                $q = Activity::query()->where(['activity_date' => $d]);
                $this->applySoftDelete($q);
                $existingForDate[$d] = $q->get();
            }

            // Check if duplicate name already exists for this date
            $isDuplicate = false;
            foreach ($existingForDate[$d] as $exRow) {
                $r = is_object($exRow) ? (array) json_decode(json_encode($exRow), true) : $exRow;
                if (isset($r['name']) && strcasecmp(trim((string) $r['name']), $name) === 0) {
                    $isDuplicate = true;
                    break;
                }
            }

            if ($isDuplicate) {
                continue;
            }

            $inserted = Activity::create([
                'name' => $name,
                'description' => null,
                'source' => 'PSS',
                'external_id' => $ext,
                'activity_date' => $d,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Add the newly created activity to the cache so subsequent rows don't duplicate it
            $existingForDate[$d][] = [
                'id' => $inserted,
                'name' => $name,
                'activity_date' => $d,
                'source' => 'PSS'
            ];
        }
    }

    /**
     * Activities for a given calendar day (dropdown / tagging).
     *
     * @return array<int, object|array>
     */
    public function listForDate(string $dateYmd): array {
        $q = Activity::query()
            ->where(['activity_date' => $dateYmd]);
        $this->applySoftDelete($q);
        return $q
            ->orderByRaw("FIELD(source, 'PSS', 'LGUMS', 'LOCAL') ASC, name ASC")
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
        $this->applySoftDelete($base, 'a');
        $countQ = Activity::query()->table('activities AS a')->select('COUNT(*) AS total');
        $this->applySoftDelete($countQ, 'a');

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
