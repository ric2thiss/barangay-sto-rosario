<?php

require_once __DIR__ . '/../../app/models/Model.php';

class Position extends Model {
    protected $table = "position";
    protected $primaryKey = "position_id";
    protected $fillable = ["position_name"];
}