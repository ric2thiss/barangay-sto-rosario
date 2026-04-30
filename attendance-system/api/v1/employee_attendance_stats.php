<?php
/**
 * Backward Compatibility Wrapper - employee_attendance_stats.php
 * 
 * OLD ENDPOINT: /api/employee_attendance_stats.php?filter={filter}
 * NEW ENDPOINT: /api/attendance/stats.php?filter={filter}
 * 
 * This file maintains backward compatibility by routing to the new modular structure.
 */

// Route to new modular attendance stats endpoint
require_once __DIR__ . "/attendance/stats.php";