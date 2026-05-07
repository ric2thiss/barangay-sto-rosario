<?php

class ActivityController {
    protected $db;
    protected $activityRepository;
    protected $lgumsRepository;
    protected $settingsRepository;

    public function __construct() {
        $this->db = (new Database())->connect();
        $this->activityRepository = new ActivityRepository($this->db);
        $this->lgumsRepository = new LgumsScheduleRepository($this->db);
        $this->settingsRepository = new SettingsRepository($this->db);
    }

    /**
     * Sync LGUMS rows for the date, then return dropdown options + active selection hint.
     */
    public function optionsForAttendance(string $dateYmd): array {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
            return ['success' => false, 'error' => 'Invalid date'];
        }

        $lgumsRows = $this->lgumsRepository->fetchEventsForDate($dateYmd);
        $this->activityRepository->importLgumsRows($lgumsRows, $dateYmd);

        $list = $this->activityRepository->listForDate($dateYmd);
        $options = [];
        foreach ($list as $row) {
            $r = is_object($row) ? json_decode(json_encode($row), true) : $row;
            $options[] = [
                'id' => (int) ($r['id'] ?? 0),
                'name' => (string) ($r['name'] ?? ''),
                'source' => (string) ($r['source'] ?? 'LOCAL'),
                'activity_date' => (string) ($r['activity_date'] ?? ''),
            ];
        }

        $currentRaw = Settings::getValue('active_attendance_activity_id', '');
        $currentId = ($currentRaw !== '' && ctype_digit((string) $currentRaw)) ? (int) $currentRaw : null;

        $lgumsIdsToday = [];
        foreach ($options as $o) {
            if (($o['source'] ?? '') === 'LGUMS') {
                $lgumsIdsToday[] = $o['id'];
            }
        }

        $suggestedId = null;
        if ($currentId === null && count($lgumsIdsToday) === 1) {
            $suggestedId = $lgumsIdsToday[0];
        }

        return [
            'success' => true,
            'date' => $dateYmd,
            'options' => $options,
            'current_active_activity_id' => $currentId,
            'suggested_activity_id' => $suggestedId,
        ];
    }

    public function setActiveActivity(?int $activityId, ?int $updatedBy = null): array {
        if ($activityId !== null && $activityId > 0) {
            if (!$this->activityRepository->existsById($activityId)) {
                return ['success' => false, 'error' => 'Invalid activity'];
            }
            $this->settingsRepository->updateSetting('active_attendance_activity_id', (string) $activityId, $updatedBy);
        } else {
            $this->settingsRepository->updateSetting('active_attendance_activity_id', '', $updatedBy);
        }

        return ['success' => true];
    }

    public function listPaginated(int $page, int $perPage, string $search = '', ?string $from = null, ?string $to = null): array {
        $data = $this->activityRepository->getPaginated($page, $perPage, $search, $from, $to);
        return [
            'success' => true,
            'activities' => $data['activities'],
            'pagination' => $data['pagination'],
        ];
    }

    public function createLocal(array $data, ?int $updatedBy = null): array {
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $date = isset($data['activity_date']) ? trim((string) $data['activity_date']) : '';
        $description = isset($data['description']) ? trim((string) $data['description']) : null;

        if ($name === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['success' => false, 'error' => 'Name and valid activity_date (Y-m-d) are required'];
        }

        if ($this->activityRepository->findLocalDuplicate($name, $date)) {
            return ['success' => false, 'error' => 'An activity with this name and date already exists'];
        }

        $now = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');

        try {
            $id = Activity::create([
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'source' => 'LOCAL',
                'external_id' => null,
                'activity_date' => $date,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return ['success' => true, 'id' => (int) $id];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Could not create activity', 'details' => $e->getMessage()];
        }
    }

    public function updateLocal(int $id, array $data, ?int $updatedBy = null): array {
        $row = $this->activityRepository->findById($id);
        if (!$row) {
            return ['success' => false, 'error' => 'Activity not found'];
        }
        $r = is_object($row) ? json_decode(json_encode($row), true) : $row;
        if (($r['source'] ?? '') !== 'LOCAL') {
            return ['success' => false, 'error' => 'Only local activities can be edited'];
        }

        $name = isset($data['name']) ? trim((string) $data['name']) : ($r['name'] ?? '');
        $date = isset($data['activity_date']) ? trim((string) $data['activity_date']) : ($r['activity_date'] ?? '');
        $description = array_key_exists('description', $data) ? trim((string) $data['description']) : ($r['description'] ?? null);

        if ($name === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['success' => false, 'error' => 'Invalid name or activity_date'];
        }

        $dup = $this->activityRepository->findLocalDuplicate($name, $date);
        if ($dup) {
            $dupId = is_object($dup) ? (int) ($dup->id ?? 0) : (int) ($dup['id'] ?? 0);
            if ($dupId !== $id) {
                return ['success' => false, 'error' => 'Another activity already uses this name and date'];
            }
        }

        $now = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
        Activity::updateById($id, [
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'activity_date' => $date,
            'updated_at' => $now,
        ]);

        return ['success' => true];
    }

    public function deleteLocal(int $id): array {
        $row = $this->activityRepository->findById($id);
        if (!$row) {
            return ['success' => false, 'error' => 'Activity not found'];
        }
        $r = is_object($row) ? json_decode(json_encode($row), true) : $row;
        if (($r['source'] ?? '') !== 'LOCAL') {
            return ['success' => false, 'error' => 'Only local activities can be deleted'];
        }

        try {
            Activity::query()->where('id', $id)->delete();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Could not delete', 'details' => $e->getMessage()];
        }
    }
}
