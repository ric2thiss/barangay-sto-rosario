<?php
/**
 * Test script to verify BASE_URL detection
 * Run this from: http://localhost/attendance-system/test_base_url.php
 * Or: http://localhost/attendance/test_base_url.php
 */

require_once __DIR__ . "/config/app.config.php";

echo "<h2>BASE_URL Detection Test</h2>";
echo "<pre>";
echo "BASE_URL: " . BASE_URL . "\n\n";
echo "Server Variables:\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "\n";
echo "Expected login URL: " . BASE_URL . "/auth/login.php\n";
echo "Expected logout URL: " . BASE_URL . "/auth/logout.php\n";
echo "</pre>";
