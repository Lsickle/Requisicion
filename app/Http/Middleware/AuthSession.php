<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class AuthSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // DEBUG: Verificar que el middleware se está ejecutando
        Log::info('AuthSession middleware ejecutándose para: ' . $request->path());
        Log::info('Session tiene api_token: ' . (Session::has('api_token') ? 'Sí' : 'No'));
        Log::info('Session tiene user: ' . (Session::has('user') ? 'Sí' : 'No'));

        // Verifica si hay sesión de usuario y token
        if (!Session::has('api_token') || !Session::has('user')) {
            Log::warning('Usuario no autenticado intentando acceder a: ' . $request->path());
            $response = redirect('/')->with('error', 'Por favor inicia sesión');
            // Forzar no-cache también en el redirect
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            return $response;
        }

        $response = $next($request);
        // Evitar que las páginas protegidas queden en caché/bfcache (back-forward cache)
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }
}