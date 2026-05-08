<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role'])) {
        if (in_array($_SESSION['role'], ['admin', 'super_admin'])) {
            header("Location: php/dashboard.php");
            exit();
        } else if ($_SESSION['role'] === 'customer') {
            header("Location: php/customer_dashboard.php");
            exit();
        }
    }
}

// Default redirect if not logged in or role not recognized
header("Location: php/landingpage.php");
exit();
?>