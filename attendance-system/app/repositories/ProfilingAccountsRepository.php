<?php

/**
 * Account listing and CRUD for profiling-system.admin plus read-only barangay_official portal users.
 */
class ProfilingAccountsRepository
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ProfilingPdo::get();
    }

    public function isAvailable(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * @return array{type: string, numeric_id: int}|null
     */
    public static function parseAccountKey(string $key): ?array
    {
        $key = trim($key);
        if (preg_match('/^pa-(\d+)$/i', $key, $m)) {
            return ["type" => "profiling_admin", "numeric_id" => (int) $m[1]];
        }
        if (preg_match('/^bo-(\d+)$/i', $key, $m)) {
            return ["type" => "barangay_official", "numeric_id" => (int) $m[1]];
        }
        if (ctype_digit($key)) {
            return ["type" => "profiling_admin", "numeric_id" => (int) $key];
        }

        return null;
    }

    /**
     * @return array{accounts: array<int, array<string, mixed>>, pagination: array<string, mixed>, searchQuery: string}
     */
    public function getPaginatedMerged(int $page, int $perPage, string $searchQuery, array $filters): array
    {
        if (!$this->pdo) {
            return [
                "accounts" => [],
                "pagination" => [
                    "currentPage" => $page,
                    "totalPages" => 0,
                    "totalRecords" => 0,
                    "perPage" => $perPage,
                    "startRecord" => 0,
                    "endRecord" => 0,
                ],
                "searchQuery" => $searchQuery,
            ];
        }

        $search = trim($searchQuery);
        $like = $search === "" ? null : "%{$search}%";

        $roleFilter = $filters["role"] ?? "";
        $activeFilter = $filters["is_active"] ?? "";

        $adminRows = [];
        $officialRows = [];

        try {
            if ($roleFilter === "" || $roleFilter === "administrator") {
                $adminRows = $this->fetchProfilingAdmins($like, $activeFilter);
            }
            if ($roleFilter === "" || $roleFilter === "manager") {
                $officialRows = $this->fetchBarangayOfficials($like, $activeFilter);
            }
        } catch (PDOException $e) {
            error_log("ProfilingAccountsRepository::getPaginatedMerged: " . $e->getMessage());
            $adminRows = [];
            $officialRows = [];
        }

        $merged = array_merge($adminRows, $officialRows);
        usort($merged, static function (array $a, array $b): int {
            $ta = strtotime($a["_sort_ts"] ?? "1970-01-01") ?: 0;
            $tb = strtotime($b["_sort_ts"] ?? "1970-01-01") ?: 0;

            return $tb <=> $ta;
        });

        foreach ($merged as &$r) {
            unset($r["_sort_ts"]);
        }
        unset($r);

        $totalRecords = count($merged);
        $totalPages = $perPage > 0 ? (int) ceil($totalRecords / $perPage) : 0;
        $offset = ($page - 1) * $perPage;
        $accounts = array_slice($merged, $offset, $perPage);

        $startRecord = $totalRecords > 0 ? $offset + 1 : 0;
        $endRecord = min($offset + $perPage, $totalRecords);

        return [
            "accounts" => $accounts,
            "pagination" => [
                "currentPage" => $page,
                "totalPages" => $totalPages,
                "totalRecords" => $totalRecords,
                "perPage" => $perPage,
                "startRecord" => $startRecord,
                "endRecord" => $endRecord,
            ],
            "searchQuery" => $searchQuery,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchProfilingAdmins(?string $like, $activeFilter): array
    {
        $sql = "SELECT id, username, email, full_name, password, role, is_active, last_login, created_at, updated_at
                FROM `admin` WHERE 1=1";
        $params = [];

        if ($like !== null) {
            $sql .= " AND (username LIKE :s OR email LIKE :s OR full_name LIKE :s)";
            $params[":s"] = $like;
        }

        if ($activeFilter !== "") {
            $sql .= " AND is_active = :ia";
            $params[":ia"] = (int) $activeFilter;
        }

        $sql .= " ORDER BY updated_at DESC, id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];

        foreach ($rows as $row) {
            $out[] = [
                "id" => "pa-" . (int) $row["id"],
                "numeric_id" => (int) $row["id"],
                "username" => $row["username"] ?? "",
                "email" => $row["email"] ?? "",
                "full_name" => $row["full_name"] ?? "",
                "role" => "administrator",
                "role_display" => $row["role"] ?? "administrator",
                "is_active" => (int) ($row["is_active"] ?? 1),
                "last_login" => $row["last_login"] ?? null,
                "created_at" => $row["created_at"] ?? null,
                "updated_at" => $row["updated_at"] ?? null,
                "source" => "profiling_admin",
                "actions_editable" => true,
                "_sort_ts" => $row["updated_at"] ?? $row["created_at"] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Active barangay officials with login credentials (read-only in this UI).
     *
     * @return list<array<string, mixed>>
     */
    private function fetchBarangayOfficials(?string $like, $activeFilter): array
    {
        $sql = "SELECT * FROM `barangay_official` WHERE username IS NOT NULL AND TRIM(username) <> ''";
        $params = [];

        if ($activeFilter !== "") {
            $wantActive = (int) $activeFilter === 1;
            $sql .= $wantActive ? " AND status = 'Active'" : " AND status <> 'Active'";
        }

        if ($like !== null) {
            $sql .= " AND (
                username LIKE :s OR first_name LIKE :s OR surname LIKE :s OR position LIKE :s
                OR CONCAT(TRIM(first_name), ' ', TRIM(surname)) LIKE :s
            )";
            $params[":s"] = $like;
        }

        $sql .= " ORDER BY updated_at DESC, id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $fn = trim((string) ($row["first_name"] ?? ""));
            $sn = trim((string) ($row["surname"] ?? ""));
            $full = trim($fn . " " . $sn);
            $isActive = (($row["status"] ?? "") === "Active") ? 1 : 0;

            $out[] = [
                "id" => "bo-" . (int) $row["id"],
                "numeric_id" => (int) $row["id"],
                "username" => $row["username"] ?? "",
                "email" => isset($row["email"]) ? (string) $row["email"] : "",
                "full_name" => $full,
                "role" => "manager",
                "role_display" => $row["position"] ?? "Barangay Official",
                "is_active" => $isActive,
                "last_login" => null,
                "created_at" => $row["created_at"] ?? null,
                "updated_at" => $row["updated_at"] ?? null,
                "source" => "profiling_barangay_official",
                "actions_editable" => false,
                "_sort_ts" => $row["updated_at"] ?? $row["created_at"] ?? null,
            ];
        }

        return $out;
    }

    public function findAdminById(int $id): ?array
    {
        if (!$this->pdo) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT id, username, email, full_name, role, is_active, last_login, created_at, updated_at FROM `admin` WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createAdmin(array $data): int
    {
        if (!$this->pdo) {
            throw new RuntimeException("Profiling database unavailable");
        }

        $hash = Admin::hashPassword($data["password"]);
        $now = date("Y-m-d H:i:s");
        $stmt = $this->pdo->prepare(
            "INSERT INTO `admin` (username, email, password, full_name, role, is_active, created_at, updated_at)
             VALUES (:username, :email, :password, :full_name, :role, :is_active, :created_at, :updated_at)"
        );
        $stmt->execute([
            ":username" => $data["username"],
            ":email" => $data["email"],
            ":password" => $hash,
            ":full_name" => $data["full_name"] !== "" ? $data["full_name"] : $data["username"],
            ":role" => $data["role"] ?? "administrator",
            ":is_active" => (int) ($data["is_active"] ?? 1),
            ":created_at" => $now,
            ":updated_at" => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateAdmin(int $id, array $data): bool
    {
        if (!$this->pdo) {
            return false;
        }

        $fields = [];
        $params = [":id" => $id];

        if (isset($data["username"])) {
            $fields[] = "username = :username";
            $params[":username"] = trim((string) $data["username"]);
        }
        if (isset($data["email"])) {
            $fields[] = "email = :email";
            $params[":email"] = trim((string) $data["email"]);
        }
        if (isset($data["full_name"])) {
            $fields[] = "full_name = :full_name";
            $params[":full_name"] = $data["full_name"] === "" || $data["full_name"] === null
                ? ""
                : trim((string) $data["full_name"]);
        }
        if (isset($data["role"])) {
            $fields[] = "role = :role";
            $params[":role"] = (string) $data["role"];
        }
        if (!empty($data["password"])) {
            $fields[] = "password = :password";
            $params[":password"] = Admin::hashPassword($data["password"]);
        }
        if (isset($data["is_active"])) {
            $fields[] = "is_active = :is_active";
            $params[":is_active"] = (int) $data["is_active"];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = :updated_at";
        $params[":updated_at"] = date("Y-m-d H:i:s");

        $sql = "UPDATE `admin` SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function deleteAdmin(int $id): bool
    {
        if (!$this->pdo) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM `admin` WHERE id = ?");

        return $stmt->execute([$id]);
    }

    public function setAdminActive(int $id, bool $active): bool
    {
        if (!$this->pdo) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE `admin` SET is_active = ?, updated_at = ? WHERE id = ?");

        return $stmt->execute([$active ? 1 : 0, date("Y-m-d H:i:s"), $id]);
    }

    public function findBarangayOfficialById(int $id): ?array
    {
        if (!$this->pdo) {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT id, status, username FROM `barangay_official` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Active = may use portal (profiling barangay_official.status).
     */
    public function setBarangayOfficialPortalActive(int $id, bool $active): bool
    {
        if (!$this->pdo) {
            return false;
        }
        $status = $active ? "Active" : "Inactive";
        $stmt = $this->pdo->prepare(
            "UPDATE `barangay_official` SET status = ?, updated_at = ? WHERE id = ?"
        );

        return $stmt->execute([$status, date("Y-m-d H:i:s"), $id]);
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        if (!$this->pdo) {
            return false;
        }
        if ($exceptId === null) {
            $stmt = $this->pdo->prepare("SELECT 1 FROM `admin` WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
        } else {
            $stmt = $this->pdo->prepare("SELECT 1 FROM `admin` WHERE username = ? AND id <> ? LIMIT 1");
            $stmt->execute([$username, $exceptId]);
        }

        return (bool) $stmt->fetchColumn();
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        if (!$this->pdo) {
            return false;
        }
        if ($exceptId === null) {
            $stmt = $this->pdo->prepare("SELECT 1 FROM `admin` WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
        } else {
            $stmt = $this->pdo->prepare("SELECT 1 FROM `admin` WHERE email = ? AND id <> ? LIMIT 1");
            $stmt->execute([$email, $exceptId]);
        }

        return (bool) $stmt->fetchColumn();
    }
}
