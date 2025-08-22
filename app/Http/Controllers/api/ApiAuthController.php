<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\Log;

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

        // Petición al API externo (ignora verificación SSL en local)
        $response = Http::withoutVerifying()->post('https://vpl-nexus-core-test-testing.up.railway.app/api/auth/login', [
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if ($response->failed()) {
            return back()->withErrors([
                'email' => 'Credenciales inválidas o error de conexión con API externo'
            ])->withInput();
        }

        $data = $response->json();

        // Extraer datos del usuario
        $userData = $data['user'] ?? [];

        // Guardar token y usuario en la sesión
        session([
            'api_token' => $data['access_token'] ?? null,
            'user' => $userData,
            'user.id' => $userData['id'] ?? null,
            'user.name' => $userData['name'] ?? ($userData['email'] ?? 'Usuario'),
            'user.email' => $userData['email'] ?? null,
        ]);

        // Extraer roles y permisos usando el helper
        $permissionData = PermissionHelper::extractRolesAndPermissionsFromUserData($userData);
        
        Session::put('user_roles', $permissionData['roles']);
        Session::put('user_permissions', $permissionData['permissions']);

        // Debug: Log para verificar los datos
        Log::info('User roles from API: ' . json_encode($permissionData['roles']));
        Log::info('User permissions from API: ' . json_encode($permissionData['permissions']));
        Log::info('Usuario autenticado: ' . json_encode(session('user')));

        // Redirigir según permisos
        if (in_array('crear requisiciones', $permissionData['permissions'])) {
            return redirect()->route('requisiciones.create');
        } elseif (in_array('ver requisicion', $permissionData['permissions'])) {
            return redirect()->route('requisiciones.menu');
        } else {
            return redirect()->route('index')->with('error', 'No tienes permisos para acceder al sistema');
        }
    }

    /**
     * Logout (elimina la sesión)
     */
    public function logout(Request $request)
    {
        // Eliminar sesión
        $request->session()->forget(['api_token', 'user', 'user_roles', 'user_permissions', 'user.id', 'user.name']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Opcional: logout en API externa
        if ($token = session('api_token')) {
            Http::withoutVerifying()->withToken($token)->post(
                'https://vpl-nexus-core-test-testing.up.railway.app/api/auth/logout'
            );
        }

        // Redirigir con mensaje flash
        return redirect()->route('index')->with('logout_success', 'Has cerrado sesión correctamente.');
    }
}
