<?php

if (!function_exists('hasApiRole')) {
    function hasApiRole($roleName) {
        $userRoles = session('user_roles', []);
        foreach ($userRoles as $role) {
            if (isset($role['name']) && $role['name'] === $roleName) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('hasAnyApiRole')) {
    function hasAnyApiRole($roleNames) {
        $userRoles = session('user_roles', []);
        foreach ($userRoles as $role) {
            if (isset($role['name']) && in_array($role['name'], (array)$roleNames)) {
                return true;
            }
        }
        return false;
    }
}