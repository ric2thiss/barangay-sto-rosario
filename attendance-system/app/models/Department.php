<?php

require_once __DIR__ . '/../../app/models/Model.php';

class Department extends Model {
    protected $table = "departments";
    protected $primaryKey = "department_id";
    protected $fillable = ["department_name"];
}