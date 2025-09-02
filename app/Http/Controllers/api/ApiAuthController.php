<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class ApiAuthController extends Controller
{
    /**
     * Login contra el API externo
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

            Log::info('Usuario autenticado: ' . json_encode(session('user')));
            Log::info('Roles: ' . json_encode($permissionData['roles']));
            Log::info('Permisos: ' . json_encode($permissionData['permissions']));

            // Permisos válidos de la sidebar
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

            // Si el usuario tiene al menos 1 de esos permisos → entra
            $userPermissions = $permissionData['permissions'];
            $hasAccess = count(array_intersect($validPermissions, $userPermissions)) > 0;

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