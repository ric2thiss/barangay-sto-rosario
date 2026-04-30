<?php

/**
 * Aggregates attendance analytics using master-list windows (attendance_windows).
 * Consumes the same window definitions as AttendanceController::getWindows().
 */
class AttendanceAnalyticsService {
    private PDO $pdo;
    private AttendanceRepository $attendanceRepo;
    private AttendanceWindowRepository $windowRepo;
    private ActivityRepository $activityRepo;
    private string $profilingDb;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->attendanceRepo = new AttendanceRepository($pdo);
        $this->windowRepo = new AttendanceWindowRepository($pdo);
        $this->activityRepo = new ActivityRepository($pdo);
        $this->profilingDb = defined('PROFILING_DB_NAME') ? PROFILING_DB_NAME : 'profiling-system';
    }

    /**
     * @return array{from:string,to:string,filter:string}
     */
    public static function resolveDateRange(string $filter, ?string $fromOverride, ?string $toOverride): array {
        $tz = new DateTimeZone('Asia/Manila');
        $now = new DateTime('now', $tz);

        if ($fromOverride && $toOverride) {
            return ['from' => $fromOverride, 'to' => $toOverride, 'filter' => $filter];
        }

        $f = strtolower(trim($filter));
        switch ($f) {
            case 'daily':
            case 'today':
                $d = $now->format('Y-m-d');
                return ['from' => $d, 'to' => $d, 'filter' => 'daily'];
            case 'weekly':
            case 'week':
                $start = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
                $end = (clone $now)->modify('sunday this week')->setTime(0, 0, 0);
                return ['from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d'), 'filter' => 'weekly'];
            case 'yearly':
            case 'year':
                $start = (clone $now)->modify('first day of january this year')->setTime(0, 0, 0);
                return ['from' => $start->format('Y-m-d'), 'to' => $now->format('Y-m-d'), 'filter' => 'yearly'];
            case 'monthly':
            case 'month':
            default:
                $start = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
                return ['from' => $start->format('Y-m-d'), 'to' => $now->format('Y-m-d'), 'filter' => 'monthly'];
        }
    }

    public static function normalizeLabel(string $value): string {
        $s = strtolower(trim($value));
        $s = str_replace([' ', '-'], '_', $s);
        $s = preg_replace('/_+/', '_', $s);
        return $s ?: '';
    }

    /**
     * @return array<int, array{employee_id:string,full_name:string}>
     */
    public function loadBarangayOfficials(): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT bo.id AS employee_id, bo.first_name, bo.middle_name, bo.surname AS last_name
                FROM `{$this->profilingDb}`.`barangay_official` AS bo
                ORDER BY bo.surname, bo.first_name
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('AttendanceAnalyticsService::loadBarangayOfficials: ' . $e->getMessage());
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $fn = trim((string) ($r['first_name'] ?? ''));
            $mn = trim((string) ($r['middle_name'] ?? ''));
            $ln = trim((string) ($r['last_name'] ?? ''));
            $name = trim($fn . ($mn !== '' ? ' ' . $mn : '') . ($ln !== '' ? ' ' . $ln : ''));
            $out[] = [
                'employee_id' => (string) $r['employee_id'],
                'full_name' => $name !== '' ? $name : (string) $r['employee_id'],
            ];
        }
        return $out;
    }

    /**
     * @return array<int, array{label:string,start:string,end:string,window_id:mixed,display_label:string,late_grace_minutes:?int}>
     */
    public function loadWindowsConfig(): array {
        $raw = $this->windowRepo->findAll();
        $list = [];
        foreach ($raw as $w) {
            $label = is_object($w) ? $w->label : $w['label'];
            $start = is_object($w) ? $w->start_time : $w['start_time'];
            $end = is_object($w) ? $w->end_time : $w['end_time'];
            $wid = is_object($w) ? ($w->window_id ?? $w->id ?? null) : ($w['window_id'] ?? $w['id'] ?? null);
            $lgRaw = is_object($w)
                ? (property_exists($w, 'late_grace_minutes') ? $w->late_grace_minutes : null)
                : ($w['late_grace_minutes'] ?? null);
            $lateGrace = null;
            if ($lgRaw !== null && $lgRaw !== '') {
                $lateGrace = (int) $lgRaw;
            }
            $norm = self::normalizeLabel((string) $label);
            if ($norm === '') {
                continue;
            }
            $list[] = [
                'label' => $norm,
                'start' => self::normalizeTimeString((string) $start),
                'end' => self::normalizeTimeString((string) $end),
                'window_id' => $wid,
                'display_label' => (string) $label,
                'late_grace_minutes' => $lateGrace,
            ];
        }
        usort($list, function ($a, $b) {
            return strcmp($a['start'], $b['start']);
        });
        return $list;
    }

    private static function normalizeTimeString(string $t): string {
        $t = trim($t);
        if (preg_match('/^\d{2}:\d{2}$/', $t)) {
            return $t . ':00';
        }
        return $t;
    }

    private static function timeToSeconds(string $hms): int {
        $parts = array_map('intval', explode(':', $hms));
        $h = $parts[0] ?? 0;
        $m = $parts[1] ?? 0;
        $s = $parts[2] ?? 0;
        return $h * 3600 + $m * 60 + $s;
    }

    private static function logDateYmd($timestamp, $createdAt): string {
        $ts = $timestamp ?: $createdAt;
        if (!$ts) {
            return '';
        }
        $dt = new DateTime((string) $ts, new DateTimeZone('Asia/Manila'));
        return $dt->format('Y-m-d');
    }

    private static function logTimeHms($timestamp, $createdAt): string {
        $ts = $timestamp ?: $createdAt;
        if (!$ts) {
            return '00:00:00';
        }
        $dt = new DateTime((string) $ts, new DateTimeZone('Asia/Manila'));
        return $dt->format('H:i:s');
    }

    private static function logUnix($timestamp, $createdAt): int {
        $ts = $timestamp ?: $createdAt;
        if (!$ts) {
            return 0;
        }
        return (new DateTime((string) $ts, new DateTimeZone('Asia/Manila')))->getTimestamp();
    }

    /**
     * Expected work minutes from window configuration (in/out pairs sharing the same prefix).
     *
     * @param array<string, array{start:string,end:string}> $winByLabel
     */
    private static function expectedWorkMinutes(array $winByLabel): int {
        $prefixes = [];
        foreach (array_keys($winByLabel) as $lab) {
            if (preg_match('/^(.+)_in$/', $lab, $m)) {
                $prefixes[$m[1]] = true;
            }
        }
        $total = 0;
        foreach (array_keys($prefixes) as $pfx) {
            $inL = $pfx . '_in';
            $outL = $pfx . '_out';
            if (!isset($winByLabel[$inL], $winByLabel[$outL])) {
                continue;
            }
            $a = self::timeToSeconds($winByLabel[$inL]['start']);
            $b = self::timeToSeconds($winByLabel[$outL]['end']);
            $diff = $b - $a;
            if ($diff < 0) {
                $diff += 86400;
            }
            $total += (int) round($diff / 60);
        }
        return $total;
    }

    /**
     * @param array<string, array<string, mixed>> $logsByLabel latest log per label for the day
     * @param array<string, array{start:string,end:string,late_grace_minutes?:?int}> $winByLabel
     * @return array{actual:int,late:bool,late_occurrences:int,undertime:bool,overtime:bool,late_minutes:int,undertime_minutes:int,overtime_minutes:int}
     */
    public static function evaluateWorkedAndFlags(
        array $logsByLabel,
        array $winByLabel,
        int $graceLateMinutes,
        int $undertimeToleranceMin
    ): array {
        $lateOccurrences = 0;
        $overtime = false;
        $actual = 0;
        $lateMinutes = 0;
        $overtimeMinutes = 0;

        foreach ($logsByLabel as $lab => $log) {
            $tsec = self::timeToSeconds(self::logTimeHms($log['timestamp'] ?? null, $log['created_at'] ?? null));
            if (!isset($winByLabel[$lab])) {
                continue;
            }
            $w = $winByLabel[$lab];
            $st = self::timeToSeconds($w['start']);
            $en = self::timeToSeconds($w['end']);

            if (preg_match('/_in$/', $lab)) {
                $graceForWindow = $graceLateMinutes;
                if (array_key_exists('late_grace_minutes', $winByLabel[$lab]) && $winByLabel[$lab]['late_grace_minutes'] !== null) {
                    $graceForWindow = (int) $winByLabel[$lab]['late_grace_minutes'];
                }
                $threshold = $st + $graceForWindow * 60;
                if ($tsec > $threshold) {
                    $lateOccurrences++;
                    $lateMinutes = max($lateMinutes, (int) round(($tsec - $threshold) / 60));
                }
            }
            if (preg_match('/_out$/', $lab) && $tsec > $en) {
                $overtime = true;
                $overtimeMinutes += (int) round(($tsec - $en) / 60);
            }
        }

        foreach ($winByLabel as $lab => $_) {
            if (!preg_match('/^(.+)_in$/', $lab, $m)) {
                continue;
            }
            $pfx = $m[1];
            $inL = $pfx . '_in';
            $outL = $pfx . '_out';
            if (!isset($logsByLabel[$inL], $logsByLabel[$outL])) {
                continue;
            }
            $u0 = self::logUnix($logsByLabel[$inL]['timestamp'] ?? null, $logsByLabel[$inL]['created_at'] ?? null);
            $u1 = self::logUnix($logsByLabel[$outL]['timestamp'] ?? null, $logsByLabel[$outL]['created_at'] ?? null);
            if ($u1 > $u0) {
                $actual += (int) round(($u1 - $u0) / 60);
            }
        }

        $expected = self::expectedWorkMinutes($winByLabel);
        $undertime = $expected > 0 && $actual > 0 && $actual < $expected - $undertimeToleranceMin;
        $undertimeMinutes = 0;
        if ($undertime && $expected > 0) {
            $undertimeMinutes = max(0, (int) round($expected - $actual));
        }

        $late = $lateOccurrences > 0;

        return [
            'actual' => $actual,
            'late' => $late,
            'late_occurrences' => $lateOccurrences,
            'undertime' => $undertime,
            'overtime' => $overtime,
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ];
    }

    /**
     * Activities overlapping the report range (for filter dropdown).
     *
     * @return array<int, array{id:int,name:string,activity_date:string}>
     */
    public function loadActivityFilterOptions(string $fromYmd, string $toYmd, int $max = 250): array {
        $data = $this->activityRepo->getPaginated(1, max(1, min(500, $max)), '', $fromYmd, $toYmd);
        $out = [['id' => 0, 'name' => 'Uncategorized (no activity tag)', 'activity_date' => '']];
        foreach ($data['activities'] ?? [] as $row) {
            $r = is_object($row) ? json_decode(json_encode($row), true) : $row;
            $id = (int) ($r['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'name' => (string) ($r['name'] ?? ''),
                'activity_date' => (string) ($r['activity_date'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $filters employee_id?, activity_id? (table status/pagination ignored)
     * @return array{rowsAll: array<int, array<string, mixed>>, windows: array<int, array<string, mixed>>, officials: array<int, array{employee_id:string,full_name:string}>, activityFilterParam: int|null}
     */
    private function generateAnalyticsRowsBundle(array $range, array $filters): array {
        $from = $range['from'];
        $to = $range['to'];

        $windows = $this->loadWindowsConfig();
        $requiredLabels = array_column($windows, 'label');
        $winByLabel = [];
        foreach ($windows as $w) {
            $winByLabel[$w['label']] = [
                'start' => $w['start'],
                'end' => $w['end'],
                'late_grace_minutes' => $w['late_grace_minutes'] ?? null,
            ];
        }

        $graceLate = 15;
        if (class_exists('Settings')) {
            $g = Settings::getValue('attendance_late_grace_minutes', '15');
            $graceLate = ctype_digit((string) $g) ? (int) $g : 15;
        }

        $undertimeTol = 5;
        if (class_exists('Settings')) {
            $t = Settings::getValue('attendance_undertime_tolerance_minutes', '5');
            $undertimeTol = ctype_digit((string) $t) ? (int) $t : 5;
        }

        $officials = $this->loadBarangayOfficials();
        $empFilter = isset($filters['employee_id']) ? trim((string) $filters['employee_id']) : '';
        if ($empFilter !== '') {
            $officials = array_values(array_filter($officials, function ($o) use ($empFilter) {
                return (string) $o['employee_id'] === $empFilter;
            }));
        }

        $activityFilterParam = null;
        if (isset($filters['activity_id'])) {
            $rawAct = trim((string) $filters['activity_id']);
            if ($rawAct !== '' && strtolower($rawAct) !== 'all') {
                if ($rawAct === '0') {
                    $activityFilterParam = 0;
                } elseif (ctype_digit($rawAct)) {
                    $activityFilterParam = (int) $rawAct;
                }
            }
        }

        $rawLogs = $this->attendanceRepo->getBetweenDatesByTimestamp($from, $to, $activityFilterParam);
        $rawLogsGlobal = $this->attendanceRepo->getBetweenDatesByTimestamp($from, $to, null);

        /** @var array<string, true> */
        $globalWinSet = [];
        foreach ($rawLogsGlobal as $row) {
            $eidG = (string) (is_object($row) ? $row->employee_id : $row['employee_id']);
            $wlG = is_object($row) ? ($row->window_label ?? $row->window) : ($row['window_label'] ?? $row['window']);
            $labG = self::normalizeLabel((string) $wlG);
            if ($labG === '') {
                continue;
            }
            $dG = self::logDateYmd(
                is_object($row) ? $row->timestamp : $row['timestamp'],
                is_object($row) ? $row->created_at : $row['created_at']
            );
            if ($dG === '' || $dG < $from || $dG > $to) {
                continue;
            }
            $globalWinSet[$eidG . '|' . $dG . '|' . $labG] = true;
        }

        /** @var array<string, array<string, array<string, array<string, mixed>>>> $byEmpDate */
        $byEmpDate = [];
        foreach ($rawLogs as $row) {
            $eid = (string) (is_object($row) ? $row->employee_id : $row['employee_id']);
            $wl = is_object($row) ? ($row->window_label ?? $row->window) : ($row['window_label'] ?? $row['window']);
            $lab = self::normalizeLabel((string) $wl);
            if ($lab === '') {
                continue;
            }
            $d = self::logDateYmd(
                is_object($row) ? $row->timestamp : $row['timestamp'],
                is_object($row) ? $row->created_at : $row['created_at']
            );
            if ($d === '' || $d < $from || $d > $to) {
                continue;
            }
            if (!isset($byEmpDate[$eid][$d])) {
                $byEmpDate[$eid][$d] = [];
            }
            $id = (int) (is_object($row) ? $row->id : $row['id']);
            if (!isset($byEmpDate[$eid][$d][$lab]) || (int) (is_object($byEmpDate[$eid][$d][$lab]) ? $byEmpDate[$eid][$d][$lab]->id : $byEmpDate[$eid][$d][$lab]['id']) < $id) {
                if (is_object($row)) {
                    $byEmpDate[$eid][$d][$lab] = [
                        'id' => $row->id,
                        'timestamp' => $row->timestamp,
                        'created_at' => $row->created_at,
                        'window' => $row->window,
                        'activity_id' => $row->activity_id ?? null,
                    ];
                } else {
                    $r = $row;
                    $byEmpDate[$eid][$d][$lab] = [
                        'id' => $r['id'] ?? null,
                        'timestamp' => $r['timestamp'] ?? null,
                        'created_at' => $r['created_at'] ?? null,
                        'window' => $r['window'] ?? null,
                        'activity_id' => $r['activity_id'] ?? null,
                    ];
                }
            }
        }

        $periodDays = self::enumerateDays($from, $to);
        $rowsAll = [];

        $lgumsByDate = [];
        try {
            if (class_exists('LgumsScheduleRepository')) {
                $lgumsRepo = new LgumsScheduleRepository($this->pdo);
                foreach ($periodDays as $d) {
                    $lgumsByDate[$d] = $lgumsRepo->fetchEventsForDate($d);
                }
            }
        } catch (Throwable $e) {
            $lgumsByDate = [];
        }

        foreach ($officials as $off) {
            $eid = $off['employee_id'];
            $name = $off['full_name'];

            foreach ($periodDays as $day) {
                $dayLogs = $byEmpDate[$eid][$day] ?? [];
                $missing = [];
                foreach ($requiredLabels as $req) {
                    if (!isset($dayLogs[$req])) {
                        $missing[] = $req;
                    }
                }

                $windowsDisplay = array_map(function ($w) {
                    return $w['display_label'] . ' (' . substr($w['start'], 0, 5) . '–' . substr($w['end'], 0, 5) . ')';
                }, $windows);

                $loggedParts = [];
                foreach ($requiredLabels as $req) {
                    if (isset($dayLogs[$req])) {
                        $lg = $dayLogs[$req];
                        $loggedParts[] = $req . ': ' . self::logTimeHms($lg['timestamp'] ?? null, $lg['created_at'] ?? null);
                    } else {
                        $loggedParts[] = $req . ': —';
                    }
                }

                $complete = count($missing) === 0;
                $absentDay = count($dayLogs) === 0;

                $late = false;
                $undertime = false;
                $overtime = false;
                $lateMin = 0;
                $undMin = 0;
                $otMin = 0;
                $lateOcc = 0;
                if ($complete) {
                    $ev = self::evaluateWorkedAndFlags($dayLogs, $winByLabel, $graceLate, $undertimeTol);
                    $late = $ev['late'];
                    $undertime = $ev['undertime'];
                    $overtime = $ev['overtime'];
                    $lateOcc = (int) ($ev['late_occurrences'] ?? 0);
                    $lateMin = (int) ($ev['late_minutes'] ?? 0);
                    $undMin = (int) ($ev['undertime_minutes'] ?? 0);
                    $otMin = (int) ($ev['overtime_minutes'] ?? 0);
                }

                $statuses = [];
                if ($absentDay) {
                    $statuses[] = 'Absent';
                } elseif (!$complete) {
                    $statuses[] = 'Incomplete';
                } else {
                    $statuses[] = 'Complete';
                }
                if ($late) {
                    $statuses[] = 'Late';
                }
                if ($undertime) {
                    $statuses[] = 'Undertime';
                }
                if ($overtime) {
                    $statuses[] = 'Overtime';
                }

                $shiftRef = $day;
                $events = $lgumsByDate[$day] ?? [];
                if (!empty($events)) {
                    $names = array_map(function ($evRow) {
                        return $evRow['event_name'] ?? '';
                    }, $events);
                    $shiftRef = $day . ' · ' . implode(', ', array_filter($names));
                }

                $row = [
                    'employee_id' => $eid,
                    'employee_name' => $name,
                    'date' => $day,
                    'shift_reference' => $shiftRef,
                    'windows_summary' => $windowsDisplay,
                    'logged_summary' => $loggedParts,
                    'missing_windows' => $missing,
                    'logs_by_window' => [],
                    'statuses' => array_values(array_unique($statuses)),
                    'is_complete' => $complete,
                    'is_absent_day' => $absentDay,
                    'late' => $late,
                    'late_occurrences' => $lateOcc,
                    'undertime' => $undertime,
                    'overtime' => $overtime,
                    'late_minutes' => $lateMin,
                    'undertime_minutes' => $undMin,
                    'overtime_minutes' => $otMin,
                    '_sort' => self::rowSortKey($absentDay, $complete, $late, $undertime, $overtime),
                ];

                foreach ($windows as $w) {
                    $lab = $w['label'];
                    $lg = $dayLogs[$lab] ?? null;
                    $gKey = $eid . '|' . $day . '|' . $lab;
                    $globalHasLog = isset($globalWinSet[$gKey]);
                    $row['logs_by_window'][] = [
                        'window_id' => $w['window_id'],
                        'label' => $lab,
                        'display_label' => $w['display_label'],
                        'start' => $w['start'],
                        'end' => $w['end'],
                        'logged_at' => $lg ? self::logTimeHms($lg['timestamp'] ?? null, $lg['created_at'] ?? null) : null,
                        'logged_datetime' => $lg ? (string) ($lg['timestamp'] ?? $lg['created_at'] ?? '') : null,
                        'attendance_id' => $lg ? (int) ($lg['id'] ?? 0) : null,
                        'has_other_activity_log' => $globalHasLog && $lg === null,
                        'can_fill' => !$globalHasLog,
                    ];
                }

                $rowsAll[] = $row;
            }
        }

        return [
            'rowsAll' => $rowsAll,
            'windows' => $windows,
            'officials' => $officials,
            'activityFilterParam' => $activityFilterParam,
        ];
    }

    /**
     * @param array<string, mixed> $filters employee_id?, status?, activity_id?, page?, per_page?
     * @return array<string, mixed>
     */
    public function buildReport(array $range, array $filters = []): array {
        $from = $range['from'];
        $to = $range['to'];

        $bundle = $this->generateAnalyticsRowsBundle($range, $filters);
        $rowsAll = $bundle['rowsAll'];
        $windows = $bundle['windows'];
        $officials = $bundle['officials'];
        $activityFilterParam = $bundle['activityFilterParam'];

        $agg = self::aggregateFromRows($rowsAll, $officials, $from, $to);
        $summary = $agg['summary'];
        $empPerfect = $agg['emp_perfect'];
        $lateF = $agg['late_count_by_emp'];
        $lateOccF = $agg['late_occurrences_by_emp'];
        $undF = $agg['undertime_count_by_emp'];
        $incF = $agg['incomplete_count_by_emp'];
        $absF = $agg['absent_days_by_emp'];
        $otF = $agg['overtime_count_by_emp'];
        $completeF = $agg['complete_days_by_emp'];
        $periodDayCount = count(self::enumerateDays($from, $to));

        $statusFilter = isset($filters['status']) ? trim(strtolower((string) $filters['status'])) : '';
        $rows = $rowsAll;
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $sf = str_replace(' ', '_', $statusFilter);
            $rows = array_values(array_filter($rowsAll, function ($row) use ($sf) {
                foreach ($row['statuses'] as $st) {
                    if (strtolower(str_replace(' ', '_', $st)) === $sf) {
                        return true;
                    }
                }
                return false;
            }));
        }

        $employeeFilterOptions = [];
        foreach ($rowsAll as $r) {
            $eid = $r['employee_id'];
            if (!isset($employeeFilterOptions[$eid])) {
                $employeeFilterOptions[$eid] = [
                    'employee_id' => $eid,
                    'full_name' => $r['employee_name'],
                ];
            }
        }
        usort($employeeFilterOptions, function ($a, $b) {
            return strcmp($a['full_name'], $b['full_name']);
        });
        $employeeFilterOptions = array_values($employeeFilterOptions);

        usort($rows, function ($a, $b) {
            if ($a['_sort'] !== $b['_sort']) {
                return $a['_sort'] <=> $b['_sort'];
            }
            if ($a['date'] !== $b['date']) {
                return strcmp($a['date'], $b['date']);
            }
            return strcmp($a['employee_name'], $b['employee_name']);
        });

        $page = isset($filters['page']) ? max(1, (int) $filters['page']) : 1;
        $perPage = isset($filters['per_page']) ? max(1, min(100, (int) $filters['per_page'])) : 25;
        $totalTableRows = count($rows);
        $totalPages = $totalTableRows > 0 ? (int) ceil($totalTableRows / $perPage) : 1;
        $offset = ($page - 1) * $perPage;
        if ($offset >= $totalTableRows && $totalTableRows > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }
        $pageRows = $totalTableRows === 0 ? [] : array_slice($rows, $offset, $perPage);

        foreach ($pageRows as &$r) {
            unset($r['_sort'], $r['late_minutes'], $r['undertime_minutes'], $r['overtime_minutes'], $r['late_occurrences']);
        }
        unset($r);

        $insights = $this->buildInsights(
            $officials,
            $empPerfect,
            $lateF,
            $lateOccF,
            $undF,
            $incF,
            $absF,
            $otF,
            $completeF,
            $periodDayCount,
            $range['filter']
        );

        $activityFilterOptions = $this->loadActivityFilterOptions($from, $to);
        $charts = $this->buildChartsPayload($rowsAll, $range, $summary, $empPerfect, $officials);

        return [
            'success' => true,
            'range' => $range,
            'windows' => $windows,
            'summary' => $summary,
            'rows' => $pageRows,
            'insights' => $insights,
            'charts' => $charts,
            'activity_filter_options' => $activityFilterOptions,
            'employee_filter_options' => $employeeFilterOptions,
            'filters_applied' => [
                'activity_id' => $activityFilterParam === null ? 'all' : (string) $activityFilterParam,
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_rows' => $totalTableRows,
                'total_pages' => max(1, $totalPages),
                'from_row' => $totalTableRows === 0 ? 0 : $offset + 1,
                'to_row' => min($offset + $perPage, $totalTableRows),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rowsAll
     * @param array<int, array{employee_id:string,full_name:string}> $officials
     * @return array{
     *   summary: array<string, int>,
     *   emp_perfect: array<string, string>,
     *   late_count_by_emp: array<string, int>,
     *   late_occurrences_by_emp: array<string, int>,
     *   undertime_count_by_emp: array<string, int>,
     *   incomplete_count_by_emp: array<string, int>,
     *   absent_days_by_emp: array<string, int>,
     *   overtime_count_by_emp: array<string, int>,
     *   complete_days_by_emp: array<string, int>
     * }
     */
    private static function aggregateFromRows(array $rowsAll, array $officials, string $from, string $to): array {
        $empPresent = [];
        $empLate = [];
        $empUndertime = [];
        $empAbsent = [];
        $empOvertime = [];

        $lateCountByEmp = [];
        $lateOccurrencesByEmp = [];
        $undertimeCountByEmp = [];
        $incompleteCountByEmp = [];
        $absentDaysByEmp = [];
        $overtimeCountByEmp = [];
        $completeDaysByEmp = [];

        foreach ($rowsAll as $row) {
            $eid = $row['employee_id'];
            if (empty($row['is_absent_day'])) {
                $empPresent[$eid] = true;
            }
            if (!empty($row['late'])) {
                $empLate[$eid] = true;
                $lateCountByEmp[$eid] = ($lateCountByEmp[$eid] ?? 0) + 1;
            }
            $occ = (int) ($row['late_occurrences'] ?? 0);
            if ($occ > 0) {
                $lateOccurrencesByEmp[$eid] = ($lateOccurrencesByEmp[$eid] ?? 0) + $occ;
            }
            if (!empty($row['undertime'])) {
                $empUndertime[$eid] = true;
                $undertimeCountByEmp[$eid] = ($undertimeCountByEmp[$eid] ?? 0) + 1;
            }
            if (!empty($row['overtime'])) {
                $empOvertime[$eid] = true;
                $overtimeCountByEmp[$eid] = ($overtimeCountByEmp[$eid] ?? 0) + 1;
            }
            if (empty($row['is_complete']) && empty($row['is_absent_day'])) {
                $incompleteCountByEmp[$eid] = ($incompleteCountByEmp[$eid] ?? 0) + 1;
            }
            if (!empty($row['is_absent_day'])) {
                $empAbsent[$eid] = true;
                $absentDaysByEmp[$eid] = ($absentDaysByEmp[$eid] ?? 0) + 1;
            }
            if (!empty($row['is_complete']) && empty($row['is_absent_day'])) {
                $completeDaysByEmp[$eid] = ($completeDaysByEmp[$eid] ?? 0) + 1;
            }
        }

        $periodDays = self::enumerateDays($from, $to);
        $byEmpDate = [];
        foreach ($rowsAll as $row) {
            $byEmpDate[$row['employee_id']][$row['date']] = $row;
        }

        $empPerfect = [];
        foreach ($officials as $o) {
            $eid = $o['employee_id'];
            $allPerfect = true;
            foreach ($periodDays as $d) {
                $r = $byEmpDate[$eid][$d] ?? null;
                if (!$r) {
                    $allPerfect = false;
                    break;
                }
                $ok = !empty($r['is_complete']) && empty($r['is_absent_day'])
                    && empty($r['late']) && empty($r['undertime']);
                if (!$ok) {
                    $allPerfect = false;
                    break;
                }
            }
            if ($allPerfect && count($periodDays) > 0) {
                $empPerfect[$eid] = $o['full_name'];
            }
        }

        return [
            'summary' => [
                'total_employees_present' => count($empPresent),
                'late_employees' => count($empLate),
                'undertime_employees' => count($empUndertime),
                'absent_employees' => count($empAbsent),
                'overtime_employees' => count($empOvertime),
                'perfect_attendance_employees' => count($empPerfect),
            ],
            'emp_perfect' => $empPerfect,
            'late_count_by_emp' => $lateCountByEmp,
            'late_occurrences_by_emp' => $lateOccurrencesByEmp,
            'undertime_count_by_emp' => $undertimeCountByEmp,
            'incomplete_count_by_emp' => $incompleteCountByEmp,
            'absent_days_by_emp' => $absentDaysByEmp,
            'overtime_count_by_emp' => $overtimeCountByEmp,
            'complete_days_by_emp' => $completeDaysByEmp,
        ];
    }

    private static function rowSortKey(bool $absent, bool $complete, bool $late, bool $undertime, bool $overtime): int {
        if (!$complete && !$absent) {
            return 0;
        }
        if ($absent) {
            return 1;
        }
        if ($late || $undertime) {
            return 2;
        }
        if ($overtime) {
            return 3;
        }
        return 4;
    }

    /**
     * @param array<int, array{employee_id:string,full_name:string}> $officials
     */
    private function buildInsights(
        array $officials,
        array $empPerfect,
        array $lateDaysByEmp,
        array $lateOccurrencesByEmp,
        array $undertimeCountByEmp,
        array $incompleteCountByEmp,
        array $absentDaysByEmp,
        array $overtimeCountByEmp,
        array $completeDaysByEmp,
        int $periodDayCount,
        string $filterLabel
    ): array {
        $idToName = [];
        foreach ($officials as $o) {
            $idToName[$o['employee_id']] = $o['full_name'];
        }

        $perfectList = [];
        foreach ($empPerfect as $eid => $n) {
            $perfectList[] = ['employee_id' => $eid, 'full_name' => $n];
        }
        usort($perfectList, function ($a, $b) {
            return strcmp($a['full_name'], $b['full_name']);
        });

        $employeeOfPeriod = $perfectList[0] ?? null;
        $map = ['daily' => 'Day', 'weekly' => 'Week', 'monthly' => 'Month', 'yearly' => 'Year'];
        $label = $map[strtolower($filterLabel)] ?? 'Period';

        arsort($lateDaysByEmp);
        arsort($undertimeCountByEmp);
        arsort($incompleteCountByEmp);
        arsort($absentDaysByEmp);
        arsort($overtimeCountByEmp);

        $mapTop = function (array $counts, int $limit = 5) use ($idToName): array {
            $out = [];
            $i = 0;
            foreach ($counts as $eid => $c) {
                $out[] = [
                    'employee_id' => (string) $eid,
                    'full_name' => $idToName[(string) $eid] ?? (string) $eid,
                    'count' => (int) $c,
                ];
                if (++$i >= $limit) {
                    break;
                }
            }
            return $out;
        };

        $T = max(0, $periodDayCount);
        $bestAttendance = [];
        foreach ($officials as $o) {
            $eid = $o['employee_id'];
            $A = (int) ($absentDaysByEmp[$eid] ?? 0);
            $L = (int) ($lateOccurrencesByEmp[$eid] ?? 0);
            $U = (int) ($undertimeCountByEmp[$eid] ?? 0);
            $I = (int) ($incompleteCountByEmp[$eid] ?? 0);
            $C = (int) ($completeDaysByEmp[$eid] ?? 0);
            $perfectTier = $A === 0 && $L === 0 && $U === 0 && $I === 0;
            $score = $T > 0 ? 1.0 - ($A + $L + $U + $I) / $T : 0.0;
            $bestAttendance[] = [
                'employee_id' => $eid,
                'full_name' => $o['full_name'],
                'attendance_score' => round($score, 4),
                'perfect_attendance' => $perfectTier,
                'metrics' => [
                    'absent_days' => $A,
                    'late_occurrences' => $L,
                    'undertime_days' => $U,
                    'incomplete_days' => $I,
                    'complete_days' => $C,
                    'period_days' => $T,
                ],
            ];
        }
        usort($bestAttendance, function ($a, $b) {
            $pa = !empty($a['perfect_attendance']);
            $pb = !empty($b['perfect_attendance']);
            if ($pa !== $pb) {
                return $pb <=> $pa;
            }
            if ($pa) {
                return strcmp($a['full_name'], $b['full_name']);
            }
            $sa = (float) ($a['attendance_score'] ?? 0);
            $sb = (float) ($b['attendance_score'] ?? 0);
            if ($sa !== $sb) {
                return $sb <=> $sa;
            }
            $ca = (int) ($a['metrics']['complete_days'] ?? 0);
            $cb = (int) ($b['metrics']['complete_days'] ?? 0);
            if ($ca !== $cb) {
                return $cb <=> $ca;
            }
            $ia = (int) ($a['metrics']['incomplete_days'] ?? 0);
            $ib = (int) ($b['metrics']['incomplete_days'] ?? 0);
            if ($ia !== $ib) {
                return $ia <=> $ib;
            }
            return strcmp($a['full_name'], $b['full_name']);
        });
        $bestAttendance = array_slice($bestAttendance, 0, 8);

        return [
            'employee_of_period' => $employeeOfPeriod ? array_merge($employeeOfPeriod, ['badge' => 'Employee of the ' . $label]) : null,
            'perfect_attendance' => $perfectList,
            'most_late' => $mapTop($lateDaysByEmp),
            'most_undertime' => $mapTop($undertimeCountByEmp),
            'most_incomplete' => $mapTop($incompleteCountByEmp),
            'most_absences' => $mapTop($absentDaysByEmp),
            'most_overtime' => $mapTop($overtimeCountByEmp),
            'best_attendance_rank' => $bestAttendance,
        ];
    }

    /**
     * Chart payloads for the analytics dashboard (read-only aggregates).
     *
     * @param array<int, array<string, mixed>> $rowsAll
     * @param array<string, string> $empPerfect
     * @param array<int, array{employee_id:string,full_name:string}> $officials
     * @return array<string, mixed>
     */
    public function buildChartsPayload(
        array $rowsAll,
        array $range,
        array $summary,
        array $empPerfect,
        array $officials
    ): array {
        $from = $range['from'];
        $to = $range['to'];
        $periodDayCount = count(self::enumerateDays($from, $to));

        $byEmp = [];
        foreach ($rowsAll as $row) {
            $eid = $row['employee_id'];
            if (!isset($byEmp[$eid])) {
                $byEmp[$eid] = ['name' => $row['employee_name'], 'clean' => 0, 'n' => 0];
            }
            $byEmp[$eid]['n']++;
            $clean = !empty($row['is_complete']) && empty($row['is_absent_day'])
                && empty($row['late']) && empty($row['undertime']);
            if ($clean) {
                $byEmp[$eid]['clean']++;
            }
        }
        $compliance = [];
        foreach ($byEmp as $agg) {
            $n = $agg['n'];
            $pct = $n > 0 ? (int) round(100 * $agg['clean'] / $n) : 0;
            $compliance[] = ['label' => $agg['name'], 'value' => $pct];
        }
        usort($compliance, function ($a, $b) {
            if ($a['value'] !== $b['value']) {
                return $b['value'] <=> $a['value'];
            }
            return strcmp($a['label'], $b['label']);
        });
        $compliance = array_slice($compliance, 0, 14);

        $lateDays = 0;
        $undDays = 0;
        $otDays = 0;
        foreach ($rowsAll as $row) {
            if (!empty($row['late'])) {
                $lateDays++;
            }
            if (!empty($row['undertime'])) {
                $undDays++;
            }
            if (!empty($row['overtime'])) {
                $otDays++;
            }
        }

        $empIssues = [];
        foreach ($rowsAll as $r) {
            $hit = !empty($r['is_absent_day']) || empty($r['is_complete'])
                || !empty($r['late']) || !empty($r['undertime']);
            if ($hit) {
                $empIssues[$r['employee_id']] = true;
            }
        }
        $needsAttention = count($empIssues);
        $perfectCount = count($empPerfect);

        $positionPie = $this->loadPositionDistributionForOfficials($officials);

        return [
            'compliance_by_employee' => [
                'labels' => array_column($compliance, 'label'),
                'values' => array_column($compliance, 'value'),
            ],
            'issue_day_counts' => [
                'labels' => ['Late days', 'Undertime days', 'Overtime days'],
                'values' => [$lateDays, $undDays, $otDays],
            ],
            'perfect_vs_attention' => [
                'labels' => ['Perfect attendance', 'Needs attention'],
                'values' => [$perfectCount, $needsAttention],
            ],
            'demographic_position' => $positionPie,
            'meta' => [
                'period_days' => $periodDayCount,
                'summary_snapshot' => [
                    'perfect_attendance_employees' => (int) ($summary['perfect_attendance_employees'] ?? 0),
                    'total_employees_present' => (int) ($summary['total_employees_present'] ?? 0),
                ],
            ],
        ];
    }

    /**
     * @param array<int, array{employee_id:string,full_name:string}> $officials
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function loadPositionDistributionForOfficials(array $officials): array {
        $ids = [];
        foreach ($officials as $o) {
            $id = (string) ($o['employee_id'] ?? '');
            if ($id !== '' && ctype_digit($id)) {
                $ids[] = (int) $id;
            }
        }
        if ($ids === []) {
            return ['labels' => [], 'values' => []];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(NULLIF(TRIM(bo.position), ''), 'Unspecified') AS lbl, COUNT(*) AS cnt
                FROM `{$this->profilingDb}`.`barangay_official` AS bo
                WHERE bo.id IN ($placeholders)
                GROUP BY lbl
                ORDER BY cnt DESC
                LIMIT 14
            ");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log('loadPositionDistributionForOfficials: ' . $e->getMessage());
            return ['labels' => [], 'values' => []];
        }
        $labels = [];
        $values = [];
        foreach ($rows as $r) {
            $labels[] = (string) ($r['lbl'] ?? '');
            $values[] = (int) ($r['cnt'] ?? 0);
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Paginated row-level detail for “Needs attention” verification (read-only).
     *
     * @param array<string, mixed> $filters employee_id?, activity_id?
     * @return array<string, mixed>
     */
    public function buildAttentionDetail(array $range, array $filters, string $detailType, int $page, int $perPage): array {
        $allowed = ['late', 'undertime', 'overtime', 'incomplete', 'absences'];
        if (!in_array($detailType, $allowed, true)) {
            return ['success' => false, 'error' => 'Invalid detail type'];
        }
        $bundle = $this->generateAnalyticsRowsBundle($range, $filters);
        $rowsAll = $bundle['rowsAll'];
        $detailRows = [];
        foreach ($rowsAll as $row) {
            $rec = null;
            switch ($detailType) {
                case 'late':
                    if (!empty($row['late'])) {
                        $rec = [
                            'employee_id' => $row['employee_id'],
                            'employee_name' => $row['employee_name'],
                            'date' => $row['date'],
                            'late_minutes' => (int) ($row['late_minutes'] ?? 0),
                        ];
                    }
                    break;
                case 'undertime':
                    if (!empty($row['undertime'])) {
                        $rec = [
                            'employee_id' => $row['employee_id'],
                            'employee_name' => $row['employee_name'],
                            'date' => $row['date'],
                            'shortfall_minutes' => (int) ($row['undertime_minutes'] ?? 0),
                        ];
                    }
                    break;
                case 'overtime':
                    if (!empty($row['overtime'])) {
                        $rec = [
                            'employee_id' => $row['employee_id'],
                            'employee_name' => $row['employee_name'],
                            'date' => $row['date'],
                            'overtime_minutes' => (int) ($row['overtime_minutes'] ?? 0),
                        ];
                    }
                    break;
                case 'incomplete':
                    if (empty($row['is_complete']) && empty($row['is_absent_day'])) {
                        $rec = [
                            'employee_id' => $row['employee_id'],
                            'employee_name' => $row['employee_name'],
                            'date' => $row['date'],
                            'missing_windows' => implode(', ', $row['missing_windows'] ?? []),
                        ];
                    }
                    break;
                case 'absences':
                    if (!empty($row['is_absent_day'])) {
                        $rec = [
                            'employee_id' => $row['employee_id'],
                            'employee_name' => $row['employee_name'],
                            'date' => $row['date'],
                        ];
                    }
                    break;
            }
            if ($rec !== null) {
                $detailRows[] = $rec;
            }
        }
        usort($detailRows, function ($a, $b) {
            $c = strcmp($b['date'], $a['date']);
            if ($c !== 0) {
                return $c;
            }
            return strcmp($a['employee_name'], $b['employee_name']);
        });
        $total = count($detailRows);
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;
        if ($offset >= $total && $total > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }
        $slice = $total === 0 ? [] : array_slice($detailRows, $offset, $perPage);

        return [
            'success' => true,
            'detail_type' => $detailType,
            'range' => $range,
            'rows' => $slice,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_rows' => $total,
                'total_pages' => max(1, $totalPages),
                'from_row' => $total === 0 ? 0 : $offset + 1,
                'to_row' => min($offset + $perPage, $total),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function enumerateDays(string $from, string $to): array {
        $out = [];
        $tz = new DateTimeZone('Asia/Manila');
        $cur = new DateTime($from . ' 00:00:00', $tz);
        $end = new DateTime($to . ' 00:00:00', $tz);
        while ($cur <= $end) {
            $out[] = $cur->format('Y-m-d');
            $cur->modify('+1 day');
        }
        return $out;
    }
}
