<?php

class AttendanceReportController {
    private PDO $pdo;
    private AttendanceRepository $attendanceRepo;
    private AttendanceReportRepository $reportRepo;

    public function __construct() {
        $this->pdo = (new Database())->connect();
        $this->attendanceRepo = new AttendanceRepository($this->pdo);
        $this->reportRepo = new AttendanceReportRepository($this->pdo);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageData(
        string $mode,
        int $page,
        int $perPage,
        string $search,
        ?string $fromDate,
        ?string $toDate,
        $activityFilter,
        int $eventActivityId,
        string $sort,
        string $order
    ): array {
        $allowedSort = ['timestamp', 'employee_id', 'full_name'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'timestamp';
        }
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        $activityList = [];
        try {
            $activityList = Activity::query()->orderByRaw('activity_date DESC, id DESC')->limit(200)->get();
        } catch (Throwable $e) {
            error_log('AttendanceReportController activities: ' . $e->getMessage());
            $activityList = [];
        }

        if ($mode === 'event' && $eventActivityId > 0) {
            $rosterSort = $sort === 'employee_id' ? 'employee_id' : ($sort === 'full_name' ? 'name' : 'name');
            $bundle = $this->reportRepo->getEventRosterPage($eventActivityId, $search, $rosterSort, $order, $page, $perPage);

            return [
                'mode' => 'event',
                'activity_list' => $activityList,
                'roster' => $bundle,
                'logs' => null,
            ];
        }

        $data = $this->attendanceRepo->getPaginatedForReports(
            $page,
            $perPage,
            $search,
            $fromDate,
            $toDate,
            $activityFilter,
            $sort,
            $order
        );

        return [
            'mode' => 'logs',
            'activity_list' => $activityList,
            'roster' => null,
            'logs' => $data,
        ];
    }
}
