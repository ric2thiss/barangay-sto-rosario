<?php

require_once __DIR__ . '/../../app/models/Model.php';

class Resident extends Model {
    protected static $pdo = null; // Isolated PDO for profiling-system
    protected $table = "`residents`";
    protected $primaryKey = "id";
    // Read-only: attendance-system must not write profiling-system resident data
    protected $fillable = [];
}
