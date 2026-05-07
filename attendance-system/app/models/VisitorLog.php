<?php

require_once __DIR__ . '/Model.php';

class VisitorLog extends Model {
    protected $table = "visitor_logs";
    protected $primaryKey = "id";
    protected $fillable = [
        "resident_id",
        "first_name",
        "middle_name",
        "last_name",
        "birthdate",
        "address",
        "purpose",
        "is_resident",
        "had_booking",
        "booking_id"
    ];
}
