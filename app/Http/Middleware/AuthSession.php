<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthSession
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();
        
        // Rutas públicas sin verificación
        if (in_array($path, ['/', 'index', 'login', 'auth/api-login', 'logout', 'api/'])) {
            return $next($request);
        }
        
        // Verificar si existe sesión de API (puede estar en cualquier formato válido)
        $hasApiToken = Session::has('api_token') && !empty(Session::get('api_token'));
        $hasUser = Session::has('user') && !empty(Session::get('user'));
        
        // Si hay sesión válida, renovarla y continuar
        if ($hasApiToken && $hasUser) {
            Session::put('last_activity', time());
            Session::save();
            return $next($request);
        }
        
        // Si no hay sesión válida, dependiendo del tipo de request
        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json([
                'message' => 'Sesión expirada. Por favor inicia sesión nuevamente.'
            ], 401);
        }
        
        return redirect('/');
    }
}