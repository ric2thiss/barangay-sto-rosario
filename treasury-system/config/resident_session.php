<?php
session_start();

// Prevent cached authenticated pages from being shown after logout.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['resident_id'])) {
    header("Location: /resident/login.php");
    exit;
}
