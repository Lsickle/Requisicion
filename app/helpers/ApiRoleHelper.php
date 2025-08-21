<?php

if (!function_exists('hasApiRole')) {
    function hasApiRole($roleName) {
        $userRoles = session('user_roles', []);
        return in_array($roleName, $userRoles);
    }
}

if (!function_exists('hasAnyApiRole')) {
    function hasAnyApiRole($roleNames) {
        $userRoles = session('user_roles', []);
        foreach ((array)$roleNames as $roleName) {
            if (in_array($roleName, $userRoles)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('hasApiPermission')) {
    function hasApiPermission($permissionName) {
        $userPermissions = session('user_permissions', []);
        return in_array($permissionName, $userPermissions);
    }
}

if (!function_exists('hasAnyApiPermission')) {
    function hasAnyApiPermission($permissionNames) {
        $userPermissions = session('user_permissions', []);
        foreach ((array)$permissionNames as $permissionName) {
            if (in_array($permissionName, $userPermissions)) {
                return true;
            }
        }
        return false;
    }
}