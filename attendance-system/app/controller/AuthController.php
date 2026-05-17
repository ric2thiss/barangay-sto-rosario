<?php

class AuthController
{
    protected $adminRepository;

    public function __construct()
    {
        $db = (new Database())->connect();
        $this->adminRepository = new AdminRepository($db);
    }

    /**
     * Get connection to profiling-system database
     *
     * @return PDO|null
     */
    protected function getProfilingDbConnection(): ?PDO
    {
        return ProfilingPdo::get();
    }

    /**
     * Authenticate against profiling-system database
     * Checks admin, barangay_official, and residents tables in order
     *
     * @param string $username
     * @param string $password
     * @return array|null Returns user data if authenticated, null otherwise
     */
    protected function authenticateProfilingSystem(string $username, string $password): ?array
    {
        $conn = $this->getProfilingDbConnection();
        if (!$conn) {
            return null;
        }

        // 1. Check profiling-system admin table
        try {
            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = :login LIMIT 1");
            $stmt->execute([
                ":login" => $username,
            ]);

            if ($row = $stmt->fetch()) {
                if (array_key_exists("is_active", $row) && (int) $row["is_active"] === 0) {
                    return null;
                }
                if (password_verify($password, $row["password"])) {
                    $fullName = $row["full_name"] ?? $row["name"] ?? $row["username"];

                    return [
                        "id" => $row["id"],
                        "username" => $row["username"],
                        "email" => "",
                        "full_name" => $fullName,
                        "role" => "Administrator",
                        "source" => "profiling_admin",
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Profiling admin check failed: " . $e->getMessage());
        }

        // 2. Check barangay_official table
        try {
            $stmt = $conn->prepare("SELECT * FROM barangay_official WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            if ($row = $stmt->fetch()) {
                if (($row['status'] ?? '') !== 'Active') {
                    return null;
                }
                if (password_verify($password, $row['password'])) {
                    return [
                        'id' => $row['id'],
                        'username' => $row['username'],
                        'email' => $row['email'] ?? null,
                        'full_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['surname'] ?? '')),
                        'role' => $row['position'] ?? 'Secretary',
                        'source' => 'profiling_barangay_official'
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Profiling barangay_official check failed: " . $e->getMessage());
        }

        // 3. Residents table check removed as per user request

        return null;
    }

    /**
     * Set session variables compatible with both attendance-system and profiling-system
     *
     * @param array $userData
     * @return void
     */
    protected function setCompatibleSessionVariables(array $userData): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Attendance-system session variables
        $_SESSION["admin_id"] = $userData["id"];
        $_SESSION["admin_username"] = $userData["username"];
        $_SESSION["admin_email"] = $userData["email"] ?? '';
        $_SESSION["admin_full_name"] = $userData["full_name"];
        $_SESSION["admin_role"] = $userData["role"];
        $_SESSION["is_authenticated"] = true;
        $_SESSION["login_time"] = time();

        // Profiling-system compatible session variables (login.php format)
        $_SESSION["user_id"] = $userData["id"];
        $_SESSION["username"] = $userData["username"];
        $_SESSION["role"] = $userData["role"];
        $_SESSION["name"] = $userData["full_name"];

        // Track authentication source for debugging/logging
        $_SESSION["auth_source"] = $userData["source"] ?? 'attendance_admin';
    }

    /**
     * Validate credentials without creating a session (re-auth for destructive actions, etc.).
     *
     * @return array{ok: bool, user?: array, message?: string}
     */
    public function authenticateWithoutSession(string $usernameOrEmail, string $password): array
    {
        $profilingUser = $this->authenticateProfilingSystem($usernameOrEmail, $password);
        if ($profilingUser) {
            return ["ok" => true, "user" => $profilingUser];
        }

        return ["ok" => false, "message" => "Invalid username or password"];
    }

    public function verifyCredentials(string $usernameOrEmail, string $password): bool
    {
        $r = $this->authenticateWithoutSession($usernameOrEmail, $password);

        return !empty($r["ok"]);
    }

    /**
     * Attempt to login with username/email and password
     * Authentication flow:
     * 1. Check attendance-system admins table
     * 2. If not found, check profiling-system admin table
     * 3. If not found, check profiling-system barangay_official table
     * 4. If not found, check profiling-system residents table
     *
     * @param string $usernameOrEmail
     * @param string $password
     * @return array
     */
    public function login(string $usernameOrEmail, string $password): array
    {
        $db = (new Database())->connect();
        $loginLog = new LoginLogRepository($db);

        $auth = $this->authenticateWithoutSession($usernameOrEmail, $password);
        if (empty($auth["ok"])) {
            $loginLog->insertAttempt(
                $usernameOrEmail,
                false,
                $auth["message"] ?? "Invalid username or password",
                null,
                null
            );

            return [
                "success" => false,
                "message" => $auth["message"] ?? "Invalid username or password",
            ];
        }

        $user = $auth["user"];
        if (!UserAccessControlSettings::isLoginAllowed($user["source"] ?? "")) {
            $loginLog->insertAttempt(
                $usernameOrEmail,
                false,
                "User type not permitted to access the system",
                $user["source"] ?? null,
                $user["role"] ?? null
            );

            return [
                "success" => false,
                "message" => "Your account type is not permitted to access this system.",
            ];
        }

        $this->setCompatibleSessionVariables($user);

        if (($user["source"] ?? "") === "attendance_admin") {
            $this->adminRepository->updateLastLogin($user["id"]);
        }

        $loginLog->insertAttempt(
            $usernameOrEmail,
            true,
            null,
            $user["source"] ?? null,
            $user["role"] ?? null
        );

        $_SESSION["_login_audit_logged"] = true;

        return [
            "success" => true,
            "message" => "Login successful",
            "admin" => [
                "id" => $user["id"],
                "username" => $user["username"],
                "email" => $user["email"],
                "full_name" => $user["full_name"],
                "role" => $user["role"],
            ],
        ];
    }

    /**
     * Logout current user
     *
     * @return void
     */
    public function logout(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Unset all session variables
        $_SESSION = [];

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy session
        session_destroy();
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION["is_authenticated"]) && $_SESSION["is_authenticated"] === true;
    }

    /**
     * Get current authenticated admin
     *
     * @return array|null
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            "id" => $_SESSION["admin_id"] ?? null,
            "username" => $_SESSION["admin_username"] ?? null,
            "email" => $_SESSION["admin_email"] ?? null,
            "full_name" => $_SESSION["admin_full_name"] ?? null,
            "role" => $_SESSION["admin_role"] ?? null
        ];
    }

    /**
     * One row per PHP session when the user reaches a protected route without having used
     * {@see login()} in this app (e.g. central login.php / shared session). Skipped when
     * {@see login()} already recorded the attempt (flag set there).
     */
    public static function recordLoginAuditIfNeeded(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION["is_authenticated"]) || !empty($_SESSION["_login_audit_logged"])) {
            return;
        }

        $_SESSION["_login_audit_logged"] = true;

        $username = (string) ($_SESSION["admin_username"] ?? $_SESSION["username"] ?? "");
        if ($username === "") {
            return;
        }

        try {
            $db = (new Database())->connect();
            $repo = new LoginLogRepository($db);
            $source = $_SESSION["auth_source"] ?? null;
            if ($source === null || $source === "") {
                $source = "portal_sso";
            }
            $role = $_SESSION["admin_role"] ?? $_SESSION["role"] ?? null;
            $repo->insertAttempt($username, true, null, $source, $role);
        } catch (Throwable $e) {
            error_log("recordLoginAuditIfNeeded: " . $e->getMessage());
        }
    }

    /**
     * Require authentication (redirect if not authenticated)
     *
     * @param string $redirectTo
     * @return void
     */
    public static function requireAuth(?string $redirectTo = null): void
    {
        if (!defined("BASE_URL")) {
            require_once __DIR__ . "/../../config/app.config.php";
        }

        if ($redirectTo === null) {
            $redirectTo = BASE_URL . "/auth/login.php";
        }

        if (!self::check()) {
            header("Location: " . $redirectTo);
            exit;
        }

        self::recordLoginAuditIfNeeded();
    }
}

