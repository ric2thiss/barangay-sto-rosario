<?php
require_once __DIR__ . '/Model.php';

class EventFine extends Model {
    protected $table = 'event_fines';
    protected $fillable = ['activity_id', 'fine_amount', 'created_at', 'updated_at'];
}
