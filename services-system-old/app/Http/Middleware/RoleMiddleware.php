<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
public function handle(Request $request, Closure $next, string ...$roles): mixed
{
    $userRole = auth()->user()?->role?->role_name;

    if (!$userRole || !in_array($userRole, $roles)) {
        return response()->view('errors.403', [], 403);
    }

    return $next($request);
}
}