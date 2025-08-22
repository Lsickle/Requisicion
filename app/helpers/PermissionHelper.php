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

    public static function setUserData($roles, $permissions, $userData)
    {
        Session::put('user_roles', $roles);
        Session::put('user_permissions', $permissions);

        // Guardar también datos básicos del usuario
        if (isset($userData['id'])) {
            Session::put('user_id', $userData['id']);
        }
        if (isset($userData['name'])) {
            Session::put('user_name', $userData['name']);
        }
        if (isset($userData['email'])) {
            Session::put('user_email', $userData['email']);
        }
        if (isset($userData['access_token'])) {
            Session::put('access_token', $userData['access_token']);
        }

        Session::save();
    }

    public static function clearUserData()
    {
        Session::forget([
            'user_roles', 
            'user_permissions', 
            'user_id', 
            'user_name', 
            'user_email',
            'access_token'
        ]);
    }

    public static function extractRolesAndPermissionsFromUserData($userData)
    {
        $roles = [];
        $permissions = [];
        
        if (isset($userData['roles']) && is_array($userData['roles'])) {
            foreach ($userData['roles'] as $roleData) {
                if (isset($roleData['roles'])) {
                    $roles[] = $roleData['roles'];
                }
                if (isset($roleData['permisos']) && is_array($roleData['permisos'])) {
                    foreach ($roleData['permisos'] as $permiso) {
                        $permissions[] = $permiso;
                    }
                }
            }
        }
        
        return [
            'roles' => array_unique($roles),
            'permissions' => array_unique($permissions),
        ];
    }

    /**
     * Devuelve el nombre del usuario según el ID.
     * Primero busca en sesión, si no lo encuentra intenta llamar la API.
     */
    public static function getUserNameById($id)
    {
        if (Session::get('user_id') == $id) {
            return Session::get('user_name');
        }

        try {
            $client = new \GuzzleHttp\Client();
            $token = Session::get('access_token');

            if (!$token) {
                return 'Usuario Desconocido';
            }

            $response = $client->get("https://vpl-nexus-core-test-testing.up.railway.app/api/users/{$id}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            return $data['name'] ?? 'Usuario Desconocido';
        } catch (\Exception $e) {
            return 'Usuario Desconocido';
        }
    }
}
