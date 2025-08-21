<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;

class PermissionHelper
{
    public static function hasPermission($permission)
    {
        $userPermissions = Session::get('user_permissions', []);
        return in_array($permission, $userPermissions);
    }

    public static function hasAnyPermission($permissions)
    {
        $userPermissions = Session::get('user_permissions', []);
        foreach ((array)$permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return true;
            }
        }
        return false;
    }

    public static function getUserRoles()
    {
        return Session::get('user_roles', []);
    }

    public static function hasRole($role)
    {
        $userRoles = Session::get('user_roles', []);
        return in_array($role, $userRoles);
    }

    public static function hasAnyRole($roles)
    {
        $userRoles = Session::get('user_roles', []);
        foreach ((array)$roles as $role) {
            if (in_array($role, $userRoles)) {
                return true;
            }
        }
        return false;
    }

    public static function getUserPermissions()
    {
        return Session::get('user_permissions', []);
    }

    public static function setUserData($roles, $permissions)
    {
        Session::put('user_roles', $roles);
        Session::put('user_permissions', $permissions);
        Session::save();
    }

    public static function clearUserData()
    {
        Session::forget(['user_roles', 'user_permissions']);
    }

    public static function extractRolesAndPermissionsFromUserData($userData)
    {
        $roles = [];
        $permissions = [];
        
        if (isset($userData['roles']) && is_array($userData['roles'])) {
            foreach ($userData['roles'] as $roleData) {
                // Extraer nombre del rol (campo "roles" en la API)
                if (isset($roleData['roles'])) {
                    $roles[] = $roleData['roles'];
                }
                
                // Extraer permisos del rol (campo "permisos" en español en la API)
                if (isset($roleData['permisos']) && is_array($roleData['permisos'])) {
                    foreach ($roleData['permisos'] as $permiso) {
                        // Los permisos vienen como strings directos, no como objetos
                        $permissions[] = $permiso;
                    }
                }
            }
        }
        
        // Eliminar duplicados
        $roles = array_unique($roles);
        $permissions = array_unique($permissions);

        return [
            'roles' => $roles,
            'permissions' => $permissions
        ];
    }
}