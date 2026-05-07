<?php

require_once __DIR__ . '/Model.php';

class CivilStatus extends Model {
    protected $table = "civil_status";
    protected $primaryKey = "civil_status_id";
    protected $fillable = ["status_name"];
}
