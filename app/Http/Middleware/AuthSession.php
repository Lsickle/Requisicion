<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AuthSession extends Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Session::has('api_token') || !Session::has('user')) {
            return redirect('/')->with('error', 'Por favor inicia sesión');
        }

        return $next($request);
    }
}