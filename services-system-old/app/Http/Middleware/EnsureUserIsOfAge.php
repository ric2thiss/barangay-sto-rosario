<?php
// app/Http/Middleware/EnsureUserIsOfAge.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsOfAge
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()?->is_of_age) {
            abort(403, 'You must be 18 or older to access this page.');
        }

        return $next($request);
    }
}