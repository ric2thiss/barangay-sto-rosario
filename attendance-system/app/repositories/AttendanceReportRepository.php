<?php

/**
 * Read-focused queries for admin attendance reports (roster by activity + summaries).
 */
class AttendanceReportRepository {
    private PDO $pdo;
    private string $profilingDb;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? (new Database())->connect();
        $this->profilingDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, pagination: array<string, int>, activity: array<string, mixed>|null, summary: array<string, int|float>}
     */
    public function getEventRosterPage(
        int $activityId,
        string $search,
        string $sort,
        string $order,
        int $page,
        int $perPage
    ): array {
        $act = Activity::find($activityId);
        if (!$act) {
            return [
                'rows' => [],
                'pagination' => [
                    'currentPage' => 1,
                    'totalPages' => 1,
                    'totalRecords' => 0,
                    'perPage' => $perPage,
                    'startRecord' => 0,
                    'endRecord' => 0,
                ],
                'activity' => null,
                'summary' => ['present' => 0, 'absent' => 0, 'incomplete' => 0, 'total_fines' => 0.0, 'employees_with_fine' => 0],
            ];
        }

        $activity = is_object($act) ? json_decode(json_encode($act), true) : $act;
        $eventDate = (string) ($activity['activity_date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            $eventDate = substr((string) ($activity['created_at'] ?? ''), 0, 10);
        }

        $fineRepo = new EventFineRepository($this->pdo);
        $fineAmount = $fineRepo->getAmountByActivityId($activityId);

        $windowRepo = new AttendanceWindowRepository($this->pdo);
        $windows = $windowRepo->findAll();
        $requiredLabels = [];
        foreach ($windows as $w) {
            $lab = is_object($w) ? (string) $w->label : (string) ($w['label'] ?? '');
            $norm = AttendanceAnalyticsService::normalizeLabel($lab);
            if ($norm !== '') {
                $requiredLabels[$norm] = true;
            }
        }
        $requiredCount = count($requiredLabels);

        $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';

        $countSql = "
            SELECT COUNT(*) AS c
            FROM `{$this->profilingDb}`.`barangay_official` AS bo
            WHERE (CONCAT(bo.first_name, ' ', bo.surname) LIKE :s OR CAST(bo.id AS CHAR) LIKE :s)
        ";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute(['s' => $searchLike]);
        $totalRecords = (int) $stmt->fetchColumn();

        $totalPages = $totalRecords > 0 ? (int) ceil($totalRecords / $perPage) : 1;
        $page = max(1, min($page, max(1, $totalPages)));
        $offset = ($page - 1) * $perPage;

        $orderSql = 'bo.surname ASC, bo.first_name ASC';
        if ($sort === 'name') {
            $orderSql = $order === 'desc' ? 'bo.surname DESC, bo.first_name DESC' : 'bo.surname ASC, bo.first_name ASC';
        } elseif ($sort === 'employee_id') {
            $orderSql = $order === 'desc' ? 'bo.id DESC' : 'bo.id ASC';
        }

        $listSql = "
            SELECT bo.id AS employee_id,
                   TRIM(CONCAT(COALESCE(bo.first_name,''), ' ', COALESCE(bo.surname,''))) AS full_name
            FROM `{$this->profilingDb}`.`barangay_official` AS bo
            WHERE (CONCAT(bo.first_name, ' ', bo.surname) LIKE :s OR CAST(bo.id AS CHAR) LIKE :s)
            ORDER BY {$orderSql}
            LIMIT :lim OFFSET :off
        ";
        $stmt = $this->pdo->prepare($listSql);
        $stmt->bindValue('s', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue('lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $officials = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $del = SchemaColumnCache::attendancesHasDeletedAt() ? 'a.deleted_at IS NULL AND ' : '';
        $logSql = "
            SELECT a.id, a.employee_id, a.timestamp, a.created_at, a.window
            FROM attendances AS a
            WHERE {$del}a.activity_id = :aid
              AND DATE(COALESCE(a.timestamp, a.created_at)) = :d
        ";
        $logStmt = $this->pdo->prepare($logSql);
        $logStmt->execute(['aid' => $activityId, 'd' => $eventDate]);
        $allLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $byEmp = [];
        foreach ($allLogs as $lg) {
            $eid = (string) $lg['employee_id'];
            $byEmp[$eid][] = $lg;
        }

        $graceLate = 15;
        if (class_exists('Settings')) {
            $g = Settings::getValue('attendance_late_grace_minutes', '15');
            $graceLate = ctype_digit((string) $g) ? (int) $g : 15;
        }

        $winByLabel = [];
        foreach ($windows as $w) {
            $lab = is_object($w) ? (string) $w->label : (string) ($w['label'] ?? '');
            $norm = AttendanceAnalyticsService::normalizeLabel($lab);
            if ($norm === '') {
                continue;
            }
            $st = is_object($w) ? (string) $w->start_time : (string) ($w['start_time'] ?? '00:00');
            $en = is_object($w) ? (string) $w->end_time : (string) ($w['end_time'] ?? '00:00');
            $lgRaw = is_object($w)
                ? (property_exists($w, 'late_grace_minutes') ? $w->late_grace_minutes : null)
                : ($w['late_grace_minutes'] ?? null);
            $lateGrace = null;
            if ($lgRaw !== null && $lgRaw !== '') {
                $lateGrace = (int) $lgRaw;
            }
            $winByLabel[$norm] = [
                'start' => strlen($st) === 5 ? $st . ':00' : $st,
                'end' => strlen($en) === 5 ? $en . ':00' : $en,
                'late_grace_minutes' => $lateGrace,
            ];
        }

        $rows = [];
        foreach ($officials as $off) {
            $eid = (string) $off['employee_id'];
            $empLogs = $byEmp[$eid] ?? [];
            $attended = count($empLogs) > 0;

            $loggedLabels = [];
            foreach ($empLogs as $lg) {
                $norm = AttendanceAnalyticsService::normalizeLabel((string) $lg['window']);
                if ($norm !== '') {
                    $loggedLabels[$norm] = true;
                }
            }
            $missingRequired = 0;
            foreach (array_keys($requiredLabels) as $rl) {
                if (empty($loggedLabels[$rl])) {
                    $missingRequired++;
                }
            }

            $timeIn = '—';
            $timeOut = '—';
            $firstInSec = null;
            $lastOutSec = null;
            foreach ($empLogs as $lg) {
                $norm = AttendanceAnalyticsService::normalizeLabel((string) $lg['window']);
                $ts = (string) ($lg['timestamp'] ?? $lg['created_at'] ?? '');
                if ($ts === '') {
                    continue;
                }
                try {
                    $dt = new DateTime($ts, new DateTimeZone('Asia/Manila'));
                    $sec = (int) $dt->format('H') * 3600 + (int) $dt->format('i') * 60 + (int) $dt->format('s');
                } catch (Exception $e) {
                    continue;
                }
                if (preg_match('/_in$/', $norm)) {
                    if ($firstInSec === null || $sec < $firstInSec) {
                        $firstInSec = $sec;
                        $timeIn = $dt->format('h:i:s A');
                    }
                }
                if (preg_match('/_out$/', $norm)) {
                    if ($lastOutSec === null || $sec > $lastOutSec) {
                        $lastOutSec = $sec;
                        $timeOut = $dt->format('h:i:s A');
                    }
                }
            }

            $status = 'Absent';
            $fine = 0.0;
            $remarks = '—';

            if (!$attended) {
                $fine = $fineAmount;
            } elseif ($requiredCount > 0 && $missingRequired > 0) {
                $status = 'Incomplete';
            } else {
                $dayLogs = [];
                foreach ($empLogs as $lg) {
                    $norm = AttendanceAnalyticsService::normalizeLabel((string) $lg['window']);
                    if ($norm === '') {
                        continue;
                    }
                    $lid = (int) ($lg['id'] ?? 0);
                    if (!isset($dayLogs[$norm]) || $lid > (int) ($dayLogs[$norm]['id'] ?? 0)) {
                        $dayLogs[$norm] = [
                            'id' => $lid,
                            'timestamp' => $lg['timestamp'] ?? null,
                            'created_at' => $lg['created_at'] ?? null,
                            'window' => $lg['window'] ?? null,
                        ];
                    }
                }
                $undertimeTol = 5;
                if (class_exists('Settings')) {
                    $t = Settings::getValue('attendance_undertime_tolerance_minutes', '5');
                    $undertimeTol = ctype_digit((string) $t) ? (int) $t : 5;
                }
                $ev = AttendanceAnalyticsService::evaluateWorkedAndFlags($dayLogs, $winByLabel, $graceLate, $undertimeTol);
                if (!empty($ev['late'])) {
                    $status = 'Late';
                } elseif (!empty($ev['undertime'])) {
                    $status = 'Undertime';
                } elseif (!empty($ev['overtime'])) {
                    $status = 'Overtime';
                } else {
                    $status = 'Present';
                }
            }

            $rows[] = [
                'employee_id' => $eid,
                'full_name' => trim((string) $off['full_name']) ?: $eid,
                'date' => $eventDate,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'status' => $status,
                'event_name' => (string) ($activity['name'] ?? ''),
                'fine' => $fine,
                'remarks' => $remarks,
            ];
        }

        // Summary over ALL officials (not only page)
        $allStmt = $this->pdo->prepare("
            SELECT bo.id AS employee_id
            FROM `{$this->profilingDb}`.`barangay_official` AS bo
            WHERE (CONCAT(bo.first_name, ' ', bo.surname) LIKE :s OR CAST(bo.id AS CHAR) LIKE :s)
        ");
        $allStmt->execute(['s' => $searchLike]);
        $allIds = $allStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $present = 0;
        $absent = 0;
        $incomplete = 0;
        $totalFines = 0.0;
        $withFine = 0;

        foreach ($allIds as $eid) {
            $eid = (string) $eid;
            $empLogs = $byEmp[$eid] ?? [];
            $attended = count($empLogs) > 0;
            if (!$attended) {
                $absent++;
                $totalFines += $fineAmount;
                if ($fineAmount > 0) {
                    $withFine++;
                }
                continue;
            }
            $loggedLabels = [];
            foreach ($empLogs as $lg) {
                $norm = AttendanceAnalyticsService::normalizeLabel((string) $lg['window']);
                if ($norm !== '') {
                    $loggedLabels[$norm] = true;
                }
            }
            $missingRequired = 0;
            foreach (array_keys($requiredLabels) as $rl) {
                if (empty($loggedLabels[$rl])) {
                    $missingRequired++;
                }
            }
            if ($requiredCount > 0 && $missingRequired > 0) {
                $incomplete++;
            } else {
                $present++;
            }
        }

        return [
            'rows' => $rows,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords,
                'perPage' => $perPage,
                'startRecord' => $totalRecords === 0 ? 0 : $offset + 1,
                'endRecord' => min($offset + $perPage, $totalRecords),
            ],
            'activity' => [
                'id' => (int) ($activity['id'] ?? $activityId),
                'name' => (string) ($activity['name'] ?? ''),
                'activity_date' => $eventDate,
                'fine_amount' => $fineAmount,
            ],
            'summary' => [
                'present' => $present,
                'absent' => $absent,
                'incomplete' => $incomplete,
                'total_fines' => round($totalFines, 2),
                'employees_with_fine' => $withFine,
            ],
        ];
    }
}
