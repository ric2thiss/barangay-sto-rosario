<?php

class NonResidentService extends Model {
    protected $table = 'non_resident_services';
    protected $fillable = ['service_id', 'service_name', 'is_allowed'];
}
