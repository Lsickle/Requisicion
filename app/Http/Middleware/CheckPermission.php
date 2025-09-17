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

        // Permisos requeridos: aceptar separados por "|" o múltiples argumentos
        $required = [];
        foreach ($permissions as $perm) {
            foreach (explode('|', (string) $perm) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $required[] = mb_strtolower($p, 'UTF-8');
                }
            }
        }

        if (empty($required)) {
            return $next($request);
        }

        $authorized = count(array_intersect($required, $userPermissions)) > 0;
        if (!$authorized) {
            return redirect()->route('index')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}