<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario está logueado y necesita cambiar la contraseña
        if (Auth::check() && Auth::user()->must_change_password) {

            // Evitar un bucle infinito si ya está en las rutas de cambio de contraseña o haciendo logout
            if (!$request->routeIs('password.change') && !$request->routeIs('password.update') && !$request->routeIs('logout')) {
                return redirect()->route('password.change');
            }
        }

        return $next($request);
    }
}
