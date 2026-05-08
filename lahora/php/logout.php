<?php
session_start();
require_once 'user_logger.php';

// Log logout before destroying session
if (isset($_SESSION['username'])) {
    require_once 'connection.php';
    if (isset($_SESSION['user_id'])) {
        $update_stmt = $conn->prepare("UPDATE users SET is_logged_in = 0 WHERE idNo = ?");
        $update_stmt->bind_param("s", $_SESSION['user_id']);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    logAction('LOGOUT', "User {$_SESSION['username']} logged out");
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Optionally clear the session cookie (if using cookies)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header("Location: login.php");
exit();
?>
