<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php");
    exit();
}

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        $_SESSION['error'] = "Invalid resident ID.";
        header("Location: resident.php");
        exit();
    }
    
    // Get resident info before soft-deleting
    $sql_get_image = "SELECT image_path, first_name, surname FROM residents WHERE id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($sql_get_image);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Soft delete — set flag instead of removing row
        $sql_delete = "UPDATE residents SET is_deleted = 1, deleted_at = NOW() WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id);
        
        if ($stmt_delete->execute()) {
            $res_name = $row['first_name'] . ' ' . $row['surname'];
            $_SESSION['success'] = "Resident $res_name has been archived. You can restore them from Deleted Residents.";
        } else {
            $_SESSION['error'] = "Error archiving resident: " . $conn->error;
        }
        
        $stmt_delete->close();
    } else {
        $_SESSION['error'] = "Resident not found.";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: resident.php");
    exit();
}
?>