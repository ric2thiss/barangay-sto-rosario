<?php

require_once __DIR__ . '/../../app/models/Model.php';

class Resident extends Model {
    protected $table = "`" . PROFILING_DB_NAME . "`.`residents`";
    protected $primaryKey = "id";
    // Read-only: attendance-system must not write profiling-system resident data
    protected $fillable = [];
}
