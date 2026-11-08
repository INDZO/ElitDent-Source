<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $role = Auth::user()->Role ?? null;

        if (!$role || !in_array($role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
