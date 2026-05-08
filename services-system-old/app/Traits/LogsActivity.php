<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('Created');
        });

        static::updated(function ($model) {
            $model->logActivity('Updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('Deleted');
        });
    }

    public function logActivity($action)
    {
        if (Auth::check()) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action.' '.class_basename($this),
                'module' => class_basename($this),
                'reference_id' => $this->getKey(),
                'timestamp' => now(),
                'ip_address' => Request::ip(),
            ]);
        }
    }
}
