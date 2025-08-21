<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compartir datos del usuario y roles en todas las vistas
        view()->composer('*', function ($view) {
            if (Session::has('user')) {
                $user = Session::get('user');
                $view->with('currentUser', $user);
                
                // Obtener roles y permisos desde el usuario en sesión
                $this->extractUserRolesAndPermissions($user);
            }
        });
    }

    /**
     * Extraer roles y permisos desde los datos del usuario
     */
    protected function extractUserRolesAndPermissions(array $user): void
    {
        // Solo procesar si no tenemos los datos en sesión y el usuario tiene roles
        if (!Session::has('user_roles') && isset($user['roles']) && is_array($user['roles'])) {
            $roles = [];
            $permissions = [];
            
            // Extraer roles y permisos de la estructura de la API
            foreach ($user['roles'] as $roleData) {
                if (isset($roleData['roles'])) {
                    $roles[] = $roleData['roles'];
                }
                
                if (isset($roleData['permisos']) && is_array($roleData['permisos'])) {
                    $permissions = array_merge($permissions, $roleData['permisos']);
                }
            }
            
            // Guardar en sesión para usar en middlewares y controllers
            Session::put('user_roles', array_unique($roles));
            Session::put('user_permissions', array_unique($permissions));
        }
    }
}