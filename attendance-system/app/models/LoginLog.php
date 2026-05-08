<?php

require_once __DIR__ . '/Model.php';

class LoginLog extends Model
{
    protected $table = 'login_logs';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username',
        'success',
        'ip_address',
        'user_agent',
        'auth_source',
        'role',
        'message',
    ];
}
