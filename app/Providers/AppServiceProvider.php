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
                
                // Obtener roles desde la API si tenemos token
                if (isset($user['token'])) {
                    $this->getUserRolesAndPermissions($user['token']);
                }
            }
        });
    }

    /**
     * Obtener roles y permisos desde la API y guardar en sesión
     */
    protected function getUserRolesAndPermissions(string $token): void
    {
        // Solo hacer la petición si no tenemos los datos en sesión
        if (!Session::has('user_roles')) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])->timeout(10)->get('https://vpl-nexus-core-test-testing.up.railway.app/api/todos/roles-permisos');

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Guardar en sesión para usar en middlewares y controllers
                    Session::put('user_roles', $data['roles'] ?? []);
                    Session::put('user_permissions', $data['permisos'] ?? []);
                    
                } else {
                    Log::warning('Error al obtener roles y permisos', [
                        'status' => $response->status()
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error('Excepción al obtener roles y permisos: ' . $e->getMessage());
            }
        }
    }
}