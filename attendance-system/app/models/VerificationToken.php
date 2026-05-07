<?php
require_once __DIR__ . '/../../app/models/Model.php';

class VerificationToken extends Model
{
    protected $table = "verification_tokens";
    protected $fillable = ["employee_id", "status", "token"];
}
