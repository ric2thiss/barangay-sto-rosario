<?php

require_once __DIR__ . '/BaseRepository.php';

class EventFineRepository extends BaseRepository {
    protected function getModelClass(): string {
        return EventFine::class;
    }

    /** @var bool */
    private static $schemaEnsured = false;

    /**
     * Create event_fines if missing (once per request). Requires DB user CREATE privilege.
     */
    private function ensureEventFinesTable(): void {
        if (self::$schemaEnsured) {
            return;
        }
        self::$schemaEnsured = true;
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `event_fines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `activity_id` INT NOT NULL,
  `fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_fines_activity` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
        try {
            $this->pdo->exec($sql);
        } catch (Throwable $e) {
            error_log('EventFineRepository::ensureEventFinesTable: ' . $e->getMessage());
            self::$schemaEnsured = false;
            throw $e;
        }
    }

    private static function isMissingEventFinesTable(Throwable $e): bool {
        if ($e instanceof PDOException) {
            $code = (string) $e->getCode();
            if ($code === '42S02' || $code === '1146') {
                return true;
            }
            $msg = $e->getMessage();
            if (stripos($msg, 'event_fines') !== false && stripos($msg, "doesn't exist") !== false) {
                return true;
            }
        }
        return false;
    }

    public function getByActivityId(int $activityId): ?array {
        $this->ensureEventFinesTable();
        try {
            $row = EventFine::query()->where(['activity_id' => $activityId])->first();
        } catch (Throwable $e) {
            if (self::isMissingEventFinesTable($e)) {
                return null;
            }
            throw $e;
        }
        if (!$row) {
            return null;
        }
        return is_object($row) ? json_decode(json_encode($row), true) : $row;
    }

    public function getAmountByActivityId(int $activityId): float {
        $r = $this->getByActivityId($activityId);
        if (!$r) {
            return 0.0;
        }
        return round((float) ($r['fine_amount'] ?? 0), 2);
    }

    /**
     * Insert or update fine for an activity.
     *
     * @throws RuntimeException when event_fines table is missing
     */
    public function upsert(int $activityId, float $amount): void {
        $existing = $this->getByActivityId($activityId);
        try {
            $now = date('Y-m-d H:i:s');
            if ($existing) {
                EventFine::query()->where(['activity_id' => $activityId])->update([
                    'fine_amount' => round($amount, 2),
                    'updated_at' => $now,
                ]);
                return;
            }
            EventFine::create([
                'activity_id' => $activityId,
                'fine_amount' => round($amount, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (Throwable $e) {
            if (self::isMissingEventFinesTable($e)) {
                throw new RuntimeException(
                    'The event_fines table does not exist. Run database/run_event_fines_migration.php once (logged in) or apply database/migrations/create_event_fines.sql.',
                    0,
                    $e
                );
            }
            throw $e;
        }
    }

    /**
     * All activities with configured absence fine (0 if not set). Newest meeting date first.
     *
     * @return array<int, array{activity_id:int, activity_name:string, activity_date:?string, fine_amount:float, fine_updated_at:?string}>
     */
    public function listActivitiesWithFineAmounts(): array {
        try {
            $this->ensureEventFinesTable();
        } catch (Throwable $e) {
            return $this->listActivitiesWithoutFinesJoin();
        }
        $sql = <<<'SQL'
SELECT a.id AS activity_id, a.name AS activity_name, a.activity_date,
       COALESCE(ef.fine_amount, 0) AS fine_amount,
       ef.updated_at AS fine_updated_at
FROM activities a
LEFT JOIN event_fines ef ON ef.activity_id = a.id
ORDER BY COALESCE(a.activity_date, '1970-01-01') DESC, a.name ASC
SQL;
        try {
            $stmt = $this->pdo->query($sql);
        } catch (Throwable $e) {
            if (self::isMissingEventFinesTable($e)) {
                return $this->listActivitiesWithoutFinesJoin();
            }
            throw $e;
        }
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[] = [
                'activity_id' => (int) ($row['activity_id'] ?? 0),
                'activity_name' => (string) ($row['activity_name'] ?? ''),
                'activity_date' => isset($row['activity_date']) && $row['activity_date'] !== null && $row['activity_date'] !== ''
                    ? (string) $row['activity_date']
                    : null,
                'fine_amount' => round((float) ($row['fine_amount'] ?? 0), 2),
                'fine_updated_at' => isset($row['fine_updated_at']) && $row['fine_updated_at'] !== null && $row['fine_updated_at'] !== ''
                    ? (string) $row['fine_updated_at']
                    : null,
            ];
        }
        return $out;
    }

    /**
     * @return array<int, array{activity_id:int, activity_name:string, activity_date:?string, fine_amount:float, fine_updated_at:?string}>
     */
    private function listActivitiesWithoutFinesJoin(): array {
        try {
            $rows = Activity::query()->orderByRaw("COALESCE(activity_date, '1970-01-01') DESC, name ASC")->get();
        } catch (Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $r = is_object($row) ? json_decode(json_encode($row), true) : $row;
            $out[] = [
                'activity_id' => (int) ($r['id'] ?? 0),
                'activity_name' => (string) ($r['name'] ?? ''),
                'activity_date' => isset($r['activity_date']) && $r['activity_date'] !== null && $r['activity_date'] !== ''
                    ? (string) $r['activity_date']
                    : null,
                'fine_amount' => 0.0,
                'fine_updated_at' => null,
            ];
        }
        return $out;
    }
}
