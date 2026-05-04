<?php
$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop and recreate DB
$conn->query("DROP DATABASE IF EXISTS `profiling-system`");
$conn->query("CREATE DATABASE `profiling-system`");
$conn->select_db("profiling-system");

// Execute SQL dump
$sql = file_get_contents("c:\\xampp\\htdocs\\treasury-system\\sto_rosario (25).sql");

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Prepare next
    } while ($conn->more_results() && $conn->next_result());
    echo "SQL import successful.\n";
} else {
    echo "Error importing SQL: " . $conn->error . "\n";
}

$conn->close();
