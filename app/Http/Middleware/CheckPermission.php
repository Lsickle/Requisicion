<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckPermission extends Middleware
{
    public function handle(Request $request, Closure $next, $permission): Response
    {
        $userPermissions = Session::get('user_permissions', []);
        $userRoles = Session::get('user_roles', []);
        
        Log::info('Checking permission: ' . $permission);
        Log::info('User permissions: ' . json_encode($userPermissions));
        Log::info('User roles: ' . json_encode($userRoles));

        // Si no tiene permisos, redirigir
        if (empty($userPermissions)) {
            Log::warning('No permissions found for user');
            return redirect('/')->with('error', 'Acceso no autorizado. No tienes permisos asignados.');
        }

        // Verificar si el usuario tiene el permiso requerido
        if (!in_array($permission, $userPermissions)) {
            Log::warning('Permission denied: ' . $permission);
            return back()->with('error', 'No tienes permisos para acceder a esta sección. Permiso requerido: ' . $permission);
        }

        Log::info('Permission granted: ' . $permission);
        return $next($request);
    }
}