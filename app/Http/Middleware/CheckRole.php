<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  string|array  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (Auth::guest()) {
            throw new HttpException(403, 'No autorizado. Debe iniciar sesión para acceder a esta página.');
        }

        // Flatten pipe-separated roles (e.g. 'admin|editor')
        $roles = collect($roles)
            ->flatMap(fn ($role) => explode('|', $role))
            ->toArray();

        foreach ($roles as $role) {
            if (Auth::user()->hasRole(trim($role))) {
                return $next($request);
            }
        }

        throw new HttpException(403, 'Acceso denegado. No tienes permisos suficientes para acceder a esta página.');
    }
}