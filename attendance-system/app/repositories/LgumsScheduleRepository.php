<?php

/**
 * Read-only access to lgums.schedule_events (cross-database).
 * Column names are configurable in app.config.php to match the real LGUMS schema.
 */
class LgumsScheduleRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array{id:mixed, event_name:string, event_date:string}>
     */
    public function fetchEventsForDate(string $dateYmd): array {
        if (!defined('LGUMS_DB_NAME') || LGUMS_DB_NAME === '') {
            return [];
        }

        $db = $this->assertIdent(LGUMS_DB_NAME);
        $table = $this->assertIdent(LGUMS_SCHEDULE_EVENTS_TABLE);
        $idCol = $this->assertIdent(LGUMS_SCHEDULE_EVENT_ID_COL);
        $nameCol = $this->assertIdent(LGUMS_SCHEDULE_EVENT_NAME_COL);
        $dateCol = $this->assertIdent(LGUMS_SCHEDULE_EVENT_DATE_COL);

        $sql = "SELECT `{$idCol}` AS `id`, `{$nameCol}` AS `event_name`, DATE(`{$dateCol}`) AS `event_date`
                FROM `{$db}`.`{$table}`
                WHERE DATE(`{$dateCol}`) = :d";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':d' => $dateYmd]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : [];
        } catch (PDOException $e) {
            error_log('LgumsScheduleRepository: ' . $e->getMessage());
            return [];
        }
    }

    private function assertIdent(string $name): string {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('Invalid SQL identifier');
        }
        return $name;
    }
}
