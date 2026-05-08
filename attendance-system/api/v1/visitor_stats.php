<?php
/**
 * Backward Compatibility Wrapper - visitor_stats.php
 * 
 * OLD ENDPOINT: /api/visitor_stats.php?filter={filter}
 * NEW ENDPOINT: /api/visitors/stats.php?filter={filter}
 * 
 * This file maintains backward compatibility by routing to the new modular structure.
 */

// Route to new modular visitors stats endpoint
require_once __DIR__ . "/visitors/stats.php";