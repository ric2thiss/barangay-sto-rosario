<?php
/**
 * delete_residents.php — Soft Delete
 * Instead of permanently deleting, sets is_deleted = 1 and deleted_at = NOW().
 * Purok Presidents can only soft-delete residents within their purok.
 */
session_start();
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Check if user is a Purok President
    $is_purok_president = (($_SESSION['staff_position'] ?? '') === 'Purok President');

    // Fetch resident info for logging and purok enforcement
    $stmt = $conn->prepare("SELECT first_name, surname, purok, image_path FROM residents WHERE id = ? AND is_deleted = 0 LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        $res_name = $row['first_name'] . ' ' . $row['surname'];

        // Purok President enforcement: cannot delete residents outside their purok
        if ($is_purok_president && $row['purok'] !== ($_SESSION['purok'] ?? '')) {
            $_SESSION['error'] = "You can only delete residents within your purok ({$_SESSION['purok']}).";
            header("Location: resident.php");
            exit;
        }

        // Soft delete — set flag instead of removing row
        $upd = $conn->prepare("UPDATE residents SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
        $upd->bind_param('i', $id);

        if ($upd->execute()) {
            // Log the soft-delete action
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_type, username, full_name, action, details, ip_address, login_at, status) VALUES (?, ?, ?, ?, 'delete_resident', ?, ?, NOW(), 'offline')");
            $log_utype   = $_SESSION['user_type'] ?? 'admin';
            $log_uname   = $_SESSION['username'] ?? 'Admin';
            $log_fname   = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
            $log_details = "Soft-deleted resident: $res_name (ID: $id)";
            $log_ip      = $_SERVER['REMOTE_ADDR'] ?? '';
            $log_stmt->bind_param('isssss', $_SESSION['user_id'], $log_utype, $log_uname, $log_fname, $log_details, $log_ip);
            $log_stmt->execute();
            $log_stmt->close();

            $_SESSION['success'] = "Resident $res_name has been archived. You can restore them from Deleted Residents.";
            header("Location: resident.php");
            exit;
        } else {
            $_SESSION['error'] = "Error archiving record: " . $conn->error;
            header("Location: resident.php");
            exit;
        }
        $upd->close();
    } else {
        $stmt->close();
        $_SESSION['error'] = "Resident not found.";
        header("Location: resident.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: resident.php");
    exit;
}

$conn->close();
?>
