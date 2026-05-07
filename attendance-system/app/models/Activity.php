<?php
require_once __DIR__ . '/Model.php';

class Activity extends Model {
    protected $table = 'activities';
    protected $fillable = [
        'name',
        'description',
        'source',
        'external_id',
        'activity_date',
        'created_at',
        'updated_at',
    ];
}
