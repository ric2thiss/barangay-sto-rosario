<?php
require_once __DIR__ . '/../../app/models/Model.php';

class VerificationLog extends Model {
    protected $table = "verification_log";
    protected $fillable = ["employee_id", "status", "device_id", "ip_address"];
}
