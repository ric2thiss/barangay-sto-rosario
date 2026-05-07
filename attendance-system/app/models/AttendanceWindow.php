<?php

require_once __DIR__ . '/Model.php';

class AttendanceWindow extends Model {
    protected $table = "attendance_windows";
    protected $primaryKey = "window_id";
    protected $fillable = ["label", "start_time", "end_time", "late_grace_minutes"];
}
