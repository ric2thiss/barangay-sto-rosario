<?php

require_once __DIR__ . '/BaseRepository.php';

class LoginLogRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return LoginLog::class;
    }

    public function insertAttempt(
        string $username,
        bool $success,
        ?string $message = null,
        ?string $authSource = null,
        ?string $role = null
    ): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 512) : null;
            LoginLog::create([
                'username' => substr($username, 0, 255),
                'success' => $success ? 1 : 0,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'auth_source' => $authSource ? substr($authSource, 0, 64) : null,
                'role' => $role ? substr($role, 0, 128) : null,
                'message' => $message ? substr($message, 0, 255) : null,
            ]);
        } catch (Throwable $e) {
            error_log('LoginLogRepository::insertAttempt: ' . $e->getMessage());
        }
    }

    /**
     * @return array{rows: array, total: int}
     */
    public function getPaged(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $countRow = LoginLog::query()->select('COUNT(*) AS c')->first();
        $total = $countRow ? (int) (is_object($countRow) ? ($countRow->c ?? 0) : ($countRow['c'] ?? 0)) : 0;

        $rows = LoginLog::query()
            ->orderBy('id', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();

        return ['rows' => is_array($rows) ? $rows : [], 'total' => $total];
    }
}
