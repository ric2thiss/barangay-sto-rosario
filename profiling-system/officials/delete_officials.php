<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php");
    exit();
}

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Get the image path before deletion
        $image_query = $conn->query("SELECT image_path FROM barangay_official WHERE id = $id");
    
    if ($image_query && $image_query->num_rows > 0) {
        $image_data = $image_query->fetch_assoc();
        $image_path = $image_data['image_path'];
        
        // Delete from database
        $delete_sql = "DELETE FROM barangay_official WHERE id = $id";
        
        if ($conn->query($delete_sql)) {
            // Delete image file if it's not the default image
            if ($image_path != 'default.jpg' && file_exists("uploads/officials/" . $image_path)) {
                unlink("uploads/officials/" . $image_path);
            }
            
            $_SESSION['success'] = "Official deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting official: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Official not found!";
    }
    
    $conn->close();
    header("Location: barangay_officials.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request!";
    header("Location: barangay_officials.php");
    exit();
}
?>