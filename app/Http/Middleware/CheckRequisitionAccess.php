<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckRequisitionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión primero');
        }

        // Obtener roles del usuario desde la sesión (que vienen de la API)
        $userRoles = Session::get('user_roles', []);
        
        // Verificar si tiene los roles permitidos
        $allowedRoles = ['supervisor', 'coordinador'];
        $hasAccess = $this->checkUserRoles($userRoles, $allowedRoles);

        if (!$hasAccess) {
            abort(403, 'Acceso denegado. Solo supervisores y coordinadores pueden crear requisiciones.');
        }

        return $next($request);
    }

    /**
     * Verificar si el usuario tiene alguno de los roles permitidos
     */
    protected function checkUserRoles(array $userRoles, array $allowedRoles): bool
    {
        foreach ($userRoles as $role) {
            // Verificar si el rol tiene el nombre permitido
            if (isset($role['name']) && in_array($role['name'], $allowedRoles)) {
                return true;
            }
            
            // También verificar si el rol tiene permisos específicos
            if (isset($role['permissions'])) {
                foreach ($role['permissions'] as $permission) {
                    if (isset($permission['name']) && $permission['name'] === 'crear requisiciones') {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
}