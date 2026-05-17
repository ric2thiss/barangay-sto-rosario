<?php
/**
 * Authentication Helper Functions
 * 
 * Include this file in pages that need authentication checks
 */

require_once __DIR__ . "/../bootstrap.php";

/**
 * Check if user is authenticated
 *
 * @return bool
 */
function isAuthenticated(): bool
{
    return AuthController::check();
}

/**
 * Get current authenticated user
 *
 * @return array|null
 */
function currentUser(): ?array
{
    return AuthController::user();
}

/**
 * Require authentication (redirect if not authenticated)
 *
 * @param string $redirectTo
 * @return void
 */
function requireAuth(?string $redirectTo = null): void
{
    if ($redirectTo === null) {
        $redirectTo = BASE_URL . "/auth/login.php";
    }
    AuthController::requireAuth($redirectTo);

    // Enforce maintenance mode for non-admin users on protected routes.
    // During maintenance, only administrators may access the system.
    try {
        $user = currentUser();
        $role = $user["role"] ?? null;

        $maintenanceExempt = class_exists("MaintenanceAccess") && MaintenanceAccess::isExempt($role);
        if (!$maintenanceExempt && class_exists("Settings") && Settings::isMaintenanceMode()) {
            $message = Settings::getValue(
                "maintenance_message",
                "The system is currently under maintenance. Please try again later."
            );

            // Detect API/JSON requests to avoid redirecting from APIs.
            $uri = $_SERVER["REQUEST_URI"] ?? "";
            $accept = $_SERVER["HTTP_ACCEPT"] ?? "";
            $isApiRequest = str_contains($uri, "/api/") || str_contains($accept, "application/json");

            // Log out non-admins to prevent continued access.
            (new AuthController())->logout();

            if ($isApiRequest) {
                http_response_code(503);
                header("Content-Type: application/json");
                echo json_encode([
                    "success" => false,
                    "maintenance_mode" => true,
                    "message" => $message
                ]);
                exit;
            }

            header("Location: " . BASE_URL . "/auth/login.php?error=maintenance");
            exit;
        }
    } catch (Exception $e) {
        // Fail open if settings system is unavailable to avoid locking out admins due to misconfig.
        error_log("Maintenance enforcement error: " . $e->getMessage());
    } catch (Error $e) {
        error_log("Maintenance enforcement fatal error: " . $e->getMessage());
    }
}

/**
 * Check if user has specific role
 *
 * @param string|array $roles
 * @return bool
 */
function hasRole($roles): bool
{
    $user = currentUser();
    if (!$user) {
        return false;
    }

    if (is_array($roles)) {
        return in_array($user["role"], $roles);
    }

    return $user["role"] === $roles;
}

/**
 * Require specific role (redirect if not authorized)
 *
 * @param string|array $roles
 * @param string $redirectTo
 * @return void
 */
function requireRole($roles, ?string $redirectTo = null): void
{
    requireAuth();

    if ($redirectTo === null) {
        $redirectTo = BASE_URL . "/admin/dashboard.php";
    }

    if (!hasRole($roles)) {
        header("Location: " . $redirectTo . "?error=unauthorized");
        exit;
    }
}

