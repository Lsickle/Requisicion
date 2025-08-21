<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Verifica si hay sesión de usuario y token
        if (!Session::has('api_token') || !Session::has('user')) {
            return redirect('/')->with('error', 'Por favor inicia sesión');
        }

        return $next($request);
    }
}
