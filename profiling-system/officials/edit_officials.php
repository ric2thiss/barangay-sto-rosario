<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php");
    exit();
}

include("connection.php");
include 'hybrid_assets.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ======================
    // ID & VALIDATION
    // ======================
    $id = intval($_POST['id']);
    
    if ($id <= 0) {
        $_SESSION['error'] = "Invalid official ID.";
        header("Location: barangay_officials.php");
        exit();
    }

    // ======================
    // OFFICIAL INFORMATION
    // ======================
    $full_name    = $conn->real_escape_string(trim($_POST['full_name']));
    $chairmanship = $conn->real_escape_string(trim($_POST['chairmanship']));
    $position     = $conn->real_escape_string($_POST['position']);
    $term_start   = $conn->real_escape_string($_POST['term_start']);
    $term_end     = $conn->real_escape_string($_POST['term_end']);
    $status       = $conn->real_escape_string($_POST['status']);

    // Validate dates
    if (strtotime($term_end) < strtotime($term_start)) {
        $_SESSION['error'] = "Term End date cannot be earlier than Term Start date.";
        header("Location: barangay_officials.php");
        exit();
    }

    // ======================
    // IMAGE UPLOAD HANDLER
    // ======================
    $uploadDir = "uploads/officials/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $image_update_sql = "";
    
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Get old image to delete
            $old_img_query = "SELECT image_path FROM barangay_officials WHERE id = $id";
            $old_img_result = $conn->query($old_img_query);
            if ($old_img_result && $old_img_result->num_rows > 0) {
                $old_img_row = $old_img_result->fetch_assoc();
                $old_image = $uploadDir . $old_img_row['image_path'];
                if (file_exists($old_image) && $old_img_row['image_path'] != 'default.jpg') {
                    unlink($old_image);
                }
            }
            
            // Upload new image
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_image_name = time() . "_official_" . uniqid() . "." . $file_extension;
            $target_file = $uploadDir . $new_image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_update_sql = ", image_path = '" . $conn->real_escape_string($new_image_name) . "'";
            } else {
                $_SESSION['error'] = "Failed to upload image.";
                header("Location: barangay_officials.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid image type. Only JPG, PNG, and GIF allowed.";
            header("Location: barangay_officials.php");
            exit();
        }
    }

    // ======================
    // UPDATE QUERY
    // ======================
    $sql = "UPDATE barangay_officials SET 
                full_name = '$full_name',
                chairmanship = '$chairmanship',
                position = '$position',
                term_start = '$term_start',
                term_end = '$term_end',
                status = '$status',
                updated_at = NOW()
                $image_update_sql
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['success'] = "Official updated successfully!";
        header("Location: barangay_officials.php");
    } else {
        $_SESSION['error'] = "Error updating record: " . $conn->error;
        header("Location: barangay_officials.php");
    }

    $conn->close();
    exit();
}
?>