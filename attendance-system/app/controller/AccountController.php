<?php

class AccountController
{
    private ProfilingAccountsRepository $profilingAccounts;

    public function __construct(?ProfilingAccountsRepository $profilingAccounts = null)
    {
        $this->profilingAccounts = $profilingAccounts ?? new ProfilingAccountsRepository();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{accounts: array, pagination: array, searchQuery: string}
     */
    public function getPaginatedAccounts($page = 1, $perPage = 10, $searchQuery = "", $filters = [])
    {
        return $this->profilingAccounts->getPaginatedMerged(
            max(1, (int) $page),
            max(1, (int) $perPage),
            (string) $searchQuery,
            is_array($filters) ? $filters : []
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data)
    {
        if (!$this->profilingAccounts->isAvailable()) {
            return [
                "success" => false,
                "status" => 503,
                "error" => "Profiling database is not available. Check MySQL and profiling-system connection.",
            ];
        }

        if (empty($data["username"]) || empty($data["email"]) || empty($data["password"])) {
            return [
                "success" => false,
                "status" => 400,
                "error" => "Username, email, and password are required",
            ];
        }

        if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            return [
                "success" => false,
                "status" => 400,
                "error" => "Invalid email format",
            ];
        }

        $username = trim((string) $data["username"]);
        $email = trim((string) $data["email"]);

        if ($this->profilingAccounts->usernameExists($username)) {
            return [
                "success" => false,
                "status" => 400,
                "error" => "Username already exists",
            ];
        }

        if ($this->profilingAccounts->emailExists($email)) {
            return [
                "success" => false,
                "status" => 400,
                "error" => "Email already exists",
            ];
        }

        try {
            $newId = $this->profilingAccounts->createAdmin([
                "username" => $username,
                "email" => $email,
                "password" => $data["password"],
                "full_name" => !empty($data["full_name"]) ? trim((string) $data["full_name"]) : $username,
                "role" => !empty($data["role"]) ? (string) $data["role"] : "administrator",
                "is_active" => isset($data["is_active"]) ? (int) $data["is_active"] : 1,
            ]);

            return [
                "success" => true,
                "status" => 201,
                "message" => "Account successfully created.",
                "id" => "pa-" . $newId,
            ];
        } catch (PDOException $err) {
            return [
                "success" => false,
                "status" => 400,
                "error" => $this->getUserFriendlyErrorMessage($err),
            ];
        } catch (Exception $err) {
            return [
                "success" => false,
                "status" => 500,
                "error" => "An unexpected error occurred. Please try again or contact support if the problem persists.",
            ];
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update($id, array $data)
    {
        $parsed = ProfilingAccountsRepository::parseAccountKey((string) $id);
        if (!$parsed || $parsed["type"] !== "profiling_admin") {
            return [
                "success" => false,
                "status" => 403,
                "error" => "Only profiling-system administrator accounts can be edited here.",
            ];
        }

        $numericId = $parsed["numeric_id"];
        if (!$this->profilingAccounts->isAvailable()) {
            return [
                "success" => false,
                "status" => 503,
                "error" => "Profiling database is not available.",
            ];
        }

        $existing = $this->profilingAccounts->findAdminById($numericId);
        if (!$existing) {
            return [
                "success" => false,
                "status" => 404,
                "error" => "Account not found",
            ];
        }

        if (!empty($data["email"]) && !filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            return [
                "success" => false,
                "status" => 400,
                "error" => "Invalid email format",
            ];
        }

        try {
            $updateData = [];

            if (isset($data["username"])) {
                $u = trim((string) $data["username"]);
                if ($this->profilingAccounts->usernameExists($u, $numericId)) {
                    return [
                        "success" => false,
                        "status" => 400,
                        "error" => "Username already exists",
                    ];
                }
                $updateData["username"] = $u;
            }

            if (isset($data["email"])) {
                $e = trim((string) $data["email"]);
                if ($this->profilingAccounts->emailExists($e, $numericId)) {
                    return [
                        "success" => false,
                        "status" => 400,
                        "error" => "Email already exists",
                    ];
                }
                $updateData["email"] = $e;
            }

            if (array_key_exists("full_name", $data)) {
                $updateData["full_name"] = trim((string) $data["full_name"]);
            }

            if (isset($data["role"])) {
                $updateData["role"] = (string) $data["role"];
            }

            if (!empty($data["password"])) {
                $updateData["password"] = $data["password"];
            }

            if (isset($data["is_active"])) {
                $updateData["is_active"] = (int) $data["is_active"];
            }

            if (empty($updateData)) {
                return [
                    "success" => false,
                    "status" => 400,
                    "error" => "No valid fields to update",
                ];
            }

            $ok = $this->profilingAccounts->updateAdmin($numericId, $updateData);

            return $ok
                ? ["success" => true, "status" => 200, "message" => "Account successfully updated."]
                : ["success" => false, "status" => 500, "error" => "Failed to update account."];
        } catch (PDOException $err) {
            return [
                "success" => false,
                "status" => 400,
                "error" => $this->getUserFriendlyErrorMessage($err),
            ];
        } catch (Exception $err) {
            return [
                "success" => false,
                "status" => 500,
                "error" => "An unexpected error occurred. Please try again or contact support if the problem persists.",
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function delete($id)
    {
        $parsed = ProfilingAccountsRepository::parseAccountKey((string) $id);
        if (!$parsed || $parsed["type"] !== "profiling_admin") {
            return [
                "success" => false,
                "status" => 403,
                "error" => "Only profiling-system administrator accounts can be deleted here.",
            ];
        }

        $numericId = $parsed["numeric_id"];

        if ($this->isCurrentProfilingAdminSessionRow($numericId)) {
            return [
                "success" => false,
                "status" => 400,
                "error" => "You cannot delete your own account",
            ];
        }

        if (!$this->profilingAccounts->isAvailable()) {
            return [
                "success" => false,
                "status" => 503,
                "error" => "Profiling database is not available.",
            ];
        }

        if (!$this->profilingAccounts->findAdminById($numericId)) {
            return [
                "success" => false,
                "status" => 404,
                "error" => "Account not found",
            ];
        }

        try {
            $deleted = $this->profilingAccounts->deleteAdmin($numericId);

            return $deleted
                ? ["success" => true, "status" => 200, "message" => "Account successfully deleted."]
                : ["success" => false, "status" => 500, "error" => "Failed to delete account."];
        } catch (PDOException $err) {
            return [
                "success" => false,
                "status" => 400,
                "error" => $this->getUserFriendlyErrorMessage($err),
            ];
        } catch (Exception $err) {
            return [
                "success" => false,
                "status" => 500,
                "error" => "An unexpected error occurred. Please try again or contact support if the problem persists.",
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toggleLock($id, bool $lock)
    {
        $parsed = ProfilingAccountsRepository::parseAccountKey((string) $id);
        if (!$parsed || $parsed["type"] !== "profiling_admin") {
            return [
                "success" => false,
                "status" => 403,
                "error" => "Only profiling-system administrator accounts can be locked here.",
            ];
        }

        $numericId = $parsed["numeric_id"];

        if ($this->isCurrentProfilingAdminSessionRow($numericId)) {
            return [
                "success" => false,
                "status" => 400,
                "error" => "You cannot lock your own account",
            ];
        }

        if (!$this->profilingAccounts->isAvailable()) {
            return [
                "success" => false,
                "status" => 503,
                "error" => "Profiling database is not available.",
            ];
        }

        if (!$this->profilingAccounts->findAdminById($numericId)) {
            return [
                "success" => false,
                "status" => 404,
                "error" => "Account not found",
            ];
        }

        try {
            $ok = $this->profilingAccounts->setAdminActive($numericId, !$lock);

            return $ok
                ? [
                    "success" => true,
                    "status" => 200,
                    "message" => $lock ? "Account successfully locked." : "Account successfully unlocked.",
                ]
                : ["success" => false, "status" => 500, "error" => "Failed to update account status."];
        } catch (PDOException $err) {
            return [
                "success" => false,
                "status" => 400,
                "error" => $this->getUserFriendlyErrorMessage($err),
            ];
        } catch (Exception $err) {
            return [
                "success" => false,
                "status" => 500,
                "error" => "An unexpected error occurred. Please try again or contact support if the problem persists.",
            ];
        }
    }

    private function isCurrentProfilingAdminSessionRow(int $numericId): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return ((string) ($_SESSION["auth_source"] ?? "")) === "profiling_admin"
            && (int) ($_SESSION["admin_id"] ?? 0) === $numericId;
    }

    private function isCurrentOfficialSessionRow(int $numericId): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return ((string) ($_SESSION["auth_source"] ?? "")) === "profiling_barangay_official"
            && (int) ($_SESSION["admin_id"] ?? 0) === $numericId;
    }

    /**
     * Allow or revoke portal access for a barangay_official row (profiling status Active/Inactive).
     *
     * @return array<string, mixed>
     */
    public function toggleOfficialPortalAccess($id, bool $allow)
    {
        $parsed = ProfilingAccountsRepository::parseAccountKey((string) $id);
        if (!$parsed || $parsed["type"] !== "barangay_official") {
            return [
                "success" => false,
                "status" => 403,
                "error" => "Only barangay official portal accounts can be updated here.",
            ];
        }

        $numericId = $parsed["numeric_id"];

        if ($this->isCurrentOfficialSessionRow($numericId)) {
            return [
                "success" => false,
                "status" => 400,
                "error" => "You cannot change system access for your own account here.",
            ];
        }

        if (!$this->profilingAccounts->isAvailable()) {
            return [
                "success" => false,
                "status" => 503,
                "error" => "Profiling database is not available.",
            ];
        }

        $row = $this->profilingAccounts->findBarangayOfficialById($numericId);
        if (!$row) {
            return [
                "success" => false,
                "status" => 404,
                "error" => "Account not found",
            ];
        }

        try {
            $ok = $this->profilingAccounts->setBarangayOfficialPortalActive($numericId, $allow);

            return $ok
                ? [
                    "success" => true,
                    "status" => 200,
                    "message" => $allow
                        ? "This user may now sign in and use the system (portal status: Active)."
                        : "System access revoked (portal status: Inactive).",
                ]
                : ["success" => false, "status" => 500, "error" => "Failed to update portal access."];
        } catch (PDOException $err) {
            return [
                "success" => false,
                "status" => 400,
                "error" => $this->getUserFriendlyErrorMessage($err),
            ];
        } catch (Exception $err) {
            return [
                "success" => false,
                "status" => 500,
                "error" => "An unexpected error occurred. Please try again or contact support if the problem persists.",
            ];
        }
    }

    private function getUserFriendlyErrorMessage(PDOException $exception): string
    {
        $errorMessage = $exception->getMessage();

        if ($exception->getCode() == 23000 || strpos($errorMessage, "1062") !== false) {
            if (preg_match("/for key '([^']+)'/", $errorMessage, $m)) {
                $k = $m[1] ?? "";
                if (stripos($k, "username") !== false) {
                    return "Username already exists. Please choose a different username.";
                }
                if (stripos($k, "email") !== false) {
                    return "Email already exists. Please use a different email address.";
                }
            }

            return "This record already exists. Please check your input and try again.";
        }

        if (strpos($errorMessage, "Unknown column") !== false) {
            return "The profiling admin table is missing required columns. Run database/profiling-system/admin_table_migration.sql on the profiling-system database.";
        }

        return "Unable to save the account information. Please check all fields and try again. If the problem persists, contact support.";
    }
}
