<?php

require_once __DIR__ . '/../../app/models/Model.php';

class ResidentFingerprints extends Model {
    protected $table = "resident_fingerprints";
    protected $fillable = ["resident_id", "template"];
}

