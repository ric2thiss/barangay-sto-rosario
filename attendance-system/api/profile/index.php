<?php
/**
 * Profile API — update name/email/username and change password.
 * Supports attendance-system admins, profiling-system admin, and barangay_official.
 */

error_reporting(E_ALL);
ini_set("display_errors", 0);
ini_set("log_errors", 1);

header("Content-Type: application/json");
ob_start();

/**
 * @param array<string, string> $fields
 */
function profile_sync_session(array $fields): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($fields["username"])) {
        $_SESSION["admin_username"] = $fields["username"];
        $_SESSION["username"] = $fields["username"];
    }
    if (isset($fields["full_name"])) {
        $_SESSION["admin_full_name"] = $fields["full_name"];
        $_SESSION["name"] = $fields["full_name"];
    }
    if (isset($fields["email"])) {
        $_SESSION["admin_email"] = $fields["email"];
    }
}

function profiling_barangay_has_email_column(PDO $pdo): bool
{
    try {
        $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
        if (!$db) {
            return false;
        }
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'barangay_official' AND COLUMN_NAME = 'email'"
        );
        $stmt->execute([$db]);

        return (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * @return array{0: string, 1: string}
 */
function profile_split_full_name(string $full): array
{
    $full = trim($full);
    if ($full === "") {
        return ["", ""];
    }
    $parts = preg_split('/\s+/', $full, 2);

    return [
        $parts[0] ?? "",
        isset($parts[1]) ? $parts[1] : ($parts[0] ?? ""),
    ];
}

try {
    require_once __DIR__ . "/../../bootstrap.php";
    require_once __DIR__ . "/../../auth/helpers.php";
    requireAuth();

    $user = currentUser();
    if (!$user) {
        ob_clean();
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $authSource = (string) ($_SESSION["auth_source"] ?? "");
    $userId = (int) ($user["id"] ?? 0);

    $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
    if ($method !== "PUT" && $method !== "POST") {
        ob_clean();
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid request data"]);
        exit;
    }

    if ($authSource === "profiling_resident") {
        ob_clean();
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Resident profiles are managed in the Profiling system.",
        ]);
        exit;
    }

    $db = (new Database())->connect();
    $adminRepository = new AdminRepository($db);
    $profilingRepo = new ProfilingAccountsRepository();

    if ($authSource === "") {
        if ($adminRepository->findById($userId)) {
            $authSource = "attendance_admin";
        } elseif ($profilingRepo->findAdminById($userId)) {
            $authSource = "profiling_admin";
        } elseif ($profilingRepo->findBarangayOfficialById($userId)) {
            $authSource = "profiling_barangay_official";
        }
    }

    // --- Password change ---
    if (isset($input["action"]) && $input["action"] === "change_password") {
        $currentPassword = (string) ($input["current_password"] ?? "");
        $newPassword = (string) ($input["new_password"] ?? "");

        if ($currentPassword === "" || $newPassword === "") {
            ob_clean();
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Current password and new password are required"]);
            exit;
        }

        if (strlen($newPassword) < 6) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "New password must be at least 6 characters"]);
            exit;
        }

        if ($authSource === "profiling_admin") {
            if (!$profilingRepo->isAvailable()) {
                ob_clean();
                http_response_code(503);
                echo json_encode(["success" => false, "message" => "Profiling database unavailable"]);
                exit;
            }
            $row = $profilingRepo->findAdminById($userId);
            if (!$row || !password_verify($currentPassword, $row["password"])) {
                ob_clean();
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Current password is incorrect"]);
                exit;
            }
            $ok = $profilingRepo->updateAdmin($userId, ["password" => $newPassword]);
            ob_clean();
            echo json_encode([
                "success" => (bool) $ok,
                "message" => $ok ? "Password changed successfully" : "Failed to update password",
            ]);
            exit;
        }

        if ($authSource === "profiling_barangay_official") {
            $pdo = ProfilingPdo::get();
            if (!$pdo) {
                ob_clean();
                http_response_code(503);
                echo json_encode(["success" => false, "message" => "Profiling database unavailable"]);
                exit;
            }
            $row = $profilingRepo->findBarangayOfficialById($userId);
            if (!$row || !password_verify($currentPassword, $row["password"])) {
                ob_clean();
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Current password is incorrect"]);
                exit;
            }
            $hash = Admin::hashPassword($newPassword);
            $stmt = $pdo->prepare("UPDATE `barangay_official` SET password = ?, updated_at = ? WHERE id = ?");
            $ok = $stmt->execute([$hash, date("Y-m-d H:i:s"), $userId]);
            ob_clean();
            echo json_encode([
                "success" => (bool) $ok,
                "message" => $ok ? "Password changed successfully" : "Failed to update password",
            ]);
            exit;
        }

        // attendance_admin or legacy
        $admin = $adminRepository->findById($userId);
        if (!$admin) {
            ob_clean();
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "User not found"]);
            exit;
        }
        if (is_object($admin)) {
            $admin = json_decode(json_encode($admin), true);
        }
        if (!$adminRepository->verifyPassword($currentPassword, $admin["password"])) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Current password is incorrect"]);
            exit;
        }
        $hashedPassword = $adminRepository->hashPassword($newPassword);
        $success = Admin::query()->where("id", $userId)->update(["password" => $hashedPassword]);
        ob_clean();
        echo json_encode([
            "success" => (bool) $success,
            "message" => $success ? "Password changed successfully" : "Failed to update password",
        ]);
        exit;
    }

    // --- Profile fields ---
    $updateData = [];

    if (isset($input["username"])) {
        $username = trim((string) $input["username"]);
        if ($username === "") {
            ob_clean();
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Username cannot be empty"]);
            exit;
        }
        $updateData["username"] = $username;
    }

    if (array_key_exists("full_name", $input)) {
        $updateData["full_name"] = trim((string) $input["full_name"]);
    }

    if (isset($input["email"])) {
        $email = trim((string) $input["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid email format"]);
            exit;
        }
        $updateData["email"] = $email;
    }

    if (empty($updateData)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No valid fields to update"]);
        exit;
    }

    if ($authSource === "profiling_admin") {
        if (!$profilingRepo->isAvailable()) {
            ob_clean();
            http_response_code(503);
            echo json_encode(["success" => false, "message" => "Profiling database unavailable"]);
            exit;
        }

        if (isset($updateData["username"]) && $profilingRepo->usernameExists($updateData["username"], $userId)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Username is already taken"]);
            exit;
        }
        if (isset($updateData["email"]) && $profilingRepo->emailExists($updateData["email"], $userId)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Email is already taken"]);
            exit;
        }

        $payload = [];
        if (isset($updateData["username"])) {
            $payload["username"] = $updateData["username"];
        }
        if (isset($updateData["email"])) {
            $payload["email"] = $updateData["email"];
        }
        if (array_key_exists("full_name", $updateData)) {
            $payload["full_name"] = $updateData["full_name"] !== ""
                ? $updateData["full_name"]
                : ($user["username"] ?? "User");
        }

        $ok = $profilingRepo->updateAdmin($userId, $payload);
        if ($ok) {
            $sync = [];
            if (isset($payload["username"])) {
                $sync["username"] = $payload["username"];
            }
            if (isset($payload["email"])) {
                $sync["email"] = $payload["email"];
            }
            if (isset($payload["full_name"])) {
                $sync["full_name"] = $payload["full_name"];
            }
            profile_sync_session($sync);
        }

        ob_clean();
        http_response_code($ok ? 200 : 500);
        echo json_encode([
            "success" => (bool) $ok,
            "message" => $ok ? "Profile updated successfully" : "Failed to update profile",
        ]);
        exit;
    }

    if ($authSource === "profiling_barangay_official") {
        $pdo = ProfilingPdo::get();
        if (!$pdo) {
            ob_clean();
            http_response_code(503);
            echo json_encode(["success" => false, "message" => "Profiling database unavailable"]);
            exit;
        }

        if (isset($updateData["username"])) {
            $chk = $pdo->prepare("SELECT id FROM `barangay_official` WHERE username = ? AND id <> ? LIMIT 1");
            $chk->execute([$updateData["username"], $userId]);
            if ($chk->fetch()) {
                ob_clean();
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Username is already taken"]);
                exit;
            }
        }

        $hasEmail = profiling_barangay_has_email_column($pdo);
        $fields = [];
        $params = [];

        if (isset($updateData["username"])) {
            $fields[] = "username = ?";
            $params[] = $updateData["username"];
        }
        if (array_key_exists("full_name", $updateData)) {
            $fullTrim = trim((string) $updateData["full_name"]);
            if ($fullTrim !== "") {
                [$fn, $sn] = profile_split_full_name($fullTrim);
                if ($sn === "") {
                    $sn = $fn;
                }
                $fields[] = "first_name = ?";
                $params[] = $fn;
                $fields[] = "surname = ?";
                $params[] = $sn;
            }
        }
        if (isset($updateData["email"]) && $hasEmail) {
            $fields[] = "email = ?";
            $params[] = $updateData["email"];
        }

        if (empty($fields)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "No valid fields to update"]);
            exit;
        }

        $fields[] = "updated_at = ?";
        $params[] = date("Y-m-d H:i:s");
        $params[] = $userId;

        $sql = "UPDATE `barangay_official` SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute($params);

        if ($ok) {
            $sync = [];
            if (isset($updateData["username"])) {
                $sync["username"] = $updateData["username"];
            }
            if (isset($updateData["email"]) && $hasEmail) {
                $sync["email"] = $updateData["email"];
            }
            if (array_key_exists("full_name", $updateData) && trim((string) $updateData["full_name"]) !== "") {
                $fullTrim = trim((string) $updateData["full_name"]);
                [$fn2, $sn2] = profile_split_full_name($fullTrim);
                if ($sn2 === "") {
                    $sn2 = $fn2;
                }
                $sync["full_name"] = trim($fn2 . " " . $sn2);
            }
            profile_sync_session($sync);
        }

        ob_clean();
        http_response_code($ok ? 200 : 500);
        echo json_encode([
            "success" => (bool) $ok,
            "message" => $ok ? "Profile updated successfully" : "Failed to update profile",
        ]);
        exit;
    }

    // attendance_admin (or empty legacy: local admins table)
    if (isset($updateData["username"])) {
        $existingAdmin = $adminRepository->findByUsername($updateData["username"]);
        if ($existingAdmin) {
            if (is_object($existingAdmin)) {
                $existingAdmin = json_decode(json_encode($existingAdmin), true);
            }
            if ((int) ($existingAdmin["id"] ?? 0) !== $userId) {
                ob_clean();
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Username is already taken by another user"]);
                exit;
            }
        }
    }

    if (isset($updateData["email"])) {
        $existingAdmin = $adminRepository->findByEmail($updateData["email"]);
        if ($existingAdmin) {
            if (is_object($existingAdmin)) {
                $existingAdmin = json_decode(json_encode($existingAdmin), true);
            }
            if ((int) ($existingAdmin["id"] ?? 0) !== $userId) {
                ob_clean();
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Email is already taken by another user"]);
                exit;
            }
        }
    }

    $localUpdate = [];
    if (isset($updateData["username"])) {
        $localUpdate["username"] = $updateData["username"];
    }
    if (array_key_exists("full_name", $updateData)) {
        $localUpdate["full_name"] = $updateData["full_name"];
    }
    if (isset($updateData["email"])) {
        $localUpdate["email"] = $updateData["email"];
    }

    $success = Admin::query()->where("id", $userId)->update($localUpdate);

    if ($success) {
        $sync = [];
        if (isset($localUpdate["username"])) {
            $sync["username"] = $localUpdate["username"];
        }
        if (isset($localUpdate["full_name"])) {
            $sync["full_name"] = $localUpdate["full_name"];
        }
        if (isset($localUpdate["email"])) {
            $sync["email"] = $localUpdate["email"];
        }
        profile_sync_session($sync);
    }

    ob_clean();
    http_response_code($success ? 200 : 500);
    echo json_encode([
        "success" => (bool) $success,
        "message" => $success ? "Profile updated successfully" : "Failed to update profile",
    ]);
} catch (Throwable $e) {
    error_log("Profile API Error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Internal server error",
    ]);
}

ob_end_flush();
