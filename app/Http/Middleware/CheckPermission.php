<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $raw = session('user_permissions', []);
        $userPermissions = [];

        if (!is_array($raw)) {
            $raw = [$raw];
        }

        foreach ($raw as $item) {
            $perm = '';

            if (is_string($item)) {
                $perm = $item;
            } elseif (is_array($item)) {
                $perm = $item['name']
                    ?? $item['permission']
                    ?? $item['permiso']
                    ?? $item['permission_name']
                    ?? $item['slug']
                    ?? '';
            } elseif (is_object($item)) {
                $perm = $item->name
                    ?? ($item->permission ?? null)
                    ?? ($item->permiso ?? null)
                    ?? ($item->permission_name ?? null)
                    ?? ($item->slug ?? null)
                    ?? '';
            }

            if (is_string($perm)) {
                $perm = trim(mb_strtolower($perm, 'UTF-8'));
                if ($perm !== '') {
                    $userPermissions[] = $perm;
                }
            }
        }

        // Obtener roles del usuario
        $userRoles = session('user_roles', []);
        if (!is_array($userRoles)) {
            $userRoles = [$userRoles];
        }
        $userRoles = array_map(fn($r) => mb_strtolower(trim($r, 'UTF-8')), $userRoles);

        // Permisos requeridos: aceptar separados por "|" o múltiples argumentos
        // Soporta formato "role:admin" para verificar roles
        $required = [];
        $requiredRoles = [];
        foreach ($permissions as $perm) {
            foreach (explode('|', (string) $perm) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    if (str_starts_with($p, 'role:')) {
                        $requiredRoles[] = mb_substr($p, 5);
                    } elseif (in_array(mb_strtolower($p, 'UTF-8'), ['admin', 'compras', 'solicitante'])) {
                        $requiredRoles[] = mb_strtolower($p, 'UTF-8');
                    } else {
                        $required[] = $p;
                    }
                }
            }
        }

        if (empty($required) && empty($requiredRoles)) {
            return $next($request);
        }

        // Verificar permisos
        $hasPermission = count(array_intersect($required, $userPermissions)) > 0;

        // Verificar roles si se especificaron
        $hasRole = false;
        if (!empty($requiredRoles)) {
            $hasRole = count(array_intersect($requiredRoles, $userRoles)) > 0;
        }

        $authorized = $hasPermission || $hasRole;
        if (!$authorized) {
            if ($request->expectsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
                return response()->json(['message' => 'No tienes permisos para realizar esta acción'], 403);
            }
            return redirect()->route('index')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}