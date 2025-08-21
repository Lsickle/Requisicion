<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Compartir permisos con todas las vistas
        View::composer('*', function ($view) {
            $userPermissions = Session::get('user_permissions', []);
            $userRole = Session::get('user_role');
            
            $view->with([
                'userPermissions' => $userPermissions,
                'userRole' => $userRole
            ]);
        });
    }

    public function register()
    {
        //
    }
}