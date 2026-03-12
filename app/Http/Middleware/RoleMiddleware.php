<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage: Route::middleware('role:admin,miembro')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role;

        // Check if user has any of the allowed roles
        foreach ($roles as $role) {
            $enumRole = UserRole::tryFrom($role);
            if ($enumRole && $userRole === $enumRole) {
                return $next($request);
            }
        }

        // Admin always passes
        if ($userRole === UserRole::Admin) {
            return $next($request);
        }

        abort(403, 'No tienes permisos para acceder a esta sección.');
    }
}
