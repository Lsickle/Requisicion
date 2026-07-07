<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Estatus_Requisicion;
use App\Observers\EstatusRequisicionObserver;
use App\Models\OrdenCompra;
use App\Observers\OrdenCompraObserver;
use Illuminate\Support\Facades\URL;

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
        
        // Registrar observer para enviar correo cuando cambie estatus
        Estatus_Requisicion::observe(EstatusRequisicionObserver::class);

        // Registrar observer para OrdenCompra
        if (class_exists(OrdenCompra::class) && class_exists(OrdenCompraObserver::class)) {
            OrdenCompra::observe(OrdenCompraObserver::class);
        }

        // Forzar esquema https cuando la URL de la app use https
        // o cuando se establezca la variable FORCE_HTTPS=true en el entorno.
        try {
            $appUrl = config('app.url') ?? env('APP_URL');
            $force = env('FORCE_HTTPS', false);
            if ((is_string($appUrl) && str_starts_with($appUrl, 'https')) || $force) {
                URL::forceScheme('https');
            }
        } catch (\Throwable $e) {
            // noop
        }
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