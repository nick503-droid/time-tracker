<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Revisa si el usuario tiene el rol permitido para entrar a la ruta.
     */

    public function handle(Request $request, Closure $next, $role)
    {
        // 1. Si no ha iniciado sesión, mándalo al login de inmediato
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // 2. Si ya inició sesión pero no tiene el rol correcto, mándalo a su dashboard
        if (Auth::user()->role !== $role) {
            return redirect()->route('employee.dashboard')->withErrors('Acceso denegado. Área exclusiva de administración.');
        }

        // 3. Si todo está bien, déjalo pasar
        return $next($request);
    }


}
