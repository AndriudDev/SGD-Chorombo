<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheControlMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Impide que el navegador almacene en caché las páginas del sistema (OBS-04):
     * tras un Logout, el botón "Atrás" no debe mostrar datos sensibles sin pasar
     * por el servidor, que rechazará la petición por falta de sesión.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, private'
        );

        return $response;
    }
}
