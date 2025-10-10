<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

/**
 * ApiAuthController
 *
 * Controlador para autenticación contra el servicio externo VPL_CORE.
 * - login: valida credenciales, llama al endpoint externo, guarda token y datos de usuario en sesión,
 *   extrae roles y permisos y redirige según permisos disponibles.
 * - logout: borra la sesión local y notifica al servicio externo para invalidar el token.
 */
class ApiAuthController extends Controller
{
    /**
     * Login contra el API externo
     *
     * Valida los campos del request (email/password), realiza la petición POST al endpoint
     * de autenticación externo y maneja resultados:
     *  - guarda token y datos de usuario en sesión
     *  - extrae roles y permisos con PermissionHelper
     *  - normaliza permisos y decide si redirigir a la vista de requisiciones
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $apiurl = env('VPL_CORE') . '/api/auth/login';

        try {
            $response = Http::withOptions([
                'force_ip_resolve' => 'v4', // Forzar IPv4
                'dns_cache_timeout' => 10, // Timeout de cache DNS
                'connect_timeout' => 30, // Timeout de conexión
                'timeout' => 30, // Timeout general
            ])
            ->withoutVerifying()
            ->post($apiurl, [
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if ($response->failed()) {
                return back()->withErrors([
                    'email' => 'Credenciales inválidas o error de conexión con API externo'
                ])->withInput();
            }

            $data = $response->json();
            $userData = $data['user'] ?? [];

            // Guardar token y usuario en sesión INCLUYENDO LA OPERACIÓN
            session([
                'api_token'   => $data['access_token'] ?? null,
                'user'        => $userData,
                'user.id'     => $userData['id'] ?? null,
                'user.name'   => $userData['name'] ?? ($userData['email'] ?? 'Usuario'),
                'user.email'  => $userData['email'] ?? null,
                'user.operaciones' => $userData['operaciones'] ?? 'Operación no definida',
            ]);

            // Extraer roles y permisos usando helper
            $permissionData = PermissionHelper::extractRolesAndPermissionsFromUserData($userData);
            Session::put('user_roles', $permissionData['roles']);
            Session::put('user_permissions', $permissionData['permissions']);

            // Normalizar (minúsculas y sin espacios extremos) para comparación robusta
            $normalize = function($txt){
                $t = mb_strtolower(trim($txt), 'UTF-8');
                // reemplazos básicos de acentos comunes
                $t = str_replace(['á','é','í','ó','ú','ä','ë','ï','ö','ü'], ['a','e','i','o','u','a','e','i','o','u'], $t);
                return $t;
            };

            // Permisos válidos de la sidebar (originales)
            $validPermissions = [
                'crear requisicion',
                'ver requisicion',
                'solicitar producto',
                'crear oc',
                'ver oc',
                'ver producto',
                'Dashboard',
                'Requisiciones en curso',
                'Aprobar requisicion',
                'Total requisiciones',
                'requisicionesxorden',
            ];

            $validNormalized = array_map($normalize, $validPermissions);
            $userPermissionsNormalized = array_map($normalize, $permissionData['permissions']);

            $hasAccess = count(array_intersect($validNormalized, $userPermissionsNormalized)) > 0;

            Log::info('Permisos normalizados', [
                'valid' => $validNormalized,
                'user' => $userPermissionsNormalized,
                'hasAccess' => $hasAccess
            ]);

            if ($hasAccess) {
                return redirect()->route('requisiciones.menu');
            }

            return redirect()->route('index')->with('error', 'No tienes permisos para acceder al sistema');

        } catch (ConnectionException $e) {
            Log::error('Connection error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'Error de conexión con el servidor. Por favor intenta más tarde.'
            ])->withInput();
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'Error interno del sistema.'
            ])->withInput();
        }
    }

    /**
     * Logout (elimina la sesión)
     *
     * Borra todas las claves de sesión relacionadas con la autenticación y, si existe
     * un token, notifica al servicio externo para invalidarlo.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $token = session('api_token');

        $request->session()->forget([
            'api_token',
            'user',
            'user_roles',
            'user_permissions',
            'user.id',
            'user.name',
            'user.email',
            'user.operaciones',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($token) {
            Http::withoutVerifying()->withToken($token)->post(env('VPL_CORE') . '/api/auth/logout');
        }

        return redirect()->route('index')->with('logout_success', 'Has cerrado sesión correctamente.');
    }
}